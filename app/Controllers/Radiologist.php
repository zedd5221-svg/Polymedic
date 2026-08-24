<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\XrayExaminationModel;
use App\Models\NotificationModel;

class Radiologist extends BaseController
{
    private function checkAuth()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('login'));
        }
        
        $role = session()->get('role');
        if ($role !== 'radiologist' && $role !== 'admin') {
            if ($role === 'receptionist') {
                return redirect()->to(base_url('receptionist/dashboard'));
            } elseif ($role === 'admin') {
                return redirect()->to(base_url('admin/dashboard'));
            }
            return redirect()->to(base_url('login'));
        }
        return null;
    }

    // =============================================
    // DASHBOARD
    // =============================================
    public function dashboard()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $xrayModel = new XrayExaminationModel();
        
        // Get counts
        $data['counts'] = $xrayModel->getCounts();
        
        // Get pending examinations
        $data['pendingExaminations'] = $xrayModel->getPendingExaminations();
        
        // Get recent examinations (last 10)
        $data['recentExaminations'] = $xrayModel->getExaminations(null, 10);
        
        // Get today's completed
        $data['todayCompleted'] = $xrayModel
            ->where('DATE(updated_at)', date('Y-m-d'))
            ->where('status', XrayExaminationModel::STATUS_COMPLETED)
            ->countAllResults();
        
        return view('Radiologist/dashboard', $data);
    }

    // =============================================
    // EXAMINATIONS LIST
    // =============================================
    public function examinations()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $xrayModel = new XrayExaminationModel();
        $status = $this->request->getGet('status');
        
        $data['examinations'] = $xrayModel->getExaminations($status);
        $data['currentStatus'] = $status;
        $data['counts'] = $xrayModel->getCounts();
        
        return view('Radiologist/examinations', $data);
    }

    // =============================================
    // VIEW EXAMINATION
    // =============================================
    public function viewExamination($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $xrayModel = new XrayExaminationModel();
        $appointmentModel = new AppointmentModel();
        
        $data['examination'] = $xrayModel->find($id);
        
        if (!$data['examination']) {
            return redirect()->to(base_url('radiologist/examinations'))
                            ->with('error', 'Examination not found');
        }
        
        // Get appointment data for additional info
        $data['appointment'] = $appointmentModel->find($data['examination']['appointment_id']);
        
        // Status class mapping
        $data['statusClass'] = [
            'pending' => 'warning',
            'processing' => 'primary',
            'completed' => 'success',
            'released' => 'info'
        ];
        
        // Get available X-Ray services from appointment
        if ($data['appointment']) {
            $data['xray_services'] = json_decode($data['appointment']['xray_services'], true) ?? [];
        } else {
            $data['xray_services'] = [];
        }
        
        return view('Radiologist/view_examination', $data);
    }

    // =============================================
    // UPLOAD IMAGE
    // =============================================
    public function uploadImage($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $xrayModel = new XrayExaminationModel();
        $examination = $xrayModel->find($id);
        
        if (!$examination) {
            return redirect()->to(base_url('radiologist/examinations'))
                            ->with('error', 'Examination not found');
        }
        
        $file = $this->request->getFile('xray_image');
        
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Please select a valid image file');
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            return redirect()->back()->with('error', 'Only JPEG, PNG, GIF, and WebP images are allowed');
        }
        
        // Generate unique filename
        $newName = 'xray_' . $id . '_' . time() . '.' . $file->getExtension();
        
        // Move file to uploads directory
        $uploadPath = ROOTPATH . 'public/uploads/xray/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        
        $file->move($uploadPath, $newName);
        
        // Update database
        $xrayModel->update($id, [
            'image_path' => '/uploads/xray/' . $newName,
            'status' => XrayExaminationModel::STATUS_PROCESSING
        ]);
        
        // Create notification
        NotificationModel::notify(
            'xray',
            'X-Ray Image Uploaded',
            'X-Ray image uploaded for patient ' . $examination['patient_name'],
            $id,
            'radiologist/examination/view/' . $id
        );
        
        return redirect()->to(base_url('radiologist/examination/view/' . $id))
                        ->with('success', 'X-Ray image uploaded successfully!');
    }

    // =============================================
    // SAVE FINDINGS
    // =============================================
    public function saveFindings($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $xrayModel = new XrayExaminationModel();
        $examination = $xrayModel->find($id);
        
        if (!$examination) {
            return redirect()->to(base_url('radiologist/examinations'))
                            ->with('error', 'Examination not found');
        }
        
        $findings = $this->request->getPost('findings');
        $interpretation = $this->request->getPost('interpretation');
        
        if (empty($findings) || empty($interpretation)) {
            return redirect()->back()->with('error', 'Please enter both findings and interpretation');
        }
        
        $xrayModel->saveFindings($id, $findings, $interpretation);
        
        // Create notification
        NotificationModel::notify(
            'xray',
            'X-Ray Interpretation Complete',
            'Radiologist completed interpretation for ' . $examination['patient_name'],
            $id,
            'radiologist/examination/view/' . $id
        );
        
        return redirect()->to(base_url('radiologist/examination/view/' . $id))
                        ->with('success', 'Findings and interpretation saved successfully!');
    }

    // =============================================
    // RELEASE RESULT
    // =============================================
    public function releaseResult($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $xrayModel = new XrayExaminationModel();
        $examination = $xrayModel->find($id);
        
        if (!$examination) {
            return redirect()->to(base_url('radiologist/examinations'))
                            ->with('error', 'Examination not found');
        }
        
        if (empty($examination['findings']) || empty($examination['interpretation'])) {
            return redirect()->back()->with('error', 'Please complete findings and interpretation first');
        }
        
        $xrayModel->updateStatus($id, XrayExaminationModel::STATUS_RELEASED);
        
        // Create notification
        NotificationModel::notify(
            'xray',
            'X-Ray Result Released',
            'X-Ray result released for patient ' . $examination['patient_name'],
            $id,
            'radiologist/examination/view/' . $id
        );
        
        return redirect()->to(base_url('radiologist/examination/view/' . $id))
                        ->with('success', 'Result released successfully!');
    }

    // =============================================
    // PRINT RESULT (PDF)
    // =============================================
    public function printResult($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $xrayModel = new XrayExaminationModel();
        $appointmentModel = new AppointmentModel();
        
        $data['examination'] = $xrayModel->find($id);
        
        if (!$data['examination']) {
            return redirect()->to(base_url('radiologist/examinations'))
                            ->with('error', 'Examination not found');
        }
        
        $data['appointment'] = $appointmentModel->find($data['examination']['appointment_id']);
        $data['xray_services'] = json_decode($data['appointment']['xray_services'] ?? '[]', true) ?? [];
        
        return view('Radiologist/print_result', $data);
    }

    // =============================================
    // NOTIFICATIONS
    // =============================================
    public function notifications()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $notificationModel = new NotificationModel();
        $data['notifications'] = $notificationModel
            ->orderBy('created_at', 'DESC')
            ->findAll();
        $data['unreadCount'] = $notificationModel->getUnreadCount();
        
        return view('Radiologist/notifications', $data);
    }

    // =============================================
    // REPORTS
    // =============================================
    public function reports()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $xrayModel = new XrayExaminationModel();
        $appointmentModel = new AppointmentModel();
        
        // Get counts for reports
        $data['counts'] = $xrayModel->getCounts();
        
        // Get all examinations for reporting
        $data['examinations'] = $xrayModel->getExaminations();
        
        // Get monthly statistics (group by month)
        $monthlyStats = $xrayModel
            ->select('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as total, status')
            ->groupBy('month, status')
            ->orderBy('month', 'DESC')
            ->findAll();
        
        // Organize monthly stats
        $monthlyData = [];
        foreach ($monthlyStats as $stat) {
            $month = $stat['month'];
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = [
                    'pending' => 0,
                    'processing' => 0,
                    'completed' => 0,
                    'released' => 0,
                    'total' => 0
                ];
            }
            $status = $stat['status'] ?? 'pending';
            if (isset($monthlyData[$month][$status])) {
                $monthlyData[$month][$status] = (int)$stat['total'];
                $monthlyData[$month]['total'] += (int)$stat['total'];
            }
        }
        
        $data['monthlyStats'] = $monthlyData;
        
        // Get total appointments with X-Ray
        $data['totalXrayAppointments'] = $appointmentModel
            ->where('xray_services IS NOT NULL')
            ->where('xray_services !=', '[]')
            ->where('xray_services !=', 'null')
            ->countAllResults();
        
        // Get total revenue (if charge column exists)
        $totalRevenue = 0;
        $allExams = $xrayModel->findAll();
        foreach ($allExams as $exam) {
            // Calculate based on exam type or use default
            $totalRevenue += 500; // Default price per exam
        }
        $data['totalRevenue'] = $totalRevenue;
        
        return view('Radiologist/reports', $data);
    }
}