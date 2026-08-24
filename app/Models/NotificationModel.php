<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Database;

class NotificationModel extends Model
{
    protected $table            = 'notifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'type',
        'title',
        'message',
        'reference_id',
        'link',
        'is_read',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTableExists();
    }

    /**
     * Ensure the notifications table exists automatically.
     */
    public function ensureTableExists()
    {
        try {
            $db = Database::connect();
            if (!$db->tableExists('notifications')) {
                $forge = Database::forge();
                $forge->addField([
                    'id' => [
                        'type'           => 'INT',
                        'constraint'     => 11,
                        'unsigned'       => true,
                        'auto_increment' => true,
                    ],
                    'type' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 50,
                        'default'    => 'system',
                    ],
                    'title' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 255,
                    ],
                    'message' => [
                        'type' => 'TEXT',
                    ],
                    'reference_id' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 50,
                        'null'       => true,
                    ],
                    'link' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 255,
                        'null'       => true,
                    ],
                    'is_read' => [
                        'type'       => 'TINYINT',
                        'constraint' => 1,
                        'default'    => 0,
                    ],
                    'created_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                    ],
                    'updated_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                    ],
                ]);
                $forge->addKey('id', true);
                $forge->addKey('type');
                $forge->addKey('is_read');
                $forge->createTable('notifications', true);
            }
        } catch (\Exception $e) {
            log_message('error', 'Notification table setup error: ' . $e->getMessage());
        }
    }

    /**
     * Clean a link - remove base URL and extra slashes
     */
    private function cleanLink($link)
    {
        if (empty($link)) {
            return null;
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
        
        return empty($link) ? null : $link;
    }

    /**
     * Easy static helper to create notifications from anywhere in the application.
     * Now cleans links before storing.
     */
    public static function notify(string $type, string $title, string $message, $referenceId = null, $link = null)
    {
        try {
            $model = new self();
            
            // Clean the link before storing
            $cleanLink = $model->cleanLink($link);
            
            return $model->insert([
                'type'         => $type,
                'title'        => $title,
                'message'      => $message,
                'reference_id' => $referenceId ? (string)$referenceId : null,
                'link'         => $cleanLink,
                'is_read'      => 0,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to create notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get full URL from stored link.
     */
    public function getFullLink($link)
    {
        if (empty($link)) {
            return base_url('admin/dashboard');
        }
        
        // Clean the link first
        $cleaned = $this->cleanLink($link);
        if (empty($cleaned)) {
            return base_url('admin/dashboard');
        }
        
        return base_url($cleaned);
    }

    /**
     * Get count of unread notifications.
     */
    public function getUnreadCount(): int
    {
        return $this->where('is_read', 0)->countAllResults();
    }

    /**
     * Get recent notifications for dropdown.
     */
    public function getRecentNotifications(int $limit = 5): array
    {
        $this->autoSyncPendingAppointments();
        $notifications = $this->orderBy('created_at', 'DESC')
                              ->orderBy('id', 'DESC')
                              ->findAll($limit);
        
        // Add full links
        foreach ($notifications as &$notif) {
            $notif['full_link'] = $this->getFullLink($notif['link'] ?? '');
        }
        
        return $notifications;
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead($id): bool
    {
        return $this->update($id, ['is_read' => 1]);
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(): bool
    {
        return $this->where('is_read', 0)->set(['is_read' => 1])->update();
    }

    /**
     * Get the appropriate link based on user role (stores only the path)
     */
    public function getDynamicLink($appointmentId)
    {
        $session = session();
        $role = $session->get('role');
        
        // Store only the path, not the full URL
        if ($role === 'admin') {
            return 'admin/appointment/view/' . $appointmentId;
        } elseif ($role === 'receptionist') {
            return 'receptionist/appointment/view/' . $appointmentId;
        } elseif ($role === 'radiologist') {
            return 'radiologist/examination/view/' . $appointmentId;
        }
        
        return 'admin/appointment/view/' . $appointmentId;
    }

    /**
     * Create notification for new appointment with role-based link
     */
    public function createAppointmentNotification($appointmentId, $fullName, $referenceNumber, $appointmentDate)
    {
        $link = $this->getDynamicLink($appointmentId);
        
        return $this->insert([
            'type'         => 'appointment',
            'title'        => 'Pending Appointment: ' . $referenceNumber,
            'message'      => 'New appointment request from ' . $fullName . ' on ' . date('M d, Y', strtotime($appointmentDate)),
            'reference_id' => (string)$appointmentId,
            'link'         => $link,
            'is_read'      => 0,
        ]);
    }

    /**
     * Create notification for X-Ray examination
     */
    public function createXrayNotification($examinationId, $patientName, $status, $link = null)
    {
        if (!$link) {
            $link = 'radiologist/examination/view/' . $examinationId;
        }
        
        $statusLabels = [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'released' => 'Released'
        ];
        
        $statusLabel = $statusLabels[$status] ?? ucfirst($status);
        
        return $this->insert([
            'type'         => 'xray',
            'title'        => 'X-Ray Examination: ' . $statusLabel,
            'message'      => 'X-Ray examination for ' . $patientName . ' is now ' . strtolower($statusLabel),
            'reference_id' => (string)$examinationId,
            'link'         => $link,
            'is_read'      => 0,
        ]);
    }

    /**
     * Auto sync existing pending appointments into notifications table if missing.
     */
    public function autoSyncPendingAppointments()
    {
        try {
            $db = Database::connect();
            if (!$db->tableExists('appointments')) {
                return;
            }

            $apptModel = new AppointmentModel();
            $pendingAppts = $apptModel->where('status', 'pending')->findAll();

            foreach ($pendingAppts as $appt) {
                $exists = $this->where('type', 'appointment')
                               ->where('reference_id', (string)$appt['id'])
                               ->first();

                if (!$exists) {
                    $link = 'admin/appointment/view/' . $appt['id'];
                    
                    $this->insert([
                        'type'         => 'appointment',
                        'title'        => 'Pending Appointment: ' . ($appt['reference_number'] ?? ('#' . $appt['id'])),
                        'message'      => 'New appointment request from ' . $appt['full_name'] . ' on ' . date('M d, Y', strtotime($appt['appointment_date'])),
                        'reference_id' => (string)$appt['id'],
                        'link'         => $link,
                        'is_read'      => 0,
                        'created_at'   => $appt['created_at'] ?? date('Y-m-d H:i:s'),
                        'updated_at'   => date('Y-m-d H:i:s')
                    ]);
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Auto sync notifications error: ' . $e->getMessage());
        }
    }

    /**
     * Get notification type icon class
     */
    public function getTypeIcon($type)
    {
        $icons = [
            'appointment' => 'bi-calendar-check',
            'xray' => 'bi-x-ray',
            'system' => 'bi-bell-fill',
            'lab' => 'bi-flask',
            'billing' => 'bi-receipt',
            'payment' => 'bi-credit-card'
        ];
        return $icons[$type] ?? 'bi-bell-fill';
    }

    /**
     * Get notification type color class
     */
    public function getTypeColor($type)
    {
        $colors = [
            'appointment' => 'appointment',
            'xray' => 'xray',
            'system' => 'system',
            'lab' => 'lab',
            'billing' => 'billing',
            'payment' => 'payment'
        ];
        return $colors[$type] ?? 'system';
    }

    /**
     * Get notifications with full links for JSON response
     */
    public function getNotificationsForJson($limit = 6): array
    {
        $notifications = $this->orderBy('created_at', 'DESC')
                              ->orderBy('id', 'DESC')
                              ->findAll($limit);
        
        $formatted = [];
        foreach ($notifications as $item) {
            $formatted[] = [
                'id'           => $item['id'],
                'type'         => $item['type'],
                'title'        => $item['title'],
                'message'      => $item['message'],
                'link'         => $this->getFullLink($item['link'] ?? ''),
                'is_read'      => (int)$item['is_read'],
                'created_at'   => $item['created_at']
            ];
        }
        
        return $formatted;
    }
}