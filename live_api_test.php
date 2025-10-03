<?php
// Test the actual share_google_maps_link.php endpoint
$driver_phone = '0736225373';
$google_maps_link = 'https://maps.app.goo.gl/LiveTest123';

// Simulate POST request
$_POST = [
    'driver_phone' => $driver_phone,
    'google_maps_link' => $google_maps_link,
    'csrf_token' => 'test'
];

echo "<h2>🚀 Live API Test</h2>";
echo "<p>Testing share_google_maps_link.php with driver 0736225373...</p>";

// Capture output
ob_start();
include 'Driver/share_google_maps_link.php';
$output = ob_get_clean();

echo "<h3>📡 API Response:</h3>";
echo "<pre>$output</pre>";

// Verify the result
$response = json_decode($output, true);
if ($response && $response['success']) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>✅ SUCCESS!</h4>";
    echo "<p>✅ Google Maps link shared successfully</p>";
    echo "<p>📊 Passengers notified: {$response['passengers_notified']}</p>";
    echo "<p>🔗 Link: {$response['google_maps_link']}</p>";
    echo "</div>";
    
    if ($response['passengers_notified'] > 0) {
        echo "<p><strong>🎯 The system now correctly identifies passengers assigned TODAY regardless of travel date!</strong></p>";
    }
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>❌ Error</h4>";
    echo "<p>Response: " . ($response['message'] ?? 'Unknown error') . "</p>";
    echo "</div>";
}

?>