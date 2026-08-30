<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\UserModel;
use App\Models\XrayExaminationModel;
use App\Models\LabRequestModel;
use App\Models\NotificationModel;
use App\Models\ServiceModel;

class Admin extends BaseController
{
    private function checkAuth()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('login'));
        }
        
        $role = session()->get('role');
        if ($role !== 'admin') {
            if ($role === 'receptionist') {
                return redirect()->to(base_url('receptionist/dashboard'));
            }
            return redirect()->to(base_url('login'));
        }
        return null;
    }

    // =============================================
    // DASHBOARD - DYNAMIC DATA
    // =============================================
    public function dashboard()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $appointmentModel = new AppointmentModel();
        $labRequestModel = new LabRequestModel();
        $notificationModel = new NotificationModel();

        $today = date('Y-m-d');
        $month = date('m');
        $year = date('Y');

        $data = [
            'totalPatients' => $appointmentModel->distinct()->select('full_name')->countAllResults(),
            'todayPatients' => $appointmentModel->where('appointment_date', $today)->countAllResults(),
            'pendingRequests' => $appointmentModel->where('status', 'pending')->countAllResults(),
            'completedRequests' => $appointmentModel->where('status', 'completed')->countAllResults(),
            'releasedResults' => $labRequestModel->where('status', 'released')->countAllResults(),
            'todayRevenue' => $appointmentModel->where('appointment_date', $today)->where('status', 'completed')->countAllResults() * 500,
            'monthlyRevenue' => $appointmentModel->where('MONTH(appointment_date)', $month)->where('YEAR(appointment_date)', $year)->where('status', 'completed')->countAllResults() * 500,
            'revenueData' => $this->getRevenueData(),
            'visitsData' => $this->getVisitsData(),
            'requestsData' => $this->getRequestsData(),
            'topTests' => $this->getTopTests(),
            'recentActivity' => $this->getRecentActivity(),
        ];

        return view('Admin/dashboard', $data);
    }

    // ===== CHART HELPERS =====

    private function getRevenueData()
    {
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $currentMonth = (int)date('m');
        $year = date('Y');
        $revenue = [];

        for ($i = 1; $i <= $currentMonth; $i++) {
            $count = (new AppointmentModel())
                ->where('MONTH(appointment_date)', $i)
                ->where('YEAR(appointment_date)', $year)
                ->where('status', 'completed')
                ->countAllResults();
            $revenue[] = $count * 500;
        }

        return [
            'labels' => array_slice($months, 0, $currentMonth),
            'values' => $revenue,
        ];
    }

    private function getVisitsData()
    {
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $visits = [];
        $start = date('Y-m-d', strtotime('monday this week'));

        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime($start . ' +' . $i . ' days'));
            $visits[] = (new AppointmentModel())->where('appointment_date', $date)->countAllResults();
        }

        return [
            'labels' => $days,
            'values' => $visits,
        ];
    }

    private function getRequestsData()
    {
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $requested = [];
        $completed = [];
        $start = date('Y-m-d', strtotime('monday this week'));

        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime($start . ' +' . $i . ' days'));
            $total = (new AppointmentModel())->where('appointment_date', $date)->countAllResults();
            $comp = (new AppointmentModel())->where('appointment_date', $date)->where('status', 'completed')->countAllResults();
            $requested[] = $total;
            $completed[] = $comp;
        }

        return [
            'labels' => $days,
            'requested' => $requested,
            'completed' => $completed,
        ];
    }

    private function getTopTests()
    {
        $labRequests = (new LabRequestModel())->findAll();
        $testCounts = [];

        foreach ($labRequests as $req) {
            $services = explode(', ', $req['lab_services']);
            foreach ($services as $service) {
                $service = trim($service);
                if (!empty($service)) {
                    $testCounts[$service] = ($testCounts[$service] ?? 0) + 1;
                }
            }
        }

        arsort($testCounts);
        $top = array_slice($testCounts, 0, 5, true);

        $result = [];
        $colors = ['#1D4ED8', '#0d9488', '#ff6b00', '#800080', '#17a2b8'];
        $i = 0;
        foreach ($top as $name => $count) {
            $result[] = ['name' => $name, 'count' => $count, 'color' => $colors[$i % count($colors)]];
            $i++;
        }

        return $result;
    }

    private function getRecentActivity()
    {
        $notifications = (new NotificationModel())
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->findAll();

        $activities = [];
        foreach ($notifications as $notif) {
            $activities[] = [
                'message' => $notif['title'] . ' — ' . $notif['message'],
                'time' => $notif['created_at'],
                'color' => $notif['type'] === 'appointment' ? '#0148ca' : ($notif['type'] === 'xray' ? '#800080' : '#04ccab'),
            ];
        }

        return $activities;
    }

    // =============================================
    // PATIENTS - INCLUDING WALK-INS + SOURCE FIELD
    // =============================================
    public function patients()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $appointmentModel = new AppointmentModel();
        $labRequestModel = new LabRequestModel();
        $xrayModel = new XrayExaminationModel();
        
        $allPatients = [];
        $seenOnlineNames = []; // Only for Online patients
        
        // ========== 1. FROM APPOINTMENTS (ONLINE) ==========
        try {
            $appointmentPatients = $appointmentModel
                ->select('full_name, email, phone, age, gender, MAX(appointment_date) as last_visit')
                ->groupBy('full_name')
                ->orderBy('full_name', 'ASC')
                ->findAll();
            
            foreach ($appointmentPatients as $patient) {
                $name = strtolower(trim($patient['full_name'] ?? ''));
                if (!empty($name)) {
                    if (!isset($seenOnlineNames[$name])) {
                        $seenOnlineNames[$name] = true;
                    }
                    $allPatients[] = [
                        'full_name' => $patient['full_name'] ?? 'Unknown',
                        'email' => $patient['email'] ?? '',
                        'phone' => $patient['phone'] ?? '',
                        'age' => $patient['age'] ?? '',
                        'gender' => $patient['gender'] ?? '',
                        'last_visit' => $patient['last_visit'] ?? null,
                        'source' => 'Online',
                    ];
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Patients - Appointments error: ' . $e->getMessage());
        }
        
        // ========== 2. FROM LAB REQUESTS (WALK-IN) ==========
        try {
            $labPatients = $labRequestModel
                ->select('patient_name as full_name, age, gender, MAX(request_date) as last_visit')
                ->groupBy('patient_name')
                ->orderBy('patient_name', 'ASC')
                ->findAll();
            
            foreach ($labPatients as $patient) {
                $name = strtolower(trim($patient['full_name'] ?? ''));
                if (!empty($name)) {
                    // NO DUPLICATE CHECK - ALL walk-in patients appear
                    $allPatients[] = [
                        'full_name' => $patient['full_name'] ?? 'Unknown',
                        'email' => '',
                        'phone' => '',
                        'age' => $patient['age'] ?? '',
                        'gender' => $patient['gender'] ?? '',
                        'last_visit' => $patient['last_visit'] ?? null,
                        'source' => 'Walk-in',
                    ];
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Patients - Lab Requests error: ' . $e->getMessage());
        }
        
        // ========== 3. FROM X-RAY EXAMINATIONS (WALK-IN) ==========
        try {
            $xrayPatients = $xrayModel
                ->select('patient_name as full_name, age, gender, MAX(exam_date) as last_visit')
                ->groupBy('patient_name')
                ->orderBy('patient_name', 'ASC')
                ->findAll();
            
            foreach ($xrayPatients as $patient) {
                $name = strtolower(trim($patient['full_name'] ?? ''));
                if (!empty($name)) {
                    // NO DUPLICATE CHECK - ALL walk-in patients appear
                    $allPatients[] = [
                        'full_name' => $patient['full_name'] ?? 'Unknown',
                        'email' => '',
                        'phone' => '',
                        'age' => $patient['age'] ?? '',
                        'gender' => $patient['gender'] ?? '',
                        'last_visit' => $patient['last_visit'] ?? null,
                        'source' => 'Walk-in',
                    ];
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Patients - X-Ray error: ' . $e->getMessage());
        }
        
        // Sort by last_visit (newest first)
        usort($allPatients, function($a, $b) {
            return strtotime($b['last_visit'] ?? '0') - strtotime($a['last_visit'] ?? '0');
        });
        
        $data['patients'] = $allPatients;
        $data['total'] = count($allPatients);
        
        return view('Admin/patients', $data);
    }
    
    // =============================================
    // VISITS
    // =============================================
    public function visits()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        return view('Admin/visits');
    }
    
    // =============================================
    // REQUESTS
    // =============================================
    public function requests()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        return view('Admin/requests');
    }
    
    // =============================================
    // USERS
    // =============================================
    public function users()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $userModel = new UserModel();
        $data['users'] = $userModel->getUsers();
        $data['total'] = $userModel->countUsers();
        
        return view('Admin/users', $data);
    }
    
    // ===== USER MANAGEMENT CRUD =====
    
    public function createUser()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $email = $this->request->getPost('email');
        $full_name = $this->request->getPost('full_name');
        $role = $this->request->getPost('role');
        $status = $this->request->getPost('status') ?? 'active';
        
        if (empty($username) || empty($password) || empty($email) || empty($full_name) || empty($role)) {
            return redirect()->to(base_url('admin/users'))
                            ->with('error', 'All fields are required');
        }
        
        $userModel = new UserModel();
        if ($userModel->getUserByUsername($username)) {
            return redirect()->to(base_url('admin/users'))
                            ->with('error', 'Username already exists');
        }
        
        $userData = [
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'full_name' => $full_name,
            'role' => $role,
            'status' => $status
        ];
        
        if ($userModel->createUser($userData)) {
            return redirect()->to(base_url('admin/users'))
                            ->with('success', 'User created successfully');
        }
        
        return redirect()->to(base_url('admin/users'))
                        ->with('error', 'Failed to create user');
    }
    
    public function updateUser($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $userModel = new UserModel();
        $user = $userModel->find($id);
        
        if (!$user) {
            return redirect()->to(base_url('admin/users'))
                            ->with('error', 'User not found');
        }
        
        $email = $this->request->getPost('email');
        $full_name = $this->request->getPost('full_name');
        $role = $this->request->getPost('role');
        $status = $this->request->getPost('status');
        $password = $this->request->getPost('password');
        
        $updateData = [
            'email' => $email,
            'full_name' => $full_name,
            'role' => $role,
            'status' => $status
        ];
        
        if (!empty($password)) {
            $updateData['password'] = $password;
        }
        
        if ($userModel->updateUser($id, $updateData)) {
            return redirect()->to(base_url('admin/users'))
                            ->with('success', 'User updated successfully');
        }
        
        return redirect()->to(base_url('admin/users'))
                        ->with('error', 'Failed to update user');
    }
    
    public function deleteUser($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $userModel = new UserModel();
        $user = $userModel->find($id);
        
        if (!$user) {
            return redirect()->to(base_url('admin/users'))
                            ->with('error', 'User not found');
        }
        
        if ($user['id'] == session()->get('user_id')) {
            return redirect()->to(base_url('admin/users'))
                            ->with('error', 'You cannot delete your own account');
        }
        
        if ($userModel->deleteUser($id)) {
            return redirect()->to(base_url('admin/users'))
                            ->with('success', 'User deleted successfully');
        }
        
        return redirect()->to(base_url('admin/users'))
                        ->with('error', 'Failed to delete user');
    }
    
    public function toggleUserStatus($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $userModel = new UserModel();
        $user = $userModel->find($id);
        
        if (!$user) {
            return redirect()->to(base_url('admin/users'))
                            ->with('error', 'User not found');
        }
        
        if ($user['id'] == session()->get('user_id')) {
            return redirect()->to(base_url('admin/users'))
                            ->with('error', 'You cannot change your own status');
        }
        
        $newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';
        
        if ($userModel->updateStatus($id, $newStatus)) {
            return redirect()->to(base_url('admin/users'))
                            ->with('success', 'User status updated successfully');
        }
        
        return redirect()->to(base_url('admin/users'))
                        ->with('error', 'Failed to update user status');
    }
    
    public function getUserData($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $userModel = new UserModel();
        $user = $userModel->find($id);
        
        if ($user) {
            unset($user['password']);
            return $this->response->setJSON($user);
        }
        
        return $this->response->setJSON(['error' => 'User not found'], 404);
    }
    
    // ===== APPOINTMENT MANAGEMENT =====
    
    public function appointments()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        try {
            $model = new AppointmentModel();
            
            $model->checkLateAppointments();
            
            $data['appointments'] = $model->orderBy('appointment_date', 'DESC')
                                          ->orderBy('appointment_time', 'ASC')
                                          ->findAll();
            
            $data['total']     = $model->countAll();
            $data['pending']   = $model->where('status', 'pending')->countAllResults();
            $data['approved']  = $model->where('status', 'approved')->countAllResults();
            $data['completed'] = $model->where('status', 'completed')->countAllResults();
            $data['cancelled'] = $model->where('status', 'cancelled')->countAllResults();
            $data['late']      = $model->where('status', 'late')->countAllResults();
            
            return view('Admin/appointments', $data);
        } catch (\Exception $e) {
            log_message('error', 'Appointments error: ' . $e->getMessage());
            return "Error: " . $e->getMessage();
        }
    }
    
    public function appointmentView($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $model = new AppointmentModel();
        $data['appointment'] = $model->find($id);
        
        if (!$data['appointment']) {
            return redirect()->to(base_url('admin/appointments'))
                            ->with('error', 'Appointment not found');
        }
        
        $data['lab_services']  = json_decode($data['appointment']['lab_services'], true) ?? [];
        $data['xray_services'] = json_decode($data['appointment']['xray_services'], true) ?? [];
        
        $data['statusClass'] = [
            'pending'   => 'warning',
            'approved'  => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
            'late'      => 'dark'
        ];
        
        return view('Admin/appointment_view', $data);
    }
    
    /**
     * Format X-Ray services to readable string
     */
    private function formatXrayServices($services)
    {
        if (empty($services)) {
            return 'X-Ray Examination';
        }
        
        if (is_string($services)) {
            $decoded = json_decode($services, true);
            if (is_array($decoded)) {
                $services = $decoded;
            }
        }
        
        if (is_array($services)) {
            $cleaned = array_map(function($service) {
                $service = str_replace('\/', '/', $service);
                $service = str_replace('\\/', '/', $service);
                $service = stripslashes($service);
                return trim($service);
            }, $services);
            return implode(', ', $cleaned);
        }
        
        return $services;
    }
    
    /**
     * Format Lab services to readable string
     */
    private function formatLabServices($services)
    {
        if (empty($services)) {
            return 'Laboratory Examination';
        }
        
        if (is_string($services)) {
            $decoded = json_decode($services, true);
            if (is_array($decoded)) {
                $services = $decoded;
            }
        }
        
        if (is_array($services)) {
            $cleaned = array_map(function($service) {
                $service = str_replace('\/', '/', $service);
                $service = str_replace('\\/', '/', $service);
                $service = stripslashes($service);
                return trim($service);
            }, $services);
            return implode(', ', $cleaned);
        }
        
        return $services;
    }
    
    public function approveAppointment($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $model = new AppointmentModel();
        $appointment = $model->find($id);
        
        if (!$appointment) {
            return redirect()->to(base_url('admin/appointments'))
                            ->with('error', 'Appointment not found');
        }
        
        if (in_array($appointment['status'], ['completed', 'cancelled', 'no_show'])) {
            return redirect()->to(base_url('admin/appointments'))
                            ->with('error', 'This appointment cannot be approved');
        }
        
        $model->update($id, [
            'status'       => 'approved',
            'arrival_time' => date('Y-m-d H:i:s')
        ]);
        
        // ===== CHECK FOR LABORATORY SERVICES AND CREATE LAB REQUEST =====
        $labServices = json_decode($appointment['lab_services'], true) ?? [];
        if (!empty($labServices)) {
            $labRequestModel = new LabRequestModel();
            $existing = $labRequestModel->where('appointment_id', $id)->first();
            
            if (!$existing) {
                $examType = $this->formatLabServices($labServices);
                
                $labRequestModel->insert([
                    'appointment_id' => $id,
                    'patient_name' => $appointment['full_name'],
                    'age' => $appointment['age'],
                    'gender' => $appointment['gender'],
                    'lab_services' => $examType,
                    'request_date' => $appointment['appointment_date'],
                    'status' => 'pending'
                ]);
                
                NotificationModel::notify(
                    'lab',
                    'New Laboratory Request',
                    'New laboratory request for ' . $appointment['full_name'],
                    $id,
                    'medtech/request/view/' . $id
                );
            }
        }
        
        // ===== CHECK FOR X-RAY SERVICES AND CREATE EXAMINATION =====
        $xrayServices = json_decode($appointment['xray_services'], true) ?? [];
        if (!empty($xrayServices)) {
            $xrayModel = new XrayExaminationModel();
            $existing = $xrayModel->where('appointment_id', $id)->first();
            
            if (!$existing) {
                $examType = $this->formatXrayServices($xrayServices);
                
                $xrayModel->insert([
                    'appointment_id' => $id,
                    'patient_name' => $appointment['full_name'],
                    'age' => $appointment['age'],
                    'gender' => $appointment['gender'],
                    'exam_type' => $examType,
                    'exam_date' => $appointment['appointment_date'],
                    'priority' => 'Routine',
                    'status' => 'pending'
                ]);
                
                NotificationModel::notify(
                    'xray',
                    'New X-Ray Examination',
                    'New X-Ray examination for ' . $appointment['full_name'],
                    $id,
                    'radiologist/examination/view/' . $id
                );
            }
        }
        
        return redirect()->to(base_url('admin/appointments'))
                        ->with('success', 'Appointment approved successfully!');
    }
    
    public function cancelAppointment($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $model = new AppointmentModel();
        $appointment = $model->find($id);
        
        if (!$appointment) {
            return redirect()->to(base_url('admin/appointments'))
                            ->with('error', 'Appointment not found');
        }
        
        if ($appointment['status'] == 'completed') {
            return redirect()->to(base_url('admin/appointments'))
                            ->with('error', 'Completed appointments cannot be cancelled');
        }
        
        $model->update($id, ['status' => 'cancelled']);
        
        return redirect()->to(base_url('admin/appointments'))
                        ->with('success', 'Appointment cancelled successfully!');
    }
    
    public function completeAppointment($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $model = new AppointmentModel();
        $appointment = $model->find($id);
        
        if (!$appointment) {
            return redirect()->to(base_url('admin/appointments'))
                            ->with('error', 'Appointment not found');
        }
        
        if ($appointment['status'] != 'approved') {
            return redirect()->to(base_url('admin/appointments'))
                            ->with('error', 'Only approved appointments can be marked as completed');
        }
        
        $model->update($id, ['status' => 'completed']);
        
        return redirect()->to(base_url('admin/appointments'))
                        ->with('success', 'Appointment marked as completed!');
    }
    
    public function deleteAppointment($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $model = new AppointmentModel();
        $appointment = $model->find($id);
        
        if (!$appointment) {
           return redirect()->to(base_url('admin/appointments'))
                            ->with('error', 'Appointment not found');
        }
        
        $model->delete($id);
        
        return redirect()->to(base_url('admin/appointments'))
                        ->with('success', 'Appointment deleted successfully!');
    }
    
    // ===== SYNC X-RAY EXAMINATIONS =====
    
    public function syncXrayExaminations()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $appointmentModel = new AppointmentModel();
        $xrayModel = new XrayExaminationModel();
        
        $appointments = $appointmentModel
            ->where('status', 'approved')
            ->findAll();
        
        $created = 0;
        $skipped = 0;
        
        foreach ($appointments as $appt) {
            $xrayServices = json_decode($appt['xray_services'], true) ?? [];
            
            if (empty($xrayServices)) {
                continue;
            }
            
            $existing = $xrayModel->where('appointment_id', $appt['id'])->first();
            
            if (!$existing) {
                $examType = $this->formatXrayServices($xrayServices);
                
                $xrayModel->insert([
                    'appointment_id' => $appt['id'],
                    'patient_name' => $appt['full_name'],
                    'age' => $appt['age'],
                    'gender' => $appt['gender'],
                    'exam_type' => $examType,
                    'exam_date' => $appt['appointment_date'],
                    'priority' => 'Routine',
                    'status' => 'pending'
                ]);
                $created++;
            } else {
                $skipped++;
            }
        }
        
        return redirect()->to(base_url('admin/appointments'))
                        ->with('success', "Synced {$created} X-Ray examinations. Skipped {$skipped} existing.");
    }
    
    // ===== SYNC LAB REQUESTS =====
    
    public function syncLabRequests()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $appointmentModel = new AppointmentModel();
        $labRequestModel = new LabRequestModel();
        
        $appointments = $appointmentModel
            ->where('lab_services IS NOT NULL')
            ->where('lab_services !=', '[]')
            ->where('lab_services !=', 'null')
            ->findAll();
        
        $created = 0;
        $skipped = 0;
        
        foreach ($appointments as $appt) {
            if (!in_array($appt['status'], ['approved', 'completed'])) {
                continue;
            }
            
            $labServices = json_decode($appt['lab_services'], true) ?? [];
            
            if (empty($labServices)) {
                continue;
            }
            
            $existing = $labRequestModel->where('appointment_id', $appt['id'])->first();
            
            if (!$existing) {
                $examType = $this->formatLabServices($labServices);
                
                $labRequestModel->insert([
                    'appointment_id' => $appt['id'],
                    'patient_name' => $appt['full_name'],
                    'age' => $appt['age'],
                    'gender' => $appt['gender'],
                    'lab_services' => $examType,
                    'request_date' => $appt['appointment_date'],
                    'status' => 'pending'
                ]);
                $created++;
            } else {
                $skipped++;
            }
        }
        
        return redirect()->to(base_url('admin/appointments'))
                        ->with('success', "Synced {$created} laboratory requests. Skipped {$skipped} existing.");
    }
    
    // ===== SERVICE MANAGEMENT =====

    public function services()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $serviceModel = new ServiceModel();
        $data['services'] = $serviceModel->orderBy('category', 'ASC')
                                         ->orderBy('service_name', 'ASC')
                                         ->findAll();
        
        $counts = $serviceModel->getCountByCategory();
        $data['total'] = $counts['total'];
        $data['lab_count'] = $counts['laboratory'];
        $data['xray_count'] = $counts['xray'];
        $data['other_count'] = $counts['other'];
        
        return view('Admin/services', $data);
    }

    public function createService()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $serviceModel = new ServiceModel();
        
        $rules = [
            'service_code' => 'required|is_unique[services.service_code]',
            'service_name' => 'required',
            'category' => 'required|in_list[laboratory,xray,other]',
            'charge' => 'permit_empty|numeric|greater_than_equal_to[0]'
        ];
        
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        
        $serviceModel->save([
            'service_code' => strtoupper($this->request->getPost('service_code')),
            'service_name' => $this->request->getPost('service_name'),
            'category' => $this->request->getPost('category'),
            'description' => $this->request->getPost('description'),
            'charge' => $this->request->getPost('charge') ?? 0,
            'is_active' => $this->request->getPost('is_active') ?? 1
        ]);
        
        return redirect()->to(base_url('admin/services'))
                        ->with('success', 'Service created successfully!');
    }

    public function updateService($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $serviceModel = new ServiceModel();
        $service = $serviceModel->find($id);
        
        if (!$service) {
            return redirect()->to(base_url('admin/services'))
                            ->with('error', 'Service not found');
        }
        
        $rules = [
            'service_code' => "required|is_unique[services.service_code,id,{$id}]",
            'service_name' => 'required',
            'category' => 'required|in_list[laboratory,xray,other]',
            'charge' => 'permit_empty|numeric|greater_than_equal_to[0]'
        ];
        
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        
        $serviceModel->update($id, [
            'service_code' => strtoupper($this->request->getPost('service_code')),
            'service_name' => $this->request->getPost('service_name'),
            'category' => $this->request->getPost('category'),
            'description' => $this->request->getPost('description'),
            'charge' => $this->request->getPost('charge') ?? 0,
            'is_active' => $this->request->getPost('is_active') ?? 1
        ]);
        
        return redirect()->to(base_url('admin/services'))
                        ->with('success', 'Service updated successfully!');
    }

    public function deleteService($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $serviceModel = new ServiceModel();
        $service = $serviceModel->find($id);
        
        if (!$service) {
            return redirect()->to(base_url('admin/services'))
                            ->with('error', 'Service not found');
        }
        
        $serviceModel->delete($id);
        
        return redirect()->to(base_url('admin/services'))
                        ->with('success', 'Service deleted successfully!');
    }

    public function toggleServiceStatus($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $serviceModel = new ServiceModel();
        $service = $serviceModel->find($id);
        
        if (!$service) {
            return redirect()->to(base_url('admin/services'))
                            ->with('error', 'Service not found');
        }
        
        $newStatus = ($service['is_active'] == 1) ? 0 : 1;
        $serviceModel->update($id, ['is_active' => $newStatus]);
        
        return redirect()->to(base_url('admin/services'))
                        ->with('success', 'Service status updated successfully!');
    }
}