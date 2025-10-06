<?php
require_once 'db.php';

echo "<h2>Adding Station Column to Users Table</h2>";

// Check if the station column already exists
$checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'station'");
if ($checkColumn && $checkColumn->num_rows > 0) {
    echo "<p>✅ Station column already exists in the users table.</p>";
    $conn->close();
    exit();
}

// Add the station column to the users table
$sql = "ALTER TABLE users ADD COLUMN station VARCHAR(100) DEFAULT NULL AFTER role";

if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Successfully added 'station' column to the users table.</p>";
    
    // Verify the column was added
    $result = $conn->query("DESCRIBE users");
    if ($result) {
        echo "<h3>Updated Users Table Structure:</h3>";
        echo "<table border='1' cellpadding='8' cellspacing='0'>";
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
} else {
    echo "<p>❌ Error adding station column: " . $conn->error . "</p>";
}

$conn->close();

echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>Run the <a href='add_station_admins.php'>add_station_admins.php</a> script to add station admins</li>";
echo "<li>Run the <a href='update_admin_passwords.php'>update_admin_passwords.php</a> script to set default passwords</li>";
echo "<li>Test the station-based filtering with sample data</li>";
echo "</ol>";
?>