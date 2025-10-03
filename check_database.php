<?php
require_once "db.php";

echo "<h2>Database Tables</h2>";
$result = $conn->query("SHOW TABLES");
if ($result->num_rows > 0) {
    while($row = $result->fetch_array()) {
        $table = $row[0];
        echo "<h3>Table: $table</h3>";
        
        // Show table structure
        echo "<h4>Structure:</h4>";
        $structure = $conn->query("DESCRIBE $table");
        if ($structure) {
            echo "<table border='1'><tr>";
            while($col = $structure->fetch_assoc()) {
                echo "<th>" . $col['Field'] . "</th>";
            }
            echo "</tr></table>";
        }
        
        // Show table data
        echo "<h4>Data (first 5 rows):</h4>";
        $data = $conn->query("SELECT * FROM $table LIMIT 5");
        if ($data && $data->num_rows > 0) {
            echo "<table border='1'><tr>";
            // Headers
            $fields = $data->fetch_fields();
            foreach ($fields as $field) {
                echo "<th>" . $field->name . "</th>";
            }
            echo "</tr>";
            
            // Rows
            $data->data_seek(0);
            while($row = $data->fetch_row()) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "No data found in table.<br>";
        }
        echo "<hr>";
    }
} else {
    echo "No tables found in database.";
}

// Check if the login_debug.log exists
if (file_exists('login_debug.log')) {
    echo "<h2>Login Debug Log</h2>";
    echo "<pre>" . htmlspecialchars(file_get_contents('login_debug.log')) . "</pre>";
} else {
    echo "<p>No login debug log found. Please try to log in first to generate debug information.</p>";
}
?>
