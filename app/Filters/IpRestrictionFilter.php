<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\SettingsModel;

class IpRestrictionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $params = null)
    {
        $session = session();
        
        // Only check for logged-in staff/admin users or staff login attempts
        if ($session->get('is_logged_in')) {
            $clientIp = $request->getIPAddress();
            $settingsModel = new SettingsModel();
            
            if (!$settingsModel->isIpAllowed($clientIp)) {
                log_message('warning', "Blocked IP access attempt from {$clientIp} for user " . $session->get('username'));
                
                // Return access denied view or response
                $response = services('response');
                $response->setStatusCode(403);
                $response->setBody(view('errors/html/error_403_ip', [
                    'clientIp' => $clientIp
                ]));
                return $response;
            }
        }
        
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $params = null)
    {
        // Do nothing after
    }
}
