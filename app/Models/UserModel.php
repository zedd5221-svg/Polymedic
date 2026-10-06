<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = ['username', 'password', 'email', 'full_name', 'role', 'prc_license', 'status'];
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
            if ($user['password'] === md5($password)) {
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

    /* Search users */
    public function searchUsers($keyword)
    {
        return $this->like('username', $keyword)
                    ->orLike('full_name', $keyword)
                    ->orLike('email', $keyword)
                    ->findAll();
    }
}