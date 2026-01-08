<?php
session_start();
include('config.php');

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

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = 'user';

    // 3. Validation Logic
    if (!preg_match('/^[a-zA-Z0-9_]{5,15}$/', $username)) {
        $message = "Username mestilah 5-15 aksara (huruf, nombor, garis bawah).";
        $message_type = "error-box";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format emel tidak sah.";
        $message_type = "error-box";
    } elseif (strlen($password) < 8 || !preg_match("/[A-Za-z]/", $password) || !preg_match("/[0-9]/", $password)) {
        $message = "Kata laluan mesti sekurang-kurangnya 8 aksara dengan huruf & nombor.";
        $message_type = "error-box";
    } else {
        // 4. SECURITY UPGRADE: Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $hashed_password, $email, $role);

        if ($stmt->execute()) {
            // SUCCESS: Automatically redirect to login page
            header("Location: login.php");
            exit;
        } else {
            $message = "Ralat: Username atau Emel sudah digunakan.";
            $message_type = "error-box";
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
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <div class="input-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="5-15 aksara" required autocomplete="new-username">
        </div>

        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="contoh@mail.com" required>
        </div>

        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Min. 8 aksara (A-Z + 123)" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn-submit">Daftar Sekarang</button>
    </form>

    <div class="form-footer">
        <p>Sudah mempunyai akaun? <a href="login.php">Log Masuk Di Sini</a></p>
    </div>
</div>

</body>
</html>