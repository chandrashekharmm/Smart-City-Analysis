<?php
// Email configuration for sending booking confirmations

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');  // For Gmail
define('SMTP_PORT', 587);                // TLS port
define('SMTP_USERNAME', 'your-email@gmail.com');  // Your email
define('SMTP_PASSWORD', 'your-app-password');      // Your app password
define('SMTP_FROM_EMAIL', 'noreply@parkingsystem.com');
define('SMTP_FROM_NAME', 'Parking System');

// Email settings
define('ENABLE_EMAIL', false);  // Set to true to enable emails (configure SMTP settings first)

/**
 * Send email function using PHPMailer
 */
function sendEmail($to_email, $to_name, $subject, $html_body, $plain_body = '') {
    if (!ENABLE_EMAIL) {
        return true; // Skip sending if disabled
    }
    
    require_once __DIR__ . '/phpmailer/PHPMailer-6.9.1/src/PHPMailer.php';
    require_once __DIR__ . '/phpmailer/PHPMailer-6.9.1/src/SMTP.php';
    require_once __DIR__ . '/phpmailer/PHPMailer-6.9.1/src/Exception.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $to_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html_body;
        $mail->AltBody = $plain_body ?: strip_tags($html_body);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Generate booking confirmation email HTML
 */
function generateBookingConfirmationEmail($booking_data) {
    $ticket_number = "PKG-" . str_pad($booking_data['booking_id'], 8, '0', STR_PAD_LEFT);
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .ticket { background: #f9f9f9; padding: 30px; border: 2px dashed #667eea; margin: 20px 0; border-radius: 10px; }
            .ticket-number { font-size: 32px; font-weight: bold; color: #667eea; text-align: center; margin-bottom: 20px; }
            .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #ddd; }
            .info-label { font-weight: 600; color: #666; }
            .info-value { color: #333; }
            .qr-code { text-align: center; margin: 20px 0; }
            .status { display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 14px; }
            .status-pending { background: #fef3c7; color: #92400e; }
            .status-approved { background: #d1fae5; color: #065f46; }
            .footer { background: #f9f9f9; padding: 20px; text-align: center; color: #666; font-size: 12px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🅿️ Parking Booking Confirmation</h1>
                <p>Your parking slot has been booked successfully!</p>
            </div>
            
            <div class="ticket">
                <div class="ticket-number">' . $ticket_number . '</div>
                
                <div class="info-row">
                    <span class="info-label">Customer Name:</span>
                    <span class="info-value">' . htmlspecialchars($booking_data['customer_name']) . '</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Location:</span>
                    <span class="info-value">' . htmlspecialchars($booking_data['location_name']) . '</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Address:</span>
                    <span class="info-value">' . htmlspecialchars($booking_data['address']) . '</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Slot Number:</span>
                    <span class="info-value">' . htmlspecialchars($booking_data['slot_number']) . '</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Booking Type:</span>
                    <span class="info-value">' . ucfirst($booking_data['booking_type']) . '</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Start Date:</span>
                    <span class="info-value">' . date('F d, Y', strtotime($booking_data['start_date'])) . '</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">End Date:</span>
                    <span class="info-value">' . date('F d, Y', strtotime($booking_data['end_date'])) . '</span>
                </div>
                
                ' . (isset($booking_data['start_time']) && $booking_data['start_time'] ? '
                <div class="info-row">
                    <span class="info-label">Time:</span>
                    <span class="info-value">' . date('h:i A', strtotime($booking_data['start_time'])) . ' - ' . date('h:i A', strtotime($booking_data['end_time'])) . '</span>
                </div>
                ' : '') . '
                
                <div class="info-row">
                    <span class="info-label">Total Amount:</span>
                    <span class="info-value" style="color: #10b981; font-weight: bold; font-size: 18px;">$' . number_format($booking_data['total_amount'], 2) . '</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Booking Status:</span>
                    <span class="info-value"><span class="status status-' . $booking_data['booking_status'] . '">' . ucfirst($booking_data['booking_status']) . '</span></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Booking Date:</span>
                    <span class="info-value">' . date('F d, Y h:i A', strtotime($booking_data['created_at'])) . '</span>
                </div>
                
                <div class="qr-code">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($ticket_number) . '" alt="QR Code">
                    <p style="color: #666; font-size: 12px; margin-top: 10px;">Scan this QR code at the parking entrance</p>
                </div>
            </div>
            
            <div style="text-align: center;">
                <a href="' . BASE_URL . 'user/my_bookings.php" class="button">View My Bookings</a>
            </div>
            
            <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;">
                <strong>⚠️ Important:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Your booking is currently <strong>' . strtoupper($booking_data['booking_status']) . '</strong></li>
                    ' . ($booking_data['booking_status'] == 'pending' ? '<li>Please wait for admin approval before using the parking slot</li>' : '') . '
                    <li>Please present this ticket or QR code at the parking entrance</li>
                    <li>Keep this email for your records</li>
                </ul>
            </div>
            
            <div class="footer">
                <p>Thank you for choosing our parking system!</p>
                <p>If you have any questions, please contact our support team.</p>
                <p style="margin-top: 10px;">© ' . date('Y') . ' Parking System. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return $html;
}

/**
 * Generate booking approval email
 */
function generateBookingApprovalEmail($booking_data) {
    $ticket_number = "PKG-" . str_pad($booking_data['booking_id'], 8, '0', STR_PAD_LEFT);
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #10b981; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: white; padding: 30px; }
            .ticket-number { font-size: 24px; font-weight: bold; color: #10b981; text-align: center; margin: 20px 0; }
            .footer { background: #f9f9f9; padding: 20px; text-align: center; color: #666; font-size: 12px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; padding: 12px 30px; background: #10b981; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>✅ Booking Approved!</h1>
                <p>Great news! Your parking booking has been approved.</p>
            </div>
            
            <div class="content">
                <p>Dear ' . htmlspecialchars($booking_data['customer_name']) . ',</p>
                
                <p>We are pleased to inform you that your parking booking has been approved by our admin team.</p>
                
                <div class="ticket-number">Ticket: ' . $ticket_number . '</div>
                
                <p><strong>Booking Details:</strong></p>
                <ul>
                    <li><strong>Location:</strong> ' . htmlspecialchars($booking_data['location_name']) . '</li>
                    <li><strong>Slot:</strong> ' . htmlspecialchars($booking_data['slot_number']) . '</li>
                    <li><strong>Date:</strong> ' . date('F d, Y', strtotime($booking_data['start_date'])) . ' to ' . date('F d, Y', strtotime($booking_data['end_date'])) . '</li>
                    <li><strong>Amount:</strong> $' . number_format($booking_data['total_amount'], 2) . '</li>
                </ul>
                
                <p>You can now use your parking slot during the booked period. Please present your ticket or QR code at the entrance.</p>
                
                <div style="text-align: center;">
                    <a href="' . BASE_URL . 'user/my_bookings.php" class="button">View Booking Details</a>
                </div>
            </div>
            
            <div class="footer">
                <p>Thank you for choosing our parking system!</p>
                <p>© ' . date('Y') . ' Parking System. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return $html;
}

/**
 * Generate booking rejection email
 */
function generateBookingRejectionEmail($booking_data) {
    $ticket_number = "PKG-" . str_pad($booking_data['booking_id'], 8, '0', STR_PAD_LEFT);
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #ef4444; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: white; padding: 30px; }
            .reason-box { background: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0; }
            .footer { background: #f9f9f9; padding: 20px; text-align: center; color: #666; font-size: 12px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>❌ Booking Not Approved</h1>
                <p>Your parking booking request could not be approved.</p>
            </div>
            
            <div class="content">
                <p>Dear ' . htmlspecialchars($booking_data['customer_name']) . ',</p>
                
                <p>We regret to inform you that your parking booking (Ticket: ' . $ticket_number . ') could not be approved.</p>
                
                <div class="reason-box">
                    <strong>Reason:</strong><br>
                    ' . nl2br(htmlspecialchars($booking_data['admin_notes'])) . '
                </div>
                
                <p><strong>Original Booking Details:</strong></p>
                <ul>
                    <li><strong>Location:</strong> ' . htmlspecialchars($booking_data['location_name']) . '</li>
                    <li><strong>Slot:</strong> ' . htmlspecialchars($booking_data['slot_number']) . '</li>
                    <li><strong>Date:</strong> ' . date('F d, Y', strtotime($booking_data['start_date'])) . ' to ' . date('F d, Y', strtotime($booking_data['end_date'])) . '</li>
                </ul>
                
                <p>Your slot has been released and is now available for other bookings. You can make a new booking at any time.</p>
                
                <div style="text-align: center;">
                    <a href="' . BASE_URL . 'user/browse_locations.php" class="button">Make New Booking</a>
                </div>
            </div>
            
            <div class="footer">
                <p>If you have any questions, please contact our support team.</p>
                <p>© ' . date('Y') . ' Parking System. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return $html;
}
?>
