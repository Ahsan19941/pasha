<?php
// File: app/controllers/ReportController.php
// Report Controller for the PASHA Benefits Portal

namespace app\controllers;

use app\core\Controller;
use app\models\MemberModel;
use app\models\OfferModel;
use app\models\PartnerModel;

class ReportController extends Controller {
    /**
     * @var MemberModel Member model
     */
    private $memberModel;
    
    /**
     * @var OfferModel Offer model
     */
    private $offerModel;
    
    /**
     * @var PartnerModel Partner model
     */
    private $partnerModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->memberModel = new MemberModel();
        $this->offerModel = new OfferModel();
        $this->partnerModel = new PartnerModel();
    }
    
    /**
     * Display reports dashboard
     * 
     * @return void
     */
    public function index() {
        // Require admin or staff role
        $this->requireRole(['admin', 'staff']);
        
        $this->render('admin/reports/index');
    }
    
    /**
     * Generate members report
     * 
     * @return void
     */
    public function membersReport() {
        // Require admin or staff role
        $this->requireRole(['admin', 'staff']);
        
        // Get filter parameters
        $status = $this->input('status');
        $dateFrom = $this->input('date_from');
        $dateTo = $this->input('date_to');
        
        // Build query
        $sql = "SELECT * FROM members WHERE 1=1";
        $params = [];
        
        if ($status) {
            $sql .= " AND membership_status = ?";
            $params[] = $status;
        }
        
        if ($dateFrom) {
            $sql .= " AND joining_date >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND joining_date <= ?";
            $params[] = $dateTo;
        }
        
        $sql .= " ORDER BY company_name ASC";
        
        // Execute query
        $members = $this->memberModel->query($sql, $params);
        
        // Get statistics
        $stats = [
            'total' => count($members),
            'active' => 0,
            'inactive' => 0,
            'pending' => 0
        ];
        
        foreach ($members as $member) {
            $stats[$member['membership_status']]++;
        }
        
        $this->render('admin/reports/members', [
            'members' => $members,
            'stats' => $stats,
            'status' => $status,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ]);
    }
    
    /**
     * Generate verifications report
     * 
     * @return void
     */
    public function verificationsReport() {
        // Require admin or staff role
        $this->requireRole(['admin', 'staff']);
        
        // Get filter parameters
        $dateFrom = $this->input('date_from');
        $dateTo = $this->input('date_to');
        $result = $this->input('result');
        
        // Build query
        $sql = "SELECT v.*, m.company_name, m.membership_id, u.email as user_email 
                FROM verifications v
                LEFT JOIN members m ON v.member_id = m.id
                LEFT JOIN users u ON v.verified_by = u.id
                WHERE 1=1";
        $params = [];
        
        if ($dateFrom) {
            $sql .= " AND DATE(v.verification_date) >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND DATE(v.verification_date) <= ?";
            $params[] = $dateTo;
        }
        
        if ($result) {
            $sql .= " AND v.verification_result = ?";
            $params[] = $result;
        }
        
        $sql .= " ORDER BY v.verification_date DESC";
        
        // Execute query
        $verifications = $this->memberModel->query($sql, $params);
        
        // Get statistics
        $stats = [
            'total' => count($verifications),
            'success' => 0,
            'failed' => 0,
            'by_method' => [
                'id' => 0,
                'company_name' => 0
            ]
        ];
        
        foreach ($verifications as $verification) {
            $stats[$verification['verification_result']]++;
            $stats['by_method'][$verification['verification_method']]++;
        }
        
        $this->render('admin/reports/verifications', [
            'verifications' => $verifications,
            'stats' => $stats,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'result' => $result
        ]);
    }
    
    /**
     * Generate offers report
     * 
     * @return void
     */
    public function offersReport() {
        // Require admin or staff role
        $this->requireRole(['admin', 'staff']);
        
        // Get filter parameters
        $status = $this->input('status');
        $category = $this->input('category');
        $partnerId = (int) $this->input('partner_id');
        
        // Build query
        $sql = "SELECT o.*, p.name as partner_name 
                FROM offers o
                LEFT JOIN partners p ON o.partner_id = p.id
                WHERE 1=1";
        $params = [];
        
        if ($status) {
            $sql .= " AND o.status = ?";
            $params[] = $status;
        }
        
        if ($category) {
            $sql .= " AND o.category = ?";
            $params[] = $category;
        }
        
        if ($partnerId) {
            $sql .= " AND o.partner_id = ?";
            $params[] = $partnerId;
        }
        
        $sql .= " ORDER BY o.start_date DESC";
        
        // Execute query
        $offers = $this->offerModel->query($sql, $params);
        
        // Get statistics
        $stats = [
            'total' => count($offers),
            'active' => 0,
            'inactive' => 0,
            'draft' => 0,
            'by_category' => []
        ];
        
        foreach ($offers as $offer) {
            $stats[$offer['status']]++;
            
            if (!isset($stats['by_category'][$offer['category']])) {
                $stats['by_category'][$offer['category']] = 0;
            }
            $stats['by_category'][$offer['category']]++;
        }
        
        // Sort categories by count
        arsort($stats['by_category']);
        
        // Get partners for dropdown
        $partners = $this->partnerModel->getActivePartnersForDropdown();
        
        // Get categories for dropdown
        $categories = $this->offerModel->getAllCategories();
        
        $this->render('admin/reports/offers', [
            'offers' => $offers,
            'stats' => $stats,
            'status' => $status,
            'category' => $category,
            'partnerId' => $partnerId,
            'partners' => $partners,
            'categories' => $categories
        ]);
    }
    
    /**
     * Export a report to CSV
     * 
     * @param string $type Report type (members, verifications, offers)
     * @return void
     */
    public function exportReport($type) {
        // Require admin or staff role
        $this->requireRole(['admin', 'staff']);
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $type . '_report_' . date('Y-m-d') . '.csv"');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Apply filters
        $filters = [];
        
        switch ($type) {
            case 'members':
                $status = $this->input('status');
                $dateFrom = $this->input('date_from');
                $dateTo = $this->input('date_to');
                
                // Build query
                $sql = "SELECT * FROM members WHERE 1=1";
                $params = [];
                
                if ($status) {
                    $sql .= " AND membership_status = ?";
                    $params[] = $status;
                    $filters[] = "Status: {$status}";
                }
                
                if ($dateFrom) {
                    $sql .= " AND joining_date >= ?";
                    $params[] = $dateFrom;
                    $filters[] = "From: {$dateFrom}";
                }
                
                if ($dateTo) {
                    $sql .= " AND joining_date <= ?";
                    $params[] = $dateTo;
                    $filters[] = "To: {$dateTo}";
                }
                
                $sql .= " ORDER BY company_name ASC";
                
                // Execute query
                $members = $this->memberModel->query($sql, $params);
                
                // CSV headers
                fputcsv($output, ['ID', 'Company Name', 'Membership ID', 'Contact Person', 'Email', 'Phone', 'Address', 'Status', 'Joining Date', 'Expiry Date']);
                
                // Write data
                foreach ($members as $member) {
                    fputcsv($output, [
                        $member['id'],
                        $member['company_name'],
                        $member['membership_id'],
                        $member['contact_person'],
                        $member['email'],
                        $member['phone'],
                        $member['address'],
                        $member['membership_status'],
                        $member['joining_date'],
                        $member['expiry_date']
                    ]);
                }
                break;
                
            case 'verifications':
                $dateFrom = $this->input('date_from');
                $dateTo = $this->input('date_to');
                $result = $this->input('result');
                
                // Build query
                $sql = "SELECT v.*, m.company_name, m.membership_id, u.email as user_email 
                        FROM verifications v
                        LEFT JOIN members m ON v.member_id = m.id
                        LEFT JOIN users u ON v.verified_by = u.id
                        WHERE 1=1";
                $params = [];
                
                if ($dateFrom) {
                    $sql .= " AND DATE(v.verification_date) >= ?";
                    $params[] = $dateFrom;
                    $filters[] = "From: {$dateFrom}";
                }
                
                if ($dateTo) {
                    $sql .= " AND DATE(v.verification_date) <= ?";
                    $params[] = $dateTo;
                    $filters[] = "To: {$dateTo}";
                }
                
                if ($result) {
                    $sql .= " AND v.verification_result = ?";
                    $params[] = $result;
                    $filters[] = "Result: {$result}";
                }
                
                $sql .= " ORDER BY v.verification_date DESC";
                
                // Execute query
                $verifications = $this->memberModel->query($sql, $params);
                
                // CSV headers
                fputcsv($output, ['ID', 'Date', 'Company Name', 'Membership ID', 'Method', 'Input', 'Result', 'Verified By', 'IP Address']);
                
                // Write data
                foreach ($verifications as $verification) {
                    fputcsv($output, [
                        $verification['id'],
                        $verification['verification_date'],
                        $verification['company_name'] ?? 'N/A',
                        $verification['membership_id'] ?? 'N/A',
                        $verification['verification_method'],
                        $verification['verification_input'],
                        $verification['verification_result'],
                        $verification['user_email'] ?? 'N/A',
                        $verification['ip_address']
                    ]);
                }
                break;
                
            case 'offers':
                $status = $this->input('status');
                $category = $this->input('category');
                $partnerId = (int) $this->input('partner_id');
                
                // Build query
                $sql = "SELECT o.*, p.name as partner_name 
                        FROM offers o
                        LEFT JOIN partners p ON o.partner_id = p.id
                        WHERE 1=1";
                $params = [];
                
                if ($status) {
                    $sql .= " AND o.status = ?";
                    $params[] = $status;
                    $filters[] = "Status: {$status}";
                }
                
                if ($category) {
                    $sql .= " AND o.category = ?";
                    $params[] = $category;
                    $filters[] = "Category: {$category}";
                }
                
                if ($partnerId) {
                    $sql .= " AND o.partner_id = ?";
                    $params[] = $partnerId;
                    
                    // Get partner name
                    $partner = $this->partnerModel->find($partnerId);
                    if ($partner) {
                        $filters[] = "Partner: {$partner['name']}";
                    }
                }
                
                $sql .= " ORDER BY o.start_date DESC";
                
                // Execute query
                $offers = $this->offerModel->query($sql, $params);
                
                // CSV headers
                fputcsv($output, ['ID', 'Title', 'Category', 'Partner', 'Status', 'Discount Value', 'Start Date', 'End Date']);
                
                // Write data
                foreach ($offers as $offer) {
                    fputcsv($output, [
                        $offer['id'],
                        $offer['title'],
                        $offer['category'],
                        $offer['partner_name'],
                        $offer['status'],
                        $offer['discount_value'],
                        $offer['start_date'],
                        $offer['end_date'] ?? 'N/A'
                    ]);
                }
                break;
                
            default:
                $this->flash('error', 'Invalid report type');
                $this->redirect('/admin/reports');
                break;
        }
        
        // Close output stream
        fclose($output);
        exit;
    }
}
