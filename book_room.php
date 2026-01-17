<?php
// ==================== SECURE BOOKING PAGE ====================

// IMPORTANT: Load dependencies in correct order
require_once __DIR__ . '/vendor/autoload.php';
startSecureSession();

// 1. Security Check: Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 3. Fetch rooms for the dropdown
$sql_rooms = "SELECT id, room_type, room_number FROM rooms ORDER BY id ASC";
$rooms_result = $conn->query($sql_rooms);

// 4. Fetch all booked dates for all rooms
$booked_dates = [];
$booked_query = "SELECT room_id, check_in_date, check_out_date FROM bookings";
$booked_result = $conn->query($booked_query);
if ($booked_result && $booked_result->num_rows > 0) {
    while ($row = $booked_result->fetch_assoc()) {
        $room_id = $row['room_id'];
        if (!isset($booked_dates[$room_id])) {
            $booked_dates[$room_id] = [];
        }
        $booked_dates[$room_id][] = [
            'start' => $row['check_in_date'],
            'end' => $row['check_out_date']
        ];
    }
}

$message = "";
$message_type = "";

// 5. Handle Booking Submission
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
            // Check for existing bookings
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
                // Insert booking
                $stmt = $conn->prepare("INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiss", $user_id, $room_id, $check_in_date, $check_out_date);

                if ($stmt->execute()) {
                    $booking_id = $stmt->insert_id;
                    
                    // Log the booking
                    logActivity($user_id, "BOOKING_CREATED", "User booked room ID: $room_id (Booking ID: $booking_id)");
                    
                    // Redirect to my_booking.php
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
    <link rel="stylesheet" type="text/css" href="bookcss.css">
    
    <!-- Removed Flatpickr CSS & JS -->
    <style>
        /* Custom Date Input Styling */
        .date-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .date-input-wrapper input[type="date"] {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 5px;
            background: rgba(255,255,255,0.1);
            color: var(--text);
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .date-input-wrapper input[type="date"]:focus {
            border-color: var(--primary);
            background: rgba(255,255,255,0.15);
            outline: none;
            box-shadow: 0 0 0 2px rgba(45, 156, 219, 0.3);
        }
        
        .date-input-wrapper::after {
            content: "📅";
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            font-size: 1.2rem;
            opacity: 0.7;
        }
        
        /* Style for date input in WebKit browsers */
        .date-input-wrapper input[type="date"]::-webkit-calendar-picker-indicator {
            background: transparent;
            color: transparent;
            cursor: pointer;
            height: auto;
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            z-index: 1;
        }
        
        /* Style for Firefox */
        .date-input-wrapper input[type="date"]::-moz-calendar-picker-indicator {
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
            position: absolute;
            left: 0;
            top: 0;
        }
    </style>
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
            <p style="font-size: 0.9rem; color: #666; margin-top: 5px;">
                <span style="color: #e74c3c;">⚠️</span> Dates in red are already booked
            </p>
        </div>

        <?php if ($message): ?>
            <div class="<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off" id="bookingForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

            <!-- Room Selection -->
            <div class="input-group">
                <label for="room_id">Room Type</label>
                <select id="room_id" name="room_id" required onchange="resetDates()">
                    <option value="" disabled selected>Select room type...</option>
                    <?php
                    if ($rooms_result && $rooms_result->num_rows > 0) {
                        while ($row = $rooms_result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') . "'>" 
                                 . htmlspecialchars($row['room_type'], ENT_QUOTES, 'UTF-8') 
                                 . " (Room " . htmlspecialchars($row['room_number'], ENT_QUOTES, 'UTF-8') . ")"
                                 . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <!-- Check-in Date -->
            <div class="input-group">
                <label for="check_in_date">Check-in Date</label>
                <div class="date-input-wrapper">
                    <input type="date" id="check_in_date" name="check_in_date" required 
                           min="<?php echo date('Y-m-d'); ?>"
                           onchange="updateCheckOutMinDate()">
                </div>
            </div>

            <!-- Check-out Date -->
            <div class="input-group">
                <label for="check_out_date">Check-out Date</label>
                <div class="date-input-wrapper">
                    <input type="date" id="check_out_date" name="check_out_date" required 
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                           onchange="validateDates()">
                </div>
            </div>

            <div style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px; font-size: 0.9rem;">
                <strong>Booking Period:</strong> 
                <span id="bookingPeriod">Not selected</span>
            </div>
            
            <!-- Room Availability Status -->
            <div id="roomAvailability" style="display: none; margin-bottom: 15px; padding: 10px; border-radius: 5px;"></div>

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
        
        <!-- Booked Dates Section -->
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.2);">
            <h4 style="margin-bottom: 10px;">📅 Booked Dates for Selected Room</h4>
            <div id="bookedDatesList" style="max-height: 200px; overflow-y: auto; font-size: 0.85rem;">
                <p style="color: #aaa; font-style: italic;">Select a room to see booked dates...</p>
            </div>
        </div>
    </div>
</div>

<!-- Convert PHP booked dates to JavaScript -->
<script>
// Booked dates from PHP database
const bookedDates = <?php echo json_encode($booked_dates); ?>;

// Format date to YYYY-MM-DD
function formatDate(date) {
    return date.toISOString().split('T')[0];
}

// Calculate tomorrow's date
function getTomorrow() {
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow;
}

// RESET DATES WHEN ROOM CHANGES
function resetDates() {
    const checkInInput = document.getElementById('check_in_date');
    const checkOutInput = document.getElementById('check_out_date');
    const bookingPeriod = document.getElementById('bookingPeriod');
    
    // Reset date inputs
    checkInInput.value = '';
    checkOutInput.value = '';
    
    // Reset min dates
    const today = new Date();
    checkInInput.min = formatDate(today);
    checkOutInput.min = formatDate(getTomorrow());
    
    // Reset booking period display
    bookingPeriod.textContent = 'Not selected';
    
    // Reset border colors
    checkInInput.style.borderColor = '';
    checkOutInput.style.borderColor = '';
    
    // Update booked dates list for the new room
    updateBookedDates();
}

// Update check-out min date based on check-in
function updateCheckOutMinDate() {
    const checkInInput = document.getElementById('check_in_date');
    const checkOutInput = document.getElementById('check_out_date');
    
    if (checkInInput.value) {
        const checkInDate = new Date(checkInInput.value);
        const nextDay = new Date(checkInDate);
        nextDay.setDate(nextDay.getDate() + 1);
        
        // Set min date for check-out to next day after check-in
        checkOutInput.min = formatDate(nextDay);
        
        // If current check-out is before the new min, reset it
        if (checkOutInput.value && new Date(checkOutInput.value) <= nextDay) {
            checkOutInput.value = formatDate(nextDay);
        }
        
        updateBookingPeriod();
    }
}

// Validate dates don't overlap with booked dates
function validateDates() {
    const roomId = document.getElementById('room_id').value;
    const checkIn = document.getElementById('check_in_date').value;
    const checkOut = document.getElementById('check_out_date').value;
    
    if (!roomId || !checkIn || !checkOut) {
        return true;
    }
    
    const datesForRoom = bookedDates[roomId] || [];
    const checkInDate = new Date(checkIn);
    const checkOutDate = new Date(checkOut);
    
    // Check if selected dates conflict with booked dates
    for (const range of datesForRoom) {
        const bookedStart = new Date(range.start);
        const bookedEnd = new Date(range.end);
        
        // Check for overlap
        if ((checkInDate <= bookedEnd && checkOutDate >= bookedStart) ||
            (checkInDate >= bookedStart && checkInDate <= bookedEnd) ||
            (checkOutDate >= bookedStart && checkOutDate <= bookedEnd)) {
            
            alert(`⚠️ Warning: The room is booked from ${range.start} to ${range.end}. Please select different dates.`);
            
            // Highlight the inputs in red
            document.getElementById('check_in_date').style.borderColor = '#e74c3c';
            document.getElementById('check_out_date').style.borderColor = '#e74c3c';
            
            return false;
        }
    }
    
    // Reset border color if valid
    document.getElementById('check_in_date').style.borderColor = '';
    document.getElementById('check_out_date').style.borderColor = '';
    return true;
}

// Update booking period display
function updateBookingPeriod() {
    const checkIn = document.getElementById('check_in_date').value;
    const checkOut = document.getElementById('check_out_date').value;
    
    if (checkIn && checkOut) {
        const start = new Date(checkIn);
        const end = new Date(checkOut);
        const nights = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
        
        document.getElementById('bookingPeriod').innerHTML = 
            `${checkIn} to ${checkOut} (${nights} night${nights > 1 ? 's' : ''})`;
    }
}

// Update booked dates list when room changes
function updateBookedDates() {
    const roomSelect = document.getElementById('room_id');
    const roomId = roomSelect.value;
    const availabilityDiv = document.getElementById('roomAvailability');
    const bookedDatesDiv = document.getElementById('bookedDatesList');
    
    if (!roomId) {
        availabilityDiv.style.display = 'none';
        bookedDatesDiv.innerHTML = '<p style="color: #aaa; font-style: italic;">Select a room to see booked dates...</p>';
        return;
    }
    
    // Get booked dates for this room
    const datesForRoom = bookedDates[roomId] || [];
    
    // Update availability message
    if (datesForRoom.length === 0) {
        availabilityDiv.innerHTML = '✅ All dates are available for booking';
        availabilityDiv.style.backgroundColor = 'rgba(39, 174, 96, 0.1)';
        availabilityDiv.style.color = '#27ae60';
        availabilityDiv.style.border = '1px solid #27ae60';
    } else {
        const bookedCount = datesForRoom.length;
        availabilityDiv.innerHTML = `⚠️ ${bookedCount} booking${bookedCount > 1 ? 's' : ''} exist for this room`;
        availabilityDiv.style.backgroundColor = 'rgba(231, 76, 60, 0.1)';
        availabilityDiv.style.color = '#e74c3c';
        availabilityDiv.style.border = '1px solid #e74c3c';
    }
    availabilityDiv.style.display = 'block';
    
    // Update booked dates list
    if (datesForRoom.length === 0) {
        bookedDatesDiv.innerHTML = '<p style="color: #27ae60;">✅ No bookings yet - All dates available!</p>';
    } else {
        let html = '<ul style="padding-left: 20px; margin: 0;">';
        datesForRoom.forEach(range => {
            const start = new Date(range.start);
            const end = new Date(range.end);
            const nights = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
            html += `<li style="margin-bottom: 5px; color: #ff6b6b;">
                <span style="color: #fff;">📅</span> ${range.start} to ${range.end} (${nights} night${nights > 1 ? 's' : ''})
            </li>`;
        });
        html += '</ul>';
        bookedDatesDiv.innerHTML = html;
    }
}

// Initialize date inputs
document.addEventListener('DOMContentLoaded', function() {
    // Set today as min date for check-in
    const today = new Date();
    document.getElementById('check_in_date').min = formatDate(today);
    
    // Set tomorrow as min date for check-out
    const tomorrow = getTomorrow();
    document.getElementById('check_out_date').min = formatDate(tomorrow);
    
    // Update period display when dates change
    document.getElementById('check_in_date').addEventListener('change', function() {
        updateBookingPeriod();
        validateDates();
    });
    
    document.getElementById('check_out_date').addEventListener('change', function() {
        updateBookingPeriod();
        validateDates();
    });
    
    // Add room change event listener - changed to resetDates
    document.getElementById('room_id').addEventListener('change', resetDates);
});

// Form submission validation
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    // First validate date selection
    const checkIn = document.getElementById('check_in_date').value;
    const checkOut = document.getElementById('check_out_date').value;
    const roomId = document.getElementById('room_id').value;
    
    if (!roomId) {
        e.preventDefault();
        alert('Please select a room type.');
        return false;
    }
    
    if (!checkIn || !checkOut) {
        e.preventDefault();
        alert('Please select both check-in and check-out dates.');
        return false;
    }
    
    // Check if check-out is after check-in
    const checkInDate = new Date(checkIn);
    const checkOutDate = new Date(checkOut);
    if (checkOutDate <= checkInDate) {
        e.preventDefault();
        alert('Check-out date must be after check-in date.');
        return false;
    }
    
    // Check if check-in is not in the past
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    if (checkInDate < today) {
        e.preventDefault();
        alert('Check-in date cannot be in the past.');
        return false;
    }
    
    // Validate against booked dates
    if (!validateDates()) {
        e.preventDefault();
        return false;
    }
    
    return true;
});
</script>

</body>
</html>