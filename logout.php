<?php
session_start();

// Include config for logging functions
include('config.php');
include('audit_log.php');

// Log the logout activity
if (isset($_SESSION['user_id']) && function_exists('logActivity')) {
    logActivity($_SESSION['user_id'], "LOGOUT", "User logged out from IP: " . $_SERVER['REMOTE_ADDR']);
}

// Destroy all session data
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect to home page
header("Location: index.html");
exit;
?>