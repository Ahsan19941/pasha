<?php
// File: app/models/MemberModel.php
// Member model for the PASHA Benefits Portal

namespace app\models;

use app\core\Model;

class MemberModel extends Model {
    /**
     * @var string Table name
     */
    protected $table = 'members';
    
    /**
     * Get all members with pagination
     * 
     * @param int $page Page number
     * @param int $limit Items per page
     * @param string $search Search term
     * @param string $status Status filter
     * @return array Members and pagination data
     */
    public function getAllWithPagination($page = 1, $limit = ITEMS_PER_PAGE, $search = null, $status = null) {
        $page = max(1, (int) $page);
        $limit = (int) $limit;
        $offset = ($page - 1) * $limit;
        
        // Build the query
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        // Add search condition
        if ($search) {
            $sql .= " WHERE (company_name LIKE ? OR membership_id LIKE ? OR contact_person LIKE ? OR email LIKE ?)";
            $searchTerm = "%{$search}%";
            $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
            
            if ($status) {
                $sql .= " AND membership_status = ?";
                $params[] = $status;
            }
        } elseif ($status) {
            $sql .= " WHERE membership_status = ?";
            $params[] = $status;
        }
        
        // Add order by
        $sql .= " ORDER BY company_name ASC";
        
        // Add limit and offset
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        // Execute the query
        $members = $this->query($sql, $params);
        
        // Count total records for pagination
        $countSql = "SELECT COUNT(*) FROM {$this->table}";
        $countParams = [];
        
        if ($search) {
            $countSql .= " WHERE (company_name LIKE ? OR membership_id LIKE ? OR contact_person LIKE ? OR email LIKE ?)";
            $searchTerm = "%{$search}%";
            $countParams = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
            
            if ($status) {
                $countSql .= " AND membership_status = ?";
                $countParams[] = $status;
            }
        } elseif ($status) {
            $countSql .= " WHERE membership_status = ?";
            $countParams[] = $status;
        }
        
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($countParams);
        $total = (int) $stmt->fetchColumn();
        
        // Calculate pagination data
        $totalPages = ceil($total / $limit);
        
        return [
            'members' => $members,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $totalPages
            ]
        ];
    }
    
    /**
     * Verify member by ID or company name
     * 
     * @param string $type Verification type (id or company_name)
     * @param string $value Verification value
     * @param int $verifiedBy User ID who performed the verification
     * @return array Verification result
     */
    public function verifyMember($type, $value, $verifiedBy = null) {
        // Determine the field to search by
        $field = $type === 'id' ? 'membership_id' : 'company_name';
        
        // Find the member
        $member = $this->findOneBy($field, $value);
        
        // Prepare verification result
        $result = [
            'success' => false,
            'member' => null,
            'message' => 'Member not found'
        ];
        
        if ($member) {
            $result['success'] = true;
            $result['member'] = $member;
            $result['message'] = 'Member verification successful';
            
            // Check if membership is active
            if ($member['membership_status'] !== 'active') {
                $result['success'] = false;
                $result['message'] = 'Membership is not active';
            }
        }
        
        // Log the verification
        $this->logVerification($type, $value, $verifiedBy, $result['success'], $member ? $member['id'] : null);
        
        return $result;
    }
    
    /**
     * Log member verification
     * 
     * @param string $method Verification method (id or company_name)
     * @param string $input Verification input value
     * @param int $verifiedBy User ID who performed the verification
     * @param bool $success Verification success
     * @param int $memberId Member ID (if found)
     * @return bool True on success, false on failure
     */
    public function logVerification($method, $input, $verifiedBy, $success, $memberId = null) {
        $sql = "INSERT INTO verifications (member_id, verified_by, verification_method, 
                verification_input, verification_result, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $params = [
            $memberId,
            $verifiedBy,
            $method,
            $input,
            $success ? 'success' : 'failed',
            getUserIp()
        ];
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Get verification history
     * 
     * @param int $memberId Member ID
     * @param int $limit Limit
     * @return array Verification history
     */
    public function getVerificationHistory($memberId, $limit = 10) {
        $sql = "SELECT v.*, u.email as verified_by_user 
                FROM verifications v 
                LEFT JOIN users u ON v.verified_by = u.id 
                WHERE v.member_id = ? 
                ORDER BY v.verification_date DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$memberId, $limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Update member status
     * 
     * @param int $id Member ID
     * @param string $status New status (active, inactive, pending)
     * @return bool True on success, false on failure
     */
    public function updateStatus($id, $status) {
        $validStatuses = ['active', 'inactive', 'pending'];
        
        if (!in_array($status, $validStatuses)) {
            return false;
        }
        
        return $this->update($id, ['membership_status' => $status]);
    }
    
    /**
     * Get expiring memberships
     * 
     * @param int $days Number of days to check
     * @return array Expiring memberships
     */
    public function getExpiringMemberships($days = 30) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE membership_status = 'active' 
                AND expiry_date IS NOT NULL 
                AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY) 
                ORDER BY expiry_date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get members statistics
     * 
     * @return array Statistics
     */
    public function getStatistics() {
        $stats = [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'pending' => 0,
            'expiring_soon' => 0,
            'recently_added' => 0
        ];
        
        // Total members
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['total'] = (int) $stmt->fetchColumn();
        
        // Active members
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE membership_status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['active'] = (int) $stmt->fetchColumn();
        
        // Inactive members
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE membership_status = 'inactive'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['inactive'] = (int) $stmt->fetchColumn();
        
        // Pending members
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE membership_status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['pending'] = (int) $stmt->fetchColumn();
        
        // Expiring soon (within 30 days)
        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE membership_status = 'active' 
                AND expiry_date IS NOT NULL 
                AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['expiring_soon'] = (int) $stmt->fetchColumn();
        
        // Recently added (last 30 days)
        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE joining_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['recently_added'] = (int) $stmt->fetchColumn();
        
        return $stats;
    }
}
