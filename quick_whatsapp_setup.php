<?php
// Quick setup for WhatsApp testing
require_once 'db.php';

echo "<h2>Quick WhatsApp Test Setup</h2>";

try {
    // Step 1: Set a booking to today's date
    $today = date('Y-m-d');
    $update_sql = "UPDATE bookings SET travel_date = ? WHERE booking_id = 1";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param('s', $today);
    $stmt->execute();
    
    echo "<p>✅ Updated booking 1 to today's date: $today</p>";
    
    // Step 2: Ensure the user has a phone number
    $update_user_sql = "UPDATE users SET phone = '0712345678' WHERE id = (SELECT user_id FROM bookings WHERE booking_id = 1)";
    $conn->query($update_user_sql);
    
    echo "<p>✅ Updated user phone number to: 0712345678</p>";
    
    // Step 3: Show the test data
    $test_sql = "
        SELECT b.booking_id, b.user_id, b.fullname, b.travel_date, b.assigned_vehicle,
               v.driver_name, v.driver_phone, v.number_plate,
               u.name as user_name, u.phone as user_phone
        FROM bookings b 
        LEFT JOIN vehicles v ON b.assigned_vehicle = v.number_plate 
        LEFT JOIN users u ON b.user_id = u.id
        WHERE b.booking_id = 1
    ";
    
    $result = $conn->query($test_sql);
    if ($result && $row = $result->fetch_assoc()) {
        echo "<h3>Test Data Ready:</h3>";
        echo "<ul>";
        echo "<li><strong>Booking ID:</strong> " . $row['booking_id'] . "</li>";
        echo "<li><strong>Passenger:</strong> " . $row['fullname'] . " (Phone: " . $row['user_phone'] . ")</li>";
        echo "<li><strong>Travel Date:</strong> " . $row['travel_date'] . "</li>";
        echo "<li><strong>Vehicle:</strong> " . $row['number_plate'] . "</li>";
        echo "<li><strong>Driver:</strong> " . $row['driver_name'] . " (Phone: " . $row['driver_phone'] . ")</li>";
        echo "</ul>";
        
        echo "<h3>Test Instructions:</h3>";
        echo "<ol>";
        echo "<li>Login as driver with phone: <strong>" . $row['driver_phone'] . "</strong></li>";
        echo "<li>Go to driver dashboard</li>";
        echo "<li>Click the green 'Send Location via WhatsApp' button</li>";
        echo "<li>Should find 1 passenger to send location to</li>";
        echo "</ol>";
        
        echo "<p><a href='../Driver/index.php' style='background: #6A0DAD; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Driver Dashboard</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #6A0DAD; }
ul, ol { margin: 10px 0; }
li { margin: 5px 0; }
</style>