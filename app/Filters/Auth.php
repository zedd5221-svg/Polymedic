<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class Auth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Check if user is logged in
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('login'))->with('error', 'Please login to access this page.');
        }
        
        // Check role-based access if specified
        if (!empty($arguments)) {
            $userRole = session()->get('role');
            
            // Allow admin to access all roles
            if ($userRole === 'admin') {
                return;
            }
            
            // Check if user role is in allowed roles
            if (!in_array($userRole, $arguments)) {
                return redirect()->to(base_url('login'))->with('error', 'Access denied. You do not have permission to access this page.');
            }
        }
    }
    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}