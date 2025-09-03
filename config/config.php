<?php
/**
 * EV Mobile Station - Main Configuration File
 * Central configuration for the entire application
 */

// Application settings
define('APP_NAME', 'EV Mobile Power & Service Station');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/evv/');
define('ADMIN_URL', APP_URL . 'admin/');

// Database settings (can be overridden by environment variables)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'ev_mobile_station');
define('DB_USERNAME', getenv('DB_USERNAME') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');

// Session settings
define('SESSION_NAME', 'ev_mobile_station');
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_PATH', '/evv/');
define('SESSION_DOMAIN', '');
define('SESSION_SECURE', false);
define('SESSION_HTTP_ONLY', true);

// Security settings
define('PASSWORD_COST', 12);
define('CSRF_TOKEN_NAME', 'csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutes

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);
define('UPLOAD_PATH', 'uploads/');

// API settings
define('GOOGLE_MAPS_API_KEY', getenv('GOOGLE_MAPS_API_KEY') ?: '');
define('SMS_API_KEY', getenv('SMS_API_KEY') ?: '');
define('EMAIL_API_KEY', getenv('EMAIL_API_KEY') ?: '');

// Email (SMTP) settings - Gmail
define('EMAIL_SMTP_HOST', getenv('EMAIL_SMTP_HOST') ?: 'smtp.gmail.com');
define('EMAIL_SMTP_PORT', getenv('EMAIL_SMTP_PORT') ?: 465);
define('EMAIL_SMTP_SSL', true);
define('EMAIL_SMTP_USER', getenv('EMAIL_SMTP_USER') ?: 'anandhuaskmg@gmail.com');
define('EMAIL_SMTP_PASSWORD', getenv('EMAIL_SMTP_PASSWORD') ?: 'leyd rbij nwzh lbyl');
define('EMAIL_FROM_EMAIL', getenv('EMAIL_FROM_EMAIL') ?: 'anandhuaskmg@gmail.com');
define('EMAIL_FROM_NAME', getenv('EMAIL_FROM_NAME') ?: 'Ev Charging Station');

// Error reporting
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Timezone
date_default_timezone_set('UTC');

// Include required files
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialize session management
class SessionInitializer {
    private static $initialized = false;
    
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        // Only start session if not already started and no output has been sent
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_name(SESSION_NAME);
            session_set_cookie_params(
                SESSION_LIFETIME,
                SESSION_PATH,
                SESSION_DOMAIN,
                SESSION_SECURE,
                SESSION_HTTP_ONLY
            );
            session_start();
        }
        
        // Set CSRF token if not exists
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        
        self::$initialized = true;
    }
}

// Initialize session
SessionInitializer::init();

// Function to get CSRF token
function getCSRFToken() {
    return $_SESSION[CSRF_TOKEN_NAME] ?? '';
}

// Function to verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Function to generate secure random string
function generateSecureString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Function to sanitize output
function sanitizeOutput($data) {
    if (is_array($data)) {
        return array_map('sanitizeOutput', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Function to check if request is AJAX
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Function to send JSON response
function sendJsonResponse($data, $status_code = 200) {
    if (!headers_sent()) {
        http_response_code($status_code);
        header('Content-Type: application/json');
    }
    echo json_encode($data);
    exit;
}

// Function to send error response
function sendErrorResponse($message, $status_code = 400) {
    sendJsonResponse(['error' => $message], $status_code);
}

// Function to send success response
function sendSuccessResponse($data = null, $message = 'Success') {
    sendJsonResponse(['success' => true, 'message' => $message, 'data' => $data]);
}
?>
