<?php
// File: app/controllers/AdminController.php
// Admin Controller for the PASHA Benefits Portal (Continued)

namespace app\controllers;

use app\core\Controller;
use app\models\MemberModel;
use app\models\OfferModel;
use app\models\PartnerModel;
use app\models\UserModel;
use app\models\ActivityLogModel;

class AdminController extends Controller {
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
     * @var UserModel User model
     */
    private $userModel;
    
    /**
     * @var ActivityLogModel Activity log model
     */
    private $logModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->memberModel = new MemberModel();
        $this->offerModel = new OfferModel();
        $this->partnerModel = new PartnerModel();
        $this->userModel = new UserModel();
        $this->logModel = new ActivityLogModel();
    }
    
    // Previous methods (dashboard, listMembers, addMemberForm, addMember, etc.) here...
    
    /**
     * Display form to edit an offer
     * 
     * @param int $id Offer ID
     * @return void
     */
    public function editOfferForm($id) {
        // Require admin or staff role
        $this->requireRole(['admin', 'staff']);
        
        // Get the offer
        $offer = $this->offerModel->find($id);
        
        if (!$offer) {
            $this->flash('error', 'Offer not found');
            $this->redirect('/admin/offers');
        }
        
        // Get partners for dropdown
        $partners = $this->partnerModel->getActivePartnersForDropdown();
        
        // Get categories for suggestions
        $categories = $this->offerModel->getAllCategories();
        
        $this->render('admin/offers/edit', [
            'offer' => $offer,
            'partners' => $partners,
            'categories' => $categories
        ]);
    }
    
    /**
     * Process form to edit an offer
     * 
     * @param int $id Offer ID
     * @return void
     */
    public function editOffer($id) {
        // Require admin or staff role
        $this->requireRole(['admin', 'staff']);
        
        // Check if form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/offers/edit/' . $id);
        }
        
        // Validate CSRF token
        $token = $this->input('csrf_token');
        if (!validateCsrfToken($token)) {
            $this->flash('error', 'Invalid form submission, please try again');
            $this->redirect('/admin/offers/edit/' . $id);
        }
        
        // Get the offer
        $offer = $this->offerModel->find($id);
        
        if (!$offer) {
            $this->flash('error', 'Offer not found');
            $this->redirect('/admin/offers');
        }
        
        // Get form data
        $updatedOffer = [
            'title' => $this->input('title', '', false),
            'description' => $this->input('description', '', false),
            'partner_id' => (int) $this->input('partner_id'),
            'category' => $this->input('category', '', false),
            'discount_value' => $this->input('discount_value', '', false),
            'redemption_instructions' => $this->input('redemption_instructions', '', false),
            'start_date' => $this->input('start_date'),
            'end_date' => $this->input('end_date'),
            'image_url' => $this->input('image_url', '', false),
            'status' => $this->input('status', 'active')
        ];
        
        // Validate required fields
        $requiredFields = ['title', 'description', 'partner_id', 'category', 'start_date'];
        $missing = $this->validateRequired($requiredFields);
        
        if (!empty($missing)) {
            $this->flash('error', 'Please fill in all required fields: ' . implode(', ', $missing));
            $this->redirect('/admin/offers/edit/' . $id);
        }
        
        // Update the offer
        $result = $this->offerModel->update($id, $updatedOffer);
        
        if ($result) {
            // Log the activity
            $this->logActivity('Offer updated', 'offer', $id, json_encode($updatedOffer));
            
            $this->flash('success', 'Offer updated successfully');
            $this->redirect('/admin/offers');
        } else {
            $this->flash('error', 'Failed to update offer, please try again');
            $this->redirect('/admin/offers/edit/' . $id);
        }
    }
    
    /**
     * Update offer status
     * 
     * @param int $id Offer ID
     * @return void
     */
    public function updateOfferStatus($id) {
        // Require admin or staff role
        $this->requireRole(['admin', 'staff']);
        
        // Check if form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/offers');
        }
        
        // Validate CSRF token
        $token = $this->input('csrf_token');
        if (!validateCsrfToken($token)) {
            $this->flash('error', 'Invalid form submission, please try again');
            $this->redirect('/admin/offers');
        }
        
        // Get the offer
        $offer = $this->offerModel->find($id);
        
        if (!$offer) {
            $this->flash('error', 'Offer not found');
            $this->redirect('/admin/offers');
        }
        
        // Get new status
        $status = $this->input('status');
        
        // Update status
        $result = $this->offerModel->updateStatus($id, $status);
        
        if ($result) {
            // Log the activity
            $this->logActivity('Offer status updated', 'offer', $id, "Status changed to {$status}");
            
            $this->flash('success', 'Offer status updated successfully');
        } else {
            $this->flash('error', 'Failed to update offer status');
        }
        
        $this->redirect('/admin/offers');
    }
    
    /**
     * List all partners
     * 
     * @return void
     */
    public function listPartners() {
        // Require admin or staff role
        $this->requireRole(['admin', 'staff']);
        
        // Get pagination parameters
        $page = (int) $this->input('page', 1);
        $search = $this->input('search');
        $status = $this->input('status');
        
        // Get partners with pagination
        $partnersData = $this->partnerModel->getAllWithPagination($page, ITEMS_PER_PAGE, $search, $status);
        
        $this->render('admin/partners/list', [
            'partners' => $partnersData['partners'],
            'pagination' => $partnersData['pagination'],
            'search' => $search,
            'status' => $status
        ]);
    }
    
    /**
     * Display form to add a new partner
     * 
     * @return void
     */
    public function addPartnerForm() {
        // Require admin or staff role
        $this->requireRole(['admin', 'staff']);
        
        $this->render('admin/partners/add');
    }
    
    /**
     * Process form to add a new partner
     * 
     * @return void
     */
    public function addPartner() {
        // Require admin or staff role
        $this->requireRole(['admin', 'staff']);
        
        // Check if form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/partners/add');
        }
        
        // Validate CSRF token
        $token = $this->input('csrf_token');
        if (!validateCsrfToken($token)) {
            $this->flash('error', 'Invalid form submission, please try again');
            $this->redirect('/admin/partners/add');
        }
        
        // Get form data
        $partner = [
            'name' => $this->input('name', '', false),
            'contact_person' => $this->input('contact_person', '', false),
            'email' => $this->input('email', '', false),
            'phone' => $this->input('phone', '', false),
            'address' => $this->input('address', '', false),
            'logo_url' => $this->input('logo_url', '', false),
            'website' => $this->input('website', '', false),
            'status' => $this->input('status', 'active')
        ];
        
        // Validate required fields
        $requiredFields = ['name', 'contact_person', 'email'];
        $missing = $this->validateRequired($requiredFields);
        
        if (!empty($missing)) {
            $this->flash('error', 'Please fill in all required fields: ' . implode(', ', $missing));
            $this->redirect('/admin/partners/add');
        }
        
        // Create the partner
        $result = $this->partnerModel->create($partner);
        
        if ($result) {
            // Log the activity
            $this->logActivity('Partner created', 'partner', $result, json_encode($partner));
            
            $this->flash('success', 'Partner added successfully');
            $this->redirect('/admin/partners');
        } else {
            $this->flash('error', 'Failed to add partner, please try again');
            $this->redirect('/admin/partners/add');
        }
    }
    
    /**
     * Display form to edit a partner
     * 
     * @param int $id Partner ID
     * @return void
     */
    public function editPartnerForm($id) {
        // Require admin or staff role
        $this->requireRole(['admin', 'staff']);
        
        // Get the partner
        $partner = $this->partnerModel->getWithOffersCount($id);
        
        if (!$partner) {
            $this->flash('error', 'Partner not found');
            $this->redirect('/admin/partners');
        }
        
        $this->render('admin/partners/edit', [
            'partner' => $partner
        ]);
    }
    
    /**
     * Process form to edit a partner
     * 
     * @param int $id Partner ID
     * @return void
     */
    public function editPartner($id) {
        // Require admin or staff role
        $this->requireRole(['admin', 'staff']);
        
        // Check if form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/partners/edit/' . $id);
        }
        
        // Validate CSRF token
        $token = $this->input('csrf_token');
        if (!validateCsrfToken($token)) {
            $this->flash('error', 'Invalid form submission, please try again');
            $this->redirect('/admin/partners/edit/' . $id);
        }
        
        // Get the partner
        $partner = $this->partnerModel->find($id);
        
        if (!$partner) {
            $this->flash('error', 'Partner not found');
            $this->redirect('/admin/partners');
        }
        
        // Get form data
        $updatedPartner = [
            'name' => $this->input('name', '', false),
            'contact_person' => $this->input('contact_person', '', false),
            'email' => $this->input('email', '', false),
            'phone' => $this->input('phone', '', false),
            'address' => $this->input('address', '', false),
            'logo_url' => $this->input('logo_url', '', false),
            'website' => $this->input('website', '', false),
            'status' => $this->input('status', 'active')
        ];
        
        // Validate required fields
        $requiredFields = ['name', 'contact_person', 'email'];
        $missing = $this->validateRequired($requiredFields);
        
        if (!empty($missing)) {
            $this->flash('error', 'Please fill in all required fields: ' . implode(', ', $missing));
            $this->redirect('/admin/partners/edit/' . $id);
        }
        
        // Update the partner
        $result = $this->partnerModel->update($id, $updatedPartner);
        
        if ($result) {
            // Log the activity
            $this->logActivity('Partner updated', 'partner', $id, json_encode($updatedPartner));
            
            $this->flash('success', 'Partner updated successfully');
            $this->redirect('/admin/partners');
        } else {
            $this->flash('error', 'Failed to update partner, please try again');
            $this->redirect('/admin/partners/edit/' . $id);
        }
    }
    
    /**
     * List all users
     * 
     * @return void
     */
    public function listUsers() {
        // Require admin role
        $this->requireRole('admin');
        
        // Get pagination parameters
        $page = (int) $this->input('page', 1);
        $search = $this->input('search');
        $role = $this->input('role');
        
        // Get users with pagination
        $usersData = $this->userModel->getAllWithPagination($page, ITEMS_PER_PAGE, $search, $role);
        
        $this->render('admin/users/list', [
            'users' => $usersData['users'],
            'pagination' => $usersData['pagination'],
            'search' => $search,
            'role' => $role
        ]);
    }
    
    /**
     * Display form to add a new user
     * 
     * @return void
     */
    public function addUserForm() {
        // Require admin role
        $this->requireRole('admin');
        
        // Get partners for dropdown (if role is partner)
        $partners = $this->partnerModel->getActivePartnersForDropdown();
        
        $this->render('admin/users/add', [
            'partners' => $partners
        ]);
    }
    
    /**
     * Process form to add a new user
     * 
     * @return void
     */
    public function addUser() {
        // Require admin role
        $this->requireRole('admin');
        
        // Check if form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/users/add');
        }
        
        // Validate CSRF token
        $token = $this->input('csrf_token');
        if (!validateCsrfToken($token)) {
            $this->flash('error', 'Invalid form submission, please try again');
            $this->redirect('/admin/users/add');
        }
        
        // Get form data
        $user = [
            'email' => $this->input('email', '', false),
            'password' => $this->input('password', '', false),
            'first_name' => $this->input('first_name', '', false),
            'last_name' => $this->input('last_name', '', false),
            'role' => $this->input('role', '', false),
            'partner_id' => $this->input('role') === 'partner' ? (int) $this->input('partner_id') : null,
            'status' => $this->input('status', 'active')
        ];
        
        // Validate required fields
        $requiredFields = ['email', 'password', 'first_name', 'last_name', 'role'];
        $missing = $this->validateRequired($requiredFields);
        
        if (!empty($missing)) {
            $this->flash('error', 'Please fill in all required fields: ' . implode(', ', $missing));
            $this->redirect('/admin/users/add');
        }
        
        // Check if email already exists
        $existingUser = $this->userModel->findOneBy('email', $user['email']);
        if ($existingUser) {
            $this->flash('error', 'A user with this email already exists');
            $this->redirect('/admin/users/add');
        }
        
        // Validate partner ID if role is partner
        if ($user['role'] === 'partner' && empty($user['partner_id'])) {
            $this->flash('error', 'Please select a partner for partner users');
            $this->redirect('/admin/users/add');
        }
        
        // Create the user
        $result = $this->userModel->createUser($user);
        
        if ($result) {
            // Remove password from logged data
            unset($user['password']);
            
            // Log the activity
            $this->logActivity('User created', 'user', $result, json_encode($user));
            
            $this->flash('success', 'User added successfully');
            $this->redirect('/admin/users');
        } else {
            $this->flash('error', 'Failed to add user, please try again');
            $this->redirect('/admin/users/add');
        }
    }
    
    /**
     * Display form to edit a user
     * 
     * @param int $id User ID
     * @return void
     */
    public function editUserForm($id) {
        // Require admin role
        $this->requireRole('admin');
        
        // Get the user
        $user = $this->userModel->getWithPartner($id);
        
        if (!$user) {
            $this->flash('error', 'User not found');
            $this->redirect('/admin/users');
        }
        
        // Get partners for dropdown (if role is partner)
        $partners = $this->partnerModel->getActivePartnersForDropdown();
        
        $this->render('admin/users/edit', [
            'user' => $user,
            'partners' => $partners
        ]);
    }
    
    /**
     * Process form to edit a user
     * 
     * @param int $id User ID
     * @return void
     */
    public function editUser($id) {
        // Require admin role
        $this->requireRole('admin');
        
        // Check if form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/users/edit/' . $id);
        }
        
        // Validate CSRF token
        $token = $this->input('csrf_token');
        if (!validateCsrfToken($token)) {
            $this->flash('error', 'Invalid form submission, please try again');
            $this->redirect('/admin/users/edit/' . $id);
        }
        
        // Get the user
        $user = $this->userModel->find($id);
        
        if (!$user) {
            $this->flash('error', 'User not found');
            $this->redirect('/admin/users');
        }
        
        // Get form data
        $updatedUser = [
            'email' => $this->input('email', '', false),
            'password' => $this->input('password', '', false), // Will be ignored if empty
            'first_name' => $this->input('first_name', '', false),
            'last_name' => $this->input('last_name', '', false),
            'role' => $this->input('role', '', false),
            'partner_id' => $this->input('role') === 'partner' ? (int) $this->input('partner_id') : null,
            'status' => $this->input('status', 'active')
        ];
        
        // Validate required fields
        $requiredFields = ['email', 'first_name', 'last_name', 'role'];
        $missing = $this->validateRequired($requiredFields);
        
        if (!empty($missing)) {
            $this->flash('error', 'Please fill in all required fields: ' . implode(', ', $missing));
            $this->redirect('/admin/users/edit/' . $id);
        }
        
        // Check if email already exists and belongs to another user
        $existingUser = $this->userModel->findOneBy('email', $updatedUser['email']);
        if ($existingUser && $existingUser['id'] != $id) {
            $this->flash('error', 'A user with this email already exists');
            $this->redirect('/admin/users/edit/' . $id);
        }
        
        // Validate partner ID if role is partner
        if ($updatedUser['role'] === 'partner' && empty($updatedUser['partner_id'])) {
            $this->flash('error', 'Please select a partner for partner users');
            $this->redirect('/admin/users/edit/' . $id);
        }
        
        // Update the user
        $result = $this->userModel->updateUser($id, $updatedUser);
        
        if ($result) {
            // Remove password from logged data
            unset($updatedUser['password']);
            
            // Log the activity
            $this->logActivity('User updated', 'user', $id, json_encode($updatedUser));
            
            $this->flash('success', 'User updated successfully');
            $this->redirect('/admin/users');
        } else {
            $this->flash('error', 'Failed to update user, please try again');
            $this->redirect('/admin/users/edit/' . $id);
        }
    }
    
    /**
     * View activity logs
     * 
     * @return void
     */
    public function activityLogs() {
        // Require admin role
        $this->requireRole('admin');
        
        // Get pagination parameters
        $page = (int) $this->input('page', 1);
        $search = $this->input('search');
        $userId = (int) $this->input('user_id');
        $entityType = $this->input('entity_type');
        $dateFrom = $this->input('date_from');
        $dateTo = $this->input('date_to');
        
        // Get logs with pagination
        $logsData = $this->logModel->getAllWithPagination($page, ITEMS_PER_PAGE, $search, $userId, $entityType, $dateFrom, $dateTo);
        
        // Get users for filter
        $users = $this->userModel->all('email ASC');
        
        $this->render('admin/logs', [
            'logs' => $logsData['logs'],
            'pagination' => $logsData['pagination'],
            'search' => $search,
            'userId' => $userId,
            'entityType' => $entityType,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'users' => $users
        ]);
    }
    
    /**
     * Display and update system settings
     * 
     * @return void
     */
    public function settings() {
        // Require admin role
        $this->requireRole('admin');
        
        // Check if form was submitted
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF token
            $token = $this->input('csrf_token');
            if (!validateCsrfToken($token)) {
                $this->flash('error', 'Invalid form submission, please try again');
                $this->redirect('/admin/settings');
            }
            
            // Get settings from form
            $settings = [
                'site_name' => $this->input('site_name', '', false),
                'site_description' => $this->input('site_description', '', false),
                'contact_email' => $this->input('contact_email', '', false),
                'contact_phone' => $this->input('contact_phone', '', false),
                'address' => $this->input('address', '', false),
                'logo_url' => $this->input('logo_url', '', false),
                'enable_member_verification' => $this->input('enable_member_verification') === 'on' ? '1' : '0',
                'enable_partner_portal' => $this->input('enable_partner_portal') === 'on' ? '1' : '0',
                'items_per_page' => (int) $this->input('items_per_page', 10),
                'maintenance_mode' => $this->input('maintenance_mode') === 'on' ? '1' : '0'
            ];
            
            // Update settings
            $success = true;
            foreach ($settings as $key => $value) {
                $sql = "INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$key, $value, $value]);
                
                if (!$result) {
                    $success = false;
                }
            }
            
            if ($success) {
                // Log the activity
                $this->logActivity('Settings updated', 'settings', null, json_encode($settings));
                
                $this->flash('success', 'Settings updated successfully');
            } else {
                $this->flash('error', 'Failed to update settings, please try again');
            }
            
            $this->redirect('/admin/settings');
        }
        
        // Get current settings
        $settings = [
            'site_name' => getSetting('site_name', APP_NAME),
            'site_description' => getSetting('site_description', ''),
            'contact_email' => getSetting('contact_email', ''),
            'contact_phone' => getSetting('contact_phone', ''),
            'address' => getSetting('address', ''),
            'logo_url' => getSetting('logo_url', ''),
            'enable_member_verification' => getSetting('enable_member_verification', '1'),
            'enable_partner_portal' => getSetting('enable_partner_portal', '1'),
            'items_per_page' => getSetting('items_per_page', ITEMS_PER_PAGE),
            'maintenance_mode' => getSetting('maintenance_mode', '0')
        ];
        
        $this->render('admin/settings', [
            'settings' => $settings
        ]);
    }
}
