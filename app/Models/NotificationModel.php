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
        'reference_gender',
        'link',
        'user_role',
        'can_open',
        'is_read',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /** Schema check runs once per request, not once per model instance. */
    private static $schemaChecked = false;

    /**
     * ============================================================
     * 1. AUDIENCE - WHO RECEIVES WHAT
     * ============================================================
     */
    public const AUDIENCE = [
        'appointment' => ['admin' => 'open',   'receptionist' => 'open'],
        'xray'        => ['admin' => 'notify', 'radiologist'  => 'open'],
        'lab'         => ['admin' => 'notify', 'med_tech'     => 'open'],
        'billing'     => ['admin' => 'notify', 'receptionist' => 'open'],
        'payment'     => ['admin' => 'notify', 'receptionist' => 'open'],
        'system'      => ['admin' => 'open'],
    ];

    /**
     * ============================================================
     * 2. ROUTES - WHERE EACH ROLE GOES
     * ============================================================
     */
    public const LINK_MAP = [
        'admin' => [
            'appointment' => 'admin/appointment/view/{id}',
        ],
        'receptionist' => [
            'appointment' => 'receptionist/appointment/view/{id}',
            'billing'     => 'receptionist/billing',
            'payment'     => 'receptionist/payments',
        ],
        'radiologist' => [
            'xray' => 'radiologist/examination/view/{id}',
        ],
        'med_tech' => [
            'lab' => 'medtech/request/view/{id}',
        ],
    ];

    public const DASHBOARDS = [
        'admin'        => 'admin/dashboard',
        'receptionist' => 'receptionist/dashboard',
        'radiologist'  => 'radiologist/dashboard',
        'med_tech'     => 'medtech/dashboard',
    ];

    public const NOTIFICATION_PAGES = [
        'admin'        => 'admin/notifications',
        'receptionist' => 'receptionist/notifications',
        'radiologist'  => 'radiologist/notifications',
        'med_tech'     => 'medtech/notifications',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    /**
     * ============================================================
     * SCHEMA - creates the table and adds missing columns
     * ============================================================
     */
    public function ensureSchema()
    {
        if (self::$schemaChecked) {
            return;
        }
        self::$schemaChecked = true;

        try {
            $db    = Database::connect();
            $forge = Database::forge();

            if (!$db->tableExists('notifications')) {
                $forge->addField([
                    'id' => [
                        'type'           => 'INT',
                        'constraint'     => 11,
                        'unsigned'       => true,
                        'auto_increment' => true,
                    ],
                    'type'             => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'system'],
                    'title'            => ['type' => 'VARCHAR', 'constraint' => 255],
                    'message'          => ['type' => 'TEXT'],
                    'reference_id'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                    'reference_gender' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
                    'link'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                    'user_role'        => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'admin'],
                    'can_open'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                    'is_read'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                    'created_at'       => ['type' => 'DATETIME', 'null' => true],
                    'updated_at'       => ['type' => 'DATETIME', 'null' => true],
                ]);
                $forge->addKey('id', true);
                $forge->addKey(['user_role', 'is_read']);
                $forge->addKey(['type', 'reference_id']);
                $forge->createTable('notifications', true);
                return;
            }

            // Table exists - add the new columns if they are missing.
            $columns = $db->getFieldNames('notifications');

            if (!in_array('user_role', $columns, true)) {
                $forge->addColumn('notifications', [
                    'user_role' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 30,
                        'default'    => 'admin',
                        'after'      => 'link',
                    ],
                ]);
                log_message('info', 'notifications: added user_role column');
            }

            if (!in_array('can_open', $columns, true)) {
                $forge->addColumn('notifications', [
                    'can_open' => [
                        'type'       => 'TINYINT',
                        'constraint' => 1,
                        'default'    => 1,
                        'after'      => 'user_role',
                    ],
                ]);
                log_message('info', 'notifications: added can_open column');
            }

            // NEW: gender for patient-related notifications (male/female/null).
            if (!in_array('reference_gender', $columns, true)) {
                $forge->addColumn('notifications', [
                    'reference_gender' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 10,
                        'null'       => true,
                        'after'      => 'reference_id',
                    ],
                ]);
                log_message('info', 'notifications: added reference_gender column');
            }
        } catch (\Exception $e) {
            log_message('error', 'Notification schema setup error: ' . $e->getMessage());
        }
    }

    /**
     * ============================================================
     * ROLE HELPERS
     * ============================================================
     */

    public function currentRole(): string
    {
        return (string) (session()->get('role') ?? '');
    }

    public function hasRoute(string $role, string $type): bool
    {
        return isset(self::LINK_MAP[$role][$type]);
    }

    public function pathFor(string $role, string $type, $referenceId = null): ?string
    {
        if (!$this->hasRoute($role, $type)) {
            return null;
        }

        $path = self::LINK_MAP[$role][$type];

        if (strpos($path, '{id}') !== false) {
            if ($referenceId === null || $referenceId === '') {
                return null;
            }
            $path = str_replace('{id}', rawurlencode((string) $referenceId), $path);
        }

        return $path;
    }

    public function resolveLink(array $notification, ?string $role = null): ?string
    {
        $role = $role ?? ($notification['user_role'] ?? $this->currentRole());

        if (isset($notification['user_role']) && $notification['user_role'] !== $role) {
            return null;
        }

        if ((int) ($notification['can_open'] ?? 0) !== 1) {
            return null;
        }

        $path = $this->pathFor($role, $notification['type'] ?? 'system', $notification['reference_id'] ?? null);

        return $path === null ? null : base_url($path);
    }

    public function dashboardFor(?string $role = null): string
    {
        $role = $role ?? $this->currentRole();
        return base_url(self::DASHBOARDS[$role] ?? 'login');
    }

    public function notificationsPageFor(?string $role = null): string
    {
        $role = $role ?? $this->currentRole();
        return base_url(self::NOTIFICATION_PAGES[$role] ?? 'login');
    }

    /**
     * ============================================================
     * DISPATCH - the only way notifications should be created
     * ============================================================
     * Fans one event out to every role in the AUDIENCE map, one row
     * each, with the correct can_open flag per role.
     *
     * @param array|null  $audience        Override, e.g. ['admin' => 'notify']
     * @param string|null $referenceGender 'male' | 'female' | null
     */
    public static function dispatch(
        string $type,
        string $title,
        string $message,
        $referenceId = null,
        ?array $audience = null,
        bool $skipDuplicates = true,
        ?string $referenceGender = null
    ): bool {
        try {
            $model   = new self();
            $targets = $audience ?? (self::AUDIENCE[$type] ?? self::AUDIENCE['system']);
            $allOk   = true;

            // Normalize gender to 'male' / 'female' / null only.
            $gender = null;
            if ($referenceGender !== null && $referenceGender !== '') {
                $g = strtolower(trim((string) $referenceGender));
                if (in_array($g, ['male', 'm', 'man', 'boy'], true)) {
                    $gender = 'male';
                } elseif (in_array($g, ['female', 'f', 'woman', 'girl'], true)) {
                    $gender = 'female';
                }
            }

            foreach ($targets as $role => $mode) {
                if ($skipDuplicates && $referenceId !== null
                    && $model->duplicateExists($type, $referenceId, $role, $title)) {
                    continue;
                }

                $canOpen = ($mode === 'open' && $model->hasRoute($role, $type)) ? 1 : 0;

                $inserted = $model->insert([
                    'type'             => $type,
                    'title'            => $title,
                    'message'          => $message,
                    'reference_id'     => $referenceId !== null ? (string) $referenceId : null,
                    'reference_gender' => $gender,
                    'link'             => $canOpen ? $model->pathFor($role, $type, $referenceId) : null,
                    'user_role'        => $role,
                    'can_open'         => $canOpen,
                    'is_read'          => 0,
                ]);

                if (!$inserted) {
                    $allOk = false;
                    log_message('error', 'Notification insert failed for role ' . $role . ' / type ' . $type);
                }
            }

            return $allOk;
        } catch (\Exception $e) {
            log_message('error', 'Failed to dispatch notification: ' . $e->getMessage());
            return false;
        }
    }

    private function duplicateExists(string $type, $referenceId, string $role, string $title): bool
    {
        return $this->where('type', $type)
                    ->where('reference_id', (string) $referenceId)
                    ->where('user_role', $role)
                    ->where('title', $title)
                    ->countAllResults() > 0;
    }

    /**
     * Backwards-compatible entry point.
     */
    public static function notify(string $type, string $title, string $message, $referenceId = null, $link = null)
    {
        return self::dispatch($type, $title, $message, $referenceId);
    }

    /**
     * ============================================================
     * SCOPED QUERIES - always filtered by role
     * ============================================================
     */

    public function getUnreadCount(?string $role = null): int
    {
        $role = $role ?? $this->currentRole();
        if ($role === '') {
            return 0;
        }

        return $this->where('user_role', $role)
                    ->where('is_read', 0)
                    ->countAllResults();
    }

    public function getAllForRole(?string $role = null): array
    {
        $role = $role ?? $this->currentRole();
        if ($role === '') {
            return [];
        }

        $rows = $this->where('user_role', $role)
                     ->orderBy('created_at', 'DESC')
                     ->orderBy('id', 'DESC')
                     ->findAll();

        return $this->decorate($rows, $role);
    }

    public function getRecentNotifications(int $limit = 5, ?string $role = null): array
    {
        $role = $role ?? $this->currentRole();
        if ($role === '') {
            return [];
        }

        $rows = $this->where('user_role', $role)
                     ->orderBy('created_at', 'DESC')
                     ->orderBy('id', 'DESC')
                     ->findAll($limit);

        return $this->decorate($rows, $role);
    }

    public function findForRole($id, ?string $role = null): ?array
    {
        $role = $role ?? $this->currentRole();
        $row  = $this->find($id);

        if (!$row || ($row['user_role'] ?? null) !== $role) {
            return null;
        }

        return $row;
    }

    public function decorate(array $rows, ?string $role = null): array
    {
        $role = $role ?? $this->currentRole();

        foreach ($rows as &$row) {
            $target            = $this->resolveLink($row, $role);
            $row['full_link']  = $target;
            $row['can_open']   = $target !== null;
            $row['icon']       = $this->getTypeIcon($row['type'] ?? 'system');
            $row['color']      = $this->getTypeColor($row['type'] ?? 'system');
            $row['time_ago']   = $this->timeAgo($row['created_at'] ?? null);
        }
        unset($row);

        return $rows;
    }

    /**
     * ============================================================
     * MUTATIONS - ownership enforced
     * ============================================================
     */

    public function markAsRead($id, ?string $role = null): bool
    {
        $role = $role ?? $this->currentRole();

        if (!$this->findForRole($id, $role)) {
            return false;
        }

        return (bool) $this->update($id, ['is_read' => 1]);
    }

    public function markAllAsRead(?string $role = null): bool
    {
        $role = $role ?? $this->currentRole();
        if ($role === '') {
            return false;
        }

        return (bool) $this->where('user_role', $role)
                           ->where('is_read', 0)
                           ->set(['is_read' => 1])
                           ->update();
    }

    public function deleteForRole($id, ?string $role = null): bool
    {
        $role = $role ?? $this->currentRole();

        if (!$this->findForRole($id, $role)) {
            return false;
        }

        return (bool) $this->delete($id);
    }

    /**
     * ============================================================
     * SYNC + PRESENTATION
     * ============================================================
     */

    public function autoSyncPendingAppointments()
    {
        try {
            $db = Database::connect();
            if (!$db->tableExists('appointments')) {
                return;
            }

            $apptModel = new AppointmentModel();
            $pending   = $apptModel->where('status', 'pending')->findAll();

            foreach ($pending as $appt) {
                self::dispatch(
                    'appointment',
                    'Pending Appointment: ' . ($appt['reference_number'] ?? ('#' . $appt['id'])),
                    'Appointment request from ' . $appt['full_name'] . ' on ' . date('M d, Y', strtotime($appt['appointment_date'])),
                    $appt['id'],
                    null,
                    true,
                    $appt['gender'] ?? null
                );
            }
        } catch (\Exception $e) {
            log_message('error', 'Auto sync notifications error: ' . $e->getMessage());
        }
    }

    public function timeAgo($datetime): string
    {
        if (!$datetime) {
            return 'Just now';
        }

        $diff = time() - strtotime($datetime);

        if ($diff < 60) {
            return 'Just now';
        }
        if ($diff < 3600) {
            $m = floor($diff / 60);
            return $m . ($m == 1 ? ' min ago' : ' mins ago');
        }
        if ($diff < 86400) {
            $h = floor($diff / 3600);
            return $h . ($h == 1 ? ' hour ago' : ' hours ago');
        }
        if ($diff < 604800) {
            $d = floor($diff / 86400);
            return $d . ($d == 1 ? ' day ago' : ' days ago');
        }

        return date('M d, Y', strtotime($datetime));
    }

    public function getTypeIcon($type): string
    {
        $icons = [
            'appointment' => 'bi-calendar-check',
            'xray'        => 'bi-x-ray',
            'lab'         => 'bi-flask',
            'billing'     => 'bi-receipt',
            'payment'     => 'bi-credit-card',
            'system'      => 'bi-bell-fill',
        ];
        return $icons[$type] ?? 'bi-bell-fill';
    }

    public function getTypeColor($type): string
    {
        $colors = ['appointment', 'xray', 'lab', 'billing', 'payment', 'system'];
        return in_array($type, $colors, true) ? $type : 'system';
    }

    public function getNotificationsForJson($limit = 6, ?string $role = null): array
    {
        $role = $role ?? $this->currentRole();

        $out = [];
        foreach ($this->getRecentNotifications($limit, $role) as $item) {
            $out[] = [
                'id'         => $item['id'],
                'type'       => $item['type'],
                'title'      => $item['title'],
                'message'    => $item['message'],
                'link'       => $item['full_link'],
                'can_open'   => $item['can_open'],
                'is_read'    => (int) $item['is_read'],
                'time_ago'   => $item['time_ago'],
                'created_at' => $item['created_at'],
            ];
        }

        return $out;
    }

    /** @deprecated kept so old calls do not fatal */
    public function getDynamicLink($appointmentId)
    {
        return $this->pathFor($this->currentRole(), 'appointment', $appointmentId);
    }

    /** @deprecated kept so old calls do not fatal */
    public function getFullLink($link)
    {
        return empty($link) ? $this->dashboardFor() : base_url(ltrim($link, '/'));
    }

    /** @deprecated use dispatch() */
    public function createAppointmentNotification($appointmentId, $fullName, $referenceNumber, $appointmentDate, $gender = null)
    {
        return self::dispatch(
            'appointment',
            'Pending Appointment: ' . $referenceNumber,
            'New appointment request from ' . $fullName . ' on ' . date('M d, Y', strtotime($appointmentDate)),
            $appointmentId,
            null,
            true,
            $gender
        );
    }

    /** @deprecated use dispatch() */
    public function createXrayNotification($examinationId, $patientName, $status, $link = null)
    {
        $labels = ['pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'released' => 'Released'];
        $label  = $labels[$status] ?? ucfirst($status);

        return self::dispatch(
            'xray',
            'X-Ray Examination: ' . $label,
            'X-Ray examination for ' . $patientName . ' is now ' . strtolower($label),
            $examinationId
        );
    }
}