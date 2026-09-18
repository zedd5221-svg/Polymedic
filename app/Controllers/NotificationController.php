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
     * GUARD
     * ============================================================
     * Exact role match. No admin bypass - an admin hitting the
     * radiologist endpoints is sent back to the admin dashboard.
     */
    private function guard(string $requiredRole)
    {
        $isAjax = $this->request->isAJAX();

        if (!session()->get('is_logged_in')) {
            return $isAjax
                ? $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized'])
                : redirect()->to(base_url('login'));
        }

        if (session()->get('role') !== $requiredRole) {
            return $isAjax
                ? $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden'])
                : redirect()->to($this->notificationModel->dashboardFor(session()->get('role')))
                            ->with('error', 'You do not have access to that page.');
        }

        return null;
    }

    /**
     * ============================================================
     * SHARED IMPLEMENTATIONS
     * ============================================================
     */

    private function listFor(string $role, string $view)
    {
        $notifications = $this->notificationModel->getAllForRole($role);

        return view($view, [
            'notifications' => $notifications,
            'unread_count'  => $this->notificationModel->getUnreadCount($role),
            'unreadCount'   => $this->notificationModel->getUnreadCount($role), // legacy view var
            'total_count'   => count($notifications),
            'notif_base'    => NotificationModel::NOTIFICATION_PAGES[$role] ?? '',
        ]);
    }

    private function fetchFor(string $role)
    {
        return $this->response->setJSON([
            'status'        => 'success',
            'unread_count'  => $this->notificationModel->getUnreadCount($role),
            'notifications' => $this->notificationModel->getNotificationsForJson(6, $role),
        ]);
    }

    /**
     * Mark read, then redirect. Rows belonging to another role are
     * treated as not found. Notify-only rows are marked read but
     * never redirect anywhere.
     */
    private function markReadFor(string $role, $id)
    {
        $notification = $this->notificationModel->findForRole($id, $role);
        $ownPage      = $this->notificationModel->notificationsPageFor($role);

        if (!$notification) {
            return $this->request->isAJAX()
                ? $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Notification not found'])
                : redirect()->to($ownPage)->with('error', 'Notification not found.');
        }

        $this->notificationModel->markAsRead($id, $role);
        $target = $this->notificationModel->resolveLink($notification, $role);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'       => 'success',
                'unread_count' => $this->notificationModel->getUnreadCount($role),
                'can_open'     => $target !== null,
                'target_link'  => $target,
            ]);
        }

        if ($target === null) {
            return redirect()->to($ownPage)->with('success', 'Marked as read.');
        }

        return redirect()->to($target);
    }

    private function markAllReadFor(string $role)
    {
        $this->notificationModel->markAllAsRead($role);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'       => 'success',
                'unread_count' => 0,
                'message'      => 'All notifications marked as read',
            ]);
        }

        return redirect()->to($this->notificationModel->notificationsPageFor($role))
                         ->with('success', 'All notifications marked as read');
    }

    private function deleteFor(string $role, $id)
    {
        $ok = $this->notificationModel->deleteForRole($id, $role);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'  => $ok ? 'success' : 'error',
                'message' => $ok ? 'Notification deleted' : 'Notification not found',
            ]);
        }

        return redirect()->to($this->notificationModel->notificationsPageFor($role))
                         ->with($ok ? 'success' : 'error', $ok ? 'Notification deleted successfully' : 'Notification not found.');
    }

    /**
     * ============================================================
     * ADMIN
     * ============================================================
     */
    public function index()
    {
        if ($r = $this->guard('admin')) return $r;
        return $this->listFor('admin', 'Admin/notifications');
    }

    public function fetch()
    {
        if ($r = $this->guard('admin')) return $r;
        return $this->fetchFor('admin');
    }

    public function markRead($id)
    {
        if ($r = $this->guard('admin')) return $r;
        return $this->markReadFor('admin', $id);
    }

    public function markAllRead()
    {
        if ($r = $this->guard('admin')) return $r;
        return $this->markAllReadFor('admin');
    }

    public function delete($id)
    {
        if ($r = $this->guard('admin')) return $r;
        return $this->deleteFor('admin', $id);
    }

    /**
     * ============================================================
     * RECEPTIONIST
     * ============================================================
     */
    public function receptionistIndex()
    {
        if ($r = $this->guard('receptionist')) return $r;
        return $this->listFor('receptionist', 'Receptionist/notifications');
    }

    public function receptionistFetch()
    {
        if ($r = $this->guard('receptionist')) return $r;
        return $this->fetchFor('receptionist');
    }

    public function receptionistMarkRead($id)
    {
        if ($r = $this->guard('receptionist')) return $r;
        return $this->markReadFor('receptionist', $id);
    }

    public function receptionistMarkAllRead()
    {
        if ($r = $this->guard('receptionist')) return $r;
        return $this->markAllReadFor('receptionist');
    }

    public function receptionistDelete($id)
    {
        if ($r = $this->guard('receptionist')) return $r;
        return $this->deleteFor('receptionist', $id);
    }

    /**
     * ============================================================
     * RADIOLOGIST
     * ============================================================
     */
    public function radiologistIndex()
    {
        if ($r = $this->guard('radiologist')) return $r;
        return $this->listFor('radiologist', 'Radiologist/notifications');
    }

    public function radiologistFetch()
    {
        if ($r = $this->guard('radiologist')) return $r;
        return $this->fetchFor('radiologist');
    }

    public function radiologistMarkRead($id)
    {
        if ($r = $this->guard('radiologist')) return $r;
        return $this->markReadFor('radiologist', $id);
    }

    public function radiologistMarkAllRead()
    {
        if ($r = $this->guard('radiologist')) return $r;
        return $this->markAllReadFor('radiologist');
    }

    public function radiologistDelete($id)
    {
        if ($r = $this->guard('radiologist')) return $r;
        return $this->deleteFor('radiologist', $id);
    }

    /**
     * ============================================================
     * MED TECH
     * ============================================================
     */
    public function medtechIndex()
    {
        if ($r = $this->guard('med_tech')) return $r;
        return $this->listFor('med_tech', 'MedTech/notifications');
    }

    public function medtechFetch()
    {
        if ($r = $this->guard('med_tech')) return $r;
        return $this->fetchFor('med_tech');
    }

    public function medtechMarkRead($id)
    {
        if ($r = $this->guard('med_tech')) return $r;
        return $this->markReadFor('med_tech', $id);
    }

    public function medtechMarkAllRead()
    {
        if ($r = $this->guard('med_tech')) return $r;
        return $this->markAllReadFor('med_tech');
    }

    public function medtechDelete($id)
    {
        if ($r = $this->guard('med_tech')) return $r;
        return $this->deleteFor('med_tech', $id);
    }
}