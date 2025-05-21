<?php
// File: app/controllers/AuthController.php
// Authentication Controller for the PASHA Benefits Portal

namespace app\controllers;

use app\core\Controller;
use app\models\UserModel;

class AuthController extends Controller {
    /**
     * @var UserModel User model
     */
    private $userModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->userModel = new UserModel();
    }
    
    /**
     * Display login form
     * 
     * @return void
     */
    public function loginForm() {
        // Redirect to appropriate dashboard if already logged in
        if ($this->isAuthenticated()) {
            if ($this->hasRole(['admin', 'staff'])) {
                $this->redirect('/admin');
            } elseif ($this->hasRole('partner')) {
                $this->redirect('/partner');
            } else {
                $this->redirect('/');
            }
        }
        
        $this->render('auth/login', [], false);
    }
    
    /**
     * Process login form
     * 
     * @return void
     */
    public function login() {
        // Check if form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login');
        }
        
        // Validate CSRF token
        $token = $this->input('csrf_token');
        if (!validateCsrfToken($token)) {
            $this->flash('error', 'Invalid form submission, please try again');
            $this->redirect('/login');
        }
        
        // Get form data
        $email = $this->input('email', '', false);
        $password = $this->input('password', '', false);
        $rememberMe = $this->input('remember_me') === 'on';
        
        // Validate required fields
        if (empty($email) || empty($password)) {
            $this->flash('error', 'Please enter both email and password');
            $this->redirect('/login');
        }
        
        // Authenticate user
        $user = $this->userModel->authenticate($email, $password);
        
        if (!$user) {
            $this->flash('error', 'Invalid email or password');
            $this->redirect('/login');
        }
        
        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_partner_id'] = $user['partner_id'];
        
        // Set session lifetime if remember me is checked
        if ($rememberMe) {
            ini_set('session.cookie_lifetime', 30 * 24 * 60 * 60); // 30 days
            ini_set('session.gc_maxlifetime', 30 * 24 * 60 * 60); // 30 days
        }
        
        // Log the activity
        $this->logActivity('User login');
        
        // Redirect to appropriate dashboard
        if ($this->hasRole(['admin', 'staff'])) {
            $this->redirect('/admin');
        } elseif ($this->hasRole('partner')) {
            $this->redirect('/partner');
        } else {
            $this->redirect('/');
        }
    }
    
    /**
     * Process logout
     * 
     * @return void
     */
    public function logout() {
        // Log the activity before destroying the session
        if ($this->isAuthenticated()) {
            $this->logActivity('User logout');
        }
        
        // Destroy the session
        session_unset();
        session_destroy();
        
        // Redirect to home page
        $this->redirect('/');
    }
    
    /**
     * Display forgot password form
     * 
     * @return void
     */
    public function forgotPasswordForm() {
        // Redirect if already logged in
        if ($this->isAuthenticated()) {
            $this->redirect('/');
        }
        
        $this->render('auth/forgot_password', [], false);
    }
    
    /**
     * Process forgot password form
     * 
     * @return void
     */
    public function forgotPassword() {
        // Check if form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/forgot-password');
        }
        
        // Validate CSRF token
        $token = $this->input('csrf_token');
        if (!validateCsrfToken($token)) {
            $this->flash('error', 'Invalid form submission, please try again');
            $this->redirect('/forgot-password');
        }
        
        // Get form data
        $email = $this->input('email', '', false);
        
        // Validate required fields
        if (empty($email)) {
            $this->flash('error', 'Please enter your email address');
            $this->redirect('/forgot-password');
        }
        
        // Create password reset token
        $resetData = $this->userModel->createPasswordResetToken($email);
        
        // Always show success message (even if email not found) to prevent email enumeration
        if ($resetData) {
            // In a real application, you would send an email with the reset link
            // For this example, we'll just log it
            logError("Password reset token for {$email}: " . url('reset-password/' . $resetData['token']), 'INFO');
            
            // Sample email content
            $resetLink = url('reset-password/' . $resetData['token']);
            $emailBody = "Hello {$resetData['user']['first_name']},\n\n"
                       . "You have requested to reset your password for the PASHA Benefits Portal.\n\n"
                       . "Please click the link below to reset your password:\n"
                       . $resetLink . "\n\n"
                       . "This link will expire in 1 hour.\n\n"
                       . "If you did not request a password reset, please ignore this email.\n\n"
                       . "Regards,\nPASHA Benefits Team";
            
            // Here you would send the email
            // mail($email, 'Password Reset Request', $emailBody, 'From: ' . MAIL_FROM);
        }
        
        $this->flash('success', 'If your email address is registered, you will receive password reset instructions');
        $this->redirect('/login');
    }
    
    /**
     * Display reset password form
     * 
     * @param string $token Reset token
     * @return void
     */
    public function resetPasswordForm($token) {
        // Redirect if already logged in
        if ($this->isAuthenticated()) {
            $this->redirect('/');
        }
        
        // Verify the token
        $tokenData = $this->userModel->verifyPasswordResetToken($token);
        
        if (!$tokenData) {
            $this->flash('error', 'Invalid or expired password reset link');
            $this->redirect('/forgot-password');
        }
        
        $this->render('auth/reset_password', [
            'token' => $token,
            'email' => $tokenData['email']
        ], false);
    }
    
    /**
     * Process reset password form
     * 
     * @param string $token Reset token
     * @return void
     */
    public function resetPassword($token) {
        // Check if form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/reset-password/' . $token);
        }
        
        // Validate CSRF token
        $csrfToken = $this->input('csrf_token');
        if (!validateCsrfToken($csrfToken)) {
            $this->flash('error', 'Invalid form submission, please try again');
            $this->redirect('/reset-password/' . $token);
        }
        
        // Verify the reset token
        $tokenData = $this->userModel->verifyPasswordResetToken($token);
        
        if (!$tokenData) {
            $this->flash('error', 'Invalid or expired password reset link');
            $this->redirect('/forgot-password');
        }
        
        // Get form data
        $password = $this->input('password', '', false);
        $confirmPassword = $this->input('confirm_password', '', false);
        
        // Validate required fields
        if (empty($password) || empty($confirmPassword)) {
            $this->flash('error', 'Please enter both password fields');
            $this->redirect('/reset-password/' . $token);
        }
        
        // Validate password match
        if ($password !== $confirmPassword) {
            $this->flash('error', 'Passwords do not match');
            $this->redirect('/reset-password/' . $token);
        }
        
        // Validate password strength
        if (strlen($password) < 8) {
            $this->flash('error', 'Password must be at least 8 characters long');
            $this->redirect('/reset-password/' . $token);
        }
        
        // Reset the password
        $success = $this->userModel->resetPassword($token, $password);
        
        if ($success) {
            $this->flash('success', 'Your password has been reset successfully. You can now log in with your new password');
            $this->redirect('/login');
        } else {
            $this->flash('error', 'Failed to reset password, please try again');
            $this->redirect('/reset-password/' . $token);
        }
    }
}
