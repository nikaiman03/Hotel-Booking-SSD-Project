<?php
// ==================== SECURITY CONFIGURATION ====================
// Set these BEFORE starting the session

// For local XAMPP without HTTPS (set to true in production with HTTPS)
$isSecure = false;

// Set secure session cookie parameters
session_set_cookie_params([
    'lifetime' => 1800, // 30 minutes
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => $isSecure,    // false for local, true for production
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Set session ini settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $isSecure ? 1 : 0);
ini_set('session.use_only_cookies', 1);

// Now start the session
session_start();

// ================================================================

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

 // Required to include this file to trace the log
$error = "";

// Check for session timeout (30 minutes)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    // Session expired
    session_unset();
    session_destroy();
    // Restart fresh session
    session_set_cookie_params([
        'lifetime' => 1800,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Update last activity time
$_SESSION['LAST_ACTIVITY'] = time();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. CSRF TOKEN VALIDATION (CRITICAL FIX)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        logFailedLogin($_POST['username'] ?? 'unknown', "CSRF token mismatch");
        die("Security error: Invalid request.");
    }

    // 2. STRICTER INPUT VALIDATION
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate username format
    if (empty($username) || !preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        $error = "Invalid username format. Use only letters, numbers, and underscores (3-50 chars).";
        logFailedLogin($username, "Invalid username format");
    } 
    // Validate password
    elseif (empty($password) || strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
        logFailedLogin($username, "Password too short");
    }
    else {
        // 3. Fetch user using Prepared Statements
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (verify_password_hash($password, $user['password'])) {
                // SUCCESS: Regenerate session ID to prevent fixation
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];

                // Generate NEW CSRF token for next request
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                // Log successful login
                if (function_exists('logActivity')) {
                    logActivity($user['id'], "LOGIN_SUCCESS", "User logged in from IP: " . $_SERVER['REMOTE_ADDR']);
                }

                // Redirect with role-based access control
                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: book_room.php");
                }
                exit;
            } else {
                $error = "Invalid username or password!";
                if (function_exists('logFailedLogin')) {
                    logFailedLogin($username, "Invalid password");
                }
            }
        } else {
            $error = "Invalid username or password!";
            if (function_exists('logFailedLogin')) {
                logFailedLogin($username, "Username not found");
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Luxury Stay</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="background-overlay"></div>
<div class="form-container">
    <div class="form-header">
        <h2>Welcome Back</h2>
        <p>Please log in to your account</p>
    </div>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="POST" action="" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        
        <div class="input-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" 
                   placeholder="Letters, numbers, underscores (3-50 characters)" 
                   required 
                   pattern="[a-zA-Z0-9_]{3,50}"
                   title="3-50 characters: letters, numbers, or underscores"
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username'], ENT_QUOTES) : ''; ?>">
        </div>
        
        <div class="input-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" 
                   placeholder="Minimum 8 characters" 
                   required
                   minlength="8">
        </div>
        
        <button type="submit" class="btn-submit">Login</button>
    </form>
    
    <div class="form-footer">
        <p>Don't have an account? <a href="register.php">Register Now</a></p>
    </div>
</div>
</body>
</html>