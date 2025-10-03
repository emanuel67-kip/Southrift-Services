<?php
require 'db.php';

echo "<h2>Adding assigned_vehicle Column to Bookings Table</h2>";

// Add the assigned_vehicle column
$sql = "ALTER TABLE bookings ADD COLUMN assigned_vehicle VARCHAR(20) NULL AFTER payment_method";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✅ Successfully added 'assigned_vehicle' column to bookings table</p>";
    
    // Verify the column was added
    $verify = $conn->query("SHOW COLUMNS FROM bookings LIKE 'assigned_vehicle'");
    if ($verify && $verify->num_rows > 0) {
        echo "<p style='color: green;'>✅ Column verified - 'assigned_vehicle' now exists</p>";
        
        // Show updated table structure
        echo "<h3>Updated Bookings Table Structure:</h3>";
        $columnsResult = $conn->query("SHOW COLUMNS FROM bookings");
        if ($columnsResult) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Column Name</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            
            while ($col = $columnsResult->fetch_assoc()) {
                $highlight = ($col['Field'] === 'assigned_vehicle') ? 'style="background-color: yellow;"' : '';
                echo "<tr $highlight>";
                echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Column verification failed</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ Error adding column: " . $conn->error . "</p>";
    
    // Check if column already exists
    $check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'assigned_vehicle'");
    if ($check && $check->num_rows > 0) {
        echo "<p style='color: blue;'>ℹ️ Column 'assigned_vehicle' already exists</p>";
    }
}

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; width: 100%; max-width: 800px; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; font-weight: bold; }
</style>