<?php
// Configure session to match the login system
if (session_status() === PHP_SESSION_NONE) {
    session_name('southrift_admin');
    session_start();
} else {
    session_start();
}

require_once 'db.php';

echo "<h1>Debug: Driver Location Sharing Connection</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} table{border-collapse:collapse;margin:10px 0;} th,td{border:1px solid #ccc;padding:8px;}</style>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p class='error'>❌ You need to be logged in as a passenger to test this.</p>";
    echo "<p><a href='login.php'>Login here</a></p>";
    exit;
}

$user_id = $_SESSION['user_id'];
echo "<p class='info'>📋 Testing for user ID: {$user_id}</p>";

// 1. Check user's bookings
echo "<h2>1. User's Bookings Today</h2>";
$booking_stmt = $conn->prepare("
    SELECT b.*, v.driver_phone, v.driver_name, v.number_plate
    FROM bookings b
    JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE b.user_id = ?
    AND DATE(b.travel_date) = CURDATE()
    ORDER BY b.created_at DESC
");
$booking_stmt->bind_param('i', $user_id);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();

if ($booking_result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Booking ID</th><th>Route</th><th>Vehicle</th><th>Driver Phone</th><th>Driver Name</th></tr>";
    
    while ($booking = $booking_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($booking['booking_id']) . "</td>";
        echo "<td>" . htmlspecialchars($booking['route']) . "</td>";
        echo "<td>" . htmlspecialchars($booking['number_plate']) . "</td>";
        echo "<td>" . htmlspecialchars($booking['driver_phone']) . "</td>";
        echo "<td>" . htmlspecialchars($booking['driver_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Get the first booking for detailed testing
    $booking_result->data_seek(0);
    $test_booking = $booking_result->fetch_assoc();
    $driver_phone = $test_booking['driver_phone'];
    
    echo "<h2>2. Driver Location Data</h2>";
    echo "<p class='info'>🔍 Checking location data for driver phone: {$driver_phone}</p>";
    
    // Check if driver exists in drivers table
    $driver_check = $conn->prepare("SELECT id, name, driver_phone FROM drivers WHERE driver_phone = ?");
    $driver_check->bind_param('s', $driver_phone);
    $driver_check->execute();
    $driver_result = $driver_check->get_result();
    
    if ($driver_result->num_rows > 0) {
        $driver = $driver_result->fetch_assoc();
        echo "<p class='success'>✅ Driver found in drivers table:</p>";
        echo "<ul>";
        echo "<li>Driver ID: " . $driver['id'] . "</li>";
        echo "<li>Name: " . htmlspecialchars($driver['name']) . "</li>";
        echo "<li>Phone: " . htmlspecialchars($driver['driver_phone']) . "</li>";
        echo "</ul>";
        
        $driver_id = $driver['id'];
        
        // Check driver_locations table
        echo "<h3>Driver Location Records:</h3>";
        $location_stmt = $conn->prepare("
            SELECT * FROM driver_locations 
            WHERE driver_id = ? 
            ORDER BY last_updated DESC 
            LIMIT 5
        ");
        $location_stmt->bind_param('i', $driver_id);
        $location_stmt->execute();
        $location_result = $location_stmt->get_result();
        
        if ($location_result->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Latitude</th><th>Longitude</th><th>Status</th><th>Last Updated</th><th>Share Token</th></tr>";
            
            while ($location = $location_result->fetch_assoc()) {
                $status_class = $location['status'] === 'active' ? 'success' : 'error';
                echo "<tr>";
                echo "<td>" . $location['id'] . "</td>";
                echo "<td>" . htmlspecialchars($location['latitude']) . "</td>";
                echo "<td>" . htmlspecialchars($location['longitude']) . "</td>";
                echo "<td class='{$status_class}'>" . htmlspecialchars($location['status']) . "</td>";
                echo "<td>" . htmlspecialchars($location['last_updated']) . "</td>";
                echo "<td>" . htmlspecialchars($location['share_token'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Test the exact query used by track_my_driver.php
            echo "<h3>Testing Track My Driver Query:</h3>";
            $test_query = $conn->prepare("
                SELECT dl.latitude, dl.longitude, dl.status, dl.last_updated, dl.share_token,
                       d.name as driver_name, d.driver_phone
                FROM driver_locations dl
                JOIN drivers d ON dl.driver_id = d.id
                WHERE d.driver_phone = ?
                AND dl.status = 'active'
                ORDER BY dl.last_updated DESC
                LIMIT 1
            ");
            $test_query->bind_param('s', $driver_phone);
            $test_query->execute();
            $test_result = $test_query->get_result();
            
            if ($test_result->num_rows > 0) {
                $test_location = $test_result->fetch_assoc();
                echo "<p class='success'>✅ Query returned active location:</p>";
                echo "<ul>";
                echo "<li>Latitude: " . $test_location['latitude'] . "</li>";
                echo "<li>Longitude: " . $test_location['longitude'] . "</li>";
                echo "<li>Status: " . $test_location['status'] . "</li>";
                echo "<li>Last Updated: " . $test_location['last_updated'] . "</li>";
                echo "<li>Driver Name: " . htmlspecialchars($test_location['driver_name']) . "</li>";
                echo "</ul>";
                
                echo "<h3>🗺️ Test Map Link:</h3>";
                $maps_url = "https://www.google.com/maps?q={$test_location['latitude']},{$test_location['longitude']}&z=15";
                echo "<p><a href='{$maps_url}' target='_blank'>📍 View on Google Maps</a></p>";
                
            } else {
                echo "<p class='error'>❌ No active location found. The query returned no results.</p>";
                echo "<p>This means either:</p>";
                echo "<ul>";
                echo "<li>Driver hasn't shared location yet</li>";
                echo "<li>Location status is not 'active'</li>";
                echo "<li>Driver ID mismatch between tables</li>";
                echo "</ul>";
            }
            
        } else {
            echo "<p class='error'>❌ No location records found for this driver.</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Driver not found in drivers table with phone: {$driver_phone}</p>";
        echo "<p>Available drivers:</p>";
        $all_drivers = $conn->query("SELECT id, name, driver_phone FROM drivers LIMIT 10");
        if ($all_drivers->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Phone</th></tr>";
            while ($d = $all_drivers->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $d['id'] . "</td>";
                echo "<td>" . htmlspecialchars($d['name']) . "</td>";
                echo "<td>" . htmlspecialchars($d['driver_phone']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
} else {
    echo "<p class='error'>❌ No bookings found for today.</p>";
    
    // Show all bookings for this user
    echo "<h3>All user bookings:</h3>";
    $all_bookings = $conn->prepare("SELECT * FROM bookings WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $all_bookings->bind_param('i', $user_id);
    $all_bookings->execute();
    $all_result = $all_bookings->get_result();
    
    if ($all_result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>Booking ID</th><th>Route</th><th>Travel Date</th><th>Assigned Vehicle</th></tr>";
        while ($b = $all_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $b['booking_id'] . "</td>";
            echo "<td>" . htmlspecialchars($b['route']) . "</td>";
            echo "<td>" . htmlspecialchars($b['travel_date']) . "</td>";
            echo "<td>" . htmlspecialchars($b['assigned_vehicle'] ?? 'Not assigned') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No bookings found for this user.</p>";
    }
}

echo "<h2>3. Quick Actions</h2>";
echo "<p><a href='track_my_driver.php'>🚗 Test Track My Driver</a></p>";
echo "<p><a href='Driver/index.php'>👨‍💼 Go to Driver Dashboard</a></p>";

?>