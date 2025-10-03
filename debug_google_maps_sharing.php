<?php
// Debug script for Google Maps sharing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Google Maps Sharing Debug</h2>";

// Test database connection
echo "<h3>1. Database Connection Test</h3>";
try {
    require_once 'db.php';
    if (isset($conn) && !$conn->connect_error) {
        echo "✅ Database connection successful<br>";
        echo "📊 Connection details: " . $conn->host_info . "<br>";
    } else {
        echo "❌ Database connection failed: " . ($conn->connect_error ?? 'Unknown error') . "<br>";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Database connection exception: " . $e->getMessage() . "<br>";
    exit;
}

// Test drivers table
echo "<h3>2. Drivers Table Test</h3>";
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM drivers");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✅ Drivers table accessible<br>";
        echo "👥 Total drivers: " . $row['count'] . "<br>";
        
        // Show sample drivers
        $sample = $conn->query("SELECT id, driver_phone, name FROM drivers LIMIT 3");
        if ($sample && $sample->num_rows > 0) {
            echo "<strong>Sample drivers:</strong><br>";
            while ($driver = $sample->fetch_assoc()) {
                echo "- ID: {$driver['id']}, Phone: {$driver['driver_phone']}, Name: " . ($driver['name'] ?? 'N/A') . "<br>";
            }
        }
    } else {
        echo "❌ Cannot access drivers table: " . $conn->error . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Drivers table error: " . $e->getMessage() . "<br>";
}

// Test driver_locations table
echo "<h3>3. Driver Locations Table Test</h3>";
try {
    $result = $conn->query("SHOW COLUMNS FROM driver_locations");
    if ($result) {
        echo "✅ Driver_locations table accessible<br>";
        echo "<strong>Columns:</strong><br>";
        while ($col = $result->fetch_assoc()) {
            echo "- {$col['Field']} ({$col['Type']})<br>";
        }
        
        // Check for google_maps_link column
        $check_gmaps = $conn->query("SHOW COLUMNS FROM driver_locations LIKE 'google_maps_link'");
        if ($check_gmaps && $check_gmaps->num_rows > 0) {
            echo "✅ google_maps_link column exists<br>";
        } else {
            echo "❌ google_maps_link column missing - needs to be added<br>";
        }
    } else {
        echo "❌ Cannot access driver_locations table: " . $conn->error . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Driver_locations table error: " . $e->getMessage() . "<br>";
}

// Test link validation function
echo "<h3>4. Link Validation Test</h3>";
function isValidGoogleMapsLink($link) {
    $patterns = [
        '/^https:\/\/maps\.app\.goo\.gl\/.+/',
        '/^https:\/\/www\.google\.com\/maps\/.+/',
        '/^https:\/\/goo\.gl\/maps\/.+/',
        '/^https:\/\/maps\.google\.com\/.+/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $link)) {
            return true;
        }
    }
    
    return false;
}

$test_links = [
    'https://maps.app.goo.gl/ABC123XYZ789',
    'https://www.google.com/maps/place/@-1.286389,36.817223,15z',
    'https://maps.google.com/maps?q=-1.286389,36.817223',
    'https://invalid-link.com/test'
];

foreach ($test_links as $link) {
    $valid = isValidGoogleMapsLink($link) ? '✅' : '❌';
    echo "$valid $link<br>";
}

// Test session handling
echo "<h3>5. Session Test</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "📊 Session status: " . 
    (session_status() === PHP_SESSION_ACTIVE ? '✅ Active' : '❌ Inactive') . "<br>";
echo "🔑 Session ID: " . session_id() . "<br>";

if (!isset($_SESSION['test_csrf'])) {
    $_SESSION['test_csrf'] = bin2hex(random_bytes(16));
}
echo "🛡️ CSRF token: " . $_SESSION['test_csrf'] . "<br>";

// Test API endpoints
echo "<h3>6. API Endpoint Test</h3>";
$test_phone = '0798365350'; // Use actual driver phone
$test_link = 'https://maps.app.goo.gl/TestLink123';

echo "<strong>Test parameters:</strong><br>";
echo "📱 Driver phone: $test_phone<br>";
echo "🔗 Test link: $test_link<br>";

// Check if this driver has any vehicles
$vehicle_check = $conn->query("SELECT number_plate FROM vehicles WHERE driver_phone = '$test_phone'");
if ($vehicle_check && $vehicle_check->num_rows > 0) {
    echo "✅ Driver has vehicles: ";
    while ($v = $vehicle_check->fetch_assoc()) {
        echo $v['number_plate'] . " ";
    }
    echo "<br>";
} else {
    echo "❌ Driver has no vehicles assigned<br>";
}

