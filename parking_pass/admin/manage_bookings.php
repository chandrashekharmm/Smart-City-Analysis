<?php
require_once '../config.php';
require_once '../email_config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();

$success = '';
$error = '';

// Handle booking approval
if (isset($_POST['approve'])) {
    $booking_id = intval($_POST['booking_id']);
    
    // Get booking details for email
    $stmt = $conn->prepare("
        SELECT b.*, u.full_name, u.email, l.location_name, l.address, s.slot_number 
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN parking_locations l ON b.location_id = l.id
        JOIN parking_slots s ON b.slot_id = s.id
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    
    $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $booking_id);
    
    if ($stmt->execute()) {
        // Log the action
        $stmt = $conn->prepare("INSERT INTO booking_logs (booking_id, action, performed_by, performed_by_type, notes) VALUES (?, 'approved', ?, 'admin', 'Booking approved by admin')");
        $stmt->bind_param("ii", $booking_id, $_SESSION['admin_id']);
        $stmt->execute();
        
        // Send approval email
        $email_data = [
            'booking_id' => $booking_id,
            'customer_name' => $booking['full_name'],
            'location_name' => $booking['location_name'],
            'address' => $booking['address'],
            'slot_number' => $booking['slot_number'],
            'booking_type' => $booking['booking_type'],
            'start_date' => $booking['start_date'],
            'end_date' => $booking['end_date'],
            'total_amount' => $booking['total_amount']
        ];
        
        $email_html = generateBookingApprovalEmail($email_data);
        sendEmail(
            $booking['email'],
            $booking['full_name'],
            'Booking Approved - PKG-' . str_pad($booking_id, 8, '0', STR_PAD_LEFT),
            $email_html
        );
        
        $success = 'Booking approved successfully and confirmation email sent';
    }
}

