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
     * ADMIN NOTIFICATION METHODS
     * ============================================================
     */

    /**
     * Display all notifications in Admin panel.
     */
    public function index()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('login'));
        }

        $data['notifications'] = $this->notificationModel->orderBy('created_at', 'DESC')->findAll();
        $data['unread_count']  = $this->notificationModel->getUnreadCount();
        $data['total_count']   = count($data['notifications']);

        return view('Admin/notifications', $data);
    }

    /**
     * AJAX Endpoint: Fetch latest unread count and notifications for Admin bell dropdown.
     */
    public function fetch()
    {
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $unreadCount = $this->notificationModel->getUnreadCount();
        $recent      = $this->notificationModel->getRecentNotifications(6);

        $formatted = array_map(function ($item) {
            $link = $item['link'] ?? 'admin/appointments';
            
            // Clean the link
            $link = str_replace('/polymedic/public/', '', $link);
            $link = str_replace('polymedic/public/', '', $link);
            $link = str_replace('/public/', '', $link);
            $link = str_replace('/index.php/', '', $link);
            $link = ltrim($link, '/');
            
            if (empty($link)) {
                $link = 'admin/appointments';
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

    /**
     * Mark single notification as read and redirect or return JSON for Admin.
     */
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
            $link = $notification['link'] ?? 'admin/appointments';
            
            // Clean the link
            $link = str_replace('/polymedic/public/', '', $link);
            $link = str_replace('polymedic/public/', '', $link);
            $link = str_replace('/public/', '', $link);
            $link = str_replace('/index.php/', '', $link);
            $link = ltrim($link, '/');
            
            if (empty($link)) {
                $link = 'admin/appointments';
            }
            
            $targetLink = base_url($link);
        } else {
            $targetLink = base_url('admin/appointments');
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

    /**
     * Mark all notifications as read for Admin.
     */
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

    /**
     * Delete a notification for Admin.
     */
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

    /**
     * Display all notifications for Receptionist panel.
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

        $data['notifications'] = $this->notificationModel->orderBy('created_at', 'DESC')->findAll();
        $data['unread_count']  = $this->notificationModel->getUnreadCount();
        $data['total_count']   = count($data['notifications']);

        return view('Receptionist/notifications', $data);
    }

    /**
     * AJAX Endpoint: Fetch notifications for Receptionist bell dropdown.
     */
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
            $link = $item['link'] ?? 'receptionist/appointments';
            
            // Clean the link
            $link = str_replace('/polymedic/public/', '', $link);
            $link = str_replace('polymedic/public/', '', $link);
            $link = str_replace('/public/', '', $link);
            $link = str_replace('/index.php/', '', $link);
            $link = ltrim($link, '/');
            
            if (empty($link)) {
                $link = 'receptionist/appointments';
            }
            
            // Make sure it uses receptionist route
            if (strpos($link, 'admin') !== false) {
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

    /**
     * Mark single notification as read for Receptionist.
     */
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
            $link = $notification['link'] ?? '';
            
            // Clean the link
            $link = str_replace('/polymedic/public/', '', $link);
            $link = str_replace('polymedic/public/', '', $link);
            $link = str_replace('/public/', '', $link);
            $link = str_replace('/index.php/', '', $link);
            $link = ltrim($link, '/');
            
            // If link is empty, redirect to receptionist appointments
            if (empty($link)) {
                $targetLink = base_url('receptionist/appointments');
            } else {
                // Make sure the link uses receptionist route
                if (strpos($link, 'admin') !== false) {
                    $link = str_replace('admin', 'receptionist', $link);
                }
                $targetLink = base_url($link);
            }
        } else {
            $targetLink = base_url('receptionist/appointments');
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

    /**
     * Mark all notifications as read for Receptionist.
     */
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

    /**
     * Delete a notification for Receptionist.
     */
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
     * HELPER METHODS
     * ============================================================
     */

    /**
     * Helper to format time into "time ago" string.
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
}