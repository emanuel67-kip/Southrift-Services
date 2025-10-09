<?php
// Test script to check assigned vehicles in bookings
require_once 'db.php';

echo "<h2>Assigned Vehicles Test</h2>";

// Check bookings with assigned vehicles
$assigned_result = $conn->query("SELECT booking_id, assigned_vehicle FROM bookings WHERE assigned_vehicle IS NOT NULL AND assigned_vehicle != '' LIMIT 5");
if ($assigned_result && $assigned_result->num_rows > 0) {
    echo "<p>Found bookings with assigned vehicles:</p>";
    echo "<ul>";
    while ($row = $assigned_result->fetch_assoc()) {
        echo "<li>Booking ID: " . $row['booking_id'] . " - Assigned Vehicle: " . $row['assigned_vehicle'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No bookings with assigned vehicles found.</p>";
}

// Check if there are any bookings at all
$total_result = $conn->query("SELECT COUNT(*) as count FROM bookings");
$count = $total_result->fetch_assoc();
echo "<p>Total bookings: " . $count['count'] . "</p>";

$conn->close();
?>