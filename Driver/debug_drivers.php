<?php
session_start();
require __DIR__ . '/auth.php';
require dirname(__DIR__) . '/db.php';

echo "<h2>Debug: Drivers in Database</h2>";

// Get all drivers
$result = $conn->query("SELECT id, name, phone, email FROM drivers");

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Name</th><th>Phone</th><th>Email</th><th>Length</th><th>Format</th></tr>";
    
    while($row = $result->fetch_assoc()) {
        $length = strlen($row['phone']);
        $format = '';
        
        // Try to determine the format
        if (strpos($row['phone'], '+') === 0) {
            $format = 'International with +';
        } elseif (is_numeric($row['phone']) && $length >= 10) {
            $format = 'Local format';
        } else {
            $format = 'Unknown format';
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . $length . " chars</td>";
        echo "<td>" . $format . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "No drivers found in the database.";
}

// Show current session data
echo "<h3>Current Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

$conn->close();
?>
