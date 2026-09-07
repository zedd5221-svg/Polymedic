<?php

namespace App\Controllers;

use App\Models\AppointmentModel;

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
        
        return redirect()->to(base_url('receptionist/appointments'))
                        ->with('success', 'Appointment approved successfully!');
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
        
        // Get only APPROVED patients from appointments table
        $appointmentModel = new AppointmentModel();
        
        // Get all approved appointments
        $approvedAppointments = $appointmentModel
            ->where('status', 'approved')
            ->orderBy('appointment_date', 'DESC')
            ->findAll();
        
        // Show all approved appointments as individual patient entries
        $patients = [];
        
        foreach ($approvedAppointments as $appointment) {
            // Add each approved appointment as a separate patient entry
            $patients[] = $appointment;
        }
        
        $data['patients'] = $patients;
        $data['total'] = count($patients);
        
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

    public function settings()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $settingsModel = new \App\Models\SettingsModel();
        $data['settings'] = $settingsModel->getAllSettings();

        return view('Receptionist/settings', $data);
    }

    public function savePrintTemplate()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $settingsModel = new \App\Models\SettingsModel();
        
        $settingsModel->setSetting('print_header_title', $this->request->getPost('print_header_title'));
        $settingsModel->setSetting('print_header_subtitle', $this->request->getPost('print_header_subtitle'));
        $settingsModel->setSetting('print_contact_info', $this->request->getPost('print_contact_info'));
        $settingsModel->setSetting('print_accent_color', $this->request->getPost('print_accent_color'));
        $settingsModel->setSetting('print_signature_title', $this->request->getPost('print_signature_title'));
        $settingsModel->setSetting('print_footer_note', $this->request->getPost('print_footer_note'));
        $settingsModel->setSetting('print_layout_style', $this->request->getPost('print_layout_style'));

        return redirect()->to(base_url('receptionist/settings'))
                        ->with('success', 'Print layout template settings saved successfully!');
    }
}