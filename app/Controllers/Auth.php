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
        $username     = trim((string) $this->request->getPost('username'));
        $password     = (string) $this->request->getPost('password');
        $selectedRole = (string) ($this->request->getPost('role') ?? 'administrator');

        if ($username === '' || $password === '') {
            return redirect()->to(base_url('login'))->withInput()
                            ->with('error', 'Username and password are required');
        }

        try {
            $userModel = new UserModel();
            $user      = $userModel->verifyLogin($username, $password);
        } catch (\Throwable $e) {
            log_message('error', 'Authentication error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->to(base_url('login'))->withInput()
                            ->with('error', 'Sign-in is temporarily unavailable. Please try again in a moment.');
        }

        if (!$user) {
            log_message('warning', 'Failed login attempt for username: ' . $username);
            return redirect()->to(base_url('login'))->withInput()
                            ->with('error', 'Invalid username or password');
        }

        if (($user['status'] ?? 'active') === 'inactive') {
            return redirect()->to(base_url('login'))->withInput()
                            ->with('error', 'Your account is inactive. Please contact administrator.');
        }

        // The role picker is a convenience; the account's own role is
        // what gets stored. Admins may sign in from any option.
        $roleMap = [
            'administrator' => 'admin',
            'receptionist'  => 'receptionist',
            'technologist'  => 'med_tech',
            'radiologist'   => 'radiologist',
        ];
        $dbSelectedRole = $roleMap[$selectedRole] ?? $selectedRole;

        if ($user['role'] !== 'admin' && $user['role'] !== $dbSelectedRole) {
            log_message('warning', 'Role mismatch for user: ' . $username . ' (selected ' . $selectedRole . ')');
            return redirect()->to(base_url('login'))->withInput()
                            ->with('error', 'The selected role does not match this account.');
        }

        // New session id on privilege change (prevents session fixation).
        session()->regenerate(true);
        session()->set([
            'is_logged_in' => true,
            'user_id'      => $user['id'],
            'username'     => $user['username'],
            'full_name'    => $user['full_name'],
            'email'        => $user['email'],
            'role'         => $user['role'],
            'avatar'       => $user['avatar'] ?? null,
        ]);

        // Shown on the My Profile page; ignored if the column is missing
        try {
            // users.updated_at is ON UPDATE CURRENT_TIMESTAMP; keep it as-is so it
            // still means "profile last edited", not "last signed in".
            db_connect()->query(
                'UPDATE users SET last_login_at = ?, updated_at = updated_at WHERE id = ?',
                [date('Y-m-d H:i:s'), $user['id']]
            );
        } catch (\Throwable $e) {
            log_message('error', 'Could not record last login: ' . $e->getMessage());
        }

        log_message('info', 'User logged in: ' . $username . ' with role: ' . $user['role']);

        return $this->redirectToDashboard($user['role']);
    }
    
    public function logout()
    {
        if (session()->get('is_logged_in')) {
            log_message('info', 'User logged out: ' . session()->get('username'));
        }
        
        session()->destroy();
        return redirect()->to(base_url('login'));
    }
    
    private function redirectToDashboard($role)
    {
        $home = [
            'admin'        => 'admin/dashboard',
            'receptionist' => 'receptionist/dashboard',
            'radiologist'  => 'radiologist/dashboard',
            'med_tech'     => 'medtech/dashboard',
            'technologist' => 'medtech/dashboard',
        ][$role] ?? null;

        if ($home === null) {
            // A role with no area of its own must not land in admin.
            session()->destroy();
            return redirect()->to(base_url('login'))
                             ->with('error', 'Your account has no dashboard assigned. Please contact the administrator.');
        }

        return redirect()->to(base_url($home));
    }
}
