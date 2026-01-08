<?php
// utils.php or audit_log.php
function logActivity($user_id, $action, $details) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'];
    $sql = "INSERT INTO audit_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $user_id, $action, $details, $ip);
    $stmt->execute();
}

function logFailedLogin($username, $reason) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'];
    $sql = "INSERT INTO failed_login_log (username, reason, ip_address) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $reason, $ip);
    $stmt->execute();
}
?>