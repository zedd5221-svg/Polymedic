<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\LabRequestModel;
use App\Models\XrayExaminationModel;
use App\Models\NotificationModel;
use App\Models\ServiceModel;
use App\Models\PatientModel;

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

        $xrayModel = new XrayExaminationModel();
        
        $data['pending'] = $xrayModel->where('status', 'pending')->countAllResults();
        $data['processing'] = $xrayModel->where('status', 'in_progress')->countAllResults();
        $data['completed'] = $xrayModel->where('status', 'completed')->countAllResults();
        $data['released'] = $xrayModel->where('status', 'released')->countAllResults();
        $data['total'] = $xrayModel->countAll();
        
        $data['recent_examinations'] = $xrayModel
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->findAll();
        
        $data['pending_examinations'] = $xrayModel
            ->where('status', 'pending')
            ->orderBy('created_at', 'ASC')
            ->limit(5)
            ->findAll();
        
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
            'pending' => $xrayModel->where('status', 'pending')->countAllResults(),
            'processing' => $xrayModel->where('status', 'in_progress')->countAllResults(),
            'completed' => $xrayModel->where('status', 'completed')->countAllResults(),
            'released' => $xrayModel->where('status', 'released')->countAllResults(),
            'total' => $xrayModel->countAll()
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
    // Saves whatever is currently in the two report fields
    // without completing the report. Status goes (or stays) at
    // in_progress. No content validation — a draft is allowed to
    // be half-written.
    // =============================================
    public function saveDraft($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $findings = $this->request->getPost('findings');
        $interpretation = $this->request->getPost('interpretation');
        
        try {
            $xrayModel = new XrayExaminationModel();
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
                // A draft that has never been touched is still just a
                // draft. Keep the status at in_progress so the
                // radiologist can come back and finish it.
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
    // Refuses unless both findings and interpretation have
    // content. On success sets the status to completed.
    // =============================================
    public function saveFindings($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $findings = trim((string) $this->request->getPost('findings'));
        $interpretation = trim((string) $this->request->getPost('interpretation'));
        
        try {
            $xrayModel = new XrayExaminationModel();
            $examination = $xrayModel->find($id);
            
            if (!$examination) {
                return redirect()->to(base_url('radiologist/examinations'))
                                ->with('error', 'Examination not found');
            }
            
            if ($examination['status'] === 'released') {
                return redirect()->to(base_url('radiologist/examination/view/' . $id))
                                ->with('error', 'This report has already been released and cannot be edited.');
            }
            
            // The completeness gate. Both fields must have content
            // before the report can be completed. The view enforces
            // the same rule on the client side; this is the actual
            // rule that a crafted POST cannot bypass.
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
        $data['pending_count'] = $xrayModel->where('status', 'pending')->countAllResults();
        $data['completed_count'] = $xrayModel->where('status', 'completed')->countAllResults();
        $data['released_count'] = $xrayModel->where('status', 'released')->countAllResults();
        
        $data['weekly_data'] = $xrayModel->getWeeklyVolumeData();
        
        return view('Radiologist/reports', $data);
    }

    public function printResult($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $xrayModel = new XrayExaminationModel();
        $data['examination'] = $xrayModel->find($id);
        
        if (!$data['examination']) {
            return redirect()->to(base_url('radiologist/examinations'))
                            ->with('error', 'Examination not found');
        }
        
        return view('Radiologist/print_result', $data);
    }

    public function uploadImage($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $file = $this->request->getFile('xray_image');
        
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'No valid image uploaded');
        }
        
        try {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file->getMimeType(), $allowedTypes)) {
                return redirect()->back()->with('error', 'Invalid file type. Please upload JPEG, PNG, GIF, or WEBP.');
            }
            
            $newName = 'xray_' . $id . '_' . time() . '.' . $file->getExtension();
            
            $file->move(FCPATH . 'uploads/xray', $newName);
            
            $xrayModel = new XrayExaminationModel();
            $xrayModel->update($id, ['image_path' => 'uploads/xray/' . $newName]);
            
            return redirect()->back()->with('success', 'Image uploaded successfully!');
            
        } catch (\Exception $e) {
            log_message('error', 'Upload image error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error uploading image: ' . $e->getMessage());
        }
    }

    // =============================================
    // RELEASE RESULT
    // Refuses if either report field is empty — a half-written
    // report must never be released to the receptionist.
    // =============================================
    public function releaseResult($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        try {
            $xrayModel = new XrayExaminationModel();
            $examination = $xrayModel->find($id);
            
            if (!$examination) {
                return redirect()->to(base_url('radiologist/examinations'))
                                ->with('error', 'Examination not found');
            }
            
            if ($examination['status'] === 'released') {
                return redirect()->to(base_url('radiologist/examination/view/' . $id))
                                ->with('error', 'This result has already been released.');
            }
            
            // Refuse to release an incomplete report, even if the
            // status was somehow set to completed.
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