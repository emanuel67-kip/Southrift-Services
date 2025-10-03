<?php
require 'db.php';

echo "<h2>Testing Passenger Assigned Vehicle Functionality</h2>";
echo "<p>This test checks if passengers can see assigned vehicle information when clicking 'Check Ride'</p>";

// Check if assigned_vehicle column exists
echo "<h3>1. Database Structure Check:</h3>";
$columns = $conn->query("SHOW COLUMNS FROM bookings LIKE 'assigned_vehicle'");
if ($columns && $columns->num_rows > 0) {
    echo "<p style='color: green;'>✅ assigned_vehicle column exists in bookings table</p>";
} else {
    echo "<p style='color: red;'>❌ assigned_vehicle column NOT found in bookings table</p>";
    echo "<p><strong>Solution:</strong> Run this SQL: <code>ALTER TABLE bookings ADD COLUMN assigned_vehicle VARCHAR(20) NULL;</code></p>";
}

// Check for bookings with assigned vehicles
echo "<h3>2. Current Bookings with Assigned Vehicles:</h3>";
$result = $conn->query("SELECT booking_id, fullname, phone, assigned_vehicle FROM bookings WHERE assigned_vehicle IS NOT NULL ORDER BY booking_id DESC LIMIT 5");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Booking ID</th><th>Passenger Name</th><th>Phone</th><th>Assigned Vehicle</th><th>Test Check Ride</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['booking_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['fullname']) . "</td>";
        echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
        echo "<td>" . htmlspecialchars($row['assigned_vehicle']) . "</td>";
        echo "<td>";
        echo "<a href='check_ride.php?booking_id=" . $row['booking_id'] . "' target='_blank' style='background: #6A0DAD; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;'>Check Ride Page</a>";
        echo " | ";
        echo "<a href='assigned_vehicle.php?booking_id=" . $row['booking_id'] . "' target='_blank' style='background: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;'>API Test</a>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No bookings with assigned vehicles found.</p>";
    
    // Let's create a test assignment
    echo "<h4>Creating test assignment...</h4>";
    $testBooking = $conn->query("SELECT booking_id FROM bookings ORDER BY booking_id DESC LIMIT 1");
    $testVehicle = $conn->query("SELECT number_plate FROM vehicles LIMIT 1");
    
    if ($testBooking && $testBooking->num_rows > 0 && $testVehicle && $testVehicle->num_rows > 0) {
        $booking = $testBooking->fetch_assoc();
        $vehicle = $testVehicle->fetch_assoc();
        
        $updateStmt = $conn->prepare("UPDATE bookings SET assigned_vehicle = ? WHERE booking_id = ?");
        $updateStmt->bind_param('si', $vehicle['number_plate'], $booking['booking_id']);
        
        if ($updateStmt->execute()) {
            echo "<p style='color: green;'>✅ Test assignment created: Booking #{$booking['booking_id']} assigned to {$vehicle['number_plate']}</p>";
            echo "<p><a href='check_ride.php?booking_id={$booking['booking_id']}' target='_blank' style='background: #6A0DAD; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🚗 Test Check Ride Page</a></p>";
            echo "<p><a href='assigned_vehicle.php?booking_id={$booking['booking_id']}' target='_blank' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🔍 Test API Response</a></p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create test assignment</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ No bookings or vehicles found to create test assignment</p>";
    }
}

// Check vehicles table
echo "<h3>3. Available Vehicles for Assignment:</h3>";
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
    echo "<p style='color: red;'>❌ No vehicles found</p>";
}

echo "<h3>4. Test Instructions:</h3>";
echo "<ol>";
echo "<li><strong>Admin assigns vehicle:</strong> Go to <a href='Admin/today_bookings.php' target='_blank'>Admin Today's Bookings</a> and assign vehicles to bookings</li>";
echo "<li><strong>Passenger checks ride:</strong> Go to <a href='profile.html' target='_blank'>Passenger Profile</a> and click 'Check Ride' button</li>";
echo "<li><strong>Expected result:</strong> Modal should show assigned vehicle details (number plate, type, color, driver info)</li>";
echo "</ol>";

echo "<h3>5. Direct Test Links:</h3>";
echo "<p><a href='profile.html' target='_blank' style='background: #17a2b8; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>👤 Open Passenger Profile</a></p>";
echo "<p><a href='Admin/today_bookings.php' target='_blank' style='background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>⚙️ Admin Dashboard</a></p>";

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
table { border-collapse: collapse; width: 100%; max-width: 800px; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; font-weight: bold; }
h2 { color: #6A0DAD; }
h3 { color: #333; margin-top: 25px; }
code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
</style>