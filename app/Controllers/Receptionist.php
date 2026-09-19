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

        return view('Receptionist/dashboard', $data);
    }

    /**
     * Aggregate all patients from every source and dedupe them.
     *
     * This is the single source of truth for the patient list and the
     * patient count. Both dashboard() and patients() call it, so the
     * number shown on the dashboard card is always the same as the
     * number shown on the Patients page.
     */
    private function getAllPatients(): array
    {
        $patientModel     = new PatientModel();
        $diagnosticModel  = new DiagnosticRequestModel();
        $appointmentModel = new AppointmentModel();

        $allPatients = [];
        $seenKeys    = [];

        // ========== 1. FROM PATIENTS TABLE (PRIMARY SOURCE) ==========
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
                        'patient_code' => $patient['patient_code'] ?? 'N/A',
                        'full_name' => $patient['full_name'] ?? 'Unknown',
                        'email' => $patient['email'] ?? '',
                        'phone' => $patient['phone'] ?? '',
                        'age' => $patient['age'] ?? '',
                        'gender' => $patient['gender'] ?? '',
                        'source' => ucfirst($patient['source'] ?? 'Unknown'),
                        'last_visit' => $patient['created_at'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Patients - Patients table error: ' . $e->getMessage());
        }

        // ========== 2. FROM APPOINTMENTS (APPROVED / COMPLETED ONLY) ==========
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
                        'patient_code' => 'N/A',
                        'full_name' => $patient['full_name'] ?? 'Unknown',
                        'email' => $patient['email'] ?? '',
                        'phone' => $patient['phone'] ?? '',
                        'age' => $patient['age'] ?? '',
                        'gender' => $patient['gender'] ?? '',
                        'source' => 'Online',
                        'last_visit' => $patient['last_visit'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Patients - Appointments error: ' . $e->getMessage());
        }

        // ========== 3. FROM LAB REQUESTS (NON-CANCELLED) ==========
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
                        'patient_code' => 'N/A',
                        'full_name' => $patient['full_name'] ?? 'Unknown',
                        'email' => '',
                        'phone' => '',
                        'age' => $patient['age'] ?? '',
                        'gender' => $patient['gender'] ?? '',
                        'source' => 'Walk-in (Lab)',
                        'last_visit' => $patient['last_visit'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Patients - Lab Requests error: ' . $e->getMessage());
        }

        // ========== 4. FROM X-RAY EXAMINATIONS (NON-CANCELLED) ==========
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
                        'patient_code' => 'N/A',
                        'full_name' => $patient['full_name'] ?? 'Unknown',
                        'email' => '',
                        'phone' => '',
                        'age' => $patient['age'] ?? '',
                        'gender' => $patient['gender'] ?? '',
                        'source' => 'Walk-in (X-Ray)',
                        'last_visit' => $patient['last_visit'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Patients - X-Ray error: ' . $e->getMessage());
        }

        // Sort by last_visit (newest first)
        usort($allPatients, function ($a, $b) {
            return strtotime($b['last_visit'] ?? '0') - strtotime($a['last_visit'] ?? '0');
        });

        return $allPatients;
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

        return view('Receptionist/appointment_view', $data);
    }

    // =============================================
    // APPROVE APPOINTMENT - CREATE DIAGNOSTIC REQUESTS
    // =============================================
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

        // Update appointment status to approved
        $model->update($id, [
            'status'       => 'approved',
            'arrival_time' => date('Y-m-d H:i:s')
        ]);

        // ===== CREATE/UPDATE PATIENT RECORD =====
        $patientModel = new PatientModel();
        $patient = $patientModel->findOrCreateFromAppointment($appointment);

        // ===== DECODE SERVICES FROM APPOINTMENT =====
        $labServices = json_decode($appointment['lab_services'], true) ?? [];
        $xrayServices = json_decode($appointment['xray_services'], true) ?? [];
        $successMessages = [];

        $diagnosticModel = new DiagnosticRequestModel();

        // ===== CREATE LAB REQUEST IF LAB SERVICES EXIST =====
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

        // ===== CREATE X-RAY REQUEST IF X-RAY SERVICES EXIST =====
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

        // Build success message
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

    // =============================================
    // PATIENTS - SHOW ALL PATIENTS (Online + Walk-in)
    // =============================================
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

    // =============================================
    // MANUAL SYNC - Create lab requests for existing approved appointments
    // =============================================
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

        // Also sync X-Ray for approved appointments
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

    // =============================================
    // DIAGNOSTIC REQUESTS - WALK-IN PATIENTS
    // =============================================
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

    /**
     * Get combined lab and x-ray requests from the merged table.
     *
     * The model's row decoration means each row already carries both
     * the new column names (services, request_date) and the legacy
     * aliases (lab_services, exam_type, exam_date), so this method
     * reads whichever name is convenient without needing two queries.
     */
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

    /**
     * Count requests by status
     */
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

    // =============================================
    // CREATE WALK-IN DIAGNOSTIC REQUEST
    // =============================================
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
                'appointment_id'   => 0,
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

    // =============================================
    // UPDATE DIAGNOSTIC REQUEST STATUS - WITH PAYMENT
    // =============================================
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

            // ===== SPECIAL: When status changes to "in_progress" (Start Processing) =====
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

            // ===== Handle other status updates =====
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

    // =============================================
    // DELETE DIAGNOSTIC REQUEST
    // =============================================
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

    // =============================================
    // GET REQUEST DETAILS FOR VIEW
    // =============================================
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

    // =============================================
    // PRINT REQUEST
    // =============================================
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

    // =============================================
    // SYNC WALK-IN PATIENTS TO PATIENTS TABLE
    // =============================================
    public function syncWalkInPatients()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $patientModel = new PatientModel();
        $count = $patientModel->syncWalkInPatients();

        return redirect()->to(base_url('receptionist/patients'))
                        ->with('success', "Synced {$count} walk-in patients to the patients table.");
    }

    // =============================================
    // DASHBOARD DATA - WEEKLY APPOINTMENTS
    // =============================================
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

    // =============================================
    // DASHBOARD DATA - SERVICE DISTRIBUTION
    // =============================================
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

    // =============================================
    // API: GET DASHBOARD DATA (AJAX)
    // =============================================
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

    // =============================================
    // GET PAYMENT DETAILS
    // =============================================
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

    // =============================================
    // PRINT RECEIPT
    // =============================================
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

    // =============================================
    // REFUND PAYMENT
    // =============================================
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