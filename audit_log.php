<?php
// ==================== SECURE AUDIT LOGGING FUNCTIONS ====================
// Include config for database connection
require_once 'config.php';

function logActivity($user_id, $action, $details) {
    global $conn;
    
    // Validate and sanitize inputs
    $user_id = filter_var($user_id, FILTER_VALIDATE_INT);
    if ($user_id === false) {
        error_log("Invalid user_id for logActivity: " . $user_id);
        return false;
    }
    
    $action = substr(trim($action), 0, 100);
    $details = substr(trim($details), 0, 1000);
    
    // Get IP address with validation
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = '0.0.0.0';
    }
    
    // Get user agent
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $user_agent = substr($user_agent, 0, 255);
    
    // Prepare SQL statement
    $sql = "INSERT INTO audit_log (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Prepare failed for audit log: " . $conn->error);
        return false;
    }
    
    // Bind parameters
    $stmt->bind_param("issss", $user_id, $action, $details, $ip, $user_agent);
    
    // Execute
    if (!$stmt->execute()) {
        error_log("Failed to log activity: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $stmt->close();
    return true;
}

function logFailedLogin($username, $reason) {
    global $conn;
    
    // Validate and sanitize inputs
    $username = substr(trim($username), 0, 50);
    $reason = substr(trim($reason), 0, 100);
    
    // Get IP address with validation
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = '0.0.0.0';
    }
    
    // Get user agent
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $user_agent = substr($user_agent, 0, 255);
    
    // Prepare SQL statement
    $sql = "INSERT INTO failed_login_log (username, reason, ip_address, user_agent) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Prepare failed for failed login log: " . $conn->error);
        return false;
    }
    
    // Bind parameters
    $stmt->bind_param("ssss", $username, $reason, $ip, $user_agent);
    
    // Execute
    if (!$stmt->execute()) {
        error_log("Failed to log failed login: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $stmt->close();
    
    // Check for brute force attempts
    checkBruteForce($username, $ip);
    
    return true;
}

function checkBruteForce($username, $ip) {
    global $conn;
    
    // Count failed attempts in last 15 minutes
    $time_ago = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    
    $sql = "SELECT COUNT(*) as attempt_count FROM failed_login_log 
            WHERE (username = ? OR ip_address = ?) 
            AND attempted_at > ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed for brute force check: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("sss", $username, $ip, $time_ago);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $attempt_count = $row['attempt_count'] ?? 0;
    
    $stmt->close();
    
    // If more than 5 attempts, log warning
    if ($attempt_count >= 5) {
        error_log("BRUTE FORCE WARNING: $attempt_count failed attempts for username: $username from IP: $ip");
        
        // Log to audit log if we can identify the user
        $sql_user = "SELECT id FROM users WHERE username = ?";
        $stmt_user = $conn->prepare($sql_user);
        if ($stmt_user) {
            $stmt_user->bind_param("s", $username);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();
            if ($user = $result_user->fetch_assoc()) {
                logActivity($user['id'], "BRUTE_FORCE_ATTEMPT", "Multiple failed login attempts detected for user from IP: $ip");
            }
            $stmt_user->close();
        }
        
        return true; // Brute force detected
    }
    
    return false;
}

function getAuditLogs($limit = 100, $offset = 0) {
    global $conn;
    
    $sql = "SELECT al.*, u.username 
            FROM audit_log al 
            LEFT JOIN users u ON al.user_id = u.id 
            ORDER BY al.created_at DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed for get audit logs: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    $stmt->close();
    return $logs;
}

function getFailedLoginLogs($limit = 100, $offset = 0) {
    global $conn;
    
    $sql = "SELECT * FROM failed_login_log 
            ORDER BY attempted_at DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed for get failed login logs: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    $stmt->close();
    return $logs;
}

// Database security helper function
function secureQuery($sql, $params = [], $types = '') {
    global $conn;
    
    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params) && !empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt;
    } catch (Exception $e) {
        error_log("Secure query error: " . $e->getMessage());
        return false;
    }
}

// Input validation function
function validateInput($input, $type = 'string', $max_length = 255) {
    if (is_null($input)) {
        return false;
    }
    
    $input = trim($input);
    
    if (strlen($input) > $max_length) {
        return false;
    }
    
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;
            
        case 'username':
            return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $input);
            
        case 'password':
            return strlen($input) >= 8;
            
        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT) !== false;
            
        case 'float':
            return filter_var($input, FILTER_VALIDATE_FLOAT) !== false;
            
        case 'date':
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $input) && strtotime($input) !== false;
            
        case 'string':
        default:
            // Basic string validation - allow letters, numbers, spaces, and common punctuation
            return preg_match('/^[a-zA-Z0-9\s.,!?@#%&*()\-_\'"+=:;\/\\\\]+$/', $input);
    }
}

// Output sanitization function
function sanitizeOutput($data) {
    if (is_array($data)) {
        return array_map('sanitizeOutput', $data);
    }
    
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
?>