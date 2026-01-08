<?php
session_start();
include('config.php');

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's bookings
$sql = "SELECT 
            b.id,
            r.room_type,
            b.check_in_date,
            b.check_out_date
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        WHERE b.user_id = ?
        ORDER BY b.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tempahan Saya | OURHOTEL</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .nav-logo span { color: #1e90ff; }
        .booking-list { margin-top: 20px; }
        .booking-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .booking-card h3 { margin-bottom: 10px; color: #1e90ff; }
        .booking-details { display: flex; gap: 40px; }
        .detail-item { font-size: 14px; }
        .no-bookings {
            text-align: center;
            color: #666;
            font-size: 1.1rem;
            margin-top: 40px;
        }
    </style>
</head>
<body>

<nav class="top-nav">
    <a href="index.html" class="nav-logo">OUR<span>HOTEL</span></a>
    <div class="nav-links">
        <a href="book_room.php" class="btn-nav">Tempah Bilik</a>
        <a href="user_profile.php" class="btn-nav">Akaun Saya</a>
        <a href="logout.php" class="btn-nav btn-logout">Log Keluar</a>
    </div>
</nav>

<div class="form-container">
    <div class="form-header">
        <h2>Tempahan Saya</h2>
        <p>Lihat semua tempahan bilik anda</p>
    </div>

    <div class="booking-list">
        <?php if (empty($bookings)): ?>
            <p class="no-bookings">Anda belum membuat sebarang tempahan.</p>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-card">
                    <h3><?php echo htmlspecialchars($booking['room_type']); ?></h3>
                    <div class="booking-details">
                        <div class="detail-item">
                            <strong>Tarikh Masuk:</strong><br>
                            <?php echo htmlspecialchars($booking['check_in_date']); ?>
                        </div>
                        <div class="detail-item">
                            <strong>Tarikh Keluar:</strong><br>
                            <?php echo htmlspecialchars($booking['check_out_date']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="form-footer">
        <a href="book_room.php">← Kembali ke Tempahan</a>
    </div>
</div>

</body>
</html>
