<?php
// Test file to verify multiple bookings functionality
require_once 'db.php';

// Test data for John with multiple bookings on the same day
$user_id = 1; // Assuming John's user ID is 1

echo "<h2>Testing Multiple Bookings Functionality</h2>";

// Get all bookings for the user for today
$stmt = $conn->prepare("
    SELECT b.*, v.driver_phone, v.driver_name, v.number_plate, v.type as vehicle_type, v.color
    FROM bookings b
    LEFT JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE b.user_id = ?
    AND DATE(b.travel_date) = CURDATE()
    ORDER BY b.created_at DESC
");

$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<h3>Today's Bookings for User ID: $user_id</h3>";

if ($result->num_rows > 0) {
    echo "<p>Found " . $result->num_rows . " bookings for today:</p>";
    echo "<ul>";
    while ($booking = $result->fetch_assoc()) {
        echo "<li>";
        echo "<strong>Booking ID:</strong> " . $booking['booking_id'] . "<br>";
        echo "<strong>Route:</strong> " . $booking['route'] . "<br>";
        echo "<strong>Vehicle:</strong> " . $booking['number_plate'] . " (" . $booking['vehicle_type'] . ")<br>";
        echo "<strong>Driver:</strong> " . $booking['driver_name'] . " (" . $booking['driver_phone'] . ")<br>";
        echo "<strong>Departure:</strong> " . $booking['departure_time'] . "<br>";
        echo "</li><br>";
    }
    echo "</ul>";
    
    echo "<p><a href='track_my_driver.php'>Test Multiple Bookings Tracking</a></p>";
} else {
    echo "<p>No bookings found for today.</p>";
}

$stmt->close();
$conn->close();
?>