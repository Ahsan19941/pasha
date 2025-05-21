<?php
// File: config/config.php
// Main configuration file for the PASHA Benefits Portal

// Application settings
define('APP_NAME', 'PASHA Benefits Portal');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/pasha-benefits'); // Change in production

// Path definitions
define('ROOT_DIR', dirname(__DIR__));
define('APP_DIR', ROOT_DIR . '/app');
define('CONFIG_DIR', ROOT_DIR . '/config');
define('PUBLIC_DIR', ROOT_DIR . '/public');
define('VIEWS_DIR', APP_DIR . '/views');
define('UPLOADS_DIR', PUBLIC_DIR . '/uploads');
define('LOGS_DIR', ROOT_DIR . '/data/logs');
define('CACHE_DIR', ROOT_DIR . '/data/cache');

// Session configuration
define('SESSION_NAME', 'pasha_session');
define('SESSION_LIFETIME', 86400); // 24 hours in seconds
define('SESSION_PATH', '/');
define('SESSION_SECURE', false); // Set to true in production with HTTPS
define('SESSION_HTTP_ONLY', true);

// Security settings
define('CSRF_TOKEN_NAME', 'pasha_csrf_token');
define('PASSWORD_COST', 12); // Bcrypt cost factor

// Logging configuration
define('LOG_ERRORS', true);
define('LOG_LEVEL', 'DEBUG'); // DEBUG, INFO, WARNING, ERROR, CRITICAL

// Email configuration
define('MAIL_FROM', 'noreply@pasha.org');
define('MAIL_FROM_NAME', 'PASHA Benefits Portal');
define('MAIL_REPLY_TO', 'support@pasha.org');

// System settings
define('ITEMS_PER_PAGE', 10); // Default pagination limit
define('MAINTENANCE_MODE', false);

// Timezone
date_default_timezone_set('Asia/Baku'); // Default timezone for Azerbaijan

// Allowed file types for uploads (comma-separated MIME types)
define('ALLOWED_IMAGE_TYPES', 'image/jpeg,image/png,image/gif');
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB

// Cache settings
define('CACHE_ENABLED', true);
define('CACHE_EXPIRY', 3600); // 1 hour in seconds
