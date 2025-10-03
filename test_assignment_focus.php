<?php
require_once 'db.php';

echo "<h2>🧪 Test Google Maps Sharing with Driver 0736225373</h2>";

// Simulate the API call that would happen from the frontend
$driver_phone = '0736225373';
$google_maps_link = 'https://maps.app.goo.gl/TestAssignmentToday';

echo "<h3>1. Driver and Vehicle Check</h3>";
$driver_stmt = $conn->prepare("SELECT id, name FROM drivers WHERE driver_phone = ?");
$driver_stmt->bind_param("s", $driver_phone);
$driver_stmt->execute();
$driver_result = $driver_stmt->get_result();
$driver = $driver_result->fetch_assoc();

if ($driver) {
    echo "✅ Driver found: {$driver['name']} (ID: {$driver['id']})<br>";
} else {
    echo "❌ Driver not found<br>";
    exit;
}

// Get vehicles
$vehicle_stmt = $conn->prepare("SELECT number_plate FROM vehicles WHERE driver_phone = ?");
$vehicle_stmt->bind_param("s", $driver_phone);
$vehicle_stmt->execute();
$vehicle_result = $vehicle_stmt->get_result();
$vehicles = [];
while ($row = $vehicle_result->fetch_assoc()) {
    $vehicles[] = $row['number_plate'];
}

echo "🚗 Vehicles: " . implode(', ', $vehicles) . "<br>";

echo "<h3>2. Check TODAY'S ASSIGNMENTS (created_at = today)</h3>";
if (!empty($vehicles)) {
    $placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
    $stmt = $conn->prepare("
        SELECT DISTINCT 
            b.booking_id,
            b.user_id, 
            b.fullname as passenger_name, 
            b.phone as passenger_phone,
            b.travel_date,
            b.created_at,
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

    if ($result->num_rows > 0) {
        echo "✅ Found {$result->num_rows} passenger(s) assigned today:<br>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Booking ID</th><th>User ID</th><th>Passenger Name</th><th>Phone</th><th>Vehicle</th><th>Travel Date</th><th>Assigned Date</th></tr>";
        
        while ($passenger = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$passenger['booking_id']}</td>";
            echo "<td>{$passenger['user_id']}</td>";
            echo "<td>{$passenger['passenger_name']}</td>";
            echo "<td>{$passenger['passenger_phone']}</td>";
            echo "<td>{$passenger['number_plate']}</td>";
            echo "<td>{$passenger['travel_date']}</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($passenger['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ No passengers assigned today<br>";
    }
}

echo "<h3>3. Simulate Google Maps Link Sharing</h3>";

// Store the Google Maps link
$store_stmt = $conn->prepare("
    INSERT INTO driver_locations (driver_id, google_maps_link, status, last_updated) 
    VALUES (?, ?, 'sharing_gmaps', NOW()) 
    ON DUPLICATE KEY UPDATE 
        google_maps_link = VALUES(google_maps_link), 
        status = 'sharing_gmaps', 
        last_updated = NOW()
");

$store_stmt->bind_param('is', $driver['id'], $google_maps_link);
if ($store_stmt->execute()) {
    echo "✅ Google Maps link stored successfully<br>";
} else {
    echo "❌ Failed to store Google Maps link: " . $store_stmt->error . "<br>";
}

echo "<h3>4. Test Passenger Notification</h3>";

// Get today's assignments again for notification
if (!empty($vehicles)) {
    $stmt->execute();
    $result = $stmt->get_result();
    
    $passengers_notified = 0;
    
    while ($passenger = $result->fetch_assoc()) {
        // Insert notification in database
        $notification_sql = "
            INSERT INTO notifications (user_id, title, message, type, created_at) 
            VALUES (?, ?, ?, 'location_sharing', NOW())
        ";
        $notif_stmt = $conn->prepare($notification_sql);
        
        $title = 'Driver Live Location Available';
        $message = "Your driver has shared their live location via Google Maps. Vehicle: {$passenger['vehicle_type']} ({$passenger['number_plate']}). Click 'Track My Ride' in your profile to view.";
        
        $notif_stmt->bind_param('iss', $passenger['user_id'], $title, $message);
        if ($notif_stmt->execute()) {
            $passengers_notified++;
            echo "✅ Notification sent to {$passenger['passenger_name']} (User ID: {$passenger['user_id']})<br>";
        } else {
            echo "❌ Failed to notify {$passenger['passenger_name']}: " . $notif_stmt->error . "<br>";
        }
    }
    
    echo "<br><strong>📊 Summary: $passengers_notified passenger(s) notified</strong><br>";
} else {
    echo "❌ No vehicles to process<br>";
}

echo "<h3>5. Test Driver Dashboard Response</h3>";

// Simulate what the frontend would receive
$response = [
    'success' => true,
    'message' => 'Google Maps location shared successfully',
    'passengers_notified' => $passengers_notified ?? 0,
    'google_maps_link' => $google_maps_link
];

echo "<strong>API Response:</strong><br>";
echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT) . "</pre>";

echo "<h3>🎯 Test Complete!</h3>";
echo "<p>The system should now correctly identify passengers assigned TODAY regardless of their travel date.</p>";

?>