<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\UserModel;
use App\Models\DiagnosticRequestModel;
use App\Models\NotificationModel;
use App\Models\ServiceModel;
use App\Models\PatientModel;
use App\Models\PaymentModel;
use App\Models\AvailabilityModel;
use App\Models\SettingsModel;

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

        $totalPatients = $patientModel->countAll();

        $todayPatients = $appointmentModel
            ->where('appointment_date', $today)
            ->countAllResults();

        $pendingRequests = $diagnosticModel
            ->where('status', 'pending')
            ->countAllResults();

        $completedRequests = $diagnosticModel
            ->where('status', 'completed')
            ->countAllResults();

        $releasedResults = $diagnosticModel
            ->where('status', 'released')
            ->countAllResults();

        // Sum amount_paid (money actually collected), not total_amount.
        $todayPayments = $paymentModel
            ->where('DATE(payment_date)', $today)
            ->where('payment_status', 'paid')
            ->findAll();
        $todayRevenue = 0;
        foreach ($todayPayments as $p) {
            $todayRevenue += floatval($p['amount_paid'] ?? 0);
        }

        $monthlyPayments = $paymentModel
            ->where('MONTH(payment_date)', $month)
            ->where('YEAR(payment_date)', $year)
            ->where('payment_status', 'paid')
            ->findAll();
        $monthlyRevenue = 0;
        foreach ($monthlyPayments as $p) {
            $monthlyRevenue += floatval($p['amount_paid'] ?? 0);
        }

        $revenueData = $this->getRevenueData();

        $visitsData = $this->getDepartmentData();

        $pendingAppointments = $this->getPendingAppointments(20);

        $requestsData = $this->getRequestsData();

        $topTests = $this->getTopTests();

        $recentActivity = $this->getRecentActivity();

        $kpiTrends = $this->getKpiTrends(14, (int) $totalPatients);

        $data = [
            'kpiTrends' => $kpiTrends,
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

    private function getKpiTrends(int $days, int $totalPatients): array
    {
        $db    = db_connect();
        $start = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        $end   = date('Y-m-d');

        $dates = [];
        for ($i = 0; $i < $days; $i++) {
            $dates[] = date('Y-m-d', strtotime($start . ' +' . $i . ' days'));
        }

        $series = function (string $table, string $dateExpr, string $valueExpr, array $where = [], array $whereIn = []) use ($db, $dates, $start, $end) {
            $b = $db->table($table)
                ->select("$dateExpr AS d, $valueExpr AS v", false)
                ->where("$dateExpr >=", $start)
                ->where("$dateExpr <=", $end);
            foreach ($where as $col => $val) {
                $b->where($col, $val);
            }
            foreach ($whereIn as $col => $vals) {
                $b->whereIn($col, $vals);
            }
            $rows = $b->groupBy('d')->get()->getResultArray();

            $map = [];
            foreach ($rows as $r) {
                $map[$r['d']] = (float) $r['v'];
            }
            return array_map(fn($d) => $map[$d] ?? 0, $dates);
        };

        $newPatients = $series('patients', 'DATE(created_at)', 'COUNT(*)');
        $patients    = [];
        $running     = $totalPatients - array_sum($newPatients);
        foreach ($newPatients as $n) {
            $running   += $n;
            $patients[] = $running;
        }

        // Use amount_paid so the chart matches the Payments/Billing pages.
        $revenue = $series('payments', 'DATE(payment_date)', 'SUM(amount_paid)', ['payment_status' => 'paid']);

        $monthStart = date('Y-m-01');
        $mtdRows = $db->table('payments')
            ->select('DATE(payment_date) AS d, SUM(amount_paid) AS v', false)
            ->where('payment_status', 'paid')
            ->where("DATE(payment_date) >=", $monthStart)
            ->where("DATE(payment_date) <=", $end)
            ->groupBy('d')->get()->getResultArray();
        $mtdMap = [];
        foreach ($mtdRows as $r) {
            $mtdMap[$r['d']] = (float) $r['v'];
        }
        $mtd     = [];
        $sum     = 0;
        $cursor  = $monthStart;
        $mtdFull = [];
        while ($cursor <= $end) {
            $sum += $mtdMap[$cursor] ?? 0;
            $mtdFull[$cursor] = $sum;
            $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
        }
        foreach ($dates as $d) {
            $mtd[] = $mtdFull[$d] ?? 0;
        }

        return [
            'dates'         => $dates,
            'patients'      => $patients,
            'today'         => $series('appointments', 'appointment_date', 'COUNT(*)'),
            'pending'       => $series('diagnostic_requests', 'DATE(request_date)', 'COUNT(*)'),
            'completed'     => $series('diagnostic_requests', 'DATE(updated_at)', 'COUNT(*)', ["status" => "completed"]),
            'released'      => $series('diagnostic_requests', 'DATE(released_at)', 'COUNT(*)', ['status' => 'released']),
            'revenue_today' => $revenue,
            'revenue_month' => $mtd,
        ];
    }

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
                $total += floatval($p['amount_paid'] ?? 0);
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

        return [
            'labels' => $days,
            'lab'    => $lab,
            'xray'   => $xray,
        ];
    }

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
    // PATIENTS
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
                        'id' => $this->resolvePatientId(null, $patient['full_name'] ?? '', $patient['age'] ?? null) ?? 0,
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
                        'id' => $this->resolvePatientId(null, $patient['full_name'] ?? '', $patient['age'] ?? null) ?? 0,
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
                        'id' => $this->resolvePatientId(null, $patient['full_name'] ?? '', $patient['age'] ?? null) ?? 0,
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

    public function viewPatient($id = null)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $id = (int) $id;
        if ($id <= 0) {
            return redirect()->to(base_url('admin/patients'))
                             ->with('error', 'Invalid patient.');
        }

        $patientModel = new PatientModel();
        $patient      = $patientModel->find($id);

        if (! $patient) {
            return redirect()->to(base_url('admin/patients'))
                             ->with('error', 'Patient not found.');
        }

        $source      = strtolower((string) ($patient['source'] ?? ''));
        $serviceType = (strpos($source, 'walk-in') !== false) ? 'Walk-in' : 'Online';

        $appointment = [
            'id'               => $patient['id']           ?? null,
            'reference_number' => $patient['patient_code'] ?? '—',
            'full_name'        => $patient['full_name']    ?? '',
            'gender'           => $patient['gender']       ?? '',
            'age'              => $patient['age']          ?? '',
            'email'            => $patient['email']        ?? '',
            'phone'            => $patient['phone']        ?? '',
            'appointment_date' => $patient['created_at']   ?? null,
            'appointment_time' => null,
            'arrival_time'     => null,
            'created_at'       => $patient['created_at']   ?? null,
            'updated_at'       => $patient['updated_at']   ?? null,
            'status'           => 'approved',
            'service_type'     => $serviceType,
            'other_requests'   => '',
        ];

        $lab_services  = [];
        $xray_services = [];

        try {
            $diagnosticModel = new DiagnosticRequestModel();

            $rows = $diagnosticModel
                ->where('patient_name', $appointment['full_name'])
                ->orderBy('created_at', 'DESC')
                ->limit(20)
                ->findAll();

            foreach ($rows as $row) {
                $services = array_filter(array_map('trim', explode(',', (string) ($row['services'] ?? ''))));
                if (($row['type'] ?? '') === 'xray') {
                    $xray_services = array_merge($xray_services, $services);
                } else {
                    $lab_services = array_merge($lab_services, $services);
                }
            }

            $lab_services  = array_values(array_unique($lab_services));
            $xray_services = array_values(array_unique($xray_services));
        } catch (\Exception $e) {
            log_message('error', 'Admin viewPatient history error: ' . $e->getMessage());
        }

        return view('Admin/appointment_view', [
            'appointment'   => $appointment,
            'lab_services'  => $lab_services,
            'xray_services' => $xray_services,
            'viewMode'      => 'patient',
        ]);
    }

    private function resolvePatientId(?int $id, string $fullName, $age = null): ?int
    {
        if (!empty($id)) {
            return (int) $id;
        }

        $fullName = trim($fullName);
        if ($fullName === '') {
            return null;
        }

        try {
            $patientModel = new PatientModel();
            $query = $patientModel->where('full_name', $fullName);
            if (!empty($age)) {
                $query->where('age', $age);
            }
            $row = $query->first();

            return isset($row['id']) ? (int) $row['id'] : null;
        } catch (\Exception $e) {
            log_message('error', 'Admin resolvePatientId error: ' . $e->getMessage());
            return null;
        }
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

        $data['viewMode'] = 'appointment';

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

        $patientModel = new PatientModel();
        $patient = $patientModel->findOrCreateFromAppointment($appointment);

        $patientCodeForRequest = $patient['patient_code'] ?? null;

        $diagnosticModel = new DiagnosticRequestModel();

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
                    'patient_code'     => $patientCodeForRequest,
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
                    'patient_code'     => $patientCodeForRequest,
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
        $patientModel     = new PatientModel();

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

                $patient = $patientModel->where('full_name', $appt['full_name'])->first();

                $diagnosticModel->insert([
                    'reference_number' => 'XR-' . date('y') . '-' . strtoupper(bin2hex(random_bytes(3))),
                    'type'             => DiagnosticRequestModel::TYPE_XRAY,
                    'appointment_id'   => $appt['id'],
                    'patient_name'     => $appt['full_name'],
                    'patient_code'     => $patient['patient_code'] ?? null,
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
        $patientModel     = new PatientModel();

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

                $patient = $patientModel->where('full_name', $appt['full_name'])->first();

                $diagnosticModel->insert([
                    'reference_number' => 'LAB-' . date('y') . '-' . strtoupper(bin2hex(random_bytes(3))),
                    'type'             => DiagnosticRequestModel::TYPE_LAB,
                    'appointment_id'   => $appt['id'],
                    'patient_name'     => $appt['full_name'],
                    'patient_code'     => $patient['patient_code'] ?? null,
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
    // =============================================

    public function diagnosticRequests()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $diagnosticModel = new DiagnosticRequestModel();
        $serviceModel    = new ServiceModel();

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

    $patientModel = new PatientModel();
    $patientCode  = null;

    try {
        $existingPatient = null;

        if (!empty($email)) {
            $existingPatient = $patientModel->where('email', $email)->first();
        }

        if (!$existingPatient) {
            $existingPatient = $patientModel->where('full_name', $patientName)
                                            ->where('age', $age)
                                            ->where('gender', $gender)
                                            ->first();
        }

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
    }

    if ($patientCode === null || $patientCode === '' || $patientCode === 'N/A') {
        return redirect()->back()
                         ->withInput()
                         ->with('error', 'Could not assign a patient code. '
                             . 'Please try again, or create the patient from the Patients page first.');
    }

    try {
        $model = new DiagnosticRequestModel();

        $prefix = $requestType === 'xray' ? 'XR' : 'LAB';

        $model->insert([
            'reference_number' => $prefix . '-' . date('y') . '-' . strtoupper(bin2hex(random_bytes(3))),
            'type'             => $requestType,
            'appointment_id'   => null,
            'patient_name'     => $patientName,
            'patient_code'     => $patientCode,
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
            $model        = new DiagnosticRequestModel();
            $paymentModel = new PaymentModel();
            $serviceModel = new ServiceModel();

            $row = $model
                ->where('id', (int) $id)
                ->where('type', $type)
                ->first();

            if (!$row) {
                return $this->response->setJSON(['success' => false, 'message' => 'Request not found.']);
            }

            if ($status === 'in_progress') {

                $existingPayment = $paymentModel->getByRequest($id, $type);

                if ($existingPayment) {
                    $model->update((int) $id, ['status' => $status]);

                    return $this->response->setJSON([
                        'success'          => true,
                        'message'          => 'Status updated. Payment already recorded.',
                        'payment_recorded' => true,
                        'payment_id'       => $existingPayment['id'],
                        'amount'           => (float) $existingPayment['total_amount'],
                    ]);
                }

                $catalogue = $serviceModel
                    ->where('is_active', 1)
                    ->findAll();

                $priceByLowerName = [];
                foreach ($catalogue as $svc) {
                    $key = strtolower(trim((string) ($svc['service_name'] ?? '')));
                    if ($key !== '') {
                        $priceByLowerName[$key] = [
                            'name'   => (string) $svc['service_name'],
                            'charge' => (float) ($svc['charge'] ?? 0),
                        ];
                    }
                }

                $servicesString = (string) ($row['services'] ?? '');

                $totalAmount  = 0.0;
                $servicesList = [];

                if ($servicesString !== '') {
                    $serviceNames = array_filter(
                        array_map('trim', explode(',', $servicesString)),
                        'strlen'
                    );

                    foreach ($serviceNames as $serviceName) {
                        $key = strtolower($serviceName);

                        if (isset($priceByLowerName[$key])) {
                            $charge = $priceByLowerName[$key]['charge'];
                            $totalAmount += $charge;

                            $servicesList[] = [
                                'name'   => $priceByLowerName[$key]['name'],
                                'charge' => $charge,
                            ];
                        } else {
                            log_message(
                                'warning',
                                'Diagnostic payment: service "' . $serviceName
                                . '" on request #' . $id . ' (' . $type
                                . ') has no active price in the services table.'
                            );
                            $servicesList[] = [
                                'name'   => $serviceName,
                                'charge' => 0.0,
                            ];
                        }
                    }
                }

                if (empty($servicesList)) {
                    $totalAmount   = 500.00;
                    $servicesList  = [[
                        'name'   => 'Consultation Fee',
                        'charge' => 500.00,
                    ]];
                }

                $patientCode = 'N/A';

                if (!empty($row['patient_code'])) {
                    $patientCode = (string) $row['patient_code'];
                } else {
                    $patientModel = new PatientModel();

                    $patient = $patientModel
                        ->where('full_name', $row['patient_name'])
                        ->where('age', $row['age'])
                        ->where('gender', $row['gender'])
                        ->first();

                    if (!$patient && !empty($row['email'])) {
                        $patient = $patientModel->where('email', $row['email'])->first();
                    }

                    if (!$patient) {
                        $patient = $patientModel
                            ->where('full_name', $row['patient_name'])
                            ->first();
                    }

                    if ($patient) {
                        $patientCode = $patient['patient_code'] ?? 'N/A';
                    }
                }

                $paymentData = [
                    'request_id'     => (int) $id,
                    'request_type'   => $type,
                    'patient_name'   => (string) $row['patient_name'],
                    'patient_code'   => $patientCode,
                    'appointment_id' => $row['appointment_id'] ?? null,
                    'services'       => json_encode($servicesList),
                    'total_amount'   => $totalAmount,
                    'amount_paid'    => $totalAmount,
                    'payment_method' => 'cash',
                    'notes'          => 'Payment recorded when starting processing',
                ];

                $paymentId = $paymentModel->recordPayment($paymentData);

                if (!$paymentId) {
                    log_message(
                        'error',
                        'Failed to record payment for diagnostic request #'
                        . $id . ' (type: ' . $type . '). Status left at '
                        . ($row['status'] ?? 'pending') . '.'
                    );

                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Could not record the payment. The status was not changed. Please try again or record the payment manually.',
                    ]);
                }

                $model->update((int) $id, ['status' => $status]);

                log_message(
                    'info',
                    'Payment recorded for request #' . $id
                    . ' (type: ' . $type
                    . ', amount: ' . number_format($totalAmount, 2) . ')'
                );

                return $this->response->setJSON([
                    'success'          => true,
                    'message'          => 'Status updated to in progress. Payment recorded: ₱'
                                          . number_format($totalAmount, 2),
                    'payment_recorded' => true,
                    'payment_id'       => $paymentId,
                    'amount'           => $totalAmount,
                    'patient_code'     => $patientCode,
                ]);
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

        $row['type'] = $type;

        return view('Receptionist/print_request', ['request' => $row, 'type' => $type]);
    }

    // =============================================
    // PAYMENTS — admin page
    // =============================================

    public function payments()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $paymentModel = new PaymentModel();
        $patientModel = new PatientModel();

        $payments = $paymentModel->orderBy('payment_date', 'DESC')->findAll();

        $today = date('Y-m-d');

        // Sum amount_paid — the money actually collected — not total_amount.
        $todayPayments = $paymentModel
            ->where('DATE(payment_date)', $today)
            ->where('payment_status', 'paid')
            ->findAll();

        $todayTotal = 0;
        foreach ($todayPayments as $p) {
            $todayTotal += (float) ($p['amount_paid'] ?? 0);
        }

        $monthlyPayments = $paymentModel
            ->where('MONTH(payment_date)', date('m'))
            ->where('YEAR(payment_date)', date('Y'))
            ->where('payment_status', 'paid')
            ->findAll();

        $monthlyTotal = 0;
        foreach ($monthlyPayments as $p) {
            $monthlyTotal += (float) ($p['amount_paid'] ?? 0);
        }

        $uniquePatients = [];
        foreach ($payments as $p) {
            if (!in_array($p['patient_name'], $uniquePatients)) {
                $uniquePatients[] = $p['patient_name'];
            }
        }

        $codes = [];
        foreach ($payments as $p) {
            $code = trim((string) ($p['patient_code'] ?? ''));
            if ($code !== '' && strtoupper($code) !== 'N/A') {
                $codes[$code] = true;
            }
        }

        $genderByCode = [];
        if (!empty($codes)) {
            $rows = $patientModel
                ->select('patient_code, gender')
                ->whereIn('patient_code', array_keys($codes))
                ->findAll();

            foreach ($rows as $row) {
                $code = trim((string) ($row['patient_code'] ?? ''));
                if ($code !== '') {
                    $genderByCode[$code] = (string) ($row['gender'] ?? '');
                }
            }
        }

        foreach ($payments as &$p) {
            $code = trim((string) ($p['patient_code'] ?? ''));
            $p['gender'] = $genderByCode[$code] ?? null;
        }
        unset($p);

        $data = [
            'payments'       => $payments,
            'total'          => count($payments),
            'today_total'    => $todayTotal,
            'today_count'    => count($todayPayments),
            'monthly_total'  => $monthlyTotal,
            'total_patients' => count($uniquePatients),
        ];

        return view('Admin/payments', $data);
    }

    public function getPaymentDetails($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        try {
            $paymentModel = new PaymentModel();
            $payment = $paymentModel->find($id);

            if (!$payment) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Payment not found',
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'data'    => $payment,
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Admin get payment details error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error fetching payment details',
            ]);
        }
    }

    public function printReceipt($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        try {
            $paymentModel = new PaymentModel();
            $payment = $paymentModel->find($id);

            if (!$payment) {
                return redirect()->back()->with('error', 'Payment not found');
            }

            $services = json_decode($payment['services'] ?? '[]', true);

            return view('Receptionist/print_receipt', [
                'payment'  => $payment,
                'services' => $services,
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Admin print receipt error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error generating receipt');
        }
    }

    public function refundPayment($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        try {
            $paymentModel = new PaymentModel();
            $payment = $paymentModel->find($id);

            if (!$payment) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Payment not found',
                ]);
            }

            if ($payment['payment_status'] !== 'paid') {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Only paid payments can be refunded',
                ]);
            }

            $paymentModel->update($id, [
                'payment_status' => 'refunded',
                'notes'          => ($payment['notes'] ?? '') . ' | Refunded on ' . date('Y-m-d H:i:s'),
            ]);

            log_message('info', 'Admin refunded payment #' . $id);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Payment refunded successfully',
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Admin refund payment error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error refunding payment',
            ]);
        }
    }

    // =============================================
    // AVAILABILITY
    // =============================================

    public function availability()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $settingsModel = new SettingsModel();
        $availability  = new AvailabilityModel();

        $data = [
            'settings' => [
                'clinic_open_time'  => $settingsModel->openTime(),
                'clinic_close_time' => $settingsModel->closeTime(),
                'slot_interval'     => $settingsModel->slotInterval(),
                'lunch_start'       => $settingsModel->lunchStart(),
                'lunch_end'         => $settingsModel->lunchEnd(),
                'closed_weekdays'   => $settingsModel->closedWeekdays(),
                'holidays'          => $settingsModel->holidays(),
                'booking_horizon'   => $settingsModel->bookingHorizon(),
            ],
            'preview'        => $availability->previewDays(14),
            'slot_intervals' => [15, 20, 30, 45, 60, 90, 120],
            'weekday_labels' => [
                0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
                4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday',
            ],
        ];

        return view('Admin/availability', $data);
    }

    public function saveAvailabilitySettings()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $settingsModel = new SettingsModel();

        $open    = (string) $this->request->getPost('clinic_open_time');
        $close   = (string) $this->request->getPost('clinic_close_time');
        $lunchS  = (string) $this->request->getPost('lunch_start');
        $lunchE  = (string) $this->request->getPost('lunch_end');
        $step    = (int)    $this->request->getPost('slot_interval');
        $horizon = (int)    $this->request->getPost('booking_horizon');

        $allowedSteps = [15, 20, 30, 45, 60, 90, 120];
        if (!in_array($step, $allowedSteps, true)) $step = 60;
        if ($horizon < 1)   $horizon = 90;
        if ($horizon > 365) $horizon = 365;

        $timePattern = '/^\d{2}:\d{2}$/';

        if (!preg_match($timePattern, $open)   || !preg_match($timePattern, $close)
            || !preg_match($timePattern, $lunchS) || !preg_match($timePattern, $lunchE)) {
            return redirect()->back()->with('error', 'Times must be HH:MM.');
        }

        if ($close <= $open) {
            return redirect()->back()->with('error', 'Closing time must be after opening time.');
        }

        $closedRaw = $this->request->getPost('closed_weekdays') ?? [];
        $closed    = [];
        if (is_array($closedRaw)) {
            foreach ($closedRaw as $d) {
                $d = (int) $d;
                if ($d >= 0 && $d <= 6) $closed[] = $d;
            }
        }

        $holidayRaw = (string) $this->request->getPost('holidays');
        $holidays   = [];
        foreach (preg_split('/\r\n|\r|\n/', $holidayRaw) as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $line)) $holidays[] = $line;
        }

        $settingsModel->saveMany([
            'clinic_open_time'  => $open,
            'clinic_close_time' => $close,
            'lunch_start'       => $lunchS,
            'lunch_end'         => $lunchE,
            'slot_interval'     => $step,
            'booking_horizon'   => $horizon,
            'closed_weekdays'   => json_encode(array_values(array_unique($closed))),
            'holidays'          => json_encode(array_values(array_unique($holidays))),
        ]);

        return redirect()->to(base_url('admin/availability'))
            ->with('success', 'Availability settings saved.');
    }

    public function blockAvailabilitySlot()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $date   = (string) $this->request->getPost('slot_date');
        $time   = (string) $this->request->getPost('slot_time');
        $dept   = (string) $this->request->getPost('department');
        $reason = (string) $this->request->getPost('block_reason');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            || !preg_match('/^\d{2}:\d{2}$/', $time)
            || !in_array($dept, ['lab', 'xray'], true)) {
            return redirect()->back()->with('error', 'Invalid slot to block.');
        }

        (new AvailabilityModel())->blockSlot($date, $time, $dept, $reason ?: null);

        return redirect()->to(base_url('admin/availability'))
            ->with('success', 'Slot blocked.');
    }

    public function unblockAvailabilitySlot()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $date = (string) $this->request->getPost('slot_date');
        $time = (string) $this->request->getPost('slot_time');
        $dept = (string) $this->request->getPost('department');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            || !preg_match('/^\d{2}:\d{2}$/', $time)
            || !in_array($dept, ['lab', 'xray'], true)) {
            return redirect()->back()->with('error', 'Invalid slot to unblock.');
        }

        (new AvailabilityModel())->unblockSlot($date, $time, $dept);

        return redirect()->to(base_url('admin/availability'))
            ->with('success', 'Slot unblocked.');
    }

    public function availabilityDay()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $date = (string) $this->request->getGet('date');
        $dept = (string) ($this->request->getGet('department') ?? 'lab');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            || !in_array($dept, ['lab', 'xray'], true)) {
            return $this->response->setStatusCode(400)
                ->setJSON(['ok' => false, 'error' => 'Invalid request.']);
        }

        $availability = new AvailabilityModel();
        $slots        = $availability->isWorkingDay($date)
            ? $availability->slotsFor($date, $dept)
            : [];

        return $this->response->setJSON([
            'ok'      => true,
            'date'    => $date,
            'dept'    => $dept,
            'working' => $availability->isWorkingDay($date),
            'slots'   => $slots,
        ]);
    }
}