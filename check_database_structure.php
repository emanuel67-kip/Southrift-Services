<?php
require_once 'db.php';

echo "<h2>🔍 Database Structure Check</h2>";

// Check bookings table structure
echo "<h3>📋 Bookings Table Columns</h3>";
$result = $conn->query("DESCRIBE bookings");

if ($result) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #6A0DAD; color: white;'>";
    echo "<th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>{$row['Field']}</strong></td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ Could not retrieve bookings table structure: " . $conn->error;
    echo "</div>";
}

// Check sample data from bookings
echo "<h3>📊 Sample Bookings Data</h3>";
$sample_query = "SELECT * FROM bookings LIMIT 5";
$sample_result = $conn->query($sample_query);

if ($sample_result && $sample_result->num_rows > 0) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "✅ Found " . $sample_result->num_rows . " sample bookings:<br>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0; font-size: 12px;'>";
    
    // Get column names
    $fields = $sample_result->fetch_fields();
    echo "<tr style='background: #28a745; color: white;'>";
    foreach ($fields as $field) {
        echo "<th>" . htmlspecialchars($field->name) . "</th>";
    }
    echo "</tr>";
    
    // Reset result pointer
    $sample_result->data_seek(0);
    while ($row = $sample_result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            $display_value = $value ? htmlspecialchars(substr($value, 0, 50)) : 'NULL';
            echo "<td>$display_value</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
    echo "⚠️ No sample data found in bookings table";
    echo "</div>";
}

// Check if there are bookings for the specific driver vehicle
echo "<h3>🚗 Bookings for Driver Vehicle KDR 645 J</h3>";
$driver_bookings = $conn->query("SELECT * FROM bookings WHERE assigned_vehicle = 'KDR 645 J'");

if ($driver_bookings && $driver_bookings->num_rows > 0) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "✅ Found " . $driver_bookings->num_rows . " booking(s) for vehicle KDR 645 J:<br>";
    
    while ($booking = $driver_bookings->fetch_assoc()) {
        echo "<div style='margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px;'>";
        echo "<strong>Booking ID:</strong> " . ($booking['booking_id'] ?? $booking['id'] ?? 'N/A') . "<br>";
        echo "<strong>Passenger:</strong> " . htmlspecialchars($booking['fullname'] ?? 'N/A') . "<br>";
        echo "<strong>Phone:</strong> " . htmlspecialchars($booking['phone'] ?? 'N/A') . "<br>";
        echo "<strong>Travel Date:</strong> " . htmlspecialchars($booking['travel_date'] ?? 'N/A') . "<br>";
        echo "<strong>Created:</strong> " . htmlspecialchars($booking['created_at'] ?? 'N/A') . "<br>";
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ No bookings found for vehicle KDR 645 J<br>";
    echo "This is why you're seeing 0 passengers!<br>";
    echo "</div>";
    
    // Show what vehicles DO have bookings
    echo "<h4>Vehicles with bookings:</h4>";
    $vehicles_with_bookings = $conn->query("SELECT DISTINCT assigned_vehicle, COUNT(*) as booking_count FROM bookings WHERE assigned_vehicle IS NOT NULL GROUP BY assigned_vehicle");
    
    if ($vehicles_with_bookings && $vehicles_with_bookings->num_rows > 0) {
        echo "<ul>";
        while ($vb = $vehicles_with_bookings->fetch_assoc()) {
            echo "<li><strong>" . htmlspecialchars($vb['assigned_vehicle']) . "</strong> - " . $vb['booking_count'] . " booking(s)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No vehicles have any bookings assigned.</p>";
    }
}

echo "<h3>🔧 Quick Fix Options</h3>";
echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px;'>";
echo "<p>If no bookings are assigned to your vehicle, you can:</p>";
echo "<ol>";
echo "<li><strong>Assign existing bookings</strong> to your vehicle (KDR 645 J)</li>";
echo "<li><strong>Create a test booking</strong> for testing purposes</li>";
echo "<li><strong>Check if bookings exist</strong> but are assigned to different vehicle names</li>";
echo "</ol>";
echo "</div>";

echo "<div style='margin: 20px 0;'>";
echo "<a href='create_test_booking_assignment.php' style='background: #6A0DAD; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🧪 Create Test Booking</a>";
echo "<a href='assign_existing_booking.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>📋 Assign Existing Booking</a>";
echo "<a href='debug_passenger_assignment.php' style='background: #17a2b8; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔍 Debug Again</a>";
echo "</div>";

?>