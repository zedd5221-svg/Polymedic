<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\LabRequestModel;
use App\Models\XrayExaminationModel;
use App\Models\NotificationModel;
use App\Models\ServiceModel;
use App\Models\PatientModel;
use App\Models\PaymentModel;

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

    public function dashboard()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $xrayModel    = new XrayExaminationModel();
        $paymentModel = new PaymentModel();
        
        $data['pending']    = $xrayModel->where('status', 'pending')->countAllResults();
        $data['processing'] = $xrayModel->where('status', 'in_progress')->countAllResults();
        $data['completed']  = $xrayModel->where('status', 'completed')->countAllResults();
        $data['released']   = $xrayModel->where('status', 'released')->countAllResults();
        $data['total']      = $xrayModel->countAll();
        
        $data['recent_examinations'] = $xrayModel
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->findAll();
        
        $data['pending_examinations'] = $xrayModel
            ->where('status', 'pending')
            ->orderBy('created_at', 'ASC')
            ->limit(5)
            ->findAll();

        /*
         * Radiology revenue.
         *
         * Only rows in the payments table whose request_type is
         * 'xray' and whose payment_status is 'paid' are counted.
         * This gives a figure that belongs to the radiology
         * department, not to the whole facility. The two methods
         * live in PaymentModel and return 0.0 when no matching
         * payments exist, which is a legitimate answer.
         */
        $data['today_revenue']   = $paymentModel->getXrayRevenueToday();
        $data['monthly_revenue'] = $paymentModel->getXrayRevenueThisMonth();
        
        return view('Radiologist/dashboard', $data);
    }

    public function examinations()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $xrayModel = new XrayExaminationModel();
        
        $data['examinations'] = $xrayModel
            ->orderBy('created_at', 'DESC')
            ->findAll();
        
        $data['counts'] = [
            'pending'    => $xrayModel->where('status', 'pending')->countAllResults(),
            'processing' => $xrayModel->where('status', 'in_progress')->countAllResults(),
            'completed'  => $xrayModel->where('status', 'completed')->countAllResults(),
            'released'   => $xrayModel->where('status', 'released')->countAllResults(),
            'total'      => $xrayModel->countAll()
        ];
        
        return view('Radiologist/examinations', $data);
    }

    public function viewExamination($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $xrayModel = new XrayExaminationModel();
        $data['examination'] = $xrayModel->find($id);
        
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
            $xrayModel = new XrayExaminationModel();
            $xrayModel->update($id, ['status' => $status]);
            
            if ($status === 'released') {
                $xrayModel->update($id, ['released_at' => date('Y-m-d H:i:s')]);
            }
            
            return $this->response->setJSON(['success' => true, 'message' => 'Status updated successfully']);
            
        } catch (\Exception $e) {
            log_message('error', 'Update examination status error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error updating status']);
        }
    }

    // =============================================
    // SAVE DRAFT
    // =============================================
    public function saveDraft($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $findings       = $this->request->getPost('findings');
        $interpretation = $this->request->getPost('interpretation');
        
        try {
            $xrayModel   = new XrayExaminationModel();
            $examination = $xrayModel->find($id);
            
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
            
            $xrayModel->update($id, $update);
            
            return redirect()->to(base_url('radiologist/examination/view/' . $id))
                            ->with('success', 'Draft saved. Complete the report when both sections are filled.');
                            
        } catch (\Exception $e) {
            log_message('error', 'Save draft error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error saving draft');
        }
    }

    // =============================================
    // SAVE FINDINGS (complete the report)
    // =============================================
    public function saveFindings($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $findings       = trim((string) $this->request->getPost('findings'));
        $interpretation = trim((string) $this->request->getPost('interpretation'));
        
        try {
            $xrayModel   = new XrayExaminationModel();
            $examination = $xrayModel->find($id);
            
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
            
            $xrayModel->update($id, $update);
            
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
        
        $xrayModel = new XrayExaminationModel();
        
        $data['total_examinations'] = $xrayModel->countAll();
        $data['pending_count']      = $xrayModel->where('status', 'pending')->countAllResults();
        $data['completed_count']    = $xrayModel->where('status', 'completed')->countAllResults();
        $data['released_count']     = $xrayModel->where('status', 'released')->countAllResults();
        
        $data['weekly_data'] = $xrayModel->getWeeklyVolumeData();
        
        return view('Radiologist/reports', $data);
    }

   public function printResult($id)
{
    $redirect = $this->checkAuth();
    if ($redirect) return $redirect;

    $xrayModel = new XrayExaminationModel();
    $examination = $xrayModel->find($id);

    if (!$examination) {
        return redirect()->to(base_url('radiologist/examinations'))
                        ->with('error', 'Examination not found');
    }

    $data['examination'] = $examination;

    /*
     * Look up the radiologist's PRC license number so it can be
     * printed under the signature on the report.
     *
     * The examination row stores the radiologist's full name, not an
     * ID, so the lookup matches on full_name AND role. Requiring the
     * role protects against a match on a user with the same name in a
     * different role. If no user matches, or the matched user has no
     * prc_license on file, the view falls back to a blank line rather
     * than printing something wrong.
     */
    $radiologistName = trim((string) ($examination['radiologist_name'] ?? ''));

    if ($radiologistName === '') {
        $radiologistName = trim((string) (session()->get('full_name') ?? ''));
    }

    $data['radiologist_license'] = '';

    if ($radiologistName !== '') {
        $userModel = new \App\Models\UserModel();
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

    // =============================================
    // UPLOAD IMAGES (up to 5 per study)
    // =============================================
    public function uploadImage($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $xrayModel   = new XrayExaminationModel();
        $examination = $xrayModel->find($id);

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

        /*
         * Load what is already stored.
         *
         * image_paths is the new JSON-array column. image_path is
         * the legacy single-column. If only the legacy column is
         * populated, treat its value as the existing list so a
         * study uploaded before the migration keeps its image.
         */
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

        $xrayModel->update($id, [
            // image_path keeps the first image for any legacy reader.
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

    // =============================================
    // REMOVE ONE IMAGE
    // =============================================
    public function removeImage($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $path = (string) $this->request->getPost('image_path');
        if ($path === '') {
            return redirect()->back()->with('error', 'No image specified.');
        }

        $xrayModel   = new XrayExaminationModel();
        $examination = $xrayModel->find($id);

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

        /*
         * Only unlink files inside uploads/xray/. A crafted POST
         * containing an arbitrary path cannot make the controller
         * delete a file elsewhere on disk.
         */
        $normalized = str_replace('\\', '/', $path);
        if (str_starts_with($normalized, 'uploads/xray/')) {
            $full = FCPATH . $normalized;
            if (is_file($full)) {
                @unlink($full);
            }
        }

        $xrayModel->update($id, [
            'image_path'  => $paths[0] ?? null,
            'image_paths' => empty($paths) ? null : json_encode($paths),
        ]);

        return redirect()->back()->with('success', 'Image removed.');
    }

    // =============================================
    // RELEASE RESULT
    // =============================================
    public function releaseResult($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        try {
            $xrayModel   = new XrayExaminationModel();
            $examination = $xrayModel->find($id);
            
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
            
            $xrayModel->update($id, $update);
            
            return redirect()->to(base_url('radiologist/examinations'))
                            ->with('success', 'Result released. The receptionist can now print it.');
                            
        } catch (\Exception $e) {
            log_message('error', 'Release result error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error releasing result');
        }
    }
}