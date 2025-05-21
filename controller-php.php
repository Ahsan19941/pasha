<?php
// File: app/core/Controller.php
// Base Controller class for the PASHA Benefits Portal

namespace app\core;

class Controller {
    /**
     * Render a view with data
     * 
     * @param string $view View name
     * @param array $data Data to pass to the view
     * @param bool $includeLayout Whether to include layout (default: true)
     * @return void
     */
    protected function render($view, $data = [], $includeLayout = true) {
        // Extract variables from data array to make them available in the view
        extract($data);
        
        // Define the view file path
        $viewFile = VIEWS_DIR . '/' . $view . '.php';
        
        // Check if view file exists
        if (!file_exists($viewFile)) {
            throw new \Exception("View file {$viewFile} not found");
        }
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        require $viewFile;
        
        // Get the content of the view
        $content = ob_get_clean();
        
        // Include layout if requested
        if ($includeLayout) {
            // Define layout based on the authenticated user role
            if (isset($_SESSION['user_role'])) {
                switch ($_SESSION['user_role']) {
                    case 'admin':
                    case 'staff':
                        $layout = VIEWS_DIR . '/layouts/admin.php';
                        break;
                    case 'partner':
                        $layout = VIEWS_DIR . '/layouts/partner.php';
                        break;
                    default:
                        $layout = VIEWS_DIR . '/layouts/main.php';
                }
            } else {
                $layout = VIEWS_DIR . '/layouts/main.php';
            }
            
            // Pass content to layout
            require $layout;
        } else {
            // Output the content without layout
            echo $content;
        }
    }
    
    /**
     * Redirect to a URL
     * 
     * @param string $url URL to redirect to
     * @return void
     */
    protected function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * Send JSON response
     * 
     * @param mixed $data Data to send as JSON
     * @param int $status HTTP status code
     * @return void
     */
    protected function json($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
    
    /**
     * Get input data from POST or GET
     * 
     * @param string $key Input key
     * @param mixed $default Default value if key does not exist
     * @param bool $sanitize Whether to sanitize the input
     * @return mixed Input value
     */
    protected function input($key, $default = null, $sanitize = true) {
        $value = null;
        
        // Check if the key exists in POST
        if (isset($_POST[$key])) {
            $value = $_POST[$key];
        } 
        // Check if the key exists in GET
        elseif (isset($_GET[$key])) {
            $value = $_GET[$key];
        }
        
        // Return default if value is null
        if ($value === null) {
            return $default;
        }
        
        // Sanitize the value if requested
        if ($sanitize) {
            if (is_array($value)) {
                array_walk_recursive($value, function(&$item) {
                    $item = htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
                });
            } else {
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        }
        
        return $value;
    }
    
    /**
     * Validate that required fields are present
     * 
     * @param array $fields Required fields
     * @return array Empty array if valid, array of missing fields otherwise
     */
    protected function validateRequired($fields) {
        $missing = [];
        
        foreach ($fields as $field) {
            $value = $this->input($field, null, false);
            
            if ($value === null || $value === '') {
                $missing[] = $field;
            }
        }
        
        return $missing;
    }
    
    /**
     * Add a flash message to the session
     * 
     * @param string $type Message type (success, error, info, warning)
     * @param string $message Message content
     * @return void
     */
    protected function flash($type, $message) {
        if (!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }
        
        $_SESSION['flash'][] = [
            'type' => $type,
            'message' => $message
        ];
    }
    
    /**
     * Check if user is authenticated
     * 
     * @return bool True if authenticated, false otherwise
     */
    protected function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Check if user has the required role
     * 
     * @param string|array $roles Required role(s)
     * @return bool True if user has the required role, false otherwise
     */
    protected function hasRole($roles) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        // Convert single role to array
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        return in_array($_SESSION['user_role'], $roles);
    }
    
    /**
     * Require authentication to access the page
     * 
     * @param string $redirect URL to redirect to if not authenticated
     * @return void
     */
    protected function requireAuth($redirect = '/login') {
        if (!$this->isAuthenticated()) {
            $this->flash('error', 'Please log in to access this page');
            $this->redirect($redirect);
        }
    }
    
    /**
     * Require specific role to access the page
     * 
     * @param string|array $roles Required role(s)
     * @param string $redirect URL to redirect to if not authorized
     * @return void
     */
    protected function requireRole($roles, $redirect = '/') {
        $this->requireAuth();
        
        if (!$this->hasRole($roles)) {
            $this->flash('error', 'You do not have permission to access this page');
            $this->redirect($redirect);
        }
    }
    
    /**
     * Log activity
     * 
     * @param string $action Action performed
     * @param string $entityType Entity type (e.g., 'member', 'offer')
     * @param int $entityId Entity ID
     * @param string $details Additional details
     * @return void
     */
    protected function logActivity($action, $entityType = null, $entityId = null, $details = null) {
        if (!$this->isAuthenticated()) {
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        
        $db = \getDbConnection();
        
        $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $action, $entityType, $entityId, $details, $ipAddress]);
    }
}
