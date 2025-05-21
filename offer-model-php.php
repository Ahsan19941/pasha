<?php
// File: app/models/OfferModel.php
// Offer model for the PASHA Benefits Portal

namespace app\models;

use app\core\Model;

class OfferModel extends Model {
    /**
     * @var string Table name
     */
    protected $table = 'offers';
    
    /**
     * Get all offers with pagination
     * 
     * @param int $page Page number
     * @param int $limit Items per page
     * @param string $search Search term
     * @param string $status Status filter
     * @param int $partnerId Partner ID filter
     * @param string $category Category filter
     * @return array Offers and pagination data
     */
    public function getAllWithPagination($page = 1, $limit = ITEMS_PER_PAGE, $search = null, $status = null, $partnerId = null, $category = null) {
        $page = max(1, (int) $page);
        $limit = (int) $limit;
        $offset = ($page - 1) * $limit;
        
        // Build the query to get offers with partner information
        $sql = "SELECT o.*, p.name as partner_name, p.logo_url as partner_logo 
                FROM {$this->table} o
                LEFT JOIN partners p ON o.partner_id = p.id";
        
        $conditions = [];
        $params = [];
        
        // Add search condition
        if ($search) {
            $conditions[] = "(o.title LIKE ? OR o.description LIKE ? OR p.name LIKE ?)";
            $searchTerm = "%{$search}%";
            $params = [$searchTerm, $searchTerm, $searchTerm];
        }
        
        // Add status filter
        if ($status) {
            $conditions[] = "o.status = ?";
            $params[] = $status;
        }
        
        // Add partner filter
        if ($partnerId) {
            $conditions[] = "o.partner_id = ?";
            $params[] = $partnerId;
        }
        
        // Add category filter
        if ($category) {
            $conditions[] = "o.category = ?";
            $params[] = $category;
        }
        
        // Add WHERE clause if there are conditions
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        // Add order by
        $sql .= " ORDER BY o.start_date DESC";
        
        // Add limit and offset
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        // Execute the query
        $offers = $this->query($sql, $params);
        
        // Count total records for pagination
        $countSql = "SELECT COUNT(*) FROM {$this->table} o
                     LEFT JOIN partners p ON o.partner_id = p.id";
        
        if (!empty($conditions)) {
            $countSql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $stmt = $this->db->prepare($countSql);
        $stmt->execute(array_slice($params, 0, -2)); // Remove limit and offset parameters
        $total = (int) $stmt->fetchColumn();
        
        // Calculate pagination data
        $totalPages = ceil($total / $limit);
        
        return [
            'offers' => $offers,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $totalPages
            ]
        ];
    }
    
    /**
     * Get all active offers for public display
     * 
     * @param int $page Page number
     * @param int $limit Items per page
     * @param string $search Search term
     * @param string $category Category filter
     * @return array Offers and pagination data
     */
    public function getPublicOffers($page = 1, $limit = ITEMS_PER_PAGE, $search = null, $category = null) {
        // Only show active offers that are currently valid
        $status = 'active';
        $currentDate = date('Y-m-d');
        
        $page = max(1, (int) $page);
        $limit = (int) $limit;
        $offset = ($page - 1) * $limit;
        
        // Build the query
        $sql = "SELECT o.*, p.name as partner_name, p.logo_url as partner_logo, p.website as partner_website
                FROM {$this->table} o
                LEFT JOIN partners p ON o.partner_id = p.id
                WHERE o.status = ? 
                AND (o.start_date <= ? AND (o.end_date IS NULL OR o.end_date >= ?))";
        
        $params = [$status, $currentDate, $currentDate];
        
        // Add search condition
        if ($search) {
            $sql .= " AND (o.title LIKE ? OR o.description LIKE ? OR p.name LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Add category filter
        if ($category) {
            $sql .= " AND o.category = ?";
            $params[] = $category;
        }
        
        // Add order by
        $sql .= " ORDER BY o.start_date DESC";
        
        // Add limit and offset
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        // Execute the query
        $offers = $this->query($sql, $params);
        
        // Count total records for pagination
        $countSql = "SELECT COUNT(*) FROM {$this->table} o
                     LEFT JOIN partners p ON o.partner_id = p.id
                     WHERE o.status = ? 
                     AND (o.start_date <= ? AND (o.end_date IS NULL OR o.end_date >= ?))";
        
        $countParams = [$status, $currentDate, $currentDate];
        
        if ($search) {
            $countSql .= " AND (o.title LIKE ? OR o.description LIKE ? OR p.name LIKE ?)";
            $countParams[] = $searchTerm;
            $countParams[] = $searchTerm;
            $countParams[] = $searchTerm;
        }
        
        if ($category) {
            $countSql .= " AND o.category = ?";
            $countParams[] = $category;
        }
        
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($countParams);
        $total = (int) $stmt->fetchColumn();
        
        // Calculate pagination data
        $totalPages = ceil($total / $limit);
        
        return [
            'offers' => $offers,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $totalPages
            ]
        ];
    }
    
