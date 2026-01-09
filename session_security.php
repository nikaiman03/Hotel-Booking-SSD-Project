<?php
// ==================== SECURE SESSION MANAGEMENT ====================

// Define constants FIRST (before using them)
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 1800); // 30 minutes
}
if (!defined('CSRF_TOKEN_LIFETIME')) {
    define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour
}

function startSecureSession() {
    // Determine if we're using HTTPS
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
                || (($_SERVER['SERVER_PORT'] ?? 0) == 443);
    
    // Set secure session cookie parameters
    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    // Set session ini settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', $isSecure ? 1 : 0);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
    ini_set('session.cookie_lifetime', SESSION_TIMEOUT);
    
    // Prevent session fixation
    ini_set('session.use_trans_sid', 0);
    //ini_set('session.sid_length', 128);
    //ini_set('session.sid_bits_per_character', 6);
    
    // Start session
    session_start();
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
        $_SESSION['regenerated'] = 0;
    } elseif (time() - $_SESSION['created'] > 300 && $_SESSION['regenerated'] < 5) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
        $_SESSION['regenerated']++;
    }
    
    // Check for session timeout
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        
        session_set_cookie_params([
            'lifetime' => SESSION_TIMEOUT,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
        
        if (isset($_SESSION['user_id'])) {
            error_log("Session timeout for user ID: " . $_SESSION['user_id']);
        }
        
        header("Location: login.php?timeout=1");
        exit;
    }
    
    // Update last activity time
    $_SESSION['LAST_ACTIVITY'] = time();
    
    // Generate CSRF token if not exists
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_created'] = time();
    }
    
    // Regenerate CSRF token if expired
    if (isset($_SESSION['csrf_token_created']) && (time() - $_SESSION['csrf_token_created'] > CSRF_TOKEN_LIFETIME)) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_created'] = time();
    }
    
    // Generate admin CSRF token if admin
    if (empty($_SESSION['admin_csrf_token']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['admin_csrf_token_created'] = time();
    }
}

// Function to validate CSRF token
function validateCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        error_log("CSRF token validation failed");
        return false;
    }
    
    return true;
}

// Function to check if user is authenticated
function isAuthenticated() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Function to check if user is admin
function isAdmin() {
    return isAuthenticated() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// NOTE: logActivity() function is in audit_log.php - don't duplicate it here!
?>