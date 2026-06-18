<?php
/**
 * Admin Password Reset Utility
 * 
 * Run this file once to reset the admin password to 'admin123'
 * After running successfully, DELETE THIS FILE for security
 */

require_once '../config.php';

$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $conn = getDBConnection();
    
    // Update admin password
    $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE username = 'admin'");
    $stmt->bind_param("s", $hashed_password);
    
    if ($stmt->execute()) {
        echo "<!DOCTYPE html>";
        echo "<html><head><title>Password Reset</title>";
        echo "<style>
            body { 
                font-family: Arial, sans-serif; 
                display: flex; 
                justify-content: center; 
                align-items: center; 
                min-height: 100vh; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0;
            }
            .container { 
                background: white; 
                padding: 40px; 
                border-radius: 10px; 
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                text-align: center;
                max-width: 500px;
            }
            .success { 
                color: #22c55e; 
                font-size: 48px; 
                margin-bottom: 20px;
            }
            h1 { 
                color: #333; 
                margin-bottom: 15px;
            }
            .info { 
                background: #e0e7ff; 
                padding: 15px; 
                border-radius: 5px; 
                margin: 20px 0;
                color: #4338ca;
            }
            .warning { 
                background: #fed7d7; 
                padding: 15px; 
                border-radius: 5px; 
                margin: 20px 0;
                color: #c53030;
                font-weight: bold;
            }
            a { 
                display: inline-block; 
                margin-top: 20px; 
                padding: 12px 30px; 
                background: #667eea; 
                color: white; 
                text-decoration: none; 
                border-radius: 5px;
                transition: all 0.3s;
            }
            a:hover { 
                background: #5568d3; 
                transform: translateY(-2px);
            }
        </style>";
        echo "</head><body>";
        echo "<div class='container'>";
        echo "<div class='success'>✓</div>";
        echo "<h1>Password Reset Successful!</h1>";
        echo "<div class='info'>";
        echo "<strong>New Credentials:</strong><br>";
        echo "Username: admin<br>";
        echo "Password: admin123";
        echo "</div>";
        echo "<div class='warning'>";
        echo "⚠️ IMPORTANT: Delete this file (reset_password.php) immediately for security!";
        echo "</div>";
        echo "<a href='login.php'>Go to Login Page</a>";
        echo "</div>";
        echo "</body></html>";
    } else {
        throw new Exception("Failed to update password");
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Error</title>";
    echo "<style>
        body { 
            font-family: Arial, sans-serif; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
        }
        .container { 
            background: white; 
            padding: 40px; 
            border-radius: 10px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 500px;
        }
        .error { 
            color: #ef4444; 
            font-size: 48px; 
            margin-bottom: 20px;
        }
        h1 { 
            color: #333; 
            margin-bottom: 15px;
        }
        .error-box { 
            background: #fed7d7; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 20px 0;
            color: #c53030;
        }
    </style>";
    echo "</head><body>";
    echo "<div class='container'>";
    echo "<div class='error'>✗</div>";
    echo "<h1>Error Resetting Password</h1>";
    echo "<div class='error-box'>" . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</div>";
    echo "</body></html>";
}
?>
