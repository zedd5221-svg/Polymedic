<?php

namespace App\Controllers;

use App\Models\NotificationModel;

class NotificationController extends BaseController
{
    protected $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new NotificationModel();
    }

    /**
     * ============================================================
     * HELPER - Clean Link (Removes ALL base URL variations)
     * ============================================================
     */
    private function cleanLink($link)
    {
        if (empty($link)) {
            return '';
        }
        
        // Remove ALL possible base URL variations
        $link = str_replace('http://localhost/polymedic/public/', '', $link);
        $link = str_replace('https://localhost/polymedic/public/', '', $link);
        $link = str_replace('http://localhost/polymedic/', '', $link);
        $link = str_replace('https://localhost/polymedic/', '', $link);
        $link = str_replace('/polymedic/public/', '', $link);
        $link = str_replace('polymedic/public/', '', $link);
        $link = str_replace('/public/', '', $link);
        $link = str_replace('/index.php/', '', $link);
        $link = str_replace(base_url(), '', $link);
        $link = ltrim($link, '/');
        
        return $link;
    }

    private function getFullLink($link)
    {
        $cleaned = $this->cleanLink($link);
        if (empty($cleaned)) {
            return base_url('admin/dashboard');
        }
        return base_url($cleaned);
    }

    /**
     * ============================================================
     * HELPER - Time Ago
     * ============================================================
     */
    private function timeAgo($datetime)
    {
        if (!$datetime) return 'Just now';
        $timestamp = strtotime($datetime);
        $diff      = time() - $timestamp;

        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ($mins == 1 ? ' min ago' : ' mins ago');
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ($hours == 1 ? ' hour ago' : ' hours ago');
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ($days == 1 ? ' day ago' : ' days ago');
        } else {
            return date('M d, Y', $timestamp);
        }
    }

    /**
     * ============================================================
     * ADMIN NOTIFICATION METHODS
     * ============================================================
     */

    public function index()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('login'));
        }

        $notifications = $this->notificationModel->orderBy('created_at', 'DESC')->findAll();
        
        foreach ($notifications as &$notif) {
            $notif['full_link'] = $this->getFullLink($notif['link'] ?? '');
        }

        $data['notifications'] = $notifications;
        $data['unread_count']  = $this->notificationModel->getUnreadCount();
        $data['total_count']   = count($notifications);

        return view('Admin/notifications', $data);
    }

    public function fetch()
    {
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $unreadCount = $this->notificationModel->getUnreadCount();
        $recent      = $this->notificationModel->getRecentNotifications(6);

        $formatted = array_map(function ($item) {
            return [
                'id'           => $item['id'],
                'type'         => $item['type'],
                'title'        => $item['title'],
                'message'      => $item['message'],
                'link'         => $this->getFullLink($item['link'] ?? ''),
                'is_read'      => (int)$item['is_read'],
                'time_ago'     => $this->timeAgo($item['created_at']),
                'created_at'   => $item['created_at']
            ];
        }, $recent);

        return $this->response->setJSON([
            'status'        => 'success',
            'unread_count'  => $unreadCount,
            'notifications' => $formatted
        ]);
    }

    public function markRead($id)
    {
        if (!session()->get('is_logged_in')) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
            }
            return redirect()->to(base_url('login'));
        }

        $notification = $this->notificationModel->find($id);

        if ($notification) {
            $this->notificationModel->markAsRead($id);
            
            // Clean the link and add base_url
            $link = $this->cleanLink($notification['link'] ?? '');
            
            if (empty($link)) {
                $targetLink = base_url('admin/dashboard');
            } else {
                $targetLink = base_url($link);
            }
        } else {
            $targetLink = base_url('admin/dashboard');
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'       => 'success',
                'unread_count' => $this->notificationModel->getUnreadCount(),
                'target_link'  => $targetLink
            ]);
        }

        return redirect()->to($targetLink);
    }

    public function markAllRead()
    {
        if (!session()->get('is_logged_in')) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
            }
            return redirect()->to(base_url('login'));
        }

        $this->notificationModel->markAllAsRead();

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'       => 'success',
                'unread_count' => 0,
                'message'      => 'All notifications marked as read'
            ]);
        }

        return redirect()->back()->with('success', 'All notifications marked as read');
    }

    public function delete($id)
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('login'));
        }

        $this->notificationModel->delete($id);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Notification deleted successfully'
            ]);
        }

        return redirect()->back()->with('success', 'Notification deleted successfully');
    }

    /**
     * ============================================================
     * RECEPTIONIST NOTIFICATION METHODS
     * ============================================================
     */

    public function receptionistIndex()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('login'));
        }

        $role = session()->get('role');
        if ($role !== 'receptionist') {
            return redirect()->to(base_url('admin/dashboard'));
        }

        $notifications = $this->notificationModel->orderBy('created_at', 'DESC')->findAll();
        
        foreach ($notifications as &$notif) {
            $notif['full_link'] = $this->getFullLink($notif['link'] ?? '');
        }

        $data['notifications'] = $notifications;
        $data['unread_count']  = $this->notificationModel->getUnreadCount();
        $data['total_count']   = count($notifications);

        return view('Receptionist/notifications', $data);
    }

    public function receptionistFetch()
    {
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $role = session()->get('role');
        if ($role !== 'receptionist') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $unreadCount = $this->notificationModel->getUnreadCount();
        $recent      = $this->notificationModel->getRecentNotifications(6);

        $formatted = array_map(function ($item) {
            $link = $this->cleanLink($item['link'] ?? '');
            
            if (!empty($link) && strpos($link, 'admin') !== false) {
                $link = str_replace('admin', 'receptionist', $link);
            }
            
            return [
                'id'           => $item['id'],
                'type'         => $item['type'],
                'title'        => $item['title'],
                'message'      => $item['message'],
                'link'         => base_url($link),
                'is_read'      => (int)$item['is_read'],
                'time_ago'     => $this->timeAgo($item['created_at']),
                'created_at'   => $item['created_at']
            ];
        }, $recent);

        return $this->response->setJSON([
            'status'        => 'success',
            'unread_count'  => $unreadCount,
            'notifications' => $formatted
        ]);
    }

    public function receptionistMarkRead($id)
    {
        if (!session()->get('is_logged_in')) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
            }
            return redirect()->to(base_url('login'));
        }

        $role = session()->get('role');
        if ($role !== 'receptionist') {
            return redirect()->to(base_url('admin/dashboard'));
        }

        $notification = $this->notificationModel->find($id);

        if ($notification) {
            $this->notificationModel->markAsRead($id);
            $link = $this->cleanLink($notification['link'] ?? '');
            
            if (!empty($link) && strpos($link, 'admin') !== false) {
                $link = str_replace('admin', 'receptionist', $link);
            }
            
            if (empty($link)) {
                $targetLink = base_url('receptionist/dashboard');
            } else {
                $targetLink = base_url($link);
            }
        } else {
            $targetLink = base_url('receptionist/dashboard');
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'       => 'success',
                'unread_count' => $this->notificationModel->getUnreadCount(),
                'target_link'  => $targetLink
            ]);
        }

        return redirect()->to($targetLink);
    }

    public function receptionistMarkAllRead()
    {
        if (!session()->get('is_logged_in')) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
            }
            return redirect()->to(base_url('login'));
        }

        $role = session()->get('role');
        if ($role !== 'receptionist') {
            return redirect()->to(base_url('admin/dashboard'));
        }

        $this->notificationModel->markAllAsRead();

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'       => 'success',
                'unread_count' => 0,
                'message'      => 'All notifications marked as read'
            ]);
        }

        return redirect()->back()->with('success', 'All notifications marked as read');
    }

    public function receptionistDelete($id)
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('login'));
        }

        $role = session()->get('role');
        if ($role !== 'receptionist') {
            return redirect()->to(base_url('admin/dashboard'));
        }

        $this->notificationModel->delete($id);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Notification deleted successfully'
            ]);
        }

        return redirect()->back()->with('success', 'Notification deleted successfully');
    }

    /**
     * ============================================================
     * RADIOLOGIST NOTIFICATION METHODS
     * ============================================================
     */

    public function radiologistIndex()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('login'));
        }

        $role = session()->get('role');
        if ($role !== 'radiologist') {
            return redirect()->to(base_url('login'));
        }

        $notifications = $this->notificationModel->orderBy('created_at', 'DESC')->findAll();
        
        foreach ($notifications as &$notif) {
            $notif['full_link'] = $this->getFullLink($notif['link'] ?? '');
        }

        $data['notifications'] = $notifications;
        $data['unread_count']  = $this->notificationModel->getUnreadCount();
        $data['total_count']   = count($notifications);

        return view('Radiologist/notifications', $data);
    }

    public function radiologistFetch()
    {
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $role = session()->get('role');
        if ($role !== 'radiologist') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $unreadCount = $this->notificationModel->getUnreadCount();
        $recent      = $this->notificationModel->getRecentNotifications(6);

        $formatted = array_map(function ($item) {
            $link = $this->cleanLink($item['link'] ?? '');
            
            if (!empty($link)) {
                if (strpos($link, 'admin') !== false) {
                    $link = str_replace('admin', 'radiologist', $link);
                }
                if (strpos($link, 'receptionist') !== false) {
                    $link = str_replace('receptionist', 'radiologist', $link);
                }
            }
            
            return [
                'id'           => $item['id'],
                'type'         => $item['type'],
                'title'        => $item['title'],
                'message'      => $item['message'],
                'link'         => base_url($link),
                'is_read'      => (int)$item['is_read'],
                'time_ago'     => $this->timeAgo($item['created_at']),
                'created_at'   => $item['created_at']
            ];
        }, $recent);

        return $this->response->setJSON([
            'status'        => 'success',
            'unread_count'  => $unreadCount,
            'notifications' => $formatted
        ]);
    }

    public function radiologistMarkRead($id)
    {
        if (!session()->get('is_logged_in')) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
            }
            return redirect()->to(base_url('login'));
        }

        $role = session()->get('role');
        if ($role !== 'radiologist') {
            return redirect()->to(base_url('login'));
        }

        $notification = $this->notificationModel->find($id);

        if ($notification) {
            $this->notificationModel->markAsRead($id);
            $link = $this->cleanLink($notification['link'] ?? '');
            
            if (!empty($link)) {
                if (strpos($link, 'admin') !== false) {
                    $link = str_replace('admin', 'radiologist', $link);
                }
                if (strpos($link, 'receptionist') !== false) {
                    $link = str_replace('receptionist', 'radiologist', $link);
                }
            }
            
            if (empty($link)) {
                $targetLink = base_url('radiologist/dashboard');
            } else {
                $targetLink = base_url($link);
            }
        } else {
            $targetLink = base_url('radiologist/dashboard');
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'       => 'success',
                'unread_count' => $this->notificationModel->getUnreadCount(),
                'target_link'  => $targetLink
            ]);
        }

        return redirect()->to($targetLink);
    }

    public function radiologistMarkAllRead()
    {
        if (!session()->get('is_logged_in')) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
            }
            return redirect()->to(base_url('login'));
        }

        $role = session()->get('role');
        if ($role !== 'radiologist') {
            return redirect()->to(base_url('login'));
        }

        $this->notificationModel->markAllAsRead();

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'       => 'success',
                'unread_count' => 0,
                'message'      => 'All notifications marked as read'
            ]);
        }

        return redirect()->back()->with('success', 'All notifications marked as read');
    }

    public function radiologistDelete($id)
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('login'));
        }

        $role = session()->get('role');
        if ($role !== 'radiologist') {
            return redirect()->to(base_url('login'));
        }

        $this->notificationModel->delete($id);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Notification deleted successfully'
            ]);
        }

        return redirect()->back()->with('success', 'Notification deleted successfully');
    }

    /**
     * ============================================================
     * MED TECH NOTIFICATION METHODS
     * ============================================================
     */

    public function medtechIndex()
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'med_tech') {
            return redirect()->to(base_url('login'));
        }

        $model = new NotificationModel();
        $notifications = $model
            ->orderBy('created_at', 'DESC')
            ->findAll();
        
        $data['notifications'] = $notifications;
        $data['unread_count'] = $model->getUnreadCount();
        $data['total_count'] = count($notifications);

        return view('MedTech/notifications', $data);
    }

    public function medtechFetch()
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'med_tech') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $model = new NotificationModel();
        $unreadCount = $model->getUnreadCount();
        $recent = $model
            ->orderBy('created_at', 'DESC')
            ->findAll(6);

        $formatted = array_map(function ($item) {
            $link = $item['link'] ?? '';
            $link = str_replace('/polymedic/public/', '', $link);
            $link = str_replace('polymedic/public/', '', $link);
            $link = ltrim($link, '/');
            
            if (empty($link)) {
                $link = 'medtech/dashboard';
            }
            
            return [
                'id' => $item['id'],
                'type' => $item['type'],
                'title' => $item['title'],
                'message' => $item['message'],
                'link' => base_url($link),
                'is_read' => (int)$item['is_read'],
                'time_ago' => $this->timeAgo($item['created_at'])
            ];
        }, $recent);

        return $this->response->setJSON([
            'status' => 'success',
            'unread_count' => $unreadCount,
            'notifications' => $formatted
        ]);
    }

    public function medtechMarkRead($id)
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'med_tech') {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
            }
            return redirect()->to(base_url('login'));
        }

        $model = new NotificationModel();
        $notification = $model->find($id);

        if ($notification) {
            $model->markAsRead($id);
            $link = $notification['link'] ?? '';
            $link = str_replace('/polymedic/public/', '', $link);
            $link = str_replace('polymedic/public/', '', $link);
            $link = ltrim($link, '/');
            
            if (empty($link)) {
                $targetLink = base_url('medtech/dashboard');
            } else {
                $targetLink = base_url($link);
            }
        } else {
            $targetLink = base_url('medtech/dashboard');
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status' => 'success',
                'unread_count' => $model->getUnreadCount(),
                'target_link' => $targetLink
            ]);
        }

        return redirect()->to($targetLink);
    }

    public function medtechMarkAllRead()
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'med_tech') {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
            }
            return redirect()->to(base_url('login'));
        }

        $model = new NotificationModel();
        $model->markAllAsRead();

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status' => 'success',
                'unread_count' => 0,
                'message' => 'All notifications marked as read'
            ]);
        }

        return redirect()->back()->with('success', 'All notifications marked as read');
    }

    public function medtechDelete($id)
    {
        if (!session()->get('is_logged_in') || session()->get('role') !== 'med_tech') {
            return redirect()->to(base_url('login'));
        }

        $model = new NotificationModel();
        $model->delete($id);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Notification deleted successfully'
            ]);
        }

        return redirect()->back()->with('success', 'Notification deleted successfully');
    }
}