<?php
// File: app/helpers/functions.php
// Helper functions for the PASHA Benefits Portal

/**
 * Get the base URL
 * 
 * @param string $path Path to append to the base URL
 * @return string Full URL
 */
function url($path = '') {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Get the URL for an asset
 * 
 * @param string $path Asset path
 * @return string Asset URL
 */
function asset($path) {
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Format a date
 * 
 * @param string $date Date string
 * @param string $format Date format
 * @return string Formatted date
 */
function formatDate($date, $format = 'd M Y') {
    if (empty($date)) {
        return '';
    }
    
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generateCsrfToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validate CSRF token
 * 
 * @param string $token Token to validate
 * @return bool True if valid, false otherwise
 */
function validateCsrfToken($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME]) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Output CSRF token field
 * 
 * @return void
 */
function csrfField() {
    echo '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}

/**
 * Check if user is admin
 * 
 * @return bool True if admin, false otherwise
 */
function isAdminUser() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if user is staff
 * 
 * @return bool True if staff, false otherwise
 */
function isStaffUser() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'staff';
}

/**
 * Check if user is partner
 * 
 * @return bool True if partner, false otherwise
 */
function isPartnerUser() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'partner';
}

/**
 * Get flash messages
 * 
 * @param bool $clear Whether to clear the messages after retrieval
 * @return array Flash messages
 */
function getFlashMessages($clear = true) {
    $messages = isset($_SESSION['flash']) ? $_SESSION['flash'] : [];
    
    if ($clear) {
        $_SESSION['flash'] = [];
    }
    
    return $messages;
}

/**
 * Escape HTML
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a random string
 * 
 * @param int $length String length
 * @return string Random string
 */
function randomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Hash a password
 * 
 * @param string $password Password to hash
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
}

/**
 * Verify a password
 * 
 * @param string $password Password to verify
 * @param string $hash Password hash
 * @return bool True if valid, false otherwise
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Log an error
 * 
 * @param string $message Error message
 * @param string $level Error level
 * @return void
 */
function logError($message, $level = 'ERROR') {
    if (!LOG_ERRORS) {
        return;
    }
    
    // Create logs directory if it doesn't exist
    if (!is_dir(LOGS_DIR)) {
        mkdir(LOGS_DIR, 0755, true);
    }
    
    // Log file path
    $logFile = LOGS_DIR . '/error-' . date('Y-m-d') . '.log';
    
    // Format the message
    $formattedMessage = '[' . date('Y-m-d H:i:s') . '] [' . $level . '] ' . $message . PHP_EOL;
    
    // Write to log file
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

/**
 * Get pagination links
 * 
 * @param int $currentPage Current page
 * @param int $totalPages Total pages
 * @param string $baseUrl Base URL
 * @return string Pagination HTML
 */
function getPaginationLinks($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<ul class="pagination">';
    
    // Previous page
    $prevDisabled = $currentPage <= 1 ? ' disabled' : '';
    $prevPage = $currentPage - 1;
    $html .= '<li class="page-item' . $prevDisabled . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . '?page=' . $prevPage . '">Previous</a>';
    $html .= '</li>';
    
    // Page numbers
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        $active = $i === $currentPage ? ' active' : '';
        $html .= '<li class="page-item' . $active . '">';
        $html .= '<a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a>';
        $html .= '</li>';
    }
    
    // Next page
    $nextDisabled = $currentPage >= $totalPages ? ' disabled' : '';
    $nextPage = $currentPage + 1;
    $html .= '<li class="page-item' . $nextDisabled . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . '?page=' . $nextPage . '">Next</a>';
    $html .= '</li>';
    
    $html .= '</ul>';
    
    return $html;
}

/**
 * Truncate a string
 * 
 * @param string $string String to truncate
 * @param int $length Maximum length
 * @param string $append String to append if truncated
 * @return string Truncated string
 */
function truncate($string, $length = 100, $append = '...') {
    if (strlen($string) <= $length) {
        return $string;
    }
    
    return substr($string, 0, $length) . $append;
}

/**
 * Get a setting value from the database
 * 
 * @param string $key Setting key
 * @param mixed $default Default value
 * @return mixed Setting value
 */
function getSetting($key, $default = null) {
    static $settings = null;
    
    // Load settings from database if not loaded yet
    if ($settings === null) {
        $settings = [];
        
        try {
            $db = getDbConnection();
            $stmt = $db->query("SELECT * FROM settings");
            
            while ($row = $stmt->fetch()) {
                $settings[$row['key']] = $row['value'];
            }
        } catch (\Exception $e) {
            logError('Failed to load settings: ' . $e->getMessage());
        }
    }
    
    return isset($settings[$key]) ? $settings[$key] : $default;
}

/**
 * Check if the current request is AJAX
 * 
 * @return bool True if AJAX, false otherwise
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Generate dropdown options
 * 
 * @param array $options Options (value => label)
 * @param mixed $selected Selected value
 * @return string Options HTML
 */
function dropdownOptions($options, $selected = null) {
    $html = '';
    
    foreach ($options as $value => $label) {
        $selectedAttr = $value == $selected ? ' selected' : '';
        $html .= '<option value="' . e($value) . '"' . $selectedAttr . '>' . e($label) . '</option>';
    }
    
    return $html;
}

/**
 * Format file size
 * 
 * @param int $bytes File size in bytes
 * @param int $precision Precision
 * @return string Formatted file size
 */
function formatFileSize($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Get user IP address
 * 
 * @return string IP address
 */
function getUserIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Generate a secure password
 * 
 * @param int $length Password length
 * @return string Generated password
 */
function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    return $password;
}
