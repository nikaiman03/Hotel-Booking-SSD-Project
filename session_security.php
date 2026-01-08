<?php
// ==================== SECURE SESSION MANAGEMENT ====================
// Include config first to get constants
require_once 'config.php';

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
    ini_set('session.sid_length', 128);
    ini_set('session.sid_bits_per_character', 6);
    
    // Start session
    session_start();
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
        $_SESSION['regenerated'] = 0;
    } elseif (time() - $_SESSION['created'] > 300 && $_SESSION['regenerated'] < 5) { // 5 minutes, max 5 regenerations
        session_regenerate_id(true);
        $_SESSION['created'] = time();
        $_SESSION['regenerated']++;
    }
    
    // Check for session timeout
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
        // Session expired
        session_unset();
        session_destroy();
        
        // Restart fresh session
        session_set_cookie_params([
            'lifetime' => SESSION_TIMEOUT,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
        
        // Log timeout if user was logged in
        if (isset($_SESSION['user_id'])) {
            error_log("Session timeout for user ID: " . $_SESSION['user_id']);
        }
        
        // Redirect to login with timeout message
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
    
    // Use hash_equals for timing attack prevention
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        error_log("CSRF token validation failed. Expected: " . substr($_SESSION['csrf_token'], 0, 10) . "... Got: " . substr($token, 0, 10) . "...");
        
        // Log CSRF attempt if user is logged in
        if (isset($_SESSION['user_id'])) {
            global $conn;
            $user_id = $_SESSION['user_id'];
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt = $conn->prepare("INSERT INTO audit_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
            $action = "CSRF_FAILED";
            $details = "CSRF token validation failed from IP: $ip";
            $stmt->bind_param("isss", $user_id, $action, $details, $ip);
            $stmt->execute();
        }
        
        return false;
    }
    
    return true;
}

// Function to validate admin CSRF token
function validateAdminCSRFToken($token) {
    if (empty($_SESSION['admin_csrf_token']) || empty($token)) {
        return false;
    }
    
    if (!hash_equals($_SESSION['admin_csrf_token'], $token)) {
        error_log("Admin CSRF token validation failed");
        return false;
    }
    
    return true;
}

// Function to generate CSRF token input field
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') . '">';
}

// Function to generate admin CSRF token input field
function adminCsrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['admin_csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') . '">';
}

// Function to check if user is authenticated
function isAuthenticated() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Function to check if user is admin
function isAdmin() {
    return isAuthenticated() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function to require authentication
function requireAuth() {
    if (!isAuthenticated()) {
        header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

// Function to require admin access
function requireAdmin() {
    requireAuth();
    
    if (!isAdmin()) {
        header("Location: 403.php");
        exit;
    }
}

// Function to log activity
function logActivity($user_id, $action, $details) {
    global $conn;
    
    if (!$conn) {
        error_log("Database connection not available for logging");
        return false;
    }
    
    // Validate inputs
    $user_id = filter_var($user_id, FILTER_VALIDATE_INT);
    $action = substr(trim($action), 0, 50);
    $details = substr(trim($details), 0, 500);
    $ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP) ?: '0.0.0.0';
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    
    // Sanitize
    $action = htmlspecialchars($action, ENT_QUOTES, 'UTF-8');
    $details = htmlspecialchars($details, ENT_QUOTES, 'UTF-8');
    
    try {
        $sql = "INSERT INTO audit_log (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed for audit log: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("issss", $user_id, $action, $details, $ip, $user_agent);
        
        if (!$stmt->execute()) {
            error_log("Failed to log activity: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $stmt->close();
        return true;
    } catch (Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
        return false;
    }
}
?>