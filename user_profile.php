<?php
session_start();
include('config.php');

// 1. Security Check: Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['user_id'];
$message = "";
$message_type = "";

// 3. Fetch current user data
$sql = "SELECT username, email FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "User not found!";
    exit;
}

$user_data = $result->fetch_assoc();

// 4. Handle Update Profile Request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    // Sanitize inputs
    $new_username = htmlspecialchars(trim($_POST['username']));
    $new_email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format!";
        $message_type = "error";
    } else {
        // Update both username and email
        $update_sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssi", $new_username, $new_email, $user_id);

        if ($update_stmt->execute()) {
            $message = "Profile successfully updated!";
            $message_type = "success";
            
            $user_data['username'] = $new_username;
            $user_data['email'] = $new_email;
        } else {
            $message = "Error: Email or Username may already be in use.";
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | OURHOTEL</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css"> 
    <style>
        /* Ensuring the span color logic is present if not in styles.css */
        .nav-logo span { color: #1e90ff; }
    </style>
</head>
<body>

<div class="background-overlay"></div>

<nav class="top-nav">
    <a href="index.html" class="nav-logo">OUR<span>HOTEL</span></a>
    <div class="nav-links">
        <a href="book_room.php" class="btn-nav">Book a Room</a>
        <a href="logout.php" class="btn-nav btn-logout">Logout</a>
    </div>
</nav>

<div class="form-container">
    <div class="form-header">
        <h2>User Profile</h2>
        <p>Update your account information</p>
    </div>

    <?php if ($message): ?>
        <p class="<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <div class="input-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" 
                   value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
        </div>

        <div class="input-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" 
                   value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
        </div>

        <button type="submit" class="btn-submit">Save Changes</button>
    </form>

    <div class="form-footer">
        <a href="book_room.php">← Back to Bookings</a>
    </div>
</div>

</body>
</html>