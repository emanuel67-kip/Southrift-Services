<?php
require_once 'db.php';

echo "<h2>Testing Track My Ride System</h2>";

// Test with sample user_id from the database
$test_user_id = 3;

echo "<h3>Testing for User ID: $test_user_id</h3>";

try {
    // Test the exact query from track_my_driver.php
    echo "<h4>1. Testing Current Day Bookings:</h4>";
    $booking_stmt = $conn->prepare("
        SELECT b.*, v.driver_phone, v.driver_name, v.number_plate, v.type as vehicle_type, v.color
        FROM bookings b
        JOIN vehicles v ON b.assigned_vehicle = v.number_plate
        WHERE b.user_id = ?
        AND DATE(b.travel_date) = CURDATE()
        ORDER BY b.created_at DESC
        LIMIT 1
    ");
    $booking_stmt->bind_param('i', $test_user_id);
    $booking_stmt->execute();
    $booking_result = $booking_stmt->get_result();
    $booking = $booking_result->fetch_assoc();

    if ($booking) {
        echo "✅ Found booking for today:<br>";
        echo "Booking ID: " . $booking['booking_id'] . "<br>";
        echo "Vehicle: " . $booking['number_plate'] . " (" . $booking['vehicle_type'] . ")<br>";
        echo "Driver: " . $booking['driver_name'] . " (" . $booking['driver_phone'] . ")<br>";
        echo "Travel Date: " . $booking['travel_date'] . "<br>";
    } else {
        echo "❌ No bookings found for today (" . date('Y-m-d') . ")<br>";
    }

    // Test with recent bookings regardless of date
    echo "<h4>2. Testing Recent Bookings (any date):</h4>";
    $recent_stmt = $conn->prepare("
        SELECT b.*, v.driver_phone, v.driver_name, v.number_plate, v.type as vehicle_type, v.color
        FROM bookings b
        JOIN vehicles v ON b.assigned_vehicle = v.number_plate
        WHERE b.user_id = ?
        ORDER BY b.created_at DESC
        LIMIT 1
    ");
    $recent_stmt->bind_param('i', $test_user_id);
    $recent_stmt->execute();
    $recent_result = $recent_stmt->get_result();
    $recent_booking = $recent_result->fetch_assoc();

    if ($recent_booking) {
        echo "✅ Found most recent booking:<br>";
        echo "Booking ID: " . $recent_booking['booking_id'] . "<br>";
        echo "Vehicle: " . $recent_booking['number_plate'] . " (" . $recent_booking['vehicle_type'] . ")<br>";
        echo "Driver: " . $recent_booking['driver_name'] . " (" . $recent_booking['driver_phone'] . ")<br>";
        echo "Travel Date: " . $recent_booking['travel_date'] . "<br>";
        echo "Departure: " . $recent_booking['departure_time'] . "<br>";
        
        // Test driver location lookup
        echo "<h4>3. Testing Driver Location Lookup:</h4>";
        $location_stmt = $conn->prepare("
            SELECT dl.latitude, dl.longitude, dl.status, dl.last_updated, dl.accuracy, dl.speed,
                   d.name as driver_name, d.driver_phone, d.phone
            FROM driver_locations dl
            JOIN drivers d ON dl.driver_id = d.id
            WHERE (d.driver_phone = ? OR d.phone = ?)
            AND dl.status = 'active'
            AND dl.last_updated >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ORDER BY dl.last_updated DESC
            LIMIT 1
        ");
        $location_stmt->bind_param('ss', $recent_booking['driver_phone'], $recent_booking['driver_phone']);
        $location_stmt->execute();
        $location_result = $location_stmt->get_result();
        $driver_location = $location_result->fetch_assoc();
        
        if ($driver_location) {
            echo "✅ Driver is sharing location:<br>";
            echo "Location: " . $driver_location['latitude'] . ", " . $driver_location['longitude'] . "<br>";
            echo "Last Updated: " . $driver_location['last_updated'] . "<br>";
            echo "Status: " . $driver_location['status'] . "<br>";
        } else {
            echo "❌ Driver is not currently sharing location<br>";
            echo "This is normal - driver needs to start sharing from their dashboard<br>";
        }
    }

    echo "<h4>4. Testing Solution:</h4>";
    echo "<p><strong>To test the complete system:</strong></p>";
    echo "<ol>";
    echo "<li>📅 <strong>Update sample booking dates</strong> to today: <code>UPDATE bookings SET travel_date = CURDATE() WHERE booking_id = 1</code></li>";
    echo "<li>🚗 <strong>Login as driver</strong> with phone: " . $recent_booking['driver_phone'] . "</li>";
    echo "<li>📍 <strong>Start location sharing</strong> from driver dashboard</li>";
    echo "<li>👤 <strong>Login as passenger</strong> (user_id: $test_user_id) and click 'Track My Ride'</li>";
    echo "</ol>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #6A0DAD; }
h3 { color: #333; }
h4 { color: #666; margin-top: 20px; }
code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
ol { margin: 10px 0; }
li { margin: 5px 0; }
</style>