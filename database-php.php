<?php
// File: config/database.php
// Database configuration for the PASHA Benefits Portal

// Database connection parameters
$db_config = [
    'host'     => 'localhost',
    'username' => 'pasha_db_user',
    'password' => 'your_secure_password',  // Change this in production!
    'database' => 'pasha_benefits',
    'charset'  => 'utf8mb4',
    'port'     => 3306,
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
];

/**
 * Get database connection 
 * 
 * @return PDO Database connection
 * @throws PDOException If connection fails
 */
function getDbConnection() {
    global $db_config;
    
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['database']};charset={$db_config['charset']};port={$db_config['port']}";
    
    try {
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $db_config['options']);
        return $pdo;
    } catch (PDOException $e) {
        // Log the error but don't expose details in production
        error_log("Database Connection Error: " . $e->getMessage());
        throw new PDOException("Database connection failed. Please contact administrator.");
    }
}
