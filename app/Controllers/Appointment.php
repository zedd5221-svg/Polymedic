<?php

namespace App\Controllers;

use App\Models\AppointmentModel;

class Appointment extends BaseController
{
    public function index(): string
    {
        return view('Appointment/index');
    }
    
    public function book(): string
    {
        return view('Appointment/book');
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
        $model->save($data);
        
        // Store in session for success page
        session()->set('appointment_data', $data);
        
        // FIXED: Use full URL with index.php
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
}