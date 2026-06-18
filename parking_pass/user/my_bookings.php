<?php
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();

// Get user bookings
$stmt = $conn->prepare("
    SELECT b.*, l.location_name, l.address, s.slot_number 
    FROM bookings b
    JOIN parking_locations l ON b.location_id = l.id
    JOIN parking_slots s ON b.slot_id = s.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$bookings = $stmt->get_result();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Parking System</title>
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
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .booking-card {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .booking-header h3 {
            color: #667eea;
            font-size: 18px;
        }
        
        .status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-approved {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-active {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-completed {
            background: #e5e7eb;
            color: #374151;
        }
        
        .status-cancelled {
            background: #fecaca;
            color: #7f1d1d;
        }
        
        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .detail-item {
            color: #555;
        }
        
        .detail-label {
            font-weight: 600;
            display: block;
            margin-bottom: 3px;
            font-size: 12px;
            color: #888;
        }
        
        .detail-value {
            font-size: 14px;
        }
        
        .admin-notes {
            margin-top: 15px;
            padding: 12px;
            background: white;
            border-radius: 5px;
            border-left: 3px solid #f59e0b;
        }
        
        .admin-notes-label {
            font-weight: 600;
            color: #f59e0b;
            margin-bottom: 5px;
        }
        
        .no-bookings {
            text-align: center;
            padding: 60px;
            color: #999;
        }
        
        .btn-new {
            display: inline-block;
            padding: 10px 20px;
            background: #48bb78;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .btn-new:hover {
            background: #38a169;
        }
    </style>
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
            <h2>My Bookings</h2>
            <a href="browse_locations.php" class="btn-new">+ New Booking</a>
            
            <?php if ($bookings->num_rows > 0): ?>
                <?php while ($booking = $bookings->fetch_assoc()): ?>
                    <div class="booking-card">
                        <div class="booking-header">
                            <h3><?php echo htmlspecialchars($booking['location_name']); ?></h3>
                            <span class="status status-<?php echo $booking['booking_status']; ?>">
                                <?php echo ucfirst($booking['booking_status']); ?>
                            </span>
                        </div>
                        
                        <div class="booking-details">
                            <div class="detail-item">
                                <span class="detail-label">Slot Number</span>
                                <span class="detail-value"><?php echo htmlspecialchars($booking['slot_number']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Booking Type</span>
                                <span class="detail-value"><?php echo ucfirst($booking['booking_type']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Start Date</span>
                                <span class="detail-value"><?php echo date('M d, Y', strtotime($booking['start_date'])); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">End Date</span>
                                <span class="detail-value"><?php echo date('M d, Y', strtotime($booking['end_date'])); ?></span>
                            </div>
                            
                            <?php if ($booking['start_time']): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Time</span>
                                    <span class="detail-value">
                                        <?php echo date('h:i A', strtotime($booking['start_time'])); ?> - 
                                        <?php echo date('h:i A', strtotime($booking['end_time'])); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="detail-item">
                                <span class="detail-label">Total Amount</span>
                                <span class="detail-value" style="color: #48bb78; font-weight: 600;">
                                    $<?php echo number_format($booking['total_amount'], 2); ?>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Payment Status</span>
                                <span class="detail-value"><?php echo ucfirst($booking['payment_status']); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Booking Date</span>
                                <span class="detail-value"><?php echo date('M d, Y h:i A', strtotime($booking['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <?php if ($booking['admin_notes']): ?>
                            <div class="admin-notes">
                                <div class="admin-notes-label">Admin Notes:</div>
                                <div><?php echo htmlspecialchars($booking['admin_notes']); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($booking['booking_status'] == 'approved'): ?>
                            <div style="text-align: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                                <a href="receipt.php?id=<?php echo $booking['id']; ?>" class="receipt-btn" style="display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-weight: 600; transition: all 0.3s;">
                                    🖨️ Print Receipt
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-bookings">
                    <h3>No bookings yet</h3>
                    <p>Start by browsing available parking locations!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
