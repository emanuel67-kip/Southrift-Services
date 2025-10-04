<?php
// Database connection
require_once 'db.php';

echo "<h1>Drivers Table Check</h1>";

// Check drivers table structure
echo "<h2>Table Structure</h2>";
$result = $conn->query("DESCRIBE drivers");
if ($result) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Show all drivers
echo "<h2>All Drivers</h2>";
$drivers = $conn->query("SELECT * FROM drivers");
if ($drivers && $drivers->num_rows > 0) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    $first = true;
    while ($driver = $drivers->fetch_assoc()) {
        if ($first) {
            // Header row
            echo "<tr>";
            foreach (array_keys($driver) as $key) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
            echo "</tr>";
            $first = false;
        }
        // Data row
        echo "<tr>";
        foreach ($driver as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No drivers found in the database.</p>";
}

// Close connection
$conn->close();
?>
