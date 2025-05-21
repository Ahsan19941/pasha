<?php
// File: public/index.php
// Main entry point for the PASHA Benefits Portal

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Autoload classes
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = ROOT_DIR . DIRECTORY_SEPARATOR . $class . '.php';
    
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    return false;
});

// Include helper functions
require_once APP_DIR . '/helpers/functions.php';

// Check maintenance mode
if (MAINTENANCE_MODE && !isAdminUser()) {
    header('HTTP/1.1 503 Service Unavailable');
    require_once VIEWS_DIR . '/maintenance.php';
    exit;
}

// Initialize router
$router = new app\core\Router();

// Define routes
// Public routes
$router->get('/', 'HomeController@index');
$router->get('/offers', 'OfferController@listPublic');
$router->get('/offers/category/([a-zA-Z0-9-]+)', 'OfferController@listByCategory');
$router->get('/offers/([0-9]+)', 'OfferController@view');
$router->get('/verify', 'MemberController@verifyForm');
$router->post('/verify', 'MemberController@verifyMember');
$router->get('/about', 'PageController@about');
$router->get('/contact', 'PageController@contactForm');
$router->post('/contact', 'PageController@submitContact');

// Authentication routes
$router->get('/login', 'AuthController@loginForm');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');
$router->get('/forgot-password', 'AuthController@forgotPasswordForm');
$router->post('/forgot-password', 'AuthController@forgotPassword');
$router->get('/reset-password/([a-zA-Z0-9]+)', 'AuthController@resetPasswordForm');
$router->post('/reset-password/([a-zA-Z0-9]+)', 'AuthController@resetPassword');

// Admin routes
$router->get('/admin', 'AdminController@dashboard');
$router->get('/admin/members', 'AdminController@listMembers');
$router->get('/admin/members/add', 'AdminController@addMemberForm');
$router->post('/admin/members/add', 'AdminController@addMember');
$router->get('/admin/members/edit/([0-9]+)', 'AdminController@editMemberForm');
$router->post('/admin/members/edit/([0-9]+)', 'AdminController@editMember');
$router->post('/admin/members/status/([0-9]+)', 'AdminController@updateMemberStatus');

$router->get('/admin/offers', 'AdminController@listOffers');
$router->get('/admin/offers/add', 'AdminController@addOfferForm');
$router->post('/admin/offers/add', 'AdminController@addOffer');
$router->get('/admin/offers/edit/([0-9]+)', 'AdminController@editOfferForm');
$router->post('/admin/offers/edit/([0-9]+)', 'AdminController@editOffer');
$router->post('/admin/offers/status/([0-9]+)', 'AdminController@updateOfferStatus');

$router->get('/admin/partners', 'AdminController@listPartners');
$router->get('/admin/partners/add', 'AdminController@addPartnerForm');
$router->post('/admin/partners/add', 'AdminController@addPartner');
$router->get('/admin/partners/edit/([0-9]+)', 'AdminController@editPartnerForm');
$router->post('/admin/partners/edit/([0-9]+)', 'AdminController@editPartner');

$router->get('/admin/users', 'AdminController@listUsers');
$router->get('/admin/users/add', 'AdminController@addUserForm');
$router->post('/admin/users/add', 'AdminController@addUser');
$router->get('/admin/users/edit/([0-9]+)', 'AdminController@editUserForm');
$router->post('/admin/users/edit/([0-9]+)', 'AdminController@editUser');

$router->get('/admin/reports', 'ReportController@index');
$router->get('/admin/reports/members', 'ReportController@membersReport');
$router->get('/admin/reports/verifications', 'ReportController@verificationsReport');
$router->get('/admin/reports/offers', 'ReportController@offersReport');
$router->get('/admin/reports/export/([a-zA-Z0-9-]+)', 'ReportController@exportReport');

$router->get('/admin/logs', 'AdminController@activityLogs');
$router->get('/admin/settings', 'AdminController@settings');
$router->post('/admin/settings', 'AdminController@updateSettings');

// Partner portal routes
$router->get('/partner', 'PartnerController@dashboard');
$router->get('/partner/verify', 'PartnerController@verifyForm');
$router->post('/partner/verify', 'PartnerController@verifyMember');
$router->get('/partner/profile', 'PartnerController@profileForm');
$router->post('/partner/profile', 'PartnerController@updateProfile');

// Not found route (must be last)
$router->notFound(function() {
    header('HTTP/1.1 404 Not Found');
    require_once VIEWS_DIR . '/404.php';
    exit;
});

// Dispatch the request
$router->dispatch();
