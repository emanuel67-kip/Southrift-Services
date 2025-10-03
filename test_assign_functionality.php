<?php
require 'db.php';

echo "<h2>Testing Assignment Functionality</h2>";

// Check if assigned_vehicle column exists in bookings table
echo "<h3>1. Checking bookings table structure:</h3>";
$columnsResult = $conn->query("SHOW COLUMNS FROM bookings");
$hasAssignedVehicle = false;

if ($columnsResult) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Column Name</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    while ($col = $columnsResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
        
        if ($col['Field'] === 'assigned_vehicle') {
            $hasAssignedVehicle = true;
        }
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Error checking table structure: " . $conn->error . "</p>";
}

echo "<h3>2. assigned_vehicle column status:</h3>";
if ($hasAssignedVehicle) {
    echo "<p style='color: green;'>✅ assigned_vehicle column EXISTS in bookings table</p>";
} else {
    echo "<p style='color: red;'>❌ assigned_vehicle column DOES NOT EXIST in bookings table</p>";
    echo "<p><strong>Solution:</strong> We need to add this column to the bookings table.</p>";
}

// Check recent bookings and their assigned vehicles
echo "<h3>3. Recent bookings with assignments:</h3>";
if ($hasAssignedVehicle) {
    $result = $conn->query("SELECT id, fullname, phone, assigned_vehicle, created_at FROM bookings ORDER BY id DESC LIMIT 10");
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Phone</th><th>Assigned Vehicle</th><th>Created</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['fullname']) . "</td>";
            echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
            echo "<td>" . htmlspecialchars($row['assigned_vehicle'] ?? '—') . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No bookings found or error: " . $conn->error . "</p>";
    }
} else {
    echo "<p>Cannot check assignments - column doesn't exist</p>";
}

// Check vehicles table
echo "<h3>4. Available vehicles:</h3>";
$vehiclesResult = $conn->query("SELECT number_plate, type, is_waiting FROM vehicles LIMIT 5");
if ($vehiclesResult && $vehiclesResult->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Number Plate</th><th>Type</th><th>Is Waiting</th></tr>";
    
    while ($row = $vehiclesResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['number_plate']) . "</td>";
        echo "<td>" . htmlspecialchars($row['type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['is_waiting'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No vehicles found or error: " . $conn->error . "</p>";
}

$conn->close();
?>