<?php
// ==================== SECURE AUDIT LOG VIEW ====================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load dependencies in correct order
require_once __DIR__ . '/vendor/autoload.php';
startSecureSession();

// 1. Security Check: Only admins can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// 2. Generate CSRF Token (if not exists)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 3. Handle Clear Logs Request (POST with CSRF)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['clear_logs'])) {
    // Validate CSRF token
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }
    
    $conn->begin_transaction();
    try {
        // Clear audit logs (keep last 1000 records for security)
        $clear_audit = $conn->prepare("DELETE FROM audit_log WHERE id NOT IN (SELECT id FROM audit_log ORDER BY created_at DESC LIMIT 1000)");
        $clear_audit->execute();
        
        // Clear failed login logs (keep last 500 records)
        $clear_failed = $conn->prepare("DELETE FROM failed_login_log WHERE id NOT IN (SELECT id FROM failed_login_log ORDER BY attempted_at DESC LIMIT 500)");
        $clear_failed->execute();
        
        $conn->commit();
        
        // Log the action
        logActivity($_SESSION['user_id'], "LOGS_CLEARED", "Admin cleared old audit logs");
        
        $message = "Logs cleared successfully (kept recent records for security).";
        $message_type = "success";
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $message = "Error clearing logs. Please try again.";
        $message_type = "error";
        error_log("Clear logs error: " . $exception->getMessage());
    }
}

