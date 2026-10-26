<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\DiagnosticRequestModel;
use App\Models\NotificationModel;
use App\Models\ServiceModel;
use App\Models\PatientModel;
use App\Models\PaymentModel;
use App\Models\AvailabilityModel;
use App\Models\InvoiceModel;
use App\Controllers\Concerns\HandlesDiagnosticRequests;

class Receptionist extends BaseController
{
    use HandlesDiagnosticRequests;

    // The 'auth:receptionist' route filter is the real guard; this is
    // a second check in case a route is ever registered without it.
    private function checkAuth()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('login'));
        }

        if (session()->get('role') !== 'receptionist') {
            return redirect()->to((new NotificationModel())->dashboardFor(session()->get('role')));
        }
        return null;
    }

    public function dashboard()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $appointmentModel = new AppointmentModel();
        $today            = date('Y-m-d');

        $data['today_appointments'] = $appointmentModel
            ->where('appointment_date', $today)
            ->orderBy('appointment_time', 'ASC')
            ->findAll();

        $data['pending_appointments'] = $appointmentModel
            ->where('status', 'pending')
            ->countAllResults();

        $data['today_completed'] = $appointmentModel
            ->where('appointment_date', $today)
            ->where('status', 'completed')
            ->countAllResults();

        // Requests waiting for the front desk to start (and charge) them.
        $data['pending_diagnostic'] = (new DiagnosticRequestModel())
            ->where('status', DiagnosticRequestModel::STATUS_PENDING)
            ->countAllResults();

        // Invoices that still carry a balance.
        $data['unpaid_bills'] = (new InvoiceModel())
            ->whereIn('status', [InvoiceModel::STATUS_UNPAID, InvoiceModel::STATUS_PARTIAL])
            ->countAllResults();

        // Sum amount_paid (money collected) instead of total_amount (invoice total).
        $data['today_collections'] = $this->collectedBetween($today, $today);

        $data['total_patients'] = count($this->collectPatients());

        $data['weekly_appointments'] = $this->getWeeklyAppointmentData();

        $serviceData = $this->getServiceDistributionData();
        $data['service_labels'] = $serviceData['labels'];
        $data['service_counts'] = $serviceData['counts'];

        $data['kpiTrends'] = $this->getKpiTrends(14, (int) $data['total_patients']);

        return view('Receptionist/dashboard', $data);
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

        $series = function (string $table, string $dateExpr, string $valueExpr, array $where = []) use ($db, $dates, $start, $end) {
            $b = $db->table($table)
                ->select("$dateExpr AS d, $valueExpr AS v", false)
                ->where("$dateExpr >=", $start)
                ->where("$dateExpr <=", $end);
            foreach ($where as $col => $val) {
                is_array($val) ? $b->whereIn($col, $val) : $b->where($col, $val);
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
            $running    += $n;
            $patients[]  = $running;
        }

        return [
            'dates'       => $dates,
            'patients'    => $patients,
            'today'       => $series('appointments', 'appointment_date', 'COUNT(*)'),
            'pending'     => $series('appointments', 'DATE(created_at)', 'COUNT(*)', ['status' => 'pending']),
            'completed'   => $series('appointments', 'DATE(updated_at)', 'COUNT(*)', ['status' => 'completed']),
            'diagnostics' => $series('diagnostic_requests', 'DATE(created_at)', 'COUNT(*)', ['status' => 'pending']),
            'unpaid'      => $series('invoices', 'DATE(created_at)', 'COUNT(*)', ['status' => ['unpaid', 'partial']]),
            'collections' => $this->getCollectionsSeries($dates),
        ];
    }

    private function getCollectionsSeries(array $dates): array
    {
        if (empty($dates)) { return []; }

        $db    = db_connect();
        $start = $dates[0];
        $end   = end($dates);

        $rows = $db->table('payments')
            ->select('DATE(payment_date) AS d, SUM(amount_paid) AS v', false)
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

        return array_map(fn($d) => $map[$d] ?? 0, $dates);
    }

    /* ============================================================
       PATIENTS KPI TRENDS
       ============================================================
       14-day daily series feeding the KPI sparklines on the
       receptionist Patients page.
       Shape: ['total' => [...], 'online' => [...], 'walkin' => [...]]
       ============================================================ */
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
                log_message('warning', 'Receptionist::getPatientsKpiTrends query failed: ' . $e->getMessage());
                return array_fill(0, count($dates), 0.0);
            }
        };

        // New patient registrations per day (any source).
        $total = $series("
            SELECT DATE(created_at) AS d, COUNT(*) AS v
            FROM patients
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY d
        ", [$start, $end]);

        // Online signups.
        $online = $series("
            SELECT DATE(created_at) AS d, COUNT(*) AS v
            FROM patients
            WHERE LOWER(source) LIKE 'online%'
              AND DATE(created_at) BETWEEN ? AND ?
            GROUP BY d
        ", [$start, $end]);

        // Walk-in signups (accepts 'walk-in', 'walkin', and similar spellings).
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

    /* ============================================================
       PAYMENTS KPI TRENDS
       ============================================================
       14-day daily series feeding the KPI sparklines on the
       receptionist Payments page.
       Shape: ['today' => [...], 'count' => [...], 'patients' => [...], 'monthly' => [...]]
       ============================================================ */
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
                log_message('warning', 'Receptionist::getPaymentsKpiTrends query failed: ' . $e->getMessage());
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

            return view('Receptionist/appointments', $data);
        } catch (\Throwable $e) {
            log_message('error', 'Appointments error: ' . $e->getMessage());
            return redirect()->to(base_url('receptionist/dashboard'))
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
            return redirect()->to(base_url('receptionist/appointments'))
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

        return view('Receptionist/appointment_view', $data);
    }

    public function viewPatient($id = null)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $id = (int) $id;
        $patient = $id > 0 ? (new PatientModel())->find($id) : null;

        if (! $patient) {
            return redirect()->to(base_url('receptionist/patients'))
                             ->with('error', 'Patient not found.');
        }

        return view('Receptionist/appointment_view', $this->patientViewData($patient));
    }

    public function approveAppointment($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $model = new AppointmentModel();
        $appointment = $model->find($id);

        if (!$appointment) {
            return redirect()->to(base_url('receptionist/appointments'))
                            ->with('error', 'Appointment not found');
        }

        if (in_array($appointment['status'], ['completed', 'cancelled', 'no_show'])) {
            return redirect()->to(base_url('receptionist/appointments'))
                            ->with('error', 'This appointment cannot be approved');
        }

        $model->update($id, [
            'status'       => 'approved',
            'arrival_time' => date('Y-m-d H:i:s')
        ]);

        $patient = (new PatientModel())->findOrCreateFromAppointment($appointment);
        $notes   = $this->createRequestsFromAppointment($appointment, $patient['patient_code'] ?? null);

        $message = 'Appointment approved successfully!';
        if ($notes !== []) {
            $message .= ' ' . implode(' ', $notes);
        } else {
            $message .= ' (No diagnostic services selected)';
        }
        if ($patient) {
            $message .= ' Patient Code: ' . $patient['patient_code'];
        }

        return redirect()->to(base_url('receptionist/appointments'))
                        ->with('success', $message);
    }

    public function cancelAppointment($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $model = new AppointmentModel();
        $appointment = $model->find($id);

        if (!$appointment) {
            return redirect()->to(base_url('receptionist/appointments'))
                            ->with('error', 'Appointment not found');
        }

        if ($appointment['status'] == 'completed') {
            return redirect()->to(base_url('receptionist/appointments'))
                            ->with('error', 'Completed appointments cannot be cancelled');
        }

        $model->update($id, ['status' => 'cancelled']);

        return redirect()->to(base_url('receptionist/appointments'))
                        ->with('success', 'Appointment cancelled successfully!');
    }

    public function completeAppointment($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $model = new AppointmentModel();
        $appointment = $model->find($id);

        if (!$appointment) {
            return redirect()->to(base_url('receptionist/appointments'))
                            ->with('error', 'Appointment not found');
        }

        if ($appointment['status'] != 'approved') {
            return redirect()->to(base_url('receptionist/appointments'))
                            ->with('error', 'Only approved appointments can be marked as completed');
        }

        $model->update($id, ['status' => 'completed']);

        return redirect()->to(base_url('receptionist/appointments'))
                        ->with('success', 'Appointment marked as completed!');
    }

    public function patients()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $patients = $this->collectPatients();

        return view('Receptionist/patients', [
            'patients'  => $patients,
            'total'     => count($patients),
            // 14-day series feeding the KPI sparklines in Receptionist/patients.
            'kpiTrends' => $this->getPatientsKpiTrends(14),
        ]);
    }

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

        return view('Receptionist/payments', [
            'payments'       => $payments,
            'total'          => count($payments),
            // Sum amount_paid — money actually collected — not total_amount.
            'today_total'    => $this->collectedBetween($today, $today),
            'today_count'    => $todayCount,
            'monthly_total'  => $this->collectedBetween(date('Y-m-01'), $today),
            'total_patients' => count(array_unique(array_column($payments, 'patient_name'))),
            // 14-day series feeding the KPI sparklines in Receptionist/payments.
            'kpiTrends'      => $this->getPaymentsKpiTrends(14),
        ]);
    }

    public function reports()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        return view('Receptionist/reports');
    }

    public function diagnosticRequests()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $diagnosticModel = new DiagnosticRequestModel();
        $serviceModel    = new ServiceModel();

        $data['requests'] = $this->getCombinedRequests($diagnosticModel);

        $data['counts'] = [
            'total' => count($data['requests']),
            'pending' => $this->countRequestsByStatus($data['requests'], 'pending'),
            'processing' => $this->countRequestsByStatus($data['requests'], 'in_progress'),
            'completed' => $this->countRequestsByStatus($data['requests'], 'completed'),
            'released' => $this->countRequestsByStatus($data['requests'], 'released'),
            'cancelled' => $this->countRequestsByStatus($data['requests'], 'cancelled')
        ];

        $data['labServices'] = $serviceModel->getServicesByCategory('laboratory');
        $data['xrayServices'] = $serviceModel->getServicesByCategory('xray');

        return view('Receptionist/diagnostic_requests', $data);
    }

    private function getCombinedRequests($diagnosticModel)
    {
        $rows = $diagnosticModel
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $combined = [];

        foreach ($rows as $row) {
            $type = $row['type'] ?? 'lab';

            $rawServices = $type === 'xray'
                ? ($row['exam_type'] ?? $row['services'] ?? '')
                : ($row['lab_services'] ?? $row['services'] ?? '');

            // Use the priority chosen when the request was created.
            $priority = strtolower((string) ($row['priority'] ?? '')) === 'stat' ? 'stat' : 'routine';

            $services = explode(', ', (string) $rawServices);
            $services = array_values(array_filter(array_map('trim', $services), 'strlen'));

            $prefix = $type === 'xray' ? 'XRAY' : 'LAB';

            $combined[] = [
                'id' => $row['id'],
                'type' => $type,
                'reference' => !empty($row['reference_number'])
                    ? $row['reference_number']
                    : $prefix . '-' . date('y') . '-' . str_pad((string) $row['id'], 4, '0', STR_PAD_LEFT),
                'patient_name' => $row['patient_name'] ?? 'Unknown',
                'patient_age' => $row['age'] ?? 'N/A',
                'patient_gender' => $row['gender'] ?? 'N/A',
                'email' => $row['email'] ?? '',
                'phone' => $row['phone'] ?? '',
                'services' => $services,
                'services_display' => implode(', ', array_slice($services, 0, 3)) . (count($services) > 3 ? ' +' . (count($services) - 3) : ''),
                'status' => $row['status'] ?? 'pending',
                'priority' => $priority,
                'doctor_name' => $row['doctor_name'] ?? '',
                'created_at' => $row['created_at'],
                'request_type' => $type === 'xray' ? 'X-Ray' : 'Laboratory',
                'source' => !empty($row['appointment_id']) ? 'Online' : 'Walk-in',
            ];
        }

        usort($combined, function($a, $b) {
            return strtotime((string) $b['created_at']) - strtotime((string) $a['created_at']);
        });

        return $combined;
    }

    private function countRequestsByStatus($requests, $status)
    {
        $count = 0;
        foreach ($requests as $request) {
            if ($request['status'] === $status) {
                $count++;
            }
        }
        return $count;
    }

    public function createDiagnosticRequest()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $requestType = $this->request->getPost('request_type');
        $patientName = trim((string) $this->request->getPost('patient_name'));
        $age = $this->request->getPost('age');
        $gender = $this->request->getPost('gender');
        $priority = $this->request->getPost('priority') === 'stat' ? 'stat' : 'routine';
        $priority = $this->request->getPost('priority') ?? 'routine';
        $doctorName = trim($this->request->getPost('doctor_name') ?? '');
        $email = trim($this->request->getPost('email') ?? '');
        $phone = trim($this->request->getPost('phone') ?? '');

        $errors = [];
        if (!in_array($requestType, ['lab', 'xray'], true)) {
            $errors[] = 'Select Laboratory or X-Ray as the request type';
        }
        if (empty($patientName)) {
            $errors[] = 'Patient name is required';
        }
        if (!is_numeric($age) || (int) $age < 0 || (int) $age > 130) {
            $errors[] = 'Age must be a number between 0 and 130';
        }
        if (!in_array($gender, ['Male', 'Female'], true)) {
            $errors[] = 'Sex must be Male or Female';
        }
        if (empty($services) || !is_array($services) || count(array_filter($services)) === 0) {
            $errors[] = 'Please select at least one service';
        }

        if (!empty($errors)) {
            return redirect()->back()
                            ->with('validation_errors', $errors)
                            ->with('error', 'Please fix the errors below')
                            ->withInput();
        }

        $cleanedServices = array_map(function($service) {
            return trim($service);
        }, array_filter($services));

        $patientModel = new PatientModel();
        $patient = null;
        $patientCode = 'N/A';

        try {
            log_message('debug', 'Creating patient: ' . $patientName . ', Age: ' . $age . ', Gender: ' . $gender . ', Phone: ' . $phone);

            $existingPatient = null;
            if (!empty($email)) {
                $existingPatient = $patientModel->where('email', $email)->first();
                if ($existingPatient) {
                    log_message('debug', 'Found existing patient by email: ' . $email);
                }
            }

            if (!$existingPatient) {
                $existingPatient = $patientModel->where('full_name', $patientName)
                                                ->where('age', $age)
                                                ->where('gender', $gender)
                                                ->first();
                if ($existingPatient) {
                    log_message('debug', 'Found existing patient by name/age/gender');
                }
            }

            if (!$existingPatient && !empty($phone)) {
                $existingPatient = $patientModel->where('phone', $phone)->first();
                if ($existingPatient) {
                    log_message('debug', 'Found existing patient by phone: ' . $phone);

                    if ($existingPatient['full_name'] !== $patientName ||
                        $existingPatient['age'] != $age ||
                        $existingPatient['gender'] !== $gender) {

                        log_message('debug', 'Phone matches but name/age/gender are different. Creating new patient...');
                        $existingPatient = null;
                    }
                }
            }

            if ($existingPatient) {
                $updateData = [];
                if (empty($existingPatient['email']) && !empty($email)) {
                    $updateData['email'] = $email;
                }
                if (empty($existingPatient['phone']) && !empty($phone)) {
                    $updateData['phone'] = $phone;
                }
                if (($existingPatient['source'] ?? 'online') !== 'walk-in') {
                    $updateData['source'] = 'walk-in';
                }
                if (!empty($updateData)) {
                    $patientModel->update($existingPatient['id'], $updateData);
                    log_message('debug', 'Updated existing patient: ' . $existingPatient['id']);
                }
                $patient = $patientModel->find($existingPatient['id']);
                $patientCode = $patient['patient_code'] ?? 'N/A';
                log_message('debug', 'Using existing patient with code: ' . $patientCode);
            } else {
                $newPatientCode = $patientModel->generatePatientCode();
                log_message('debug', 'Generated new patient code: ' . $newPatientCode);

                $patientData = [
                    'patient_code' => $newPatientCode,
                    'full_name' => $patientName,
                    'email' => $email ?: null,
                    'phone' => $phone ?: null,
                    'age' => $age,
                    'gender' => $gender,
                    'source' => 'walk-in'
                ];

                try {
                    $patientModel->insert($patientData);
                    $patientId = $patientModel->getInsertID();

                    if ($patientId) {
                        $patient = $patientModel->find($patientId);
                        $patientCode = $patient['patient_code'] ?? $newPatientCode;
                        log_message('debug', 'Patient created with ID: ' . $patientId . ', Code: ' . $patientCode);
                    } else {
                        log_message('error', 'Patient insert returned no ID for: ' . $patientName);
                    }
                } catch (\Exception $insertException) {
                    log_message('error', 'Patient insert exception: ' . $insertException->getMessage());

                    if (strpos($insertException->getMessage(), 'Duplicate entry') !== false) {
                        log_message('debug', 'Duplicate entry detected, trying to find existing patient...');
                        $existing = $patientModel->where('full_name', $patientName)
                                                ->where('age', $age)
                                                ->where('gender', $gender)
                                                ->first();
                        if ($existing) {
                            $patient = $existing;
                            $patientCode = $patient['patient_code'] ?? 'N/A';
                            log_message('debug', 'Found existing patient after duplicate error: ' . $patientCode);
                        } else {
                            $fallbackCode = 'PAT-' . date('y') . '-' . date('Hi') . rand(10, 99);
                            log_message('debug', 'Using fallback code: ' . $fallbackCode);
                            $patientData['patient_code'] = $fallbackCode;
                            try {
                                $patientModel->insert($patientData);
                                $patientId = $patientModel->getInsertID();
                                if ($patientId) {
                                    $patient = $patientModel->find($patientId);
                                    $patientCode = $patient['patient_code'] ?? $fallbackCode;
                                    log_message('debug', 'Patient created with fallback code: ' . $patientCode);
                                }
                            } catch (\Exception $fallbackException) {
                                log_message('error', 'Fallback insert also failed: ' . $fallbackException->getMessage());
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Patient creation error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
        }

        if ($patientCode === null || $patientCode === '' || $patientCode === 'N/A') {
            return redirect()->back()
                             ->withInput()
                             ->with('error', 'Could not assign a patient code. '
                                 . 'Please try again, or create the patient from the Patients page first.');
        }

        if ($requestType === 'lab' || $requestType === 'xray') {
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
                'email'            => $email ?: null,
                'phone'            => $phone ?: null,
                'services'         => implode(', ', $cleanedServices),
                'request_date'     => date('Y-m-d'),
                'doctor_name'      => $doctorName,
                'priority'         => $requestType === 'xray'
                                        ? ($priority === 'stat' ? 'STAT' : 'Routine')
                                        : $priority,
                'status'           => DiagnosticRequestModel::STATUS_PENDING,
            ]);

            $requestId = $model->getInsertID();

            NotificationModel::dispatch(
                $requestType,
                $requestType === 'lab' ? 'New Lab Request (Walk-in)' : 'New X-Ray Request (Walk-in)',
                'New ' . ($requestType === 'lab' ? 'lab' : 'X-Ray') . ' request for walk-in patient ' . $patientName,
                (int) $requestId,
                null,
                true,
                $gender
            );

            $label = $requestType === 'lab' ? 'Lab' : 'X-Ray';

            return redirect()->to(base_url('receptionist/diagnostic-requests'))
                            ->with('success', $label . ' request created successfully for ' . $patientName . '! Patient Code: ' . $patientCode);
        }

        return redirect()->back()->with('error', 'Invalid request type');
    }

    public function updateDiagnosticStatus($id, $type, $status)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        try {
            return $this->response->setJSON($this->changeDiagnosticStatus((int) $id, (string) $type, (string) $status));
        } catch (\Throwable $e) {
            log_message('error', 'Update diagnostic status error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error updating status. Please try again.']);
        }
    }

    public function deleteDiagnosticRequest($id, $type)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        try {
            if (!in_array($type, ['lab', 'xray'], true)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Invalid request type']);
            }

            $model = new DiagnosticRequestModel();

            $request = $model
                ->where('id', (int) $id)
                ->where('type', $type)
                ->first();

            if (!$request) {
                return $this->response->setJSON(['success' => false, 'message' => 'Request not found']);
            }

            $model->delete((int) $id);

            return $this->response->setJSON(['success' => true, 'message' => 'Request deleted successfully']);

        } catch (\Exception $e) {
            log_message('error', 'Delete diagnostic request error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error deleting request']);
        }
    }

    public function getRequestDetails($id, $type)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        try {
            if (!in_array($type, ['lab', 'xray'], true)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Invalid request type']);
            }

            $model = new DiagnosticRequestModel();

            $request = $model
                ->where('id', (int) $id)
                ->where('type', $type)
                ->first();

            if (!$request) {
                return $this->response->setJSON(['success' => false, 'message' => 'Request not found']);
            }

            $patientCode = $request['patient_code'] ?? null;
            if (empty($patientCode)) {
                $patientModel = new PatientModel();
                $patient = $patientModel->where('full_name', $request['patient_name'])->first();
                if ($patient) {
                    $patientCode = $patient['patient_code'] ?? 'N/A';
                } else {
                    $patientCode = 'N/A';
                }
            }

            $source = !empty($request['appointment_id']) ? 'Online' : 'Walk-in';
            $priority = $request['priority'] ?? 'routine';
            $updatedAt = $request['updated_at'] ?? $request['created_at'] ?? null;

            return $this->response->setJSON([
                'success' => true,
                'data' => [
                    'id' => $request['id'],
                    'patient_name' => $request['patient_name'] ?? 'Unknown',
                    'patient_code' => $patientCode,
                    'age' => $request['age'] ?? 'N/A',
                    'gender' => $request['gender'] ?? 'N/A',
                    'email' => $request['email'] ?? '',
                    'phone' => $request['phone'] ?? '',
                    'services' => $request['services'] ?? '',
                    'status' => $request['status'] ?? 'pending',
                    'doctor_name' => $request['doctor_name'] ?? '',
                    'created_at' => $request['created_at'] ?? date('Y-m-d H:i:s'),
                    'updated_at' => $updatedAt,
                    'findings' => $request['findings'] ?? null,
                    'remarks' => $request['remarks'] ?? null,
                    'released_at' => $request['released_at'] ?? null,
                    'source' => $source,
                    'priority' => $priority,
                    'radiologist_name' => $request['radiologist_name'] ?? null,
                    'med_tech_name' => $request['med_tech_name'] ?? null,
                    'interpretation' => $request['interpretation'] ?? null,
                    'image_path' => $request['image_path'] ?? null,
                    'exam_date' => $request['request_date'] ?? null,
                    'request_date' => $request['request_date'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Get request details error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error fetching details']);
        }
    }

    // =============================================
    // PRINT — department-aware (matches Admin)
    // =============================================
    public function printRequest($id, $type)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $type = strtolower((string) $type);
        if (!in_array($type, ['lab', 'xray'], true)) {
            return redirect()->to(base_url('receptionist/diagnostic-requests'))
                             ->with('error', 'Unknown request type.');
        }

        $row = (new DiagnosticRequestModel())
            ->where('id', (int) $id)
            ->where('type', $type)
            ->first();

        if (!$row) {
            return redirect()->to(base_url('receptionist/diagnostic-requests'))
                             ->with('error', 'Request not found.');
        }

        return $this->renderPrintable($row, $type);
    }

    public function syncWalkInPatients()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $patientModel = new PatientModel();
        $count = $patientModel->syncWalkInPatients();

        return redirect()->to(base_url('receptionist/patients'))
                        ->with('success', "Synced {$count} walk-in patients to the patients table.");
    }

    /** Appointments per day for the last 7 days (oldest first), one query. */
    private function getWeeklyAppointmentData()
    {
        $start = date('Y-m-d', strtotime('-6 days'));

        $rows = db_connect()->table('appointments')
            ->select('appointment_date AS d, COUNT(*) AS n')
            ->where('appointment_date >=', $start)
            ->where('appointment_date <=', date('Y-m-d'))
            ->groupBy('appointment_date')
            ->get()->getResultArray();
        $counts = array_column($rows, 'n', 'd');

        $weeklyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $weeklyData[] = (int) ($counts[date('Y-m-d', strtotime("-$i days"))] ?? 0);
        }

        return $weeklyData;
    }

    private function getServiceDistributionData()
    {
        $appointmentModel = new AppointmentModel();
        $today = date('Y-m-d');

        $todayAppointments = $appointmentModel
            ->where('appointment_date', $today)
            ->findAll();

        $serviceCounts = [];

        foreach ($todayAppointments as $appt) {
            $labList  = json_decode($appt['lab_services']  ?? '[]', true);
            $xrayList = json_decode($appt['xray_services'] ?? '[]', true);

            $hasLab  = is_array($labList)  && count(array_filter($labList))  > 0;
            $hasXray = is_array($xrayList) && count(array_filter($xrayList)) > 0;

            if ($hasLab && $hasXray) {
                $type = 'Laboratory + X-Ray';
            } elseif ($hasXray) {
                $type = 'X-Ray';
            } elseif ($hasLab) {
                $type = 'Laboratory';
            } else {
                $raw  = trim((string) ($appt['service_type'] ?? ''));
                $type = $raw !== '' ? ucfirst(strtolower($raw)) : 'Unspecified';
            }

            $serviceCounts[$type] = ($serviceCounts[$type] ?? 0) + 1;
        }

        if (empty($serviceCounts)) {
            return [
                'labels' => [],
                'counts' => []
            ];
        }

        return [
            'labels' => array_keys($serviceCounts),
            'counts' => array_values($serviceCounts)
        ];
    }

    public function getDashboardData()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        try {
            $data = [
                'weekly_appointments' => $this->getWeeklyAppointmentData(),
                'service_distribution' => $this->getServiceDistributionData()
            ];

            return $this->response->setJSON([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Get dashboard data error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error fetching dashboard data'
            ]);
        }
    }

    public function getPaymentDetails($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        try {
            $paymentModel = new PaymentModel();
            $payment = $paymentModel->find($id);

            if (!$payment) {
                return $this->response->setJSON(['success' => false, 'message' => 'Payment not found']);
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $payment
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Get payment details error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error fetching payment details']);
        }
    }

    // The printable receipt lives in the Billing module.
    public function printReceipt($id)
    {
        return redirect()->to(base_url('receptionist/billing/receipt/' . (int) $id));
    }

    // Refunds are admin-only: they go through admin/billing or the
    // admin Payments page, which also recalculate the invoice.

    /* ============================================================
       AVAILABILITY + OVERRIDE BOOKING
       ============================================================ */

    public function appointmentSlots()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $date = (string) $this->request->getGet('date');
        $type = (string) ($this->request->getGet('type') ?? 'laboratory');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->response->setStatusCode(400)
                ->setJSON(['ok' => false, 'error' => 'Invalid date.']);
        }

        $availability = new AvailabilityModel();

        if (!$availability->isWorkingDay($date)) {
            return $this->response->setJSON([
                'ok' => true, 'working' => false,
                'error' => 'The clinic is closed on that date.',
                'slots' => [],
            ]);
        }

        $depts   = $availability->departmentsFor($type);
        $perDept = [];
        foreach ($depts as $d) {
            $perDept[$d] = $availability->slotsFor($date, $d);
        }

        $merged = [];
        foreach ($perDept[$depts[0]] as $i => $slot) {
            $available = $slot['available'];
            foreach ($depts as $d) {
                if (!$perDept[$d][$i]['available']) { $available = false; break; }
            }
            $merged[] = [
                'time'      => $slot['time'],
                'label'     => $slot['label'],
                'available' => $available,
                'blocked'   => $slot['blocked'],
                'reason'    => $slot['reason'],
            ];
        }

        return $this->response->setJSON([
            'ok' => true, 'working' => true, 'date' => $date,
            'type' => $type, 'slots' => $merged,
        ]);
    }

    public function overrideBook()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $rules = [
            'appointment_date' => 'required|valid_date',
            'appointment_time' => 'required|regex_match[/^\d{2}:\d{2}$/]',
            'full_name'        => 'required|min_length[2]|max_length[150]',
            'age'              => 'required|is_natural_no_zero|less_than_equal_to[130]',
            'gender'           => 'required|in_list[Male,Female,Other]',
            'service_type'     => 'required|in_list[laboratory,xray,both]',
            'email'            => 'permit_empty|valid_email',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', 'Please fill in the override form completely.');
        }

        $date    = (string) $this->request->getPost('appointment_date');
        $time    = (string) $this->request->getPost('appointment_time');
        $type    = (string) $this->request->getPost('service_type');
        $lab     = $this->cleanServiceList((array) ($this->request->getPost('lab_services')  ?? []));
        $xray    = $this->cleanServiceList((array) ($this->request->getPost('xray_services') ?? []));

        if ($lab === [] && $xray === []) {
            return redirect()->back()->withInput()
                ->with('error', 'Select at least one laboratory or X-ray service.');
        }

        $email   = trim((string) $this->request->getPost('email'));
        $phone   = trim((string) $this->request->getPost('phone'));

        $reference = 'APPT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

        $appointmentModel = new AppointmentModel();

        $appointmentData = [
            'reference_number' => $reference,
            'appointment_date' => $date,
            'appointment_time' => $time,
            'full_name'        => $this->request->getPost('full_name'),
            'age'              => $this->request->getPost('age'),
            'gender'           => $this->request->getPost('gender'),
            'email'            => $email !== '' ? $email : null,
            'phone'            => $phone !== '' ? $phone : null,
            'service_type'     => $type,
            'lab_services'     => !empty($lab)  ? json_encode(array_values($lab))  : null,
            'xray_services'    => !empty($xray) ? json_encode(array_values($xray)) : null,
            'other_requests'   => $this->request->getPost('other_requests') ?: null,
            'status'           => 'approved',
            'is_override'      => 1,
            'arrival_time'     => date('Y-m-d H:i:s'),
        ];

        $appointmentId = $appointmentModel->insert($appointmentData);

        if (!$appointmentId) {
            return redirect()->back()->withInput()
                ->with('error', 'Could not create the override appointment.');
        }

        $patientModel = new PatientModel();
        $patientModel->findOrCreateFromAppointment($appointmentData);

        return redirect()->to(base_url('receptionist/appointments'))
            ->with('success', 'Override booking created (' . $reference . '). '
                . 'Use the Approve button to generate the diagnostic request(s).');
    }
}