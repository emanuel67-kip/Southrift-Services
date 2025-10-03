<?php
require 'db.php';

echo "<h2>Testing Passenger Check Ride Functionality</h2>";

// Test 1: Check if we can find bookings with assigned vehicles
echo "<h3>1. Checking for bookings with assigned vehicles:</h3>";
$result = $conn->query("SELECT booking_id, fullname, phone, assigned_vehicle FROM bookings WHERE assigned_vehicle IS NOT NULL ORDER BY booking_id DESC LIMIT 5");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Booking ID</th><th>Name</th><th>Phone</th><th>Assigned Vehicle</th><th>Test Links</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['booking_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['fullname']) . "</td>";
        echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
        echo "<td>" . htmlspecialchars($row['assigned_vehicle'] ?: '—') . "</td>";
        echo "<td>";
        echo "<a href='check_ride.php?booking_id=" . $row['booking_id'] . "' target='_blank'>Check Ride Page</a> | ";
        echo "<a href='assigned_vehicle.php?booking_id=" . $row['booking_id'] . "' target='_blank'>JSON API</a>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No bookings with assigned vehicles found.</p>";
    
    // Let's manually assign a vehicle to test
    echo "<h4>Creating test assignment:</h4>";
    $testBooking = $conn->query("SELECT booking_id FROM bookings ORDER BY booking_id DESC LIMIT 1");
    $testVehicle = $conn->query("SELECT number_plate FROM vehicles LIMIT 1");
    
    if ($testBooking && $testBooking->num_rows > 0 && $testVehicle && $testVehicle->num_rows > 0) {
        $booking = $testBooking->fetch_assoc();
        $vehicle = $testVehicle->fetch_assoc();
        
        $updateStmt = $conn->prepare("UPDATE bookings SET assigned_vehicle = ? WHERE booking_id = ?");
        $updateStmt->bind_param('si', $vehicle['number_plate'], $booking['booking_id']);
        
        if ($updateStmt->execute()) {
            echo "<p style='color: green;'>✅ Test assignment created: Booking #{$booking['booking_id']} assigned to {$vehicle['number_plate']}</p>";
            echo "<p><a href='check_ride.php?booking_id={$booking['booking_id']}' target='_blank'>Test Check Ride Page</a></p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create test assignment</p>";
        }
    }
}

// Test 2: Check vehicles table structure
echo "<h3>2. Available vehicles for assignment:</h3>";
$vehiclesResult = $conn->query("SELECT number_plate, type, color, driver_name FROM vehicles LIMIT 3");
if ($vehiclesResult && $vehiclesResult->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Number Plate</th><th>Type</th><th>Color</th><th>Driver</th></tr>";
    
    while ($row = $vehiclesResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['number_plate']) . "</td>";
        echo "<td>" . htmlspecialchars($row['type'] ?: '—') . "</td>";
        echo "<td>" . htmlspecialchars($row['color'] ?: '—') . "</td>";
        echo "<td>" . htmlspecialchars($row['driver_name'] ?: '—') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No vehicles found</p>";
}

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; width: 100%; max-width: 800px; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; font-weight: bold; }
a { color: #6A0DAD; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>