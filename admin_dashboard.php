<?php
session_start();
include('config.php');

// 1. Security Check: Only admins can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// 2. CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";
$message_type = "";

// --- 3. FIXED DELETE LOGIC (From Your Code - Transaction Safe) ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    if ($delete_id == $_SESSION['user_id']) {
        $message = "Anda tidak boleh memadam akaun anda sendiri!";
        $message_type = "error";
    } else {
        $conn->begin_transaction();
        try {
            // Delete bookings first to avoid foreign key error
            $stmt1 = $conn->prepare("DELETE FROM bookings WHERE user_id = ?");
            $stmt1->bind_param("i", $delete_id);
            $stmt1->execute();

            $stmt2 = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt2->bind_param("i", $delete_id);
            $stmt2->execute();

            $conn->commit();
            $message = "Pengguna dan rekod tempahan berjaya dipadam!";
            $message_type = "success";
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $message = "Ralat semasa memadam: " . $exception->getMessage();
            $message_type = "error";
        }
    }
}

// --- 4. ADD USER LOGIC (Merged Security) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Password Validation (Teammate's criteria)
    if (strlen($password) < 8 || !preg_match("/[0-9]/", $password)) {
        $message = "Kata laluan mesti sekurang-kurangnya 8 aksara dan mengandungi nombor.";
        $message_type = "error";
    } elseif (!in_array($role, ['admin', 'user'])) {
        $message = "Peranan tidak sah.";
        $message_type = "error";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
        
        if ($stmt->execute()) {
            $message = "Pengguna baru berjaya didaftarkan!";
            $message_type = "success";
            
            // Auditing (From Teammate's code)
            $admin_name = $_SESSION['username'] ?? 'Admin';
            error_log("Admin $admin_name added new user: $username with role $role.");
        } else {
            $message = "Ralat: " . $stmt->error;
            $message_type = "error";
        }
    }
}

$users_result = $conn->query("SELECT id, username, email, role FROM users");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Luxury Stay</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css"> </head>
<body>

<div class="background-overlay"></div>

<nav class="top-nav">
    <a href="index.html" class="nav-logo">Luxury Stay Admin</a>
    <div class="nav-links">
        <a href="user_profile.php" class="btn-nav">Profil</a>
        <a href="logout.php" class="btn-nav btn-logout">Log Keluar</a>
    </div>
</nav>

<div class="admin-dashboard">
    <div class="dashboard-card">
        <div class="form-header" style="text-align:left;">
            <h2 style="border-bottom: 3px solid #1e90ff; display: inline-block; padding-bottom: 5px; margin-bottom: 25px;">Manage Users</h2>
        </div>

        <?php if ($message): ?>
            <p class="<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="admin-form-grid">
                <div class="input-group">
                    <label>Nama Pengguna</label>
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div class="input-group">
                    <label>Alamat Emel</label>
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>
                <div class="input-group">
                    <label>Kata Laluan</label>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div class="input-group">
                    <label>Peranan (Role)</label>
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
                    <?php if ($users_result->num_rows > 0): ?>
                        <?php while ($user = $users_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="role-badge <?php echo $user['role'] == 'admin' ? 'role-admin' : 'role-user'; ?>">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="?delete_id=<?php echo $user['id']; ?>" class="btn-delete" 
                                   onclick="return confirm('AMARAN: Ini akan memadam semua tempahan pengguna ini juga. Teruskan?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;">Tiada pengguna dijumpai.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>