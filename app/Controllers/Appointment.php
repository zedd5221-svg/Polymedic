<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\ServiceModel;
use App\Models\LabRequestModel;
use App\Models\XrayExaminationModel;
use App\Models\NotificationModel;
use App\Models\PatientModel;

class Appointment extends BaseController
{
    public function index(): string
    {
        return view('Appointment/index');
    }
    
    public function book(): string
    {
        // Get services from database
        $serviceModel = new ServiceModel();
        $data['labServices'] = $serviceModel->getServicesByCategory('laboratory');
        $data['xrayServices'] = $serviceModel->getServicesByCategory('xray');
        
        return view('Appointment/book', $data);
    }
    
    public function submit()
    {
        // Validation rules
        $rules = [
            'appointment_date' => 'required|valid_date',
            'appointment_time' => 'required',
            'email' => 'required|valid_email',
            'phone' => 'required|min_length[10]',
            'full_name' => 'required|min_length[2]',
            'age' => 'required|numeric|greater_than[0]',
            'gender' => 'required',
            'other_requests' => 'permit_empty'
        ];
        
        if ($this->validate($rules)) {
            // Get selected services
            $labServices = $this->request->getPost('lab_services') ?? [];
            $xrayServices = $this->request->getPost('xray_services') ?? [];
            $serviceType = $this->request->getPost('service_type') ?? 'laboratory';
            
            // Generate reference number
            $reference = 'APPT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            // Prepare data for appointment
            $appointmentData = [
                'reference_number' => $reference,
                'appointment_date' => $this->request->getPost('appointment_date'),
                'appointment_time' => $this->request->getPost('appointment_time'),
                'full_name' => $this->request->getPost('full_name'),
                'age' => $this->request->getPost('age'),
                'gender' => $this->request->getPost('gender'),
                'email' => $this->request->getPost('email'),
                'phone' => $this->request->getPost('phone'),
                'service_type' => $serviceType,
                'lab_services' => !empty($labServices) ? json_encode($labServices) : null,
                'xray_services' => !empty($xrayServices) ? json_encode($xrayServices) : null,
                'other_requests' => $this->request->getPost('other_requests'),
                'status' => 'pending'
            ];
            
            // ===== SAVE APPOINTMENT =====
            $appointmentModel = new AppointmentModel();
            $appointmentId = $appointmentModel->insert($appointmentData);
            
            if (!$appointmentId) {
                log_message('error', 'Failed to save appointment for: ' . $appointmentData['full_name']);
                return redirect()->back()->with('error', 'Failed to save appointment. Please try again.')->withInput();
            }
            
            // ===== CREATE/UPDATE PATIENT RECORD =====
            $patientModel = new PatientModel();
            $patient = $patientModel->findOrCreateFromAppointment($appointmentData);
            
            if ($patient) {
                log_message('info', 'Patient created/found from online booking: ' . $appointmentData['full_name'] . ' (Code: ' . $patient['patient_code'] . ')');
            } else {
                log_message('error', 'Failed to create/find patient from online booking: ' . $appointmentData['full_name']);
            }
            
            // ============================================================
            // ❌ REMOVED: Auto-creation of lab requests on booking
            // ❌ REMOVED: Auto-creation of x-ray requests on booking
            // ✅ Diagnostic requests will now be created ONLY when the
            //    receptionist approves the appointment in Receptionist::approveAppointment()
            // ============================================================
            
            // ===== TRIGGER ADMIN NOTIFICATION =====
            NotificationModel::notify(
                'appointment',
                'New Appointment Request: ' . $reference,
                'New appointment request from ' . $appointmentData['full_name'] . ' for ' . date('M d, Y', strtotime($appointmentData['appointment_date'])),
                $appointmentId,
                'admin/appointment/view/' . $appointmentId
            );
            
            // ===== TRIGGER RECEPTIONIST NOTIFICATION =====
            NotificationModel::notify(
                'appointment',
                'New Appointment: ' . $reference,
                'New appointment request from ' . $appointmentData['full_name'] . ' for ' . date('M d, Y', strtotime($appointmentData['appointment_date'])),
                $appointmentId,
                'receptionist/appointment/view/' . $appointmentId
            );
            
            // Store in session for success page
            session()->set('appointment_data', $appointmentData);
            
            return redirect()->to(base_url('appointment/success/' . $reference))
                            ->with('message', 'Appointment booked successfully!');
        } else {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
    }
    
    public function success($reference = null)
    {
        $model = new AppointmentModel();
        $data['appointment'] = $model->where('reference_number', $reference)->first();
        $data['reference'] = $reference;
        return view('Appointment/success', $data);
    }
    
    /**
     * API endpoint to get service prices
     */
    public function getServicePrices()
    {
        $serviceModel = new ServiceModel();
        $services = $serviceModel->getActiveServices();
        
        $prices = [];
        foreach ($services as $service) {
            $prices[$service['service_name']] = (float)$service['charge'];
        }
        
        return $this->response->setJSON($prices);
    }
}