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
            return redirect()->to((new NotificationModel())->dashboardFor($role));
        }
        return null;
    }

    /** An X-ray request by id; lab rows are never reachable from here. */
    private function findXray($id): ?array
    {
        return (new DiagnosticRequestModel())
            ->where('id', (int) $id)
            ->where('type', DiagnosticRequestModel::TYPE_XRAY)
            ->first();
    }

    private function departmentStatuses(): array
    {
        return [
            DiagnosticRequestModel::STATUS_IN_PROGRESS,
            DiagnosticRequestModel::STATUS_COMPLETED,
            DiagnosticRequestModel::STATUS_RELEASED,
        ];
    }

    /**
     * X-ray worklist counts from one grouped query.
     * pending = paid but not opened-and-saved by a radiologist yet,
     * processing = in reading (a radiologist has saved a draft).
     */
    private function departmentCounts(): array
    {
        return (new DiagnosticRequestModel())->getDepartmentCounts(DiagnosticRequestModel::TYPE_XRAY);
    }

    /**
     * ------------------------------------------------------------------
     * Payment precondition helper.
     * Mirrors the same guard used by MedTech.
     * ------------------------------------------------------------------
     */
    private function requestIsPaid(int $id, string $type = DiagnosticRequestModel::TYPE_XRAY): bool
    {
        return !empty((new PaymentModel())->getByRequest($id, $type));
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

        $data = $this->departmentCounts();

        $model->resetQuery();
        $data['recent_examinations'] = $model
            ->where('type', $type)
            ->whereIn('status', $departmentStatuses)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->findAll();
        $data['recent_examinations'] = DiagnosticRequestModel::withDepartmentStatus($data['recent_examinations']);

        $model->resetQuery();
        $data['pending_examinations'] = $model
            ->where('type', $type)
            ->where('status', DiagnosticRequestModel::STATUS_IN_PROGRESS)
            ->orderBy('created_at', 'ASC')
            ->limit(5)
            ->findAll();
        $data['pending_examinations'] = DiagnosticRequestModel::withDepartmentStatus($data['pending_examinations']);

        $data['today_revenue']   = $paymentModel->getXrayRevenueToday();
        $data['monthly_revenue'] = $paymentModel->getXrayRevenueThisMonth();

        $data['servicesCatalog'] = $this->getAvailableXrayServices();

        $data['kpiTrends'] = $this->getKpiTrends(14, (int) $data['total']);

        return view('Radiologist/dashboard', $data);
    }

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
                // a null value means $col is a raw SQL condition
                $val === null ? $b->where($col, null, false) : $b->where($col, $val);
            }
            $rows = $b->groupBy('d')->get()->getResultArray();

            $map = [];
            foreach ($rows as $r) {
                $map[$r['d']] = (float) $r['v'];
            }
            return array_map(fn($d) => $map[$d] ?? 0, $dates);
        };

        $newTotal = $series('DATE(created_at)', 'COUNT(*)');
        $total    = [];
        $running  = $totalStudies - array_sum($newTotal);
        foreach ($newTotal as $n) {
            $running += $n;
            $total[]  = $running;
        }

        return [
            'dates'       => $dates,
            'pending'     => $series('DATE(created_at)', 'COUNT(*)', [DiagnosticRequestModel::untouchedSql($type) => null]),
            'processing'  => $series('DATE(created_at)', 'COUNT(*)', ['status' => 'in_progress', 'NOT ' . DiagnosticRequestModel::untouchedSql($type) => null]),
            'completed'   => $series('DATE(updated_at)', 'COUNT(*)', ['status' => 'completed']),
            'released'    => $series('DATE(released_at)', 'COUNT(*)', ['status' => 'released']),
            'total'       => $total,
            'revenue_today' => $this->getXrayRevenueSeries($dates, false),
            'revenue_month' => $this->getXrayRevenueSeries($dates, true),
        ];
    }

    private function getXrayRevenueSeries(array $dates, bool $cumulative): array
    {
        if (empty($dates)) { return []; }

        $db    = db_connect();
        $start = $dates[0];
        $end   = end($dates);

        $rows = $db->table('payments')
            ->select('DATE(payment_date) AS d, SUM(amount_paid) AS v', false)
            ->where('payment_status', 'paid')
            ->where('request_type', DiagnosticRequestModel::TYPE_XRAY)
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

        $data['examinations'] = DiagnosticRequestModel::withDepartmentStatus($data['examinations']);
        $data['counts']       = $this->departmentCounts();

        return view('Radiologist/examinations', $data);
    }

    public function viewExamination($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $model = new DiagnosticRequestModel();
        $data['examination'] = $this->findXray($id);
        if ($data['examination']) {
            [$data['examination']] = DiagnosticRequestModel::withDepartmentStatus([$data['examination']]);
        }

        if (!$data['examination']) {
            return redirect()->to(base_url('radiologist/examinations'))
                            ->with('error', 'Examination not found');
        }

        // Pass the payment state to the view so it can disable the
        // report form when the study has not been paid yet.
        $data['isPaid'] = $this->requestIsPaid((int) $id, DiagnosticRequestModel::TYPE_XRAY);

        return view('Radiologist/view_examination', $data);
    }

    public function saveDraft($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $findings       = $this->request->getPost('findings');
        $interpretation = $this->request->getPost('interpretation');

        try {
            $model       = new DiagnosticRequestModel();
            $examination = $this->findXray($id);

            if (!$examination) {
                return redirect()->to(base_url('radiologist/examinations'))
                                ->with('error', 'Examination not found');
            }

            // Payment precondition.
            if (!$this->requestIsPaid((int) $id, DiagnosticRequestModel::TYPE_XRAY)) {
                return redirect()->to(base_url('radiologist/examinations'))
                                ->with('error', 'This study has not been paid yet. '
                                    . 'The receptionist must click Start before the report can be written.');
            }

            if ($examination['status'] === 'released') {
                return redirect()->to(base_url('radiologist/examination/view/' . $id))
                                ->with('error', 'This report has already been released and cannot be edited.');
            }

            // Preserve an already-advanced status. Only a `pending`
            // request is moved to `in_progress` by saving a draft.
            $status = $examination['status'] === DiagnosticRequestModel::STATUS_PENDING
                ? DiagnosticRequestModel::STATUS_IN_PROGRESS
                : $examination['status'];

            $update = [
                'findings'       => $findings,
                'interpretation' => $interpretation,
                'status'         => $status,
            ];

            if (empty($examination['radiologist_name'])) {
                $update['radiologist_name'] = PaymentModel::currentCashierName();
                $update['radiologist_id']   = PaymentModel::currentStaffId();
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
            $examination = $this->findXray($id);

            if (!$examination) {
                return redirect()->to(base_url('radiologist/examinations'))
                                ->with('error', 'Examination not found');
            }

            // Payment precondition.
            if (!$this->requestIsPaid((int) $id, DiagnosticRequestModel::TYPE_XRAY)) {
                return redirect()->to(base_url('radiologist/examinations'))
                                ->with('error', 'This study has not been paid yet. '
                                    . 'The receptionist must click Start before the report can be saved.');
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
                $update['radiologist_name'] = PaymentModel::currentCashierName();
                $update['radiologist_id']   = PaymentModel::currentStaffId();
            }

            $model->update($id, $update);

            return redirect()->to(base_url('radiologist/examination/view/' . $id))
                            ->with('success', 'Report completed. You can now release it to the receptionist.');

        } catch (\Exception $e) {
            log_message('error', 'Save findings error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error saving report');
        }
    }

    public function reports()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $model = new DiagnosticRequestModel();
        $type  = DiagnosticRequestModel::TYPE_XRAY;

        $departmentStatuses = $this->departmentStatuses();

        $counts = $this->departmentCounts();
        $data['total_examinations'] = $counts['total'];
        $data['pending_count']      = $counts['pending'];
        $data['completed_count']    = $counts['completed'];
        $data['released_count']     = $counts['released'];

        $data['weekly_data'] = $model->getWeeklyVolumeData();

        return view('Radiologist/reports', $data);
    }

    public function printResult($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $model = new DiagnosticRequestModel();
        $examination = $this->findXray($id);

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

    /**
     * Save the Findings / Impression text that came along with an image
     * upload or removal, as a draft. Same rules as saveDraft(): the status
     * is never moved backwards and the radiologist is credited on first save.
     * Does nothing if the form did not send the text or it is unchanged.
     */
    private function keepTypedReport(array $examination): void
    {
        $findings       = $this->request->getPost('findings');
        $interpretation = $this->request->getPost('interpretation');

        if ($findings === null && $interpretation === null) {
            return;
        }

        $update = [];
        if ($findings !== null && (string) $findings !== (string) ($examination['findings'] ?? '')) {
            $update['findings'] = (string) $findings;
        }
        if ($interpretation !== null && (string) $interpretation !== (string) ($examination['interpretation'] ?? '')) {
            $update['interpretation'] = (string) $interpretation;
        }
        if ($update === []) {
            return;
        }

        if (($examination['status'] ?? '') === DiagnosticRequestModel::STATUS_PENDING) {
            $update['status'] = DiagnosticRequestModel::STATUS_IN_PROGRESS;
        }
        if (empty($examination['radiologist_name'])) {
            $update['radiologist_name'] = PaymentModel::currentCashierName();
            $update['radiologist_id']   = PaymentModel::currentStaffId();
        }

        (new DiagnosticRequestModel())->update((int) $examination['id'], $update);
    }

    public function uploadImage($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $model       = new DiagnosticRequestModel();
        $examination = $this->findXray($id);

        if (!$examination) {
            return redirect()->back()->with('error', 'Examination not found.');
        }

        // Payment precondition — a study that hasn't been paid cannot
        // have images uploaded against it.
        if (!$this->requestIsPaid((int) $id, DiagnosticRequestModel::TYPE_XRAY)) {
            return redirect()->back()->with('error', 'This study has not been paid yet.');
        }

        if (($examination['status'] ?? '') === 'released') {
            return redirect()->back()->with('error', 'This report has already been released. Images can no longer be changed.');
        }

        // Uploading / removing an image reloads the page; keep whatever
        // was typed in Findings / Impression instead of losing it.
        $this->keepTypedReport($examination);

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
        $examination = $this->findXray($id);

        if (!$examination) {
            return redirect()->back()->with('error', 'Examination not found.');
        }

        if (!$this->requestIsPaid((int) $id, DiagnosticRequestModel::TYPE_XRAY)) {
            return redirect()->back()->with('error', 'This study has not been paid yet.');
        }

        if (($examination['status'] ?? '') === 'released') {
            return redirect()->back()->with('error', 'This report has already been released. Images can no longer be changed.');
        }

        // Uploading / removing an image reloads the page; keep whatever
        // was typed in Findings / Impression instead of losing it.
        $this->keepTypedReport($examination);

        $paths = [];
        if (!empty($examination['image_paths'])) {
            $decoded = json_decode($examination['image_paths'], true);
            if (is_array($decoded)) {
                $paths = array_values(array_filter($decoded, 'is_string'));
            }
        } elseif (!empty($examination['image_path'])) {
            $paths = [$examination['image_path']];
        }

        // Only ever delete a file this examination actually references.
        if (!in_array($path, $paths, true)) {
            return redirect()->back()->with('error', 'That image does not belong to this examination.');
        }

        $paths = array_values(array_filter($paths, static fn ($p) => $p !== $path));

        $normalized = str_replace('\\', '/', $path);
        if (str_starts_with($normalized, 'uploads/xray/') && !str_contains($normalized, '..')) {
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
            $examination = $this->findXray($id);

            if (!$examination) {
                return redirect()->to(base_url('radiologist/examinations'))
                                ->with('error', 'Examination not found');
            }

            // Payment precondition.
            if (!$this->requestIsPaid((int) $id, DiagnosticRequestModel::TYPE_XRAY)) {
                return redirect()->to(base_url('radiologist/examinations'))
                                ->with('error', 'This study has not been paid yet.');
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
                $update['radiologist_name'] = PaymentModel::currentCashierName();
                $update['radiologist_id']   = PaymentModel::currentStaffId();
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