<?php
require_once '../config.php';
require_once '../email_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : 0;

if ($location_id == 0) {
    header('Location: browse_locations.php');
    exit();
}

$conn = getDBConnection();

// Get location details
$stmt = $conn->prepare("SELECT * FROM parking_locations WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $location_id);
$stmt->execute();
$location = $stmt->get_result()->fetch_assoc();

if (!$location) {
    header('Location: browse_locations.php');
    exit();
}

// Get available slots
$stmt = $conn->prepare("SELECT * FROM parking_slots WHERE location_id = ? AND status = 'available'");
$stmt->bind_param("i", $location_id);
$stmt->execute();
$slots = $stmt->get_result();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $slot_id = intval($_POST['slot_id']);
    $booking_type = $_POST['booking_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $start_time = isset($_POST['start_time']) ? $_POST['start_time'] : null;
    $end_time = isset($_POST['end_time']) ? $_POST['end_time'] : null;
    
    // Calculate total amount
    $total_amount = 0;
    if ($booking_type == 'hourly' && $start_time && $end_time) {
        $start = new DateTime($start_time);
        $end = new DateTime($end_time);
        $hours = ($end->getTimestamp() - $start->getTimestamp()) / 3600;
        $total_amount = $hours * $location['price_per_hour'];
    } elseif ($booking_type == 'daily') {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $days = $start->diff($end)->days + 1;
        $total_amount = $days * $location['price_per_day'];
    } elseif ($booking_type == 'monthly') {
        $total_amount = $location['price_per_month'];
    }
    
    // Insert booking
    $stmt = $conn->prepare("
        INSERT INTO bookings (user_id, location_id, slot_id, booking_type, start_date, end_date, start_time, end_time, total_amount)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiisssssd", $_SESSION['user_id'], $location_id, $slot_id, $booking_type, $start_date, $end_date, $start_time, $end_time, $total_amount);
    
    if ($stmt->execute()) {
        $booking_id = $conn->insert_id;
        
        // Update slot status
        $stmt = $conn->prepare("UPDATE parking_slots SET status = 'occupied' WHERE id = ?");
        $stmt->bind_param("i", $slot_id);
        $stmt->execute();
        
        // Update available slots count
        $stmt = $conn->prepare("UPDATE parking_locations SET available_slots = available_slots - 1 WHERE id = ?");
        $stmt->bind_param("i", $location_id);
        $stmt->execute();
        
        // Log the booking
        $stmt = $conn->prepare("INSERT INTO booking_logs (booking_id, action, performed_by, performed_by_type) VALUES (?, 'created', ?, 'user')");
        $stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
        $stmt->execute();
        
        // Get user details for email
        $stmt = $conn->prepare("SELECT u.full_name, u.email FROM users u WHERE u.id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        // Get slot number
        $stmt = $conn->prepare("SELECT slot_number FROM parking_slots WHERE id = ?");
        $stmt->bind_param("i", $slot_id);
        $stmt->execute();
        $slot = $stmt->get_result()->fetch_assoc();
        
        // Prepare email data
        $email_data = [
            'booking_id' => $booking_id,
            'customer_name' => $user['full_name'],
            'location_name' => $location['location_name'],
            'address' => $location['address'],
            'slot_number' => $slot['slot_number'],
            'booking_type' => $booking_type,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'total_amount' => $total_amount,
            'booking_status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Send confirmation email
        $email_html = generateBookingConfirmationEmail($email_data);
        sendEmail(
            $user['email'],
            $user['full_name'],
            'Parking Booking Confirmation - PKG-' . str_pad($booking_id, 8, '0', STR_PAD_LEFT),
            $email_html
        );
        
        $success = 'Booking submitted successfully! A confirmation email with your ticket has been sent. Waiting for admin approval.';
    } else {
        $error = 'Booking failed. Please try again.';
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Parking Slot - Parking System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        .navbar {
            background: #667eea;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .navbar h1 {
            font-size: 24px;
        }
        
        .navbar-right {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .navbar a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .location-header {
            border-bottom: 2px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .location-header h2 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 600;
        }
        
        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .pricing-info {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .price-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .total-amount {
            background: #667eea;
            color: white;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .total-amount.hidden {
            display: none;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: #48bb78;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #38a169;
        }
        
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #fed7d7;
            color: #c53030;
        }
        
        .alert-success {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .time-inputs {
            display: none;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
    </style>
    <script>
        function calculateAmount() {
            var bookingType = document.getElementById('booking_type').value;
            var startDate = new Date(document.getElementById('start_date').value);
            var endDate = new Date(document.getElementById('end_date').value);
            var totalAmount = 0;
            var display = document.getElementById('totalAmountDisplay');
            
            if (bookingType === 'hourly') {
                document.querySelector('.time-inputs').style.display = 'grid';
                var startTime = document.getElementById('start_time').value;
                var endTime = document.getElementById('end_time').value;
                
                if (startTime && endTime) {
                    var start = new Date('2000-01-01 ' + startTime);
                    var end = new Date('2000-01-01 ' + endTime);
                    var hours = (end - start) / (1000 * 60 * 60);
                    if (hours > 0) {
                        totalAmount = hours * <?php echo $location['price_per_hour']; ?>;
                    }
                }
            } else {
                document.querySelector('.time-inputs').style.display = 'none';
                
                if (bookingType === 'daily' && startDate && endDate && !isNaN(startDate) && !isNaN(endDate)) {
                    var days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;
                    if (days > 0) {
                        totalAmount = days * <?php echo $location['price_per_day']; ?>;
                    }
                } else if (bookingType === 'monthly') {
                    totalAmount = <?php echo $location['price_per_month']; ?>;
                }
            }
            
            display.innerHTML = 'Total Amount: $' + totalAmount.toFixed(2);
            display.style.background = totalAmount > 0 ? '#48bb78' : '#667eea';
        }
        
        // Calculate on page load if form is filled
        window.onload = function() {
            calculateAmount();
        };
    </script>
</head>
<body>
    <div class="navbar">
        <h1>🅿️ Parking System</h1>
        <div class="navbar-right">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
            <a href="dashboard.php">Dashboard</a>
            <a href="browse_locations.php">Browse Locations</a>
            <a href="my_bookings.php">My Bookings</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <div class="location-header">
                <h2><?php echo htmlspecialchars($location['location_name']); ?></h2>
                <p><?php echo htmlspecialchars($location['address']); ?>, <?php echo htmlspecialchars($location['city']); ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <a href="dashboard.php" class="btn">Go to Dashboard</a>
            <?php else: ?>
                <div class="pricing-info">
                    <h4 style="margin-bottom: 10px;">Pricing Information</h4>
                    <div class="price-item">
                        <span>Hourly Rate:</span>
                        <span>$<?php echo number_format($location['price_per_hour'], 2); ?></span>
                    </div>
                    <div class="price-item">
                        <span>Daily Rate:</span>
                        <span>$<?php echo number_format($location['price_per_day'], 2); ?></span>
                    </div>
                    <div class="price-item">
                        <span>Monthly Pass:</span>
                        <span>$<?php echo number_format($location['price_per_month'], 2); ?></span>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="slot_id">Select Parking Slot *</label>
                        <select id="slot_id" name="slot_id" required>
                            <option value="">Choose a slot</option>
                            <?php while ($slot = $slots->fetch_assoc()): ?>
                                <option value="<?php echo $slot['id']; ?>">
                                    Slot <?php echo htmlspecialchars($slot['slot_number']); ?> - <?php echo ucfirst($slot['slot_type']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="booking_type">Booking Type *</label>
                        <select id="booking_type" name="booking_type" required onchange="calculateAmount()">
                            <option value="">Select booking type</option>
                            <option value="hourly">Hourly</option>
                            <option value="daily">Daily</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="start_date">Start Date *</label>
                            <input type="date" id="start_date" name="start_date" required onchange="calculateAmount()" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date">End Date *</label>
                            <input type="date" id="end_date" name="end_date" required onchange="calculateAmount()" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="grid-2 time-inputs">
                        <div class="form-group">
                            <label for="start_time">Start Time</label>
                            <input type="time" id="start_time" name="start_time" onchange="calculateAmount()">
                        </div>
                        
                        <div class="form-group">
                            <label for="end_time">End Time</label>
                            <input type="time" id="end_time" name="end_time" onchange="calculateAmount()">
                        </div>
                    </div>
                    
                    <div class="total-amount" id="totalAmountDisplay">
                        Total Amount: $0.00
                    </div>
                    
                    <button type="submit" class="btn">Submit Booking</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
