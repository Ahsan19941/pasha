<?php
// File: app/models/PartnerModel.php
// Partner model for the PASHA Benefits Portal

namespace app\models;

use app\core\Model;

class PartnerModel extends Model {
    /**
     * @var string Table name
     */
    protected $table = 'partners';
    
    /**
     * Get all partners with pagination
     * 
     * @param int $page Page number
     * @param int $limit Items per page
     * @param string $search Search term
     * @param string $status Status filter
     * @return array Partners and pagination data
     */
    public function getAllWithPagination($page = 1, $limit = ITEMS_PER_PAGE, $search = null, $status = null) {
        $page = max(1, (int) $page);
        $limit = (int) $limit;
        $offset = ($page - 1) * $limit;
        
        // Build the query
        $sql = "SELECT p.*, COUNT(o.id) as offer_count 
                FROM {$this->table} p
                LEFT JOIN offers o ON p.id = o.partner_id";
        
        $conditions = [];
        $params = [];
        
        // Add search condition
        if ($search) {
            $conditions[] = "(p.name LIKE ? OR p.contact_person LIKE ? OR p.email LIKE ?)";
            $searchTerm = "%{$search}%";
            $params = [$searchTerm, $searchTerm, $searchTerm];
        }
        
        // Add status filter
        if ($status) {
            $conditions[] = "p.status = ?";
            $params[] = $status;
        }
        
        // Add WHERE clause if there are conditions
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        // Add GROUP BY
        $sql .= " GROUP BY p.id";
        
        // Add order by
        $sql .= " ORDER BY p.name ASC";
        
        // Add limit and offset
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        // Execute the query
        $partners = $this->query($sql, $params);
        
        // Count total records for pagination
        $countSql = "SELECT COUNT(*) FROM {$this->table} p";
        
        if (!empty($conditions)) {
            $countSql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $stmt = $this->db->prepare($countSql);
        $stmt->execute(array_slice($params, 0, -2)); // Remove limit and offset parameters
        $total = (int) $stmt->fetchColumn();
        
        // Calculate pagination data
        $totalPages = ceil($total / $limit);
        
        return [
            'partners' => $partners,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $totalPages
            ]
        ];
    }
    
    /**
     * Get partner with offers count
     * 
     * @param int $id Partner ID
     * @return array|null Partner data or null if not found
     */
    public function getWithOffersCount($id) {
        $sql = "SELECT p.*, COUNT(o.id) as offer_count 
                FROM {$this->table} p
                LEFT JOIN offers o ON p.id = o.partner_id
                WHERE p.id = ?
                GROUP BY p.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Get all active partners for dropdown lists
     * 
     * @return array Partners (id => name)
     */
    public function getActivePartnersForDropdown() {
        $sql = "SELECT id, name FROM {$this->table} WHERE status = 'active' ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $partners = [];
        while ($row = $stmt->fetch()) {
            $partners[$row['id']] = $row['name'];
        }
        
        return $partners;
    }
    
    /**
     * Update partner status
     * 
     * @param int $id Partner ID
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
     * Get partner statistics
     * 
     * @return array Statistics
     */
    public function getStatistics() {
        $stats = [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'with_offers' => 0,
            'without_offers' => 0,
            'top_partners' => []
        ];
        
        // Total partners
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['total'] = (int) $stmt->fetchColumn();
        
        // Active partners
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['active'] = (int) $stmt->fetchColumn();
        
        // Inactive partners
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE status = 'inactive'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['inactive'] = (int) $stmt->fetchColumn();
        
        // Partners with offers
        $sql = "SELECT COUNT(DISTINCT partner_id) FROM offers";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['with_offers'] = (int) $stmt->fetchColumn();
        
        // Partners without offers
        $stats['without_offers'] = $stats['total'] - $stats['with_offers'];
        
        // Top partners by number of offers
        $sql = "SELECT p.id, p.name, COUNT(o.id) as offer_count 
                FROM {$this->table} p
                JOIN offers o ON p.id = o.partner_id
                GROUP BY p.id
                ORDER BY offer_count DESC
                LIMIT 5";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['top_partners'] = $stmt->fetchAll();
        
        return $stats;
    }
}
