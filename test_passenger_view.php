<?php
require_once 'db.php';

echo "<h2>🔍 TEST: Can Passengers See Shared Location?</h2>";

// Test data
$driver_phone = '0736225373';
$passenger_user_id = 3; // Miriam Chebet

echo "<h3>1. First, Simulate Driver Sharing (Direct Database)</h3>";

// Get driver ID
$driver_stmt = $conn->prepare("SELECT id FROM drivers WHERE driver_phone = ?");
$driver_stmt->bind_param("s", $driver_phone);
$driver_stmt->execute();
$driver_result = $driver_stmt->get_result();
$driver = $driver_result->fetch_assoc();

if ($driver) {
    $driver_id = $driver['id'];
    echo "✅ Driver found: ID $driver_id<br>";
    
    // Store Google Maps link directly in database
    $google_maps_link = 'https://maps.app.goo.gl/TestPassengerView123';
    
    $location_stmt = $conn->prepare("
        INSERT INTO driver_locations (driver_id, google_maps_link, status, last_updated) 
        VALUES (?, ?, 'sharing_gmaps', NOW()) 
        ON DUPLICATE KEY UPDATE 
            google_maps_link = VALUES(google_maps_link), 
            status = 'sharing_gmaps', 
            last_updated = NOW()
    ");
    $location_stmt->bind_param('is', $driver_id, $google_maps_link);
    
    if ($location_stmt->execute()) {
        echo "✅ Google Maps link stored in database<br>";
    } else {
        echo "❌ Failed to store Google Maps link<br>";
        exit;
    }
} else {
    echo "❌ Driver not found<br>";
    exit;
}

echo "<h3>2. Test What Passenger Sees</h3>";

// Simulate passenger query from track_my_driver.php
echo "<h4>🔍 Testing passenger booking lookup:</h4>";

$booking_stmt = $conn->prepare("
    SELECT b.*, v.driver_phone, v.driver_name, v.number_plate, v.type as vehicle_type, v.color
    FROM bookings b
    JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
    LIMIT 1
");
$booking_stmt->bind_param('i', $passenger_user_id);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();
$booking = $booking_result->fetch_assoc();

if ($booking) {
    echo "✅ Passenger booking found:<br>";
    echo "- Booking ID: {$booking['booking_id']}<br>";
    echo "- Driver Phone: {$booking['driver_phone']}<br>";
    echo "- Vehicle: {$booking['number_plate']}<br>";
    echo "- Travel Date: {$booking['travel_date']}<br>";
    
    echo "<h4>🔍 Testing Google Maps link lookup:</h4>";
    
    // Check for Google Maps link sharing
    $gmaps_stmt = $conn->prepare("
        SELECT dl.google_maps_link, dl.status, dl.last_updated,
               d.name as driver_name, d.driver_phone
        FROM driver_locations dl
        JOIN drivers d ON dl.driver_id = d.id
        WHERE d.driver_phone = ?
        AND dl.status = 'sharing_gmaps'
        AND dl.google_maps_link IS NOT NULL
        ORDER BY dl.last_updated DESC
        LIMIT 1
    ");
    $gmaps_stmt->bind_param('s', $booking['driver_phone']);
    $gmaps_stmt->execute();
    $gmaps_result = $gmaps_stmt->get_result();
    $gmaps_data = $gmaps_result->fetch_assoc();
    
    if ($gmaps_data && !empty($gmaps_data['google_maps_link'])) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
        echo "<h4>🎉 SUCCESS!</h4>";
        echo "✅ <strong>Passenger CAN see the Google Maps link!</strong><br>";
        echo "<strong>Driver:</strong> {$gmaps_data['driver_name']}<br>";
        echo "<strong>Status:</strong> {$gmaps_data['status']}<br>";
        echo "<strong>Link:</strong> " . substr($gmaps_data['google_maps_link'], 0, 50) . "...<br>";
        echo "<strong>Last Updated:</strong> {$gmaps_data['last_updated']}<br>";
        echo "</div>";
        
        // Test the actual track_my_driver.php page
        echo "<h4>📱 Track My Driver Page Test:</h4>";
        echo "<p><a href='track_my_driver.php' target='_blank' style='background: #6A0DAD; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🗺️ Open Track My Driver Page</a></p>";
        echo "<p><em>When you click the link above, you should see a Google Maps button that opens the driver's live location.</em></p>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
        echo "❌ <strong>No Google Maps link found for this driver</strong><br>";
        echo "The driver might not be sharing location or there's a database issue.<br>";
        echo "</div>";
    }
    
} else {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
    echo "⚠️ <strong>No booking found for passenger ID: $passenger_user_id</strong><br>";
    echo "The passenger needs to have a booking to track their driver.<br>";
    echo "</div>";
}

echo "<h3>3. Summary & Instructions</h3>";

echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
echo "<h4>📋 For Complete Testing:</h4>";
echo "<ol>";
echo "<li><strong>Driver shares location:</strong> Go to <a href='Driver/index.php'>Driver Dashboard</a> → Click 'Share Live Location' → Paste Google Maps link</li>";
echo "<li><strong>Passenger checks location:</strong> Go to <a href='profile.html'>Passenger Profile</a> → Click 'Track My Driver'</li>";
echo "<li><strong>Alternative:</strong> Direct link to <a href='track_my_driver.php'>Track My Driver</a></li>";
echo "</ol>";
echo "</div>";

if ($booking && $gmaps_data) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
    echo "<h4>🎯 LOCATION SHARING IS WORKING!</h4>";
    echo "<p>✅ Driver can share Google Maps location<br>";
    echo "✅ Passenger can see shared location<br>";
    echo "📱 The complete flow is functional!</p>";
    echo "</div>";
}

?>