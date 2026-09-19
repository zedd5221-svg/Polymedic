<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\UserModel;
use App\Models\DiagnosticRequestModel;
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

        $appointmentModel  = new AppointmentModel();
        $diagnosticModel   = new DiagnosticRequestModel();
        $notificationModel = new NotificationModel();
        $patientModel      = new PatientModel();
        $paymentModel      = new PaymentModel();

        $today = date('Y-m-d');
        $month = date('m');
        $year  = date('Y');

        // ===== TOTAL PATIENTS =====
        $totalPatients = $patientModel->countAll();

        // ===== TODAY'S PATIENTS =====
        $todayPatients = $appointmentModel
            ->where('appointment_date', $today)
            ->countAllResults();

        // ===== PENDING REQUESTS (Lab + X-Ray) =====
        $pendingRequests = $diagnosticModel
            ->where('status', 'pending')
            ->countAllResults();

        // ===== COMPLETED REQUESTS (Lab + X-Ray) =====
        $completedRequests = $diagnosticModel
            ->where('status', 'completed')
            ->countAllResults();

        // ===== RELEASED RESULTS (Lab + X-Ray) =====
        $releasedResults = $diagnosticModel
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

        // ===== DEPARTMENT VOLUME (Lab vs X-Ray, this week) =====
        $visitsData = $this->getDepartmentData();

        // ===== PENDING APPOINTMENTS (mini approval panel) =====
        $pendingAppointments = $this->getPendingAppointments(20);

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
            'pendingAppointments' => $pendingAppointments,
            'requestsData' => $requestsData,
            'topTests' => $topTests,
            'recentActivity' => $recentActivity,
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
        $paymentModel = new PaymentModel();

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

        if (empty(array_filter($revenue))) {
            $appointmentModel = new AppointmentModel();
            $revenue = [];
            for ($i = 1; $i <= $currentMonth; $i++) {
                $count = $appointmentModel
                    ->where('MONTH(appointment_date)', $i)
                    ->where('YEAR(appointment_date)', $year)
                    ->where('status', 'completed')
                    ->countAllResults();
                $revenue[] = $count * 500;
            }
        }

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
     * Patients handled by each department, per day of the current week.
     *
     * Both department counts come from the merged table now, split by
     * the type column. A patient with both lab and xray requests is
     * counted once under each department, which is what the stacked
     * chart is meant to show.
     */
    private function getDepartmentData()
    {
        $days  = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $start = date('Y-m-d', strtotime('monday this week'));

        $labModel  = new DiagnosticRequestModel();
        $xrayModel = new DiagnosticRequestModel();

        $lab  = [];
        $xray = [];

        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime($start . ' +' . $i . ' days'));

            $lab[] = $labModel
                ->where('type', DiagnosticRequestModel::TYPE_LAB)
                ->where('DATE(request_date)', $date)
                ->countAllResults();

            $xray[] = $xrayModel
                ->where('type', DiagnosticRequestModel::TYPE_XRAY)
                ->where('DATE(request_date)', $date)
                ->countAllResults();
        }

        // No placeholder numbers here on purpose. A dashboard showing
        // invented patient counts is worse than one showing an empty
        // week, and the view already handles the empty case.
        return [
            'labels' => $days,
            'lab'    => $lab,
            'xray'   => $xray,
        ];
    }

    /**
     * Appointments waiting for approval, soonest first.
     * The dashboard panel pages through these four at a time.
     */
    private function getPendingAppointments(int $limit = 20)
    {
        $model = new AppointmentModel();

        return $model->where('status', 'pending')
                     ->orderBy('appointment_date', 'ASC')
                     ->orderBy('appointment_time', 'ASC')
                     ->findAll($limit);
    }

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

    private function getTopTests()
    {
        $labRequests = (new DiagnosticRequestModel())
            ->where('type', DiagnosticRequestModel::TYPE_LAB)
            ->findAll();

        $testCounts = [];

        foreach ($labRequests as $req) {
            $services = $req['lab_services'] ?? '';

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

    private function getRecentActivity()
    {
        $notifications = (new NotificationModel())
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->findAll();

        $activities = [];
        foreach ($notifications as $notif) {
            $color = '#0D9488';

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
    //
    // A patient only exists once they've been registered. That means:
    //   - a row in the patients table, OR
    //   - an appointment that has been approved (pending bookings
    //     are not patients yet), OR
    //   - a real lab or x-ray request (not cancelled).
    //
    // Pending online bookings are deliberately excluded here. They
    // show on the Appointments page until the front desk approves
    // them, which is what creates the patients-table row.
    // =============================================
    public function patients()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $patientModel     = new PatientModel();
        $diagnosticModel  = new DiagnosticRequestModel();
        $appointmentModel = new AppointmentModel();

        $allPatients = [];
        $seenKeys = [];

        // ========== 1. FROM PATIENTS TABLE ==========
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

        // ========== 2. FROM APPOINTMENTS (APPROVED / COMPLETED ONLY) ==========
        // A pending online booking is not a patient yet. The front
        // desk must approve it first. Filtering on status here stops
        // unapproved bookings from appearing on the Patients page.
        try {
            $appointmentPatients = $appointmentModel
                ->select('id, full_name, email, phone, age, gender, MAX(appointment_date) as last_visit, MIN(created_at) as created_at')
                ->whereIn('status', ['approved', 'completed'])
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

        // ========== 3. FROM LAB REQUESTS (NON-CANCELLED) ==========
        try {
            $labPatients = $diagnosticModel
                ->select('id, patient_name as full_name, age, gender, MAX(request_date) as last_visit, MIN(created_at) as created_at')
                ->where('type', DiagnosticRequestModel::TYPE_LAB)
                ->whereIn('status', ['pending', 'in_progress', 'completed', 'released'])
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

        // ========== 4. FROM X-RAY EXAMINATIONS (NON-CANCELLED) ==========
        try {
            $xrayPatients = $diagnosticModel
                ->select('id, patient_name as full_name, age, gender, MAX(request_date) as last_visit, MIN(created_at) as created_at')
                ->where('type', DiagnosticRequestModel::TYPE_XRAY)
                ->whereIn('status', ['pending', 'in_progress', 'completed', 'released'])
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

        usort($allPatients, function($a, $b) {
            return strtotime($b['last_visit'] ?? '0') - strtotime($a['last_visit'] ?? '0');
        });

        $data['patients'] = $allPatients;
        $data['total'] = count($allPatients);

        return view('Admin/patients', $data);
    }

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

        if (!empty($patient['patient_code']) && $patient['patient_code'] !== 'N/A') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Patient already has a code: ' . $patient['patient_code']
            ]);
        }

        $newCode = $patientModel->generatePatientCode();

        $patientModel->update($id, [
            'patient_code' => $newCode,
            'source' => $patient['source'] ?? 'walk-in',
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $updatedPatient = $patientModel->find($id);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Patient approved successfully',
            'patient_code' => $newCode,
            'patient' => $updatedPatient
        ]);
    }

    public function visits()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        return view('Admin/visits');
    }

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

    public function createUser()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $username  = $this->request->getPost('username');
        $password  = $this->request->getPost('password');
        $email     = $this->request->getPost('email');
        $full_name = $this->request->getPost('full_name');
        $role      = $this->request->getPost('role');
        $status    = $this->request->getPost('status') ?? 'active';

        if (empty($username) || empty($password) || empty($email) || empty($full_name) || empty($role)) {
            return redirect()->to(base_url('admin/users'))
                            ->with('error', 'All fields are required');
        }

        $userModel = new UserModel();
        if ($userModel->getUserByUsername($username)) {
            return redirect()->to(base_url('admin/users'))
                            ->with('error', 'Username already exists');
        }

        /*
         * PRC license is required only for med_tech and radiologist.
         * For other roles we force the value to NULL so nothing stale
         * can be persisted by a crafted POST.
         */
        $prcInput = trim((string) $this->request->getPost('prc_license'));

        if ($userModel->requiresPrcLicense($role)) {
            if ($prcInput === '') {
                return redirect()->back()
                                ->withInput()
                                ->with('error', 'PRC License No. is required for Medical Technologist and Radiologist accounts.');
            }
            $prcValue = $prcInput;
        } else {
            $prcValue = null;
        }

        $userData = [
            'username'    => $username,
            'password'    => $password,
            'email'       => $email,
            'full_name'   => $full_name,
            'role'        => $role,
            'prc_license' => $prcValue,
            'status'      => $status
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

        $email     = $this->request->getPost('email');
        $full_name = $this->request->getPost('full_name');
        $role      = $this->request->getPost('role');
        $status    = $this->request->getPost('status');
        $password  = $this->request->getPost('password');

        $prcInput = trim((string) $this->request->getPost('prc_license'));

        if ($userModel->requiresPrcLicense($role)) {
            if ($prcInput === '') {
                return redirect()->back()
                                ->withInput()
                                ->with('error', 'PRC License No. is required for Medical Technologist and Radiologist accounts.');
            }
            $prcValue = $prcInput;
        } else {
            /* Moving a user off a clinical role clears the PRC number. */
            $prcValue = null;
        }

        $updateData = [
            'email'       => $email,
            'full_name'   => $full_name,
            'role'        => $role,
            'prc_license' => $prcValue,
            'status'      => $status
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

        // The dashboard's approval panel links carry ?from=dashboard,
        // so the user is returned to wherever they approved from.
        $returnTo = $this->request->getGet('from') === 'dashboard'
            ? base_url('admin/dashboard')
            : base_url('admin/appointments');

        $model = new AppointmentModel();
        $appointment = $model->find($id);

        if (!$appointment) {
            return redirect()->to($returnTo)
                            ->with('error', 'Appointment not found');
        }

        if (in_array($appointment['status'], ['completed', 'cancelled', 'no_show'])) {
            return redirect()->to($returnTo)
                            ->with('error', 'This appointment cannot be approved');
        }

        $model->update($id, [
            'status'       => 'approved',
            'arrival_time' => date('Y-m-d H:i:s')
        ]);

        // ===== CREATE PATIENT RECORD =====
        $patientModel = new PatientModel();
        $patient = $patientModel->findOrCreateFromAppointment($appointment);

        $diagnosticModel = new DiagnosticRequestModel();

        // ===== LAB REQUEST =====
        $labServices = json_decode($appointment['lab_services'], true) ?? [];
        if (!empty($labServices)) {
            $existing = $diagnosticModel
                ->where('appointment_id', $id)
                ->where('type', DiagnosticRequestModel::TYPE_LAB)
                ->first();

            if (!$existing) {
                $serviceList = $this->formatLabServices($labServices);

                $diagnosticModel->insert([
                    'reference_number' => 'LAB-' . date('y') . '-' . strtoupper(bin2hex(random_bytes(3))),
                    'type'             => DiagnosticRequestModel::TYPE_LAB,
                    'appointment_id'   => $id,
                    'patient_name'     => $appointment['full_name'],
                    'age'              => $appointment['age'],
                    'gender'           => $appointment['gender'],
                    'email'            => $appointment['email'] ?? null,
                    'phone'            => $appointment['phone'] ?? null,
                    'services'         => $serviceList,
                    'request_date'     => $appointment['appointment_date'],
                    'priority'         => 'routine',
                    'status'           => DiagnosticRequestModel::STATUS_PENDING,
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

        // ===== X-RAY REQUEST =====
        $xrayServices = json_decode($appointment['xray_services'], true) ?? [];
        if (!empty($xrayServices)) {
            $existing = $diagnosticModel
                ->where('appointment_id', $id)
                ->where('type', DiagnosticRequestModel::TYPE_XRAY)
                ->first();

            if (!$existing) {
                $examType = $this->formatXrayServices($xrayServices);

                $diagnosticModel->insert([
                    'reference_number' => 'XR-' . date('y') . '-' . strtoupper(bin2hex(random_bytes(3))),
                    'type'             => DiagnosticRequestModel::TYPE_XRAY,
                    'appointment_id'   => $id,
                    'patient_name'     => $appointment['full_name'],
                    'age'              => $appointment['age'],
                    'gender'           => $appointment['gender'],
                    'email'            => $appointment['email'] ?? null,
                    'phone'            => $appointment['phone'] ?? null,
                    'services'         => $examType,
                    'request_date'     => $appointment['appointment_date'],
                    'priority'         => 'Routine',
                    'status'           => DiagnosticRequestModel::STATUS_PENDING,
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

        return redirect()->to($returnTo)
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

    public function syncXrayExaminations()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $appointmentModel = new AppointmentModel();
        $diagnosticModel  = new DiagnosticRequestModel();

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

            $existing = $diagnosticModel
                ->where('appointment_id', $appt['id'])
                ->where('type', DiagnosticRequestModel::TYPE_XRAY)
                ->first();

            if (!$existing) {
                $examType = $this->formatXrayServices($xrayServices);

                $diagnosticModel->insert([
                    'reference_number' => 'XR-' . date('y') . '-' . strtoupper(bin2hex(random_bytes(3))),
                    'type'             => DiagnosticRequestModel::TYPE_XRAY,
                    'appointment_id'   => $appt['id'],
                    'patient_name'     => $appt['full_name'],
                    'age'              => $appt['age'],
                    'gender'           => $appt['gender'],
                    'services'         => $examType,
                    'request_date'     => $appt['appointment_date'],
                    'priority'         => 'Routine',
                    'status'           => DiagnosticRequestModel::STATUS_PENDING,
                ]);
                $created++;
            } else {
                $skipped++;
            }
        }

        return redirect()->to(base_url('admin/appointments'))
                        ->with('success', "Synced {$created} X-Ray examinations. Skipped {$skipped} existing.");
    }

    public function syncLabRequests()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $appointmentModel = new AppointmentModel();
        $diagnosticModel  = new DiagnosticRequestModel();

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

            $existing = $diagnosticModel
                ->where('appointment_id', $appt['id'])
                ->where('type', DiagnosticRequestModel::TYPE_LAB)
                ->first();

            if (!$existing) {
                $examType = $this->formatLabServices($labServices);

                $diagnosticModel->insert([
                    'reference_number' => 'LAB-' . date('y') . '-' . strtoupper(bin2hex(random_bytes(3))),
                    'type'             => DiagnosticRequestModel::TYPE_LAB,
                    'appointment_id'   => $appt['id'],
                    'patient_name'     => $appt['full_name'],
                    'age'              => $appt['age'],
                    'gender'           => $appt['gender'],
                    'services'         => $examType,
                    'request_date'     => $appt['appointment_date'],
                    'priority'         => 'routine',
                    'status'           => DiagnosticRequestModel::STATUS_PENDING,
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

    $perPage = 10;
    $page = (int) ($this->request->getGet('page') ?? 1);
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $perPage;

    /*
     * Category filter. Accepts 'laboratory', 'xray', or 'other'.
     * Anything else — including 'all' and empty string — is treated
     * as "no filter". This is what makes the tabs on the services
     * page actually narrow the list.
     */
    $categoryFilter = strtolower(trim((string) $this->request->getGet('category')));
    $allowed        = ['laboratory', 'xray', 'other'];

    if (!in_array($categoryFilter, $allowed, true)) {
        $categoryFilter = null;
    }

    if ($categoryFilter !== null) {
        $serviceModel->where('category', $categoryFilter);
    }

    $total      = $serviceModel->countAllResults(false);
    $totalPages = max(1, (int) ceil($total / $perPage));

    if ($page > $totalPages) {
        $page   = $totalPages;
        $offset = ($page - 1) * $perPage;
    }

    $services = $serviceModel
        ->orderBy('category', 'ASC')
        ->orderBy('service_name', 'ASC')
        ->limit($perPage, $offset)
        ->findAll();

    /*
     * The category counts must always reflect the entire table,
     * not the current filter. A fresh model instance is used so
     * the where('category', ...) above does not carry over.
     */
    $counts = (new ServiceModel())->getCountByCategory();

    $data = [
        'services'        => $services,
        'total'           => $total,
        'lab_count'       => $counts['laboratory'] ?? 0,
        'xray_count'      => $counts['xray'] ?? 0,
        'other_count'     => $counts['other'] ?? 0,
        'currentPage'     => $page,
        'perPage'         => $perPage,
        'totalPages'      => $totalPages,
        'startRow'        => $total > 0 ? $offset + 1 : 0,
        'endRow'          => min($offset + $perPage, $total),
        'active_category' => $categoryFilter ?? 'all',
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

    // =============================================
    // DIAGNOSTIC REQUESTS
    // Same queue the receptionist handles at the front desk, exposed
    // here so an administrator can create, adjust, and print requests
    // without leaving the admin area.
    // =============================================

    public function diagnosticRequests()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $diagnosticModel = new DiagnosticRequestModel();
        $serviceModel    = new ServiceModel();

        /*
         * All diagnostic rows live in one table now. A single query
         * returns every request, and each row carries its own type.
         * The view and the client script read the same shape as before.
         */
        $rows = $diagnosticModel
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $requests = [];
        foreach ($rows as $row) {
            $requests[] = $this->buildDiagnosticRow($row, $row['type'] ?? 'lab');
        }

        $counts = [
            'total'       => count($requests),
            'pending'     => 0,
            'processing'  => 0,
            'completed'   => 0,
            'released'    => 0,
            'cancelled'   => 0,
        ];
        foreach ($requests as $r) {
            $s = $r['status'] ?? 'pending';
            if ($s === 'in_progress') { $s = 'processing'; }
            if (isset($counts[$s])) { $counts[$s]++; }
        }

        $data = [
            'requests'     => $requests,
            'counts'       => $counts,
            'labServices'  => $serviceModel->where('category', 'laboratory')
                                           ->where('is_active', 1)
                                           ->orderBy('service_name', 'ASC')
                                           ->findAll(),
            'xrayServices' => $serviceModel->where('category', 'xray')
                                           ->where('is_active', 1)
                                           ->orderBy('service_name', 'ASC')
                                           ->findAll(),
        ];

        return view('Admin/diagnostic_requests', $data);
    }

    /**
     * Reduce one diagnostic_requests row to the shape the view expects.
     *
     * Source is derived from the appointment_id foreign key, the same
     * way the receptionist controller does it. A populated appointment_id
     * means the request came from an approved online booking; zero or
     * NULL means it was created by hand at the front desk.
     *
     * The DiagnosticRequestModel decorates each row with both the new
     * (services) and the legacy (lab_services / exam_type) column
     * names, so this method doesn't need to know which side the row
     * originally came from.
     */
    private function buildDiagnosticRow(array $row, string $type): array
    {
        if ($type === 'xray') {
            $servicesRaw = $row['services'] ?? $row['exam_type'] ?? '';
            $requestType = 'X-Ray';
            $priority    = $row['priority'] ?? 'Routine';
        } else {
            $servicesRaw = $row['services'] ?? $row['lab_services'] ?? '';
            $requestType = 'Laboratory';
            $priority    = $row['priority'] ?? 'routine';
        }

        $services = $this->splitServices($servicesRaw);
        $isStat   = strtolower((string) $priority) === 'stat';

        return [
            'id'             => $row['id'] ?? 0,
            'type'           => $type,
            'reference'      => $this->diagnosticReference($row, $type),
            'patient_name'   => $row['patient_name'] ?? 'Unknown',
            'patient_age'    => $row['age'] ?? '',
            'patient_gender' => $row['gender'] ?? '',
            'request_type'   => $requestType,
            'source'         => !empty($row['appointment_id']) ? 'Online' : 'Walk-in',
            'status'         => $row['status'] ?? 'pending',
            'priority'       => $isStat ? 'stat' : 'routine',
            'doctor_name'    => $row['doctor_name'] ?? '',
            'phone'          => $row['phone'] ?? '',
            'email'          => $row['email'] ?? '',
            'services'       => $services,
            'created_at'     => $row['created_at'] ?? null,
            'updated_at'     => $row['updated_at'] ?? null,
            'released_at'    => $row['released_at'] ?? null,
            'findings'       => $row['findings'] ?? '',
            'interpretation' => $row['interpretation'] ?? '',
            'remarks'        => $row['remarks'] ?? '',
        ];
    }

    private function splitServices($raw): array
    {
        if (is_array($raw)) {
            return array_values(array_filter(array_map('trim', $raw)));
        }
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    private function diagnosticReference(array $row, string $type): string
    {
        if (!empty($row['reference_number'])) {
            return (string) $row['reference_number'];
        }
        $prefix = $type === 'xray' ? 'XR' : 'LAB';
        $year   = !empty($row['created_at']) ? date('y', strtotime($row['created_at'])) : date('y');
        return $prefix . '-' . $year . '-' . str_pad((string) ($row['id'] ?? 0), 4, '0', STR_PAD_LEFT);
    }

    public function createDiagnosticRequest()
    {
    $redirect = $this->checkAuth();
    if ($redirect) return $redirect;

    $post = $this->request->getPost();

    $errors = [];

    if (trim((string) ($post['patient_name'] ?? '')) === '') {
        $errors[] = 'Patient name is required.';
    }
    if (!is_numeric($post['age'] ?? null) || (int) $post['age'] < 0 || (int) $post['age'] > 130) {
        $errors[] = 'Age must be a number between 0 and 130.';
    }
    if (!in_array($post['gender'] ?? '', ['Male', 'Female'], true)) {
        $errors[] = 'Sex must be Male or Female.';
    }
    if (!in_array($post['request_type'] ?? '', ['lab', 'xray'], true)) {
        $errors[] = 'Select Laboratory or X-Ray as the request type.';
    }
    $services = $post['services'] ?? [];
    if (!is_array($services) || count(array_filter($services)) === 0) {
        $errors[] = 'Select at least one service.';
    }

    if (!empty($errors)) {
        return redirect()->back()
                         ->withInput()
                         ->with('validation_errors', $errors);
    }

    $requestType = $post['request_type'];
    $serviceList = implode(', ', array_map('trim', (array) $services));
    $priority    = ($post['priority'] ?? 'routine') === 'stat' ? 'stat' : 'routine';
    $patientName = trim((string) $post['patient_name']);
    $age         = (int) $post['age'];
    $gender      = (string) $post['gender'];
    $doctor      = trim((string) ($post['doctor_name'] ?? ''));
    $phone       = trim((string) ($post['phone'] ?? ''));
    $email       = trim((string) ($post['email'] ?? ''));
    $today       = date('Y-m-d');

    // ===== CREATE OR REUSE PATIENT RECORD =====
    // This is the step the admin path was missing. It matches the
    // receptionist's logic so both create the same patient row with
    // a generated patient_code.
    $patientModel = new PatientModel();
    $patientCode  = null;

    try {
        $existingPatient = null;

        // 1. Match by email (most reliable)
        if (!empty($email)) {
            $existingPatient = $patientModel->where('email', $email)->first();
        }

        // 2. Match by name + age + gender
        if (!$existingPatient) {
            $existingPatient = $patientModel->where('full_name', $patientName)
                                            ->where('age', $age)
                                            ->where('gender', $gender)
                                            ->first();
        }

        // 3. Match by phone, but only if name/age/gender also line up
        if (!$existingPatient && !empty($phone)) {
            $byPhone = $patientModel->where('phone', $phone)->first();

            if ($byPhone
                && $byPhone['full_name'] === $patientName
                && (int) $byPhone['age'] === $age
                && $byPhone['gender'] === $gender) {
                $existingPatient = $byPhone;
            }
        }

        if ($existingPatient) {
            // Fill in any blank fields on the existing record.
            $updates = [];
            if (empty($existingPatient['email']) && !empty($email)) {
                $updates['email'] = $email;
            }
            if (empty($existingPatient['phone']) && !empty($phone)) {
                $updates['phone'] = $phone;
            }
            if (($existingPatient['source'] ?? '') !== 'walk-in') {
                $updates['source'] = 'walk-in';
            }
            if (!empty($updates)) {
                $patientModel->update($existingPatient['id'], $updates);
            }

            $patient     = $patientModel->find($existingPatient['id']);
            $patientCode = $patient['patient_code'] ?? null;
        } else {
            // New patient — generate a code and insert.
            $newCode = $patientModel->generatePatientCode();

            $patientModel->insert([
                'patient_code' => $newCode,
                'full_name'    => $patientName,
                'email'        => $email !== '' ? $email : null,
                'phone'        => $phone !== '' ? $phone : null,
                'age'          => $age,
                'gender'       => $gender,
                'source'       => 'walk-in',
            ]);

            $patientCode = $newCode;
        }
    } catch (\Exception $e) {
        log_message('error', 'Admin walk-in patient creation failed: ' . $e->getMessage());
        // Continue without a patient code rather than failing the request.
    }

    // ===== CREATE THE DIAGNOSTIC REQUEST =====
    try {
        $model = new DiagnosticRequestModel();

        $prefix = $requestType === 'xray' ? 'XR' : 'LAB';

        $model->insert([
            'reference_number' => $prefix . '-' . date('y') . '-' . strtoupper(bin2hex(random_bytes(3))),
            'type'             => $requestType,
            'appointment_id'   => 0,
            'patient_name'     => $patientName,
            'patient_code'     => $patientCode,   // <- now populated
            'age'              => $age,
            'gender'           => $gender,
            'email'            => $email !== '' ? $email : null,
            'phone'            => $phone !== '' ? $phone : null,
            'services'         => $serviceList,
            'request_date'     => $today,
            'doctor_name'      => $doctor,
            'priority'         => $requestType === 'xray'
                                    ? ($priority === 'stat' ? 'STAT' : 'Routine')
                                    : $priority,
            'status'           => DiagnosticRequestModel::STATUS_PENDING,
        ]);

        NotificationModel::dispatch(
            $requestType,
            $requestType === 'lab' ? 'New Laboratory Request' : 'New X-Ray Request',
            'New ' . ($requestType === 'lab' ? 'laboratory' : 'x-ray') . ' request for ' . $patientName,
            null,
            null,
            true,
            $gender
        );

    } catch (\Exception $e) {
        log_message('error', 'Create diagnostic request error: ' . $e->getMessage());
        return redirect()->back()->withInput()->with('error', 'Could not create the request.');
    }

    return redirect()->to(base_url('admin/diagnostic-requests'))
                     ->with('success', 'Diagnostic request created.');
    }

    public function updateDiagnosticStatus($id, $type, $status)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $type   = strtolower((string) $type);
        $status = strtolower((string) $status);

        if (!in_array($type, ['lab', 'xray'], true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unknown request type.']);
        }
        if (!in_array($status, ['pending', 'in_progress', 'completed', 'released', 'cancelled'], true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unknown status.']);
        }

        try {
            $model = new DiagnosticRequestModel();

            $row = $model
                ->where('id', (int) $id)
                ->where('type', $type)
                ->first();

            if (!$row) {
                return $this->response->setJSON(['success' => false, 'message' => 'Request not found.']);
            }

            $update = ['status' => $status];
            if ($status === 'released') {
                $update['released_at'] = date('Y-m-d H:i:s');
            }

            $model->update((int) $id, $update);

            return $this->response->setJSON(['success' => true, 'message' => 'Status updated.']);

        } catch (\Exception $e) {
            log_message('error', 'Update diagnostic status error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Could not update the status.']);
        }
    }

    public function deleteDiagnosticRequest($id, $type)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $type = strtolower((string) $type);
        if (!in_array($type, ['lab', 'xray'], true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unknown request type.']);
        }

        try {
            $model = new DiagnosticRequestModel();

            $row = $model
                ->where('id', (int) $id)
                ->where('type', $type)
                ->first();

            if (!$row) {
                return $this->response->setJSON(['success' => false, 'message' => 'Request not found.']);
            }

            $model->delete((int) $id);

            return $this->response->setJSON(['success' => true, 'message' => 'Request deleted.']);

        } catch (\Exception $e) {
            log_message('error', 'Delete diagnostic request error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Could not delete the request.']);
        }
    }

    public function getRequestDetails($id, $type)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $type = strtolower((string) $type);
        if (!in_array($type, ['lab', 'xray'], true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unknown request type.']);
        }

        try {
            $model = new DiagnosticRequestModel();

            $row = $model
                ->where('id', (int) $id)
                ->where('type', $type)
                ->first();

            if (!$row) {
                return $this->response->setJSON(['success' => false, 'message' => 'Request not found.']);
            }

            $data = [
                'patient_name'   => $row['patient_name'] ?? '',
                'age'            => $row['age'] ?? '',
                'gender'         => $row['gender'] ?? '',
                'phone'          => $row['phone'] ?? '',
                'email'          => $row['email'] ?? '',
                'doctor_name'    => $row['doctor_name'] ?? '',
                'source'         => !empty($row['appointment_id']) ? 'Online' : 'Walk-in',
                'status'         => $row['status'] ?? 'pending',
                'priority'       => $row['priority'] ?? 'routine',
                'services'       => $row['services'] ?? '',
                'created_at'     => $row['created_at'] ?? null,
                'updated_at'     => $row['updated_at'] ?? null,
                'released_at'    => $row['released_at'] ?? null,
                'findings'       => $row['findings'] ?? '',
                'interpretation' => $row['interpretation'] ?? '',
                'remarks'        => $row['remarks'] ?? '',
                'patient_code'   => $row['patient_code'] ?? null,
            ];

            return $this->response->setJSON(['success' => true, 'data' => $data]);

        } catch (\Exception $e) {
            log_message('error', 'Get request details error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Could not load the request.']);
        }
    }

    public function printRequest($id, $type)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $type = strtolower((string) $type);
        if (!in_array($type, ['lab', 'xray'], true)) {
            return redirect()->to(base_url('admin/diagnostic-requests'));
        }

        $model = new DiagnosticRequestModel();

        $row = $model
            ->where('id', (int) $id)
            ->where('type', $type)
            ->first();

        if (!$row) {
            return redirect()->to(base_url('admin/diagnostic-requests'))
                             ->with('error', 'Request not found.');
        }

        /*
         * The print view already handles both types. It reads a `type`
         * key from the row, so add it here rather than duplicating the
         * print template.
         */
        $row['type'] = $type;

        return view('Receptionist/print_request', ['request' => $row, 'type' => $type]);
    }
}