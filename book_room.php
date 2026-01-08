<?php
session_start();
include('config.php');

// 1. Security Check: Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Fetch rooms for the dropdown
$sql_rooms = "SELECT id, room_type FROM rooms";
$rooms_result = $conn->query($sql_rooms);

$message = "";
$message_type = "";

// 3. Handle Booking Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $room_id = $_POST['room_id'];
    $check_in_date = $_POST['check_in_date'];
    $check_out_date = $_POST['check_out_date'];
    $user_id = $_SESSION['user_id'];

    if ($check_out_date <= $check_in_date) {
        $message = "Tarikh daftar keluar mestilah selepas tarikh daftar masuk.";
        $message_type = "error-box";
    } else {
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $user_id, $room_id, $check_in_date, $check_out_date);

        if ($stmt->execute()) {
            $message = "Bilik berjaya ditempah! Jumpa anda nanti.";
            $message_type = "success-box";
        } else {
            $message = "Ralat: " . $stmt->error;
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
    <title>Tempah Bilik | OURHOTEL</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e90ff; 
            --primary-dark: #1c3f95;
            --success: #27ae60;
            --white: #ffffff;
            --text: #333333;
            --error: #e74c3c;
            --bg-card: rgba(255, 255, 255, 0.98);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            min-height: 100vh;
            display: flex; flex-direction: column; align-items: center;
            position: relative; background: #000;
        }

        .background-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)),
                        url('https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&w=1500&q=80');
            background-size: cover; background-position: center;
            filter: blur(5px); z-index: -1; transform: scale(1.1);
        }

        .top-nav {
            width: 100%; padding: 15px 5%;
            display: flex; justify-content: space-between; align-items: center;
            background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(10px);
            position: fixed; top: 0; left: 0; z-index: 100;
        }

        /* Specific Brand Color Update */
        .nav-logo { color: white; font-size: 1.5rem; font-weight: 600; text-decoration: none; }
        .nav-logo span { color: var(--primary); }
        
        .nav-links a { margin-left: 15px; }

        .btn-nav {
            padding: 8px 20px; background: var(--primary); color: white;
            text-decoration: none; border-radius: 50px; font-weight: 600;
            transition: 0.3s; font-size: 0.9rem;
        }

        .booking-container {
            width: 90%; max-width: 900px;
            margin: 120px 0 40px 0;
            display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
        }

        .booking-card {
            background: var(--bg-card);
            padding: 40px; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.5);
        }

        .price-card {
            background: rgba(0, 0, 0, 0.5); color: white;
            padding: 30px; border-radius: 20px; backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: var(--text); }

        .input-group select, .input-group input {
            width: 100%; padding: 12px; border: 2px solid #ddd;
            border-radius: 10px; font-size: 1rem;
        }

        .btn-book {
            width: 100%; padding: 15px; background: var(--success);
            color: white; border: none; border-radius: 12px;
            font-size: 1.1rem; font-weight: 600; cursor: pointer;
            transition: 0.3s;
        }

        .error-box { background: #fee; color: var(--error); padding: 10px; border-radius: 8px; text-align: center; margin-bottom: 20px; border: 1px solid var(--error); font-size: 0.9rem; }
        .success-box { background: #efe; color: var(--success); padding: 10px; border-radius: 8px; text-align: center; margin-bottom: 20px; border: 1px solid var(--success); font-size: 0.9rem; }

        .price-item { display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
        .price-item h4 { color: var(--primary); }
        
        @media (max-width: 768px) { .booking-container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="background-overlay"></div>

<nav class="top-nav">
    <a href="index.html" class="nav-logo">OUR<span>HOTEL</span></a>
    <div class="nav-links">
        <a href="user_profile.php" class="btn-nav">Akaun Saya</a>
        <a href="my_booking.php" class="btn-nav">Tempahan Saya</a>
        <a href="logout.php" class="btn-nav" style="background:#e74c3c;">Log Keluar</a>
    </div>
</nav>

<div class="booking-container">
    <div class="booking-card">
        <div style="text-align: center; margin-bottom: 25px;">
            <h2 style="font-size: 1.8rem; color: var(--text);">Tempah Bilik</h2>
            <p>Rancang penginapan mewah anda</p>
        </div>

        <?php if ($message): ?>
            <div class="<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="input-group">
                <label for="room_id">Jenis Bilik</label>
                <select id="room_id" name="room_id" required>
                    <option value="" disabled selected>Pilih jenis bilik...</option>
                    <?php
                    if ($rooms_result && $rooms_result->num_rows > 0) {
                        while ($row = $rooms_result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['id']) . "'>" . htmlspecialchars($row['room_type']) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="input-group">
                <label for="check_in_date">Tarikh Daftar Masuk</label>
                <input type="date" id="check_in_date" name="check_in_date" required min="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="input-group">
                <label for="check_out_date">Tarikh Daftar Keluar</label>
                <input type="date" id="check_out_date" name="check_out_date" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
            </div>

            <button type="submit" class="btn-book">Tempah Sekarang</button>
        </form>
    </div>

    <div class="price-card">
        <h3 style="margin-bottom: 20px; border-bottom: 2px solid var(--primary); display: inline-block;">Kadar Harga & Jenis Bilik</h3>
        <div class="price-item">
            <div>
                <h4>Standard</h4>
                <p style="font-size: 0.8rem; opacity: 0.8;">Sesuai untuk 1-2 orang.</p>
            </div>
            <p class="price">RM 120.00 / malam</p>
        </div>
        <div class="price-item">
            <div>
                <h4>Deluxe</h4>
                <p style="font-size: 0.8rem; opacity: 0.8;">Pemandangan bandar & Wi-Fi laju.</p>
            </div>
            <p class="price">RM 180.00 / malam</p>
        </div>
        <div class="price-item">
            <div>
                <h4>Family</h4>
                <p style="font-size: 0.8rem; opacity: 0.8;">Luas, untuk 4 orang tamu.</p>
            </div>
            <p class="price">RM 250.00 / malam</p>
        </div>
        <div class="price-item">
            <div>
                <h4>Suite</h4>
                <p style="font-size: 0.8rem; opacity: 0.8;">Kemewahan maksima dengan Jakuzi.</p>
            </div>
            <p class="price">RM 350.00 / malam</p>
        </div>
    </div>
</div>

</body>
</html>