<?php
// Simple debug without authentication requirements
require dirname(__DIR__) . '/db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== WhatsApp Driver Information Debug (No Auth) ===\n\n";

try {
    // Check what vehicles exist in the system
    echo "1. All vehicles in the system:\n";
    $all_vehicles = $conn->query("SELECT driver_phone, driver_name, number_plate, type, color FROM vehicles ORDER BY driver_phone");
    
    if ($all_vehicles && $all_vehicles->num_rows > 0) {
        while ($v = $all_vehicles->fetch_assoc()) {
            echo "   Phone: '{$v['driver_phone']}' | Name: '{$v['driver_name']}' | Plate: '{$v['number_plate']}' | Type: '{$v['type']}' | Color: '{$v['color']}'\n";
        }
    } else {
        echo "   ❌ No vehicles found in the system\n";
    }

    // Check today's bookings
    echo "\n2. Today's bookings:\n";
    $bookings = $conn->query("
        SELECT b.booking_id, b.fullname, b.phone, b.assigned_vehicle, DATE(b.created_at) as booking_date
        FROM bookings b
        WHERE DATE(b.created_at) = CURDATE()
        ORDER BY b.booking_id
    ");
    
    if ($bookings && $bookings->num_rows > 0) {
        while ($booking = $bookings->fetch_assoc()) {
            echo "   Booking #{$booking['booking_id']}: {$booking['fullname']} | Phone: '{$booking['phone']}' | Vehicle: '{$booking['assigned_vehicle']}' | Date: {$booking['booking_date']}\n";
        }
    } else {
        echo "   ❌ No bookings found for today\n";
    }

    // Test the WhatsApp query specifically for driver phone 0736225373
    echo "\n3. Testing WhatsApp query for driver phone '0736225373':\n";
    $test_phone = '0736225373';
    
    $test_stmt = $conn->prepare("
        SELECT DISTINCT b.user_id, b.fullname, b.phone as user_phone
        FROM bookings b
        JOIN vehicles v ON b.assigned_vehicle = v.number_plate
        WHERE v.driver_phone = ?
        AND DATE(b.created_at) = CURDATE()
        AND b.phone IS NOT NULL
        AND b.phone != ''
    ");
    
    if ($test_stmt) {
        $test_stmt->bind_param('s', $test_phone);
        $test_stmt->execute();
        $result = $test_stmt->get_result();
        $passengers = $result->fetch_all(MYSQLI_ASSOC);
        
        echo "   Query executed successfully. Found " . count($passengers) . " passengers:\n";
        
        if (count($passengers) > 0) {
            foreach ($passengers as $passenger) {
                echo "      - {$passenger['fullname']} | Phone: '{$passenger['user_phone']}' | User ID: {$passenger['user_id']}\n";
            }
        } else {
            echo "      ❌ No passengers found for driver phone '$test_phone'\n";
            
            // Let's debug why
            echo "\n   Debug - Step by step:\n";
            
            // Check if vehicle exists for this phone
            $vehicle_check = $conn->prepare("SELECT * FROM vehicles WHERE driver_phone = ?");
            $vehicle_check->bind_param('s', $test_phone);
            $vehicle_check->execute();
            $vehicle_result = $vehicle_check->get_result();
            
            if ($vehicle_result->num_rows > 0) {
                $vehicle = $vehicle_result->fetch_assoc();
                echo "      ✅ Vehicle found: {$vehicle['number_plate']} - {$vehicle['driver_name']}\n";
                
                // Check bookings for this vehicle
                $booking_check = $conn->prepare("
                    SELECT * FROM bookings 
                    WHERE assigned_vehicle = ? 
                    AND DATE(created_at) = CURDATE()
                ");
                $booking_check->bind_param('s', $vehicle['number_plate']);
                $booking_check->execute();
                $booking_result = $booking_check->get_result();
                
                if ($booking_result->num_rows > 0) {
                    echo "      ✅ Bookings found for vehicle {$vehicle['number_plate']}:\n";
                    while ($booking = $booking_result->fetch_assoc()) {
                        $phone_status = empty($booking['phone']) ? "❌ NO PHONE" : "✅ Phone: {$booking['phone']}";
                        echo "         - Booking #{$booking['booking_id']}: {$booking['fullname']} | $phone_status\n";
                    }
                } else {
                    echo "      ❌ No bookings found for vehicle {$vehicle['number_plate']} today\n";
                }
            } else {
                echo "      ❌ No vehicle found for driver phone '$test_phone'\n";
            }
        }
    } else {
        echo "   ❌ Failed to prepare test query: " . $conn->error . "\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";
?>