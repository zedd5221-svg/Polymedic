<?php

namespace App\Controllers;

use App\Models\AppointmentModel;
use App\Models\UserModel;
use App\Models\XrayExaminationModel;
use App\Models\NotificationModel;
use App\Models\ServiceModel;
use App\Models\SettingsModel;

class Admin extends BaseController
{
    private function checkAuth()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('login'));
        }
        
        $role = session()->get('role');
        if ($role !== 'admin') {
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
        
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $email = $this->request->getPost('email');
        $full_name = $this->request->getPost('full_name');
        $role = $this->request->getPost('role');
        $status = $this->request->getPost('status') ?? 'active';
        
        if (empty($username) || empty($password) || empty($email) || empty($full_name) || empty($role)) {
            return redirect()->to(base_url('admin/users'))
                            ->with('error', 'All fields are required');
        }
        
        $userModel = new UserModel();
        if ($userModel->getUserByUsername($username)) {
            return redirect()->to(base_url('admin/users'))
                            ->with('error', 'Username already exists');
        }
        
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
        
        $email = $this->request->getPost('email');
        $full_name = $this->request->getPost('full_name');
        $role = $this->request->getPost('role');
        $status = $this->request->getPost('status');
        $password = $this->request->getPost('password');
        
        $updateData = [
            'email' => $email,
            'full_name' => $full_name,
            'role' => $role,
            'status' => $status
        ];
        
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
    
    /**
     * Format X-Ray services to readable string
     */
    private function formatXrayServices($services)
    {
        if (empty($services)) {
            return 'X-Ray Examination';
        }
        
        if (is_string($services)) {
            $decoded = json_decode($services, true);
            if (is_array($decoded)) {
                $services = $decoded;
            }
        }
        
        if (is_array($services)) {
            $cleaned = array_map(function($service) {
                $service = str_replace('\/', '/', $service);
                $service = str_replace('\\/', '/', $service);
                $service = stripslashes($service);
                return trim($service);
            }, $services);
            return implode(', ', $cleaned);
        }
        
        return $services;
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
        
        // ===== CHECK FOR X-RAY SERVICES AND CREATE EXAMINATION =====
        $xrayServices = json_decode($appointment['xray_services'], true) ?? [];
        if (!empty($xrayServices)) {
            $xrayModel = new XrayExaminationModel();
            $existing = $xrayModel->where('appointment_id', $id)->first();
            
            if (!$existing) {
                // Format exam type properly
                $examType = $this->formatXrayServices($xrayServices);
                
                $xrayModel->insert([
                    'appointment_id' => $id,
                    'patient_name' => $appointment['full_name'],
                    'age' => $appointment['age'],
                    'gender' => $appointment['gender'],
                    'exam_type' => $examType,
                    'exam_date' => $appointment['appointment_date'],
                    'priority' => 'Routine',
                    'status' => 'pending'
                ]);
                
                NotificationModel::notify(
                    'xray',
                    'New X-Ray Examination',
                    'New X-Ray examination for ' . $appointment['full_name'],
                    $id,
                    'radiologist/examination/view/' . $id
                );
            }
        }
        
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
    
    // ===== SYNC X-RAY EXAMINATIONS =====
    
    public function syncXrayExaminations()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;
        
        $appointmentModel = new AppointmentModel();
        $xrayModel = new XrayExaminationModel();
        
        // Get all approved appointments with X-Ray services
        $appointments = $appointmentModel
            ->where('status', 'approved')
            ->findAll();
        
        $created = 0;
        $skipped = 0;
        
        foreach ($appointments as $appt) {
            $xrayServices = json_decode($appt['xray_services'], true) ?? [];
            
            if (empty($xrayServices)) {
                continue;
            }
            
            $existing = $xrayModel->where('appointment_id', $appt['id'])->first();
            
            if (!$existing) {
                // Format exam type properly
                $examType = $this->formatXrayServices($xrayServices);
                
                $xrayModel->insert([
                    'appointment_id' => $appt['id'],
                    'patient_name' => $appt['full_name'],
                    'age' => $appt['age'],
                    'gender' => $appt['gender'],
                    'exam_type' => $examType,
                    'exam_date' => $appt['appointment_date'],
                    'priority' => 'Routine',
                    'status' => 'pending'
                ]);
                $created++;
            } else {
                $skipped++;
            }
        }
        
        return redirect()->to(base_url('admin/appointments'))
                        ->with('success', "Synced {$created} X-Ray examinations. Skipped {$skipped} existing.");
    }
    
    // ===== SERVICE MANAGEMENT =====

public function services()
{
    $redirect = $this->checkAuth();
    if ($redirect) return $redirect;
    
    $serviceModel = new ServiceModel();
    $data['services'] = $serviceModel->orderBy('category', 'ASC')
                                     ->orderBy('service_name', 'ASC')
                                     ->findAll();
    
    $counts = $serviceModel->getCountByCategory();
    $data['total'] = $counts['total'];
    $data['lab_count'] = $counts['laboratory'];
    $data['xray_count'] = $counts['xray'];
    $data['other_count'] = $counts['other'];
    
    return view('Admin/services', $data);
}

public function createService()
{
    $redirect = $this->checkAuth();
    if ($redirect) return $redirect;
    
    $serviceModel = new ServiceModel();
    
    $rules = [
        'service_code' => 'required|is_unique[services.service_code]',
        'service_name' => 'required',
        'category' => 'required|in_list[laboratory,xray,other]',
        'charge' => 'permit_empty|numeric|greater_than_equal_to[0]'
    ];
    
    if (!$this->validate($rules)) {
        return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
    }
    
    $serviceModel->save([
        'service_code' => strtoupper($this->request->getPost('service_code')),
        'service_name' => $this->request->getPost('service_name'),
        'category' => $this->request->getPost('category'),
        'description' => $this->request->getPost('description'),
        'charge' => $this->request->getPost('charge') ?? 0,
        'is_active' => $this->request->getPost('is_active') ?? 1
    ]);
    
    return redirect()->to(base_url('admin/services'))
                    ->with('success', 'Service created successfully!');
}

public function updateService($id)
{
    $redirect = $this->checkAuth();
    if ($redirect) return $redirect;
    
    $serviceModel = new ServiceModel();
    $service = $serviceModel->find($id);
    
    if (!$service) {
        return redirect()->to(base_url('admin/services'))
                        ->with('error', 'Service not found');
    }
    
    $rules = [
        'service_code' => "required|is_unique[services.service_code,id,{$id}]",
        'service_name' => 'required',
        'category' => 'required|in_list[laboratory,xray,other]',
        'charge' => 'permit_empty|numeric|greater_than_equal_to[0]'
    ];
    
    if (!$this->validate($rules)) {
        return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
    }
    
    $serviceModel->update($id, [
        'service_code' => strtoupper($this->request->getPost('service_code')),
        'service_name' => $this->request->getPost('service_name'),
        'category' => $this->request->getPost('category'),
        'description' => $this->request->getPost('description'),
        'charge' => $this->request->getPost('charge') ?? 0,
        'is_active' => $this->request->getPost('is_active') ?? 1
    ]);
    
    return redirect()->to(base_url('admin/services'))
                    ->with('success', 'Service updated successfully!');
}

public function deleteService($id)
{
    $redirect = $this->checkAuth();
    if ($redirect) return $redirect;
    
    $serviceModel = new ServiceModel();
    $service = $serviceModel->find($id);
    
    if (!$service) {
        return redirect()->to(base_url('admin/services'))
                        ->with('error', 'Service not found');
    }
    
    $serviceModel->delete($id);
    
    return redirect()->to(base_url('admin/services'))
                    ->with('success', 'Service deleted successfully!');
}

public function toggleServiceStatus($id)
{
    $redirect = $this->checkAuth();
    if ($redirect) return $redirect;
    
    $serviceModel = new ServiceModel();
    $service = $serviceModel->find($id);
    
    if (!$service) {
        return redirect()->to(base_url('admin/services'))
                        ->with('error', 'Service not found');
    }
    
    $newStatus = ($service['is_active'] == 1) ? 0 : 1;
    $serviceModel->update($id, ['is_active' => $newStatus]);
    
    return redirect()->to(base_url('admin/services'))
                    ->with('success', 'Service status updated successfully!');
}

// ===== SETTINGS & PRINT TEMPLATE MANAGEMENT =====

public function settings()
{
    $redirect = $this->checkAuth();
    if ($redirect) return $redirect;

    $settingsModel = new SettingsModel();
    $data['settings'] = $settingsModel->getAllSettings();
    $data['clientIp'] = $this->request->getIPAddress();

    return view('Admin/settings', $data);
}

public function updateIpSettings()
{
    $redirect = $this->checkAuth();
    if ($redirect) return $redirect;

    $settingsModel = new SettingsModel();
    $enabled = $this->request->getPost('ip_restriction_enabled') ? '1' : '0';
    $allowedIps = $this->request->getPost('allowed_ips') ?? '';

    $settingsModel->setSetting('ip_restriction_enabled', $enabled);
    $settingsModel->setSetting('allowed_ips', $allowedIps);

    return redirect()->to(base_url('admin/settings'))
                    ->with('success', 'IP restriction settings updated successfully!');
}

public function savePrintTemplate()
{
    $redirect = $this->checkAuth();
    if ($redirect) return $redirect;

    $settingsModel = new SettingsModel();
    
    $settingsModel->setSetting('print_header_title', $this->request->getPost('print_header_title'));
    $settingsModel->setSetting('print_header_subtitle', $this->request->getPost('print_header_subtitle'));
    $settingsModel->setSetting('print_contact_info', $this->request->getPost('print_contact_info'));
    $settingsModel->setSetting('print_accent_color', $this->request->getPost('print_accent_color'));
    $settingsModel->setSetting('print_signature_title', $this->request->getPost('print_signature_title'));
    $settingsModel->setSetting('print_footer_note', $this->request->getPost('print_footer_note'));
    $settingsModel->setSetting('print_layout_style', $this->request->getPost('print_layout_style'));

    return redirect()->to(base_url('admin/settings'))
                    ->with('success', 'Print layout template settings saved successfully!');
}

public function uploadLogo()
{
    $redirect = $this->checkAuth();
    if ($redirect) return $redirect;

    $settingsModel = new SettingsModel();
    $file = $this->request->getFile('facility_logo');

    if (!$file || !$file->isValid() || $file->hasMoved()) {
        return redirect()->to(base_url('admin/settings'))
                        ->with('error', 'Please select a valid image file for the logo.');
    }

    // Validate file type
    $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
    if (!in_array($file->getMimeType(), $allowedMime)) {
        return redirect()->to(base_url('admin/settings'))
                        ->with('error', 'Only JPG, PNG, GIF, SVG, and WebP image files are allowed for the logo.');
    }

    // Validate file size (max 2MB)
    if ($file->getSize() > 2 * 1024 * 1024) {
        return redirect()->to(base_url('admin/settings'))
                        ->with('error', 'Logo file size must be less than 2MB.');
    }

    $uploadPath = ROOTPATH . 'public/uploads/logos/';
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0777, true);
    }

    // Remove old logo if it exists
    $oldLogoPath = $settingsModel->getSetting('print_logo_path', '');
    if (!empty($oldLogoPath)) {
        $oldFile = ROOTPATH . 'public' . $oldLogoPath;
        if (file_exists($oldFile)) {
            @unlink($oldFile);
        }
    }

    $newName = 'facility_logo_' . time() . '.' . $file->getExtension();
    $file->move($uploadPath, $newName);

    $settingsModel->setSetting('print_logo_path', '/uploads/logos/' . $newName);

    return redirect()->to(base_url('admin/settings'))
                    ->with('success', 'Facility logo uploaded successfully!');
}

public function removeLogo()
{
    $redirect = $this->checkAuth();
    if ($redirect) return $redirect;

    $settingsModel = new SettingsModel();
    $oldLogoPath = $settingsModel->getSetting('print_logo_path', '');
    if (!empty($oldLogoPath)) {
        $oldFile = ROOTPATH . 'public' . $oldLogoPath;
        if (file_exists($oldFile)) {
            @unlink($oldFile);
        }
    }
    $settingsModel->setSetting('print_logo_path', '');

    return redirect()->to(base_url('admin/settings'))
                    ->with('success', 'Facility logo removed successfully.');
}
}