<?php
// Set the correct session configuration to match the login system
if (session_status() === PHP_SESSION_NONE) {
    session_name('southrift_admin');
    session_start();
}

require dirname(__DIR__) . '/db.php';

echo "<h2>Available Drivers for Testing</h2>";

$stmt = $conn->prepare("SELECT id, name, driver_phone, number_plate, route, status FROM drivers ORDER BY id");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Name</th><th>Phone</th><th>Number Plate</th><th>Route</th><th>Status</th><th>Login Credentials</th>";
    echo "</tr>";
    
    while ($driver = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($driver['id']) . "</td>";
        echo "<td>" . htmlspecialchars($driver['name']) . "</td>";
        echo "<td>" . htmlspecialchars($driver['driver_phone']) . "</td>";
        echo "<td>" . htmlspecialchars($driver['number_plate']) . "</td>";
        echo "<td>" . htmlspecialchars($driver['route']) . "</td>";
        echo "<td>" . htmlspecialchars($driver['status']) . "</td>";
        echo "<td><strong>Username:</strong> " . htmlspecialchars($driver['driver_phone']) . "<br>";
        echo "<strong>Password:</strong> " . htmlspecialchars($driver['number_plate']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>How to Test:</h3>";
    echo "<ol>";
    echo "<li>Go to <a href='../login.html' target='_blank'>login.html</a></li>";
    echo "<li>Use <strong>Phone</strong> as username and <strong>Number Plate</strong> as password</li>";
    echo "<li>Should redirect to <a href='index.php' target='_blank'>Driver Dashboard</a></li>";
    echo "<li>Then try accessing <a href='profile.php' target='_blank'>Driver Profile</a></li>";
    echo "</ol>";
    
} else {
    echo "<p style='color: red;'>❌ No drivers found in database!</p>";
    echo "<p>You need to add drivers through the Admin panel first.</p>";
    echo "<p><a href='../Admin/add_vehicle.php' target='_blank'>Add Vehicle & Driver</a></p>";
}
?>