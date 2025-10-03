<?php
require_once 'db.php';

// Start session to get driver info
if (session_status() === PHP_SESSION_NONE) {
    session_name('southrift_admin');
    session_start();
}

$driver_phone = $_SESSION['phone'] ?? '0736225373';
$driver_vehicle = 'KDR 645 J'; // From the error message

echo "<h2>📋 Assign Booking to Driver Vehicle</h2>";
echo "<p><strong>Driver Phone:</strong> $driver_phone</p>";
echo "<p><strong>Driver Vehicle:</strong> $driver_vehicle</p>";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    if ($booking_id > 0) {
        $update_stmt = $conn->prepare("UPDATE bookings SET assigned_vehicle = ? WHERE booking_id = ? OR id = ?");
        $update_stmt->bind_param('sii', $driver_vehicle, $booking_id, $booking_id);
        
        if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
            echo "✅ <strong>SUCCESS!</strong> Booking #$booking_id has been assigned to vehicle $driver_vehicle<br>";
            echo "🎉 Location sharing should now work!";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
            echo "❌ Failed to assign booking. Error: " . $conn->error;
            echo "</div>";
        }
    }
}

// Handle creating a new test booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_test_booking'])) {
    $passenger_name = trim($_POST['passenger_name']) ?: 'Test Passenger';
    $passenger_phone = trim($_POST['passenger_phone']) ?: '0712345678';
    $travel_date = $_POST['travel_date'] ?: date('Y-m-d', strtotime('+1 day'));
    
    // Create new booking
    $insert_stmt = $conn->prepare("
        INSERT INTO bookings (fullname, phone, assigned_vehicle, travel_date, created_at, route, pickup_location, destination) 
        VALUES (?, ?, ?, ?, NOW(), 'Test Route', 'Test Pickup', 'Test Destination')
    ");
    $insert_stmt->bind_param('ssss', $passenger_name, $passenger_phone, $driver_vehicle, $travel_date);
    
    if ($insert_stmt->execute()) {
        $new_booking_id = $conn->insert_id;
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "✅ <strong>SUCCESS!</strong> Created test booking #$new_booking_id<br>";
        echo "👤 Passenger: $passenger_name ($passenger_phone)<br>";
        echo "🚗 Assigned to: $driver_vehicle<br>";
        echo "📅 Travel Date: $travel_date<br>";
        echo "🎉 Location sharing should now work!";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "❌ Failed to create test booking. Error: " . $conn->error;
        echo "</div>";
    }
}

// Show existing unassigned bookings
echo "<h3>📋 Existing Unassigned Bookings</h3>";
$unassigned_query = "SELECT * FROM bookings WHERE assigned_vehicle IS NULL OR assigned_vehicle = '' ORDER BY created_at DESC LIMIT 10";
$unassigned_result = $conn->query($unassigned_query);

