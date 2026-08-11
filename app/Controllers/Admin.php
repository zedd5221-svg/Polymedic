<?php

namespace App\Controllers;

use App\Models\AppointmentModel;

class Admin extends BaseController
{
    public function dashboard()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/polymedic/public/login');
        }
        return view('Admin/dashboard');
    }
    
    public function patients()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/polymedic/public/login');
        }
        return view('Admin/patients');
    }
    
    public function visits()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/polymedic/public/login');
        }
        return view('Admin/visits');
    }
    
    public function requests()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/polymedic/public/login');
        }
        return view('Admin/requests');
    }
    
    public function users()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/polymedic/public/login');
        }
        return view('Admin/users');
    }
    
    // ===== APPOINTMENT MANAGEMENT =====
    
    public function appointments()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/polymedic/public/login');
        }
        
        try {
            $model = new AppointmentModel();
            
            // Get all appointments sorted by date and time
            $data['appointments'] = $model->orderBy('appointment_date', 'DESC')
                                          ->orderBy('appointment_time', 'ASC')
                                          ->findAll();
            
            // Get counts for stats
            $data['total'] = $model->countAll();
            $data['pending'] = $model->where('status', 'pending')->countAllResults();
            $data['approved'] = $model->where('status', 'approved')->countAllResults();
            $data['completed'] = $model->where('status', 'completed')->countAllResults();
            $data['cancelled'] = $model->where('status', 'cancelled')->countAllResults();
            $data['late'] = $model->where('status', 'late')->countAllResults();
            
            return view('Admin/appointments', $data);
        } catch (\Exception $e) {
            // Log the error
            log_message('error', 'Appointments error: ' . $e->getMessage());
            
            // Return simple error message
            return "Error: " . $e->getMessage();
        }
    }
    
    public function appointmentView($id)
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/polymedic/public/login');
        }
        
        $model = new AppointmentModel();
        $data['appointment'] = $model->find($id);
        
        if (!$data['appointment']) {
            return redirect()->to('/polymedic/public/admin/appointments')
                            ->with('error', 'Appointment not found');
        }
        
        // Decode services
        $data['lab_services'] = json_decode($data['appointment']['lab_services'], true) ?? [];
        $data['xray_services'] = json_decode($data['appointment']['xray_services'], true) ?? [];
        
        // Status color mapping
        $data['statusClass'] = [
            'pending' => 'warning',
            'approved' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
            'late' => 'dark'
        ];
        
        return view('Admin/appointment_view', $data);
    }
    
    public function approveAppointment($id)
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/polymedic/public/login');
        }
        
        $model = new AppointmentModel();
        $appointment = $model->find($id);
        
        if (!$appointment) {
            return redirect()->to('http://localhost/polymedic/public/admin/appointments/')
                            ->with('error', 'Appointment not found');
        }
        
        // Check if appointment is already processed
        if (in_array($appointment['status'], ['completed', 'cancelled', 'no_show'])) {
            return redirect()->to('http://localhost/polymedic/public/admin/appointments/')
                            ->with('error', 'This appointment cannot be approved');
        }
        
        // Update status to approved
        $model->update($id, [
            'status' => 'approved',
            'arrival_time' => date('Y-m-d H:i:s')
        ]);
        
        return redirect()->to('http://localhost/polymedic/public/admin/appointments/')
                        ->with('success', 'Appointment approved successfully!');
    }
    
    public function cancelAppointment($id)
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/polymedic/public/login');
        }
        
        $model = new AppointmentModel();
        $appointment = $model->find($id);
        
        if (!$appointment) {
            return redirect()->to('/polymedic/public/admin/appointments')
                            ->with('error', 'Appointment not found');
        }
        
        // Check if appointment is already completed
        if ($appointment['status'] == 'completed') {
            return redirect()->to('http://localhost/polymedic/public/admin/appointments/')
                            ->with('error', 'Completed appointments cannot be cancelled');
        }
        
        // Update status to cancelled
        $model->update($id, ['status' => 'cancelled']);
         return redirect()->to('http://localhost/polymedic/public/admin/appointments/')
                        ->with('success', 'Appointment cancelled successfully!');
    }
    
    public function completeAppointment($id)
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/polymedic/public/login');
        }
        
        $model = new AppointmentModel();
        $appointment = $model->find($id);
        
        if (!$appointment) {
            return redirect()->to('http://localhost/polymedic/public/admin/appointments/')
                            ->with('error', 'Appointment not found');
        }
        
        // Only approved appointments can be completed
        if ($appointment['status'] != 'approved') {
            return redirect()->to('http://localhost/polymedic/public/admin/appointments/')
                            ->with('error', 'Only approved appointments can be marked as completed');
        }
        
        // Update status to completed
        $model->update($id, ['status' => 'completed']);
        
       return redirect()->to('http://localhost/polymedic/public/admin/appointments/')
                        ->with('success', 'Appointment marked as completed!');
    }
    
    // ===== DELETE APPOINTMENT =====
    public function deleteAppointment($id)
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/polymedic/public/login');
        }
        
        $model = new AppointmentModel();
        $appointment = $model->find($id);
        
        if (!$appointment) {
           return redirect()->to('http://localhost/polymedic/public/admin/appointments/')
                            ->with('error', 'Appointment not found');
        }
        
        // Delete the appointment
        $model->delete($id);
        
        return redirect()->to('http://localhost/polymedic/public/admin/appointments/')
                        ->with('success', 'Appointment deleted successfully!');
    }
}