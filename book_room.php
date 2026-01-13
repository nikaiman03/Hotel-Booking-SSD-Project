<?php
// ==================== SECURE BOOKING PAGE ====================

// IMPORTANT: Load dependencies in correct order
// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';
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

// 4. Fetch all booked dates for all rooms to pre-load JavaScript
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
    
    <!-- Date Range Picker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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

            <!-- In book_room.php, change this part of the form: -->
            <div class="input-group">
                <label for="room_id">Room Type</label>
                <<select id="room_id" name="room_id" required onchange="updateBookedDates()">
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

            <div class="input-group">
                <label for="check_in_date">Check-in Date</label>
                <input type="text" id="check_in_date" name="check_in_date" required 
                       placeholder="Select check-in date" readonly>
            </div>

            <div class="input-group">
                <label for="check_out_date">Check-out Date</label>
                <input type="text" id="check_out_date" name="check_out_date" required 
                       placeholder="Select check-out date" readonly>
            </div>

            <div style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px; font-size: 0.9rem;">
                <strong>Booking Period:</strong> 
                <span id="bookingPeriod">Not selected</span>
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

// Initialize date pickers
let checkInPicker, checkOutPicker;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize date pickers
    checkInPicker = flatpickr("#check_in_date", {
        minDate: "today",
        dateFormat: "Y-m-d",
        onChange: function(selectedDates, dateStr) {
            if (selectedDates.length > 0) {
                // Update check-out min date to day after check-in
                checkOutPicker.set('minDate', new Date(selectedDates[0].getTime() + 86400000));
                updateBookingPeriod();
                validateBooking();
            }
        }
    });
    
    checkOutPicker = flatpickr("#check_out_date", {
        minDate: new Date().fp_incr(1), // Tomorrow
        dateFormat: "Y-m-d",
        onChange: function() {
            updateBookingPeriod();
            validateBooking();
        }
    });
    
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
    
    // Add room change event listener
    document.getElementById('room_id').addEventListener('change', updateBookedDates);
});

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
        availabilityDiv.style.color = '#27ae60';
    } else {
        const bookedCount = datesForRoom.length;
        availabilityDiv.innerHTML = `⚠️ ${bookedCount} booking${bookedCount > 1 ? 's' : ''} exist for this room`;
        availabilityDiv.style.color = '#e74c3c';
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
    
    // Update date picker disabled dates
    updateDatePickerDisabledDates(roomId);
}

function updateDatePickerDisabledDates(roomId) {
    const datesForRoom = bookedDates[roomId] || [];
    
    // Convert booked dates to flatpickr disabled date ranges
    const disabledDates = datesForRoom.map(range => {
        return {
            from: range.start,
            to: range.end
        };
    });
    
    // Update both date pickers
    checkInPicker.set('disable', disabledDates);
    checkOutPicker.set('disable', disabledDates);
}

function validateBooking() {
    const roomId = document.getElementById('room_id').value;
    const checkIn = document.getElementById('check_in_date').value;
    const checkOut = document.getElementById('check_out_date').value;
    
    if (!roomId || !checkIn || !checkOut) return;
    
    const datesForRoom = bookedDates[roomId] || [];
    
    // Check if selected dates conflict with booked dates
    const checkInDate = new Date(checkIn);
    const checkOutDate = new Date(checkOut);
    
    for (const range of datesForRoom) {
        const bookedStart = new Date(range.start);
        const bookedEnd = new Date(range.end);
        
        // Check for overlap
        if ((checkInDate <= bookedEnd && checkOutDate >= bookedStart) ||
            (checkInDate >= bookedStart && checkInDate <= bookedEnd) ||
            (checkOutDate >= bookedStart && checkOutDate <= bookedEnd)) {
            
            alert(`⚠️ Warning: The room is booked from ${range.start} to ${range.end}. Please select different dates.`);
            return false;
        }
    }
    
    return true;
}

// Form submission validation
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    if (!validateBooking()) {
        e.preventDefault();
        return false;
    }
    
    // Check if dates are selected
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
    
    return true;
});
</script>

</body>
</html>