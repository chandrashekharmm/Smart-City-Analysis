<?php
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($booking_id == 0) {
    header('Location: my_bookings.php');
    exit();
}

$conn = getDBConnection();

// Get booking details
$stmt = $conn->prepare("
    SELECT b.*, l.location_name, l.address, l.city, s.slot_number, s.slot_type, u.full_name, u.email, u.phone
    FROM bookings b
    JOIN parking_locations l ON b.location_id = l.id
    JOIN parking_slots s ON b.slot_id = s.id
    JOIN users u ON b.user_id = u.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    header('Location: my_bookings.php');
    exit();
}

$conn->close();

$ticket_number = "PKG-" . str_pad($booking_id, 8, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo $ticket_number; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .receipt-header {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        
        .receipt-header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .receipt-header p {
            font-size: 14px;
        }
        
        .ticket-number {
            text-align: center;
            font-size: 32px;
            font-weight: bold;
            margin: 20px 0;
            letter-spacing: 2px;
        }
        
        .qr-code {
            text-align: center;
            margin: 20px 0;
        }
        
        .qr-code img {
            border: 2px solid #000;
            padding: 10px;
        }
        
        .receipt-section {
            margin: 20px 0;
        }
        
        .receipt-section h3 {
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .receipt-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dotted #ccc;
        }
        
        .receipt-row:last-child {
            border-bottom: none;
        }
        
        .receipt-row .label {
            font-weight: bold;
        }
        
        .receipt-row .value {
            text-align: right;
        }
        
        .total-section {
            background: #f0f0f0;
            padding: 15px;
            margin: 20px 0;
            border: 2px solid #000;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 20px;
            font-weight: bold;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 12px;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .receipt-footer {
            text-align: center;
            border-top: 3px double #000;
            padding-top: 20px;
            margin-top: 30px;
            font-size: 12px;
        }
        
        .no-print {
            text-align: center;
            margin-top: 20px;
        }
        
        .btn {
            padding: 12px 30px;
            margin: 0 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-print {
            background: #667eea;
            color: white;
        }
        
        .btn-print:hover {
            background: #5568d3;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
        }
        
        .btn-back:hover {
            background: #5a6268;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .receipt-container {
                box-shadow: none;
                padding: 20px;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h1>🅿️ PARKING RECEIPT</h1>
            <p>Parking Pass Management System</p>
            <p>Thank you for choosing our service</p>
        </div>
        
        <div class="ticket-number">
            <?php echo $ticket_number; ?>
        </div>
        
        <div class="qr-code">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($ticket_number); ?>" alt="QR Code">
            <p style="font-size: 12px; margin-top: 5px;">Scan at parking entrance</p>
        </div>
        
        <div class="receipt-section">
            <h3>CUSTOMER INFORMATION</h3>
            <div class="receipt-row">
                <span class="label">Name:</span>
                <span class="value"><?php echo htmlspecialchars($booking['full_name']); ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Email:</span>
                <span class="value"><?php echo htmlspecialchars($booking['email']); ?></span>
            </div>
            <?php if ($booking['phone']): ?>
            <div class="receipt-row">
                <span class="label">Phone:</span>
                <span class="value"><?php echo htmlspecialchars($booking['phone']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="receipt-section">
            <h3>PARKING DETAILS</h3>
            <div class="receipt-row">
                <span class="label">Location:</span>
                <span class="value"><?php echo htmlspecialchars($booking['location_name']); ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Address:</span>
                <span class="value"><?php echo htmlspecialchars($booking['address']) . ', ' . htmlspecialchars($booking['city']); ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Slot Number:</span>
                <span class="value"><?php echo htmlspecialchars($booking['slot_number']); ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Slot Type:</span>
                <span class="value"><?php echo ucfirst($booking['slot_type']); ?></span>
            </div>
        </div>
        
        <div class="receipt-section">
            <h3>BOOKING INFORMATION</h3>
            <div class="receipt-row">
                <span class="label">Booking Type:</span>
                <span class="value"><?php echo ucfirst($booking['booking_type']); ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Start Date:</span>
                <span class="value"><?php echo date('F d, Y', strtotime($booking['start_date'])); ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">End Date:</span>
                <span class="value"><?php echo date('F d, Y', strtotime($booking['end_date'])); ?></span>
            </div>
            <?php if ($booking['start_time'] && $booking['end_time']): ?>
            <div class="receipt-row">
                <span class="label">Time:</span>
                <span class="value">
                    <?php echo date('h:i A', strtotime($booking['start_time'])); ?> - 
                    <?php echo date('h:i A', strtotime($booking['end_time'])); ?>
                </span>
            </div>
            <?php endif; ?>
            <div class="receipt-row">
                <span class="label">Booking Date:</span>
                <span class="value"><?php echo date('F d, Y h:i A', strtotime($booking['created_at'])); ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Status:</span>
                <span class="value">
                    <span class="status-badge status-<?php echo $booking['booking_status']; ?>">
                        <?php echo strtoupper($booking['booking_status']); ?>
                    </span>
                </span>
            </div>
        </div>
        
        <div class="total-section">
            <div class="total-row">
                <span>TOTAL AMOUNT:</span>
                <span>$<?php echo number_format($booking['total_amount'], 2); ?></span>
            </div>
        </div>
        
        <div class="receipt-section">
            <h3>PAYMENT INFORMATION</h3>
            <div class="receipt-row">
                <span class="label">Payment Status:</span>
                <span class="value"><?php echo ucfirst($booking['payment_status']); ?></span>
            </div>
        </div>
        
        <?php if ($booking['admin_notes']): ?>
        <div class="receipt-section">
            <h3>ADMIN NOTES</h3>
            <p><?php echo nl2br(htmlspecialchars($booking['admin_notes'])); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="receipt-footer">
            <p><strong>IMPORTANT INFORMATION</strong></p>
            <p>• Please present this receipt or QR code at the parking entrance</p>
            <p>• Keep this receipt for your records</p>
            <p>• Contact support for any issues or changes</p>
            <p style="margin-top: 10px;">Printed on: <?php echo date('F d, Y h:i A'); ?></p>
            <p>© <?php echo date('Y'); ?> Parking System. All rights reserved.</p>
        </div>
    </div>
    
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-print">🖨️ Print Receipt</button>
        <a href="my_bookings.php" class="btn btn-back">← Back to Bookings</a>
    </div>
</body>
</html>
