<?php
// ==================== SECURE SESSION MANAGEMENT ====================
// Make sure config is loaded first for constants
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 1800); //session will destroy after 30 min
}
if (!defined('CSRF_TOKEN_LIFETIME')) {
    define('CSRF_TOKEN_LIFETIME', 3600);
}

function startSecureSession() {
    // Check if config.php is loaded
    if (!isset($GLOBALS['conn'])) {
        require_once __DIR__ . '/config.php';
    }
    
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
        'samesite' => 'Lax' 
    ]);
    
    // Set session ini settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', $isSecure ? 1 : 0);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
    ini_set('session.cookie_lifetime', SESSION_TIMEOUT);
    ini_set('session.use_trans_sid', 0);
    
    // Start session
    session_start();
    
    // Initialize session if new
    if (empty($_SESSION['init'])) {
        $_SESSION['init'] = true;
        $_SESSION['created'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }
    
    // Security checks
    if (!empty($_SESSION['ip_address']) && $_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
        session_regenerate_id(true);
        session_unset();
        session_destroy();
        header("Location: login.php?session=hijacked");
        exit;
    }
    
    if (!empty($_SESSION['user_agent']) && $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')) {
        session_regenerate_id(true);
    }
    
    // Check for session timeout
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
        // Log timeout
        if (isset($_SESSION['user_id'])) {
            logActivity($_SESSION['user_id'], "SESSION_TIMEOUT", "Session expired due to inactivity");
        }
        
        session_unset();
        session_destroy();
        
        // Start new session for message
        session_start();
        $_SESSION['session_expired'] = true;
        
        header("Location: login.php?timeout=1");
        exit;
    }
    
    // Regenerate session ID periodically (every 5 minutes)
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > 300) { // 5 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
    
    // Update last activity time
    $_SESSION['LAST_ACTIVITY'] = time();

    function generate_csrf_token() {
    if (empty($_SESSION['csrf_token']) || 
        empty($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_LIFETIME) {
        
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
}
    
    // Generate CSRF token if not exists
    generate_csrf_token();
}

// Check if user is authenticated
function isAuthenticated() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Check if user is admin
function isAdmin() {
    return isAuthenticated() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Force logout function
function forceLogout($reason = 'security_violation') {
    if (isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], "FORCE_LOGOUT", "Reason: $reason");
    }
    
    session_unset();
    session_destroy();
    
    // Start fresh session for message
    session_start();
    $_SESSION['logout_reason'] = $reason;
    
    header("Location: login.php");
    exit;
}
?>