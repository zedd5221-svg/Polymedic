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
     * Display all notifications in Admin panel.
     */
    public function index()
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/polymedic/public/login');
        }

        $data['notifications'] = $this->notificationModel->orderBy('created_at', 'DESC')->findAll();
        $data['unread_count']  = $this->notificationModel->getUnreadCount();
        $data['total_count']   = count($data['notifications']);

        return view('Admin/notifications', $data);
    }

    /**
     * AJAX Endpoint: Fetch latest unread count and notifications for bell dropdown.
     */
    public function fetch()
    {
        $unreadCount = $this->notificationModel->getUnreadCount();
        $recent      = $this->notificationModel->getRecentNotifications(6);

        $formatted = array_map(function ($item) {
            return [
                'id'           => $item['id'],
                'type'         => $item['type'],
                'title'        => $item['title'],
                'message'      => $item['message'],
                'link'         => $item['link'] ?? '/polymedic/public/admin/appointments',
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
     * Mark single notification as read and redirect or return JSON.
     */
    public function markRead($id)
    {
        if (!session()->get('is_logged_in')) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
            }
            return redirect()->to('/polymedic/public/login');
        }

        $notification = $this->notificationModel->find($id);

        if ($notification) {
            $this->notificationModel->markAsRead($id);
            $targetLink = $notification['link'] ?: '/polymedic/public/admin/appointments';
        } else {
            $targetLink = '/polymedic/public/admin/appointments';
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
     * Mark all notifications as read.
     */
    public function markAllRead()
    {
        if (!session()->get('is_logged_in')) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
            }
            return redirect()->to('/polymedic/public/login');
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
     * Delete a notification.
     */
    public function delete($id)
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/polymedic/public/login');
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
