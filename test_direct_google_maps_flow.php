<?php
require_once 'db.php';

echo "<h1>🧪 Testing Direct Google Maps Link Attachment</h1>";

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { background: #e7f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
.warning { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
h2, h3 { color: #6A0DAD; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
td, th { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>";

try {
    echo "<h2>Step 1: Ensure Database Structure</h2>";
    
    // Check if Google Maps link columns exist
    $check_column = $conn->query("SHOW COLUMNS FROM bookings LIKE 'google_maps_link'");
    
    if ($check_column->num_rows == 0) {
        echo "<div class='warning'>⚠️ Adding required columns to bookings table...</div>";
        $alter_query = "ALTER TABLE bookings ADD COLUMN google_maps_link TEXT NULL, ADD COLUMN shared_location_updated TIMESTAMP NULL";
        
        if ($conn->query($alter_query)) {
            echo "<div class='success'>✅ Successfully added Google Maps columns to bookings table</div>";
        } else {
            echo "<div class='error'>❌ Failed to add columns: " . $conn->error . "</div>";
        }
    } else {
        echo "<div class='success'>✅ Google Maps columns already exist in bookings table</div>";
    }
    
    echo "<h2>Step 2: Test Complete Flow</h2>";
    
    // Simple test with sample data
    $test_google_maps_link = "https://maps.app.goo.gl/test" . time();
    
    echo "<div class='info'>";
    echo "<h3>🎯 Expected Behavior:</h3>";
    echo "<ol>";
    echo "<li><strong>Driver shares location:</strong> Google Maps link gets attached to all today's passengers</li>";
    echo "<li><strong>Passenger clicks 'Track My Ride':</strong> Gets direct access to driver's live location</li>";
    echo "<li><strong>Two access modes:</strong>";
    echo "<ul>";
    echo "<li>Direct redirect: ?redirect=true</li>";
    echo "<li>Interface with buttons: normal access</li>";
    echo "</ul></li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h2>Step 3: Test Links</h2>";
    
    echo "<div class='info'>";
    echo "<p><strong>🔗 Test the system:</strong></p>";
    echo "<ul>";
    echo "<li><a href='add_google_maps_column.php' target='_blank'>Add Database Columns (if needed)</a></li>";
    echo "<li><a href='Driver/index.php' target='_blank'>Driver Dashboard - Share Location</a></li>";
    echo "<li><a href='track_my_driver.php' target='_blank'>Passenger Tracking Interface</a></li>";
    echo "<li><a href='track_my_driver.php?redirect=true' target='_blank'>Direct Google Maps Redirect</a></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h2>Step 4: How It Works</h2>";
    
    echo "<div class='success'>";
    echo "<h3>✅ System Flow:</h3>";
    echo "<p><strong>1. Driver Side:</strong></p>";
    echo "<ul>";
    echo "<li>Driver goes to dashboard and clicks 'Share Live Location'</li>";
    echo "<li>Driver pastes Google Maps live location link</li>";
    echo "<li>System finds all passengers with bookings for today</li>";
    echo "<li>Google Maps link is attached to each passenger's booking record</li>";
    echo "</ul>";
    echo "<p><strong>2. Passenger Side:</strong></p>";
    echo "<ul>";
    echo "<li>Passenger clicks 'Track My Ride' from their profile</li>";
    echo "<li>System checks their booking for attached Google Maps link</li>";
    echo "<li>If link exists: Shows interface with direct access buttons</li>";
    echo "<li>If ?redirect=true: Redirects immediately to Google Maps</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}
?>