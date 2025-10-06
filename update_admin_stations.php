<?php
require_once 'db.php';

// Update admin stations
$adminStations = [
    1 => 'Nairobi', // Assuming admin with ID 1 is stationed in Nairobi
    // Add more admins and their stations as needed
];

echo "<h2>Updating Admin Stations</h2>";

foreach ($adminStations as $adminId => $station) {
    $stmt = $conn->prepare("UPDATE users SET station = ? WHERE id = ? AND role = 'admin'");
    $stmt->bind_param("si", $station, $adminId);
    
    if ($stmt->execute()) {
        echo "<p>✅ Updated admin ID $adminId with station: $station</p>";
    } else {
        echo "<p>❌ Failed to update admin ID $adminId: " . $stmt->error . "</p>";
    }
    
    $stmt->close();
}

echo "<p>✅ Admin station update completed!</p>";

// Display current admin stations
echo "<h3>Current Admin Stations:</h3>";
$result = $conn->query("SELECT id, name, station FROM users WHERE role = 'admin'");
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Admin ID</th><th>Name</th><th>Station</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['station'] ?? 'Not set') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No admins found.</p>";
}

$conn->close();
?>