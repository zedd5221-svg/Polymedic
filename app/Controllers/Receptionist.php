<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\LabRequestModel;
use App\Models\XrayExaminationModel;
use App\Models\NotificationModel;
use App\Models\ServiceModel;
use App\Models\PatientModel;

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
        
        // Get today's collections (completed appointments today * demo rate)
        $todayCompleted = $appointmentModel
            ->where('appointment_date', $today)
            ->where('status', 'completed')
            ->countAllResults();
        $data['today_collections'] = $todayCompleted * 500;

        return view('Receptionist/dashboard', $data);
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
    // APPROVE APPOINTMENT - WITH PATIENT CREATION
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
        
        // Update appointment status
        $model->update($id, [
            'status'       => 'approved',
            'arrival_time' => date('Y-m-d H:i:s')
        ]);
        
        // ===== CREATE PATIENT RECORD =====
        $patientModel = new PatientModel();
        $patient = $patientModel->findOrCreateFromAppointment($appointment);
        
        // ===== CREATE LAB REQUEST IF LAB SERVICES EXIST =====
        $labServices = json_decode($appointment['lab_services'], true) ?? [];
        $xrayServices = json_decode($appointment['xray_services'], true) ?? [];
        $successMessages = ['Patient record created/updated successfully!'];
        
        // Check if there are lab services
        if (!empty($labServices)) {
            $labRequestModel = new LabRequestModel();
            
            // Check if lab request already exists for this appointment
            $existing = $labRequestModel->where('appointment_id', $id)->first();
            
            if (!$existing) {
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
                    'lab_services' => implode(', ', $cleanedServices),
                    'request_date' => date('Y-m-d'),
                    'status' => 'pending'
                ];
                
                $labRequestModel->insert($labData);
                $labRequestId = $labRequestModel->getInsertID();
                
                // Create notification for MedTech
                NotificationModel::notify(
                    'lab',
                    'New Lab Request',
                    'New lab request for patient ' . $appointment['full_name'],
                    $labRequestId,
                    '/polymedic/public/medtech/request/view/' . $labRequestId
                );
                
                $successMessages[] = 'Lab request created successfully!';
            } else {
                $successMessages[] = 'Lab request already exists.';
            }
        }
        
        // Check if there are X-Ray services
        if (!empty($xrayServices)) {
            $xrayModel = new XrayExaminationModel();
            
            // Check if x-ray examination already exists
            $existing = $xrayModel->where('appointment_id', $id)->first();
            
            if (!$existing) {
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
                    'exam_type' => implode(', ', $cleanedServices),
                    'exam_date' => date('Y-m-d'),
                    'status' => 'pending'
                ];
                
                $xrayModel->insert($xrayData);
                $xrayId = $xrayModel->getInsertID();
                
                // Create notification for Radiologist
                NotificationModel::notify(
                    'xray',
                    'New X-Ray Request',
                    'New X-Ray request for patient ' . $appointment['full_name'],
                    $xrayId,
                    '/polymedic/public/radiologist/examination/view/' . $xrayId
                );
                
                $successMessages[] = 'X-Ray request created successfully!';
            } else {
                $successMessages[] = 'X-Ray request already exists.';
            }
        }
        
        $message = 'Appointment approved successfully!';
        if (!empty($successMessages)) {
            $message .= ' ' . implode(' ', $successMessages);
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

    // =============================================
    // PATIENTS - SHOW ALL PATIENTS (Online + Walk-in)
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
        usort($allPatients, function($a, $b) {
            return strtotime($b['last_visit'] ?? '0') - strtotime($a['last_visit'] ?? '0');
        });
        
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
        
        return view('Receptionist/payments');
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
    // UPDATE DIAGNOSTIC REQUEST STATUS
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
            if ($type === 'lab') {
                $model = new LabRequestModel();
                $model->update($id, ['status' => $status]);
                
                if ($status === 'released') {
                    $model->update($id, ['released_at' => date('Y-m-d H:i:s')]);
                }
                
            } elseif ($type === 'xray') {
                $model = new XrayExaminationModel();
                $model->update($id, ['status' => $status]);
                
                if ($status === 'released') {
                    $model->update($id, ['released_at' => date('Y-m-d H:i:s')]);
                }
            } else {
                return $this->response->setJSON(['success' => false, 'message' => 'Invalid request type']);
            }
            
            return $this->response->setJSON(['success' => true, 'message' => 'Status updated successfully']);
            
        } catch (\Exception $e) {
            log_message('error', 'Update diagnostic status error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error updating status']);
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
    // GET REQUEST DETAILS FOR VIEW
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
            
            return $this->response->setJSON([
                'success' => true,
                'data' => [
                    'id' => $request['id'],
                    'patient_name' => $request['patient_name'] ?? 'Unknown',
                    'age' => $request['age'] ?? 'N/A',
                    'gender' => $request['gender'] ?? 'N/A',
                    'services' => $type === 'lab' ? ($request['lab_services'] ?? '') : ($request['exam_type'] ?? ''),
                    'status' => $request['status'] ?? 'pending',
                    'doctor_name' => $request['doctor_name'] ?? 'Dr. Ana Cruz',
                    'created_at' => $request['created_at'] ?? date('Y-m-d H:i:s'),
                    'findings' => $request['findings'] ?? null,
                    'remarks' => $request['remarks'] ?? null,
                    'released_at' => $request['released_at'] ?? null
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
}