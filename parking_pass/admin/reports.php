<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();

// Revenue statistics
$total_revenue = $conn->query("SELECT SUM(total_amount) as total FROM bookings WHERE payment_status = 'paid'")->fetch_assoc()['total'] ?? 0;
$pending_revenue = $conn->query("SELECT SUM(total_amount) as total FROM bookings WHERE payment_status = 'pending'")->fetch_assoc()['total'] ?? 0;

// Booking statistics by type
$hourly_bookings = $conn->query("SELECT COUNT(*) as count, SUM(total_amount) as revenue FROM bookings WHERE booking_type = 'hourly'")->fetch_assoc();
$daily_bookings = $conn->query("SELECT COUNT(*) as count, SUM(total_amount) as revenue FROM bookings WHERE booking_type = 'daily'")->fetch_assoc();
$monthly_bookings = $conn->query("SELECT COUNT(*) as count, SUM(total_amount) as revenue FROM bookings WHERE booking_type = 'monthly'")->fetch_assoc();

// Location-wise bookings
$location_stats = $conn->query("
    SELECT l.location_name, l.city,
           COUNT(b.id) as total_bookings,
           SUM(b.total_amount) as revenue,
           l.available_slots,
           l.total_slots
    FROM parking_locations l
    LEFT JOIN bookings b ON l.id = b.location_id
    GROUP BY l.id
    ORDER BY total_bookings DESC
");

// Recent revenue by date
$revenue_by_date = $conn->query("
    SELECT DATE(created_at) as date, 
           COUNT(*) as bookings,
           SUM(total_amount) as revenue
    FROM bookings
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
    LIMIT 10
");

// Top customers
$top_customers = $conn->query("
    SELECT u.full_name, u.email,
           COUNT(b.id) as total_bookings,
           SUM(b.total_amount) as total_spent
    FROM users u
    JOIN bookings b ON u.id = b.user_id
    GROUP BY u.id
    ORDER BY total_spent DESC
    LIMIT 10
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin</title>
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
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #10b981;
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
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }
        
        .booking-type-card {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .booking-type-card h4 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .booking-type-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        
        .booking-type-stats div {
            text-align: center;
        }
        
        .booking-type-stats .label {
            font-size: 12px;
            color: #666;
        }
        
        .booking-type-stats .value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
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
        <h1 style="margin-bottom: 20px; color: #333;">Reports & Analytics</h1>
        
        <div class="stats">
            <div class="stat-card">
                <h3>Total Revenue (Paid)</h3>
                <div class="number">$<?php echo number_format($total_revenue, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending Revenue</h3>
                <div class="number" style="color: #f59e0b;">$<?php echo number_format($pending_revenue, 2); ?></div>
            </div>
        </div>
        
        <div class="card">
            <h2>Booking Types Analysis</h2>
            <div class="booking-type-card">
                <h4>Hourly Bookings</h4>
                <div class="booking-type-stats">
                    <div>
                        <div class="label">Total Bookings</div>
                        <div class="value"><?php echo $hourly_bookings['count'] ?? 0; ?></div>
                    </div>
                    <div>
                        <div class="label">Revenue</div>
                        <div class="value">$<?php echo number_format($hourly_bookings['revenue'] ?? 0, 2); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="booking-type-card">
                <h4>Daily Bookings</h4>
                <div class="booking-type-stats">
                    <div>
                        <div class="label">Total Bookings</div>
                        <div class="value"><?php echo $daily_bookings['count'] ?? 0; ?></div>
                    </div>
                    <div>
                        <div class="label">Revenue</div>
                        <div class="value">$<?php echo number_format($daily_bookings['revenue'] ?? 0, 2); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="booking-type-card">
                <h4>Monthly Bookings</h4>
                <div class="booking-type-stats">
                    <div>
                        <div class="label">Total Bookings</div>
                        <div class="value"><?php echo $monthly_bookings['count'] ?? 0; ?></div>
                    </div>
                    <div>
                        <div class="label">Revenue</div>
                        <div class="value">$<?php echo number_format($monthly_bookings['revenue'] ?? 0, 2); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid-2">
            <div class="card">
                <h2>Location Performance</h2>
                <?php if ($location_stats->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Location</th>
                                <th>Bookings</th>
                                <th>Revenue</th>
                                <th>Occupancy</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($loc = $location_stats->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($loc['location_name']); ?></td>
                                    <td><?php echo $loc['total_bookings'] ?? 0; ?></td>
                                    <td>$<?php echo number_format($loc['revenue'] ?? 0, 2); ?></td>
                                    <td><?php echo $loc['total_slots'] - $loc['available_slots']; ?> / <?php echo $loc['total_slots']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 30px;">No data available</p>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>Top Customers</h2>
                <?php if ($top_customers->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Bookings</th>
                                <th>Total Spent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($customer = $top_customers->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                                    <td><?php echo $customer['total_bookings']; ?></td>
                                    <td>$<?php echo number_format($customer['total_spent'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 30px;">No data available</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <h2>Revenue by Date (Last 30 Days)</h2>
            <?php if ($revenue_by_date->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Bookings</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($rev = $revenue_by_date->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($rev['date'])); ?></td>
                                <td><?php echo $rev['bookings']; ?></td>
                                <td>$<?php echo number_format($rev['revenue'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 30px;">No revenue data available</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
