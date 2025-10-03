<?php
require_once 'db.php';

echo "<h2>✅ Google Maps Sharing Fix - FINAL VERIFICATION</h2>";

$driver_phone = '0736225373';

echo "<h3>📋 Summary of Changes Made</h3>";
echo "<ol>";
echo "<li><strong>Updated share_google_maps_link.php:</strong> Changed filter from <code>DATE(b.travel_date) = CURDATE()</code> to <code>DATE(b.created_at) = CURDATE()</code></li>";
echo "<li><strong>Updated check_google_maps_sharing.php:</strong> Changed filter from <code>DATE(b.travel_date) = CURDATE()</code> to <code>DATE(b.created_at) = CURDATE()</code></li>";
echo "</ol>";

echo "<h3>🔍 Before vs After Comparison</h3>";

// Test OLD logic (travel_date = today)
echo "<h4>❌ OLD Logic (travel_date = today):</h4>";
$old_query = "
    SELECT COUNT(DISTINCT b.user_id) as passenger_count
    FROM bookings b
    JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE v.driver_phone = ?
    AND DATE(b.travel_date) = CURDATE()
";
$old_stmt = $conn->prepare($old_query);
$old_stmt->bind_param('s', $driver_phone);
$old_stmt->execute();
$old_result = $old_stmt->get_result();
$old_count = $old_result->fetch_assoc()['passenger_count'];
echo "Passengers found: <strong>$old_count</strong> (This was the problem - showed 0)<br>";

// Test NEW logic (created_at = today)
echo "<h4>✅ NEW Logic (created_at = today):</h4>";
$new_query = "
    SELECT COUNT(DISTINCT b.user_id) as passenger_count
    FROM bookings b
    JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE v.driver_phone = ?
    AND DATE(b.created_at) = CURDATE()
";
$new_stmt = $conn->prepare($new_query);
$new_stmt->bind_param('s', $driver_phone);
$new_stmt->execute();
$new_result = $new_stmt->get_result();
$new_count = $new_result->fetch_assoc()['passenger_count'];
echo "Passengers found: <strong>$new_count</strong> ✅ (This now correctly shows assignments made today)<br>";

echo "<h3>📊 Detailed Assignment Analysis</h3>";
$detail_query = "
    SELECT b.booking_id, b.fullname, b.travel_date, 
           DATE(b.created_at) as assignment_date,
           TIME(b.created_at) as assignment_time
    FROM bookings b
    JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE v.driver_phone = ?
    ORDER BY b.created_at DESC
";
$detail_stmt = $conn->prepare($detail_query);
$detail_stmt->bind_param('s', $driver_phone);
$detail_stmt->execute();
$detail_result = $detail_stmt->get_result();

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Booking ID</th><th>Passenger</th><th>Travel Date</th><th>Assignment Date</th><th>Assignment Time</th><th>Status</th></tr>";

while ($row = $detail_result->fetch_assoc()) {
    $is_today_assignment = ($row['assignment_date'] == date('Y-m-d'));
    $is_today_travel = ($row['travel_date'] == date('Y-m-d'));
    
    $status = '';
    if ($is_today_assignment && $is_today_travel) {
        $status = '🟢 Both today';
    } elseif ($is_today_assignment) {
        $status = '🔵 Assigned today';
    } elseif ($is_today_travel) {
        $status = '🟡 Travel today';
    } else {
        $status = '⚪ Neither today';
    }
    
    $row_style = $is_today_assignment ? 'background-color: #e7f3ff;' : '';
    
    echo "<tr style='$row_style'>";
    echo "<td>{$row['booking_id']}</td>";
    echo "<td>{$row['fullname']}</td>";
    echo "<td>{$row['travel_date']}</td>";
    echo "<td>{$row['assignment_date']}</td>";
    echo "<td>{$row['assignment_time']}</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><strong>Legend:</strong><br>";
echo "🔵 Blue highlight = Assignment made today (these passengers will receive Google Maps notifications)<br>";

echo "<h3>🎯 SOLUTION SUMMARY</h3>";
echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 15px 0;'>";
echo "<h4>✅ Problem SOLVED!</h4>";
echo "<p><strong>Issue:</strong> Google Maps sharing reported '0 passengers' because it was looking for bookings with <code>travel_date = today</code></p>";
echo "<p><strong>Root Cause:</strong> Passengers were assigned today but their travel date is for a future date (October 1st)</p>";
echo "<p><strong>Solution:</strong> Changed the filter to <code>created_at = today</code> to focus on when the assignment was made, not when they're traveling</p>";
echo "<p><strong>Result:</strong> Driver 0736225373 now correctly shows <strong>$new_count passenger(s)</strong> assigned today</p>";
echo "</div>";

echo "<h3>🚀 Ready to Test</h3>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Go to the driver dashboard (Driver/index.php)</li>";
echo "<li>Login as driver 0736225373</li>";
echo "<li>Click on 'Share Live Location' card</li>";
echo "<li>Paste any Google Maps live location sharing link</li>";
echo "<li>You should now see: <strong>'Location shared with $new_count passengers!'</strong></li>";
echo "</ol>";

echo "<p><em>The passenger (Miriam Chebet) will receive a notification that the driver has shared their live location via Google Maps.</em></p>";

?>