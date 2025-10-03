<?php
require_once 'db.php';

echo "<h2>Bookings Table Structure</h2>";
$result = $conn->query('DESCRIBE bookings');
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td><td>{$row['Extra']}</td></tr>";
}
echo "</table>";

echo "<h2>Sample Bookings Data</h2>";
$sample = $conn->query('SELECT * FROM bookings LIMIT 3');
echo "<table border='1'>";
if ($sample->num_rows > 0) {
    $first = true;
    while ($row = $sample->fetch_assoc()) {
        if ($first) {
            echo "<tr>";
            foreach (array_keys($row) as $column) {
                echo "<th>$column</th>";
            }
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
} else {
    echo "<tr><td>No bookings found</td></tr>";
}
echo "</table>";
?>