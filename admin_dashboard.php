<?php
// ==================== SECURITY CONFIGURATION ====================
$isSecure = false; // Set to true in production

session_set_cookie_params([
    'lifetime' => 1800,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => 'Strict'
]);

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $isSecure ? 1 : 0);
ini_set('session.use_only_cookies', 1);

session_start();
// ================================================================

include('config.php');
include('audit_log.php');

// ==================== STRICT ACCESS CONTROL ====================
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Log unauthorized access attempt
    if (function_exists('logActivity') && isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], "UNAUTHORIZED_ACCESS", 
                   "Attempted to access admin dashboard");
    }
    
    header("Location: 403.php");
    exit;
}

// Regenerate session ID periodically for security
if (!isset($_SESSION['last_regeneration']) || time() - $_SESSION['last_regeneration'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Generate CSRF token for admin actions
if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// ==================== ADMIN ACTIONS ====================
$message = "";
$message_type = "";

// Handle user deletion with transaction
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Prevent self-deletion
    if ($delete_id == $_SESSION['user_id']) {
        $message = "You cannot delete your own account!";
        $message_type = "error";
    } else {
        $conn->begin_transaction();
        try {
            // Delete bookings first
            $stmt1 = $conn->prepare("DELETE FROM bookings WHERE user_id = ?");
            $stmt1->bind_param("i", $delete_id);
            $stmt1->execute();

            // Delete user
            $stmt2 = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt2->bind_param("i", $delete_id);
            $stmt2->execute();

            $conn->commit();
            $message = "User and all bookings successfully deleted!";
            $message_type = "success";
            
            // Log the action
            if (function_exists('logActivity')) {
                logActivity($_SESSION['user_id'], "USER_DELETED", "Deleted user ID: $delete_id");
            }
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            error_log("Delete user error: " . $exception->getMessage());
            $message = "Error during deletion. Please try again.";
            $message_type = "error";
        }
    }
}

// Handle add user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    // CSRF validation
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $username = filter_var(trim($_POST['username']), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Input validation
    if (strlen($password) < 8 || !preg_match("/[0-9]/", $password)) {
        $message = "Password must be at least 8 characters and contain a number.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $message_type = "error";
    } elseif (!in_array($role, ['admin', 'user'])) {
        $message = "Invalid role specified.";
        $message_type = "error";
    } else {
        // Check if user exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $message = "Username or email already exists.";
            $message_type = "error";
        } else {
            // Hash password and insert
            $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
            
            if ($stmt->execute()) {
                $message = "New user successfully registered!";
                $message_type = "success";
                
                // Log the action
                if (function_exists('logActivity')) {
                    logActivity($_SESSION['user_id'], "USER_CREATED", "Created user: $username ($role)");
                }
            } else {
                error_log("Add user error: " . $stmt->error);
                $message = "Error creating user. Please try again.";
                $message_type = "error";
            }
        }
    }
}

// ==================== FETCH DATA ====================
// Get users with prepared statement
$stmt = $conn->prepare("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
$stmt->execute();
$users_result = $stmt->get_result();

// Get statistics
$stats_stmt = $conn->prepare("SELECT 
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM bookings) as total_bookings,
    (SELECT COUNT(*) FROM audit_log WHERE action LIKE '%FAILED%' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as failed_logins");
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Hotel Booking System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /* [Keep all your existing CSS styles] */
        /* Add to your existing styles: */
        
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 600;
            color: #1e90ff;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<div class="background-overlay"></div>

<nav class="top-nav">
    <a href="index.html" class="nav-logo">OUR<span>HOTEL</span> Admin</a>
    <div class="nav-links">
        <a href="admin_dashboard.php" class="btn-nav">Dashboard</a>
        <a href="book_room.php" class="btn-nav">Book Room</a>
        <a href="logout.php" class="btn-nav" style="background:#e74c3c;">Logout</a>
    </div>
</nav>

<div class="admin-dashboard">
    <!-- Stats Section -->
    <div class="admin-stats">
        <div class="stat-card">
            <div class="stat-value"><?php echo htmlspecialchars($stats['total_users'] ?? 0); ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo htmlspecialchars($stats['total_bookings'] ?? 0); ?></div>
            <div class="stat-label">Total Bookings</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo htmlspecialchars($stats['failed_logins'] ?? 0); ?></div>
            <div class="stat-label">Failed Logins (24h)</div>
        </div>
    </div>

    <!-- Add User Form -->
    <div class="dashboard-card">
        <div class="form-header">
            <h2>Add New User</h2>
        </div>

        <?php if ($message): ?>
            <div class="<?php echo $message_type === 'success' ? 'success-box' : 'error-box'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <div class="admin-form-grid">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Enter username" required 
                           pattern="[a-zA-Z0-9_]{3,50}"
                           title="3-50 characters: letters, numbers, underscore">
                </div>
                
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="user@example.com" required>
                </div>
                
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Minimum 8 characters with number" required
                           minlength="8"
                           pattern="^(?=.*[A-Za-z])(?=.*\d).{8,}$">
                </div>
                
                <div class="input-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" name="add_user" class="btn-admin-submit">Add User</button>
        </form>
    </div>

    <!-- Users Table -->
    <div class="dashboard-card">
        <div class="form-header">
            <h2>Manage Users</h2>
            <p>Total: <?php echo $users_result->num_rows; ?> users</p>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users_result->num_rows > 0): ?>
                        <?php while ($user = $users_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="role-badge <?php echo $user['role'] === 'admin' ? 'role-admin' : 'role-user'; ?>">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                            <td>
                                <a href="?delete_id=<?php echo $user['id']; ?>" class="btn-delete" 
                                   onclick="return confirm('WARNING: This will delete all bookings by this user. Continue?')">
                                    Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center;">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>