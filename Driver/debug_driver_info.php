<?php
require __DIR__ . '/auth.php';
require dirname(__DIR__) . '/db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Driver Information Debug ===\n\n";

// Get driver phone from session
$driver_phone = $_SESSION['phone'] ?? '';
echo "1. Driver phone from session: '$driver_phone'\n\n";

// Check what's in the vehicles table for this driver
echo "2. Querying vehicles table...\n";
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE driver_phone = ?");
$stmt->bind_param("s", $driver_phone);
$stmt->execute();
$result = $stmt->get_result();
$vehicle = $result->fetch_assoc();

if ($vehicle) {
    echo "✅ Found vehicle record:\n";
    foreach ($vehicle as $key => $value) {
        echo "   $key: '$value'\n";
    }
} else {
    echo "❌ No vehicle found for driver phone: '$driver_phone'\n";
    
    // Show all vehicles in the table
    echo "\n3. All vehicles in the table:\n";
    $all_vehicles = $conn->query("SELECT driver_phone, driver_name, number_plate FROM vehicles");
    while ($v = $all_vehicles->fetch_assoc()) {
        echo "   Phone: '{$v['driver_phone']}' | Name: '{$v['driver_name']}' | Plate: '{$v['number_plate']}'\n";
    }
}

// Check bookings assigned to this driver's vehicle
echo "\n4. Checking bookings for this driver...\n";
if ($vehicle) {
    $booking_stmt = $conn->prepare("
        SELECT b.booking_id, b.fullname, b.phone, b.assigned_vehicle
        FROM bookings b
        WHERE b.assigned_vehicle = ?
        AND DATE(b.created_at) = CURDATE()
    ");
    $booking_stmt->bind_param("s", $vehicle['number_plate']);
    $booking_stmt->execute();
    $booking_result = $booking_stmt->get_result();
    
    $bookings = [];
    while ($booking = $booking_result->fetch_assoc()) {
        $bookings[] = $booking;
    }
    
    if (count($bookings) > 0) {
        echo "✅ Found " . count($bookings) . " bookings:\n";
        foreach ($bookings as $booking) {
            echo "   Booking {$booking['booking_id']}: {$booking['fullname']} | Phone: {$booking['phone']} | Vehicle: {$booking['assigned_vehicle']}\n";
        }
    } else {
        echo "❌ No bookings found for vehicle: {$vehicle['number_plate']}\n";
    }
} else {
    echo "❌ Cannot check bookings - no vehicle found\n";
}

echo "\n=== Debug Complete ===\n";
?>