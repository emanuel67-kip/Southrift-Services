<?php
require_once 'db.php';

echo "<h2>Update Sample Booking for Testing</h2>";

try {
    // Update the first booking to today's date
    $update_sql = "UPDATE bookings SET travel_date = CURDATE() WHERE booking_id = 1";
    if ($conn->query($update_sql)) {
        echo "✅ Successfully updated booking ID 1 to today's date<br>";
    } else {
        echo "❌ Error updating booking: " . $conn->error . "<br>";
    }

    // Check the updated booking
    $check_sql = "
        SELECT b.*, v.driver_name, v.driver_phone, v.number_plate, v.type 
        FROM bookings b 
        JOIN vehicles v ON b.assigned_vehicle = v.number_plate 
        WHERE booking_id = 1
    ";
    $result = $conn->query($check_sql);
    
    if ($result && $row = $result->fetch_assoc()) {
        echo "<h3>Updated Booking Details:</h3>";
        echo "Booking ID: " . $row['booking_id'] . "<br>";
        echo "User ID: " . $row['user_id'] . "<br>";
        echo "Passenger: " . $row['fullname'] . "<br>";
        echo "Travel Date: " . $row['travel_date'] . "<br>";
        echo "Vehicle: " . $row['number_plate'] . " (" . $row['type'] . ")<br>";
        echo "Driver: " . $row['driver_name'] . " (" . $row['driver_phone'] . ")<br>";
        
        echo "<h3>📋 Next Steps to Test:</h3>";
        echo "<ol>";
        echo "<li><strong>Login as Driver:</strong> Use phone number: <code>" . $row['driver_phone'] . "</code></li>";
        echo "<li><strong>Start Location Sharing:</strong> Go to driver dashboard and click 'Share Live Location'</li>";
        echo "<li><strong>Login as Passenger:</strong> Use the account with user_id: <code>" . $row['user_id'] . "</code></li>";
        echo "<li><strong>Track Ride:</strong> Click 'Track My Ride' to see live location</li>";
        echo "</ol>";
        
        echo "<p><strong>Driver Dashboard:</strong> <a href='Driver/index.php'>Click here</a></p>";
        echo "<p><strong>Passenger Profile:</strong> <a href='profile.html'>Click here</a></p>";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #6A0DAD; }
h3 { color: #333; margin-top: 20px; }
code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
ol { margin: 10px 0; }
li { margin: 5px 0; }
a { color: #6A0DAD; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>