if ($unassigned_result && $unassigned_result->num_rows > 0) {
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px;'>";
    echo "✅ Found " . $unassigned_result->num_rows . " unassigned booking(s):<br>";
    
    while ($booking = $unassigned_result->fetch_assoc()) {
        $booking_id = $booking['booking_id'] ?? $booking['id'] ?? 'N/A';
        echo "<div style='margin: 10px 0; padding: 10px; background: white; border-radius: 5px; border: 1px solid #ddd;'>";
        echo "<strong>Booking #$booking_id</strong><br>";
        echo "👤 <strong>Passenger:</strong> " . htmlspecialchars($booking['fullname'] ?? 'N/A') . "<br>";
        echo "📞 <strong>Phone:</strong> " . htmlspecialchars($booking['phone'] ?? 'N/A') . "<br>";
        echo "📅 <strong>Travel Date:</strong> " . htmlspecialchars($booking['travel_date'] ?? 'N/A') . "<br>";
        echo "📍 <strong>Route:</strong> " . htmlspecialchars($booking['route'] ?? 'N/A') . "<br>";
        
        echo "<form method='POST' style='margin-top: 10px;'>";
        echo "<input type='hidden' name='booking_id' value='$booking_id'>";
        echo "<button type='submit' name='assign_booking' style='background: #6A0DAD; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer;'>";
        echo "🚗 Assign to My Vehicle";
        echo "</button>";
        echo "</form>";
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
    echo "⚠️ No unassigned bookings found<br>";
    echo "You can create a test booking below.";
    echo "</div>";
}

// Show all bookings assigned to any vehicle
echo "<h3>🚗 Currently Assigned Bookings</h3>";
$assigned_query = "SELECT * FROM bookings WHERE assigned_vehicle IS NOT NULL AND assigned_vehicle != '' ORDER BY created_at DESC LIMIT 10";
$assigned_result = $conn->query($assigned_query);

if ($assigned_result && $assigned_result->num_rows > 0) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "✅ Found " . $assigned_result->num_rows . " assigned booking(s):<br>";
    
    while ($booking = $assigned_result->fetch_assoc()) {
        $booking_id = $booking['booking_id'] ?? $booking['id'] ?? 'N/A';
        $is_mine = ($booking['assigned_vehicle'] === $driver_vehicle);
        $highlight = $is_mine ? 'background: #d1ecf1; border: 2px solid #6A0DAD;' : 'background: white;';
        
        echo "<div style='margin: 10px 0; padding: 10px; border-radius: 5px; border: 1px solid #ddd; $highlight'>";
        echo "<strong>Booking #$booking_id</strong> " . ($is_mine ? "👑 <strong>(YOUR VEHICLE)</strong>" : "") . "<br>";
        echo "👤 <strong>Passenger:</strong> " . htmlspecialchars($booking['fullname'] ?? 'N/A') . "<br>";
        echo "📞 <strong>Phone:</strong> " . htmlspecialchars($booking['phone'] ?? 'N/A') . "<br>";
        echo "🚗 <strong>Vehicle:</strong> " . htmlspecialchars($booking['assigned_vehicle']) . "<br>";
        echo "📅 <strong>Travel Date:</strong> " . htmlspecialchars($booking['travel_date'] ?? 'N/A') . "<br>";
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ No assigned bookings found";
    echo "</div>";
}

// Create test booking form
echo "<h3>🧪 Create Test Booking</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<form method='POST'>";
echo "<div style='margin: 10px 0;'>";
echo "<label><strong>Passenger Name:</strong></label><br>";
echo "<input type='text' name='passenger_name' value='Test Passenger' style='padding: 5px; width: 200px;'>";
echo "</div>";
echo "<div style='margin: 10px 0;'>";
echo "<label><strong>Passenger Phone:</strong></label><br>";
echo "<input type='text' name='passenger_phone' value='0712345678' style='padding: 5px; width: 200px;'>";
echo "</div>";
echo "<div style='margin: 10px 0;'>";
echo "<label><strong>Travel Date:</strong></label><br>";
echo "<input type='date' name='travel_date' value='" . date('Y-m-d', strtotime('+1 day')) . "' style='padding: 5px; width: 200px;'>";
echo "</div>";
echo "<button type='submit' name='create_test_booking' style='background: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer;'>";
echo "🧪 Create Test Booking (Assigned to $driver_vehicle)";
echo "</button>";
echo "</form>";
echo "</div>";

echo "<h3>📋 Next Steps</h3>";
echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px;'>";
echo "<ol>";
echo "<li><strong>Assign a booking</strong> to your vehicle using the buttons above</li>";
echo "<li><strong>Go to Driver Dashboard:</strong> <a href='Driver/index.php'>Driver/index.php</a></li>";
echo "<li><strong>Test location sharing</strong> - you should now see passengers!</li>";
echo "</ol>";
echo "</div>";

echo "<div style='margin: 20px 0;'>";
echo "<a href='Driver/index.php' style='background: #6A0DAD; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🚗 Driver Dashboard</a>";
echo "<a href='test_location_fix.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🧪 Test Location Fix</a>";
echo "<a href='check_database_structure.php' style='background: #17a2b8; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔍 Check Database</a>";
echo "</div>";

?>