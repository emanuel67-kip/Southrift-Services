<?php
require_once 'db.php';

echo "<h2>Update All Booking Routes</h2>";

// Define the correct route mappings
$routeMappings = [
    'litein-nairobi' => 'litein-nairobi',
    'nairobi-kisumu' => 'nairobi-kisumu',
    'nairobi-nakuru' => 'nairobi-nakuru',
    'kisumu-nairobi' => 'kisumu-nairobi',
    'nakuru-nairobi' => 'nakuru-nairobi',
    'bomet-nairobi' => 'bomet-nairobi',
    'nairobi-bomet' => 'nairobi-bomet'
];

// Get all bookings
echo "<h3>Checking All Bookings</h3>";
$stmt = $conn->query("SELECT booking_id, fullname, route FROM bookings ORDER BY booking_id");
if ($stmt && $stmt->num_rows > 0) {
    $updatedCount = 0;
    $skippedCount = 0;
    
    while ($row = $stmt->fetch_assoc()) {
        $bookingId = $row['booking_id'];
        $currentRoute = strtolower(trim($row['route']));
        $passenger = $row['fullname'];
        
        // Check if we need to update this route
        if (isset($routeMappings[$currentRoute])) {
            // Route is already correct
            echo "<p>✅ Booking $bookingId ($passenger): Route '$currentRoute' is already correct</p>";
            $skippedCount++;
        } else {
            // Try to determine the correct route
            $correctRoute = null;
            
            // Handle common variations
            if (strpos($currentRoute, 'litein') !== false && strpos($currentRoute, 'nairobi') !== false) {
                $correctRoute = 'litein-nairobi';
            } elseif (strpos($currentRoute, 'nairobi') !== false && strpos($currentRoute, 'kisumu') !== false) {
                $correctRoute = 'nairobi-kisumu';
            } elseif (strpos($currentRoute, 'nairobi') !== false && strpos($currentRoute, 'nakuru') !== false) {
                $correctRoute = 'nairobi-nakuru';
            } elseif (strpos($currentRoute, 'kisumu') !== false && strpos($currentRoute, 'nairobi') !== false) {
                $correctRoute = 'kisumu-nairobi';
            } elseif (strpos($currentRoute, 'nakuru') !== false && strpos($currentRoute, 'nairobi') !== false) {
                $correctRoute = 'nakuru-nairobi';
            } elseif (strpos($currentRoute, 'bomet') !== false && strpos($currentRoute, 'nairobi') !== false) {
                $correctRoute = 'bomet-nairobi';
            } elseif (strpos($currentRoute, 'nairobi') !== false && strpos($currentRoute, 'bomet') !== false) {
                $correctRoute = 'nairobi-bomet';
            }
            
            if ($correctRoute) {
                // Update the route
                $updateStmt = $conn->prepare("UPDATE bookings SET route = ? WHERE booking_id = ?");
                $updateStmt->bind_param("si", $correctRoute, $bookingId);
                
                if ($updateStmt->execute()) {
                    echo "<p>✅ Booking $bookingId ($passenger): Updated route from '$currentRoute' to '$correctRoute'</p>";
                    $updatedCount++;
                } else {
                    echo "<p>❌ Booking $bookingId ($passenger): Failed to update route: " . $conn->error . "</p>";
                }
                $updateStmt->close();
            } else {
                echo "<p>⚠️ Booking $bookingId ($passenger): Could not determine correct route for '$currentRoute'</p>";
                $skippedCount++;
            }
        }
    }
    
    echo "<h3>Summary</h3>";
    echo "<p>✅ Updated: $updatedCount booking(s)</p>";
    echo "<p>✅ Skipped (already correct): $skippedCount booking(s)</p>";
} else {
    echo "<p>No bookings found in the database.</p>";
}

$stmt->close();

// Show all current routes
echo "<h3>Current Routes in Database</h3>";
$routeStmt = $conn->query("SELECT route, COUNT(*) as count FROM bookings GROUP BY route ORDER BY count DESC");
if ($routeStmt && $routeStmt->num_rows > 0) {
    echo "<table border='1' cellpadding='8' cellspacing='0'>";
    echo "<tr><th>Route</th><th>Count</th></tr>";
    while ($routeRow = $routeStmt->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($routeRow['route']) . "</td>";
        echo "<td>" . $routeRow['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
$routeStmt->close();

$conn->close();

echo "<h3>Verification</h3>";
echo "<p>Run the <a href='diagnose_station_filtering.php'>diagnose_station_filtering.php</a> script again to verify that the station-based filtering is now working correctly.</p>";
?>