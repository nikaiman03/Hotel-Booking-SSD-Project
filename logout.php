<?php
// logout.php - Enhanced Secure Logout with Session Hijacking Protection
// =========================================

// Enable strict error reporting during development
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Load security configuration
require_once 'config.php';

// Function to securely destroy session
function secureSessionDestroy() {
    // 1. Clear session data
    $_SESSION = array();
    
    // 2. Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        
        // Delete with SameSite attribute
        setcookie(session_name(), '', [
            'expires' => time() - 86400, // 1 day in past
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax'
        ]);
    }
    
    // 3. Destroy session
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    
    // 4. Unset session cookie from global array
    if (isset($_COOKIE[session_name()])) {
        unset($_COOKIE[session_name()]);
    }
    
    // 5. Clear session from memory
    session_unset();
}

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', $isSecure ? 1 : 0);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    
    session_start();
}

// Log logout activity
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $username = htmlspecialchars($_SESSION['username'] ?? 'unknown', ENT_QUOTES, 'UTF-8');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Log to file if function exists
    if (function_exists('logActivity')) {
        logActivity($user_id, "LOGOUT", "User '$username' logged out from IP: $ip");
    }
    
    // Also log to custom log file
    $log_file = __DIR__ . '/logs/logout.log';
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    
    $log_entry = "[" . date('Y-m-d H:i:s') . "] UserID: $user_id | Username: $username | IP: $ip | Action: LOGOUT\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    
    $was_logged_in = true;
} else {
    $was_logged_in = false;
}

// Securely destroy the session
secureSessionDestroy();

// Start a fresh session for messages
session_start();

// Set session regeneration to prevent fixation
session_regenerate_id(true);

// Set logout message
if ($was_logged_in) {
    $_SESSION['flash_message'] = [
        'type' => 'success',
        'text' => 'You have been successfully logged out.'
    ];
} else {
    $_SESSION['flash_message'] = [
        'type' => 'info', 
        'text' => 'No active session found.'
    ];
}

/*// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header_remove("X-Powered-By");*/

// Redirect to login page with timestamp to prevent caching
$timestamp = time();
header("Location: login.php?logout=1&t=$timestamp");
exit;
?>