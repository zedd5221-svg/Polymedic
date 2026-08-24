<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\ServiceModel;

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
            
            // Prepare data for database
            $data = [
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
            
            // Save to database
            $model = new AppointmentModel();
            $appointmentId = $model->insert($data);
            
            // Trigger Admin Notification
            \App\Models\NotificationModel::notify(
                'appointment',
                'New Appointment Request: ' . $reference,
                'New appointment request from ' . $data['full_name'] . ' for ' . date('M d, Y', strtotime($data['appointment_date'])),
                $appointmentId,
                '/polymedic/public/admin/appointment/view/' . $appointmentId
            );
            
            // Store in session for success page
            session()->set('appointment_data', $data);
            
            return redirect()->to('http://localhost/polymedic/public/index.php/appointment/success/' . $reference)
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