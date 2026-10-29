<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'username', 'password', 'email', 'full_name', 'role', 'prc_license', 'status',
        // Self-service profile fields (see Profile controller)
        'contact_number', 'gender', 'birthdate', 'address', 'bio', 'avatar',
        'last_login_at', 'password_changed_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $DBGroup = 'default';

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /* Get user by username */
    public function getUserByUsername($username)
    {
        return $this->where('username', $username)->first();
    }

    /* Verify login credentials */
    public function verifyLogin($username, $password)
    {
        $user = $this->getUserByUsername($username);

        if ($user) {
            /* MD5 legacy upgrade */
            if (hash_equals((string) $user['password'], md5((string) $password))) {
                $this->update($user['id'], ['password' => password_hash($password, PASSWORD_DEFAULT)]);
                return $user;
            }

            /* bcrypt */
            if (password_verify($password, $user['password'])) {
                return $user;
            }
        }

        return false;
    }

    /* Get all users */
    public function getUsers($limit = null, $offset = 0)
    {
        if ($limit) {
            return $this->limit($limit, $offset)->findAll();
        }
        return $this->findAll();
    }

    /* Count total users */
    public function countUsers()
    {
        return $this->countAll();
    }

    /* Create user with bcrypt hashed password */
    public function createUser($data)
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return $this->insert($data);
    }

    /* Update user with bcrypt hashed password if provided */
    public function updateUser($id, $data)
    {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } elseif (isset($data['password'])) {
            unset($data['password']);
        }
        return $this->update($id, $data);
    }

    /* Update user status */
    public function updateStatus($id, $status)
    {
        return $this->update($id, ['status' => $status]);
    }

    /* Delete user */
    public function deleteUser($id)
    {
        return $this->delete($id);
    }

    /* Get users by role */
    public function getUsersByRole($role)
    {
        return $this->where('role', $role)->findAll();
    }

    /*
     * Roles that must carry a PRC license number.
     * Receptionists and administrators are not licensed by the PRC
     * for the purposes of this system, so their records may leave
     * prc_license empty.
     */
    public function requiresPrcLicense(string $role): bool
    {
        return in_array($role, ['med_tech', 'radiologist'], true);
    }

    /* Human-readable role label */
    public static function roleLabel(?string $role): string
    {
        return [
            'admin'        => 'Administrator',
            'receptionist' => 'Receptionist',
            'radiologist'  => 'Radiologist',
            'med_tech'     => 'Medical Technologist',
            'physician'    => 'Physician',
        ][$role] ?? ucfirst((string) $role);
    }

    /* Public URL of a user's avatar, or the default placeholder */
    public static function avatarUrl(?string $avatar): string
    {
        if ($avatar && is_file(FCPATH . $avatar)) {
            return base_url($avatar);
        }
        return base_url('assets/images/default-avatar.png');
    }

    /** Diagnostic request type each clinical role reads. */
    public const STAFF_DEPARTMENT = ['med_tech' => 'lab', 'radiologist' => 'xray'];

    /**
     * Active clinical staff offered as "Referring physician" choices on the
     * new diagnostic request form. 'dept' is the request type they serve
     * (med tech -> lab, radiologist -> xray); the form only offers the
     * staff whose dept matches the services picked.
     *
     * @return list<array{id: int, name: string, role: string, role_label: string, dept: string, prc_license: string}>
     */
    public function getReferringStaff(): array
    {
        $rows = $this->select('id, full_name, role, prc_license')
                     ->whereIn('role', array_keys(self::STAFF_DEPARTMENT))
                     ->where('status', 'active')
                     ->where('full_name IS NOT NULL')
                     ->where('full_name <>', '')
                     ->orderBy("FIELD(role, 'med_tech', 'radiologist')", '', false)
                     ->orderBy('full_name', 'ASC')
                     ->findAll();

        return array_map(static fn(array $u): array => [
            'id'          => (int) $u['id'],
            'name'        => trim((string) $u['full_name']),
            'role'        => (string) $u['role'],
            'role_label'  => self::roleLabel($u['role']),
            'dept'        => self::STAFF_DEPARTMENT[$u['role']],
            'prc_license' => trim((string) ($u['prc_license'] ?? '')),
        ], $rows);
    }

    /* Search users */
    public function searchUsers($keyword)
    {
        return $this->like('username', $keyword)
                    ->orLike('full_name', $keyword)
                    ->orLike('email', $keyword)
                    ->findAll();
    }
}