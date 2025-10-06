<?php
require_once 'db.php';

echo "<h2>Diagnose Station-Based Booking Filtering Issues</h2>";

// 1. Check all admins and their stations
echo "<h3>1. Admins and Their Assigned Stations</h3>";
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
} else {
    echo "<p>No admins found or query failed: " . $conn->error . "</p>";
}

// 2. Check all bookings from today
echo "<h3>2. All Bookings Created Today</h3>";
$bookingStmt = $conn->query("SELECT booking_id, fullname, phone, route, boarding_point, created_at FROM bookings WHERE DATE(created_at) = CURDATE() ORDER BY booking_id DESC");
if ($bookingStmt && $bookingStmt->num_rows > 0) {
    echo "<table border='1' cellpadding='8' cellspacing='0'>";
    echo "<tr><th>Booking ID</th><th>Passenger</th><th>Phone</th><th>Route</th><th>Boarding Point</th><th>Created At</th></tr>";
    while ($booking = $bookingStmt->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($booking['booking_id']) . "</td>";
        echo "<td>" . htmlspecialchars($booking['fullname']) . "</td>";
        echo "<td>" . htmlspecialchars($booking['phone']) . "</td>";
        echo "<td>" . htmlspecialchars($booking['route']) . "</td>";
        echo "<td style='background: " . (strtolower(trim($booking['boarding_point'])) === 'litein' ? '#e7f9ef' : '#fdecea') . "'>" . htmlspecialchars($booking['boarding_point']) . "</td>";
        echo "<td>" . htmlspecialchars($booking['created_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Count bookings by boarding point
    echo "<h4>Booking Count by Boarding Point</h4>";
    $countStmt = $conn->query("SELECT boarding_point, COUNT(*) as count FROM bookings WHERE DATE(created_at) = CURDATE() GROUP BY boarding_point");
    if ($countStmt && $countStmt->num_rows > 0) {
        echo "<ul>";
        while ($count = $countStmt->fetch_assoc()) {
            echo "<li><strong>" . htmlspecialchars($count['boarding_point']) . ":</strong> " . $count['count'] . " booking(s)</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p>No bookings found for today or query failed: " . $conn->error . "</p>";
}

// 3. Test specific queries for each station
echo "<h3>3. Station-Specific Booking Queries (What Each Admin Would See)</h3>";
$stations = ['Nairobi', 'Litein', 'Kisumu', 'Nakuru', 'Bomet'];

foreach ($stations as $station) {
    echo "<h4>Bookings for $station Station:</h4>";
    $stmt = $conn->prepare("SELECT booking_id, fullname, route, boarding_point FROM bookings WHERE DATE(created_at) = CURDATE() AND boarding_point = ? ORDER BY booking_id DESC");
    $stmt->bind_param("s", $station);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            echo "<ul>";
            while ($row = $result->fetch_assoc()) {
                echo "<li>" . htmlspecialchars($row['fullname']) . " - " . htmlspecialchars($row['route']) . " (Boarding: " . htmlspecialchars($row['boarding_point']) . ")</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: #666;'>No bookings found for $station station today.</p>";
        }
    } else {
        echo "<p style='color: red;'>Query failed: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

// 4. Check for case sensitivity issues
echo "<h3>4. Case Sensitivity Check for Boarding Points</h3>";
$caseStmt = $conn->query("SELECT DISTINCT boarding_point FROM bookings WHERE DATE(created_at) = CURDATE()");
if ($caseStmt && $caseStmt->num_rows > 0) {
    echo "<ul>";
    while ($row = $caseStmt->fetch_assoc()) {
        $boardingPoint = $row['boarding_point'];
        echo "<li>";
        echo "Boarding Point: <strong>" . htmlspecialchars($boardingPoint) . "</strong> ";
        echo "(Length: " . strlen($boardingPoint) . ", ";
        echo "Trimmed: '" . htmlspecialchars(trim($boardingPoint)) . "', ";
        echo "Lowercase: '" . htmlspecialchars(strtolower(trim($boardingPoint))) . "')";
        echo "</li>";
    }
    echo "</ul>";
}

// 5. Check for extra spaces or special characters
echo "<h3>5. Boarding Point Data Analysis</h3>";
$analysisStmt = $conn->query("SELECT boarding_point, HEX(boarding_point) as hex_value FROM bookings WHERE DATE(created_at) = CURDATE() GROUP BY boarding_point");
if ($analysisStmt && $analysisStmt->num_rows > 0) {
    echo "<table border='1' cellpadding='8' cellspacing='0'>";
    echo "<tr><th>Boarding Point</th><th>HEX Value</th><th>Character Analysis</th></tr>";
    while ($row = $analysisStmt->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['boarding_point']) . "</td>";
        echo "<td>" . $row['hex_value'] . "</td>";
        echo "<td>";
        // Check for common issues
        $bp = $row['boarding_point'];
        if (trim($bp) !== $bp) {
            echo "Contains extra spaces. ";
        }
        if (strtolower(trim($bp)) === 'litein') {
            echo "<span style='color: green;'>✅ Matches 'litein'</span>";
        } else {
            echo "<span style='color: red;'>❌ Does not match 'litein'</span>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();

echo "<h3>Troubleshooting Steps</h3>";
echo "<ol>";
echo "<li><strong>Check Admin Station Assignment:</strong> Ensure the Litein admin has 'Litein' exactly as their station value</li>";
echo "<li><strong>Check Booking Boarding Point:</strong> Ensure the booking was created with 'Litein' exactly as the boarding point</li>";
echo "<li><strong>Case Sensitivity:</strong> The system is case-sensitive, so 'Litein' ≠ 'litein' ≠ 'LITEIN'</li>";
echo "<li><strong>Extra Spaces:</strong> Check for leading/trailing spaces in boarding point values</li>";
echo "<li><strong>Special Characters:</strong> Check for hidden characters in boarding point values</li>";
echo "</ol>";

echo "<p>If you're still having issues, please share:</p>";
echo "<ul>";
echo "<li>The exact boarding point value used when creating the booking</li>";
echo "<li>The exact station value assigned to the Litein admin</li>";
echo "<li>Any error messages you're seeing</li>";
echo "</ul>";
?>