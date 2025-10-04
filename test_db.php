<?php
// test_db_structure.php
require_once 'db.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check bookings table structure
echo "<h2>Bookings Table Structure</h2>";
$result = $conn->query("DESCRIBE bookings");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>".$row['Field']."</td>";
        echo "<td>".$row['Type']."</td>";
        echo "<td>".$row['Null']."</td>";
        echo "<td>".$row['Key']."</td>";
        echo "<td>".$row['Default']."</td>";
        echo "<td>".$row['Extra']."</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error describing table: " . $conn->error;
}

// Check users table structure
echo "<h2>Users Table Structure</h2>";
$result = $conn->query("DESCRIBE users");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>".$row['Field']."</td>";
        echo "<td>".$row['Type']."</td>";
        echo "<td>".$row['Null']."</td>";
        echo "<td>".$row['Key']."</td>";
        echo "<td>".$row['Default']."</td>";
        echo "<td>".$row['Extra']."</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error describing table: " . $conn->error;
}

// Show recent bookings with user info
echo "<h2>Recent Bookings with User Info</h2>";
$query = "SELECT b.*, u.id as user_table_id, u.name as user_name, u.phone as user_phone 
          FROM bookings b 
          LEFT JOIN users u ON b.phone = u.phone 
          ORDER BY b.id DESC 
          LIMIT 5";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr>";
    // Get column names
    $fields = $result->fetch_fields();
    foreach ($fields as $field) {
        echo "<th>".$field->name."</th>";
    }
    echo "</tr>";
    
    // Reset pointer
    $result->data_seek(0);
    
    // Show data
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach($row as $value) {
            echo "<td>".($value === null ? "NULL" : $value)."</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No bookings found or error: " . $conn->error;
}

$conn->close();
?>
