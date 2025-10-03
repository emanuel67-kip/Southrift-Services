<?php
require_once 'db.php';

echo "<h2>🔧 COMPREHENSIVE FIX TEST</h2>";

$driver_phone = '0736225373';

echo "<h3>1. Files Updated Status</h3>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px;'>";
echo "<h4>✅ Files Fixed (using created_at = CURDATE()):</h4>";
echo "<ul>";
echo "<li>✅ <code>Driver/share_google_maps_link.php</code> - Main Google Maps sharing</li>";
echo "<li>✅ <code>Driver/check_google_maps_sharing.php</code> - Passenger count check</li>";
echo "<li>✅ <code>Driver/stop_google_maps_sharing.php</code> - Stop sharing</li>";
echo "<li>✅ <code>Driver/todays_bookings.php</code> - Today's passengers page</li>";
echo "<li>✅ <code>Driver/get_assigned_passengers.php</code> - API for getting passengers</li>";
echo "</ul>";
echo "</div>";

echo "<h3>2. Test All API Endpoints</h3>";

// Test 1: Check passenger count
echo "<h4>🧪 Test 1: Passenger Count Check</h4>";
$vehicles = [];
$vehicle_stmt = $conn->prepare("SELECT number_plate FROM vehicles WHERE driver_phone = ?");
$vehicle_stmt->bind_param("s", $driver_phone);
$vehicle_stmt->execute();
$vehicle_result = $vehicle_stmt->get_result();
while ($row = $vehicle_result->fetch_assoc()) {
    $vehicles[] = $row['number_plate'];
}

if (!empty($vehicles)) {
    $placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
    $count_stmt = $conn->prepare("
        SELECT COUNT(DISTINCT b.user_id) as passenger_count
        FROM bookings b
        WHERE b.assigned_vehicle IN ($placeholders)
        AND DATE(b.created_at) = CURDATE()
    ");
    $types = str_repeat('s', count($vehicles));
    $count_stmt->bind_param($types, ...$vehicles);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $passenger_count = $count_result->fetch_assoc()['passenger_count'];
    
    echo "<p><strong>Passenger Count:</strong> $passenger_count ✅</p>";
} else {
    echo "<p>❌ No vehicles found for driver</p>";
}

// Test 2: Get assigned passengers API
echo "<h4>🧪 Test 2: Get Assigned Passengers API</h4>";
$api_url = "http://localhost/Southrift%20Services/Driver/get_assigned_passengers.php";
$post_data = http_build_query([
    'csrf_token' => 'test'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID=test;");
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>API Response (HTTP $http_code):</strong></p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; max-height: 150px; overflow-y: auto;'>$response</pre>";

// Test 3: Direct Google Maps sharing test
echo "<h4>🧪 Test 3: Google Maps Sharing API</h4>";
session_start();
$_SESSION['phone'] = $driver_phone;
$_SESSION['csrf_token'] = 'test';

$share_post_data = http_build_query([
    'driver_phone' => $driver_phone,
    'google_maps_link' => 'https://maps.app.goo.gl/ComprehensiveTest123',
    'csrf_token' => 'test'
]);

$share_ch = curl_init();
curl_setopt($share_ch, CURLOPT_URL, "http://localhost/Southrift%20Services/Driver/share_google_maps_link.php");
curl_setopt($share_ch, CURLOPT_POST, true);
curl_setopt($share_ch, CURLOPT_POSTFIELDS, $share_post_data);
curl_setopt($share_ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($share_ch, CURLOPT_COOKIE, "southrift_admin=" . session_id() . ";");
$share_response = curl_exec($share_ch);
$share_http_code = curl_getinfo($share_ch, CURLINFO_HTTP_CODE);
curl_close($share_ch);

echo "<p><strong>Google Maps Share Response (HTTP $share_http_code):</strong></p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; max-height: 150px; overflow-y: auto;'>$share_response</pre>";

$share_result = json_decode($share_response, true);
if ($share_result && $share_result['success'] && $share_result['passengers_notified'] > 0) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>🎉 SUCCESS!</h4>";
    echo "<p>✅ Google Maps sharing is working correctly!</p>";
    echo "<p>📊 <strong>Passengers notified: {$share_result['passengers_notified']}</strong></p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>❌ Still Issues</h4>";
    echo "<p>The Google Maps sharing still has problems.</p>";
    if ($share_result) {
        echo "<p><strong>Error:</strong> " . ($share_result['message'] ?? 'Unknown error') . "</p>";
    }
    echo "</div>";
}

echo "<h3>3. Manual Test Instructions</h3>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px;'>";
echo "<h4>🧑‍💻 To test manually:</h4>";
echo "<ol>";
echo "<li><strong>Login as driver 0736225373</strong> in <a href='Driver/index.php'>Driver Dashboard</a></li>";
echo "<li><strong>Click 'Share Live Location' card</strong></li>";
echo "<li><strong>Paste any Google Maps link</strong> (e.g., https://maps.app.goo.gl/test123)</li>";
echo "<li><strong>You should now see:</strong> 'Location shared with $passenger_count passengers!'</li>";
echo "</ol>";
echo "</div>";

echo "<h3>4. Check Driver's Today's Passengers</h3>";
echo "<p><a href='Driver/todays_bookings.php' style='background: #6A0DAD; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>📅 View Today's Assigned Passengers</a></p>";
echo "<p><em>This page should show passengers assigned to driver TODAY (not just traveling today)</em></p>";

?>