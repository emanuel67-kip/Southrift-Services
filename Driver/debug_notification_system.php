<?php
// Debug the enhanced notification system
require_once '../db.php';

session_start();

$driver_phone = $_SESSION['phone'] ?? '';
echo "Driver phone from session: " . htmlspecialchars($driver_phone) . "<br>";

if (empty($driver_phone)) {
    echo "No driver phone in session<br>";
    exit;
}

try {
    // Test driver ID resolution
    echo "<h3>Testing Driver ID Resolution:</h3>";
    
    // Method 1: Try driver_phone field first
    $stmt = $conn->prepare("SELECT id, name, driver_phone, phone FROM drivers WHERE driver_phone = ? LIMIT 1");
    $stmt->bind_param('s', $driver_phone);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        echo "Found driver by driver_phone: " . print_r($row, true) . "<br>";
        $driver_id = (int)$row['id'];
    } else {
        echo "Not found by driver_phone field<br>";
        
        // Method 2: Try phone field as fallback
        $stmt = $conn->prepare("SELECT id, name, driver_phone, phone FROM drivers WHERE phone = ? LIMIT 1");
        $stmt->bind_param('s', $driver_phone);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            echo "Found driver by phone: " . print_r($row, true) . "<br>";
            $driver_id = (int)$row['id'];
        } else {
            echo "Not found by phone field either<br>";
            exit;
        }
    }
    
    echo "Final driver_id: $driver_id<br>";
    
    // Test tables exist
    echo "<h3>Testing Tables:</h3>";
    
    // Check driver_locations table
    $result = $conn->query("DESCRIBE driver_locations");
    if ($result) {
        echo "✅ driver_locations table exists<br>";
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        echo "Columns: " . implode(', ', $columns) . "<br>";
    } else {
        echo "❌ driver_locations table missing<br>";
    }
    
    // Check driver_share_sessions table
    $result = $conn->query("DESCRIBE driver_share_sessions");
    if ($result) {
        echo "✅ driver_share_sessions table exists<br>";
    } else {
        echo "❌ driver_share_sessions table missing: " . $conn->error . "<br>";
    }
    
    // Check notifications table
    $result = $conn->query("DESCRIBE notifications");
    if ($result) {
        echo "✅ notifications table exists<br>";
    } else {
        echo "❌ notifications table missing<br>";
    }
    
    // Test finding passengers for this driver
    echo "<h3>Testing Passenger Finding:</h3>";
    
    $notification_stmt = $conn->prepare("
        SELECT DISTINCT 
            b.user_id, 
            u.name as passenger_name, 
            u.phone as passenger_phone,
            b.pickup_location,
            b.destination,
            b.status as booking_status,
            v.number_plate,
            v.type as vehicle_type
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN vehicles v ON b.assigned_vehicle = v.number_plate
        WHERE (v.driver_phone = ? OR v.driver_phone = ?)
        AND DATE(b.travel_date) = CURDATE()
        AND b.status IN ('assigned', 'picked_up')
    ");
    $notification_stmt->bind_param('ss', $driver_phone, $driver_phone);
    $notification_stmt->execute();
    $passengers_result = $notification_stmt->get_result();
    
    echo "Found " . $passengers_result->num_rows . " passengers for today<br>";
    
    while ($passenger = $passengers_result->fetch_assoc()) {
        echo "Passenger: " . print_r($passenger, true) . "<br>";
    }
    
    if ($passengers_result->num_rows == 0) {
        echo "<strong>No passengers found for today. This might be why notifications fail.</strong><br>";
        
        // Check all bookings for this driver
        $all_bookings_stmt = $conn->prepare("
            SELECT b.*, v.number_plate, v.type as vehicle_type
            FROM bookings b
            JOIN vehicles v ON b.assigned_vehicle = v.number_plate
            WHERE v.driver_phone = ?
            ORDER BY b.travel_date DESC
            LIMIT 5
        ");
        $all_bookings_stmt->bind_param('s', $driver_phone);
        $all_bookings_stmt->execute();
        $all_bookings_result = $all_bookings_stmt->get_result();
        
        echo "<h4>Recent bookings for this driver:</h4>";
        while ($booking = $all_bookings_result->fetch_assoc()) {
            echo "Booking: " . print_r($booking, true) . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>