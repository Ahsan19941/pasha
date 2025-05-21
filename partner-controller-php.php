<?php
// File: app/controllers/PartnerController.php
// Partner Controller for the PASHA Benefits Portal

namespace app\controllers;

use app\core\Controller;
use app\models\PartnerModel;
use app\models\MemberModel;
use app\models\OfferModel;

class PartnerController extends Controller {
    /**
     * @var PartnerModel Partner model
     */
    private $partnerModel;
    
    /**
     * @var MemberModel Member model
     */
    private $memberModel;
    
    /**
     * @var OfferModel Offer model
     */
    private $offerModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->partnerModel = new PartnerModel();
        $this->memberModel = new MemberModel();
        $this->offerModel = new OfferModel();
    }
    
    /**
     * Display partner dashboard
     * 
     * @return void
     */
    public function dashboard() {
        // Require partner role
        $this->requireRole('partner');
        
        // Get partner ID from session
        $partnerId = $_SESSION['user_partner_id'];
        
        // Get partner information
        $partner = $this->partnerModel->find($partnerId);
        
        if (!$partner) {
            $this->flash('error', 'Partner not found');
            $this->redirect('/');
        }
        
        // Get partner's offers
        $offers = $this->offerModel->findBy('partner_id', $partnerId, 'start_date DESC');
        
        // Get recent verifications
        $recentVerifications = $this->memberModel->query(
            "SELECT v.*, m.company_name, m.membership_id 
             FROM verifications v 
             JOIN members m ON v.member_id = m.id 
             WHERE v.verified_by = ? 
             ORDER BY v.verification_date DESC 
             LIMIT 10",
            [$_SESSION['user_id']]
        );
        
        $this->render('partner/dashboard', [
            'partner' => $partner,
            'offers' => $offers,
            'verifications' => $recentVerifications
        ]);
    }
    
    /**
     * Display member verification form
     * 
     * @return void
     */
    public function verifyForm() {
        // Require partner role
        $this->requireRole('partner');
        
        $this->render('partner/verify');
    }
    
    /**
     * Process member verification
     * 
     * @return void
     */
    public function verifyMember() {
        // Require partner role
        $this->requireRole('partner');
        
        // Check if form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/partner/verify');
        }
        
        // Validate CSRF token
        $token = $this->input('csrf_token');
        if (!validateCsrfToken($token)) {
            $this->flash('error', 'Invalid form submission, please try again');
            $this->redirect('/partner/verify');
        }
        
        // Get form data
        $type = $this->input('verification_type', 'id');
        $value = $this->input('verification_value', '', false);
        
        // Validate required fields
        if (empty($value)) {
            $this->flash('error', 'Please enter a value to verify');
            $this->redirect('/partner/verify');
        }
        
        // Get user ID
        $userId = $_SESSION['user_id'];
        
        // Verify the member
        $result = $this->memberModel->verifyMember($type, $value, $userId);
        
        // Display the result
        $this->render('partner/verify_result', [
            'result' => $result,
            'type' => $type,
            'value' => $value
        ]);
    }
    
    /**
     * Display partner profile form
     * 
     * @return void
     */
    public function profileForm() {
        // Require partner role
        $this->requireRole('partner');
        
        // Get partner ID from session
        $partnerId = $_SESSION['user_partner_id'];
        
        // Get partner information
        $partner = $this->partnerModel->find($partnerId);
        
        if (!$partner) {
            $this->flash('error', 'Partner not found');
            $this->redirect('/partner');
        }
        
        $this->render('partner/profile', [
            'partner' => $partner
        ]);
    }
    
    /**
     * Process partner profile update
     * 
     * @return void
     */
    public function updateProfile() {
        // Require partner role
        $this->requireRole('partner');
        
        // Check if form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/partner/profile');
        }
        
        // Validate CSRF token
        $token = $this->input('csrf_token');
        if (!validateCsrfToken($token)) {
            $this->flash('error', 'Invalid form submission, please try again');
            $this->redirect('/partner/profile');
        }
        
        // Get partner ID from session
        $partnerId = $_SESSION['user_partner_id'];
        
        // Get partner information
        $partner = $this->partnerModel->find($partnerId);
        
        if (!$partner) {
            $this->flash('error', 'Partner not found');
            $this->redirect('/partner');
        }
        
        // Get form data
        $updatedPartner = [
            'contact_person' => $this->input('contact_person', '', false),
            'email' => $this->input('email', '', false),
            'phone' => $this->input('phone', '', false),
            'address' => $this->input('address', '', false),
            'website' => $this->input('website', '', false)
        ];
        
        // Partners can only update specific fields
        
        // Validate required fields
        $requiredFields = ['contact_person', 'email'];
        $missing = $this->validateRequired($requiredFields);
        
        if (!empty($missing)) {
            $this->flash('error', 'Please fill in all required fields: ' . implode(', ', $missing));
            $this->redirect('/partner/profile');
        }
        
        // Update the partner
        $result = $this->partnerModel->update($partnerId, $updatedPartner);
        
        if ($result) {
            // Log the activity
            $this->logActivity('Partner profile updated', 'partner', $partnerId, json_encode($updatedPartner));
            
            $this->flash('success', 'Profile updated successfully');
        } else {
            $this->flash('error', 'Failed to update profile, please try again');
        }
        
        $this->redirect('/partner/profile');
    }
}
