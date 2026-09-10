<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\UserModel;
use App\Models\XrayExaminationModel;
use App\Models\LabRequestModel;
use App\Models\NotificationModel;
use App\Models\ServiceModel;
use App\Models\PatientModel;
use App\Models\PaymentModel;

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
            if ($role === 'medtech') {
                return redirect()->to(base_url('medtech/dashboard'));
            }
            if ($role === 'radiologist') {
                return redirect()->to(base_url('radiologist/dashboard'));
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
        $xrayModel = new XrayExaminationModel();
        $notificationModel = new NotificationModel();
        $patientModel = new PatientModel();
        $paymentModel = new PaymentModel();

        $today = date('Y-m-d');
        $month = date('m');
        $year = date('Y');

        // ===== TOTAL PATIENTS =====
        $totalPatients = $patientModel->countAll();

        // ===== TODAY'S PATIENTS =====
        $todayPatients = $appointmentModel
            ->where('appointment_date', $today)
            ->countAllResults();

        // ===== PENDING REQUESTS (Lab + X-Ray) =====
        $pendingRequests = $labRequestModel
            ->where('status', 'pending')
            ->countAllResults();
        $pendingRequests += $xrayModel
            ->where('status', 'pending')
            ->countAllResults();

        // ===== COMPLETED REQUESTS (Lab + X-Ray) =====
        $completedRequests = $labRequestModel
            ->where('status', 'completed')
            ->countAllResults();
        $completedRequests += $xrayModel
            ->where('status', 'completed')
            ->countAllResults();

        // ===== RELEASED RESULTS (Lab + X-Ray) =====
        $releasedResults = $labRequestModel
            ->where('status', 'released')
            ->countAllResults();
        $releasedResults += $xrayModel
            ->where('status', 'released')
            ->countAllResults();

        // ===== TODAY'S REVENUE (from payments table) =====
        $todayPayments = $paymentModel
            ->where('DATE(payment_date)', $today)
            ->where('payment_status', 'paid')
            ->findAll();
        $todayRevenue = 0;
        foreach ($todayPayments as $p) {
            $todayRevenue += floatval($p['total_amount']);
        }

        // ===== MONTHLY REVENUE (from payments table) =====
        $monthlyPayments = $paymentModel
            ->where('MONTH(payment_date)', $month)
            ->where('YEAR(payment_date)', $year)
            ->where('payment_status', 'paid')
            ->findAll();
        $monthlyRevenue = 0;
        foreach ($monthlyPayments as $p) {
            $monthlyRevenue += floatval($p['total_amount']);
        }

        // ===== REVENUE DATA (Last 12 Months) =====
        $revenueData = $this->getRevenueData();

        // ===== VISITS DATA (Last 7 Days) =====
        $visitsData = $this->getVisitsData();

        // ===== REQUESTS DATA (Last 7 Days) =====
        $requestsData = $this->getRequestsData();

        // ===== TOP LAB TESTS =====
        $topTests = $this->getTopTests();

        // ===== RECENT ACTIVITY =====
        $recentActivity = $this->getRecentActivity();

        // ===== BUILD DATA ARRAY =====
        $data = [
            'totalPatients' => $totalPatients,
            'todayPatients' => $todayPatients,
            'pendingRequests' => $pendingRequests,
            'completedRequests' => $completedRequests,
            'releasedResults' => $releasedResults,
            'todayRevenue' => $todayRevenue,
            'monthlyRevenue' => $monthlyRevenue,
            'revenueData' => $revenueData,
            'visitsData' => $visitsData,
            'requestsData' => $requestsData,
            'topTests' => $topTests,
            'recentActivity' => $recentActivity,
        ];

        return view('Admin/dashboard', $data);
    }

    // ===== CHART HELPERS =====

    /**
     * Get revenue data for the current year
     * Uses payments table if available, otherwise falls back to appointments
     */
    private function getRevenueData()
    {
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $currentMonth = (int)date('m');
        $year = date('Y');
        $revenue = [];
        $paymentModel = new PaymentModel();

        // Try to get revenue from payments table
        for ($i = 1; $i <= $currentMonth; $i++) {
            $monthPayments = $paymentModel
                ->where('MONTH(payment_date)', $i)
                ->where('YEAR(payment_date)', $year)
                ->where('payment_status', 'paid')
                ->findAll();
            
            $total = 0;
            foreach ($monthPayments as $p) {
                $total += floatval($p['total_amount']);
            }
            $revenue[] = $total;
        }

        // If no payments data, fallback to appointment-based revenue
        if (empty(array_filter($revenue))) {
            $appointmentModel = new AppointmentModel();
            $revenue = [];
            for ($i = 1; $i <= $currentMonth; $i++) {
                $count = $appointmentModel
                    ->where('MONTH(appointment_date)', $i)
                    ->where('YEAR(appointment_date)', $year)
                    ->where('status', 'completed')
                    ->countAllResults();
                $revenue[] = $count * 500; // ₱500 per completed appointment
            }
        }

        // If still no data, provide sample data for display
        if (empty(array_filter($revenue))) {
            for ($i = 1; $i <= $currentMonth; $i++) {
                $revenue[] = rand(1000, 8000);
            }
        }

        return [
            'labels' => array_slice($months, 0, $currentMonth),
            'values' => $revenue,
        ];
    }

    /**
     * Get daily patient visits for the current week
     */
    private function getVisitsData()
    {
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $visits = [];
        $start = date('Y-m-d', strtotime('monday this week'));
        $appointmentModel = new AppointmentModel();

        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime($start . ' +' . $i . ' days'));
            $visits[] = $appointmentModel
                ->where('appointment_date', $date)
                ->countAllResults();
        }

        // If no data, provide sample data
        if (empty(array_filter($visits))) {
            $visits = [5, 8, 12, 10, 15, 6, 4];
        }

        return [
            'labels' => $days,
            'values' => $visits,
            'targets' => array_fill(0, 7, 10) // Optional target for chart
        ];
    }

    /**
     * Get diagnostic requests data for the current week
     */
    private function getRequestsData()
    {
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $requested = [];
        $completed = [];
        $start = date('Y-m-d', strtotime('monday this week'));
        $appointmentModel = new AppointmentModel();

        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime($start . ' +' . $i . ' days'));
            $total = $appointmentModel
                ->where('appointment_date', $date)
                ->countAllResults();
            $comp = $appointmentModel
                ->where('appointment_date', $date)
                ->where('status', 'completed')
                ->countAllResults();
            $requested[] = $total;
            $completed[] = $comp;
        }

        return [
            'labels' => $days,
            'requested' => $requested,
            'completed' => $completed,
        ];
    }

    /**
     * Get top requested lab tests
     */
    private function getTopTests()
    {
        $labRequests = (new LabRequestModel())->findAll();
        $testCounts = [];

        foreach ($labRequests as $req) {
            $services = $req['lab_services'] ?? '';
            
            // Handle different service formats
            if (is_string($services) && strpos($services, ',') !== false) {
                $serviceArray = array_map('trim', explode(',', $services));
            } elseif (is_string($services) && !empty($services)) {
                $serviceArray = [trim($services)];
            } else {
                $serviceArray = [];
            }
            
            foreach ($serviceArray as $service) {
                $service = trim($service);
                if (!empty($service) && $service !== 'NULL' && $service !== 'null' && $service !== '') {
                    $testCounts[$service] = ($testCounts[$service] ?? 0) + 1;
                }
            }
        }

        arsort($testCounts);
        $top = array_slice($testCounts, 0, 5, true);

        $result = [];
        $colors = ['#1D4ED8', '#0D9488', '#D97706', '#7C3AED', '#059669'];
        $i = 0;
        foreach ($top as $name => $count) {
            $result[] = [
                'name' => $name, 
                'count' => $count, 
                'color' => $colors[$i % count($colors)]
            ];
            $i++;
        }

        return $result;
    }

    /**
     * Get recent activity from notifications
     */
    private function getRecentActivity()
    {
        $notifications = (new NotificationModel())
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->findAll();

        $activities = [];
        foreach ($notifications as $notif) {
            $color = '#0D9488'; // default teal
            
            if ($notif['type'] === 'appointment') {
                $color = '#1D4ED8';
            } elseif ($notif['type'] === 'xray') {
                $color = '#7C3AED';
            } elseif ($notif['type'] === 'lab') {
                $color = '#059669';
            }
            
            $activities[] = [
                'message' => $notif['title'] . ' — ' . $notif['message'],
                'time' => $notif['created_at'],
                'color' => $color,
            ];
        }

        return $activities;
    }

    // =============================================
    // PATIENTS - COMPLETE FIX (ALL SOURCES)
    // =============================================
    public function patients()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $patientModel = new PatientModel();
        $labRequestModel = new LabRequestModel();
        $xrayModel = new XrayExaminationModel();
        $appointmentModel = new AppointmentModel();
        
        $allPatients = [];
        $seenKeys = [];
        
        // ========== 1. FROM PATIENTS TABLE (PRIMARY SOURCE - WALK-IN & SYNCED) ==========
        try {
            $patientsTable = $patientModel
                ->orderBy('created_at', 'DESC')
                ->findAll();
            
            foreach ($patientsTable as $patient) {
                $name = strtolower(trim($patient['full_name'] ?? ''));
                $key = $name . '|' . ($patient['age'] ?? '') . '|' . ($patient['gender'] ?? '');
                if (!empty($name) && !isset($seenKeys[$key])) {
                    $seenKeys[$key] = true;
                    $allPatients[] = [
                        'id' => $patient['id'] ?? 0,
                        'patient_code' => $patient['patient_code'] ?? 'N/A',
                        'full_name' => $patient['full_name'] ?? 'Unknown',
                        'email' => $patient['email'] ?? '',
                        'phone' => $patient['phone'] ?? '',
                        'age' => $patient['age'] ?? '',
                        'gender' => $patient['gender'] ?? '',
                        'source' => ucfirst($patient['source'] ?? 'Unknown'),
                        'last_visit' => $patient['created_at'] ?? null,
                        'created_at' => $patient['created_at'] ?? null,
                        'status' => 'active'
                    ];
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Patients - Patients table error: ' . $e->getMessage());
        }
        
        // ========== 2. FROM APPOINTMENTS (ONLINE - if not in patients table) ==========
        try {
            $appointmentPatients = $appointmentModel
                ->select('id, full_name, email, phone, age, gender, MAX(appointment_date) as last_visit, MIN(created_at) as created_at')
                ->groupBy('full_name')
                ->orderBy('full_name', 'ASC')
                ->findAll();
            
            foreach ($appointmentPatients as $patient) {
                $name = strtolower(trim($patient['full_name'] ?? ''));
                $key = $name . '|' . ($patient['age'] ?? '') . '|' . ($patient['gender'] ?? '');
                if (!empty($name) && !isset($seenKeys[$key])) {
                    $seenKeys[$key] = true;
                    $allPatients[] = [
                        'id' => $patient['id'] ?? 0,
                        'patient_code' => 'N/A',
                        'full_name' => $patient['full_name'] ?? 'Unknown',
                        'email' => $patient['email'] ?? '',
                        'phone' => $patient['phone'] ?? '',
                        'age' => $patient['age'] ?? '',
                        'gender' => $patient['gender'] ?? '',
                        'source' => 'Online',
                        'last_visit' => $patient['last_visit'] ?? null,
                        'created_at' => $patient['created_at'] ?? null,
                        'status' => 'active'
                    ];
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Patients - Appointments error: ' . $e->getMessage());
        }
        
        // ========== 3. FROM LAB REQUESTS (WALK-IN - if not in patients table) ==========
        try {
            $labPatients = $labRequestModel
                ->select('id, patient_name as full_name, age, gender, MAX(request_date) as last_visit, MIN(created_at) as created_at')
                ->groupBy('patient_name')
                ->orderBy('patient_name', 'ASC')
                ->findAll();
            
            foreach ($labPatients as $patient) {
                $name = strtolower(trim($patient['full_name'] ?? ''));
                $key = $name . '|' . ($patient['age'] ?? '') . '|' . ($patient['gender'] ?? '');
                if (!empty($name) && !isset($seenKeys[$key])) {
                    $seenKeys[$key] = true;
                    $allPatients[] = [
                        'id' => $patient['id'] ?? 0,
                        'patient_code' => 'N/A',
                        'full_name' => $patient['full_name'] ?? 'Unknown',
                        'email' => '',
                        'phone' => '',
                        'age' => $patient['age'] ?? '',
                        'gender' => $patient['gender'] ?? '',
                        'source' => 'Walk-in',
                        'last_visit' => $patient['last_visit'] ?? null,
                        'created_at' => $patient['created_at'] ?? null,
                        'status' => 'active'
                    ];
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Patients - Lab Requests error: ' . $e->getMessage());
        }
        
        // ========== 4. FROM X-RAY EXAMINATIONS (WALK-IN - if not in patients table) ==========
        try {
            $xrayPatients = $xrayModel
                ->select('id, patient_name as full_name, age, gender, MAX(exam_date) as last_visit, MIN(created_at) as created_at')
                ->groupBy('patient_name')
                ->orderBy('patient_name', 'ASC')
                ->findAll();
            
            foreach ($xrayPatients as $patient) {
                $name = strtolower(trim($patient['full_name'] ?? ''));
                $key = $name . '|' . ($patient['age'] ?? '') . '|' . ($patient['gender'] ?? '');
                if (!empty($name) && !isset($seenKeys[$key])) {
                    $seenKeys[$key] = true;
                    $allPatients[] = [
                        'id' => $patient['id'] ?? 0,
                        'patient_code' => 'N/A',
                        'full_name' => $patient['full_name'] ?? 'Unknown',
                        'email' => '',
                        'phone' => '',
                        'age' => $patient['age'] ?? '',
                        'gender' => $patient['gender'] ?? '',
                        'source' => 'Walk-in',
                        'last_visit' => $patient['last_visit'] ?? null,
                        'created_at' => $patient['created_at'] ?? null,
                        'status' => 'active'
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
    // APPROVE PATIENT - GENERATE PATIENT CODE
    // =============================================
    public function approvePatient($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $patientModel = new PatientModel();
        $patient = $patientModel->find($id);
        
        if (!$patient) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Patient not found'
            ]);
        }
        
        // Check if patient already has a code
        if (!empty($patient['patient_code']) && $patient['patient_code'] !== 'N/A') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Patient already has a code: ' . $patient['patient_code']
            ]);
        }
        
        // Generate new patient code
        $newCode = $patientModel->generatePatientCode();
        
        // Update patient
        $patientModel->update($id, [
            'patient_code' => $newCode,
            'source' => $patient['source'] ?? 'walk-in',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Get updated patient
        $updatedPatient = $patientModel->find($id);
        
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Patient approved successfully',
            'patient_code' => $newCode,
            'patient' => $updatedPatient
        ]);
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
        
        // ===== CREATE PATIENT RECORD =====
        $patientModel = new PatientModel();
        $patient = $patientModel->findOrCreateFromAppointment($appointment);
        
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
        
        $message = 'Appointment approved successfully!';
        if ($patient) {
            $message .= ' Patient Code: ' . $patient['patient_code'];
        }
        
        return redirect()->to(base_url('admin/appointments'))
                        ->with('success', $message);
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
    
    // ===== SERVICE MANAGEMENT WITH PAGINATION =====

    public function services()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $serviceModel = new ServiceModel();
        
        // ===== PAGINATION SETUP =====
        $perPage = 10;
        $page = (int)($this->request->getGet('page') ?? 1);
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $perPage;
        
        // ===== GET TOTAL COUNT =====
        $total = $serviceModel->countAll();
        $totalPages = max(1, ceil($total / $perPage));
        
        // Ensure page doesn't exceed total pages
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }
        
        // ===== GET SERVICES FOR CURRENT PAGE =====
        $services = $serviceModel
            ->orderBy('category', 'ASC')
            ->orderBy('service_name', 'ASC')
            ->limit($perPage, $offset)
            ->findAll();
        
        // ===== GET COUNTS FOR STATS =====
        $counts = $serviceModel->getCountByCategory();
        
        $data = [
            'services' => $services,
            'total' => $total,
            'lab_count' => $counts['laboratory'] ?? 0,
            'xray_count' => $counts['xray'] ?? 0,
            'other_count' => $counts['other'] ?? 0,
            'currentPage' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'startRow' => $offset + 1,
            'endRow' => min($offset + $perPage, $total)
        ];
        
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