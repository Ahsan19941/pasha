<?php
// File: app/models/ActivityLogModel.php
// Activity Log model for the PASHA Benefits Portal

namespace app\models;

use app\core\Model;

class ActivityLogModel extends Model {
    /**
     * @var string Table name
     */
    protected $table = 'activity_logs';
    
    /**
     * Get all logs with pagination
     * 
     * @param int $page Page number
     * @param int $limit Items per page
     * @param string $search Search term
     * @param int $userId User ID filter
     * @param string $entityType Entity type filter
     * @param string $dateFrom Date from filter
     * @param string $dateTo Date to filter
     * @return array Logs and pagination data
     */
    public function getAllWithPagination($page = 1, $limit = ITEMS_PER_PAGE, $search = null, $userId = null, $entityType = null, $dateFrom = null, $dateTo = null) {
        $page = max(1, (int) $page);
        $limit = (int) $limit;
        $offset = ($page - 1) * $limit;
        
        // Build the query
        $sql = "SELECT l.*, u.email as user_email, u.first_name, u.last_name, u.role 
                FROM {$this->table} l
                LEFT JOIN users u ON l.user_id = u.id";
        
        $conditions = [];
        $params = [];
        
        // Add search condition
        if ($search) {
            $conditions[] = "(l.action LIKE ? OR l.details LIKE ?)";
            $searchTerm = "%{$search}%";
            $params = [$searchTerm, $searchTerm];
        }
        
        // Add user filter
        if ($userId) {
            $conditions[] = "l.user_id = ?";
            $params[] = $userId;
        }
        
        // Add entity type filter
        if ($entityType) {
            $conditions[] = "l.entity_type = ?";
            $params[] = $entityType;
        }
        
        // Add date from filter
        if ($dateFrom) {
            $conditions[] = "DATE(l.created_at) >= ?";
            $params[] = $dateFrom;
        }
        
        // Add date to filter
        if ($dateTo) {
            $conditions[] = "DATE(l.created_at) <= ?";
            $params[] = $dateTo;
        }
        
        // Add WHERE clause if there are conditions
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        // Add order by
        $sql .= " ORDER BY l.created_at DESC";
        
        // Add limit and offset
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        // Execute the query
        $logs = $this->query($sql, $params);
        
        // Count total records for pagination
        $countSql = "SELECT COUNT(*) FROM {$this->table} l";
        
        if (!empty($conditions)) {
            $countSql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $stmt = $this->db->prepare($countSql);
        $stmt->execute(array_slice($params, 0, -2)); // Remove limit and offset parameters
        $total = (int) $stmt->fetchColumn();
        
        // Calculate pagination data
        $totalPages = ceil($total / $limit);
        
        return [
            'logs' => $logs,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $totalPages
            ]
        ];
    }
    
    /**
     * Get recent activity
     * 
     * @param int $limit Number of logs to get
     * @param int $userId User ID filter
     * @return array Activity logs
     */
    public function getRecentActivity($limit = 10, $userId = null) {
        $sql = "SELECT l.*, u.email as user_email, u.first_name, u.last_name, u.role 
                FROM {$this->table} l
                LEFT JOIN users u ON l.user_id = u.id";
        
        $params = [];
        
        if ($userId) {
            $sql .= " WHERE l.user_id = ?";
            $params[] = $userId;
        }
        
        $sql .= " ORDER BY l.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        return $this->query($sql, $params);
    }
    
    /**
     * Get activity count by entity type
     * 
     * @param string $dateFrom Date from filter
     * @param string $dateTo Date to filter
     * @return array Activity counts
     */
    public function getActivityCountByEntityType($dateFrom = null, $dateTo = null) {
        $sql = "SELECT entity_type, COUNT(*) as count 
                FROM {$this->table}
                WHERE entity_type IS NOT NULL";
        
        $params = [];
        
        if ($dateFrom) {
            $sql .= " AND DATE(created_at) >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND DATE(created_at) <= ?";
            $params[] = $dateTo;
        }
        
        $sql .= " GROUP BY entity_type ORDER BY count DESC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * Get activity count by user
     * 
     * @param string $dateFrom Date from filter
     * @param string $dateTo Date to filter
     * @param int $limit Number of users to get
     * @return array Activity counts
     */
    public function getActivityCountByUser($dateFrom = null, $dateTo = null, $limit = 5) {
        $sql = "SELECT l.user_id, u.email, u.first_name, u.last_name, u.role, COUNT(*) as count 
                FROM {$this->table} l
                JOIN users u ON l.user_id = u.id
                WHERE l.user_id IS NOT NULL";
        
        $params = [];
        
        if ($dateFrom) {
            $sql .= " AND DATE(l.created_at) >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND DATE(l.created_at) <= ?";
            $params[] = $dateTo;
        }
        
        $sql .= " GROUP BY l.user_id ORDER BY count DESC LIMIT ?";
        $params[] = $limit;
        
        return $this->query($sql, $params);
    }
    
    /**
     * Get activity count by date
     * 
     * @param int $days Number of days to get
     * @return array Activity counts
     */
    public function getActivityCountByDate($days = 30) {
        $sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM {$this->table}
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
        
        return $this->query($sql, [$days]);
    }
}
