<?php
echo "<h2>🧪 Live Google Maps Sharing Test</h2>";

// Test with current session or default driver
session_start();
$driver_phone = $_SESSION['phone'] ?? '0736225373';

echo "<h3>Testing Google Maps sharing for driver: $driver_phone</h3>";

if (isset($_POST['test_share'])) {
    $google_maps_link = $_POST['google_maps_link'];
    
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
    echo "<h4>🔄 Processing share request...</h4>";
    
    // Create the POST data
    $post_data = http_build_query([
        'driver_phone' => $driver_phone,
        'google_maps_link' => $google_maps_link,
        'csrf_token' => 'test'
    ]);
    
    // Use cURL to call the API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/Southrift%20Services/Driver/share_google_maps_link.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<h4>📡 API Response (HTTP $http_code):</h4>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>$response</pre>";
    
    $result = json_decode($response, true);
    if ($result) {
        if ($result['success']) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>✅ SUCCESS!</h4>";
            echo "<p><strong>Passengers notified:</strong> {$result['passengers_notified']}</p>";
            echo "<p><strong>Google Maps link:</strong> {$result['google_maps_link']}</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>❌ ERROR</h4>";
            echo "<p><strong>Message:</strong> {$result['message']}</p>";
            if (isset($result['debug'])) {
                echo "<p><strong>Debug:</strong> " . print_r($result['debug'], true) . "</p>";
            }
            echo "</div>";
        }
    }
    echo "</div>";
}
?>

<div style="max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
    <h3>🗺️ Test Google Maps Live Location Sharing</h3>
    
    <form method="POST">
        <div style="margin-bottom: 15px;">
            <label><strong>Driver Phone:</strong></label><br>
            <input type="text" value="<?= htmlspecialchars($driver_phone) ?>" readonly style="width: 100%; padding: 8px; background: #f8f9fa;">
        </div>
        
        <div style="margin-bottom: 15px;">
            <label><strong>Google Maps Live Location Link:</strong></label><br>
            <input type="url" name="google_maps_link" value="https://maps.app.goo.gl/TestLiveShare123" 
                   style="width: 100%; padding: 8px;" required>
            <small style="color: #666;">Paste your Google Maps live location sharing link here</small>
        </div>
        
        <button type="submit" name="test_share" 
                style="background: #6A0DAD; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
            📡 Test Share Location
        </button>
    </form>
</div>

<div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0;">
    <h4>📋 Expected Result:</h4>
    <p>If you're logged in as driver <strong>0736225373</strong>, you should see:</p>
    <ul>
        <li>✅ <strong>SUCCESS!</strong></li>
        <li>📊 <strong>Passengers notified: 1</strong> (Miriam Chebet)</li>
        <li>🔗 Your Google Maps link</li>
    </ul>
    <p>If you see "Passengers notified: 0", please check:</p>
    <ul>
        <li>Are you logged in as the correct driver?</li>
        <li>Is there a booking assigned to your vehicle today?</li>
    </ul>
</div>

<style>
input[type="url"], input[type="text"] {
    border: 1px solid #ddd;
    border-radius: 4px;
}
button:hover {
    background: #5a0b8a !important;
}
</style>