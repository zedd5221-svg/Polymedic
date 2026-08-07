<?php

namespace App\Controllers;

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
}