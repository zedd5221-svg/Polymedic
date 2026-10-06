<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class Auth implements FilterInterface
{
    /**
     * Dashboard per role, used when access is denied so a user is
     * bounced home instead of being thrown back to the login screen.
     */
    private const DASHBOARDS = [
        'admin'        => 'admin/dashboard',
        'receptionist' => 'receptionist/dashboard',
        'radiologist'  => 'radiologist/dashboard',
        'med_tech'     => 'medtech/dashboard',
    ];

    /**
     * Areas an admin is still allowed to enter.
     *
     * The old filter let admin into EVERY role's area, which is why an
     * admin clicking a notification ended up inside the radiologist or
     * med tech screens. Leave this empty to lock admin to admin routes.
     * Add e.g. 'receptionist' here if admin genuinely needs to cover
     * the front desk.
     */
    private const ADMIN_MAY_ENTER = [];

    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('login'))
                             ->with('error', 'Please login to access this page.');
        }

        if (empty($arguments)) {
            return;
        }

        $userRole = (string) session()->get('role');

        // Exact role match
        if (in_array($userRole, $arguments, true)) {
            return;
        }

        // Narrow, explicit admin exception (empty by default)
        if ($userRole === 'admin' && array_intersect($arguments, self::ADMIN_MAY_ENTER)) {
            return;
        }

        $home = self::DASHBOARDS[$userRole] ?? 'login';

        return redirect()->to(base_url($home))
                         ->with('error', 'Access denied. You do not have permission to open that page.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}