<?php
// Test script to verify bookings display
session_start();
$_SESSION['user_id'] = 1; // Test with user ID 1

require_once 'db.php';

echo "<h1>Bookings Display Test</h1>\n";

try {
    // Test fetching bookings for user
    $stmt = $conn->prepare("SELECT 
        booking_id,
        fullname,
        phone,
        route,
        boarding_point,
        travel_date,
        departure_time,
        seats,
        payment_method,
        assigned_vehicle,
        created_at,
        google_maps_link
        FROM bookings 
        WHERE user_id = ? 
        ORDER BY created_at DESC");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $user_id = 1;
    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<h2>Bookings found:</h2>\n";
        echo "<table border='1'>\n";
        echo "<tr><th>ID</th><th>Route</th><th>Date</th><th>Seats</th><th>Status</th></tr>\n";
        
        while ($row = $result->fetch_assoc()) {
            // Determine status
            $travel_date = new DateTime($row['travel_date']);
            $today = new DateTime();
            
            if ($travel_date > $today) {
                $status = 'Upcoming';
            } else if ($travel_date < $today) {
                $status = 'Completed';
            } else {
                $status = 'Today';
            }
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['booking_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['route']) . "</td>";
            echo "<td>" . htmlspecialchars($row['travel_date']) . "</td>";
            echo "<td>" . htmlspecialchars($row['seats']) . "</td>";
            echo "<td>" . htmlspecialchars($status) . "</td>";
            echo "</tr>\n";
        }
        
        echo "</table>\n";
    } else {
        echo "<p>No bookings found for user ID: $user_id</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>