    /**
     * Get a single offer with partner information
     * 
     * @param int $id Offer ID
     * @param bool $publicOnly Whether to only get active offers
     * @return array|null Offer data or null if not found
     */
    public function getWithPartner($id, $publicOnly = false) {
        $sql = "SELECT o.*, p.name as partner_name, p.logo_url as partner_logo, 
                p.website as partner_website, p.contact_person as partner_contact, 
                p.email as partner_email, p.phone as partner_phone
                FROM {$this->table} o
                LEFT JOIN partners p ON o.partner_id = p.id
                WHERE o.id = ?";
        
        $params = [$id];
        
        if ($publicOnly) {
            $currentDate = date('Y-m-d');
            $sql .= " AND o.status = 'active' 
                    AND (o.start_date <= ? AND (o.end_date IS NULL OR o.end_date >= ?))";
            $params[] = $currentDate;
            $params[] = $currentDate;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Get all categories
     * 
     * @return array Categories
     */
    public function getAllCategories() {
        $sql = "SELECT DISTINCT category FROM {$this->table} ORDER BY category ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $categories = [];
        while ($row = $stmt->fetch()) {
            $categories[] = $row['category'];
        }
        
        return $categories;
    }
    
    /**
     * Update offer status
     * 
     * @param int $id Offer ID
     * @param string $status New status (active, inactive, draft)
     * @return bool True on success, false on failure
     */
    public function updateStatus($id, $status) {
        $validStatuses = ['active', 'inactive', 'draft'];
        
        if (!in_array($status, $validStatuses)) {
            return false;
        }
        
        return $this->update($id, ['status' => $status]);
    }
    
    /**
     * Get offers statistics
     * 
     * @return array Statistics
     */
    public function getStatistics() {
        $currentDate = date('Y-m-d');
        $stats = [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'draft' => 0,
            'expired' => 0,
            'by_category' => []
        ];
        
        // Total offers
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['total'] = (int) $stmt->fetchColumn();
        
        // Active offers
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['active'] = (int) $stmt->fetchColumn();
        
        // Inactive offers
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE status = 'inactive'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['inactive'] = (int) $stmt->fetchColumn();
        
        // Draft offers
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE status = 'draft'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['draft'] = (int) $stmt->fetchColumn();
        
        // Expired offers (end date in the past)
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE status = 'active' AND end_date IS NOT NULL AND end_date < ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$currentDate]);
        $stats['expired'] = (int) $stmt->fetchColumn();
        
        // Offers by category
        $sql = "SELECT category, COUNT(*) as count FROM {$this->table} GROUP BY category ORDER BY count DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $categories = $stmt->fetchAll();
        
        foreach ($categories as $category) {
            $stats['by_category'][$category['category']] = (int) $category['count'];
        }
        
        return $stats;
    }
}
