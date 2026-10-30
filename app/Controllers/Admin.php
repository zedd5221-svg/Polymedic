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
use App\Controllers\Concerns\HandlesDiagnosticRequests;

class Admin extends BaseController
{
    use HandlesDiagnosticRequests;

    private const ROLES = ['admin', 'receptionist', 'radiologist', 'med_tech'];

    // The 'auth:admin' route filter is the real guard; this is a
    // second check in case a route is ever registered without it.
    private function checkAuth()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('login'));
        }

        if (session()->get('role') !== 'admin') {
            return redirect()->to((new NotificationModel())->dashboardFor(session()->get('role')));
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

        $totalPatients = $patientModel->countAll();

        $todayPatients = $appointmentModel
            ->where('appointment_date', $today)
            ->countAllResults();

        $requestCounts     = $diagnosticModel->getCounts();
        $pendingRequests   = $requestCounts['pending'];
        $completedRequests = $requestCounts['completed'];
        $releasedResults   = $requestCounts['released'];

        // amount_paid (money actually collected), not total_amount.
        $todayRevenue   = $this->collectedBetween($today, $today);
        $monthlyRevenue = $this->collectedBetween(date('Y-m-01'), $today);

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

    /**
     * Build a 14-day daily series for each Payments KPI card.
     */
    private function getPaymentsKpiTrends(int $days = 14): array
    {
        $db    = db_connect();
        $end   = date('Y-m-d');
        $start = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));

        $dates = [];
        for ($i = 0; $i < $days; $i++) {
            $dates[] = date('Y-m-d', strtotime($start . ' +' . $i . ' days'));
        }

        $series = function (string $sql, array $bind) use ($db, $dates): array {
            try {
                $rows = $db->query($sql, $bind)->getResultArray();
                $map  = [];
                foreach ($rows as $r) {
                    $map[$r['d']] = (float) ($r['v'] ?? 0);
                }
                return array_map(fn($d) => $map[$d] ?? 0.0, $dates);
            } catch (\Throwable $e) {
                log_message('warning', 'Admin::getPaymentsKpiTrends query failed: ' . $e->getMessage());
                return array_fill(0, count($dates), 0.0);
            }
        };

        $today = $series("
            SELECT DATE(payment_date) AS d, SUM(amount_paid) AS v
            FROM payments
            WHERE payment_status = 'paid'
              AND DATE(payment_date) BETWEEN ? AND ?
            GROUP BY d
        ", [$start, $end]);

        $count = $series("
            SELECT DATE(payment_date) AS d, COUNT(*) AS v
            FROM payments
            WHERE payment_status = 'paid'
              AND DATE(payment_date) BETWEEN ? AND ?
            GROUP BY d
        ", [$start, $end]);

        $patients = $series("
            SELECT DATE(payment_date) AS d, COUNT(DISTINCT patient_code) AS v
            FROM payments
            WHERE payment_status = 'paid'
              AND patient_code IS NOT NULL
              AND patient_code <> ''
              AND patient_code <> 'N/A'
              AND DATE(payment_date) BETWEEN ? AND ?
            GROUP BY d
        ", [$start, $end]);

        $monthStart = date('Y-m-01');
        $monthDaily = $series("
            SELECT DATE(payment_date) AS d, SUM(amount_paid) AS v
            FROM payments
            WHERE payment_status = 'paid'
              AND DATE(payment_date) BETWEEN ? AND ?
            GROUP BY d
        ", [$monthStart, $end]);

        $monthMap = [];
        foreach ($dates as $i => $d) {
            $monthMap[$d] = $monthDaily[$i] ?? 0.0;
        }
        $running = 0.0;
        $cursor  = $monthStart;
        $sumLookup = [];
        while ($cursor <= $end) {
            $running += $monthMap[$cursor] ?? 0.0;
            $sumLookup[$cursor] = $running;
            $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
        }
        $monthly = [];
        foreach ($dates as $d) {
            $monthly[] = $sumLookup[$d] ?? 0.0;
        }

        return [
            'today'    => $today,
            'count'    => $count,
            'patients' => $patients,
            'monthly'  => $monthly,
        ];
    }

    /**
     * Build a 14-day daily series for each Patients KPI card.
     *
     * Returned shape:
     *   [
     *     'total'  => [float, ... 14 values],   // new registrations per day
     *     'online' => [float, ... 14 values],   // online signups per day
     *     'walkin' => [float, ... 14 values],   // walk-in signups per day
     *   ]
     */
    private function getPatientsKpiTrends(int $days = 14): array
    {
        $db    = db_connect();
        $end   = date('Y-m-d');
        $start = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));

        $dates = [];
        for ($i = 0; $i < $days; $i++) {
            $dates[] = date('Y-m-d', strtotime($start . ' +' . $i . ' days'));
        }

        $series = function (string $sql, array $bind) use ($db, $dates): array {
            try {
                $rows = $db->query($sql, $bind)->getResultArray();
                $map  = [];
                foreach ($rows as $r) {
                    $map[$r['d']] = (float) ($r['v'] ?? 0);
                }
                return array_map(fn($d) => $map[$d] ?? 0.0, $dates);
            } catch (\Throwable $e) {
                log_message('warning', 'Admin::getPatientsKpiTrends query failed: ' . $e->getMessage());
                return array_fill(0, count($dates), 0.0);
            }
        };

        // New patient registrations per day — any source.
        $total = $series("
            SELECT DATE(created_at) AS d, COUNT(*) AS v
            FROM patients
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY d
        ", [$start, $end]);

        // Online signups. Matches the view's strpos check by accepting
        // several common spellings of the source value.
        $online = $series("
            SELECT DATE(created_at) AS d, COUNT(*) AS v
            FROM patients
            WHERE LOWER(source) LIKE 'online%'
              AND DATE(created_at) BETWEEN ? AND ?
            GROUP BY d
        ", [$start, $end]);

        // Walk-in signups. Same idea — accept 'walk-in', 'walkin', etc.
        $walkin = $series("
            SELECT DATE(created_at) AS d, COUNT(*) AS v
            FROM patients
            WHERE (
                LOWER(source) LIKE 'walk%-in%'
                OR LOWER(source) LIKE 'walkin%'
            )
              AND DATE(created_at) BETWEEN ? AND ?
            GROUP BY d
        ", [$start, $end]);

        return [
            'total'  => $total,
            'online' => $online,
            'walkin' => $walkin,
        ];
    }

    /**
     * Collected revenue per month of the current year. Months with no
     * payments are real zeros; the chart must never show made-up data.
     */
    private function getRevenueData()
    {
        $months       = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $currentMonth = (int) date('n');

        $rows = db_connect()->table('payments')
            ->select('MONTH(payment_date) AS m, SUM(amount_paid) AS total', false)
            ->where('payment_status', PaymentModel::STATUS_PAID)
            ->where('payment_date >=', date('Y-01-01 00:00:00'))
            ->where('payment_date <=', date('Y-12-31 23:59:59'))
            ->groupBy('m')
            ->get()->getResultArray();

        $byMonth = array_column($rows, 'total', 'm');
        $revenue = [];
        for ($i = 1; $i <= $currentMonth; $i++) {
            $revenue[] = (float) ($byMonth[$i] ?? 0);
        }

        return [
            'labels' => array_slice($months, 0, $currentMonth),
            'values' => $revenue,
        ];
    }

    /** Count rows per day of the current week (Mon..Sun) in one query. */
    private function weekSeries(string $table, string $dateColumn, array $where = []): array
    {
        $start = date('Y-m-d', strtotime('monday this week'));
        $end   = date('Y-m-d', strtotime($start . ' +6 days'));

        $builder = db_connect()->table($table)
            ->select("DATE({$dateColumn}) AS d, COUNT(*) AS n", false)
            ->where("{$dateColumn} >=", $start)
            ->where("{$dateColumn} <=", $end . ' 23:59:59');
        foreach ($where as $col => $val) {
            $builder->where($col, $val);
        }
        $counts = array_column($builder->groupBy('d')->get()->getResultArray(), 'n', 'd');

        $out = [];
        for ($i = 0; $i < 7; $i++) {
            $out[] = (int) ($counts[date('Y-m-d', strtotime($start . " +{$i} days"))] ?? 0);
        }
        return $out;
    }

    private function getDepartmentData()
    {
        return [
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'lab'    => $this->weekSeries('diagnostic_requests', 'request_date', ['type' => DiagnosticRequestModel::TYPE_LAB]),
            'xray'   => $this->weekSeries('diagnostic_requests', 'request_date', ['type' => DiagnosticRequestModel::TYPE_XRAY]),
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
        return [
            'labels'    => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'requested' => $this->weekSeries('appointments', 'appointment_date'),
            'completed' => $this->weekSeries('appointments', 'appointment_date', ['status' => 'completed']),
        ];
    }

    private function getTopTests()
    {
        $labRequests = db_connect()->table('diagnostic_requests')
            ->select('services')
            ->where('type', DiagnosticRequestModel::TYPE_LAB)
            ->get()->getResultArray();

        $testCounts = [];

        foreach ($labRequests as $req) {
            $services = $req['services'] ?? '';

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

        $patients = $this->collectPatients();

        return view('Admin/patients', [
            'patients'  => $patients,
            'total'     => count($patients),
            // 14-day series feeding the KPI sparklines in Admin/patients.
            'kpiTrends' => $this->getPatientsKpiTrends(14),
        ]);
    }

    public function viewPatient($id = null)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $id = (int) $id;
        $patient = $id > 0 ? (new PatientModel())->find($id) : null;

        if (! $patient) {
            return redirect()->to(base_url('admin/patients'))
                             ->with('error', 'Patient not found.');
        }

        return view('Admin/appointment_view', $this->patientViewData($patient));
    }

    public function approvePatient($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $patientModel = new PatientModel();
        $patient = $patientModel->find((int) $id);

        if (!$patient) {
            return $this->response->setStatusCode(404)->setJSON([
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

        $patientModel->update((int) $id, [
            'patient_code' => $newCode,
            'source'       => $patient['source'] ?? 'walk-in',
        ]);

        return $this->response->setJSON([
            'success'      => true,
            'message'      => 'Patient approved successfully',
            'patient_code' => $newCode,
            'patient'      => $patientModel->find((int) $id),
        ]);
    }

    // The old "requests" page was merged into diagnostic-requests.
    public function requests()
    {
        return redirect()->to(base_url('admin/diagnostic-requests'));
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

        $username  = trim((string) $this->request->getPost('username'));
        $password  = (string) $this->request->getPost('password');
        $email     = trim((string) $this->request->getPost('email'));
        $full_name = trim((string) $this->request->getPost('full_name'));
        $role      = (string) $this->request->getPost('role');
        $status    = $this->request->getPost('status') === 'inactive' ? 'inactive' : 'active';

        if ($username === '' || $password === '' || $email === '' || $full_name === '' || $role === '') {
            return redirect()->to(base_url('admin/users'))
                            ->with('error', 'All fields are required');
        }

        if ($error = $this->userFieldError($role, $email)) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        if (strlen($password) < 8) {
            return redirect()->back()->withInput()->with('error', 'Password must be at least 8 characters.');
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

        $email     = trim((string) $this->request->getPost('email'));
        $full_name = trim((string) $this->request->getPost('full_name'));
        $role      = (string) $this->request->getPost('role');
        $status    = $this->request->getPost('status') === 'inactive' ? 'inactive' : 'active';
        $password  = (string) $this->request->getPost('password');

        if ($full_name === '') {
            return redirect()->back()->withInput()->with('error', 'Full name is required.');
        }
        if ($error = $this->userFieldError($role, $email)) {
            return redirect()->back()->withInput()->with('error', $error);
        }
        if ($password !== '' && strlen($password) < 8) {
            return redirect()->back()->withInput()->with('error', 'Password must be at least 8 characters.');
        }

        // An admin must not be able to lock themselves out.
        if ((int) $user['id'] === (int) session()->get('user_id')) {
            $role   = 'admin';
            $status = 'active';
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

        $updateData = [
            'email'       => $email,
            'full_name'   => $full_name,
            'role'        => $role,
            'prc_license' => $prcValue,
            'status'      => $status
        ];

        if ($password !== '') {
            $updateData['password'] = $password;
        }

        if ($userModel->updateUser($id, $updateData)) {
            if ((int) $user['id'] === (int) session()->get('user_id')) {
                session()->set(['full_name' => $full_name, 'email' => $email]);
            }
            return redirect()->to(base_url('admin/users'))
                            ->with('success', 'User updated successfully');
        }

        return redirect()->to(base_url('admin/users'))
                        ->with('error', 'Failed to update user');
    }

    private function userFieldError(string $role, string $email): ?string
    {
        if (!in_array($role, self::ROLES, true)) {
            return 'Please choose a valid role.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Please enter a valid email address.';
        }
        return null;
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

        return $this->response->setStatusCode(404)->setJSON(['error' => 'User not found']);
    }

    // ===== APPOINTMENT MANAGEMENT =====

    public function appointments()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        try {
            $model = new AppointmentModel();

            $model->checkLateAppointments();

            $data = $this->appointmentStatusCounts();
            $data['appointments'] = $model->orderBy('appointment_date', 'DESC')
                                          ->orderBy('appointment_time', 'ASC')
                                          ->findAll();

            return view('Admin/appointments', $data);
        } catch (\Throwable $e) {
            log_message('error', 'Appointments error: ' . $e->getMessage());
            return redirect()->to(base_url('admin/dashboard'))
                             ->with('error', 'Could not load appointments. Please try again.');
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

        $patient = (new PatientModel())->findOrCreateFromAppointment($appointment);
        $notes   = $this->createRequestsFromAppointment($appointment, $patient['patient_code'] ?? null);

        $message = trim('Appointment approved successfully! ' . implode(' ', $notes));
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

    /**
     * Back-fill missing lab / x-ray requests for approved appointments
     * (e.g. ones approved before request creation existed).
     */
    public function syncXrayExaminations()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $patientModel = new PatientModel();
        $created      = 0;

        $appointments = (new AppointmentModel())->where('status', 'approved')->findAll();

        foreach ($appointments as $appt) {
            $patient = $patientModel->where('full_name', $appt['full_name'])->first();
            foreach ($this->createRequestsFromAppointment($appt, $patient['patient_code'] ?? null) as $note) {
                if (str_ends_with($note, 'created.')) {
                    $created++;
                }
            }
        }

        return redirect()->to(base_url('admin/appointments'))
                        ->with('success', "Synced {$created} missing diagnostic request(s).");
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
        $locks    = $this->startLocks($rows);
        foreach ($rows as $row) {
            $requests[] = $this->buildDiagnosticRow($row, $row['type'] ?? 'lab')
                + ['start_lock' => $locks[(int) $row['id']] ?? null];
        }

        $counts = $this->monitoringCounts($requests);

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
            // Suggestions for the "Referring physician" field.
            'referringStaff' => (new UserModel())->getReferringStaff(),
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
            'monitor'        => DiagnosticRequestModel::monitorStage($row),
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
    // 'lab', 'xray' or 'both' (one request per department).
    $types = $this->walkInTypes($post['request_type'] ?? null);
    if ($types === []) {
        $errors[] = 'Select Laboratory, X-Ray or Lab + X-Ray as the request type.';
    }
    $servicesByType = $this->walkInServices($post['services'] ?? [], $types);
    $errors = array_merge($errors, $this->walkInServiceErrors($servicesByType));

    if (!empty($errors)) {
        return redirect()->back()
                         ->withInput()
                         ->with('validation_errors', $errors);
    }

    $priority    = ($post['priority'] ?? 'routine') === 'stat' ? 'stat' : 'routine';
    $patientName = trim((string) $post['patient_name']);
    $age         = (int) $post['age'];
    $gender      = (string) $post['gender'];
    $doctors     = $this->walkInDoctors($post['doctor_name'] ?? '', $post['doctor_by_type'] ?? [], $types);
    $phone       = trim((string) ($post['phone'] ?? ''));
    $email       = trim((string) ($post['email'] ?? ''));

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
        $created = $this->insertWalkInRequests($servicesByType, [
            'name'   => $patientName,
            'code'   => $patientCode,
            'age'    => $age,
            'gender' => $gender,
            'email'  => $email,
            'phone'  => $phone,
        ], $doctors, $priority);
    } catch (\Throwable $e) {
        log_message('error', 'Create diagnostic request error: ' . $e->getMessage());
        return redirect()->back()->withInput()->with('error', 'Could not create the request.');
    }

    foreach ($created as $type => $row) {
        NotificationModel::dispatch(
            $type,
            $type === 'lab' ? 'New Laboratory Request' : 'New X-Ray Request',
            'New ' . ($type === 'lab' ? 'laboratory' : 'x-ray') . ' request for ' . $patientName,
            $row['id'],
            null,
            true,
            $gender
        );
    }

    return redirect()->to(base_url('admin/diagnostic-requests'))
                     ->with('success', count($created) > 1
                         ? 'Lab and X-Ray requests created (' . implode(', ', array_column($created, 'reference')) . ').'
                         : 'Diagnostic request created.');
    }

    public function updateDiagnosticStatus($id, $type, $status)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        try {
            $assignee = (int) $this->request->getPost('assigned_to');
            return $this->response->setJSON($this->changeDiagnosticStatus((int) $id, (string) $type, (string) $status, $assignee ?: null));
        } catch (\Throwable $e) {
            log_message('error', 'Update diagnostic status error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Could not update the status.']);
        }
    }

    /** Staff (with workload) the Start modal can assign a request to. */
    public function assignmentOptions($id, $type)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        try {
            return $this->response->setJSON($this->assignmentOptionsFor((int) $id, (string) $type));
        } catch (\Throwable $e) {
            log_message('error', 'Assignment options error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Could not load the available staff. Please try again.']);
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
                'monitor'        => DiagnosticRequestModel::monitorStage($row),
            ];

            return $this->response->setJSON(['success' => true, 'data' => $data]);

        } catch (\Exception $e) {
            log_message('error', 'Get request details error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Could not load the request.']);
        }
    }

    // =============================================
    // PRINT (department-aware — X-Ray vs Laboratory)
    // =============================================
    public function printRequest($id, $type)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $type = strtolower((string) $type);
        if (!in_array($type, ['lab', 'xray'], true)) {
            return redirect()->to(base_url('admin/diagnostic-requests'))
                             ->with('error', 'Unknown request type.');
        }

        $row = (new DiagnosticRequestModel())
            ->where('id', (int) $id)
            ->where('type', $type)
            ->first();

        if (!$row) {
            return redirect()->to(base_url('admin/diagnostic-requests'))
                             ->with('error', 'Request not found.');
        }

        return $this->renderPrintable($row, $type);
    }

    // =============================================
    // PAYMENTS — admin page
    // =============================================

    public function payments()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $today    = date('Y-m-d');
        $payments = (new PaymentModel())->orderBy('payment_date', 'DESC')->findAll();

        $todayCount = (new PaymentModel())
            ->where('payment_status', PaymentModel::STATUS_PAID)
            ->where('payment_date >=', $today . ' 00:00:00')
            ->where('payment_date <=', $today . ' 23:59:59')
            ->countAllResults();

        $this->attachPatientGender($payments);

        return view('Admin/payments', [
            'payments'       => $payments,
            'total'          => count($payments),
            // Sum amount_paid — the money actually collected — not total_amount.
            'today_total'    => $this->collectedBetween($today, $today),
            'today_count'    => $todayCount,
            'monthly_total'  => $this->collectedBetween(date('Y-m-01'), $today),
            'total_patients' => count(array_unique(array_column($payments, 'patient_name'))),
            // 14-day series feeding the KPI sparklines in Admin/payments.
            'kpiTrends'      => $this->getPaymentsKpiTrends(14),
        ]);
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

    // The printable receipt lives in the Billing module.
    public function printReceipt($id)
    {
        return redirect()->to(base_url('admin/billing/receipt/' . (int) $id));
    }

    public function refundPayment($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        try {
            $paymentModel = new PaymentModel();
            $payment = $paymentModel->find((int) $id);

            if (!$payment) {
                return $this->response->setJSON(['success' => false, 'message' => 'Payment not found']);
            }

            if ($payment['payment_status'] !== PaymentModel::STATUS_PAID) {
                return $this->response->setJSON(['success' => false, 'message' => 'Only paid payments can be refunded']);
            }

            // voidPayment() also recalculates the linked invoice, so the
            // invoice's balance and status follow the refund.
            if (!$paymentModel->voidPayment((int) $id, 'Refunded from Payments page', PaymentModel::currentCashierName())) {
                return $this->response->setJSON(['success' => false, 'message' => 'Error refunding payment']);
            }

            log_message('info', 'Admin refunded payment #' . $id);

            return $this->response->setJSON(['success' => true, 'message' => 'Payment refunded successfully']);

        } catch (\Throwable $e) {
            log_message('error', 'Admin refund payment error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error refunding payment']);
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