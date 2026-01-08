<?php
session_start();
include('config.php');

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Sanitize input
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 2. Fetch user by username using Prepared Statements
    $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // 3. SECURE VERIFICATION: Check plaintext input against hashed DB password
        if (password_verify($password, $user['password'])) {
            
            // SUCCESS: Setup sessions
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // SECURITY ADDITION: CSRF Token for the session (From Teammate's Logic)
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: book_room.php");
            }
            exit;
        } else {
            $error = "Nama pengguna atau kata laluan salah!";
        }
    } else {
        $error = "Nama pengguna atau kata laluan salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Masuk | Luxury Stay</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css"> </head>
<body>

<div class="background-overlay"></div>

<div class="form-container">
    <div class="form-header">
        <h2>Selamat Datang</h2>
        <p>Sila log masuk ke akaun anda</p>
    </div>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="input-group">
            <label for="username">Nama Pengguna</label>
            <input type="text" id="username" name="username" placeholder="Masukkan username" required>
        </div>

        <div class="input-group">
            <label for="password">Kata Laluan</label>
            <input type="password" id="password" name="password" placeholder="Masukkan kata laluan" required>
        </div>

        <button type="submit" class="btn-submit">Log Masuk</button>
    </form>

    <div class="form-footer">
        <p>Belum mempunyai akaun? <a href="register.php">Daftar Sekarang</a></p>
    </div>
</div>

</body>
</html>