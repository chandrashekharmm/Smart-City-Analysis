<?php
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();

// Get all active parking locations
$result = $conn->query("SELECT * FROM parking_locations WHERE status = 'active' ORDER BY location_name");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Locations - Parking System</title>
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
        
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .locations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .location-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .location-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .location-card h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 20px;
        }
        
        .location-info {
            color: #666;
            margin-bottom: 15px;
        }
        
        .location-info p {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        
        .location-info p::before {
            content: '📍';
            margin-right: 8px;
        }
        
        .pricing {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .pricing h4 {
            color: #555;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .price-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .price-label {
            color: #666;
        }
        
        .price-value {
            color: #48bb78;
            font-weight: 600;
        }
        
        .availability {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            background: #e6f7ff;
            border-radius: 5px;
        }
        
        .availability span {
            color: #0066cc;
            font-weight: 600;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #5568d3;
        }
        
        .no-locations {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 10px;
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
        <h2>Available Parking Locations</h2>
        
        <?php if ($result->num_rows > 0): ?>
            <div class="locations-grid">
                <?php while ($location = $result->fetch_assoc()): ?>
                    <div class="location-card">
                        <h3><?php echo htmlspecialchars($location['location_name']); ?></h3>
                        
                        <div class="location-info">
                            <p><?php echo htmlspecialchars($location['address']); ?></p>
                            <p style="padding-left: 28px;"><?php echo htmlspecialchars($location['city']); ?></p>
                        </div>
                        
                        <div class="availability">
                            <span>Available Slots:</span>
                            <span><?php echo $location['available_slots']; ?> / <?php echo $location['total_slots']; ?></span>
                        </div>
                        
                        <div class="pricing">
                            <h4>Pricing</h4>
                            <div class="price-item">
                                <span class="price-label">Hourly Rate:</span>
                                <span class="price-value">$<?php echo number_format($location['price_per_hour'], 2); ?></span>
                            </div>
                            <div class="price-item">
                                <span class="price-label">Daily Rate:</span>
                                <span class="price-value">$<?php echo number_format($location['price_per_day'], 2); ?></span>
                            </div>
                            <div class="price-item">
                                <span class="price-label">Monthly Pass:</span>
                                <span class="price-value">$<?php echo number_format($location['price_per_month'], 2); ?></span>
                            </div>
                        </div>
                        
                        <a href="book_slot.php?location_id=<?php echo $location['id']; ?>" class="btn">Book Now</a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-locations">
                <h3>No parking locations available at the moment</h3>
                <p>Please check back later</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
