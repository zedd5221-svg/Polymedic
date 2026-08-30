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
        $successMessages = ['Patient record created/updated successfully!'];
        
        if (!empty($labServices)) {
            $labRequestModel = new LabRequestModel();
            $existing = $labRequestModel->where('appointment_id', $id)->first();
            
            if (!$existing) {
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
                    'request_date' => date('Y-m-d'),
                    'status' => 'pending'
                ];
                
                $labRequestModel->insert($labData);
                $labRequestId = $labRequestModel->getInsertID();
                
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
        
        if (!empty($xrayServices)) {
            $xrayModel = new XrayExaminationModel();
            $existing = $xrayModel->where('appointment_id', $id)->first();
            
            if (!$existing) {
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
                    'exam_date' => date('Y-m-d'),
                    'status' => 'pending'
                ];
                
                $xrayModel->insert($xrayData);
                $xrayId = $xrayModel->getInsertID();
                
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

    public function patients()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $patientModel = new PatientModel();
        $labRequestModel = new LabRequestModel();
        $xrayModel = new XrayExaminationModel();
        
        $patients = $patientModel->orderBy('created_at', 'DESC')->findAll();
        
        $allPatients = [];
        
        foreach ($patients as $patient) {
            $sourceDisplay = ucfirst($patient['source'] ?? 'Unknown');
            
            if (($patient['source'] ?? '') === 'walk-in') {
                $hasLab = $labRequestModel
                    ->where('patient_name', $patient['full_name'])
                    ->where('appointment_id', 0)
                    ->countAllResults() > 0;
                
                $hasXray = $xrayModel
                    ->where('patient_name', $patient['full_name'])
                    ->where('appointment_id', 0)
                    ->countAllResults() > 0;
                
                if ($hasLab && $hasXray) {
                    $sourceDisplay = 'Walk-in (Lab & X-Ray)';
                } elseif ($hasLab) {
                    $sourceDisplay = 'Walk-in (Lab)';
                } elseif ($hasXray) {
                    $sourceDisplay = 'Walk-in (X-Ray)';
                } else {
                    $sourceDisplay = 'Walk-in';
                }
            }
            
            $allPatients[] = [
                'patient_code' => $patient['patient_code'] ?? 'N/A',
                'full_name' => $patient['full_name'] ?? 'Unknown',
                'email' => $patient['email'] ?? '',
                'phone' => $patient['phone'] ?? '',
                'age' => $patient['age'] ?? '',
                'gender' => $patient['gender'] ?? '',
                'source' => $sourceDisplay,
                'last_visit' => $patient['created_at'] ?? null,
            ];
        }
        
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
    
    public function syncLabRequests()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $appointmentModel = new AppointmentModel();
        $labRequestModel = new LabRequestModel();
        $xrayModel = new XrayExaminationModel();
        $patientModel = new PatientModel();
        
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
            
            $existing = $labRequestModel->where('appointment_id', $appointment['id'])->first();
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

    public function diagnosticRequests()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $labRequestModel = new LabRequestModel();
        $xrayModel = new XrayExaminationModel();
        $serviceModel = new ServiceModel();
        
        $data['requests'] = $this->getCombinedRequests($labRequestModel, $xrayModel);
        
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
            $priority = 'routine';
            if (strpos(strtolower($lab['lab_services'] ?? ''), 'stat') !== false) {
                $priority = 'stat';
            }
            
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

    // =============================================
    // CREATE WALK-IN DIAGNOSTIC REQUEST - FIXED
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
        $cleanedServices = array_map('trim', array_filter($services));
        
        // ===== CREATE/UPDATE PATIENT RECORD =====
        $patientModel = new PatientModel();
        $patientCode = 'N/A';
        
        try {
            $patientData = [
                'full_name' => $patientName,
                'age'       => $age,
                'gender'    => $gender,
                'email'     => $email,
                'phone'     => $phone
            ];
            
            $patient = $patientModel->findOrCreateFromWalkin($patientData);
            if ($patient) {
                $patientCode = $patient['patient_code'];
                log_message('info', "Diagnostic request patient: $patientName (Code: $patientCode)");
            } else {
                // FALLBACK: direct insert with unique code (emergency)
                log_message('error', "Model failed to create patient, attempting direct fallback");
                $db = \Config\Database::connect();
                $maxAttempts = 3;
                for ($i = 0; $i < $maxAttempts; $i++) {
                    $newCode = $patientModel->generatePatientCode();
                    $insertData = [
                        'patient_code' => $newCode,
                        'full_name'    => $patientName,
                        'email'        => $email ?: null,
                        'phone'        => $phone ?: null,
                        'age'          => $age,
                        'gender'       => $gender,
                        'source'       => 'walk-in',
                        'created_at'   => date('Y-m-d H:i:s'),
                        'updated_at'   => date('Y-m-d H:i:s')
                    ];
                    try {
                        $db->table('patients')->insert($insertData);
                        $id = $db->insertID();
                        if ($id) {
                            $patientCode = $newCode;
                            log_message('info', "Fallback insert succeeded: $patientName (Code: $newCode, ID: $id)");
                            break;
                        }
                    } catch (\Exception $e) {
                        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                            log_message('warning', "Fallback duplicate code $newCode, retry");
                            continue;
                        }
                        log_message('error', "Fallback insert error: " . $e->getMessage());
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Patient creation error: ' . $e->getMessage());
        }
        
        // Create lab or x-ray request
        if ($requestType === 'lab') {
            $labRequestModel = new LabRequestModel();
            $data = [
                'appointment_id' => 0,
                'patient_name'   => $patientName,
                'age'            => $age,
                'gender'         => $gender,
                'email'          => $email,
                'phone'          => $phone,
                'lab_services'   => implode(', ', $cleanedServices),
                'request_date'   => date('Y-m-d'),
                'doctor_name'    => $doctorName,
                'status'         => 'pending',
                'created_at'     => date('Y-m-d H:i:s')
            ];
            $labRequestModel->insert($data);
            $requestId = $labRequestModel->getInsertID();
            
            NotificationModel::notify(
                'lab',
                'New Lab Request (Walk-in)',
                'New lab request for walk-in patient ' . $patientName,
                $requestId,
                '/polymedic/public/medtech/request/view/' . $requestId
            );
            
            return redirect()->to(base_url('receptionist/diagnostic-requests'))
                            ->with('success', "Lab request created for $patientName! Patient Code: $patientCode");
                            
        } elseif ($requestType === 'xray') {
            $xrayModel = new XrayExaminationModel();
            $data = [
                'appointment_id' => 0,
                'patient_name'   => $patientName,
                'age'            => $age,
                'gender'         => $gender,
                'email'          => $email,
                'phone'          => $phone,
                'exam_type'      => implode(', ', $cleanedServices),
                'exam_date'      => date('Y-m-d'),
                'doctor_name'    => $doctorName,
                'priority'       => $priority,
                'status'         => 'pending',
                'created_at'     => date('Y-m-d H:i:s')
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
                            ->with('success', "X-Ray request created for $patientName! Patient Code: $patientCode");
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
        
        try {
            $patientModel = new PatientModel();
            $count = $patientModel->syncWalkInPatients();
            
            if ($count > 0) {
                return redirect()->to(base_url('receptionist/debug-patients'))
                                ->with('success', "Synced {$count} new walk-in patients to the patients table.");
            } else {
                return redirect()->to(base_url('receptionist/debug-patients'))
                                ->with('info', "No new walk-in patients to sync. All patients already exist.");
            }
        } catch (\Exception $e) {
            log_message('error', 'Sync walk-in patients error: ' . $e->getMessage());
            return redirect()->to(base_url('receptionist/debug-patients'))
                            ->with('error', 'Error syncing patients: ' . $e->getMessage());
        }
    }

    // =============================================
    // VERIFY SYNC - Check what patients should be synced
    // =============================================
    public function verifySync()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $db = \Config\Database::connect();
        $patientModel = new PatientModel();
        
        echo "<pre>";
        echo "=== VERIFYING SYNC - PATIENTS TO BE ADDED ===\n\n";
        
        // Get all lab request patients
        $labPatients = $db->query("
            SELECT DISTINCT 
                lr.patient_name as full_name, 
                lr.age, 
                lr.gender,
                lr.email,
                lr.phone,
                MIN(lr.created_at) as created_at
            FROM lab_requests lr
            WHERE lr.appointment_id = 0
            AND lr.patient_name IS NOT NULL 
            AND lr.patient_name != ''
            GROUP BY lr.patient_name, lr.age, lr.gender, lr.email, lr.phone
        ")->getResultArray();
        
        echo "=== LAB REQUESTS (WALK-IN) - " . count($labPatients) . " total ===\n";
        echo str_repeat('-', 80) . "\n";
        
        foreach ($labPatients as $p) {
            $existing = $patientModel->where('full_name', $p['full_name'])
                                     ->where('age', $p['age'])
                                     ->first();
            
            $lastName = '';
            $parts = explode(' ', trim($p['full_name']));
            if (!empty($parts)) {
                $lastName = end($parts);
            }
            
            $existingByLastName = null;
            if (!empty($lastName)) {
                $existingByLastName = $patientModel->like('full_name', $lastName, 'both')
                                                  ->where('age', $p['age'])
                                                  ->first();
            }
            
            $status = "ALREADY EXISTS";
            if (!$existing && !$existingByLastName) {
                $status = "✓ NEW - WILL BE CREATED";
            } elseif (!$existing && $existingByLastName) {
                $status = "⚠ FOUND BY LAST NAME ONLY: " . $existingByLastName['full_name'];
            }
            
            echo $p['full_name'] . " (Age: " . $p['age'] . ") | " . $status . "\n";
        }
        
        echo "\n=== X-RAY EXAMINATIONS (WALK-IN) ===\n";
        echo str_repeat('-', 80) . "\n";
        
        $xrayPatients = $db->query("
            SELECT DISTINCT 
                xe.patient_name as full_name, 
                xe.age, 
                xe.gender,
                xe.email,
                xe.phone,
                MIN(xe.created_at) as created_at
            FROM xray_examinations xe
            WHERE xe.appointment_id = 0
            AND xe.patient_name IS NOT NULL 
            AND xe.patient_name != ''
            GROUP BY xe.patient_name, xe.age, xe.gender, xe.email, xe.phone
        ")->getResultArray();
        
        foreach ($xrayPatients as $p) {
            $existing = $patientModel->where('full_name', $p['full_name'])
                                     ->where('age', $p['age'])
                                     ->first();
            
            $lastName = '';
            $parts = explode(' ', trim($p['full_name']));
            if (!empty($parts)) {
                $lastName = end($parts);
            }
            
            $existingByLastName = null;
            if (!empty($lastName)) {
                $existingByLastName = $patientModel->like('full_name', $lastName, 'both')
                                                  ->where('age', $p['age'])
                                                  ->first();
            }
            
            $status = "ALREADY EXISTS";
            if (!$existing && !$existingByLastName) {
                $status = "✓ NEW - WILL BE CREATED";
            } elseif (!$existing && $existingByLastName) {
                $status = "⚠ FOUND BY LAST NAME ONLY: " . $existingByLastName['full_name'];
            }
            
            echo $p['full_name'] . " (Age: " . $p['age'] . ") | " . $status . "\n";
        }
        
        echo "\n</pre>";
        die();
    }

    // =============================================
    // DEBUG - Check patient creation
    // =============================================
    public function debugPatients()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $patientModel = new PatientModel();
        $patients = $patientModel->orderBy('id', 'DESC')->limit(20)->findAll();
        
        echo "<pre>";
        echo "=== PATIENTS TABLE (LAST 20) ===\n";
        echo str_repeat('=', 130) . "\n";
        printf("%-5s | %-15s | %-30s | %-12s | %-8s | %-20s\n", "ID", "Code", "Name", "Source", "Age", "Created");
        echo str_repeat('-', 130) . "\n";
        
        foreach ($patients as $p) {
            $name = substr($p['full_name'] ?? 'Unknown', 0, 29);
            $code = $p['patient_code'] ?? 'N/A';
            $source = $p['source'] ?? 'Unknown';
            $age = $p['age'] ?? 'N/A';
            $created = date('Y-m-d H:i:s', strtotime($p['created_at'] ?? 'now'));
            printf("%-5s | %-15s | %-30s | %-12s | %-8s | %-20s\n", 
                $p['id'], $code, $name, $source, $age, $created);
        }
        
        echo str_repeat('-', 130) . "\n";
        echo "TOTAL PATIENTS IN DATABASE: " . $patientModel->countAll() . "\n\n";
        
        $db = \Config\Database::connect();
        
        $totalLabWalkins = $db->table('lab_requests')
            ->where('appointment_id', 0)
            ->countAllResults();
        
        echo "TOTAL LAB REQUESTS (WALK-IN): " . $totalLabWalkins . "\n";
        
        $labWalkins = $db->table('lab_requests')
            ->where('appointment_id', 0)
            ->orderBy('id', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();
        
        echo "\n=== LAB REQUESTS (WALK-IN) - LAST 5 ===\n";
        echo str_repeat('-', 80) . "\n";
        printf("%-5s | %-25s | %-10s | %-20s\n", "ID", "Name", "Age", "Created");
        echo str_repeat('-', 80) . "\n";
        
        foreach ($labWalkins as $lab) {
            $name = substr($lab['patient_name'] ?? 'Unknown', 0, 24);
            $age = $lab['age'] ?? 'N/A';
            $created = date('Y-m-d H:i:s', strtotime($lab['created_at'] ?? 'now'));
            printf("%-5s | %-25s | %-10s | %-20s\n", $lab['id'], $name, $age, $created);
        }
        
        echo "\n";
        
        $totalXrayWalkins = $db->table('xray_examinations')
            ->where('appointment_id', 0)
            ->countAllResults();
        
        echo "TOTAL X-RAY EXAMINATIONS (WALK-IN): " . $totalXrayWalkins . "\n";
        
        $xrayWalkins = $db->table('xray_examinations')
            ->where('appointment_id', 0)
            ->orderBy('id', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();
        
        echo "\n=== X-RAY EXAMINATIONS (WALK-IN) - LAST 5 ===\n";
        echo str_repeat('-', 80) . "\n";
        printf("%-5s | %-25s | %-10s | %-20s\n", "ID", "Name", "Age", "Created");
        echo str_repeat('-', 80) . "\n";
        
        foreach ($xrayWalkins as $xray) {
            $name = substr($xray['patient_name'] ?? 'Unknown', 0, 24);
            $age = $xray['age'] ?? 'N/A';
            $created = date('Y-m-d H:i:s', strtotime($xray['created_at'] ?? 'now'));
            printf("%-5s | %-25s | %-10s | %-20s\n", $xray['id'], $name, $age, $created);
        }
        
        echo "\n" . str_repeat('=', 130) . "\n";
        echo "To sync missing patients: Go to /polymedic/public/receptionist/sync-walk-in-patients\n";
        echo "To verify what will be synced: Go to /polymedic/public/receptionist/verify-sync\n";
        echo "</pre>";
        die();
    }

    public function printRequest($id, $type)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        try {
            if ($type === 'lab') {
                $model = new LabRequestModel();
                $data['title'] = 'Laboratory Request';
            } elseif ($type === 'xray') {
                $model = new XrayExaminationModel();
                $data['title'] = 'X-Ray Request';
            } else {
                return redirect()->back()->with('error', 'Invalid request type');
            }
            
            $request = $model->find($id);
            if (!$request) {
                return redirect()->back()->with('error', 'Request not found');
            }
            
            $data['request'] = $request;
            $data['type'] = $type;
            
            return view('Receptionist/print_request', $data);
            
        } catch (\Exception $e) {
            log_message('error', 'Print request error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error generating print view');
        }
    }
}