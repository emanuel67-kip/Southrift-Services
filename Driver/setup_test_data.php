<?php
require_once '../db.php';

echo "Setting up test data for WhatsApp location sharing...\n\n";

try {
    // First, let's check what we have
    echo "=== CURRENT DATA ===\n";
    
    // Check bookings
    $result = $conn->query("SELECT COUNT(*) as count FROM bookings");
    $count = $result->fetch_assoc()['count'];
    echo "Bookings in database: $count\n";
    
    // Check vehicles
    $result = $conn->query("SELECT COUNT(*) as count FROM vehicles");
    $count = $result->fetch_assoc()['count'];
    echo "Vehicles in database: $count\n";
    
    // Check users
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $count = $result->fetch_assoc()['count'];
    echo "Users in database: $count\n";
    
    echo "\n=== UPDATING TEST DATA ===\n";
    
    // Update the first booking to today's date
    $today = date('Y-m-d');
    $update_sql = "UPDATE bookings SET travel_date = ? WHERE booking_id = 1";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param('s', $today);
    
    if ($stmt->execute()) {
        echo "✅ Updated booking ID 1 to today's date: $today\n";
        
        // Now check the updated booking with vehicle and user details
        $check_sql = "
            SELECT b.booking_id, b.user_id, b.fullname, b.travel_date, b.assigned_vehicle,
                   v.driver_name, v.driver_phone, v.number_plate, v.type,
                   u.name as user_name, u.phone as user_phone
            FROM bookings b 
            LEFT JOIN vehicles v ON b.assigned_vehicle = v.number_plate 
            LEFT JOIN users u ON b.user_id = u.id
            WHERE b.booking_id = 1
        ";
        
        $result = $conn->query($check_sql);
        if ($result && $row = $result->fetch_assoc()) {
            echo "\n=== TEST BOOKING DETAILS ===\n";
            echo "Booking ID: " . $row['booking_id'] . "\n";
            echo "Passenger: " . $row['fullname'] . " (User ID: " . $row['user_id'] . ")\n";
            echo "User Phone: " . ($row['user_phone'] ?? 'NOT_SET') . "\n";
            echo "Travel Date: " . $row['travel_date'] . "\n";
            echo "Vehicle: " . ($row['number_plate'] ?? 'NOT_SET') . " (" . ($row['type'] ?? 'NOT_SET') . ")\n";
            echo "Driver: " . ($row['driver_name'] ?? 'NOT_SET') . "\n";
            echo "Driver Phone: " . ($row['driver_phone'] ?? 'NOT_SET') . "\n";
            
            // Check if user has phone number
            if (empty($row['user_phone'])) {
                echo "\n⚠️  WARNING: User doesn't have a phone number!\n";
                echo "Updating user phone number...\n";
                
                $phone = '0712345678'; // Test phone number
                $update_user_sql = "UPDATE users SET phone = ? WHERE id = ?";
                $stmt = $conn->prepare($update_user_sql);
                $stmt->bind_param('si', $phone, $row['user_id']);
                
                if ($stmt->execute()) {
                    echo "✅ Updated user phone to: $phone\n";
                } else {
                    echo "❌ Failed to update user phone\n";
                }
            }
            
            echo "\n=== TEST INSTRUCTIONS ===\n";
            echo "1. Login as driver with phone: " . ($row['driver_phone'] ?? 'CHECK_VEHICLES_TABLE') . "\n";
            echo "2. Go to driver dashboard\n";
            echo "3. Click 'Send Location via WhatsApp' button\n";
            echo "4. Should find 1 passenger to send to\n";
            
        } else {
            echo "❌ Could not find booking details\n";
        }
        
    } else {
        echo "❌ Failed to update booking: " . $conn->error . "\n";
    }
    
    // Also show a sample of available data
    echo "\n=== SAMPLE VEHICLES ===\n";
    $vehicles = $conn->query("SELECT number_plate, driver_name, driver_phone FROM vehicles LIMIT 3");
    while ($vehicle = $vehicles->fetch_assoc()) {
        echo "Plate: " . $vehicle['number_plate'] . ", Driver: " . $vehicle['driver_name'] . ", Phone: " . $vehicle['driver_phone'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";
?>