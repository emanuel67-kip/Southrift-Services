<?php
require_once 'db.php';

echo "<h2>Fix Station Assignments</h2>";

// Check if the station column exists
$checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'station'");
if (!$checkColumn || $checkColumn->num_rows === 0) {
    echo "<p>❌ Error: The 'station' column does not exist in the users table.</p>";
    echo "<p>Please run the add_station_column_to_users.sql script first:</p>";
    echo "<pre>ALTER TABLE users ADD COLUMN station VARCHAR(100) DEFAULT NULL AFTER role;</pre>";
    $conn->close();
    exit();
}

// Fix common issues with station assignments
echo "<h3>Fixing Common Station Assignment Issues</h3>";

// 1. Fix case sensitivity issues for boarding points
$boardingFixes = [
    'litein' => 'Litein',
    'LITEIN' => 'Litein',
    ' nairobi' => 'Nairobi',
    'nairobi ' => 'Nairobi',
    'NAIROBI' => 'Nairobi',
    ' kisumu' => 'Kisumu',
    'kisumu ' => 'Kisumu',
    'KISUMU' => 'Kisumu',
    ' nakuru' => 'Nakuru',
    'nakuru ' => 'Nakuru',
    'NAKURU' => 'Nakuru',
    ' bomet' => 'Bomet',
    'bomet ' => 'Bomet',
    'BOMET' => 'Bomet'
];

echo "<h4>1. Fixing Boarding Point Case Sensitivity Issues</h4>";
foreach ($boardingFixes as $from => $to) {
    $stmt = $conn->prepare("UPDATE bookings SET boarding_point = ? WHERE boarding_point = ?");
    $stmt->bind_param("ss", $to, $from);
    
    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        if ($affected > 0) {
            echo "<p>✅ Fixed $affected booking(s): '$from' → '$to'</p>";
        }
    } else {
        echo "<p>❌ Error fixing '$from' → '$to': " . $stmt->error . "</p>";
    }
    $stmt->close();
}

// 2. Fix case sensitivity issues for admin stations
$stationFixes = [
    'litein' => 'Litein',
    'LITEIN' => 'Litein',
    ' nairobi' => 'Nairobi',
    'nairobi ' => 'Nairobi',
    'NAIROBI' => 'Nairobi',
    ' kisumu' => 'Kisumu',
    'kisumu ' => 'Kisumu',
    'KISUMU' => 'Kisumu',
    ' nakuru' => 'Nakuru',
    'nakuru ' => 'Nakuru',
    'NAKURU' => 'Nakuru',
    ' bomet' => 'Bomet',
    'bomet ' => 'Bomet',
    'BOMET' => 'Bomet'
];

echo "<h4>2. Fixing Admin Station Case Sensitivity Issues</h4>";
foreach ($stationFixes as $from => $to) {
    $stmt = $conn->prepare("UPDATE users SET station = ? WHERE station = ? AND role = 'admin'");
    $stmt->bind_param("ss", $to, $from);
    
    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        if ($affected > 0) {
            echo "<p>✅ Fixed $affected admin(s): '$from' → '$to'</p>";
        }
    } else {
        echo "<p>❌ Error fixing '$from' → '$to': " . $stmt->error . "</p>";
    }
    $stmt->close();
}

// 3. Trim whitespace from boarding points
echo "<h4>3. Trimming Whitespace from Boarding Points</h4>";
$trimStmt = $conn->query("UPDATE bookings SET boarding_point = TRIM(boarding_point) WHERE boarding_point != TRIM(boarding_point)");
if ($trimStmt) {
    // Note: affected_rows doesn't work with UPDATE queries in this context
    echo "<p>✅ Trimmed whitespace from boarding points</p>";
} else {
    echo "<p>❌ Error trimming boarding points: " . $conn->error . "</p>";
}

// 4. Trim whitespace from admin stations
echo "<h4>4. Trimming Whitespace from Admin Stations</h4>";
$trimAdminStmt = $conn->query("UPDATE users SET station = TRIM(station) WHERE station != TRIM(station) AND role = 'admin'");
if ($trimAdminStmt) {
    echo "<p>✅ Trimmed whitespace from admin stations</p>";
} else {
    echo "<p>❌ Error trimming admin stations: " . $conn->error . "</p>";
}

// 5. Verify the fixes
echo "<h3>Verification After Fixes</h3>";

// Check admins and their stations
echo "<h4>Admins and Their Stations</h4>";
$adminStmt = $conn->query("SELECT id, name, email, station FROM users WHERE role = 'admin' ORDER BY station");
if ($adminStmt && $adminStmt->num_rows > 0) {
    echo "<table border='1' cellpadding='8' cellspacing='0'>";
    echo "<tr><th>Admin ID</th><th>Name</th><th>Email</th><th>Station</th></tr>";
    while ($admin = $adminStmt->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($admin['id']) . "</td>";
        echo "<td>" . htmlspecialchars($admin['name']) . "</td>";
        echo "<td>" . htmlspecialchars($admin['email']) . "</td>";
        echo "<td>" . htmlspecialchars($admin['station'] ?? '<em>Not set</em>') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check bookings and their boarding points
echo "<h4>Today's Bookings and Their Boarding Points</h4>";
$bookingStmt = $conn->query("SELECT booking_id, fullname, route, boarding_point FROM bookings WHERE DATE(created_at) = CURDATE() ORDER BY boarding_point, booking_id DESC");
if ($bookingStmt && $bookingStmt->num_rows > 0) {
    echo "<table border='1' cellpadding='8' cellspacing='0'>";
    echo "<tr><th>Booking ID</th><th>Passenger</th><th>Route</th><th>Boarding Point</th></tr>";
    while ($booking = $bookingStmt->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($booking['booking_id']) . "</td>";
        echo "<td>" . htmlspecialchars($booking['fullname']) . "</td>";
        echo "<td>" . htmlspecialchars($booking['route']) . "</td>";
        echo "<td>" . htmlspecialchars($booking['boarding_point']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();

echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>Run the <a href='diagnose_station_filtering.php'>diagnose_station_filtering.php</a> script again to verify the fixes</li>";
echo "<li>If the Litein admin still doesn't see bookings, check that:</li>";
echo "<ul>";
echo "<li>The admin's station is exactly 'Litein'</li>";
echo "<li>The booking's boarding point is exactly 'Litein'</li>";
echo "<li>Both are spelled the same way with the same case</li>";
echo "</ul>";
echo "<li>Try creating a new test booking with the exact values</li>";
echo "</ol>";
?>