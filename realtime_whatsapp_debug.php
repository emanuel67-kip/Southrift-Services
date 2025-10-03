<?php
// Real-time WhatsApp debug for current session
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

echo "<h2>🔍 Real-Time WhatsApp Debug</h2>";
echo "<p><em>This shows exactly what happens when you click the WhatsApp button</em></p>";

try {
    // Show current session info
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>📱 Current Session Info</h3>";
    echo "<p><strong>Driver Phone:</strong> " . ($_SESSION['phone'] ?? 'NOT_SET') . "</p>";
    echo "<p><strong>Driver Name:</strong> " . ($_SESSION['name'] ?? 'NOT_SET') . "</p>";
    echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
    echo "</div>";
    
    $driver_phone = $_SESSION['phone'] ?? null;
    
    if (!$driver_phone) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
        echo "<h3>❌ Problem Found!</h3>";
        echo "<p><strong>Driver phone is not set in session.</strong></p>";
        echo "<p>You need to login as a driver first.</p>";
        echo "<p><a href='Driver/index.php'>Go to Driver Dashboard</a></p>";
        echo "</div>";
        exit;
    }
    
    echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>🔍 Testing WhatsApp Query with Phone: $driver_phone</h3>";
    echo "</div>";
    
    // Test the exact query that WhatsApp sender uses
    $query = "
        SELECT DISTINCT u.id as user_id, u.name, u.phone, b.fullname
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN vehicles v ON b.assigned_vehicle = v.number_plate
        WHERE v.driver_phone = ?
        AND DATE(b.travel_date) = CURDATE()
        AND u.phone IS NOT NULL
        AND u.phone != ''
    ";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>🗄️ Running Query:</h4>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto;'>";
    echo htmlspecialchars($query);
    echo "</pre>";
    echo "<p><strong>Parameters:</strong> driver_phone = '$driver_phone'</p>";
    echo "</div>";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $driver_phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $passenger_count = $result->num_rows;
    
    if ($passenger_count > 0) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
        echo "<h3>✅ Success! Found $passenger_count passenger(s)</h3>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #25D366; color: white;'>";
        echo "<th>User ID</th><th>Name</th><th>Phone</th><th>Passenger Name</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['user_id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . $row['phone'] . "</td>";
            echo "<td>" . $row['fullname'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p><strong>✅ WhatsApp should work perfectly!</strong></p>";
        echo "<p>If you're still getting the error, there might be a parameter passing issue.</p>";
        echo "</div>";
        
        // Test the actual WhatsApp sender API
        echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>🧪 Test WhatsApp API Call</h4>";
        echo "<p>Click this button to test the exact same call that the WhatsApp button makes:</p>";
        
        // Create a test form that simulates the WhatsApp button
        echo "<form method='post' action='Driver/whatsapp_location_sender.php' style='margin: 10px 0;'>";
        echo "<input type='hidden' name='csrf_token' value='" . ($_SESSION['csrf_token'] ?? '') . "'>";
        echo "<input type='hidden' name='driver_id' value='$driver_phone'>";
        echo "<input type='hidden' name='latitude' value='-1.286389'>";
        echo "<input type='hidden' name='longitude' value='36.817223'>";
        echo "<input type='hidden' name='message' value='Test message from debug script'>";
        echo "<button type='submit' style='background: #25D366; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
        echo "🧪 Test WhatsApp API Call";
        echo "</button>";
        echo "</form>";
        echo "</div>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
        echo "<h3>❌ No passengers found</h3>";
        echo "<p>Let me debug why...</p>";
        echo "</div>";
        
        // Debug step by step
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>🔍 Step-by-Step Debug</h4>";
        
        // 1. Check if there are bookings for today
        $bookings_today = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE DATE(travel_date) = CURDATE()");
        $booking_count = $bookings_today->fetch_assoc()['count'];
        echo "<p>1️⃣ <strong>Bookings for today:</strong> $booking_count</p>";
        
        if ($booking_count == 0) {
            echo "<p style='color: red;'>⚠️ No bookings for today! Creating test booking...</p>";
            
            // Create a test booking
            $today = date('Y-m-d');
            $conn->query("UPDATE bookings SET travel_date = '$today' WHERE booking_id = 1");
            $conn->query("UPDATE users SET phone = '0712345678' WHERE id = (SELECT user_id FROM bookings WHERE booking_id = 1)");
            
            echo "<p style='color: green;'>✅ Created test booking for today</p>";
        }
        
        // 2. Check vehicles with this phone
        $vehicle_check = $conn->prepare("SELECT number_plate, driver_name FROM vehicles WHERE driver_phone = ?");
        $vehicle_check->bind_param('s', $driver_phone);
        $vehicle_check->execute();
        $vehicle_result = $vehicle_check->get_result();
        $vehicle_count = $vehicle_result->num_rows;
        
        echo "<p>2️⃣ <strong>Vehicles with phone '$driver_phone':</strong> $vehicle_count</p>";
        
        if ($vehicle_count == 0) {
            echo "<p style='color: red;'>⚠️ No vehicles found with your phone number!</p>";
            echo "<p>Available vehicles:</p>";
            
            $all_vehicles = $conn->query("SELECT number_plate, driver_name, driver_phone FROM vehicles LIMIT 5");
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Plate</th><th>Driver</th><th>Phone</th></tr>";
            while ($v = $all_vehicles->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $v['number_plate'] . "</td>";
                echo "<td>" . $v['driver_name'] . "</td>";
                echo "<td>" . $v['driver_phone'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<p style='color: orange;'>💡 <strong>Solution:</strong> Login with one of the phone numbers above, or update your vehicle record.</p>";
        } else {
            while ($vehicle = $vehicle_result->fetch_assoc()) {
                echo "<p>✅ Vehicle found: " . $vehicle['number_plate'] . " (" . $vehicle['driver_name'] . ")</p>";
            }
        }
        
        // 3. Check users with phones
        $users_with_phones = $conn->query("SELECT COUNT(*) as count FROM users WHERE phone IS NOT NULL AND phone != ''");
        $user_count = $users_with_phones->fetch_assoc()['count'];
        echo "<p>3️⃣ <strong>Users with phone numbers:</strong> $user_count</p>";
        
        // 4. Try to run the query step by step
        echo "<h5>🔍 Debugging the JOIN query:</h5>";
        
        // First, get bookings for today
        $step1 = $conn->query("SELECT booking_id, user_id, fullname, assigned_vehicle FROM bookings WHERE DATE(travel_date) = CURDATE()");
        echo "<p>Step 1 - Bookings today: " . $step1->num_rows . "</p>";
        
        // Then check the vehicle join
        $step2 = $conn->prepare("
            SELECT b.booking_id, b.assigned_vehicle, v.number_plate, v.driver_phone 
            FROM bookings b 
            JOIN vehicles v ON b.assigned_vehicle = v.number_plate 
            WHERE DATE(b.travel_date) = CURDATE() AND v.driver_phone = ?
        ");
        $step2->bind_param('s', $driver_phone);
        $step2->execute();
        $step2_result = $step2->get_result();
        echo "<p>Step 2 - After vehicle join: " . $step2_result->num_rows . "</p>";
        
        // Finally check the user join
        $step3 = $conn->prepare("
            SELECT b.booking_id, u.id, u.name, u.phone 
            FROM bookings b 
            JOIN vehicles v ON b.assigned_vehicle = v.number_plate 
            JOIN users u ON b.user_id = u.id
            WHERE DATE(b.travel_date) = CURDATE() 
            AND v.driver_phone = ?
            AND u.phone IS NOT NULL 
            AND u.phone != ''
        ");
        $step3->bind_param('s', $driver_phone);
        $step3->execute();
        $step3_result = $step3->get_result();
        echo "<p>Step 3 - After user join with phone filter: " . $step3_result->num_rows . "</p>";
        
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
    echo "<h3>❌ Error occurred</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; max-width: 1000px; }
h2, h3, h4 { color: #6A0DAD; }
table { width: 100%; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background: #f8f9fa; }
tr:nth-child(even) { background: #f9f9f9; }
pre { font-size: 12px; }
</style>