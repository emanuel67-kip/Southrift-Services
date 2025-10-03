<?php
require_once '../db.php';

// Set content type for better output
header('Content-Type: text/plain; charset=utf-8');

echo "WhatsApp Location Test Data Setup\n";
echo "==================================\n\n";

try {
    // Step 1: Create a test user if none exists
    echo "1. Checking/Creating test user...\n";
    $user_check = $conn->query("SELECT id, name, phone FROM users WHERE phone IS NOT NULL LIMIT 1");
    
    if ($user_check->num_rows === 0) {
        // Create a test user
        $insert_user = "INSERT INTO users (name, phone, email, password) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_user);
        $name = "Test Passenger";
        $phone = "0712345678";
        $email = "test@passenger.com";
        $password = password_hash("password123", PASSWORD_DEFAULT);
        $stmt->bind_param('ssss', $name, $phone, $email, $password);
        
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            echo "✅ Created test user: ID $user_id, Phone: $phone\n";
        } else {
            echo "❌ Failed to create test user\n";
            exit;
        }
    } else {
        $user = $user_check->fetch_assoc();
        $user_id = $user['id'];
        echo "✅ Found existing user: ID $user_id, Phone: " . $user['phone'] . "\n";
    }
    
    // Step 2: Check/Create test vehicle
    echo "\n2. Checking/Creating test vehicle...\n";
    $vehicle_check = $conn->query("SELECT number_plate, driver_name, driver_phone FROM vehicles LIMIT 1");
    
    if ($vehicle_check->num_rows === 0) {
        // Create a test vehicle
        $insert_vehicle = "INSERT INTO vehicles (number_plate, driver_name, driver_phone, type, color, make, model) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_vehicle);
        $plate = "KCA 123A";
        $driver_name = "Test Driver";
        $driver_phone = "0798765432";
        $type = "Sedan";
        $color = "White";
        $make = "Toyota";
        $model = "Corolla";
        $stmt->bind_param('sssssss', $plate, $driver_name, $driver_phone, $type, $color, $make, $model);
        
        if ($stmt->execute()) {
            echo "✅ Created test vehicle: $plate, Driver: $driver_name, Phone: $driver_phone\n";
        } else {
            echo "❌ Failed to create test vehicle\n";
            exit;
        }
    } else {
        $vehicle = $vehicle_check->fetch_assoc();
        $plate = $vehicle['number_plate'];
        $driver_phone = $vehicle['driver_phone'];
        echo "✅ Found existing vehicle: $plate, Driver Phone: $driver_phone\n";
    }
    
    // Step 3: Create/Update test booking for today
    echo "\n3. Creating test booking for today...\n";
    $today = date('Y-m-d');
    
    // Check if booking exists for today
    $booking_check = $conn->prepare("SELECT booking_id FROM bookings WHERE user_id = ? AND assigned_vehicle = ? AND DATE(travel_date) = CURDATE()");
    $booking_check->bind_param('is', $user_id, $plate);
    $booking_check->execute();
    $existing_booking = $booking_check->get_result();
    
    if ($existing_booking->num_rows === 0) {
        // Create new booking
        $insert_booking = "INSERT INTO bookings (user_id, fullname, pickup_location, destination, travel_date, assigned_vehicle, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_booking);
        $fullname = "Test Passenger";
        $pickup = "Test Pickup Location";
        $destination = "Test Destination";
        $stmt->bind_param('isssss', $user_id, $fullname, $pickup, $destination, $today, $plate);
        
        if ($stmt->execute()) {
            $booking_id = $conn->insert_id;
            echo "✅ Created test booking: ID $booking_id for today ($today)\n";
        } else {
            echo "❌ Failed to create test booking: " . $conn->error . "\n";
            exit;
        }
    } else {
        $booking = $existing_booking->fetch_assoc();
        echo "✅ Found existing booking for today: ID " . $booking['booking_id'] . "\n";
    }
    
    // Step 4: Test the WhatsApp query
    echo "\n4. Testing WhatsApp passenger query...\n";
    $test_query = "
        SELECT DISTINCT u.id as user_id, u.name, u.phone, b.pickup_location, b.destination
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN vehicles v ON b.assigned_vehicle = v.number_plate
        WHERE v.driver_phone = ?
        AND DATE(b.travel_date) = CURDATE()
        AND u.phone IS NOT NULL
        AND u.phone != ''
    ";
    
    $stmt = $conn->prepare($test_query);
    $stmt->bind_param('s', $driver_phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "Query for driver phone: $driver_phone\n";
    echo "Passengers found: " . $result->num_rows . "\n";
    
    if ($result->num_rows > 0) {
        echo "\nPassenger details:\n";
        while ($row = $result->fetch_assoc()) {
            echo "- User ID: " . $row['user_id'] . "\n";
            echo "  Name: " . $row['name'] . "\n";
            echo "  Phone: " . $row['phone'] . "\n";
            echo "  Pickup: " . $row['pickup_location'] . "\n";
            echo "  Destination: " . $row['destination'] . "\n\n";
        }
        
        echo "✅ SUCCESS! WhatsApp location sharing should now work!\n\n";
        echo "=== TEST INSTRUCTIONS ===\n";
        echo "1. Login as driver with phone: $driver_phone\n";
        echo "2. Password: Use the driver login system\n";
        echo "3. Go to driver dashboard\n";
        echo "4. Click 'Send Location via WhatsApp' button\n";
        echo "5. Should find " . $result->num_rows . " passenger(s) to send to\n";
        
    } else {
        echo "❌ Still no passengers found. Debugging...\n";
        
        // Debug individual components
        echo "\nDebugging components:\n";
        
        // Check bookings for today
        $debug1 = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE DATE(travel_date) = CURDATE()");
        $count1 = $debug1->fetch_assoc()['count'];
        echo "- Bookings for today: $count1\n";
        
        // Check users with phones
        $debug2 = $conn->query("SELECT COUNT(*) as count FROM users WHERE phone IS NOT NULL AND phone != ''");
        $count2 = $debug2->fetch_assoc()['count'];
        echo "- Users with phone numbers: $count2\n";
        
        // Check vehicles with driver phones
        $debug3 = $conn->query("SELECT COUNT(*) as count FROM vehicles WHERE driver_phone IS NOT NULL AND driver_phone != ''");
        $count3 = $debug3->fetch_assoc()['count'];
        echo "- Vehicles with driver phones: $count3\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\nSetup complete!\n";
?>