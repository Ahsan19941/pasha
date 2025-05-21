<?php
// File: app/models/UserModel.php
// User model for the PASHA Benefits Portal

namespace app\models;

use app\core\Model;

class UserModel extends Model {
    /**
     * @var string Table name
     */
    protected $table = 'users';
    
    /**
     * Authenticate a user
     * 
     * @param string $email User email
     * @param string $password User password
     * @return array|bool User data if authentication successful, false otherwise
     */
    public function authenticate($email, $password) {
        // Find user by email
        $user = $this->findOneBy('email', $email);
        
        // Check if user exists and is active
        if (!$user || $user['status'] !== 'active') {
            return false;
        }
        
        // Verify password
        if (!verifyPassword($password, $user['password'])) {
            return false;
        }
        
        // Update last login time
        $this->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);
        
        // Remove password from the user data
        unset($user['password']);
        
        return $user;
    }
    
    /**
     * Get all users with pagination
     * 
     * @param int $page Page number
     * @param int $limit Items per page
     * @param string $search Search term
     * @param string $role Role filter
     * @return array Users and pagination data
     */
    public function getAllWithPagination($page = 1, $limit = ITEMS_PER_PAGE, $search = null, $role = null) {
        $page = max(1, (int) $page);
        $limit = (int) $limit;
        $offset = ($page - 1) * $limit;
        
        // Build the query
        $sql = "SELECT u.*, p.name as partner_name 
                FROM {$this->table} u
                LEFT JOIN partners p ON u.partner_id = p.id";
        
        $conditions = [];
        $params = [];
        
        // Add search condition
        if ($search) {
            $conditions[] = "(u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
            $searchTerm = "%{$search}%";
            $params = [$searchTerm, $searchTerm, $searchTerm];
        }
        
        // Add role filter
        if ($role) {
            $conditions[] = "u.role = ?";
            $params[] = $role;
        }
        
        // Add WHERE clause if there are conditions
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        // Add order by
        $sql .= " ORDER BY u.last_name ASC, u.first_name ASC";
        
        // Add limit and offset
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        // Execute the query
        $users = $this->query($sql, $params);
        
        // Count total records for pagination
        $countSql = "SELECT COUNT(*) FROM {$this->table} u
                     LEFT JOIN partners p ON u.partner_id = p.id";
        
        if (!empty($conditions)) {
            $countSql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $stmt = $this->db->prepare($countSql);
        $stmt->execute(array_slice($params, 0, -2)); // Remove limit and offset parameters
        $total = (int) $stmt->fetchColumn();
        
        // Calculate pagination data
        $totalPages = ceil($total / $limit);
        
        return [
            'users' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $totalPages
            ]
        ];
    }
    
    /**
     * Create a new user
     * 
     * @param array $data User data
     * @return int|bool The last inserted ID or false on failure
     */
    public function createUser($data) {
        // Hash the password
        if (isset($data['password'])) {
            $data['password'] = hashPassword($data['password']);
        }
        
        return $this->create($data);
    }
    
    /**
     * Update a user
     * 
     * @param int $id User ID
     * @param array $data User data
     * @return bool True on success, false on failure
     */
    public function updateUser($id, $data) {
        // Hash the password if it's being updated
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = hashPassword($data['password']);
        } else {
            // Remove password from the data if it's empty
            unset($data['password']);
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * Get user with partner information
     * 
     * @param int $id User ID
     * @return array|null User data or null if not found
     */
    public function getWithPartner($id) {
        $sql = "SELECT u.*, p.name as partner_name 
                FROM {$this->table} u
                LEFT JOIN partners p ON u.partner_id = p.id
                WHERE u.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Update user status
     * 
     * @param int $id User ID
     * @param string $status New status (active, inactive)
     * @return bool True on success, false on failure
     */
    public function updateStatus($id, $status) {
        $validStatuses = ['active', 'inactive'];
        
        if (!in_array($status, $validStatuses)) {
            return false;
        }
        
        return $this->update($id, ['status' => $status]);
    }
    
    /**
     * Create a password reset token
     * 
     * @param string $email User email
     * @return array|bool Reset data if successful, false otherwise
     */
    public function createPasswordResetToken($email) {
        // Find user by email
        $user = $this->findOneBy('email', $email);
        
        if (!$user || $user['status'] !== 'active') {
            return false;
        }
        
        // Generate a token
        $token = randomString(64);
        $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour from now
        
        // Store the token in the password_resets table
        $sql = "INSERT INTO password_resets (email, token, created_at, expires_at) 
                VALUES (?, ?, NOW(), ?)
                ON DUPLICATE KEY UPDATE token = ?, created_at = NOW(), expires_at = ?";
        
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([$email, $token, $expiry, $token, $expiry]);
        
        if (!$success) {
            return false;
        }
        
        return [
            'user' => $user,
            'token' => $token,
            'expiry' => $expiry
        ];
    }
    
    /**
     * Verify a password reset token
     * 
     * @param string $token Reset token
     * @return array|bool User data if token is valid, false otherwise
     */
    public function verifyPasswordResetToken($token) {
        $sql = "SELECT pr.*, u.id as user_id, u.first_name, u.last_name 
                FROM password_resets pr
                JOIN users u ON pr.email = u.email
                WHERE pr.token = ? AND pr.expires_at > NOW()";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$token]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return false;
        }
        
        return $result;
    }
    
    /**
     * Reset password using a token
     * 
     * @param string $token Reset token
     * @param string $password New password
     * @return bool True on success, false on failure
     */
    public function resetPassword($token, $password) {
        // Verify the token
        $tokenData = $this->verifyPasswordResetToken($token);
        
        if (!$tokenData) {
            return false;
        }
        
        // Update the password
        $hashedPassword = hashPassword($password);
        $userId = $tokenData['user_id'];
        
        $success = $this->update($userId, ['password' => $hashedPassword]);
        
        if ($success) {
            // Delete the token
            $sql = "DELETE FROM password_resets WHERE token = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$token]);
        }
        
        return $success;
    }
}
