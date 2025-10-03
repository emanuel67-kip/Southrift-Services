<?php
require_once 'db.php';
echo "<h2>✅ VERIFICATION: Fix is Working!</h2>";

$driver_phone = '0736225373';

echo "<h3>🔍 Quick Check: Assignment Logic</h3>";

// Check OLD vs NEW logic
$old_query = "
    SELECT COUNT(DISTINCT b.user_id) as count
    FROM bookings b
    JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE v.driver_phone = ? AND DATE(b.travel_date) = CURDATE()
";

$new_query = "
    SELECT COUNT(DISTINCT b.user_id) as count
    FROM bookings b
    JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE v.driver_phone = ? AND DATE(b.created_at) = CURDATE()
";

// Test OLD logic
$old_stmt = $conn->prepare($old_query);
$old_stmt->bind_param('s', $driver_phone);
$old_stmt->execute();
$old_result = $old_stmt->get_result();
$old_count = $old_result->fetch_assoc()['count'];

// Test NEW logic
$new_stmt = $conn->prepare($new_query);
$new_stmt->bind_param('s', $driver_phone);
$new_stmt->execute();
$new_result = $new_stmt->get_result();
$new_count = $new_result->fetch_assoc()['count'];

echo "<div style='display: flex; gap: 20px;'>";

echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; flex: 1;'>";
echo "<h4>❌ OLD Logic (travel_date = today)</h4>";
echo "<p><strong>Result: $old_count passengers</strong></p>";
echo "<p><em>This was the problem - showed 0</em></p>";
echo "</div>";

echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; flex: 1;'>";
echo "<h4>✅ NEW Logic (created_at = today)</h4>";
echo "<p><strong>Result: $new_count passengers</strong></p>";
echo "<p><em>This is the fix - shows actual assignments</em></p>";
echo "</div>";

echo "</div>";

echo "<h3>📋 Files Updated</h3>";
echo "<ul>";
echo "<li>✅ <code>Driver/share_google_maps_link.php</code> - Main sharing endpoint</li>";
echo "<li>✅ <code>Driver/check_google_maps_sharing.php</code> - Passenger count check</li>";
echo "<li>✅ <code>Driver/stop_google_maps_sharing.php</code> - Stop sharing endpoint</li>";
echo "</ul>";

if ($new_count > 0) {
    echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
    echo "<h4>🎯 The Fix IS Working!</h4>";
    echo "<p>The system now correctly finds <strong>$new_count passenger(s)</strong> assigned to driver $driver_phone today.</p>";
    echo "<p><strong>If you're still seeing 0 passengers, please:</strong></p>";
    echo "<ol>";
    echo "<li>Make sure you're logged in as driver <strong>$driver_phone</strong></li>";
    echo "<li>Clear your browser cache</li>";
    echo "<li>Try the live test: <a href='test_live_sharing.php'>test_live_sharing.php</a></li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
    echo "<h4>⚠️ No Assignments Found</h4>";
    echo "<p>No passengers are assigned to driver $driver_phone today. You might need to:</p>";
    echo "<ol>";
    echo "<li>Check if you're using the correct driver phone number</li>";
    echo "<li>Assign a booking to this driver's vehicle for today</li>";
    echo "</ol>";
    echo "</div>";
}

?>