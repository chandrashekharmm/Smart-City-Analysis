<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();

$success = '';
$error = '';

// Handle location addition
if (isset($_POST['add_location'])) {
    $location_name = trim($_POST['location_name']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $total_slots = intval($_POST['total_slots']);
    $price_per_hour = floatval($_POST['price_per_hour']);
    $price_per_day = floatval($_POST['price_per_day']);
    $price_per_month = floatval($_POST['price_per_month']);
    
    $stmt = $conn->prepare("INSERT INTO parking_locations (location_name, address, city, total_slots, available_slots, price_per_hour, price_per_day, price_per_month) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssidddd", $location_name, $address, $city, $total_slots, $total_slots, $price_per_hour, $price_per_day, $price_per_month);
    
    if ($stmt->execute()) {
        $location_id = $conn->insert_id;
        
        // Create slots for this location
        for ($i = 1; $i <= $total_slots; $i++) {
            $slot_number = "A" . str_pad($i, 3, '0', STR_PAD_LEFT);
            $stmt = $conn->prepare("INSERT INTO parking_slots (location_id, slot_number) VALUES (?, ?)");
            $stmt->bind_param("is", $location_id, $slot_number);
            $stmt->execute();
        }
        
        $success = 'Location added successfully with ' . $total_slots . ' parking slots!';
    } else {
        $error = 'Failed to add location';
    }
}

// Handle location deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM parking_locations WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = 'Location deleted successfully';
    }
}

// Handle location status toggle
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $stmt = $conn->prepare("UPDATE parking_locations SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = 'Location status updated';
    }
}

// Get all locations
$locations = $conn->query("SELECT * FROM parking_locations ORDER BY created_at DESC");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Locations - Admin</title>
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
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .card h2 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }
        
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        .btn {
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #2563eb;
        }
        
        .btn-success {
            background: #10b981;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-danger {
            background: #ef4444;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f9f9f9;
            font-weight: 600;
            color: #555;
        }
        
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
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
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Add New Parking Location</h2>
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="location_name">Location Name *</label>
                        <input type="text" id="location_name" name="location_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="city">City *</label>
                        <input type="text" id="city" name="city" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="total_slots">Total Slots *</label>
                        <input type="number" id="total_slots" name="total_slots" required min="1">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Full Address *</label>
                    <input type="text" id="address" name="address" required>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="price_per_hour">Price per Hour ($) *</label>
                        <input type="number" id="price_per_hour" name="price_per_hour" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="price_per_day">Price per Day ($) *</label>
                        <input type="number" id="price_per_day" name="price_per_day" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="price_per_month">Price per Month ($) *</label>
                        <input type="number" id="price_per_month" name="price_per_month" step="0.01" required>
                    </div>
                </div>
                
                <button type="submit" name="add_location" class="btn btn-success">Add Location</button>
            </form>
        </div>
        
        <div class="card">
            <h2>Manage Parking Locations</h2>
            
            <?php if ($locations->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Location Name</th>
                            <th>City</th>
                            <th>Slots</th>
                            <th>Hourly</th>
                            <th>Daily</th>
                            <th>Monthly</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($location = $locations->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $location['id']; ?></td>
                                <td><?php echo htmlspecialchars($location['location_name']); ?></td>
                                <td><?php echo htmlspecialchars($location['city']); ?></td>
                                <td><?php echo $location['available_slots']; ?> / <?php echo $location['total_slots']; ?></td>
                                <td>$<?php echo number_format($location['price_per_hour'], 2); ?></td>
                                <td>$<?php echo number_format($location['price_per_day'], 2); ?></td>
                                <td>$<?php echo number_format($location['price_per_month'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $location['status']; ?>">
                                        <?php echo ucfirst($location['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?toggle=<?php echo $location['id']; ?>" class="btn btn-small">
                                            <?php echo $location['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>
                                        </a>
                                        <a href="view_slots.php?location_id=<?php echo $location['id']; ?>" class="btn btn-small btn-success">
                                            View Slots
                                        </a>
                                        <a href="?delete=<?php echo $location['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('Are you sure you want to delete this location?')">
                                            Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 30px;">No locations added yet</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
