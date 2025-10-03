<?php
require_once 'db.php';

echo "<h2>🔍 COMPLETE LOCATION SHARING TEST</h2>";

// Simulate the complete flow
$driver_phone = '0736225373';
$passenger_user_id = 3; // Miriam Chebet

echo "<h3>Step 1: Driver Shares Google Maps Location</h3>";

// Simulate driver sharing Google Maps location
$google_maps_link = 'https://maps.app.goo.gl/CompleteFlowTest123';
$_POST = [
    'driver_phone' => $driver_phone,
    'google_maps_link' => $google_maps_link,
    'csrf_token' => 'test'
];

// Start session for driver
session_start();
$_SESSION['phone'] = $driver_phone;
$_SESSION['csrf_token'] = 'test';

echo "<h4>📡 Testing share_google_maps_link.php</h4>";

// Capture output from the sharing script
ob_start();
$old_post = $_POST;
include 'Driver/share_google_maps_link.php';
$share_output = ob_get_clean();
$_POST = $old_post; // Restore $_POST

echo "<strong>Share API Response:</strong><br>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>$share_output</pre>";

$share_result = json_decode($share_output, true);
if ($share_result && $share_result['success']) {
    echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "✅ <strong>Driver sharing successful!</strong><br>";
    echo "📊 Passengers notified: {$share_result['passengers_notified']}<br>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>Driver sharing failed!</strong><br>";
    echo "Error: " . ($share_result['message'] ?? 'Unknown error') . "<br>";
    echo "</div>";
    exit;
}

echo "<h3>Step 2: Check Passenger Notifications</h3>";

// Check if notification was created
$notif_stmt = $conn->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? AND type = 'location_sharing' 
    ORDER BY created_at DESC 
    LIMIT 1
");
$notif_stmt->bind_param('i', $passenger_user_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
$notification = $notif_result->fetch_assoc();

if ($notification) {
    echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "✅ <strong>Notification created!</strong><br>";
    echo "<strong>Title:</strong> {$notification['title']}<br>";
    echo "<strong>Message:</strong> {$notification['message']}<br>";
    echo "<strong>Created:</strong> {$notification['created_at']}<br>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>No notification found for passenger!</strong><br>";
    echo "</div>";
}

echo "<h3>Step 3: Check Driver Location Storage</h3>";

// Check if driver location was stored
$location_stmt = $conn->prepare("
    SELECT dl.* 
    FROM driver_locations dl
    JOIN drivers d ON dl.driver_id = d.id
    WHERE d.driver_phone = ?
    AND dl.status = 'sharing_gmaps'
    ORDER BY dl.last_updated DESC
    LIMIT 1
");
$location_stmt->bind_param('s', $driver_phone);
$location_stmt->execute();
$location_result = $location_stmt->get_result();
$location_data = $location_result->fetch_assoc();

if ($location_data) {
    echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "✅ <strong>Driver location stored!</strong><br>";
    echo "<strong>Status:</strong> {$location_data['status']}<br>";
    echo "<strong>Google Maps Link:</strong> " . substr($location_data['google_maps_link'], 0, 50) . "...<br>";
    echo "<strong>Last Updated:</strong> {$location_data['last_updated']}<br>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>Driver location not stored!</strong><br>";
    echo "</div>";
}

echo "<h3>Step 4: Test Passenger Track My Driver Page</h3>";

// Clear session and set passenger session
session_destroy();
session_start();
$_SESSION['user_id'] = $passenger_user_id;
$_SESSION['role'] = 'passenger';

echo "<p>🧪 <strong>Testing track_my_driver.php for passenger...</strong></p>";

// Test the track_my_driver.php page
$track_url = "http://localhost/Southrift%20Services/track_my_driver.php";

// Use file_get_contents with context to simulate a request
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Cookie: PHPSESSID=" . session_id() . "\r\n"
    ]
]);

$track_content = @file_get_contents($track_url, false, $context);

if ($track_content) {
    // Check if the page contains the Google Maps link
    if (strpos($track_content, $google_maps_link) !== false) {
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ <strong>Track My Driver page shows Google Maps link!</strong><br>";
        echo "<p>The passenger can successfully see the shared location.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "⚠️ <strong>Track My Driver page loaded but no Google Maps link found</strong><br>";
        echo "<p>This might be because the passenger doesn't have a booking or other issue.</p>";
        echo "</div>";
    }
} else {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>Could not load Track My Driver page</strong><br>";
    echo "</div>";
}

echo "<h3>Step 5: Manual Test Instructions</h3>";

echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
echo "<h4>🧑‍💻 Manual Test Steps:</h4>";
echo "<ol>";
echo "<li><strong>Login as Passenger:</strong> Use the login form with passenger credentials</li>";
echo "<li><strong>Go to Profile:</strong> <a href='profile.html'>profile.html</a></li>";
echo "<li><strong>Click 'Track My Driver':</strong> In the Quick Actions section</li>";
echo "<li><strong>Expected Result:</strong> You should see the Google Maps link button</li>";
echo "</ol>";
echo "</div>";

if ($share_result && $share_result['success'] && $notification && $location_data) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
    echo "<h4>🎉 COMPLETE FLOW WORKING!</h4>";
    echo "<p>✅ Driver sharing works<br>";
    echo "✅ Passenger notification created<br>";
    echo "✅ Driver location stored<br>";
    echo "📱 Passengers should be able to track the driver's location!</p>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
    echo "<h4>⚠️ PARTIAL SUCCESS</h4>";
    echo "<p>Some components are working, but there may be issues with the complete flow.</p>";
    echo "</div>";
}

?>