// Handle booking rejection
if (isset($_POST['reject'])) {
    $booking_id = intval($_POST['booking_id']);
    $admin_notes = trim($_POST['admin_notes']);
    
    // Get booking details for email
    $stmt = $conn->prepare("
        SELECT b.*, u.full_name, u.email, l.location_name, l.address, s.slot_number 
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN parking_locations l ON b.location_id = l.id
        JOIN parking_slots s ON b.slot_id = s.id
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    
    $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'rejected', admin_notes = ? WHERE id = ?");
    $stmt->bind_param("si", $admin_notes, $booking_id);
    
    if ($stmt->execute()) {
        // Get booking details to free up the slot
        $stmt = $conn->prepare("SELECT slot_id, location_id FROM bookings WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $booking_info = $stmt->get_result()->fetch_assoc();
        
        // Free up the slot
        $stmt = $conn->prepare("UPDATE parking_slots SET status = 'available' WHERE id = ?");
        $stmt->bind_param("i", $booking_info['slot_id']);
        $stmt->execute();
        
        // Update available slots count
        $stmt = $conn->prepare("UPDATE parking_locations SET available_slots = available_slots + 1 WHERE id = ?");
        $stmt->bind_param("i", $booking_info['location_id']);
        $stmt->execute();
        
        // Log the action
        $stmt = $conn->prepare("INSERT INTO booking_logs (booking_id, action, performed_by, performed_by_type, notes) VALUES (?, 'rejected', ?, 'admin', ?)");
        $stmt->bind_param("iis", $booking_id, $_SESSION['admin_id'], $admin_notes);
        $stmt->execute();
        
        // Send rejection email
        $email_data = [
            'booking_id' => $booking_id,
            'customer_name' => $booking['full_name'],
            'location_name' => $booking['location_name'],
            'address' => $booking['address'],
            'slot_number' => $booking['slot_number'],
            'booking_type' => $booking['booking_type'],
            'start_date' => $booking['start_date'],
            'end_date' => $booking['end_date'],
            'total_amount' => $booking['total_amount'],
            'admin_notes' => $admin_notes
        ];
        
        $email_html = generateBookingRejectionEmail($email_data);
        sendEmail(
            $booking['email'],
            $booking['full_name'],
            'Booking Status Update - PKG-' . str_pad($booking_id, 8, '0', STR_PAD_LEFT),
            $email_html
        );
        
        $success = 'Booking rejected successfully and notification email sent';
    }
}

// Handle marking as active
if (isset($_POST['mark_active'])) {
    $booking_id = intval($_POST['booking_id']);
    
    $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'active' WHERE id = ?");
    $stmt->bind_param("i", $booking_id);
    
    if ($stmt->execute()) {
        $stmt = $conn->prepare("INSERT INTO booking_logs (booking_id, action, performed_by, performed_by_type) VALUES (?, 'activated', ?, 'admin')");
        $stmt->bind_param("ii", $booking_id, $_SESSION['admin_id']);
        $stmt->execute();
        
        $success = 'Booking marked as active';
    }
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build query based on filter
$query = "
    SELECT b.*, u.full_name, u.email, u.phone, l.location_name, s.slot_number 
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN parking_locations l ON b.location_id = l.id
    JOIN parking_slots s ON b.slot_id = s.id
";

if ($filter != 'all') {
    $query .= " WHERE b.booking_status = '" . $conn->real_escape_string($filter) . "'";
}

$query .= " ORDER BY b.created_at DESC";

$bookings = $conn->query($query);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Admin</title>
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
            max-width: 1600px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            background: #e5e7eb;
            color: #374151;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: #3b82f6;
            color: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f9f9f9;
            font-weight: 600;
            color: #555;
        }
        
        .status {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
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
        
        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            font-size: 11px;
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
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            margin: 100px auto;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
        }
        
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
            font-family: inherit;
        }
    </style>
    <script>
        function showRejectModal(bookingId) {
            document.getElementById('reject_booking_id').value = bookingId;
            document.getElementById('rejectModal').style.display = 'block';
        }
        
        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
        }
    </script>
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
        
        <div class="card">
            <h2>Manage Bookings</h2>
            
            <div class="filters">
                <a href="?filter=all" class="filter-btn <?php echo $filter == 'all' ? 'active' : ''; ?>">All</a>
                <a href="?filter=pending" class="filter-btn <?php echo $filter == 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="?filter=approved" class="filter-btn <?php echo $filter == 'approved' ? 'active' : ''; ?>">Approved</a>
                <a href="?filter=active" class="filter-btn <?php echo $filter == 'active' ? 'active' : ''; ?>">Active</a>
                <a href="?filter=rejected" class="filter-btn <?php echo $filter == 'rejected' ? 'active' : ''; ?>">Rejected</a>
                <a href="?filter=completed" class="filter-btn <?php echo $filter == 'completed' ? 'active' : ''; ?>">Completed</a>
            </div>
            
            <?php if ($bookings->num_rows > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Contact</th>
                                <th>Location</th>
                                <th>Slot</th>
                                <th>Type</th>
                                <th>Dates</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($booking = $bookings->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $booking['id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['email']); ?><br>
                                        <?php echo htmlspecialchars($booking['phone']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['location_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['slot_number']); ?></td>
                                    <td><?php echo ucfirst($booking['booking_type']); ?></td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($booking['start_date'])); ?><br>
                                        to <?php echo date('M d, Y', strtotime($booking['end_date'])); ?>
                                    </td>
                                    <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status status-<?php echo $booking['booking_status']; ?>">
                                            <?php echo ucfirst($booking['booking_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($booking['booking_status'] == 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <button type="submit" name="approve" class="btn btn-success">Approve</button>
                                                </form>
                                                <button onclick="showRejectModal(<?php echo $booking['id']; ?>)" class="btn btn-danger">Reject</button>
                                            <?php elseif ($booking['booking_status'] == 'approved'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <button type="submit" name="mark_active" class="btn btn-primary">Mark Active</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 30px;">No bookings found</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 20px;">Reject Booking</h2>
            <form method="POST">
                <input type="hidden" id="reject_booking_id" name="booking_id">
                <div style="margin-bottom: 15px;">
                    <label for="admin_notes" style="display: block; margin-bottom: 5px; font-weight: 600;">Reason for Rejection:</label>
                    <textarea id="admin_notes" name="admin_notes" rows="4" required placeholder="Enter reason for rejection..."></textarea>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="reject" class="btn btn-danger" style="flex: 1; padding: 10px;">Reject Booking</button>
                    <button type="button" onclick="closeRejectModal()" class="btn" style="flex: 1; padding: 10px; background: #6b7280; color: white;">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
