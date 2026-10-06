<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\DiagnosticRequestModel;
use App\Models\NotificationModel;
use App\Models\PaymentModel;
use App\Models\ServiceModel;
use App\Models\UserModel;

class Radiologist extends BaseController
{
    private function checkAuth()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('login'));
        }

        $role = session()->get('role');
        if ($role !== 'radiologist') {
            if ($role === 'admin') {
                return redirect()->to(base_url('admin/dashboard'));
            }
            if ($role === 'receptionist') {
                return redirect()->to(base_url('receptionist/dashboard'));
            }
            if ($role === 'medtech') {
                return redirect()->to(base_url('medtech/dashboard'));
            }
            return redirect()->to(base_url('login'));
        }
        return null;
    }

    private function departmentStatuses(): array
    {
        return [
            DiagnosticRequestModel::STATUS_IN_PROGRESS,
            DiagnosticRequestModel::STATUS_COMPLETED,
            DiagnosticRequestModel::STATUS_RELEASED,
        ];
    }

    private function getAvailableXrayServices()
    {
        $serviceModel = new ServiceModel();

        $services = $serviceModel->getXrayServices();

        $categoryMap = [
            'Chest'             => [
                'chest', 'thoracic bony', 'thoracic bony cage',
            ],
            'Skull & Face'      => [
                'skull', 'towner', 'orbit', 'nasap',
                'paranasal', 'pns', 'neck', 'cervical',
            ],
            'Spine & Pelvis'    => [
                'thoracic vert', 'thoracolumbar', 'lumbosacral',
                'lumbar', 'whole spine', 'pelvis', 'frog leg',
            ],
            'Upper Extremities' => [
                'shoulder', 'humerus', 'arm', 'elbow',
                'forearm', 'radius', 'wrist',
                'hand', 'finger', 'metacarpal',
            ],
            'Lower Extremities' => [
                'leg', 'knee', 'foot', 'ankle',
            ],
            'Abdomen'           => [
                'abdomen',
            ],
        ];

        $categoryCounts = [];
        foreach ($categoryMap as $cat => $_) {
            $categoryCounts[$cat] = 0;
        }
        $categoryCounts['Other'] = 0;

        foreach ($services as $service) {
            $name    = strtolower((string) ($service['service_name'] ?? ''));
            $matched = false;

            foreach ($categoryMap as $cat => $keywords) {
                foreach ($keywords as $kw) {
                    if (strpos($name, $kw) !== false) {
                        $categoryCounts[$cat]++;
                        $matched = true;
                        break 2;
                    }
                }
            }

            if (!$matched) {
                $categoryCounts['Other']++;
            }
        }

        $categoryCounts = array_filter($categoryCounts);

        return [
            'services'       => $services,
            'count'          => count($services),
            'categoryCounts' => $categoryCounts,
        ];
    }

    public function dashboard()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $model        = new DiagnosticRequestModel();
        $paymentModel = new PaymentModel();
        $type         = DiagnosticRequestModel::TYPE_XRAY;

        $departmentStatuses = $this->departmentStatuses();

        $data['pending']    = $model
            ->where('type', $type)
            ->where('status', DiagnosticRequestModel::STATUS_IN_PROGRESS)
            ->countAllResults();

        $model->resetQuery();
        $data['processing'] = $data['pending'];

        $model->resetQuery();
        $data['completed'] = $model
            ->where('type', $type)
            ->where('status', DiagnosticRequestModel::STATUS_COMPLETED)
            ->countAllResults();

        $model->resetQuery();
        $data['released'] = $model
            ->where('type', $type)
            ->where('status', DiagnosticRequestModel::STATUS_RELEASED)
            ->countAllResults();

        $model->resetQuery();
        $data['total'] = $model
            ->where('type', $type)
            ->whereIn('status', $departmentStatuses)
            ->countAllResults();

        $model->resetQuery();
        $data['recent_examinations'] = $model
            ->where('type', $type)
            ->whereIn('status', $departmentStatuses)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->findAll();

        $model->resetQuery();
        $data['pending_examinations'] = $model
            ->where('type', $type)
            ->where('status', DiagnosticRequestModel::STATUS_IN_PROGRESS)
            ->orderBy('created_at', 'ASC')
            ->limit(5)
            ->findAll();

        $data['today_revenue']   = $paymentModel->getXrayRevenueToday();
        $data['monthly_revenue'] = $paymentModel->getXrayRevenueThisMonth();

        $data['servicesCatalog'] = $this->getAvailableXrayServices();

        // KPI sparklines — same look as the Admin dashboard.
        $data['kpiTrends'] = $this->getKpiTrends(14, (int) $data['total']);

        return view('Radiologist/dashboard', $data);
    }

    /**
     * Daily series for the KPI sparklines on the Radiologist dashboard.
     * Oldest day first, ending today.
     */
    private function getKpiTrends(int $days, int $totalStudies): array
    {
        $db    = db_connect();
        $start = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        $end   = date('Y-m-d');
        $type  = DiagnosticRequestModel::TYPE_XRAY;

        $dates = [];
        for ($i = 0; $i < $days; $i++) {
            $dates[] = date('Y-m-d', strtotime($start . ' +' . $i . ' days'));
        }

        $series = function (string $dateExpr, string $valueExpr, array $where = []) use ($db, $dates, $start, $end, $type) {
            $b = $db->table('diagnostic_requests')
                ->select("$dateExpr AS d, $valueExpr AS v", false)
                ->where('type', $type)
                ->where("$dateExpr >=", $start)
                ->where("$dateExpr <=", $end);
            foreach ($where as $col => $val) {
                $b->where($col, $val);
            }
            $rows = $b->groupBy('d')->get()->getResultArray();

            $map = [];
            foreach ($rows as $r) {
                $map[$r['d']] = (float) $r['v'];
            }
            return array_map(fn($d) => $map[$d] ?? 0, $dates);
        };

        // Total studies: cumulative walk-back from today's total.
        $newTotal = $series('DATE(created_at)', 'COUNT(*)');
        $total    = [];
        $running  = $totalStudies - array_sum($newTotal);
        foreach ($newTotal as $n) {
            $running += $n;
            $total[]  = $running;
        }

        return [
            'dates'       => $dates,
            'pending'     => $series('DATE(created_at)', 'COUNT(*)', ['status' => 'in_progress']),
            'processing'  => $series('DATE(created_at)', 'COUNT(*)', ['status' => 'in_progress']),
            'completed'   => $series('DATE(updated_at)', 'COUNT(*)', ['status' => 'completed']),
            'released'    => $series('DATE(released_at)', 'COUNT(*)', ['status' => 'released']),
            'total'       => $total,

            // Revenue sparklines — read from the payments table.
            'revenue_today' => $this->getXrayRevenueSeries($dates, false),
            'revenue_month' => $this->getXrayRevenueSeries($dates, true),
        ];
    }

    /**
     * Daily paid revenue, or a running month-to-date cumulative total
     * when $cumulative is true.
     */
    private function getXrayRevenueSeries(array $dates, bool $cumulative): array
    {
        if (empty($dates)) { return []; }

        $db    = db_connect();
        $start = $dates[0];
        $end   = end($dates);

        $rows = $db->table('payments')
            ->select('DATE(payment_date) AS d, SUM(total_amount) AS v', false)
            ->where('payment_status', 'paid')
            ->where('DATE(payment_date) >=', $start)
            ->where('DATE(payment_date) <=', $end)
            ->groupBy('d')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[$r['d']] = (float) $r['v'];
        }

        $series = array_map(fn($d) => $map[$d] ?? 0, $dates);

        if (!$cumulative) {
            return $series;
        }

        // Month-to-date cumulative — resets on the first of the month.
        $monthStart = date('Y-m-01');
        $running    = 0;
        $out        = [];
        foreach ($dates as $i => $d) {
            if ($d < $monthStart) {
                $out[] = 0;
                continue;
            }
            $running += $series[$i];
            $out[]    = $running;
        }
        return $out;
    }

    public function examinations()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $model = new DiagnosticRequestModel();
        $type  = DiagnosticRequestModel::TYPE_XRAY;

        $departmentStatuses = $this->departmentStatuses();

        $data['examinations'] = $model
            ->where('type', $type)
            ->whereIn('status', $departmentStatuses)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $model->resetQuery();
        $data['counts'] = [
            'pending'    => $model
                ->where('type', $type)
                ->where('status', DiagnosticRequestModel::STATUS_IN_PROGRESS)
                ->countAllResults(),

            'processing' => $model
                ->where('type', $type)
                ->where('status', DiagnosticRequestModel::STATUS_IN_PROGRESS)
                ->countAllResults(),

            'completed'  => $model
                ->where('type', $type)
                ->where('status', DiagnosticRequestModel::STATUS_COMPLETED)
                ->countAllResults(),

            'released'   => $model
                ->where('type', $type)
                ->where('status', DiagnosticRequestModel::STATUS_RELEASED)
                ->countAllResults(),

            'total'      => $model
                ->where('type', $type)
                ->whereIn('status', $departmentStatuses)
                ->countAllResults(),
        ];

        return view('Radiologist/examinations', $data);
    }

    public function viewExamination($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $model = new DiagnosticRequestModel();
        $data['examination'] = $model->find($id);

        if (!$data['examination']) {
            return redirect()->to(base_url('radiologist/examinations'))
                            ->with('error', 'Examination not found');
        }

        return view('Radiologist/view_examination', $data);
    }

    public function updateStatus($id, $status)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $validStatuses = ['pending', 'in_progress', 'completed', 'released', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid status']);
        }

        try {
            $model = new DiagnosticRequestModel();
            $model->updateStatus($id, $status);

            return $this->response->setJSON(['success' => true, 'message' => 'Status updated successfully']);

        } catch (\Exception $e) {
            log_message('error', 'Update examination status error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error updating status']);
        }
    }

    public function saveDraft($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $findings       = $this->request->getPost('findings');
        $interpretation = $this->request->getPost('interpretation');

        try {
            $model       = new DiagnosticRequestModel();
            $examination = $model->find($id);

            if (!$examination) {
                return redirect()->to(base_url('radiologist/examinations'))
                                ->with('error', 'Examination not found');
            }

            if ($examination['status'] === 'released') {
                return redirect()->to(base_url('radiologist/examination/view/' . $id))
                                ->with('error', 'This report has already been released and cannot be edited.');
            }

            $update = [
                'findings'       => $findings,
                'interpretation' => $interpretation,
                'status'         => 'in_progress',
            ];

            if (empty($examination['radiologist_name'])) {
                $update['radiologist_name'] = session()->get('full_name') ?? 'Radiologist';
            }

            $model->update($id, $update);

            return redirect()->to(base_url('radiologist/examination/view/' . $id))
                            ->with('success', 'Draft saved. Complete the report when both sections are filled.');

        } catch (\Exception $e) {
            log_message('error', 'Save draft error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error saving draft');
        }
    }

    public function saveFindings($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $findings       = trim((string) $this->request->getPost('findings'));
        $interpretation = trim((string) $this->request->getPost('interpretation'));

        try {
            $model       = new DiagnosticRequestModel();
            $examination = $model->find($id);

            if (!$examination) {
                return redirect()->to(base_url('radiologist/examinations'))
                                ->with('error', 'Examination not found');
            }

            if ($examination['status'] === 'released') {
                return redirect()->to(base_url('radiologist/examination/view/' . $id))
                                ->with('error', 'This report has already been released and cannot be edited.');
            }

            $missing = [];
            if ($findings === '')       { $missing[] = 'Findings'; }
            if ($interpretation === '') { $missing[] = 'Impression'; }

            if (!empty($missing)) {
                return redirect()->to(base_url('radiologist/examination/view/' . $id))
                                ->with('error', 'Cannot complete this report: ' . implode(' and ', $missing) . ' must not be empty. Use Save draft to keep working.');
            }

            $update = [
                'findings'       => $findings,
                'interpretation' => $interpretation,
                'status'         => 'completed',
            ];

            if (empty($examination['radiologist_name'])) {
                $update['radiologist_name'] = session()->get('full_name') ?? 'Radiologist';
            }

            $model->update($id, $update);

            return redirect()->to(base_url('radiologist/examination/view/' . $id))
                            ->with('success', 'Report completed. You can now release it to the receptionist.');

        } catch (\Exception $e) {
            log_message('error', 'Save findings error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error saving report');
        }
    }

    public function notifications()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $notificationModel = new NotificationModel();

        $data['notifications'] = $notificationModel
            ->where('user_role', 'radiologist')
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $data['unread_count'] = $notificationModel
            ->where('user_role', 'radiologist')
            ->where('is_read', 0)
            ->countAllResults();

        $data['total_count'] = count($data['notifications']);

        return view('Radiologist/notifications', $data);
    }

    public function markNotificationRead($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        try {
            $notificationModel = new NotificationModel();
            $notificationModel->update($id, ['is_read' => 1]);

            return redirect()->to(base_url('radiologist/notifications'))
                            ->with('success', 'Notification marked as read');

        } catch (\Exception $e) {
            log_message('error', 'Mark notification read error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error marking notification as read');
        }
    }

    public function markAllRead()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        try {
            $notificationModel = new NotificationModel();
            $notificationModel->where('user_role', 'radiologist')
                             ->set(['is_read' => 1])
                             ->update();

            return redirect()->to(base_url('radiologist/notifications'))
                            ->with('success', 'All notifications marked as read');

        } catch (\Exception $e) {
            log_message('error', 'Mark all read error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error marking all as read');
        }
    }

    public function deleteNotification($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        try {
            $notificationModel = new NotificationModel();
            $notificationModel->delete($id);

            return redirect()->to(base_url('radiologist/notifications'))
                            ->with('success', 'Notification deleted');

        } catch (\Exception $e) {
            log_message('error', 'Delete notification error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error deleting notification');
        }
    }

    public function reports()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $model = new DiagnosticRequestModel();
        $type  = DiagnosticRequestModel::TYPE_XRAY;

        $departmentStatuses = $this->departmentStatuses();

        $model->resetQuery();
        $data['total_examinations'] = $model
            ->where('type', $type)
            ->whereIn('status', $departmentStatuses)
            ->countAllResults();

        $model->resetQuery();
        $data['pending_count'] = $model
            ->where('type', $type)
            ->where('status', DiagnosticRequestModel::STATUS_IN_PROGRESS)
            ->countAllResults();

        $model->resetQuery();
        $data['completed_count'] = $model
            ->where('type', $type)
            ->where('status', DiagnosticRequestModel::STATUS_COMPLETED)
            ->countAllResults();

        $model->resetQuery();
        $data['released_count'] = $model
            ->where('type', $type)
            ->where('status', DiagnosticRequestModel::STATUS_RELEASED)
            ->countAllResults();

        $data['weekly_data'] = $model->getWeeklyVolumeData();

        return view('Radiologist/reports', $data);
    }

    public function printResult($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $model = new DiagnosticRequestModel();
        $examination = $model->find($id);

        if (!$examination) {
            return redirect()->to(base_url('radiologist/examinations'))
                            ->with('error', 'Examination not found');
        }

        $data['examination'] = $examination;

        $radiologistName = trim((string) ($examination['radiologist_name'] ?? ''));

        if ($radiologistName === '') {
            $radiologistName = trim((string) (session()->get('full_name') ?? ''));
        }

        $data['radiologist_license'] = '';

        if ($radiologistName !== '') {
            $userModel = new UserModel();
            $radiologistUser = $userModel
                ->where('full_name', $radiologistName)
                ->where('role', 'radiologist')
                ->first();

            if ($radiologistUser && !empty($radiologistUser['prc_license'])) {
                $data['radiologist_license'] = trim((string) $radiologistUser['prc_license']);
            }
        }

        return view('Radiologist/print_result', $data);
    }

    public function uploadImage($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $model       = new DiagnosticRequestModel();
        $examination = $model->find($id);

        if (!$examination) {
            return redirect()->back()->with('error', 'Examination not found.');
        }

        if (($examination['status'] ?? '') === 'released') {
            return redirect()->back()->with('error', 'This report has already been released. Images can no longer be changed.');
        }

        $files = $this->request->getFileMultiple('xray_images');

        if (empty($files) || !is_array($files)) {
            return redirect()->back()->with('error', 'No images selected.');
        }

        $existing = [];
        if (!empty($examination['image_paths'])) {
            $decoded = json_decode($examination['image_paths'], true);
            if (is_array($decoded)) {
                $existing = array_values(array_filter($decoded, 'is_string'));
            }
        } elseif (!empty($examination['image_path'])) {
            $existing = [$examination['image_path']];
        }

        $remainingSlots = 5 - count($existing);
        if ($remainingSlots <= 0) {
            return redirect()->back()->with('error', 'This study already has the maximum of 5 images. Remove one before uploading another.');
        }

        $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExt  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $uploadDir = FCPATH . 'uploads/xray';

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            log_message('error', 'Upload directory could not be created: ' . $uploadDir);
            return redirect()->back()->with('error', 'Server could not prepare the upload directory.');
        }

        $added   = [];
        $skipped = 0;

        foreach ($files as $file) {
            if (count($added) >= $remainingSlots) {
                $skipped++;
                continue;
            }

            if (!$file || !$file->isValid() || $file->hasMoved()) {
                $skipped++;
                continue;
            }

            $mime = $file->getMimeType();
            $ext  = strtolower($file->getExtension());

            if (!in_array($mime, $allowedMime, true) || !in_array($ext, $allowedExt, true)) {
                $skipped++;
                continue;
            }

            $newName = 'xray_' . $id . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

            try {
                $file->move($uploadDir, $newName);
            } catch (\Exception $e) {
                log_message('error', 'Move failed for examination ' . $id . ': ' . $e->getMessage());
                $skipped++;
                continue;
            }

            $added[] = 'uploads/xray/' . $newName;
        }

        if (empty($added)) {
            return redirect()->back()->with('error', 'No valid image was uploaded. Accepted formats: JPEG, PNG, GIF, WEBP.');
        }

        $allPaths = array_merge($existing, $added);

        $model->update($id, [
            'image_path'  => $allPaths[0],
            'image_paths' => json_encode(array_values($allPaths)),
        ]);

        $count = count($added);
        $msg   = $count === 1
            ? 'Image uploaded successfully.'
            : $count . ' images uploaded successfully.';

        if ($skipped > 0) {
            $msg .= ' ' . $skipped . ' file' . ($skipped === 1 ? ' was' : 's were') . ' skipped (invalid type or over the 5-image limit).';
        }

        return redirect()->back()->with('success', $msg);
    }

    public function removeImage($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $path = (string) $this->request->getPost('image_path');
        if ($path === '') {
            return redirect()->back()->with('error', 'No image specified.');
        }

        $model       = new DiagnosticRequestModel();
        $examination = $model->find($id);

        if (!$examination) {
            return redirect()->back()->with('error', 'Examination not found.');
        }

        if (($examination['status'] ?? '') === 'released') {
            return redirect()->back()->with('error', 'This report has already been released. Images can no longer be changed.');
        }

        $paths = [];
        if (!empty($examination['image_paths'])) {
            $decoded = json_decode($examination['image_paths'], true);
            if (is_array($decoded)) {
                $paths = array_values(array_filter($decoded, 'is_string'));
            }
        } elseif (!empty($examination['image_path'])) {
            $paths = [$examination['image_path']];
        }

        $paths = array_values(array_filter($paths, static fn ($p) => $p !== $path));

        $normalized = str_replace('\\', '/', $path);
        if (str_starts_with($normalized, 'uploads/xray/')) {
            $full = FCPATH . $normalized;
            if (is_file($full)) {
                @unlink($full);
            }
        }

        $model->update($id, [
            'image_path'  => $paths[0] ?? null,
            'image_paths' => empty($paths) ? null : json_encode($paths),
        ]);

        return redirect()->back()->with('success', 'Image removed.');
    }

    public function releaseResult($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        try {
            $model       = new DiagnosticRequestModel();
            $examination = $model->find($id);

            if (!$examination) {
                return redirect()->to(base_url('radiologist/examinations'))
                                ->with('error', 'Examination not found');
            }

            if ($examination['status'] === 'released') {
                return redirect()->to(base_url('radiologist/examination/view/' . $id))
                                ->with('error', 'This result has already been released.');
            }

            $missing = [];
            if (trim((string) ($examination['findings'] ?? '')) === '')       { $missing[] = 'Findings'; }
            if (trim((string) ($examination['interpretation'] ?? '')) === '') { $missing[] = 'Impression'; }

            if (!empty($missing)) {
                return redirect()->to(base_url('radiologist/examination/view/' . $id))
                                ->with('error', 'Cannot release: ' . implode(' and ', $missing) . ' must be filled first.');
            }

            $update = [
                'status'      => 'released',
                'released_at' => date('Y-m-d H:i:s'),
            ];

            if (empty($examination['radiologist_name'])) {
                $update['radiologist_name'] = session()->get('full_name') ?? 'Radiologist';
            }

            $model->update($id, $update);

            return redirect()->to(base_url('radiologist/examinations'))
                            ->with('success', 'Result released. The receptionist can now print it.');

        } catch (\Exception $e) {
            log_message('error', 'Release result error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error releasing result');
        }
    }
}