<?php
// ==================== SECURE ADMIN DASHBOARD ====================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// IMPORTANT: Load dependencies in correct order
// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';
startSecureSession();            // 4. Now start session

// 1. Security Check: Only admins can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// 2. Generate CSRF Token (already done in startSecureSession, but ensure it exists)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";
$message_type = "";

// --- 3. SECURE DELETE LOGIC (POST method with CSRF) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
    // Validate CSRF token
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }
    
    $delete_id = intval($_POST['delete_id']);
    
    if ($delete_id == $_SESSION['user_id']) {
        $message = "You cannot delete your own account!";
        $message_type = "error";
    } else {
        $conn->begin_transaction();
        try {
            // Delete bookings first (foreign key constraint)
            $stmt1 = $conn->prepare("DELETE FROM bookings WHERE user_id = ?");
            $stmt1->bind_param("i", $delete_id);
            $stmt1->execute();

            // Delete user
            $stmt2 = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt2->bind_param("i", $delete_id);
            $stmt2->execute();

            $conn->commit();
            
            // Log the deletion
            logActivity($_SESSION['user_id'], "USER_DELETED", "Admin deleted user ID: $delete_id");
            
            $message = "User and booking records successfully deleted!";
            $message_type = "success";
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $message = "Error during deletion. Please try again.";
            $message_type = "error";
            error_log("Delete user error: " . $exception->getMessage());
        }
    }
}

// --- 4. SECURE ADD USER LOGIC (WITH PREPARED STATEMENTS) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    // Validate CSRF token
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Input Validation
    if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        $message = "Username must be 3-50 characters (letters, numbers, underscores only).";
        $message_type = "error";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $message_type = "error";
    }
    elseif (strlen($password) < 8 || !preg_match("/[A-Za-z]/", $password) || !preg_match("/[0-9]/", $password)) {
        $message = "Password must be at least 8 characters with letters and numbers.";
        $message_type = "error";
    }
    elseif (!in_array($role, ['user', 'admin'])) {
        $message = "Invalid role selected.";
        $message_type = "error";
    }
    else {
        // SECURE: Check if user exists with prepared statement
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $message = "Username or email already exists!";
            $message_type = "error";
        } else {
            // Hash password securely
            $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            // SECURE: Insert with prepared statement
            $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
            
            if ($insert_stmt->execute()) {
                $new_user_id = $insert_stmt->insert_id;
                
                // Log the action
                logActivity($_SESSION['user_id'], "USER_CREATED", "Admin created new user: $username (ID: $new_user_id)");
                
                $message = "User successfully added!";
                $message_type = "success";
            } else {
                $message = "Error adding user. Please try again.";
                $message_type = "error";
                error_log("Add user error: " . $conn->error);
            }
        }
    }
}

// Fetch all users securely
$users_result = $conn->query("SELECT id, username, email, role FROM users ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Luxury Stay</title>

    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="background-overlay"></div>

<!-- Update the navigation section in admin_dashboard.php -->
<nav class="top-nav">
    <a href="index.html" class="nav-logo">Luxury Stay Admin</a>
    <div class="nav-links">
        <a href="audit_log_view.php" class="btn-nav" style="background: #27ae60;">View Audit Log</a>
        <a href="user_profile.php" class="btn-nav">Profile</a>
        <a href="logout.php" class="btn-nav btn-logout">Logout</a>
    </div>
</nav>

<div class="admin-dashboard">
    <div class="dashboard-card">
        <div class="form-header" style="text-align:left;">
            <h2 style="border-bottom: 3px solid #1e90ff; display: inline-block; padding-bottom: 5px; margin-bottom: 25px;">Add New User</h2>
        </div>

        <?php if ($message): ?>
            <p class="<?php echo $message_type; ?>" style="padding: 15px; margin-bottom: 20px; border-radius: 8px; text-align: center;">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            
            <div class="admin-form-grid">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Username (3-50 chars)" required 
                           pattern="[a-zA-Z0-9_]{3,50}"
                           title="3-50 characters: letters, numbers, or underscores">
                </div>
                
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="user@example.com" required>
                </div>
                
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Min. 8 characters" required 
                           minlength="8"
                           pattern="^(?=.*[A-Za-z])(?=.*\d).{8,}$"
                           title="Minimum 8 characters with at least one letter and one number">
                </div>
                
                <div class="input-group">
                    <label>Role</label>
                    <select name="role" style="width:100%; padding:12px; border-radius:10px; border:2px solid #eee; background: white;">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" name="add_user" class="btn-admin-submit">Add User</button>
        </form>
    </div>

    <div class="dashboard-card">
        <div class="form-header" style="text-align:left;">
            <h2 style="border-bottom: 3px solid #1e90ff; display: inline-block; padding-bottom: 5px; margin-bottom: 25px;">Existing Users</h2>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users_result && $users_result->num_rows > 0): ?>
                        <?php while ($user = $users_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($user['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span class="role-badge <?php echo $user['role'] == 'admin' ? 'role-admin' : 'role-user'; ?>">
                                    <?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('WARNING: This will delete all booking records for this user. Proceed?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" name="delete_user" class="btn-delete" style="cursor: pointer;">Delete</button>
                                </form>
                                <?php else: ?>
                                <span style="color: #999; font-size: 0.85rem;">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>