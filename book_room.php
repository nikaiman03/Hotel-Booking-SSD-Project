<?php
// ==================== SECURE BOOKING PAGE ====================

// IMPORTANT: Load dependencies in correct order
include('config.php');           // 1. Database connection first
include('audit_log.php');        // 2. Logging functions second
include('session_security.php'); // 3. Session functions last
startSecureSession();            // 4. Now start session

// 1. Security Check: Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Generate CSRF Token (already done in startSecureSession, but ensure it exists)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 3. Fetch rooms for the dropdown using prepared statement
$sql_rooms = "SELECT id, room_type FROM rooms ORDER BY id ASC";
$rooms_result = $conn->query($sql_rooms);

$message = "";
$message_type = "";

// 4. Handle Booking Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $room_id = intval($_POST['room_id']);
    $check_in_date = $_POST['check_in_date'];
    $check_out_date = $_POST['check_out_date'];
    $user_id = $_SESSION['user_id'];

    // Input Validation
    if ($room_id <= 0) {
        $message = "Please select a valid room.";
        $message_type = "error-box";
    }
    elseif (empty($check_in_date) || empty($check_out_date)) {
        $message = "Please select both check-in and check-out dates.";
        $message_type = "error-box";
    }
    elseif ($check_out_date <= $check_in_date) {
        $message = "Check-out date must be after the check-in date.";
        $message_type = "error-box";
    }
    elseif (strtotime($check_in_date) < strtotime(date('Y-m-d'))) {
        $message = "Check-in date cannot be in the past.";
        $message_type = "error-box";
    }
    else {
        // Verify room exists
        $verify_room = $conn->prepare("SELECT id FROM rooms WHERE id = ?");
        $verify_room->bind_param("i", $room_id);
        $verify_room->execute();
        $room_exists = $verify_room->get_result();
        
        if ($room_exists->num_rows == 0) {
            $message = "Invalid room selected.";
            $message_type = "error-box";
        } else {
            // Check for existing bookings (prevent double booking)
            $check_booking = $conn->prepare("SELECT id FROM bookings WHERE room_id = ? 
                                             AND ((check_in_date <= ? AND check_out_date >= ?) 
                                             OR (check_in_date <= ? AND check_out_date >= ?))");
            $check_booking->bind_param("issss", $room_id, $check_out_date, $check_in_date, $check_in_date, $check_out_date);
            $check_booking->execute();
            $existing_booking = $check_booking->get_result();
            
            if ($existing_booking->num_rows > 0) {
                $message = "This room is already booked for the selected dates. Please choose different dates.";
                $message_type = "error-box";
            } else {
                // Insert booking with prepared statement
                $stmt = $conn->prepare("INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiss", $user_id, $room_id, $check_in_date, $check_out_date);

                if ($stmt->execute()) {
                    $booking_id = $stmt->insert_id;
                    
                    // Log the booking
                    logActivity($user_id, "BOOKING_CREATED", "User booked room ID: $room_id (Booking ID: $booking_id)");
                    
                    // Redirect to my_booking.php after successful booking
                    header("Location: my_booking.php");
                    exit;

                } else {
                    $message = "Error processing your booking. Please try again.";
                    $message_type = "error-box";
                    error_log("Booking error: " . $stmt->error);
                }
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
    <title>Book a Room | OURHOTEL</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="bookcss.css">
</head>
<body>

<div class="background-overlay"></div>

<nav class="top-nav">
    <a href="index.html" class="nav-logo">OUR<span>HOTEL</span></a>
    <div class="nav-links">
        <a href="my_booking.php" class="btn-nav">My Bookings</a>
        <a href="user_profile.php" class="btn-nav">My Account</a>
        <a href="logout.php" class="btn-nav" style="background:#e74c3c;">Logout</a>
    </div>
</nav>

<div class="booking-container">
    <div class="booking-card">
        <div style="text-align: center; margin-bottom: 25px;">
            <h2 style="font-size: 1.8rem; color: var(--text);">Book a Room</h2>
            <p>Plan your luxury stay</p>
        </div>

        <?php if ($message): ?>
            <div class="<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

            <div class="input-group">
                <label for="room_id">Room Type</label>
                <select id="room_id" name="room_id" required>
                    <option value="" disabled selected>Select room type...</option>
                    <?php
                    if ($rooms_result && $rooms_result->num_rows > 0) {
                        while ($row = $rooms_result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') . "'>" 
                                 . htmlspecialchars($row['room_type'], ENT_QUOTES, 'UTF-8') . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="input-group">
                <label for="check_in_date">Check-in Date</label>
                <input type="date" id="check_in_date" name="check_in_date" required 
                       min="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="input-group">
                <label for="check_out_date">Check-out Date</label>
                <input type="date" id="check_out_date" name="check_out_date" required 
                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
            </div>

            <button type="submit" class="btn-book">Book Now</button>
        </form>
    </div>

    <div class="price-card">
        <h3 style="margin-bottom: 20px; border-bottom: 2px solid var(--primary); display: inline-block;">Rates & Room Types</h3>
        <div class="price-item">
            <div>
                <h4>Standard</h4>
                <p style="font-size: 0.8rem; opacity: 0.8;">Ideal for 1-2 people.</p>
            </div>
            <p class="price">RM 120.00 / night</p>
        </div>
        <div class="price-item">
            <div>
                <h4>Deluxe</h4>
                <p style="font-size: 0.8rem; opacity: 0.8;">City view & fast Wi-Fi.</p>
            </div>
            <p class="price">RM 180.00 / night</p>
        </div>
        <div class="price-item">
            <div>
                <h4>Family</h4>
                <p style="font-size: 0.8rem; opacity: 0.8;">Spacious, for 4 guests.</p>
            </div>
            <p class="price">RM 250.00 / night</p>
        </div>
        <div class="price-item">
            <div>
                <h4>Suite</h4>
                <p style="font-size: 0.8rem; opacity: 0.8;">Maximum luxury with Jacuzzi.</p>
            </div>
            <p class="price">RM 350.00 / night</p>
        </div>
    </div>
</div>

</body>
</html>