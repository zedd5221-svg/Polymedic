<?php

namespace App\Controllers;

class Auth extends BaseController
{
    public function login()
    {
        // Check if already logged in
        if (session()->get('is_logged_in')) {
            return redirect()->to('/polymedic/public/admin/dashboard');
        }
        
        return view('Auth/login');
    }
    
    public function authenticate()
    {
        // Get credentials from form
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $role = $this->request->getPost('role') ?? 'administrator';
        
        // Store user info in session
        session()->set([
            'is_logged_in' => true,
            'username' => $username,
            'role' => $role
        ]);
        
        // FIXED: Full URL redirect
        return redirect()->to('http://localhost/polymedic/public/admin/dashboard');
    }
    
    public function logout()
    {
        session()->destroy();
        return redirect()->to('http://localhost/polymedic/public/login');
    }
}