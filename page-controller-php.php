<?php
// File: app/controllers/PageController.php
// Page Controller for static pages in the PASHA Benefits Portal

namespace app\controllers;

use app\core\Controller;

class PageController extends Controller {
    /**
     * Display about page
     * 
     * @return void
     */
    public function about() {
        $this->render('pages/about');
    }
    
    /**
     * Display contact form
     * 
     * @return void
     */
    public function contactForm() {
        $this->render('pages/contact');
    }
    
    /**
     * Process contact form submission
     * 
     * @return void
     */
    public function submitContact() {
        // Check if form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/contact');
        }
        
        // Validate CSRF token
        $token = $this->input('csrf_token');
        if (!validateCsrfToken($token)) {
            $this->flash('error', 'Invalid form submission, please try again');
            $this->redirect('/contact');
        }
        
        // Get form data
        $name = $this->input('name', '', false);
        $email = $this->input('email', '', false);
        $subject = $this->input('subject', '', false);
        $message = $this->input('message', '', false);
        
        // Validate required fields
        $requiredFields = ['name', 'email', 'subject', 'message'];
        $missing = $this->validateRequired($requiredFields);
        
        if (!empty($missing)) {
            $this->flash('error', 'Please fill in all required fields: ' . implode(', ', $missing));
            $this->redirect('/contact');
        }
        
        // In a real application, send email here
        // For now, just log the message
        logError("Contact form submission from {$name} ({$email}): {$subject}", 'INFO');
        
        // Sample email content
        $emailBody = "Contact Form Submission\n\n"
                   . "Name: {$name}\n"
                   . "Email: {$email}\n"
                   . "Subject: {$subject}\n\n"
                   . "Message:\n{$message}";
        
        // Here you would send the email
        // $to = getSetting('contact_email', MAIL_FROM);
        // mail($to, 'Contact Form: ' . $subject, $emailBody, 'From: ' . MAIL_FROM);
        
        // Add to database if needed
        $sql = "INSERT INTO contact_messages (name, email, subject, message, ip_address, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        try {
            $db = \getDbConnection();
            $stmt = $db->prepare($sql);
            $stmt->execute([$name, $email, $subject, $message, getUserIp()]);
            
            $this->flash('success', 'Your message has been sent. We will get back to you soon.');
        } catch (\Exception $e) {
            logError('Failed to save contact message: ' . $e->getMessage());
            $this->flash('success', 'Your message has been sent. We will get back to you soon.');
        }
        
        $this->redirect('/contact');
    }
}
