<?php
session_start();
require __DIR__ . '/auth.php';
require dirname(__DIR__) . '/db.php';

header('Content-Type: application/json');

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$driver_phone = $_SESSION['phone'] ?? '';

if (empty($driver_phone)) {
    echo json_encode(['success' => false, 'error' => 'Driver not logged in']);
    exit;
}

try {
    // Get assigned passengers for today
    $passengers = [];
    
    // First, get all vehicles assigned to this driver
    $vehicle_stmt = $conn->prepare("SELECT number_plate FROM vehicles WHERE driver_phone = ?");
    $vehicle_stmt->bind_param("s", $driver_phone);
    $vehicle_stmt->execute();
    $vehicle_result = $vehicle_stmt->get_result();
    
    $vehicles = [];
    while ($row = $vehicle_result->fetch_assoc()) {
        $vehicles[] = $row['number_plate'];
    }
    
    if (!empty($vehicles)) {
        // Get today's bookings for all driver's vehicles
        $placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
        
        $stmt = $conn->prepare("
            SELECT 
                b.user_id, 
                b.fullname, 
                b.phone, 
                b.booking_id,
                b.pickup_location,
                b.destination,
                b.travel_date,
                b.status as booking_status
            FROM bookings b
            WHERE b.assigned_vehicle IN ($placeholders)
            AND DATE(b.created_at) = CURDATE()
            AND b.phone IS NOT NULL
            AND b.phone != ''
            AND b.status IN ('confirmed', 'assigned', 'picked_up')
            ORDER BY b.booking_id
        ");
        
        if ($stmt) {
            $types = str_repeat('s', count($vehicles));
            $stmt->bind_param($types, ...$vehicles);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $passengers[] = [
                    'user_id' => $row['user_id'],
                    'fullname' => $row['fullname'],
                    'name' => $row['fullname'], // For compatibility
                    'phone' => $row['phone'],
                    'booking_id' => $row['booking_id'],
                    'pickup_location' => $row['pickup_location'],
                    'destination' => $row['destination'],
                    'travel_date' => $row['travel_date'],
                    'booking_status' => $row['booking_status']
                ];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'passengers' => $passengers,
        'total_count' => count($passengers),
        'vehicle_count' => count($vehicles)
    ]);
    
} catch (Exception $e) {
    error_log("Get assigned passengers error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to load passengers: ' . $e->getMessage()
    ]);
}
?>