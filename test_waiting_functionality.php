<?php
require_once 'db.php';

echo "<h2>Testing Vehicles in Waiting Functionality</h2>";

// Check if vehicles table has the required columns
echo "<h3>1. Checking vehicles table structure</h3>";
$result = $conn->query("SHOW COLUMNS FROM vehicles WHERE Field IN ('is_waiting', 'is_active')");
if ($result && $result->num_rows >= 2) {
    echo "✅ Both is_waiting and is_active columns exist<br>";
} else {
    echo "❌ Missing required columns. Please run add_waiting_column.php first<br>";
    exit;
}

// Check current vehicles
echo "<h3>2. Current vehicles in database</h3>";
$result = $conn->query("SELECT id, number_plate, type, color, driver_name, is_waiting, is_active FROM vehicles LIMIT 10");

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
        echo "<td style='padding: 5px; color:" . ($row['is_waiting'] ? 'orange' : 'green') . ";'>" . ($row['is_waiting'] ? 'WAITING' : 'NOT WAITING') . "</td>";
        echo "<td style='padding: 5px; color:" . ($row['is_active'] ? 'green' : 'red') . ";'>" . ($row['is_active'] ? 'ACTIVE' : 'INACTIVE') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ No vehicles found in database<br>";
    
    // Let's add a sample vehicle for testing
    echo "<h3>3. Adding sample vehicle for testing</h3>";
    $sample_sql = "INSERT INTO vehicles (number_plate, type, color, route, capacity, driver_name, driver_phone, owner_name, owner_phone, is_waiting, is_active) 
                   VALUES ('KDA 123A', 'Matatu', 'White', 'Nairobi-Kisumu', 14, 'John Doe', '0712345678', 'Jane Smith', '0723456789', 0, 1)";
    
    if ($conn->query($sample_sql)) {
        echo "✅ Sample vehicle added successfully<br>";
        echo "<p><strong>Test vehicle:</strong> KDA 123A (White Matatu, Driver: John Doe)</p>";
    } else {
        echo "❌ Error adding sample vehicle: " . $conn->error . "<br>";
    }
}

// Check vehicles currently in waiting
echo "<h3>4. Vehicles currently in waiting</h3>";
$result = $conn->query("SELECT * FROM vehicles WHERE is_waiting = 1");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #ffc107; color: black;'><th>Number Plate</th><th>Type</th><th>Color</th><th>Driver Name</th><th>Status</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td style='padding: 5px;'>".$row['number_plate']."</td>";
        echo "<td style='padding: 5px;'>".$row['type']."</td>";
        echo "<td style='padding: 5px;'>".$row['color']."</td>";
        echo "<td style='padding: 5px;'>".$row['driver_name']."</td>";
        echo "<td style='padding: 5px; color: orange; font-weight: bold;'>WAITING</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>ℹ️ No vehicles currently in waiting</p>";
}

echo "<h3>5. Testing Instructions</h3>";
echo "<ol>";
echo "<li>Go to <a href='Admin/index.php' target='_blank'>Admin Dashboard</a></li>";
echo "<li>Use the 'Add vehicle to waiting' form at the bottom</li>";
echo "<li>Enter a number plate from the vehicles above (e.g., 'KDA 123A')</li>";
echo "<li>Click 'Add to Waiting'</li>";
echo "<li>Check <a href='Admin/vehicle_waiting.php' target='_blank'>Vehicles in Waiting</a> page</li>";
echo "</ol>";

$conn->close();
?>