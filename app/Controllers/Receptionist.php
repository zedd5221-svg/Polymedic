<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\LabRequestModel;
use App\Models\XrayExaminationModel;
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
        
        // Get today's date
        $today = date('Y-m-d');
        
        // Get today's appointments
        $data['today_appointments'] = $appointmentModel
            ->where('appointment_date', $today)
            ->orderBy('appointment_time', 'ASC')
            ->findAll();
        
        // Get pending online appointments (all pending, not just today)
        $data['pending_appointments'] = $appointmentModel
            ->where('status', 'pending')
            ->countAllResults();
        
        // Get today's completed appointments
        $data['today_completed'] = $appointmentModel
            ->where('appointment_date', $today)
            ->where('status', 'completed')
            ->countAllResults();
        
        // Get pending diagnostic requests (approved appointments)
        $data['pending_diagnostic'] = $appointmentModel
            ->where('status', 'approved')
            ->countAllResults();
        
        // Get unpaid bills (approved appointments)
        $data['unpaid_bills'] = $appointmentModel
            ->where('status', 'approved')
            ->countAllResults();
        
        // Get today's collections from payments table
        $todayPayments = $paymentModel
            ->where('DATE(payment_date)', $today)
            ->where('payment_status', 'paid')
            ->findAll();
        
        $todayCollections = 0;
        foreach ($todayPayments as $payment) {
            $todayCollections += floatval($payment['total_amount']);
        }
        $data['today_collections'] = $todayCollections;

        // Get total patients (same deduped count as the Patients page)
        $data['total_patients'] = count($this->getAllPatients());
        
        // Get weekly appointment data for chart
        $data['weekly_appointments'] = $this->getWeeklyAppointmentData();
        
        // Get service distribution for today
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
        $labRequestModel  = new LabRequestModel();
        $xrayModel        = new XrayExaminationModel();
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

        // ========== 2. FROM APPOINTMENTS (ONLINE - if not in patients table) ==========
        try {
            $appointmentPatients = $appointmentModel
                ->select('full_name, email, phone, age, gender, MAX(appointment_date) as last_visit')
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

        // ========== 3. FROM LAB REQUESTS (WALK-IN - if not in patients table) ==========
        try {
            $labPatients = $labRequestModel
                ->select('patient_name as full_name, age, gender, MAX(request_date) as last_visit')
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

        // ========== 4. FROM X-RAY EXAMINATIONS (WALK-IN - if not in patients table) ==========
        try {
            $xrayPatients = $xrayModel
                ->select('patient_name as full_name, age, gender, MAX(exam_date) as last_visit')
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
            
            // Check for late appointments if method exists
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
        
        // ===== CREATE LAB REQUEST IF LAB SERVICES EXIST =====
        if (!empty($labServices)) {
            $labRequestModel = new LabRequestModel();
            
            // Check if lab request already exists for this appointment
            $existing = $labRequestModel->where('appointment_id', $id)->first();
            
            if ($existing) {
                $successMessages[] = 'Lab request already exists.';
            } else {
                // Clean lab services - remove JSON escape characters
                $cleanedServices = array_map(function($service) {
                    $service = str_replace('\/', '/', $service);
                    $service = str_replace('\\/', '/', $service);
                    $service = stripslashes($service);
                    return trim($service);
                }, $labServices);
                
                // Create lab request
                $labData = [
                    'appointment_id' => $appointment['id'],
                    'patient_name' => $appointment['full_name'],
                    'age' => $appointment['age'],
                    'gender' => $appointment['gender'],
                    'email' => $appointment['email'] ?? null,
                    'phone' => $appointment['phone'] ?? null,
                    'lab_services' => implode(', ', $cleanedServices),
                    'request_date' => date('Y-m-d'),
                    'status' => 'pending'
                ];
                
                $labRequestModel->insert($labData);
                $labRequestId = $labRequestModel->getInsertID();
                
                // Create notification for MedTech
                NotificationModel::notify(
                    'lab',
                    'New Lab Request (Online Booking)',
                    'New lab request for online patient ' . $appointment['full_name'],
                    $labRequestId,
                    '/polymedic/public/medtech/request/view/' . $labRequestId
                );
                
                $successMessages[] = 'Lab request created successfully!';
                log_message('info', 'Lab request created for appointment #' . $id . ' (Patient: ' . $appointment['full_name'] . ')');
            }
        }
        
        // ===== CREATE X-RAY REQUEST IF X-RAY SERVICES EXIST =====
        if (!empty($xrayServices)) {
            $xrayModel = new XrayExaminationModel();
            
            // Check if x-ray examination already exists
            $existing = $xrayModel->where('appointment_id', $id)->first();
            
            if ($existing) {
                $successMessages[] = 'X-Ray request already exists.';
            } else {
                // Clean X-Ray services
                $cleanedServices = array_map(function($service) {
                    $service = str_replace('\/', '/', $service);
                    $service = str_replace('\\/', '/', $service);
                    $service = stripslashes($service);
                    return trim($service);
                }, $xrayServices);
                
                // Create x-ray examination
                $xrayData = [
                    'appointment_id' => $appointment['id'],
                    'patient_name' => $appointment['full_name'],
                    'age' => $appointment['age'],
                    'gender' => $appointment['gender'],
                    'email' => $appointment['email'] ?? null,
                    'phone' => $appointment['phone'] ?? null,
                    'exam_type' => implode(', ', $cleanedServices),
                    'exam_date' => date('Y-m-d'),
                    'status' => 'pending'
                ];
                
                $xrayModel->insert($xrayData);
                $xrayId = $xrayModel->getInsertID();
                
                // Create notification for Radiologist
                NotificationModel::notify(
                    'xray',
                    'New X-Ray Request (Online Booking)',
                    'New X-Ray request for online patient ' . $appointment['full_name'],
                    $xrayId,
                    '/polymedic/public/radiologist/examination/view/' . $xrayId
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
        
        // If no services were selected, add a note
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
        
        // Load payment data
        $paymentModel = new PaymentModel();
        $payments = $paymentModel->orderBy('payment_date', 'DESC')->findAll();
        
        // Calculate stats
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
        
        // Get unique patients count
        $uniquePatients = [];
        foreach ($payments as $p) {
            if (!in_array($p['patient_name'], $uniquePatients)) {
                $uniquePatients[] = $p['patient_name'];
            }
        }

        // Attach each patient's gender so the view can render a
        // male/female avatar. Matched by patient_code, which is the
        // unique key on the patients table. Payments whose
        // patient_code is missing or does not match a patient row
        // get a null gender, and the view falls back to initials.
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
        $labRequestModel = new LabRequestModel();
        $xrayModel = new XrayExaminationModel();
        $patientModel = new PatientModel();
        
        // Get all approved appointments with lab services that don't have lab requests
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
            // Create patient if doesn't exist
            $patient = $patientModel->findOrCreateFromAppointment($appointment);
            if ($patient) {
                $patientsCreated++;
            }
            
            // Check if lab request already exists
            $existing = $labRequestModel->where('appointment_id', $appointment['id'])->first();
            if ($existing) {
                $skipped++;
                continue;
            }
            
            $labServices = json_decode($appointment['lab_services'], true) ?? [];
            
            if (!empty($labServices)) {
                // Clean lab services
                $cleanedServices = array_map(function($service) {
                    $service = str_replace('\/', '/', $service);
                    $service = str_replace('\\/', '/', $service);
                    $service = stripslashes($service);
                    return trim($service);
                }, $labServices);
                
                $labData = [
                    'appointment_id' => $appointment['id'],
                    'patient_name' => $appointment['full_name'],
                    'age' => $appointment['age'],
                    'gender' => $appointment['gender'],
                    'lab_services' => implode(', ', $cleanedServices),
                    'request_date' => $appointment['appointment_date'] ?? date('Y-m-d'),
                    'status' => 'pending'
                ];
                $labRequestModel->insert($labData);
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
            $existing = $xrayModel->where('appointment_id', $appointment['id'])->first();
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
                
                $xrayData = [
                    'appointment_id' => $appointment['id'],
                    'patient_name' => $appointment['full_name'],
                    'age' => $appointment['age'],
                    'gender' => $appointment['gender'],
                    'exam_type' => implode(', ', $cleanedServices),
                    'exam_date' => $appointment['appointment_date'] ?? date('Y-m-d'),
                    'status' => 'pending'
                ];
                $xrayModel->insert($xrayData);
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

        $labRequestModel = new LabRequestModel();
        $xrayModel = new XrayExaminationModel();
        $serviceModel = new ServiceModel();
        
        // Get all diagnostic requests (lab + x-ray combined)
        $data['requests'] = $this->getCombinedRequests($labRequestModel, $xrayModel);
        
        // Get counts for filters
        $data['counts'] = [
            'total' => count($data['requests']),
            'pending' => $this->countRequestsByStatus($data['requests'], 'pending'),
            'processing' => $this->countRequestsByStatus($data['requests'], 'in_progress'),
            'completed' => $this->countRequestsByStatus($data['requests'], 'completed'),
            'released' => $this->countRequestsByStatus($data['requests'], 'released'),
            'cancelled' => $this->countRequestsByStatus($data['requests'], 'cancelled')
        ];
        
        // Get services for the create modal
        $data['labServices'] = $serviceModel->getServicesByCategory('laboratory');
        $data['xrayServices'] = $serviceModel->getServicesByCategory('xray');
        
        return view('Receptionist/diagnostic_requests', $data);
    }

    /**
     * Get combined lab and x-ray requests
     */
    private function getCombinedRequests($labRequestModel, $xrayModel)
    {
        $labRequests = $labRequestModel
            ->orderBy('created_at', 'DESC')
            ->findAll();
        
        $xrayRequests = $xrayModel
            ->orderBy('created_at', 'DESC')
            ->findAll();
        
        $combined = [];
        
        foreach ($labRequests as $lab) {
            // Determine priority
            $priority = 'routine';
            if (strpos(strtolower($lab['lab_services'] ?? ''), 'stat') !== false) {
                $priority = 'stat';
            }
            
            // Get service names as array
            $services = explode(', ', $lab['lab_services'] ?? '');
            $services = array_filter($services, function($s) { return !empty(trim($s)); });
            
            $combined[] = [
                'id' => $lab['id'],
                'type' => 'lab',
                'reference' => 'LAB-' . date('y') . '-' . str_pad($lab['id'], 4, '0', STR_PAD_LEFT),
                'patient_name' => $lab['patient_name'] ?? 'Unknown',
                'patient_age' => $lab['age'] ?? 'N/A',
                'patient_gender' => $lab['gender'] ?? 'N/A',
                'email' => $lab['email'] ?? '',
                'phone' => $lab['phone'] ?? '',
                'services' => $services,
                'services_display' => implode(', ', array_slice($services, 0, 3)) . (count($services) > 3 ? ' +' . (count($services) - 3) : ''),
                'status' => $lab['status'] ?? 'pending',
                'priority' => $priority,
                'doctor_name' => $lab['doctor_name'] ?? 'Dr. Ana Cruz',
                'created_at' => $lab['created_at'],
                'request_type' => 'Laboratory',
                'source' => $lab['appointment_id'] > 0 ? 'Online' : 'Walk-in'
            ];
        }
        
        foreach ($xrayRequests as $xray) {
            $priority = $xray['priority'] ?? 'routine';
            
            $services = explode(', ', $xray['exam_type'] ?? '');
            $services = array_filter($services, function($s) { return !empty(trim($s)); });
            
            $combined[] = [
                'id' => $xray['id'],
                'type' => 'xray',
                'reference' => 'XRAY-' . date('y') . '-' . str_pad($xray['id'], 4, '0', STR_PAD_LEFT),
                'patient_name' => $xray['patient_name'] ?? 'Unknown',
                'patient_age' => $xray['age'] ?? 'N/A',
                'patient_gender' => $xray['gender'] ?? 'N/A',
                'email' => $xray['email'] ?? '',
                'phone' => $xray['phone'] ?? '',
                'services' => $services,
                'services_display' => implode(', ', array_slice($services, 0, 3)) . (count($services) > 3 ? ' +' . (count($services) - 3) : ''),
                'status' => $xray['status'] ?? 'pending',
                'priority' => $priority,
                'doctor_name' => $xray['doctor_name'] ?? 'Dr. Ana Cruz',
                'created_at' => $xray['created_at'],
                'request_type' => 'X-Ray',
                'source' => $xray['appointment_id'] > 0 ? 'Online' : 'Walk-in'
            ];
        }
        
        // Sort by created_at descending (newest first)
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
    // CREATE WALK-IN DIAGNOSTIC REQUEST - COMPLETELY FIXED
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
        
        // Validation
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
        
        // Clean services
        $cleanedServices = array_map(function($service) {
            return trim($service);
        }, array_filter($services));
        
        // ===== CREATE/UPDATE PATIENT RECORD - FIXED: Phone is NOT the only criteria =====
        $patientModel = new PatientModel();
        $patient = null;
        $patientCode = 'N/A';
        
        try {
            // Log the attempt
            log_message('debug', 'Creating patient: ' . $patientName . ', Age: ' . $age . ', Gender: ' . $gender . ', Phone: ' . $phone);
            
            // STEP 1: Try to find by email (most reliable)
            $existingPatient = null;
            if (!empty($email)) {
                $existingPatient = $patientModel->where('email', $email)->first();
                if ($existingPatient) {
                    log_message('debug', 'Found existing patient by email: ' . $email);
                }
            }
            
            // STEP 2: Try by name + age + gender (more reliable than phone alone)
            if (!$existingPatient) {
                $existingPatient = $patientModel->where('full_name', $patientName)
                                                ->where('age', $age)
                                                ->where('gender', $gender)
                                                ->first();
                if ($existingPatient) {
                    log_message('debug', 'Found existing patient by name/age/gender');
                }
            }
            
            // STEP 3: ONLY try by phone if we haven't found a patient yet
            // AND we have a phone number
            if (!$existingPatient && !empty($phone)) {
                $existingPatient = $patientModel->where('phone', $phone)->first();
                if ($existingPatient) {
                    log_message('debug', 'Found existing patient by phone: ' . $phone);
                    
                    // Check if the found patient has the same name/age
                    // If not, this is a DIFFERENT person with the same phone number
                    // We should create a new patient
                    if ($existingPatient['full_name'] !== $patientName || 
                        $existingPatient['age'] != $age || 
                        $existingPatient['gender'] !== $gender) {
                        
                        log_message('debug', 'Phone matches but name/age/gender are different. Creating new patient...');
                        $existingPatient = null; // Reset to create new patient
                    }
                }
            }
            
            if ($existingPatient) {
                // Update existing patient info
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
                // Generate new patient code
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
                
                // Insert with error handling
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
                    
                    // Check if it's a duplicate key error
                    if (strpos($insertException->getMessage(), 'Duplicate entry') !== false) {
                        log_message('debug', 'Duplicate entry detected, trying to find existing patient...');
                        // Try to find the patient that was just created by another process
                        $existing = $patientModel->where('full_name', $patientName)
                                                ->where('age', $age)
                                                ->where('gender', $gender)
                                                ->first();
                        if ($existing) {
                            $patient = $existing;
                            $patientCode = $patient['patient_code'] ?? 'N/A';
                            log_message('debug', 'Found existing patient after duplicate error: ' . $patientCode);
                        } else {
                            // Try with a fallback code
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
            // Continue anyway to create the lab request
        }
        
        // Create lab or x-ray request
        if ($requestType === 'lab') {
            $labRequestModel = new LabRequestModel();
            
            $data = [
                'appointment_id' => 0,
                'patient_name' => $patientName,
                'age' => $age,
                'gender' => $gender,
                'email' => $email ?: null,
                'phone' => $phone ?: null,
                'lab_services' => implode(', ', $cleanedServices),
                'request_date' => date('Y-m-d'),
                'doctor_name' => $doctorName,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $labRequestModel->insert($data);
            $requestId = $labRequestModel->getInsertID();
            
            // Create notification for MedTech
            NotificationModel::notify(
                'lab',
                'New Lab Request (Walk-in)',
                'New lab request for walk-in patient ' . $patientName,
                $requestId,
                '/polymedic/public/medtech/request/view/' . $requestId
            );
            
            return redirect()->to(base_url('receptionist/diagnostic-requests'))
                            ->with('success', 'Lab request created successfully for ' . $patientName . '! Patient Code: ' . $patientCode);
                            
        } elseif ($requestType === 'xray') {
            $xrayModel = new XrayExaminationModel();
            
            $data = [
                'appointment_id' => 0,
                'patient_name' => $patientName,
                'age' => $age,
                'gender' => $gender,
                'email' => $email ?: null,
                'phone' => $phone ?: null,
                'exam_type' => implode(', ', $cleanedServices),
                'exam_date' => date('Y-m-d'),
                'doctor_name' => $doctorName,
                'priority' => $priority,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $xrayModel->insert($data);
            $requestId = $xrayModel->getInsertID();
            
            NotificationModel::notify(
                'xray',
                'New X-Ray Request (Walk-in)',
                'New X-Ray request for walk-in patient ' . $patientName,
                $requestId,
                '/polymedic/public/radiologist/examination/view/' . $requestId
            );
            
            return redirect()->to(base_url('receptionist/diagnostic-requests'))
                            ->with('success', 'X-Ray request created successfully for ' . $patientName . '! Patient Code: ' . $patientCode);
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
            // Load models
            $labRequestModel = new LabRequestModel();
            $xrayModel = new XrayExaminationModel();
            $paymentModel = new PaymentModel();
            $serviceModel = new ServiceModel();
            
            // Get the request data
            if ($type === 'lab') {
                $model = $labRequestModel;
                $request = $model->find($id);
                $servicesString = $request['lab_services'] ?? '';
            } elseif ($type === 'xray') {
                $model = $xrayModel;
                $request = $model->find($id);
                $servicesString = $request['exam_type'] ?? '';
            } else {
                return $this->response->setJSON(['success' => false, 'message' => 'Invalid request type']);
            }
            
            if (!$request) {
                return $this->response->setJSON(['success' => false, 'message' => 'Request not found']);
            }
            
            // ===== SPECIAL: When status changes to "in_progress" (Start Processing) =====
            // This is when payment should be recorded
            if ($status === 'in_progress') {
                
                // Check if payment already exists for this request
                $existingPayment = $paymentModel->getByRequest($id, $type);
                
                if ($existingPayment) {
                    // Payment already exists, just update status
                    $model->update($id, ['status' => $status]);
                    
                    return $this->response->setJSON([
                        'success' => true, 
                        'message' => 'Status updated successfully. Payment already recorded.',
                        'payment_recorded' => true,
                        'payment_id' => $existingPayment['id']
                    ]);
                }
                
                // Calculate total amount from services
                $totalAmount = 0;
                $servicesList = [];
                
                if (!empty($servicesString)) {
                    // Get service names from the string
                    $serviceNames = array_map('trim', explode(',', $servicesString));
                    
                    // Fetch charges from services table
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
                
                // If no services found, use default consultation fee
                if ($totalAmount == 0) {
                    $totalAmount = 500.00; // Default consultation fee
                    $servicesList[] = [
                        'name' => 'Consultation Fee',
                        'charge' => 500.00
                    ];
                }
                
                // Get patient code
                $patientCode = 'N/A';
                $patientModel = new PatientModel();
                $patient = $patientModel->where('full_name', $request['patient_name'])->first();
                if ($patient) {
                    $patientCode = $patient['patient_code'] ?? 'N/A';
                }
                
                // Record payment
                $paymentData = [
                    'request_id' => $id,
                    'request_type' => $type,
                    'patient_name' => $request['patient_name'],
                    'patient_code' => $patientCode,
                    'appointment_id' => $request['appointment_id'] ?? null,
                    'services' => json_encode($servicesList),
                    'total_amount' => $totalAmount,
                    'amount_paid' => $totalAmount, // Full payment upfront
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
                
                // Update request status
                $model->update($id, ['status' => $status]);
                
                // Return success with payment info
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
            // Update request status
            $model->update($id, ['status' => $status]);
            
            if ($status === 'released') {
                $model->update($id, ['released_at' => date('Y-m-d H:i:s')]);
            }
            
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
            if ($type === 'lab') {
                $model = new LabRequestModel();
            } elseif ($type === 'xray') {
                $model = new XrayExaminationModel();
            } else {
                return $this->response->setJSON(['success' => false, 'message' => 'Invalid request type']);
            }
            
            $request = $model->find($id);
            if (!$request) {
                return $this->response->setJSON(['success' => false, 'message' => 'Request not found']);
            }
            
            $model->delete($id);
            
            return $this->response->setJSON(['success' => true, 'message' => 'Request deleted successfully']);
            
        } catch (\Exception $e) {
            log_message('error', 'Delete diagnostic request error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error deleting request']);
        }
    }

    // =============================================
    // GET REQUEST DETAILS FOR VIEW - FIXED WITH EMAIL & PHONE
    // =============================================
    public function getRequestDetails($id, $type)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        try {
            if ($type === 'lab') {
                $model = new LabRequestModel();
            } elseif ($type === 'xray') {
                $model = new XrayExaminationModel();
            } else {
                return $this->response->setJSON(['success' => false, 'message' => 'Invalid request type']);
            }
            
            $request = $model->find($id);
            if (!$request) {
                return $this->response->setJSON(['success' => false, 'message' => 'Request not found']);
            }
            
            // Get patient code from patients table
            $patientCode = 'N/A';
            $patientModel = new PatientModel();
            $patient = $patientModel->where('full_name', $request['patient_name'])->first();
            if ($patient) {
                $patientCode = $patient['patient_code'] ?? 'N/A';
            }
            
            // Determine source
            $source = ($request['appointment_id'] ?? 0) > 0 ? 'Online' : 'Walk-in';
            
            // Get priority
            $priority = $request['priority'] ?? 'routine';
            
            // Get updated_at (use created_at if not set)
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
                    'services' => $type === 'lab' ? ($request['lab_services'] ?? '') : ($request['exam_type'] ?? ''),
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
                    'exam_date' => $request['exam_date'] ?? null,
                    'request_date' => $request['request_date'] ?? null
                ]
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Get request details error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error fetching details']);
        }
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
        
        // Get last 7 days
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
            // Derive the bucket from the actual service columns, not
            // from service_type. The booking form writes 'laboratory'
            // into service_type regardless of what the patient picked,
            // so it cannot be trusted.
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
                // Neither column has data. Fall back to the stored
                // service_type, then to "Unspecified".
                $raw  = trim((string) ($appt['service_type'] ?? ''));
                $type = $raw !== '' ? ucfirst(strtolower($raw)) : 'Unspecified';
            }

            $serviceCounts[$type] = ($serviceCounts[$type] ?? 0) + 1;
        }

        // If no data, provide an empty series so the view shows its
        // own "No appointments today" empty state.
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
            
            // Parse services
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