<?php
// ==================== SECURITY CONFIGURATION ====================
$isSecure = false; // Set to true in production with HTTPS

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => 'Lax'
]);

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $isSecure ? 1 : 0);
ini_set('session.use_only_cookies', 1);

session_start();
// ================================================================

include('config.php');
include('audit_log.php'); // Include logging functions

$message = "";
$message_type = ""; 

// 1. CSRF Token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2. CSRF Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = 'user';

    // 3. Enhanced Validation Logic
    if (empty($username) || !preg_match('/^[a-zA-Z0-9_]{5,15}$/', $username)) {
        $message = "Username mestilah 5-15 aksara (huruf, nombor, garis bawah).";
        $message_type = "error-box";
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format emel tidak sah.";
        $message_type = "error-box";
    } elseif (strlen($password) < 8 || !preg_match("/[A-Za-z]/", $password) || !preg_match("/[0-9]/", $password)) {
        $message = "Kata laluan mesti sekurang-kurangnya 8 aksara dengan huruf & nombor.";
        $message_type = "error-box";
    } else {
        // 4. Check for existing username/email
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $message = "Username atau Emel sudah digunakan.";
            $message_type = "error-box";
        } else {
            // 5. SECURITY: Hash the password with bcrypt
            $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $username, $hashed_password, $email, $role);

            if ($stmt->execute()) {
                // 6. Log the registration activity
                $new_user_id = $stmt->insert_id;
                if (function_exists('logActivity')) {
                    $ip = $_SERVER['REMOTE_ADDR'];
                    logActivity($new_user_id, "REGISTRATION", "New user registered from IP: $ip");
                }

                // SUCCESS: Redirect to login page
                $_SESSION['registration_success'] = "Registration successful! Please login.";
                header("Location: login.php");
                exit;
            } else {
                $message = "Ralat sistem. Sila cuba lagi.";
                $message_type = "error-box";
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
    <title>Daftar Akaun | OURHOTEL</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="background-overlay"></div>

<div class="form-container"> 
    <div class="form-header">
        <a href="index.html" style="text-decoration:none; color:#1e90ff; font-size:0.9rem;">← Laman Utama</a>
        <h2 style="margin-top:10px;">Daftar Akaun</h2>
        <p>Sertai kami untuk pengalaman terbaik</p>
    </div>

    <?php if ($message): ?>
        <div class="<?php echo $message_type; ?>" style="margin-bottom: 20px; padding: 10px; border-radius: 5px; text-align: center; <?php echo ($message_type == 'error-box' ? 'background: #ffebee; color: #c62828;' : 'background: #e8f5e9; color: #2e7d32;'); ?>">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

        <div class="input-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="5-15 aksara (huruf, nombor, _)" required 
                   pattern="[a-zA-Z0-9_]{5,15}"
                   title="5-15 characters: letters, numbers, or underscore"
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username'], ENT_QUOTES) : ''; ?>">
        </div>

        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="contoh@mail.com" required
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES) : ''; ?>">
        </div>

        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Min. 8 aksara (mesti ada huruf & nombor)" required 
                   minlength="8"
                   pattern="^(?=.*[A-Za-z])(?=.*\d).{8,}$"
                   title="Minimum 8 characters with at least one letter and one number">
        </div>

        <button type="submit" class="btn-submit">Daftar Sekarang</button>
    </form>

    <div class="form-footer">
        <p>Sudah mempunyai akaun? <a href="login.php">Log Masuk Di Sini</a></p>
    </div>
</div>

</body>
</html>