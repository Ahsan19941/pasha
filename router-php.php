<?php
// File: app/core/Router.php
// Router class for the PASHA Benefits Portal

namespace app\core;

class Router {
    /**
     * @var array Routes collection
     */
    private $routes = [
        'GET' => [],
        'POST' => []
    ];
    
    /**
     * @var callable Function to call when no route matches
     */
    private $notFoundCallback;
    
    /**
     * Add a GET route
     * 
     * @param string $pattern URL pattern to match
     * @param string|callable $callback Controller method (Controller@method) or callable
     * @return void
     */
    public function get($pattern, $callback) {
        $this->addRoute('GET', $pattern, $callback);
    }
    
    /**
     * Add a POST route
     * 
     * @param string $pattern URL pattern to match
     * @param string|callable $callback Controller method (Controller@method) or callable
     * @return void
     */
    public function post($pattern, $callback) {
        $this->addRoute('POST', $pattern, $callback);
    }
    
    /**
     * Add a route to the routes collection
     * 
     * @param string $method HTTP method (GET, POST)
     * @param string $pattern URL pattern to match
     * @param string|callable $callback Controller method or callable
     * @return void
     */
    private function addRoute($method, $pattern, $callback) {
        // Convert pattern to regex for matching
        $pattern = $this->patternToRegex($pattern);
        $this->routes[$method][$pattern] = $callback;
    }
    
    /**
     * Convert a URL pattern to a regular expression
     * 
     * @param string $pattern URL pattern
     * @return string Regular expression
     */
    private function patternToRegex($pattern) {
        // Escape forward slashes
        $pattern = str_replace('/', '\/', $pattern);
        
        // Add start and end delimiters
        return '/^' . $pattern . '\/?$/';
    }
    
    /**
     * Set callback for when no route matches
     * 
     * @param callable $callback Function to call when no route matches
     * @return void
     */
    public function notFound($callback) {
        $this->notFoundCallback = $callback;
    }
    
    /**
     * Match the current request to a route and call the associated controller
     * 
     * @return void
     */
    public function dispatch() {
        // Get request method and URI
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->getUri();
        
        // Look for matching route
        foreach ($this->routes[$method] as $pattern => $callback) {
            if (preg_match($pattern, $uri, $matches)) {
                // Remove the first match (full string)
                array_shift($matches);
                
                // Execute the callback with parameters
                return $this->executeCallback($callback, $matches);
            }
        }
        
        // No route found, execute the notFound callback
        if (is_callable($this->notFoundCallback)) {
            call_user_func($this->notFoundCallback);
        } else {
            header('HTTP/1.1 404 Not Found');
            echo '404 Page Not Found';
        }
    }
    
    /**
     * Get the current URI (removing query string and base path)
     * 
     * @return string Current URI
     */
    private function getUri() {
        // Get the URI from the request
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        
        // Remove query string
        $position = strpos($uri, '?');
        if ($position !== false) {
            $uri = substr($uri, 0, $position);
        }
        
        // Remove base path
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/' && $basePath !== '\\') {
            $uri = substr($uri, strlen($basePath));
        }
        
        // Ensure the URI starts with /
        if (!$uri || $uri[0] !== '/') {
            $uri = '/' . $uri;
        }
        
        // Remove trailing slash if not root
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
        }
        
        return $uri;
    }
    
    /**
     * Execute a route callback
     * 
     * @param string|callable $callback Controller method or callable
     * @param array $params Parameters to pass to the callback
     * @return mixed Result of the callback
     */
    private function executeCallback($callback, $params = []) {
        // If the callback is a string in the format "Controller@method"
        if (is_string($callback) && strpos($callback, '@') !== false) {
            list($controller, $method) = explode('@', $callback);
            
            // Add namespace to controller name
            $controller = 'app\\controllers\\' . $controller;
            
            // Check if controller class exists
            if (!class_exists($controller)) {
                throw new \Exception("Controller {$controller} not found");
            }
            
            // Create controller instance
            $controllerInstance = new $controller();
            
            // Check if the method exists
            if (!method_exists($controllerInstance, $method)) {
                throw new \Exception("Method {$method} not found in controller {$controller}");
            }
            
            // Call the controller method with parameters
            return call_user_func_array([$controllerInstance, $method], $params);
        }
        
        // If the callback is a callable
        if (is_callable($callback)) {
            return call_user_func_array($callback, $params);
        }
        
        throw new \Exception("Invalid callback specified");
    }
}
