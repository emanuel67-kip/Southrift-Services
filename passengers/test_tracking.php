<?php
// Test script to check tracking functionality
session_name('southrift_admin');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<p>User not logged in. Please <a href='../login.html'>login</a> first.</p>";
    exit;
}

require_once '../db.php';

$user_id = $_SESSION['user_id'];

echo "<h2>Tracking Test for User ID: $user_id</h2>";

// Get all user's bookings for today with assigned vehicles
$booking_stmt = $conn->prepare("
    SELECT b.*, v.driver_phone, v.driver_name, v.number_plate, v.type as vehicle_type, v.color
    FROM bookings b
    JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE b.user_id = ?
    AND b.assigned_vehicle IS NOT NULL
    AND b.assigned_vehicle != ''
    AND DATE(b.travel_date) = CURDATE()
    ORDER BY b.created_at DESC
");
$booking_stmt->bind_param('i', $user_id);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();

$bookings = [];
while ($row = $booking_result->fetch_assoc()) {
    $bookings[] = $row;
}

echo "<h3>Today's Bookings with Assigned Vehicles:</h3>";
if (empty($bookings)) {
    echo "<p>No bookings found for today with assigned vehicles.</p>";
} else {
    echo "<ul>";
    foreach ($bookings as $booking) {
        echo "<li>Booking ID: " . $booking['booking_id'] . " - Route: " . $booking['route'] . " - Vehicle: " . $booking['number_plate'] . "</li>";
        
        // Check for Google Maps link
        if (!empty($booking['google_maps_link'])) {
            echo "<ul><li>Google Maps Link: " . $booking['google_maps_link'] . "</li></ul>";
        }
        
        // Check for driver location
        $location_stmt = $conn->prepare("
            SELECT dl.latitude, dl.longitude, dl.status, dl.last_updated, dl.accuracy, dl.speed,
                   d.name as driver_name, d.driver_phone
            FROM driver_locations dl
            JOIN drivers d ON dl.driver_id = d.id
            WHERE d.driver_phone = ?
            AND dl.status = 'active'
            AND dl.last_updated >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ORDER BY dl.last_updated DESC
            LIMIT 1
        ");
        $location_stmt->bind_param('s', $booking['driver_phone']);
        $location_stmt->execute();
        $location_result = $location_stmt->get_result();
        $driver_location = $location_result->fetch_assoc();
        
        if ($driver_location) {
            echo "<ul><li>Driver Location: " . $driver_location['latitude'] . ", " . $driver_location['longitude'] . " (Updated: " . $driver_location['last_updated'] . ")</li></ul>";
        } else {
            echo "<ul><li>No recent driver location found</li></ul>";
        }
    }
    echo "</ul>";
}

$conn->close();
?>