// Check today's bookings for all drivers
echo "<h3>📊 Today's Bookings Analysis</h3>";
$today_bookings = $conn->query("
    SELECT 
        COUNT(*) as total_today,
        COUNT(CASE WHEN assigned_vehicle IS NOT NULL AND assigned_vehicle != '' THEN 1 END) as with_vehicle
    FROM bookings 
    WHERE DATE(travel_date) = CURDATE()
");

if ($today_bookings) {
    $stats = $today_bookings->fetch_assoc();
    echo "📈 Total bookings today: {$stats['total_today']}<br>";
    echo "🚗 With vehicle assigned: {$stats['with_vehicle']}<br>";
    
    if ($stats['total_today'] == 0) {
        echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "⚠️ <strong>No bookings for today!</strong> You need to create a booking for today to test passenger notifications.";
        echo "</div>";
    }
} else {
    echo "❌ Could not check today's bookings<br>";
}

// Show sample bookings if any exist
$sample_bookings = $conn->query("
    SELECT b.user_id, b.fullname, b.phone, b.assigned_vehicle, b.travel_date
    FROM bookings b
    WHERE DATE(b.travel_date) = CURDATE()
    LIMIT 5
");

if ($sample_bookings && $sample_bookings->num_rows > 0) {
    echo "<h4>📋 Today's Bookings:</h4>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>User ID</th><th>Name</th><th>Phone</th><th>Vehicle</th><th>Date</th></tr>";
    while ($booking = $sample_bookings->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$booking['user_id']}</td>";
        echo "<td>{$booking['fullname']}</td>";
        echo "<td>{$booking['phone']}</td>";
        echo "<td>{$booking['assigned_vehicle']}</td>";
        echo "<td>{$booking['travel_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Create a form for manual testing
echo "<h3>7. Manual Test Form</h3>";
?>
<form method="POST" action="">
    <input type="hidden" name="test_action" value="share">
    <label>Driver Phone:</label><br>
    <select name="driver_phone" style="width: 300px; padding: 5px; margin: 5px 0;">
        <option value="0798365350">0798365350 (Kibet Bett)</option>
        <option value="0736225373">0736225373 (Driver1)</option>
    </select><br>
    
    <label>Google Maps Link:</label><br>
    <input type="text" name="google_maps_link" value="<?= htmlspecialchars($test_link) ?>" style="width: 300px; padding: 5px; margin: 5px 0;"><br>
    
    <button type="submit" style="padding: 10px 20px; background: #6A0DAD; color: white; border: none; border-radius: 5px; cursor: pointer;">
        🧪 Test Share Function
    </button>
</form>

<?php
// Process test form
if (isset($_POST['test_action']) && $_POST['test_action'] === 'share') {
    echo "<h3>8. Test Results</h3>";
    
    $driver_phone = $_POST['driver_phone'];
    $google_maps_link = $_POST['google_maps_link'];
    
    echo "<strong>Testing share functionality...</strong><br>";
    
    // Test driver lookup
    $stmt = $conn->prepare("SELECT id FROM drivers WHERE driver_phone = ?");
    if ($stmt) {
        $stmt->bind_param('s', $driver_phone);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $driver_id = $row['id'];
            echo "✅ Driver found: ID $driver_id<br>";
            
            // Test location update
            $update_stmt = $conn->prepare("
                INSERT INTO driver_locations (driver_id, google_maps_link, status, last_updated) 
                VALUES (?, ?, 'sharing_gmaps', NOW()) 
                ON DUPLICATE KEY UPDATE 
                    google_maps_link = VALUES(google_maps_link), 
                    status = 'sharing_gmaps', 
                    last_updated = NOW()
            ");
            
            if ($update_stmt) {
                $update_stmt->bind_param('is', $driver_id, $google_maps_link);
                if ($update_stmt->execute()) {
                    echo "✅ Location update successful<br>";
                                
                    // Now test passenger notification
                    echo "<strong>Testing passenger lookup...</strong><br>";
                                
                    // Get vehicles for this driver
                    $vehicle_check = $conn->prepare("SELECT number_plate FROM vehicles WHERE driver_phone = ?");
                    $vehicle_check->bind_param('s', $driver_phone);
                    $vehicle_check->execute();
                    $vehicle_result = $vehicle_check->get_result();
                    $vehicles = [];
                    while ($v = $vehicle_result->fetch_assoc()) {
                        $vehicles[] = $v['number_plate'];
                    }
                    echo "Vehicles found: " . implode(', ', $vehicles) . "<br>";
                                
                    if (!empty($vehicles)) {
                        // Check today's bookings
                        $placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
                        $booking_check = $conn->prepare("
                            SELECT b.user_id, b.fullname, b.phone, b.assigned_vehicle, b.travel_date
                            FROM bookings b
                            WHERE b.assigned_vehicle IN ($placeholders)
                            AND DATE(b.travel_date) = CURDATE()
                        ");
                        $types = str_repeat('s', count($vehicles));
                        $booking_check->bind_param($types, ...$vehicles);
                        $booking_check->execute();
                        $booking_result = $booking_check->get_result();
                                    
                        echo "Bookings found for today: " . $booking_result->num_rows . "<br>";
                                    
                        if ($booking_result->num_rows > 0) {
                            echo "<strong>Booking details:</strong><br>";
                            while ($booking = $booking_result->fetch_assoc()) {
                                echo "- User ID: {$booking['user_id']}, Name: {$booking['fullname']}, Vehicle: {$booking['assigned_vehicle']}, Date: {$booking['travel_date']}<br>";
                            }
                        } else {
                            echo "❌ No bookings found for driver's vehicles today<br>";
                        }
                    } else {
                        echo "❌ No vehicles found for driver<br>";
                    }
                } else {
                    echo "❌ Location update failed: " . $update_stmt->error . "<br>";
                }
            } else {
                echo "❌ Location update prepare failed: " . $conn->error . "<br>";
            }
        } else {
            echo "❌ Driver not found for phone: $driver_phone<br>";
            
            // Show available drivers
            $all_drivers = $conn->query("SELECT id, driver_phone, name FROM drivers LIMIT 5");
            if ($all_drivers && $all_drivers->num_rows > 0) {
                echo "<strong>Available drivers:</strong><br>";
                while ($driver = $all_drivers->fetch_assoc()) {
                    echo "- ID: {$driver['id']}, Phone: {$driver['driver_phone']}, Name: " . ($driver['name'] ?? 'N/A') . "<br>";
                }
            }
        }
    } else {
        echo "❌ Driver lookup prepare failed: " . $conn->error . "<br>";
    }
}

echo "<h3>🎉 Debug Complete</h3>";
echo "<p>If all tests pass, the Google Maps sharing should work. If there are issues, check the error messages above.</p>";
?>