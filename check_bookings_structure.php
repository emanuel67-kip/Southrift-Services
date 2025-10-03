<?php
require_once 'db.php';

echo "<h2>Bookings Table Structure</h2>";

try {
    // Get the actual column structure of the bookings table
    $result = $conn->query("DESCRIBE bookings");
    
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #6A0DAD; color: white;'><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>Sample Booking Data</h3>";
    $sample = $conn->query("SELECT * FROM bookings LIMIT 1");
    if ($sample && $row = $sample->fetch_assoc()) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #6A0DAD; color: white;'><th>Column</th><th>Value</th></tr>";
        foreach ($row as $column => $value) {
            echo "<tr><td>$column</td><td>" . ($value ?? 'NULL') . "</td></tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #6A0DAD; }
table { width: 100%; margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
tr:nth-child(even) { background: #f9f9f9; }
</style>
