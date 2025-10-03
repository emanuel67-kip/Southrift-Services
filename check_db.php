<?php
// Database connection
require_once 'db.php';

echo "<h1>Database Check</h1>";

// Check drivers table
$tables = $conn->query("SHOW TABLES");
echo "<h2>Available Tables:</h2>";
while ($table = $tables->fetch_array()) {
    echo "<p>" . $table[0] . "</p>";
    
    // Show table structure
    $columns = $conn->query("SHOW COLUMNS FROM " . $table[0]);
    echo "<h3>Table: " . $table[0] . "</h3>";
    echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
    while ($col = $columns->fetch_assoc()) {
        echo "<tr><td>" . $col['Field'] . "</td><td>" . $col['Type'] . "</td></tr>";
    }
    echo "</table>";
    
    // Show sample data
    $data = $conn->query("SELECT * FROM " . $table[0] . " LIMIT 3");
    if ($data && $data->num_rows > 0) {
        echo "<h4>Sample Data:</h4><pre>";
        while ($row = $data->fetch_assoc()) {
            print_r($row);
            echo "\n---\n";
        }
        echo "</pre>";
    }
}
?>
