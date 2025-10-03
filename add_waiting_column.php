<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db.php';

echo "<h2>Adding Missing Columns to Vehicles Table</h2>";

try {
    // Check if is_waiting column exists
    echo "<h3>Step 1: Checking if is_waiting column exists</h3>";
    $result = $conn->query("SHOW COLUMNS FROM vehicles LIKE 'is_waiting'");
    
    if ($result->num_rows == 0) {
        echo "❌ is_waiting column not found. Adding it...<br>";
        
        $sql = "ALTER TABLE vehicles ADD COLUMN is_waiting TINYINT(1) DEFAULT 0 AFTER status";
        
        if ($conn->query($sql)) {
            echo "✅ Successfully added is_waiting column to vehicles table<br>";
        } else {
            echo "❌ Error adding is_waiting column: " . $conn->error . "<br>";
        }
    } else {
        echo "✅ is_waiting column already exists in vehicles table<br>";
    }
    
    // Check if is_active column exists (also used in the code)
    echo "<h3>Step 2: Checking if is_active column exists</h3>";
    $result = $conn->query("SHOW COLUMNS FROM vehicles LIKE 'is_active'");
    
    if ($result->num_rows == 0) {
        echo "❌ is_active column not found. Adding it...<br>";
        
        $sql = "ALTER TABLE vehicles ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER is_waiting";
        
        if ($conn->query($sql)) {
            echo "✅ Successfully added is_active column to vehicles table<br>";
        } else {
            echo "❌ Error adding is_active column: " . $conn->error . "<br>";
        }
    } else {
        echo "✅ is_active column already exists in vehicles table<br>";
    }
    
    // Show current vehicles table structure
    echo "<h3>Step 3: Current vehicles table structure</h3>";
    $result = $conn->query("DESCRIBE vehicles");
    
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='padding: 5px;'>".$row['Field']."</td>";
            echo "<td style='padding: 5px;'>".$row['Type']."</td>";
            echo "<td style='padding: 5px;'>".$row['Null']."</td>";
            echo "<td style='padding: 5px;'>".$row['Key']."</td>";
            echo "<td style='padding: 5px;'>".$row['Default']."</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ Error describing vehicles table: " . $conn->error . "<br>";
    }
    
    // Show sample vehicles data
    echo "<h3>Step 4: Sample vehicles data</h3>";
    $result = $conn->query("SELECT id, number_plate, type, color, driver_name, is_waiting, is_active FROM vehicles LIMIT 5");
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Number Plate</th><th>Type</th><th>Color</th><th>Driver Name</th><th>Is Waiting</th><th>Is Active</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='padding: 5px;'>".$row['id']."</td>";
            echo "<td style='padding: 5px;'>".$row['number_plate']."</td>";
            echo "<td style='padding: 5px;'>".$row['type']."</td>";
            echo "<td style='padding: 5px;'>".$row['color']."</td>";
            echo "<td style='padding: 5px;'>".$row['driver_name']."</td>";
            echo "<td style='padding: 5px;'>".$row['is_waiting']."</td>";
            echo "<td style='padding: 5px;'>".$row['is_active']."</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ No vehicles found or error: " . $conn->error . "<br>";
    }
    
    echo "<h3>✅ Database update completed!</h3>";
    echo "<p>You can now use the 'Add to Waiting' functionality. <a href='Admin/index.php'>Go to Admin Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error occurred:</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
} finally {
    $conn->close();
}
?>