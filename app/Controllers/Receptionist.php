<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\DiagnosticRequestModel;
use App\Models\NotificationModel;
use App\Models\ServiceModel;
use App\Models\PatientModel;
use App\Models\PaymentModel;

class Receptionist extends BaseController
{
    private function checkAuth()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('login'));
        }

        $role = session()->get('role');
        if ($role !== 'receptionist') {
            if ($role === 'admin') {
                return redirect()->to(base_url('admin/dashboard'));
            }
            return redirect()->to(base_url('login'));
        }
        return null;
    }

    public function dashboard()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $appointmentModel = new AppointmentModel();
        $paymentModel = new PaymentModel();

        $today = date('Y-m-d');

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

        $data['pending_diagnostic'] = $appointmentModel
            ->where('status', 'approved')
            ->countAllResults();

        $data['unpaid_bills'] = $appointmentModel
            ->where('status', 'approved')
            ->countAllResults();

        $todayPayments = $paymentModel
            ->where('DATE(payment_date)', $today)
            ->where('payment_status', 'paid')
            ->findAll();

        $todayCollections = 0;
        foreach ($todayPayments as $payment) {
            $todayCollections += floatval($payment['total_amount']);
        }
        $data['today_collections'] = $todayCollections;

        $data['total_patients'] = count($this->getAllPatients());

        $data['weekly_appointments'] = $this->getWeeklyAppointmentData();

        $serviceData = $this->getServiceDistributionData();
        $data['service_labels'] = $serviceData['labels'];
        $data['service_counts'] = $serviceData['counts'];

        // KPI sparklines — same look as the Admin dashboard.
        $data['kpiTrends'] = $this->getKpiTrends(14, (int) $data['total_patients']);

        return view('Receptionist/dashboard', $data);
    }

    /**
     * Daily series for the KPI sparklines on the Receptionist dashboard.
     * Oldest day first, ending today. Every value is a real per-day
     * count from the same tables the KPI cards themselves read.
     *
     * Keys match the card keys in the Receptionist dashboard view.
     */
    private function getKpiTrends(int $days, int $totalPatients): array
    {
        $db    = db_connect();
        $start = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        $end   = date('Y-m-d');

        $dates = [];
        for ($i = 0; $i < $days; $i++) {
            $dates[] = date('Y-m-d', strtotime($start . ' +' . $i . ' days'));
        }

        // Generic "date, value" grouped query → day-indexed series.
        $series = function (string $table, string $dateExpr, string $valueExpr, array $where = []) use ($db, $dates, $start, $end) {
            $b = $db->table($table)
                ->select("$dateExpr AS d, $valueExpr AS v", false)
                ->where("$dateExpr >=", $start)
                ->where("$dateExpr <=", $end);
            foreach ($where as $col => $val) {
                $b->where($col, $val);
            }
            $rows = $b->groupBy('d')->get()->getResultArray();

            $map = [];
            foreach ($rows as $r) {
                $map[$r['d']] = (float) $r['v'];
            }
            return array_map(fn($d) => $map[$d] ?? 0, $dates);
        };

        // All patients: cumulative walk-back from today's total using
        // the patients table's per-day new registrations.
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
            'unpaid'      => $series('diagnostic_requests', 'DATE(created_at)', 'COUNT(*)', ['status' => 'in_progress']),
            'collections' => $this->getCollectionsSeries($dates),
        ];
    }

    /**
     * Daily paid revenue — real per-day sums from the payments table.
     */
    private function getCollectionsSeries(array $dates): array
    {
        if (empty($dates)) { return []; }

        $db    = db_connect();
        $start = $dates[0];
        $end   = end($dates);

        $rows = $db->table('payments')
            ->select('DATE(payment_date) AS d, SUM(total_amount) AS v', false)
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

    private function getAllPatients(): array
    {
        $patientModel     = new PatientModel();
        $diagnosticModel  = new DiagnosticRequestModel();
        $appointmentModel = new AppointmentModel();

        $allPatients = [];
        $seenKeys    = [];

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
                        'id'           => $patient['id'] ?? null,
                        'patient_code' => $patient['patient_code'] ?? 'N/A',
                        'full_name'    => $patient['full_name'] ?? 'Unknown',
                        'email'        => $patient['email'] ?? '',
                        'phone'        => $patient['phone'] ?? '',
                        'age'          => $patient['age'] ?? '',
                        'gender'       => $patient['gender'] ?? '',
                        'source'       => ucfirst($patient['source'] ?? 'Unknown'),
                        'last_visit'   => $patient['created_at'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Patients - Patients table error: ' . $e->getMessage());
        }

        try {
            $appointmentPatients = $appointmentModel
                ->select('full_name, email, phone, age, gender, MAX(appointment_date) as last_visit')
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
                        'id'           => $this->resolvePatientId(null, $patient['full_name'] ?? '', $patient['age'] ?? null),
                        'patient_code' => 'N/A',
                        'full_name'    => $patient['full_name'] ?? 'Unknown',
                        'email'        => $patient['email'] ?? '',
                        'phone'        => $patient['phone'] ?? '',
                        'age'          => $patient['age'] ?? '',
                        'gender'       => $patient['gender'] ?? '',
                        'source'       => 'Online',
                        'last_visit'   => $patient['last_visit'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Patients - Appointments error: ' . $e->getMessage());
        }

        try {
            $labPatients = $diagnosticModel
                ->select('patient_name as full_name, age, gender, MAX(request_date) as last_visit')
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
                        'id'           => $this->resolvePatientId(null, $patient['full_name'] ?? '', $patient['age'] ?? null),
                        'patient_code' => 'N/A',
                        'full_name'    => $patient['full_name'] ?? 'Unknown',
                        'email'        => '',
                        'phone'        => '',
                        'age'          => $patient['age'] ?? '',
                        'gender'       => $patient['gender'] ?? '',
                        'source'       => 'Walk-in (Lab)',
                        'last_visit'   => $patient['last_visit'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Patients - Lab Requests error: ' . $e->getMessage());
        }

        try {
            $xrayPatients = $diagnosticModel
                ->select('patient_name as full_name, age, gender, MAX(request_date) as last_visit')
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
                        'id'           => $this->resolvePatientId(null, $patient['full_name'] ?? '', $patient['age'] ?? null),
                        'patient_code' => 'N/A',
                        'full_name'    => $patient['full_name'] ?? 'Unknown',
                        'email'        => '',
                        'phone'        => '',
                        'age'          => $patient['age'] ?? '',
                        'gender'       => $patient['gender'] ?? '',
                        'source'       => 'Walk-in (X-Ray)',
                        'last_visit'   => $patient['last_visit'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Patients - X-Ray error: ' . $e->getMessage());
        }

        usort($allPatients, function ($a, $b) {
            return strtotime($b['last_visit'] ?? '0') - strtotime($a['last_visit'] ?? '0');
        });

        return $allPatients;
    }

    /**
     * Resolve a patient row's id by name (and optionally age) if it
     * wasn't already provided. Used so the eye icon always has a valid
     * patient id, even for rows that came from appointments or
     * diagnostic requests rather than the patients table.
     */
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
            log_message('error', 'resolvePatientId error: ' . $e->getMessage());
            return null;
        }
    }

    public function appointments()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        try {
            $model = new AppointmentModel();

            if (method_exists($model, 'checkLateAppointments')) {
                $model->checkLateAppointments();
            }

            $data['appointments'] = $model->orderBy('appointment_date', 'DESC')
                                          ->orderBy('appointment_time', 'ASC')
                                          ->findAll();

            $data['total']     = $model->countAll();
            $data['pending']   = $model->where('status', 'pending')->countAllResults();
            $data['approved']  = $model->where('status', 'approved')->countAllResults();
            $data['completed'] = $model->where('status', 'completed')->countAllResults();
            $data['cancelled'] = $model->where('status', 'cancelled')->countAllResults();
            $data['late']      = $model->where('status', 'late')->countAllResults();

            return view('Receptionist/appointments', $data);
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

    /**
     * View a single patient using the shared appointment/patient view.
     * Reached from the eye icon on the patients list.
     */
    public function viewPatient($id = null)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $id = (int) $id;
        if ($id <= 0) {
            return redirect()->to(base_url('receptionist/patients'))
                             ->with('error', 'Invalid patient.');
        }

        $patientModel = new PatientModel();
        $patient      = $patientModel->find($id);

        if (! $patient) {
            return redirect()->to(base_url('receptionist/patients'))
                             ->with('error', 'Patient not found.');
        }

        // Normalize "source" into a human-readable service type.
        $source      = strtolower((string) ($patient['source'] ?? ''));
        $serviceType = (strpos($source, 'walk-in') !== false) ? 'Walk-in' : 'Online';

        // Shape the data the way appointment_view.php expects.
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

        // Pull this patient's diagnostic history (optional — fails silently).
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
            log_message('error', 'viewPatient history error: ' . $e->getMessage());
        }

        return view('Receptionist/appointment_view', [
            'appointment'   => $appointment,
            'lab_services'  => $lab_services,
            'xray_services' => $xray_services,
            'viewMode'      => 'patient',
        ]);
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

        $patientModel = new PatientModel();
        $patient = $patientModel->findOrCreateFromAppointment($appointment);

        $labServices = json_decode($appointment['lab_services'], true) ?? [];
        $xrayServices = json_decode($appointment['xray_services'], true) ?? [];
        $successMessages = [];

        $diagnosticModel = new DiagnosticRequestModel();

        if (!empty($labServices)) {
            $existing = $diagnosticModel
                ->where('appointment_id', $id)
                ->where('type', DiagnosticRequestModel::TYPE_LAB)
                ->first();

            if ($existing) {
                $successMessages[] = 'Lab request already exists.';
            } else {
                $cleanedServices = array_map(function($service) {
                    $service = str_replace('\/', '/', $service);
                    $service = str_replace('\\/', '/', $service);
                    $service = stripslashes($service);
                    return trim($service);
                }, $labServices);

                $diagnosticModel->insert([
                    'reference_number' => 'LAB-' . date('y') . '-' . strtoupper(bin2hex(random_bytes(3))),
                    'type'             => DiagnosticRequestModel::TYPE_LAB,
                    'appointment_id'   => $appointment['id'],
                    'patient_name'     => $appointment['full_name'],
                    'age'              => $appointment['age'],
                    'gender'           => $appointment['gender'],
                    'email'            => $appointment['email'] ?? null,
                    'phone'            => $appointment['phone'] ?? null,
                    'services'         => implode(', ', $cleanedServices),
                    'request_date'     => date('Y-m-d'),
                    'priority'         => 'routine',
                    'status'           => DiagnosticRequestModel::STATUS_PENDING,
                ]);

                $labRequestId = $diagnosticModel->getInsertID();

                NotificationModel::notify(
                    'lab',
                    'New Lab Request (Online Booking)',
                    'New lab request for online patient ' . $appointment['full_name'],
                    $labRequestId,
                    'medtech/request/view/' . $labRequestId
                );

                $successMessages[] = 'Lab request created successfully!';
                log_message('info', 'Lab request created for appointment #' . $id . ' (Patient: ' . $appointment['full_name'] . ')');
            }
        }

        if (!empty($xrayServices)) {
            $existing = $diagnosticModel
                ->where('appointment_id', $id)
                ->where('type', DiagnosticRequestModel::TYPE_XRAY)
                ->first();

            if ($existing) {
                $successMessages[] = 'X-Ray request already exists.';
            } else {
                $cleanedServices = array_map(function($service) {
                    $service = str_replace('\/', '/', $service);
                    $service = str_replace('\\/', '/', $service);
                    $service = stripslashes($service);
                    return trim($service);
                }, $xrayServices);

                $diagnosticModel->insert([
                    'reference_number' => 'XR-' . date('y') . '-' . strtoupper(bin2hex(random_bytes(3))),
                    'type'             => DiagnosticRequestModel::TYPE_XRAY,
                    'appointment_id'   => $appointment['id'],
                    'patient_name'     => $appointment['full_name'],
                    'age'              => $appointment['age'],
                    'gender'           => $appointment['gender'],
                    'email'            => $appointment['email'] ?? null,
                    'phone'            => $appointment['phone'] ?? null,
                    'services'         => implode(', ', $cleanedServices),
                    'request_date'     => date('Y-m-d'),
                    'priority'         => 'Routine',
                    'status'           => DiagnosticRequestModel::STATUS_PENDING,
                ]);

                $xrayId = $diagnosticModel->getInsertID();

                NotificationModel::notify(
                    'xray',
                    'New X-Ray Request (Online Booking)',
                    'New X-Ray request for online patient ' . $appointment['full_name'],
                    $xrayId,
                    'radiologist/examination/view/' . $xrayId
                );

                $successMessages[] = 'X-Ray request created successfully!';
                log_message('info', 'X-Ray request created for appointment #' . $id . ' (Patient: ' . $appointment['full_name'] . ')');
            }
        }

        $message = 'Appointment approved successfully!';
        if (!empty($successMessages)) {
            $message .= ' ' . implode(' ', $successMessages);
        }
        if ($patient) {
            $message .= ' Patient Code: ' . $patient['patient_code'];
        }

        if (empty($labServices) && empty($xrayServices)) {
            $message .= ' (No diagnostic services selected)';
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

        $allPatients = $this->getAllPatients();

        $data['patients'] = $allPatients;
        $data['total'] = count($allPatients);

        return view('Receptionist/patients', $data);
    }

    public function billing()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        return view('Receptionist/billing');
    }

    public function payments()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $paymentModel = new PaymentModel();
        $payments = $paymentModel->orderBy('payment_date', 'DESC')->findAll();

        $today = date('Y-m-d');
        $todayPayments = $paymentModel->where('DATE(payment_date)', $today)->where('payment_status', 'paid')->findAll();
        $todayTotal = 0;
        foreach ($todayPayments as $p) {
            $todayTotal += floatval($p['total_amount']);
        }

        $monthlyPayments = $paymentModel->where('MONTH(payment_date)', date('m'))->where('YEAR(payment_date)', date('Y'))->where('payment_status', 'paid')->findAll();
        $monthlyTotal = 0;
        foreach ($monthlyPayments as $p) {
            $monthlyTotal += floatval($p['total_amount']);
        }

        $uniquePatients = [];
        foreach ($payments as $p) {
            if (!in_array($p['patient_name'], $uniquePatients)) {
                $uniquePatients[] = $p['patient_name'];
            }
        }

        $patientModel = new PatientModel();

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
            'payments' => $payments,
            'total' => count($payments),
            'today_total' => $todayTotal,
            'today_count' => count($todayPayments),
            'monthly_total' => $monthlyTotal,
            'total_patients' => count($uniquePatients)
        ];

        return view('Receptionist/payments', $data);
    }

    public function reports()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        return view('Receptionist/reports');
    }

    public function syncLabRequests()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $appointmentModel = new AppointmentModel();
        $diagnosticModel  = new DiagnosticRequestModel();
        $patientModel     = new PatientModel();

        $appointments = $appointmentModel
            ->where('status', 'approved')
            ->where('lab_services IS NOT NULL')
            ->where('lab_services !=', '[]')
            ->where('lab_services !=', 'null')
            ->findAll();

        $created = 0;
        $skipped = 0;
        $patientsCreated = 0;

        foreach ($appointments as $appointment) {
            $patient = $patientModel->findOrCreateFromAppointment($appointment);
            if ($patient) {
                $patientsCreated++;
            }

            $existing = $diagnosticModel
                ->where('appointment_id', $appointment['id'])
                ->where('type', DiagnosticRequestModel::TYPE_LAB)
                ->first();

            if ($existing) {
                $skipped++;
                continue;
            }

            $labServices = json_decode($appointment['lab_services'], true) ?? [];

            if (!empty($labServices)) {
                $cleanedServices = array_map(function($service) {
                    $service = str_replace('\/', '/', $service);
                    $service = str_replace('\\/', '/', $service);
                    $service = stripslashes($service);
                    return trim($service);
                }, $labServices);

                $diagnosticModel->insert([
                    'reference_number' => 'LAB-' . date('y') . '-' . strtoupper(bin2hex(random_bytes(3))),
                    'type'             => DiagnosticRequestModel::TYPE_LAB,
                    'appointment_id'   => $appointment['id'],
                    'patient_name'     => $appointment['full_name'],
                    'age'              => $appointment['age'],
                    'gender'           => $appointment['gender'],
                    'services'         => implode(', ', $cleanedServices),
                    'request_date'     => $appointment['appointment_date'] ?? date('Y-m-d'),
                    'priority'         => 'routine',
                    'status'           => DiagnosticRequestModel::STATUS_PENDING,
                ]);
                $created++;
            }
        }

        $xrayAppointments = $appointmentModel
            ->where('status', 'approved')
            ->where('xray_services IS NOT NULL')
            ->where('xray_services !=', '[]')
            ->where('xray_services !=', 'null')
            ->findAll();

        $xrayCreated = 0;
        $xraySkipped = 0;

        foreach ($xrayAppointments as $appointment) {
            $existing = $diagnosticModel
                ->where('appointment_id', $appointment['id'])
                ->where('type', DiagnosticRequestModel::TYPE_XRAY)
                ->first();

            if ($existing) {
                $xraySkipped++;
                continue;
            }

            $xrayServices = json_decode($appointment['xray_services'], true) ?? [];

            if (!empty($xrayServices)) {
                $cleanedServices = array_map(function($service) {
                    $service = str_replace('\/', '/', $service);
                    $service = str_replace('\\/', '/', $service);
                    $service = stripslashes($service);
                    return trim($service);
                }, $xrayServices);

                $diagnosticModel->insert([
                    'reference_number' => 'XR-' . date('y') . '-' . strtoupper(bin2hex(random_bytes(3))),
                    'type'             => DiagnosticRequestModel::TYPE_XRAY,
                    'appointment_id'   => $appointment['id'],
                    'patient_name'     => $appointment['full_name'],
                    'age'              => $appointment['age'],
                    'gender'           => $appointment['gender'],
                    'services'         => implode(', ', $cleanedServices),
                    'request_date'     => $appointment['appointment_date'] ?? date('Y-m-d'),
                    'priority'         => 'Routine',
                    'status'           => DiagnosticRequestModel::STATUS_PENDING,
                ]);
                $xrayCreated++;
            }
        }

        return redirect()->to(base_url('receptionist/appointments'))
                        ->with('success', "Synced $created lab requests, $xrayCreated X-Ray requests, and $patientsCreated patients. Skipped $skipped lab and $xraySkipped X-Ray (already exist).");
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

            $priority = $row['priority'] ?? 'routine';
            if ($type === 'lab') {
                $priority = strpos(strtolower((string) $rawServices), 'stat') !== false
                    ? 'stat'
                    : 'routine';
            } else {
                $priority = strtolower((string) $priority) === 'stat' ? 'stat' : 'routine';
            }

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
                'doctor_name' => $row['doctor_name'] ?? 'Dr. Ana Cruz',
                'created_at' => $row['created_at'],
                'request_type' => $type === 'xray' ? 'X-Ray' : 'Laboratory',
                'source' => !empty($row['appointment_id']) ? 'Online' : 'Walk-in',
            ];
        }

        usort($combined, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
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
        $patientName = trim($this->request->getPost('patient_name'));
        $age = $this->request->getPost('age');
        $gender = $this->request->getPost('gender');
        $services = $this->request->getPost('services') ?? [];
        $priority = $this->request->getPost('priority') ?? 'routine';
        $doctorName = trim($this->request->getPost('doctor_name') ?? '');
        $email = trim($this->request->getPost('email') ?? '');
        $phone = trim($this->request->getPost('phone') ?? '');

        $errors = [];
        if (empty($patientName)) {
            $errors[] = 'Patient name is required';
        }
        if (empty($age) || $age < 0) {
            $errors[] = 'Valid age is required';
        }
        if (empty($gender)) {
            $errors[] = 'Gender is required';
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

        if ($requestType === 'lab' || $requestType === 'xray') {
            $model = new DiagnosticRequestModel();

            $prefix = $requestType === 'xray' ? 'XR' : 'LAB';

            $model->insert([
                'reference_number' => $prefix . '-' . date('y') . '-' . strtoupper(bin2hex(random_bytes(3))),
                'type'             => $requestType,
                'appointment_id'   => null,
                'patient_name'     => $patientName,
                'patient_code'     => $patientCode !== 'N/A' ? $patientCode : null,
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

            NotificationModel::notify(
                $requestType,
                $requestType === 'lab' ? 'New Lab Request (Walk-in)' : 'New X-Ray Request (Walk-in)',
                'New ' . ($requestType === 'lab' ? 'lab' : 'X-Ray') . ' request for walk-in patient ' . $patientName,
                $requestId,
                ($requestType === 'lab' ? 'medtech/request/view/' : 'radiologist/examination/view/') . $requestId
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

        $validStatuses = ['pending', 'in_progress', 'completed', 'released', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid status']);
        }

        try {
            $model = new DiagnosticRequestModel();
            $paymentModel = new PaymentModel();
            $serviceModel = new ServiceModel();

            $request = $model
                ->where('id', (int) $id)
                ->where('type', $type)
                ->first();

            if (!$request) {
                return $this->response->setJSON(['success' => false, 'message' => 'Request not found']);
            }

            $servicesString = $request['services'] ?? '';

            if ($status === 'in_progress') {

                $existingPayment = $paymentModel->getByRequest($id, $type);

                if ($existingPayment) {
                    $model->update($id, ['status' => $status]);

                    return $this->response->setJSON([
                        'success' => true,
                        'message' => 'Status updated successfully. Payment already recorded.',
                        'payment_recorded' => true,
                        'payment_id' => $existingPayment['id']
                    ]);
                }

                $totalAmount = 0;
                $servicesList = [];

                if (!empty($servicesString)) {
                    $serviceNames = array_map('trim', explode(',', $servicesString));

                    foreach ($serviceNames as $serviceName) {
                        if (!empty($serviceName)) {
                            $service = $serviceModel->where('service_name', $serviceName)->first();
                            if ($service) {
                                $charge = floatval($service['charge'] ?? 0);
                                $totalAmount += $charge;
                                $servicesList[] = [
                                    'name' => $serviceName,
                                    'charge' => $charge
                                ];
                            }
                        }
                    }
                }

                if ($totalAmount == 0) {
                    $totalAmount = 500.00;
                    $servicesList[] = [
                        'name' => 'Consultation Fee',
                        'charge' => 500.00
                    ];
                }

                $patientCode = 'N/A';
                $patientModel = new PatientModel();
                $patient = $patientModel->where('full_name', $request['patient_name'])->first();
                if ($patient) {
                    $patientCode = $patient['patient_code'] ?? 'N/A';
                }

                $paymentData = [
                    'request_id' => $id,
                    'request_type' => $type,
                    'patient_name' => $request['patient_name'],
                    'patient_code' => $patientCode,
                    'appointment_id' => $request['appointment_id'] ?? null,
                    'services' => json_encode($servicesList),
                    'total_amount' => $totalAmount,
                    'amount_paid' => $totalAmount,
                    'payment_method' => 'cash',
                    'received_by' => session()->get('username') ?? 'receptionist',
                    'notes' => 'Payment recorded when starting processing'
                ];

                $paymentId = $paymentModel->recordPayment($paymentData);

                if ($paymentId) {
                    log_message('info', 'Payment recorded for request #' . $id . ' (Type: ' . $type . ', Amount: ₱' . $totalAmount . ')');
                } else {
                    log_message('error', 'Failed to record payment for request #' . $id);
                }

                $model->update($id, ['status' => $status]);

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Status updated to in_progress. Payment recorded: ₱' . number_format($totalAmount, 2),
                    'payment_recorded' => true,
                    'payment_id' => $paymentId,
                    'amount' => $totalAmount,
                    'patient_code' => $patientCode
                ]);
            }

            $update = ['status' => $status];
            if ($status === 'released') {
                $update['released_at'] = date('Y-m-d H:i:s');
            }
            $model->update($id, $update);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Status updated successfully',
                'payment_recorded' => false
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Update diagnostic status error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error updating status: ' . $e->getMessage()
            ]);
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
                    'doctor_name' => $request['doctor_name'] ?? 'Dr. Ana Cruz',
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

    public function printRequest($id, $type)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $type = strtolower((string) $type);
        if (!in_array($type, ['lab', 'xray'], true)) {
            return redirect()->to(base_url('receptionist/diagnostic-requests'));
        }

        $model = new DiagnosticRequestModel();

        $row = $model
            ->where('id', (int) $id)
            ->where('type', $type)
            ->first();

        if (!$row) {
            return redirect()->to(base_url('receptionist/diagnostic-requests'))
                             ->with('error', 'Request not found.');
        }

        $row['type'] = $type;

        return view('Receptionist/print_request', ['request' => $row, 'type' => $type]);
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

    private function getWeeklyAppointmentData()
    {
        $appointmentModel = new AppointmentModel();
        $weeklyData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $count = $appointmentModel->where('appointment_date', $date)->countAllResults();
            $weeklyData[] = $count;
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

            $data = [
                'payment' => $payment,
                'services' => $services
            ];

            return view('Receptionist/print_receipt', $data);

        } catch (\Exception $e) {
            log_message('error', 'Print receipt error: ' . $e->getMessage());
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
                return $this->response->setJSON(['success' => false, 'message' => 'Payment not found']);
            }

            if ($payment['payment_status'] !== 'paid') {
                return $this->response->setJSON(['success' => false, 'message' => 'Only paid payments can be refunded']);
            }

            $paymentModel->update($id, [
                'payment_status' => 'refunded',
                'notes' => ($payment['notes'] ?? '') . ' | Refunded on ' . date('Y-m-d H:i:s')
            ]);

            log_message('info', 'Payment #' . $id . ' refunded');

            return $this->response->setJSON(['success' => true, 'message' => 'Payment refunded successfully']);

        } catch (\Exception $e) {
            log_message('error', 'Refund payment error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error refunding payment']);
        }
    }
}