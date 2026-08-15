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
     * Easy static helper to create notifications from anywhere in the application.
     */
    public static function notify(string $type, string $title, string $message, $referenceId = null, $link = null)
    {
        try {
            $model = new self();
            return $model->insert([
                'type'         => $type,
                'title'        => $title,
                'message'      => $message,
                'reference_id' => $referenceId ? (string)$referenceId : null,
                'link'         => $link,
                'is_read'      => 0,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to create notification: ' . $e->getMessage());
            return false;
        }
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
        return $this->orderBy('created_at', 'DESC')
                    ->orderBy('id', 'DESC')
                    ->findAll($limit);
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
        }
        
        // Default fallback for public (admin)
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
                    // Store only the path
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
}