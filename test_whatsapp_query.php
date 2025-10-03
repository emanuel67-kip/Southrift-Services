<?php
require_once 'db.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name('southrift_admin');
    session_start();
}

echo "<h2>Direct WhatsApp Query Test</h2>";

$driver_phone = $_SESSION['phone'] ?? '072323443'; // Use session phone or fallback
echo "<p><strong>Testing with driver phone:</strong> $driver_phone</p>";

try {
    // Test the exact query used in WhatsApp sender
    $stmt = $conn->prepare("
        SELECT DISTINCT u.id as user_id, u.name, u.phone, b.fullname
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN vehicles v ON b.assigned_vehicle = v.number_plate
        WHERE v.driver_phone = ?
        AND DATE(b.travel_date) = CURDATE()
        AND u.phone IS NOT NULL
        AND u.phone != ''
    ");
    
    $stmt->bind_param('s', $driver_phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<p><strong>Passengers found:</strong> " . $result->num_rows . "</p>";
    
    if ($result->num_rows > 0) {
        echo "<h3>✅ SUCCESS! Passengers found:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #25D366; color: white;'><th>User ID</th><th>Name</th><th>Phone</th><th>Passenger Name</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['user_id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . $row['phone'] . "</td>";
            echo "<td>" . $row['fullname'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4 style='color: #155724;'>The query works! WhatsApp should work now.</h4>";
        echo "<p>If you're still getting the error, there might be a session or parameter issue.</p>";
        echo "</div>";
    } else {
        echo "<h3>❌ No passengers found. Debugging...</h3>";
        
        // Debug step by step
        echo "<h4>Debug Information:</h4>";
        
        // 1. Check bookings for today
        $bookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE DATE(travel_date) = CURDATE()");
        $booking_count = $bookings->fetch_assoc()['count'];
        echo "<p>1. Bookings for today: <strong>$booking_count</strong></p>";
        
        // 2. Check vehicles with this driver phone
        $vehicles = $conn->prepare("SELECT COUNT(*) as count FROM vehicles WHERE driver_phone = ?");
        $vehicles->bind_param('s', $driver_phone);
        $vehicles->execute();
        $vehicle_count = $vehicles->get_result()->fetch_assoc()['count'];
        echo "<p>2. Vehicles with driver phone '$driver_phone': <strong>$vehicle_count</strong></p>";
        
        // 3. Check users with phones
        $users = $conn->query("SELECT COUNT(*) as count FROM users WHERE phone IS NOT NULL AND phone != ''");
        $user_count = $users->fetch_assoc()['count'];
        echo "<p>3. Users with phone numbers: <strong>$user_count</strong></p>";
        
        // 4. Show what vehicles exist
        echo "<h4>Available Vehicles:</h4>";
        $all_vehicles = $conn->query("SELECT number_plate, driver_name, driver_phone FROM vehicles");
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr style='background: #6A0DAD; color: white;'><th>Plate</th><th>Driver</th><th>Phone</th></tr>";
        while ($v = $all_vehicles->fetch_assoc()) {
            $highlight = ($v['driver_phone'] == $driver_phone) ? "style='background: yellow;'" : "";
            echo "<tr $highlight>";
            echo "<td>" . $v['number_plate'] . "</td>";
            echo "<td>" . $v['driver_name'] . "</td>";
            echo "<td>" . $v['driver_phone'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 5. Show bookings for today
        echo "<h4>Bookings for Today:</h4>";
        $today_bookings = $conn->query("
            SELECT b.booking_id, b.user_id, b.fullname, b.assigned_vehicle, u.phone as user_phone
            FROM bookings b 
            LEFT JOIN users u ON b.user_id = u.id
            WHERE DATE(b.travel_date) = CURDATE()
        ");
        
        if ($today_bookings->num_rows > 0) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr style='background: #6A0DAD; color: white;'><th>Booking ID</th><th>User ID</th><th>Passenger</th><th>Vehicle</th><th>User Phone</th></tr>";
            while ($b = $today_bookings->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $b['booking_id'] . "</td>";
                echo "<td>" . $b['user_id'] . "</td>";
                echo "<td>" . $b['fullname'] . "</td>";
                echo "<td>" . $b['assigned_vehicle'] . "</td>";
                echo "<td>" . ($b['user_phone'] ?? 'NO_PHONE') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No bookings found for today!</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #6A0DAD; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
tr:nth-child(even) { background: #f9f9f9; }
</style>