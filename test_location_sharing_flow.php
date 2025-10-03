<?php
require_once 'db.php';

echo "<h1>🧪 Testing Complete Location Sharing Flow</h1>";

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { background: #e7f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
.warning { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
h2, h3 { color: #6A0DAD; }
</style>";

try {
    echo "<h2>Step 1: Check Database Setup</h2>";
    
    // Check if tables exist
    $tables_check = [
        'drivers' => $conn->query("SHOW TABLES LIKE 'drivers'")->num_rows > 0,
        'driver_locations' => $conn->query("SHOW TABLES LIKE 'driver_locations'")->num_rows > 0,
        'bookings' => $conn->query("SHOW TABLES LIKE 'bookings'")->num_rows > 0,
        'vehicles' => $conn->query("SHOW TABLES LIKE 'vehicles'")->num_rows > 0,
        'notifications' => $conn->query("SHOW TABLES LIKE 'notifications'")->num_rows > 0
    ];
    
    foreach ($tables_check as $table => $exists) {
        if ($exists) {
            echo "<div class='success'>✅ Table '$table' exists</div>";
        } else {
            echo "<div class='error'>❌ Table '$table' missing</div>";
        }
    }
    
    echo "<h2>Step 2: Check Test Data</h2>";
    
    // Check for test driver
    $driver_check = $conn->query("SELECT id, driver_phone, name FROM drivers LIMIT 5");
    if ($driver_check->num_rows > 0) {
        echo "<div class='success'>✅ Found drivers:</div>";
        while ($driver = $driver_check->fetch_assoc()) {
            echo "<ul><li>Driver ID: {$driver['id']}, Phone: {$driver['driver_phone']}, Name: {$driver['name']}</li></ul>";
        }
    } else {
        echo "<div class='error'>❌ No drivers found in database</div>";
    }
    
    // Check for test vehicles
    $vehicle_check = $conn->query("SELECT number_plate, driver_phone, type FROM vehicles LIMIT 5");
    if ($vehicle_check->num_rows > 0) {
        echo "<div class='success'>✅ Found vehicles:</div>";
        while ($vehicle = $vehicle_check->fetch_assoc()) {
            echo "<ul><li>Vehicle: {$vehicle['number_plate']}, Driver: {$vehicle['driver_phone']}, Type: {$vehicle['type']}</li></ul>";
        }
    } else {
        echo "<div class='error'>❌ No vehicles found in database</div>";
    }
    
    // Check for test bookings
    $booking_check = $conn->query("
        SELECT b.booking_id, b.user_id, b.fullname, b.assigned_vehicle, b.status, v.driver_phone 
        FROM bookings b 
        LEFT JOIN vehicles v ON b.assigned_vehicle = v.number_plate 
        WHERE b.assigned_vehicle IS NOT NULL 
        LIMIT 5
    ");
    if ($booking_check->num_rows > 0) {
        echo "<div class='success'>✅ Found bookings with assigned vehicles:</div>";
        while ($booking = $booking_check->fetch_assoc()) {
            echo "<ul><li>Booking ID: {$booking['booking_id']}, Passenger: {$booking['fullname']}, Vehicle: {$booking['assigned_vehicle']}, Driver: {$booking['driver_phone']}, Status: {$booking['status']}</li></ul>";
        }
    } else {
        echo "<div class='warning'>⚠️ No bookings with assigned vehicles found</div>";
    }
    
    echo "<h2>Step 3: Test Google Maps Link Storage</h2>";
    
    // Get first driver for testing
    $test_driver = $conn->query("SELECT id, driver_phone FROM drivers LIMIT 1")->fetch_assoc();
    if ($test_driver) {
        $test_google_maps_link = "https://maps.app.goo.gl/test123456789";
        
        $store_stmt = $conn->prepare("
            INSERT INTO driver_locations (driver_id, google_maps_link, status, last_updated) 
            VALUES (?, ?, 'sharing_gmaps', NOW()) 
            ON DUPLICATE KEY UPDATE 
                google_maps_link = VALUES(google_maps_link), 
                status = 'sharing_gmaps', 
                last_updated = NOW()
        ");
        $store_stmt->bind_param('is', $test_driver['id'], $test_google_maps_link);
        
        if ($store_stmt->execute()) {
            echo "<div class='success'>✅ Successfully stored test Google Maps link for driver: {$test_driver['driver_phone']}</div>";
        } else {
            echo "<div class='error'>❌ Failed to store Google Maps link: " . $store_stmt->error . "</div>";
        }
    }
    
    echo "<h2>Step 4: Test Location Retrieval</h2>";
    
    if ($test_driver) {
        $retrieve_stmt = $conn->prepare("
            SELECT dl.google_maps_link, dl.status, dl.last_updated,
                   d.name as driver_name, d.driver_phone
            FROM driver_locations dl
            JOIN drivers d ON dl.driver_id = d.id
            WHERE d.driver_phone = ?
            AND dl.status = 'sharing_gmaps'
            AND dl.google_maps_link IS NOT NULL
            AND dl.google_maps_link != ''
            ORDER BY dl.last_updated DESC
            LIMIT 1
        ");
        $retrieve_stmt->bind_param('s', $test_driver['driver_phone']);
        $retrieve_stmt->execute();
        $result = $retrieve_stmt->get_result();
        $gmaps_data = $result->fetch_assoc();
        
        if ($gmaps_data) {
            echo "<div class='success'>✅ Successfully retrieved Google Maps data:</div>";
            echo "<ul>";
            echo "<li>Driver: {$gmaps_data['driver_name']} ({$gmaps_data['driver_phone']})</li>";
            echo "<li>Link: {$gmaps_data['google_maps_link']}</li>";
            echo "<li>Status: {$gmaps_data['status']}</li>";
            echo "<li>Last Updated: {$gmaps_data['last_updated']}</li>";
            echo "</ul>";
        } else {
            echo "<div class='error'>❌ Could not retrieve Google Maps data</div>";
        }
    }
    
    echo "<h2>Step 5: Test Passenger-Driver Matching</h2>";
    
    // Test the booking query logic
    $test_user_id = $conn->query("SELECT user_id FROM bookings WHERE assigned_vehicle IS NOT NULL LIMIT 1")->fetch_assoc()['user_id'] ?? null;
    
    if ($test_user_id) {
        $booking_stmt = $conn->prepare("
            SELECT b.*, v.driver_phone, v.driver_name, v.number_plate, v.type as vehicle_type, v.color
            FROM bookings b
            JOIN vehicles v ON b.assigned_vehicle = v.number_plate
            WHERE b.user_id = ?
            AND b.assigned_vehicle IS NOT NULL
            AND b.assigned_vehicle != ''
            ORDER BY b.created_at DESC
            LIMIT 1
        ");
        $booking_stmt->bind_param('i', $test_user_id);
        $booking_stmt->execute();
        $booking_result = $booking_stmt->get_result();
        $booking = $booking_result->fetch_assoc();
        
        if ($booking) {
            echo "<div class='success'>✅ Found passenger booking with assigned vehicle:</div>";
            echo "<ul>";
            echo "<li>Passenger: {$booking['fullname']} (User ID: {$test_user_id})</li>";
            echo "<li>Vehicle: {$booking['assigned_vehicle']}</li>";
            echo "<li>Driver: {$booking['driver_name']} ({$booking['driver_phone']})</li>";
            echo "<li>Status: {$booking['status']}</li>";
            echo "</ul>";
            
            // Check if this driver has shared location
            if (isset($test_driver) && $test_driver['driver_phone'] === $booking['driver_phone']) {
                echo "<div class='success'>🎯 PERFECT MATCH! The test driver matches the passenger's assigned driver</div>";
            } else {
                echo "<div class='warning'>⚠️ Test driver doesn't match passenger's driver. This is normal for separate test data.</div>";
            }
        } else {
            echo "<div class='error'>❌ No active booking found for user ID: $test_user_id</div>";
        }
    } else {
        echo "<div class='error'>❌ No user with assigned vehicle found</div>";
    }
    
    echo "<h2>Step 6: Manual Testing Instructions</h2>";
    
    echo "<div class='info'>";
    echo "<h3>🧑‍💻 Complete Testing Flow:</h3>";
    echo "<ol>";
    echo "<li><strong>Login as Driver:</strong>";
    if (isset($test_driver)) {
        echo " <a href='Driver/index.php'>Driver Dashboard</a> (use phone: {$test_driver['driver_phone']})";
    } else {
        echo " <a href='Driver/index.php'>Driver Dashboard</a>";
    }
    echo "</li>";
    echo "<li><strong>Share Location:</strong> Click 'Share Live Location' card and paste a Google Maps link</li>";
    echo "<li><strong>Login as Passenger:</strong>";
    if (isset($test_user_id)) {
        echo " Use user ID $test_user_id or create a booking";
    } else {
        echo " Create a booking and get it assigned to a vehicle";
    }
    echo "</li>";
    echo "<li><strong>Track Driver:</strong> Go to <a href='profile.html'>Passenger Profile</a> → Click 'Track My Ride'</li>";
    echo "<li><strong>Expected Result:</strong> Passenger should see the Google Maps link button</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h2>Step 7: Quick Links for Testing</h2>";
    
    echo "<div class='info'>";
    echo "<p><strong>🔗 Testing Links:</strong></p>";
    echo "<ul>";
    echo "<li><a href='Driver/index.php' target='_blank'>Driver Dashboard</a></li>";
    echo "<li><a href='login.php' target='_blank'>Login Page</a></li>";
    echo "<li><a href='profile.html' target='_blank'>Passenger Profile</a></li>";
    echo "<li><a href='track_my_driver.php' target='_blank'>Track My Driver (Direct)</a></li>";
    if (isset($test_user_id)) {
        echo "<li><a href='track_my_driver.php?test_user_id=$test_user_id' target='_blank'>Track Driver for User $test_user_id</a></li>";
    }
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}
?>