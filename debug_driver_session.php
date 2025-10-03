<?php
echo "<h2>🎯 DEFINITIVE TEST - Driver Session Check</h2>";

// Start session and check current user
session_start();

echo "<h3>1. Current Session Information</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
echo "<strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? "Active" : "Inactive") . "<br>";
echo "<strong>Session ID:</strong> " . session_id() . "<br>";
echo "<strong>Driver Phone:</strong> " . ($_SESSION['phone'] ?? 'NOT SET') . "<br>";
echo "<strong>Driver Name:</strong> " . ($_SESSION['name'] ?? 'NOT SET') . "<br>";
echo "<strong>Role:</strong> " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";
echo "</div>";

if (isset($_SESSION['phone'])) {
    $driver_phone = $_SESSION['phone'];
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
    echo "✅ <strong>You are logged in as driver: $driver_phone</strong>";
    echo "</div>";
    
    // Test the exact same query that share_google_maps_link.php uses
    require_once 'db.php';
    
    echo "<h3>2. Test Query for Your Driver</h3>";
    
    // Get vehicles
    $vehicles = [];
    $vehicle_stmt = $conn->prepare("SELECT number_plate FROM vehicles WHERE driver_phone = ?");
    $vehicle_stmt->bind_param("s", $driver_phone);
    $vehicle_stmt->execute();
    $vehicle_result = $vehicle_stmt->get_result();
    while ($row = $vehicle_result->fetch_assoc()) {
        $vehicles[] = $row['number_plate'];
    }
    
    if (!empty($vehicles)) {
        echo "<strong>Your vehicles:</strong> " . implode(', ', $vehicles) . "<br>";
        
        // Test the exact query from share_google_maps_link.php
        $placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
        $stmt = $conn->prepare("
            SELECT DISTINCT 
                b.user_id, 
                b.fullname as passenger_name, 
                b.phone as passenger_phone,
                v.number_plate,
                v.type as vehicle_type
            FROM bookings b
            JOIN vehicles v ON b.assigned_vehicle = v.number_plate
            WHERE b.assigned_vehicle IN ($placeholders)
            AND DATE(b.created_at) = CURDATE()
        ");

        $types = str_repeat('s', count($vehicles));
        $stmt->bind_param($types, ...$vehicles);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $passenger_count = $result->num_rows;
        
        if ($passenger_count > 0) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
            echo "✅ <strong>SUCCESS! Found $passenger_count passenger(s) for your session!</strong><br>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
            echo "<tr><th>User ID</th><th>Name</th><th>Phone</th><th>Vehicle</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$row['user_id']}</td>";
                echo "<td>{$row['passenger_name']}</td>";
                echo "<td>{$row['passenger_phone']}</td>";
                echo "<td>{$row['number_plate']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
            echo "❌ <strong>No passengers found for your driver session</strong>";
            echo "</div>";
        }
        
        echo "<h3>3. Live API Test</h3>";
        echo "<p>Now test with your actual session:</p>";
        echo "<div style='background: #fff; padding: 15px; border: 2px solid #6A0DAD; border-radius: 8px; margin: 15px 0;'>";
        echo "<form method='post' style='margin: 0;'>";
        echo "<input type='hidden' name='test_api' value='1'>";
        echo "<label><strong>Google Maps Link:</strong></label><br>";
        echo "<input type='url' name='google_maps_link' value='https://maps.app.goo.gl/LiveTest" . time() . "' style='width: 100%; padding: 8px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px;'>";
        echo "<br><button type='submit' style='background: #6A0DAD; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>🧪 Test Google Maps Sharing API</button>";
        echo "</form>";
        echo "</div>";
        
        // Handle API test
        if (isset($_POST['test_api'])) {
            echo "<h4>📡 API Test Result:</h4>";
            
            $test_link = $_POST['google_maps_link'];
            
            // Simulate the API call
            $_POST_backup = $_POST;
            $_POST = [
                'driver_phone' => $driver_phone,
                'google_maps_link' => $test_link,
                'csrf_token' => $_SESSION['csrf_token'] ?? 'test'
            ];
            
            // Capture output
            ob_start();
            include 'Driver/share_google_maps_link.php';
            $api_output = ob_get_clean();
            
            // Restore POST
            $_POST = $_POST_backup;
            
            echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; max-height: 200px; overflow-y: auto;'>$api_output</pre>";
            
            $api_result = json_decode($api_output, true);
            if ($api_result && $api_result['success']) {
                echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
                echo "<h4>🎉 API TEST SUCCESS!</h4>";
                echo "<p>✅ Google Maps sharing API is working!</p>";
                echo "<p><strong>Passengers notified:</strong> {$api_result['passengers_notified']}</p>";
                echo "<p><strong>If you're still seeing 0 in the dashboard, it's a browser cache issue!</strong></p>";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
                echo "<h4>❌ API TEST FAILED</h4>";
                echo "<p>Error: " . ($api_result['message'] ?? 'Unknown error') . "</p>";
                echo "</div>";
            }
        }
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
        echo "❌ <strong>No vehicles assigned to your driver</strong>";
        echo "</div>";
    }
    
} else {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
    echo "⚠️ <strong>You are NOT logged in as a driver!</strong><br>";
    echo "<p>Please login to the driver dashboard first:</p>";
    echo "<p><a href='Driver/index.php' style='background: #6A0DAD; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>👨‍💼 Login as Driver</a></p>";
    echo "</div>";
}

echo "<h3>4. Browser Cache Fix</h3>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
echo "<h4>If the API works but dashboard still shows 0:</h4>";
echo "<ol>";
echo "<li><strong>Hard Refresh:</strong> Press <code>Ctrl + F5</code> (Windows) or <code>Cmd + Shift + R</code> (Mac)</li>";
echo "<li><strong>Clear Cache:</strong> Clear your browser cache completely</li>";
echo "<li><strong>Incognito Mode:</strong> Try the driver dashboard in incognito/private browsing</li>";
echo "<li><strong>Check Console:</strong> Press F12 and check for JavaScript errors</li>";
echo "</ol>";
echo "</div>";

?>