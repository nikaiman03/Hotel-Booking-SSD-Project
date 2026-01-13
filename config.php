<?php
// ==================== SECURE CONFIGURATION ====================
// Define application environment
define('APP_ENV', 'development'); // Change to 'production' for deployment
define('APP_DEBUG', true); // MUST be false for assignment!

// ==================== ERROR HANDLING ====================
// Production mode: hide errors from users (OWASP requirement)
if (false) {  // Force display errors temporarily
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    
    // Create logs directory if it doesn't exist
    $log_dir = __DIR__ . '/logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    ini_set('error_log', $log_dir . '/php_errors.log');
} else {
    // Development mode only
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Custom error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno]: $errstr in $errfile on line $errline");
    
    if (!headers_sent() && (APP_ENV === 'production' || !APP_DEBUG)) {
        header('HTTP/1.1 500 Internal Server Error');
        if (file_exists(__DIR__ . '/500.php')) {
            include(__DIR__ . '/500.php');
        } else {
            echo '<h1>System Error</h1><p>Please try again later.</p>';
        }
        exit;
    }
    return false;
}
set_error_handler("customErrorHandler");

// ==================== DATABASE CONNECTION ====================
// SECURITY: Create .env file with these values
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'hotel_booking');

try {
    $GLOBALS['conn'] = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    if ($GLOBALS['conn']->connect_error) {
        error_log("Database connection failed: " . $GLOBALS['conn']->connect_error);
        
        if (APP_ENV === 'production' || !APP_DEBUG) {
            die("Database connection error. Please try again later.");
        } else {
            die("Database connection failed: " . htmlspecialchars($GLOBALS['conn']->connect_error));
        }
    }
    
    // Set UTF-8 charset to prevent encoding issues
    $GLOBALS['conn']->set_charset("utf8mb4");
    
} catch (Exception $e) {
    error_log("Database exception: " . $e->getMessage());
    die("System temporarily unavailable. Please try again later.");
}

// Make $conn available globally
$conn = $GLOBALS['conn'];

// ==================== APPLICATION CONSTANTS ====================
define('SITE_NAME', 'OURHOTEL Hotel');
define('MAX_LOGIN_ATTEMPTS', 5);
//define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
//define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour

// ==================== SESSION SECURITY ====================
// Set secure session cookie parameters (if session is started here)
function setSecureSessionParams() {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
}

// ==================== ADDITIONAL SECURITY MEASURES ====================
// Disable exposure of PHP version
if (!headers_sent()) {
    header_remove('X-Powered-By');
}

// Note: Security headers are now handled by .htaccess
// This prevents duplicate headers and ensures they're always sent
?>