// 4. Fetch audit logs with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Get total count for pagination
$total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM audit_log");
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch audit logs
$audit_stmt = $conn->prepare("
    SELECT id, user_id, action, details, ip_address, user_agent, created_at 
    FROM audit_log 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$audit_stmt->bind_param("ii", $limit, $offset);
$audit_stmt->execute();
$audit_result = $audit_stmt->get_result();

// 5. Fetch failed login logs
$failed_stmt = $conn->prepare("
    SELECT id, username, reason, ip_address, attempted_at 
    FROM failed_login_log 
    ORDER BY attempted_at DESC 
    LIMIT 100
");
$failed_stmt->execute();
$failed_result = $failed_stmt->get_result();

// 6. Get statistics
$stats_stmt = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM audit_log WHERE action LIKE 'LOGIN_%') as total_logins,
        (SELECT COUNT(*) FROM audit_log WHERE action = 'LOGIN_SUCCESS') as successful_logins,
        (SELECT COUNT(*) FROM failed_login_log) as failed_logins,
        (SELECT COUNT(*) FROM audit_log WHERE action LIKE 'BOOKING_%') as total_bookings,
        (SELECT COUNT(*) FROM audit_log WHERE action = 'USER_CREATED') as users_created,
        (SELECT COUNT(*) FROM audit_log WHERE action = 'USER_DELETED') as users_deleted
");
$stats = $stats_stmt->fetch_assoc();

// Function to format action for display
function formatAction($action) {
    $action_map = [
        'LOGIN_SUCCESS' => '<span style="color: #27ae60; font-weight: 600;">✅ Login Success</span>',
        'LOGIN_FAILED' => '<span style="color: #e74c3c; font-weight: 600;">❌ Login Failed</span>',
        'LOGOUT' => '<span style="color: #3498db; font-weight: 600;">🚪 Logout</span>',
        'REGISTRATION' => '<span style="color: #9b59b6; font-weight: 600;">📝 Registration</span>',
        'USER_CREATED' => '<span style="color: #2ecc71; font-weight: 600;">👤 User Created</span>',
        'USER_DELETED' => '<span style="color: #e74c3c; font-weight: 600;">🗑️ User Deleted</span>',
        'BOOKING_CREATED' => '<span style="color: #f39c12; font-weight: 600;">🏨 Booking Created</span>',
        'SESSION_TIMEOUT' => '<span style="color: #95a5a6; font-weight: 600;">⏰ Session Timeout</span>',
        'LOGS_CLEARED' => '<span style="color: #34495e; font-weight: 600;">🧹 Logs Cleared</span>',
        'FORCE_LOGOUT' => '<span style="color: #c0392b; font-weight: 600;">⚠️ Force Logout</span>'
    ];
    
    return $action_map[$action] ?? '<span style="color: #7f8c8d; font-weight: 600;">' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '</span>';
}

// Function to truncate long text
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    return htmlspecialchars(substr($text, 0, $length), ENT_QUOTES, 'UTF-8') . '...';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log | Luxury Stay Admin</title>
    <style>
        /* Internal CSS - No external dependencies to avoid CSP issues */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)),
                        url('./hotelb/img/photo.png');
            background-size: cover;
            background-position: center;
            color: #333;
        }

        .background-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7));
            filter: blur(8px);
            z-index: -1;
            transform: scale(1.1);
        }

        .top-nav {
            width: 100%;
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            position: fixed;
            top: 0;
            z-index: 100;
        }

        .nav-logo {
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 10px;
        }

        .btn-nav {
            text-decoration: none;
            color: white;
            padding: 8px 18px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            background: rgba(255,255,255,0.2);
            transition: 0.3s;
        }

        .btn-nav:hover {
            background: rgba(255,255,255,0.3);
        }

        .btn-logout {
            background: #e74c3c;
        }

        .admin-container {
            width: 95%;
            max-width: 1300px;
            margin: 120px auto 40px;
        }

        .dashboard-card {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            margin-bottom: 30px;
            border: 1px solid rgba(0,0,0,0.1);
        }

        .form-header {
            text-align: left;
            margin-bottom: 25px;
        }

        .form-header h2 {
            border-bottom: 3px solid #1e90ff;
            display: inline-block;
            padding-bottom: 5px;
            color: #2c3e50;
            font-size: 1.8rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #1e90ff;
        }

        .stat-card h3 {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .stat-positive {
            border-left-color: #27ae60;
        }

        .stat-negative {
            border-left-color: #e74c3c;
        }

        .stat-warning {
            border-left-color: #f39c12;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th {
            background: #1e3a8a;
            color: white;
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            color: #333;
            font-size: 0.9rem;
            vertical-align: top;
        }

        tr:nth-child(even) {
            background: #f8fafc;
        }

        tr:hover {
            background: #f0f7ff;
        }

        .ip-address {
            font-family: monospace;
            background: #f1f1f1;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .timestamp {
            color: #7f8c8d;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .details-cell {
            max-width: 300px;
            word-wrap: break-word;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .pagination a, .pagination span {
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: #2c3e50;
            background: #f1f1f1;
            font-weight: 600;
            transition: 0.3s;
        }

        .pagination a:hover {
            background: #1e90ff;
            color: white;
        }

        .pagination .current {
            background: #1e90ff;
            color: white;
        }

        .clear-logs-form {
            margin-top: 20px;
            padding: 20px;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 12px;
        }

        .clear-logs-form h3 {
            color: #856404;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .btn-clear {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-clear:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .btn-back {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-back:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        @media (max-width: 768px) {
            .admin-container {
                width: 98%;
                margin: 100px auto 20px;
            }
            
            .dashboard-card {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .stat-value {
                font-size: 1.5rem;
            }
            
            th, td {
                padding: 10px;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .top-nav {
                padding: 10px 3%;
            }
            
            .nav-links {
                gap: 5px;
            }
            
            .btn-nav {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>

<div class="background-overlay"></div>

<nav class="top-nav">
    <a href="index.html" class="nav-logo">Luxury Stay Admin</a>
    <div class="nav-links">
        <a href="admin_dashboard.php" class="btn-nav">Dashboard</a>
        <a href="user_profile.php" class="btn-nav">Profile</a>
        <a href="logout.php" class="btn-nav btn-logout">Logout</a>
    </div>
</nav>

<div class="admin-container">
    <!-- Statistics Dashboard -->
    <div class="dashboard-card">
        <div class="form-header">
            <h2>Audit Log Dashboard</h2>
        </div>

        <?php if (isset($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card stat-positive">
                <h3>Successful Logins</h3>
                <div class="stat-value"><?php echo htmlspecialchars($stats['successful_logins'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            
            <div class="stat-card stat-negative">
                <h3>Failed Logins</h3>
                <div class="stat-value"><?php echo htmlspecialchars($stats['failed_logins'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            
            <div class="stat-card stat-warning">
                <h3>Total Bookings</h3>
                <div class="stat-value"><?php echo htmlspecialchars($stats['total_bookings'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Users Created</h3>
                <div class="stat-value"><?php echo htmlspecialchars($stats['users_created'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Users Deleted</h3>
                <div class="stat-value"><?php echo htmlspecialchars($stats['users_deleted'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Total Log Entries</h3>
                <div class="stat-value"><?php echo htmlspecialchars($total_rows ?? 0, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>
    </div>

    <!-- Audit Log Table -->
    <div class="dashboard-card">
        <div class="form-header">
            <h2>Audit Log Entries (Page <?php echo $page; ?> of <?php echo $total_pages; ?>)</h2>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Timestamp</th>
                        <th>User ID</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP Address</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($audit_result && $audit_result->num_rows > 0): ?>
                        <?php while ($log = $audit_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="timestamp"><?php echo htmlspecialchars($log['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($log['user_id'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo formatAction($log['action']); ?></td>
                            <td class="details-cell"><?php echo truncateText($log['details'] ?? ''); ?></td>
                            <td><span class="ip-address"><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo truncateText($log['user_agent'] ?? '', 50); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 30px; color: #7f8c8d;">
                                No audit log entries found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>">Previous</a>
            <?php endif; ?>
            
            <?php 
            // Show page numbers
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            
            for ($i = $start; $i <= $end; $i++):
            ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>">Next</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Failed Login Logs -->
    <div class="dashboard-card">
        <div class="form-header">
            <h2>Failed Login Attempts (Recent 100)</h2>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Timestamp</th>
                        <th>Username</th>
                        <th>Reason</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($failed_result && $failed_result->num_rows > 0): ?>
                        <?php while ($failed = $failed_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($failed['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="timestamp"><?php echo htmlspecialchars($failed['attempted_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><strong><?php echo htmlspecialchars($failed['username'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td>
                                <span style="color: <?php echo $failed['reason'] === 'Invalid password' ? '#e74c3c' : '#f39c12'; ?>; font-weight: 600;">
                                    <?php echo htmlspecialchars($failed['reason'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td><span class="ip-address"><?php echo htmlspecialchars($failed['ip_address'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 30px; color: #7f8c8d;">
                                No failed login attempts found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Clear Logs Form -->
    <div class="dashboard-card">
        <div class="clear-logs-form">
            <h3>⚠️ Clear Old Logs</h3>
            <p style="margin-bottom: 15px; color: #856404;">
                This will clear log entries older than the most recent 1000 audit logs and 500 failed login logs.
                Recent records will be kept for security monitoring.
            </p>
            <form method="POST" onsubmit="return confirm('WARNING: This will permanently delete old log entries (keeping recent ones). Proceed?')">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" name="clear_logs" class="btn-clear">Clear Old Logs</button>
            </form>
        </div>

        <a href="admin_dashboard.php" class="btn-back">← Back to Admin Dashboard</a>
    </div>
</div>

<!-- Security enhancement script -->
<script>
// Prevent caching of this page
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}

// Add security logging to console
console.log('%c🔒 Security Audit Log Accessed', 'color: #27ae60; font-weight: bold; font-size: 14px;');
console.log('%cTimestamp: <?php echo date("Y-m-d H:i:s"); ?>', 'color: #7f8c8d;');
console.log('%cUser ID: <?php echo htmlspecialchars($_SESSION["user_id"] ?? "Unknown", ENT_QUOTES, "UTF-8"); ?>', 'color: #3498db;');

// Auto-refresh logs every 5 minutes
setTimeout(() => {
    console.log('Auto-refreshing audit logs...');
    window.location.reload();
}, 300000); // 5 minutes

// Add security event listener for copy protection
document.addEventListener('copy', function(e) {
    console.warn('Audit log data copied - logged for security');
    // In a real system, you might want to log this to the server
});
</script>

</body>
</html>