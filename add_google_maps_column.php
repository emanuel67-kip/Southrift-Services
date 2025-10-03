<?php
require_once 'db.php';

echo "<h2>Adding Google Maps Link Column to Bookings Table</h2>";

try {
    // Check if column already exists
    $check_column = $conn->query("SHOW COLUMNS FROM bookings LIKE 'google_maps_link'");
    
    if ($check_column->num_rows > 0) {
        echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "⚠️ Column 'google_maps_link' already exists in bookings table";
        echo "</div>";
    } else {
        // Add the column
        $alter_query = "ALTER TABLE bookings ADD COLUMN google_maps_link TEXT NULL, ADD COLUMN shared_location_updated TIMESTAMP NULL";
        
        if ($conn->query($alter_query)) {
            echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "✅ Successfully added 'google_maps_link' and 'shared_location_updated' columns to bookings table";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "❌ Failed to add columns: " . $conn->error;
            echo "</div>";
        }
    }
    
    // Show updated table structure
    echo "<h3>Updated Bookings Table Structure</h3>";
    $result = $conn->query('DESCRIBE bookings');
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $highlight = ($row['Field'] == 'google_maps_link' || $row['Field'] == 'shared_location_updated') ? 'style="background: #d4edda;"' : '';
        echo "<tr $highlight><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td><td>{$row['Extra']}</td></tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
}
?>