<?php
require_once 'db.php';

echo "<h2>Fix Existing Booking Route</h2>";

// Show current booking details
echo "<h3>Current Booking Details (ID: 35)</h3>";
$stmt = $conn->prepare("SELECT booking_id, fullname, phone, route, boarding_point FROM bookings WHERE booking_id = ?");
$stmt->bind_param("i", 35);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    echo "<p><strong>Before Update:</strong></p>";
    echo "<ul>";
    echo "<li>Passenger: " . htmlspecialchars($row['fullname']) . "</li>";
    echo "<li>Phone: " . htmlspecialchars($row['phone']) . "</li>";
    echo "<li>Route: <span style='color: red;'>" . htmlspecialchars($row['route']) . "</span></li>";
    echo "<li>Boarding Point: " . htmlspecialchars($row['boarding_point']) . "</li>";
    echo "</ul>";
    
    // Update the route to "litein-nairobi" since that's what it should be
    echo "<h3>Updating Route to 'litein-nairobi'</h3>";
    $updateStmt = $conn->prepare("UPDATE bookings SET route = 'litein-nairobi' WHERE booking_id = ?");
    $updateStmt->bind_param("i", 35);
    
    if ($updateStmt->execute()) {
        echo "<p style='color: green;'>✅ Successfully updated route to 'litein-nairobi'.</p>";
        
        // Show updated details
        echo "<h3>After Update:</h3>";
        $verifyStmt = $conn->prepare("SELECT booking_id, fullname, phone, route, boarding_point FROM bookings WHERE booking_id = ?");
        $verifyStmt->bind_param("i", 35);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        
        if ($verifyResult && $updatedRow = $verifyResult->fetch_assoc()) {
            echo "<ul>";
            echo "<li>Passenger: " . htmlspecialchars($updatedRow['fullname']) . "</li>";
            echo "<li>Phone: " . htmlspecialchars($updatedRow['phone']) . "</li>";
            echo "<li>Route: <span style='color: green;'>" . htmlspecialchars($updatedRow['route']) . "</span></li>";
            echo "<li>Boarding Point: " . htmlspecialchars($updatedRow['boarding_point']) . "</li>";
            echo "</ul>";
            
            // Extract starting station from route
            $routeParts = explode('-', $updatedRow['route']);
            $startingStation = ucfirst($routeParts[0]);
            echo "<p><strong>Starting Station (from route):</strong> <span style='color: blue;'>" . htmlspecialchars($startingStation) . "</span></p>";
        }
        $verifyStmt->close();
    } else {
        echo "<p style='color: red;'>❌ Error updating route: " . $conn->error . "</p>";
    }
    $updateStmt->close();
} else {
    echo "<p>Booking ID 35 not found.</p>";
}

$stmt->close();

// Test the new filtering logic
echo "<h3>Testing New Filtering Logic</h3>";

// Test for Litein admin
echo "<h4>What the Litein Admin Would See:</h4>";
$testStmt = $conn->prepare("SELECT booking_id, fullname, route FROM bookings WHERE DATE(created_at) = CURDATE() AND SUBSTRING_INDEX(route, '-', 1) = ? ORDER BY booking_id DESC");
$testStmt->bind_param("s", "litein");
$testStmt->execute();
$testResult = $testStmt->get_result();

if ($testResult && $testResult->num_rows > 0) {
    echo "<ul>";
    while ($booking = $testResult->fetch_assoc()) {
        echo "<li>Booking ID " . $booking['booking_id'] . ": " . htmlspecialchars($booking['fullname']) . " - Route: " . htmlspecialchars($booking['route']) . "</li>";
    }
    echo "</ul>";
    echo "<p style='color: green;'>✅ The Litein admin should now see this booking!</p>";
} else {
    echo "<p style='color: red;'>❌ The Litein admin would not see any bookings.</p>";
}
$testStmt->close();

$conn->close();

echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>Log in as the Litein admin (adminlitein@gmail.com)</li>";
echo "<li>Go to 'Today's Bookings' page</li>";
echo "<li>You should now see the booking with ID 35</li>";
echo "<li>The table will show 'Starting Station' as 'Litein' instead of 'Boarding Point'</li>";
echo "</ol>";
?>