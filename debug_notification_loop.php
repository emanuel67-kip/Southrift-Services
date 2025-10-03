<?php
require_once 'db.php';

echo "<h1>🎯 Debug: Notification Loop Issue</h1>";

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { background: #e7f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
.warning { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
h2, h3 { color: #6A0DAD; }
code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
</style>";

// Test with the exact same data
$driver_phone = "0736225373";
$test_google_maps_link = "https://maps.app.goo.gl/test" . time();

echo "<div class='info'>Testing with driver: <code>$driver_phone</code></div>";
echo "<div class='info'>Test Google Maps link: <code>$test_google_maps_link</code></div>";

try {
    // Step 1: Get vehicles (we know this works)
    $vehicles = [];
    $vehicle_stmt = $conn->prepare("SELECT number_plate FROM vehicles WHERE driver_phone = ?");
    $vehicle_stmt->bind_param("s", $driver_phone);
    $vehicle_stmt->execute();
    $vehicle_result = $vehicle_stmt->get_result();
    while ($row = $vehicle_result->fetch_assoc()) {
        $vehicles[] = $row['number_plate'];
    }
    
    echo "<div class='success'>✅ Found vehicles: " . implode(', ', $vehicles) . "</div>";
    
    // Step 2: Get passengers (we know this works)
    $placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
    $stmt = $conn->prepare("
        SELECT DISTINCT 
            b.user_id, 
            b.fullname as passenger_name, 
            b.phone as passenger_phone,
            v.number_plate,
            v.type as vehicle_type,
            b.booking_id
        FROM bookings b
        JOIN vehicles v ON b.assigned_vehicle = v.number_plate
        WHERE b.assigned_vehicle IN ($placeholders)
        AND b.phone IS NOT NULL
        AND b.phone != ''
        AND b.user_id IS NOT NULL
        AND DATE(b.travel_date) = CURDATE()
    ");

    $types = str_repeat('s', count($vehicles));
    $stmt->bind_param($types, ...$vehicles);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<div class='success'>✅ Found " . $result->num_rows . " passengers</div>";
    
    // Step 3: Test the notification loop
    $passengers_notified = 0;
    
    while ($passenger = $result->fetch_assoc()) {
        echo "<h3>Processing Passenger: {$passenger['passenger_name']}</h3>";
        echo "<div class='info'>User ID: {$passenger['user_id']}, Booking ID: {$passenger['booking_id']}</div>";
        
        // Test 1: Check if google_maps_link column exists in bookings
        $columns_check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'google_maps_link'");
        if ($columns_check->num_rows == 0) {
            echo "<div class='error'>❌ PROBLEM FOUND: 'google_maps_link' column doesn't exist in bookings table!</div>";
            echo "<div class='warning'>You need to run: <a href='add_google_maps_column.php'>add_google_maps_column.php</a></div>";
            continue;
        } else {
            echo "<div class='success'>✅ google_maps_link column exists</div>";
        }
        
        // Test 2: Try to update the booking
        $update_booking_sql = "
            UPDATE bookings 
            SET google_maps_link = ?, shared_location_updated = NOW() 
            WHERE user_id = ? AND booking_id = ?
        ";
        $update_stmt = $conn->prepare($update_booking_sql);
        
        if (!$update_stmt) {
            echo "<div class='error'>❌ Failed to prepare booking update: " . $conn->error . "</div>";
            continue;
        }
        
        $update_stmt->bind_param('sii', $test_google_maps_link, $passenger['user_id'], $passenger['booking_id']);
        
        if ($update_stmt->execute()) {
            echo "<div class='success'>✅ Booking updated successfully</div>";
            echo "<div class='info'>Affected rows: " . $update_stmt->affected_rows . "</div>";
        } else {
            echo "<div class='error'>❌ Failed to update booking: " . $update_stmt->error . "</div>";
            continue;
        }
        
        // Test 3: Check if notifications table exists
        $notifications_check = $conn->query("SHOW TABLES LIKE 'notifications'");
        if ($notifications_check->num_rows == 0) {
            echo "<div class='error'>❌ PROBLEM FOUND: 'notifications' table doesn't exist!</div>";
            echo "<div class='warning'>Creating notifications table...</div>";
            
            $create_notifications = "
                CREATE TABLE notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    type VARCHAR(50) DEFAULT 'info',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    read_at TIMESTAMP NULL
                )
            ";
            
            if ($conn->query($create_notifications)) {
                echo "<div class='success'>✅ Created notifications table</div>";
            } else {
                echo "<div class='error'>❌ Failed to create notifications table: " . $conn->error . "</div>";
                continue;
            }
        } else {
            echo "<div class='success'>✅ notifications table exists</div>";
        }
        
        // Test 4: Try to insert notification
        $notification_sql = "
            INSERT INTO notifications (user_id, title, message, type, created_at) 
            VALUES (?, ?, ?, 'location_sharing', NOW())
        ";
        $notif_stmt = $conn->prepare($notification_sql);
        
        if (!$notif_stmt) {
            echo "<div class='error'>❌ Failed to prepare notification insert: " . $conn->error . "</div>";
            continue;
        }
        
        $title = 'Driver Live Location Available';
        $message = "Your driver has shared their live location via Google Maps. Vehicle: {$passenger['vehicle_type']} ({$passenger['number_plate']}). Click 'Track My Ride' to access the live location.";
        
        $notif_stmt->bind_param('iss', $passenger['user_id'], $title, $message);
        
        if ($notif_stmt->execute()) {
            $passengers_notified++;
            echo "<div class='success'>✅ Notification sent successfully!</div>";
            echo "<div class='info'>Notification ID: " . $conn->insert_id . "</div>";
        } else {
            echo "<div class='error'>❌ Failed to send notification: " . $notif_stmt->error . "</div>";
        }
    }
    
    echo "<h2>🎯 Final Result</h2>";
    echo "<div class='success'>Passengers notified: <strong>$passengers_notified</strong></div>";
    
    if ($passengers_notified == 0) {
        echo "<div class='error'>❌ Still getting 0 notifications. Check the error messages above for the specific issue.</div>";
    } else {
        echo "<div class='success'>✅ Notifications working! The issue was likely missing database tables/columns.</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Exception: " . $e->getMessage() . "</div>";
}
?>