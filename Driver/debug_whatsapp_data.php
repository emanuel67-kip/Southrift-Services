<?php
require_once '../db.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>Debug: Database State for WhatsApp Location</h2>";

try {
    echo "<h3>1. Current Bookings (Today)</h3>";
    $today_sql = "
        SELECT b.*, u.name, u.phone, v.driver_name, v.driver_phone, v.number_plate, v.type 
        FROM bookings b 
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN vehicles v ON b.assigned_vehicle = v.number_plate 
        WHERE DATE(b.travel_date) = CURDATE()
        ORDER BY b.booking_id
    ";
    $result = $conn->query($today_sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Booking ID</th><th>User ID</th><th>Passenger</th><th>Phone</th><th>Vehicle</th><th>Driver</th><th>Driver Phone</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['booking_id'] . "</td>";
            echo "<td>" . $row['user_id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . $row['phone'] . "</td>";
            echo "<td>" . $row['number_plate'] . " (" . $row['type'] . ")</td>";
            echo "<td>" . $row['driver_name'] . "</td>";
            echo "<td>" . $row['driver_phone'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No bookings found for today!</p>";
        
        echo "<h4>All bookings in database:</h4>";
        $all_sql = "SELECT booking_id, user_id, fullname, travel_date, assigned_vehicle FROM bookings ORDER BY booking_id LIMIT 5";
        $all_result = $conn->query($all_sql);
        if ($all_result && $all_result->num_rows > 0) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>User ID</th><th>Name</th><th>Date</th><th>Vehicle</th></tr>";
            while ($row = $all_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['booking_id'] . "</td>";
                echo "<td>" . $row['user_id'] . "</td>";
                echo "<td>" . $row['fullname'] . "</td>";
                echo "<td>" . $row['travel_date'] . "</td>";
                echo "<td>" . $row['assigned_vehicle'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<p><strong>Action:</strong> <a href='../update_booking_for_testing.php'>Update a booking to today's date</a></p>";
        }
    }
    
    echo "<h3>2. Available Vehicles</h3>";
    $vehicles_sql = "SELECT number_plate, driver_name, driver_phone, type, color FROM vehicles ORDER BY number_plate";
    $vehicles_result = $conn->query($vehicles_sql);
    
    if ($vehicles_result && $vehicles_result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Plate</th><th>Driver</th><th>Phone</th><th>Type</th><th>Color</th></tr>";
        while ($row = $vehicles_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['number_plate'] . "</td>";
            echo "<td>" . $row['driver_name'] . "</td>";
            echo "<td>" . $row['driver_phone'] . "</td>";
            echo "<td>" . $row['type'] . "</td>";
            echo "<td>" . $row['color'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No vehicles found!</p>";
    }
    
    echo "<h3>3. Available Users</h3>";
    $users_sql = "SELECT id, name, phone FROM users WHERE phone IS NOT NULL AND phone != '' ORDER BY id LIMIT 5";
    $users_result = $conn->query($users_sql);
    
    if ($users_result && $users_result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Phone</th></tr>";
        while ($row = $users_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . $row['phone'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>4. Current Driver Session Info</h3>";
    session_start();
    echo "<p><strong>Session Phone:</strong> " . ($_SESSION['phone'] ?? 'NOT_SET') . "</p>";
    echo "<p><strong>Session Name:</strong> " . ($_SESSION['name'] ?? 'NOT_SET') . "</p>";
    echo "<p><strong>Session Role:</strong> " . ($_SESSION['role'] ?? 'NOT_SET') . "</p>";
    
    if (isset($_SESSION['phone'])) {
        echo "<h4>Test Query for Current Driver:</h4>";
        $test_sql = "
            SELECT DISTINCT u.id as user_id, u.name, u.phone, b.pickup_location, b.destination
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN vehicles v ON b.assigned_vehicle = v.number_plate
            WHERE v.driver_phone = ?
            AND DATE(b.travel_date) = CURDATE()
            AND u.phone IS NOT NULL
            AND u.phone != ''
        ";
        
        $stmt = $conn->prepare($test_sql);
        $stmt->bind_param('s', $_SESSION['phone']);
        $stmt->execute();
        $test_result = $stmt->get_result();
        
        echo "<p><strong>Query for driver phone:</strong> " . $_SESSION['phone'] . "</p>";
        echo "<p><strong>Results found:</strong> " . $test_result->num_rows . "</p>";
        
        if ($test_result->num_rows > 0) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>User ID</th><th>Name</th><th>Phone</th><th>Pickup</th><th>Destination</th></tr>";
            while ($row = $test_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['user_id'] . "</td>";
                echo "<td>" . $row['name'] . "</td>";
                echo "<td>" . $row['phone'] . "</td>";
                echo "<td>" . $row['pickup_location'] . "</td>";
                echo "<td>" . $row['destination'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #6A0DAD; }
table { margin: 10px 0; }
th { background: #6A0DAD; color: white; padding: 8px; }
td { padding: 8px; }
tr:nth-child(even) { background: #f9f9f9; }
a { color: #6A0DAD; }
</style>