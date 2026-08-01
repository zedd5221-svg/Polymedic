<?php

namespace App\Controllers;

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
            // Prepare data
            $appointmentData = [
                'appointment_date' => $this->request->getPost('appointment_date'),
                'appointment_time' => $this->request->getPost('appointment_time'),
                'email' => $this->request->getPost('email'),
                'phone' => $this->request->getPost('phone'),
                'full_name' => $this->request->getPost('full_name'),
                'age' => $this->request->getPost('age'),
                'gender' => $this->request->getPost('gender'),
                'other_requests' => $this->request->getPost('other_requests'),
                'reference_number' => 'CP-' . date('Ymd') . '-' . rand(100, 999)
            ];
            
            // Store in session
            session()->set('appointment_data', $appointmentData);
            
            // FIXED: Full URL redirect
            return redirect()->to('http://localhost/polymedic/public/index.php/appointment/success/' . $appointmentData['reference_number'])
                            ->with('message', 'Appointment booked successfully!');
        } else {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
    }
    
    public function success($reference = null)
    {
        $data['appointment'] = session()->get('appointment_data');
        $data['reference'] = $reference;
        return view('Appointment/success', $data);
    }
}