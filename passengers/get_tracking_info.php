<?php
// Configure session to match the login system
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters to match login.php
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    ini_set('session.use_only_cookies', 1);
    
    // Set the same session name as login system
    session_name('southrift_admin');
    
    // Set session cookie parameters
    $lifetime = 60 * 60; // 1 hour for passengers
    $path = '/';
    $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $httponly = true;
    
    session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
    
    // Start the session
    session_start();
} else {
    session_start();
}

require_once 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if (!$booking_id) {
    echo json_encode(['success' => false, 'error' => 'Booking ID not provided']);
    exit();
}

// Get user's booking with assigned vehicle
$booking_stmt = $conn->prepare("
    SELECT b.*, v.driver_phone, v.driver_name, v.number_plate, v.type as vehicle_type, v.color
    FROM bookings b
    JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE b.user_id = ?
    AND b.booking_id = ?
    AND b.assigned_vehicle IS NOT NULL
    AND b.assigned_vehicle != ''
    AND DATE(b.travel_date) = CURDATE()
    ORDER BY b.created_at DESC
    LIMIT 1
");
$booking_stmt->bind_param('ii', $user_id, $booking_id);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();
$booking = $booking_result->fetch_assoc();

if (!$booking) {
    echo json_encode(['success' => false, 'error' => 'No active booking found for today with assigned vehicle']);
    exit();
}

$response = [
    'success' => true,
    'driver_info' => [
        'name' => $booking['driver_name'],
        'phone' => $booking['driver_phone'],
        'vehicle_plate' => $booking['number_plate'],
        'vehicle_type' => $booking['vehicle_type'],
        'vehicle_color' => $booking['color'] ?? 'N/A'
    ]
];

// Check if this booking has a direct Google Maps link attached by the driver
if (!empty($booking['google_maps_link'])) {
    $response['google_maps_link'] = $booking['google_maps_link'];
} else {
    // Check for GPS location sharing (existing system)
    $location_stmt = $conn->prepare("
        SELECT dl.latitude, dl.longitude, dl.status, dl.last_updated, dl.accuracy, dl.speed,
               d.name as driver_name, d.driver_phone
        FROM driver_locations dl
        JOIN drivers d ON dl.driver_id = d.id
        WHERE d.driver_phone = ?
        AND dl.status = 'active'
        AND dl.last_updated >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ORDER BY dl.last_updated DESC
        LIMIT 1
    ");
    $location_stmt->bind_param('s', $booking['driver_phone']);
    $location_stmt->execute();
    $location_result = $location_stmt->get_result();
    $driver_location = $location_result->fetch_assoc();
    
    if ($driver_location) {
        $response['driver_location'] = $driver_location;
    }
}

echo json_encode($response);
?>