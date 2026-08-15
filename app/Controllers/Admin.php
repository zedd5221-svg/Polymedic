<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\UserModel;

class Admin extends BaseController
{
    private function checkAuth()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('login'));
        }
        
        $role = session()->get('role');
        if ($role !== 'admin') {
            // Redirect based on role
            if ($role === 'receptionist') {
                return redirect()->to(base_url('receptionist/dashboard'));
            }
            return redirect()->to(base_url('login'));
        }
        return null;
    }

    public function dashboard()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        return view('Admin/dashboard');
    }
    
    public function patients()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        return view('Admin/patients');
    }
    
    public function visits()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        return view('Admin/visits');
    }
    
    public function requests()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        return view('Admin/requests');
    }
    
    public function users()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $userModel = new UserModel();
        $data['users'] = $userModel->getUsers();
        $data['total'] = $userModel->countUsers();
        
        return view('Admin/users', $data);
    }
    
    // ===== USER MANAGEMENT CRUD =====
    
    public function createUser()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        // Get POST data
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $email = $this->request->getPost('email');
        $full_name = $this->request->getPost('full_name');
        $role = $this->request->getPost('role');
        $status = $this->request->getPost('status') ?? 'active';
        
        // Validate
        if (empty($username) || empty($password) || empty($email) || empty($full_name) || empty($role)) {
            return redirect()->to(base_url('admin/users'))
                            ->with('error', 'All fields are required');
        }
        
        // Check if username exists
        $userModel = new UserModel();
        if ($userModel->getUserByUsername($username)) {
            return redirect()->to(base_url('admin/users'))
                            ->with('error', 'Username already exists');
        }
        
        // Create user
        $userData = [
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'full_name' => $full_name,
            'role' => $role,
            'status' => $status
        ];
        
        if ($userModel->createUser($userData)) {
            return redirect()->to(base_url('admin/users'))
                            ->with('success', 'User created successfully');
        }
        
        return redirect()->to(base_url('admin/users'))
                        ->with('error', 'Failed to create user');
    }
    
    public function updateUser($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $userModel = new UserModel();
        $user = $userModel->find($id);
        
        if (!$user) {
            return redirect()->to(base_url('admin/users'))
                            ->with('error', 'User not found');
        }
        
        // Get POST data
        $email = $this->request->getPost('email');
        $full_name = $this->request->getPost('full_name');
        $role = $this->request->getPost('role');
        $status = $this->request->getPost('status');
        $password = $this->request->getPost('password');
        
        // Prepare update data
        $updateData = [
            'email' => $email,
            'full_name' => $full_name,
            'role' => $role,
            'status' => $status
        ];
        
        // Only update password if provided
        if (!empty($password)) {
            $updateData['password'] = $password;
        }
        
        if ($userModel->updateUser($id, $updateData)) {
            return redirect()->to(base_url('admin/users'))
                            ->with('success', 'User updated successfully');
        }
        
        return redirect()->to(base_url('admin/users'))
                        ->with('error', 'Failed to update user');
    }
    
    public function deleteUser($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $userModel = new UserModel();
        $user = $userModel->find($id);
        
        if (!$user) {
            return redirect()->to(base_url('admin/users'))
                            ->with('error', 'User not found');
        }
        
        // Prevent deleting self
        if ($user['id'] == session()->get('user_id')) {
            return redirect()->to(base_url('admin/users'))
                            ->with('error', 'You cannot delete your own account');
        }
        
        if ($userModel->deleteUser($id)) {
            return redirect()->to(base_url('admin/users'))
                            ->with('success', 'User deleted successfully');
        }
        
        return redirect()->to(base_url('admin/users'))
                        ->with('error', 'Failed to delete user');
    }
    
    public function toggleUserStatus($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $userModel = new UserModel();
        $user = $userModel->find($id);
        
        if (!$user) {
            return redirect()->to(base_url('admin/users'))
                            ->with('error', 'User not found');
        }
        
        // Prevent toggling self
        if ($user['id'] == session()->get('user_id')) {
            return redirect()->to(base_url('admin/users'))
                            ->with('error', 'You cannot change your own status');
        }
        
        $newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';
        
        if ($userModel->updateStatus($id, $newStatus)) {
            return redirect()->to(base_url('admin/users'))
                            ->with('success', 'User status updated successfully');
        }
        
        return redirect()->to(base_url('admin/users'))
                        ->with('error', 'Failed to update user status');
    }
    
    public function getUserData($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $userModel = new UserModel();
        $user = $userModel->find($id);
        
        if ($user) {
            // Remove sensitive data
            unset($user['password']);
            return $this->response->setJSON($user);
        }
        
        return $this->response->setJSON(['error' => 'User not found'], 404);
    }
    
    // ===== APPOINTMENT MANAGEMENT =====
    
    public function appointments()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        try {
            $model = new AppointmentModel();
            
            $model->checkLateAppointments();
            
            $data['appointments'] = $model->orderBy('appointment_date', 'DESC')
                                          ->orderBy('appointment_time', 'ASC')
                                          ->findAll();
            
            $data['total']     = $model->countAll();
            $data['pending']   = $model->where('status', 'pending')->countAllResults();
            $data['approved']  = $model->where('status', 'approved')->countAllResults();
            $data['completed'] = $model->where('status', 'completed')->countAllResults();
            $data['cancelled'] = $model->where('status', 'cancelled')->countAllResults();
            $data['late']      = $model->where('status', 'late')->countAllResults();
            
            return view('Admin/appointments', $data);
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
            return redirect()->to(base_url('admin/appointments'))
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
        
        return view('Admin/appointment_view', $data);
    }
    
    public function approveAppointment($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $model = new AppointmentModel();
        $appointment = $model->find($id);
        
        if (!$appointment) {
            return redirect()->to(base_url('admin/appointments'))
                            ->with('error', 'Appointment not found');
        }
        
        if (in_array($appointment['status'], ['completed', 'cancelled', 'no_show'])) {
            return redirect()->to(base_url('admin/appointments'))
                            ->with('error', 'This appointment cannot be approved');
        }
        
        $model->update($id, [
            'status'       => 'approved',
            'arrival_time' => date('Y-m-d H:i:s')
        ]);
        
        return redirect()->to(base_url('admin/appointments'))
                        ->with('success', 'Appointment approved successfully!');
    }
    
    public function cancelAppointment($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $model = new AppointmentModel();
        $appointment = $model->find($id);
        
        if (!$appointment) {
            return redirect()->to(base_url('admin/appointments'))
                            ->with('error', 'Appointment not found');
        }
        
        if ($appointment['status'] == 'completed') {
            return redirect()->to(base_url('admin/appointments'))
                            ->with('error', 'Completed appointments cannot be cancelled');
        }
        
        $model->update($id, ['status' => 'cancelled']);
        
        return redirect()->to(base_url('admin/appointments'))
                        ->with('success', 'Appointment cancelled successfully!');
    }
    
    public function completeAppointment($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $model = new AppointmentModel();
        $appointment = $model->find($id);
        
        if (!$appointment) {
            return redirect()->to(base_url('admin/appointments'))
                            ->with('error', 'Appointment not found');
        }
        
        if ($appointment['status'] != 'approved') {
            return redirect()->to(base_url('admin/appointments'))
                            ->with('error', 'Only approved appointments can be marked as completed');
        }
        
        $model->update($id, ['status' => 'completed']);
        
        return redirect()->to(base_url('admin/appointments'))
                        ->with('success', 'Appointment marked as completed!');
    }
    
    public function deleteAppointment($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $model = new AppointmentModel();
        $appointment = $model->find($id);
        
        if (!$appointment) {
           return redirect()->to(base_url('admin/appointments'))
                            ->with('error', 'Appointment not found');
        }
        
        $model->delete($id);
        
        return redirect()->to(base_url('admin/appointments'))
                        ->with('success', 'Appointment deleted successfully!');
    }
}