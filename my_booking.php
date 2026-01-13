<?php
// ==================== SECURE MY BOOKINGS PAGE ====================

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';
startSecureSession();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Prevent session fixation
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) {
    // Regenerate session ID every 30 minutes
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Check for session hijacking
if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
    session_destroy();
    header("Location: login.php?error=session_hijacked");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user's bookings with prepared statement - ADDED ROOM NUMBER
$sql = "SELECT 
            b.id,
            r.room_type,
            r.room_number,
            r.price,
            b.check_in_date,
            b.check_out_date
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        WHERE b.user_id = ?
        ORDER BY b.check_in_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

// Calculate total nights and price for each booking
foreach ($bookings as &$booking) {
    $check_in = new DateTime($booking['check_in_date']);
    $check_out = new DateTime($booking['check_out_date']);
    $nights = $check_in->diff($check_out)->days;
    $booking['nights'] = $nights;
    $booking['total_price'] = $nights * $booking['price'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | OURHOTEL</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="myBookingcss.css">
</head>
<body>

<div class="background-overlay"></div>

<nav class="top-nav">
    <a href="index.html" class="nav-logo">OUR<span>HOTEL</span></a>
    <div class="nav-links">
        <a href="book_room.php" class="btn-nav">Book Room</a>
        <a href="user_profile.php" class="btn-nav">My Account</a>
        <a href="logout.php" class="btn-nav btn-logout">Logout</a>
    </div>
</nav>

<div class="main-container">
    <div class="page-header">
        <h1>My Bookings</h1>
        <p>View all your room reservations</p>
    </div>

    <div class="booking-list">
        <?php if (empty($bookings)): ?>
            <div class="no-bookings">
                <div class="no-bookings-icon">📅</div>
                <h3>No Bookings Yet</h3>
                <p>You haven't made any room reservations.</p>
                <a href="book_room.php" class="btn-primary">Book a Room Now</a>
            </div>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-card">
                    <div class="booking-header">
                        <h3><?php echo htmlspecialchars($booking['room_type'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <span class="booking-id">
                            Booking #<?php echo htmlspecialchars($booking['id'], ENT_QUOTES, 'UTF-8'); ?>
                            • Room <?php echo htmlspecialchars($booking['room_number'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>
                    
                    <div class="booking-details">
                        <div class="detail-item">
                            <strong>Check-in Date</strong>
                            <span><?php echo date('d M Y', strtotime($booking['check_in_date'])); ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <strong>Check-out Date</strong>
                            <span><?php echo date('d M Y', strtotime($booking['check_out_date'])); ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <strong>Number of Nights</strong>
                            <span><?php echo $booking['nights']; ?> night<?php echo $booking['nights'] > 1 ? 's' : ''; ?></span>
                        </div>
                        
                        <!-- ADD THIS NEW ITEM -->
                        <div class="detail-item">
                            <strong>Room Number</strong>
                            <span>Room <?php echo htmlspecialchars($booking['room_number'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <div class="price-tag">
                                Total: RM <?php echo number_format($booking['total_price'], 2); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <a href="book_room.php" class="back-link">← Back to Book a Room</a>
</div>

</body>
</html>