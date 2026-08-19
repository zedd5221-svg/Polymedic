<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    // =============================================
    // CHANGED: Removed 'user_management.' prefix
    // Now uses 'users' table in default database
    // =============================================
    protected $table = 'users';  // ← CHANGED: was 'user_management.users'
    protected $primaryKey = 'id';
    protected $allowedFields = ['username', 'password', 'email', 'full_name', 'role', 'status'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $DBGroup = 'default'; // Uses polymedic_db

    public function __construct()
    {
        parent::__construct();
        // No longer need to specify database prefix
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
        
        // Check MD5 (legacy) or bcrypt (new)
        if ($user) {
            // Check if password matches MD5 (old users)
            if ($user['password'] === md5($password)) {
                // Upgrade to bcrypt on successful login
                $this->update($user['id'], ['password' => password_hash($password, PASSWORD_DEFAULT)]);
                return $user;
            }
            
            // Check if password matches bcrypt (new users)
            if (password_verify($password, $user['password'])) {
                return $user;
            }
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

    // Create user with bcrypt hashed password (upgraded from MD5)
    public function createUser($data)
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return $this->insert($data);
    }

    // Update user with bcrypt hashed password if provided
    public function updateUser($id, $data)
    {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
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