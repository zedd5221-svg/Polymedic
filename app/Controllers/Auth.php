<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        // Check if already logged in
        if (session()->get('is_logged_in')) {
            $role = session()->get('role');
            return $this->redirectToDashboard($role);
        }
        
        return view('Auth/login');
    }
    
    public function authenticate()
    {
        // Get credentials from form
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $selectedRole = $this->request->getPost('role') ?? 'administrator';
        
        // Validate input
        if (empty($username) || empty($password)) {
            return redirect()->to(base_url('login'))
                            ->with('error', 'Username and password are required');
        }
        
        try {
            // Check if UserModel exists
            if (!class_exists('App\Models\UserModel')) {
                return redirect()->to(base_url('login'))
                                ->with('error', 'UserModel not found. Please check the file exists at app/Models/UserModel.php');
            }
            
            // Create UserModel instance
            $userModel = new UserModel();
            
            // Debug: Check if we can connect to the database
            try {
                // Try to count users to test connection
                $userCount = $userModel->countAll();
                log_message('debug', 'UserModel connection successful. Total users: ' . $userCount);
            } catch (\Exception $dbError) {
                log_message('error', 'Database connection error: ' . $dbError->getMessage());
                return redirect()->to(base_url('login'))
                                ->with('error', 'Database connection error: ' . $dbError->getMessage());
            }
            
            // Verify credentials
            $user = $userModel->verifyLogin($username, $password);
            
            if (!$user) {
                // Log failed attempt
                log_message('warning', 'Failed login attempt for username: ' . $username);
                return redirect()->to(base_url('login'))
                                ->with('error', 'Invalid username or password');
            }
            
            // Check if user is active
            if (isset($user['status']) && $user['status'] === 'inactive') {
                return redirect()->to(base_url('login'))
                                ->with('error', 'Your account is inactive. Please contact administrator.');
            }
            
            // Map the selected role from form to database role
            $roleMap = [
                'administrator' => 'admin',
                'receptionist' => 'receptionist',
                'technologist' => 'med_tech',
                'radiologist' => 'radiologist'  // ← ADD THIS
            ];
            
            // Get the mapped database role
            $dbSelectedRole = $roleMap[$selectedRole] ?? $selectedRole;
            
            // Debug logging
            log_message('debug', 'User role from DB: ' . $user['role'] . ', Selected role: ' . $selectedRole . ', Mapped role: ' . $dbSelectedRole);
            
            // Allow login if:
            // 1. User is admin (can access any role) OR
            // 2. User's role matches the selected role
            $isAdmin = ($user['role'] === 'admin');
            $roleMatches = ($user['role'] === $dbSelectedRole);
            
            if (!$isAdmin && !$roleMatches) {
                log_message('warning', 'Role mismatch for user: ' . $username . ' - User role: ' . $user['role'] . ', Selected: ' . $selectedRole . ', Mapped: ' . $dbSelectedRole);
                return redirect()->to(base_url('login'))
                                ->with('error', 'Role mismatch. Please select the correct role for your account. Your role is: ' . ucfirst($user['role']));
            }
            
            // Store user info in session
            session()->set([
                'is_logged_in' => true,
                'user_id'      => $user['id'],
                'username'     => $user['username'],
                'full_name'    => $user['full_name'],
                'email'        => $user['email'],
                'role'         => $user['role']
            ]);
            
            // Log successful login
            log_message('info', 'User logged in: ' . $username . ' with role: ' . $user['role']);
            
            // Redirect based on role
            return $this->redirectToDashboard($user['role']);
            
        } catch (\Exception $e) {
            // Log the error with details
            $errorMessage = 'Authentication error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine();
            log_message('error', $errorMessage);
            
            // Return to login with specific error
            return redirect()->to(base_url('login'))
                            ->with('error', 'System error: ' . $e->getMessage() . ' (Check error logs for details)');
        }
    }
    
    public function logout()
    {
        // Log logout
        if (session()->get('is_logged_in')) {
            log_message('info', 'User logged out: ' . session()->get('username'));
        }
        
        session()->destroy();
        return redirect()->to(base_url('login'));
    }
    
    private function redirectToDashboard($role)
    {
        switch ($role) {
            case 'admin':
                return redirect()->to(base_url('admin/dashboard'));
            case 'receptionist':
                return redirect()->to(base_url('receptionist/dashboard'));
            case 'radiologist':  // ← FIXED: Added this case
                return redirect()->to(base_url('radiologist/dashboard'));
            case 'med_tech':
            case 'technologist':
                return redirect()->to(base_url('admin/dashboard'));
            case 'physician':
                return redirect()->to(base_url('admin/dashboard'));
            default:
                return redirect()->to(base_url('admin/dashboard'));
        }
    }
    
    public function saveTheme()
    {
        $theme = $this->request->getPost('theme') ?? 'light';
        if (!in_array($theme, ['light', 'dark'])) {
            $theme = 'light';
        }

        session()->set('user_theme', $theme);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'  => 'success',
                'theme'   => $theme,
                'message' => 'Theme updated successfully'
            ]);
        }

        return redirect()->back();
    }

    // For testing purposes - check if database connection works
    public function testConnection()
    {
        try {
            $userModel = new UserModel();
            $users = $userModel->findAll();
            echo "Connection successful! Total users: " . count($users) . "<br>";
            echo "<pre>";
            print_r($users);
            echo "</pre>";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "<br>";
            echo "File: " . $e->getFile() . "<br>";
            echo "Line: " . $e->getLine();
        }
    }
}