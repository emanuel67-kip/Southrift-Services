<?php
require_once 'db.php';

// Configure session to match the driver system
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    ini_set('session.use_only_cookies', 1);
    
    session_name('southrift_admin');
    
    $lifetime = 2592000; // 30 days
    $path = '/';
    $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $httponly = true;
    
    session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
    session_start();
}

echo "<h2>WhatsApp Location Debug Tool</h2>";
echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

try {
    // Step 1: Check current session
    echo "<h3>1. Current Session Info</h3>";
    echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
    echo "<p><strong>Driver Phone:</strong> " . ($_SESSION['phone'] ?? 'NOT_SET') . "</p>";
    echo "<p><strong>Driver Name:</strong> " . ($_SESSION['name'] ?? 'NOT_SET') . "</p>";
    echo "<p><strong>Role:</strong> " . ($_SESSION['role'] ?? 'NOT_SET') . "</p>";
    
    // Step 2: Force setup test data
    echo "<h3>2. Setting up test data...</h3>";
    
    // First ensure we have a booking for today
    $today = date('Y-m-d');
    $update_booking = "UPDATE bookings SET travel_date = ? WHERE booking_id = 1";
    $stmt = $conn->prepare($update_booking);
    $stmt->bind_param('s', $today);
    $stmt->execute();
    echo "<p>✅ Updated booking 1 to today: $today</p>";
    
    // Ensure user has phone
    $update_user = "UPDATE users SET phone = '0712345678' WHERE id = (SELECT user_id FROM bookings WHERE booking_id = 1)";
    $conn->query($update_user);
    echo "<p>✅ Set user phone to: 0712345678</p>";
    
    // Step 3: Check all relevant data
    echo "<h3>3. Database Check</h3>";
    
    // Check bookings for today
    $bookings_today = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE DATE(travel_date) = CURDATE()");
    $count = $bookings_today->fetch_assoc()['count'];
    echo "<p><strong>Bookings for today:</strong> $count</p>";
    
    // Check users with phones
    $users_with_phones = $conn->query("SELECT COUNT(*) as count FROM users WHERE phone IS NOT NULL AND phone != ''");
    $count = $users_with_phones->fetch_assoc()['count'];
    echo "<p><strong>Users with phone numbers:</strong> $count</p>";
    
    // Check vehicles
    $vehicles = $conn->query("SELECT COUNT(*) as count FROM vehicles WHERE driver_phone IS NOT NULL AND driver_phone != ''");
    $count = $vehicles->fetch_assoc()['count'];
    echo "<p><strong>Vehicles with driver phones:</strong> $count</p>";
    
    // Step 4: Show actual booking details
    echo "<h3>4. Current Booking Details</h3>";
    $booking_details = "
        SELECT b.booking_id, b.user_id, b.fullname, b.travel_date, b.assigned_vehicle,
               v.driver_name, v.driver_phone, v.number_plate, v.type,
               u.name as user_name, u.phone as user_phone
        FROM bookings b 
        LEFT JOIN vehicles v ON b.assigned_vehicle = v.number_plate 
        LEFT JOIN users u ON b.user_id = u.id
        WHERE DATE(b.travel_date) = CURDATE()
        ORDER BY b.booking_id
        LIMIT 5
    ";
    
    $result = $conn->query($booking_details);
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #6A0DAD; color: white;'>";
        echo "<th>Booking ID</th><th>User ID</th><th>Passenger</th><th>Phone</th><th>Vehicle</th><th>Driver</th><th>Driver Phone</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['booking_id'] . "</td>";
            echo "<td>" . $row['user_id'] . "</td>";
            echo "<td>" . $row['user_name'] . "</td>";
            echo "<td>" . $row['user_phone'] . "</td>";
            echo "<td>" . $row['number_plate'] . " (" . $row['type'] . ")</td>";
            echo "<td>" . $row['driver_name'] . "</td>";
            echo "<td>" . $row['driver_phone'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No bookings found for today!</p>";
    }
    
    // Step 5: Test the exact WhatsApp query with different driver phones
    echo "<h3>5. Testing WhatsApp Query</h3>";
    
    // Get all unique driver phones from vehicles
    $driver_phones_result = $conn->query("SELECT DISTINCT driver_phone FROM vehicles WHERE driver_phone IS NOT NULL AND driver_phone != ''");
    
    if ($driver_phones_result && $driver_phones_result->num_rows > 0) {
        echo "<p>Testing with each driver phone in the system:</p>";
        
        while ($phone_row = $driver_phones_result->fetch_assoc()) {
            $test_phone = $phone_row['driver_phone'];
            echo "<h4>Testing with driver phone: $test_phone</h4>";
            
            $whatsapp_query = "
                SELECT DISTINCT u.id as user_id, u.name, u.phone, b.fullname, b.destination
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                JOIN vehicles v ON b.assigned_vehicle = v.number_plate
                WHERE v.driver_phone = ?
                AND DATE(b.travel_date) = CURDATE()
                AND u.phone IS NOT NULL
                AND u.phone != ''
            ";
            
            $stmt = $conn->prepare($whatsapp_query);
            $stmt->bind_param('s', $test_phone);
            $stmt->execute();
            $result = $stmt->get_result();
            
            echo "<p><strong>Passengers found:</strong> " . $result->num_rows . "</p>";
            
            if ($result->num_rows > 0) {
                echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
                echo "<tr style='background: #25D366; color: white;'><th>User ID</th><th>Name</th><th>Phone</th><th>Passenger Name</th></tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['user_id'] . "</td>";
                    echo "<td>" . $row['name'] . "</td>";
                    echo "<td>" . $row['phone'] . "</td>";
                    echo "<td>" . $row['fullname'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<h4 style='color: #155724; margin: 0 0 10px 0;'>✅ SUCCESS! WhatsApp will work with this driver phone:</h4>";
                echo "<p><strong>Driver Phone to use:</strong> $test_phone</p>";
                echo "<p><strong>Passengers found:</strong> " . $result->num_rows . "</p>";
                echo "</div>";
                break;
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #6A0DAD; }
table { width: 100%; }
th, td { padding: 8px; text-align: left; }
tr:nth-child(even) { background: #f9f9f9; }
</style>