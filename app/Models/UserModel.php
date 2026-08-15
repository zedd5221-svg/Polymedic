<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    // Reference the users table in user_management database
    protected $table = 'user_management.users';
    protected $primaryKey = 'id';
    protected $allowedFields = ['username', 'password', 'email', 'full_name', 'role', 'status'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $DBGroup = 'default'; // Use default connection (polymedic_db)

    public function __construct()
    {
        parent::__construct();
        // Set the database to use polymedic_db but reference user_management.users
        // This works because both are on the same MySQL server
        $this->db = \Config\Database::connect();
    }

    // Get user by username
    public function getUserByUsername($username)
    {
        return $this->where('username', $username)->first();
    }

    // Verify login credentials
    public function verifyLogin($username, $password)
    {
        $user = $this->getUserByUsername($username);
        if ($user && $user['password'] === md5($password)) {
            return $user;
        }
        return false;
    }

    // Get all users
    public function getUsers($limit = null, $offset = 0)
    {
        if ($limit) {
            return $this->limit($limit, $offset)->findAll();
        }
        return $this->findAll();
    }

    // Count total users
    public function countUsers()
    {
        return $this->countAll();
    }

    // Create user with MD5 hashed password
    public function createUser($data)
    {
        if (isset($data['password'])) {
            $data['password'] = md5($data['password']);
        }
        return $this->insert($data);
    }

    // Update user with MD5 hashed password if provided
    public function updateUser($id, $data)
    {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = md5($data['password']);
        } elseif (isset($data['password'])) {
            unset($data['password']);
        }
        return $this->update($id, $data);
    }

    // Update user status
    public function updateStatus($id, $status)
    {
        return $this->update($id, ['status' => $status]);
    }

    // Delete user
    public function deleteUser($id)
    {
        return $this->delete($id);
    }

    // Get users by role
    public function getUsersByRole($role)
    {
        return $this->where('role', $role)->findAll();
    }

    // Search users
    public function searchUsers($keyword)
    {
        return $this->like('username', $keyword)
                    ->orLike('full_name', $keyword)
                    ->orLike('email', $keyword)
                    ->findAll();
    }
}