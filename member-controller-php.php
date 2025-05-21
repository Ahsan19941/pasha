<?php
// File: app/controllers/MemberController.php
// Member Controller for the PASHA Benefits Portal

namespace app\controllers;

use app\core\Controller;
use app\models\MemberModel;

class MemberController extends Controller {
    /**
     * @var MemberModel Member model
     */
    private $memberModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->memberModel = new MemberModel();
    }
    
    /**
     * Display member verification form
     * 
     * @return void
     */
    public function verifyForm() {
        $this->render('members/verify');
    }
    
    /**
     * Process member verification
     * 
     * @return void
     */
    public function verifyMember() {
        // Check if form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/verify');
        }
        
        // Validate CSRF token
        $token = $this->input('csrf_token');
        if (!validateCsrfToken($token)) {
            $this->flash('error', 'Invalid form submission, please try again');
            $this->redirect('/verify');
        }
        
        // Get form data
        $type = $this->input('verification_type', 'id');
        $value = $this->input('verification_value', '', false);
        
        // Validate required fields
        if (empty($value)) {
            $this->flash('error', 'Please enter a value to verify');
            $this->redirect('/verify');
        }
        
        // Get user ID if authenticated
        $userId = $this->isAuthenticated() ? $_SESSION['user_id'] : null;
        
        // Verify the member
        $result = $this->memberModel->verifyMember($type, $value, $userId);
        
        // Display the result
        $this->render('members/verify_result', [
            'result' => $result,
            'type' => $type,
            'value' => $value
        ]);
    }
}
