<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : 0;

if ($location_id == 0) {
    header('Location: manage_locations.php');
    exit();
}

$conn = getDBConnection();

// Get location details
$stmt = $conn->prepare("SELECT * FROM parking_locations WHERE id = ?");
$stmt->bind_param("i", $location_id);
$stmt->execute();
$location = $stmt->get_result()->fetch_assoc();

if (!$location) {
    header('Location: manage_locations.php');
    exit();
}

$success = '';

// Handle adding new slot
if (isset($_POST['add_slot'])) {
    $slot_number = trim($_POST['slot_number']);
    $slot_type = $_POST['slot_type'];
    
    $stmt = $conn->prepare("INSERT INTO parking_slots (location_id, slot_number, slot_type) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $location_id, $slot_number, $slot_type);
    
    if ($stmt->execute()) {
        // Update total and available slots
        $conn->query("UPDATE parking_locations SET total_slots = total_slots + 1, available_slots = available_slots + 1 WHERE id = $location_id");
        $success = 'Slot added successfully';
    }
}

// Handle slot status update
if (isset($_GET['update_slot'])) {
    $slot_id = intval($_GET['update_slot']);
    $new_status = $_GET['status'];
    
    $stmt = $conn->prepare("UPDATE parking_slots SET status = ? WHERE id = ? AND location_id = ?");
    $stmt->bind_param("sii", $new_status, $slot_id, $location_id);
    if ($stmt->execute()) {
        $success = 'Slot status updated';
    }
}

// Get all slots for this location
$slots = $conn->query("SELECT * FROM parking_slots WHERE location_id = $location_id ORDER BY slot_number");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Slots - Admin</title>
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
            background: #1f2937;
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
            max-width: 1200px;
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
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .location-header h2 {
            color: #3b82f6;
        }
        
        .slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .slot-card {
            border: 2px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .slot-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .slot-card.available {
            border-color: #10b981;
            background: #f0fdf4;
        }
        
        .slot-card.occupied {
            border-color: #ef4444;
            background: #fef2f2;
        }
        
        .slot-card.maintenance {
            border-color: #f59e0b;
            background: #fffbeb;
        }
        
        .slot-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .slot-type {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .slot-status {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .status-available {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-occupied {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-maintenance {
            background: #fef3c7;
            color: #92400e;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 600;
        }
        
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-back {
            display: inline-block;
            padding: 10px 20px;
            background: #6b7280;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .btn-back:hover {
            background: #4b5563;
        }
        
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            background: #d1fae5;
            color: #065f46;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🔐 Admin Panel</h1>
        <div class="navbar-right">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</span>
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_locations.php">Locations</a>
            <a href="manage_bookings.php">Bookings</a>
            <a href="manage_users.php">Users</a>
            <a href="reports.php">Reports</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <a href="manage_locations.php" class="btn-back">← Back to Locations</a>
        
        <?php if ($success): ?>
            <div class="alert"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="location-header">
                <h2><?php echo htmlspecialchars($location['location_name']); ?></h2>
                <p><?php echo htmlspecialchars($location['address']); ?>, <?php echo htmlspecialchars($location['city']); ?></p>
                <p style="margin-top: 10px;">
                    <strong>Available Slots:</strong> <?php echo $location['available_slots']; ?> / <?php echo $location['total_slots']; ?>
                </p>
            </div>
            
            <h3 style="margin-bottom: 15px;">Add New Slot</h3>
            <form method="POST" action="" style="display: grid; grid-template-columns: 1fr 1fr 150px; gap: 15px; align-items: end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="slot_number">Slot Number</label>
                    <input type="text" id="slot_number" name="slot_number" required placeholder="e.g., A001">
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="slot_type">Slot Type</label>
                    <select id="slot_type" name="slot_type" required>
                        <option value="regular">Regular</option>
                        <option value="premium">Premium</option>
                        <option value="disabled">Disabled</option>
                    </select>
                </div>
                
                <button type="submit" name="add_slot" class="btn btn-success">Add Slot</button>
            </form>
        </div>
        
        <div class="card">
            <h3 style="margin-bottom: 20px;">Parking Slots</h3>
            
            <?php if ($slots->num_rows > 0): ?>
                <div class="slots-grid">
                    <?php while ($slot = $slots->fetch_assoc()): ?>
                        <div class="slot-card <?php echo $slot['status']; ?>">
                            <div class="slot-number"><?php echo htmlspecialchars($slot['slot_number']); ?></div>
                            <div class="slot-type"><?php echo ucfirst($slot['slot_type']); ?></div>
                            <div class="slot-status status-<?php echo $slot['status']; ?>">
                                <?php echo ucfirst($slot['status']); ?>
                            </div>
                            <?php if ($slot['status'] == 'available'): ?>
                                <a href="?location_id=<?php echo $location_id; ?>&update_slot=<?php echo $slot['id']; ?>&status=maintenance" class="btn btn-primary">
                                    Set Maintenance
                                </a>
                            <?php elseif ($slot['status'] == 'maintenance'): ?>
                                <a href="?location_id=<?php echo $location_id; ?>&update_slot=<?php echo $slot['id']; ?>&status=available" class="btn btn-success">
                                    Set Available
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 30px;">No slots added yet</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
