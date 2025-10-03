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
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

function send($ok, $payload = []) {
    echo json_encode(array_merge(['success' => $ok], $payload));
    exit;
}

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        send(false, ['error' => 'Not authenticated']);
    }

    $booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
    if ($booking_id <= 0) {
        http_response_code(400);
        send(false, ['error' => 'Missing or invalid booking_id']);
    }

    // 1) Verify booking belongs to this user and get assigned vehicle
    $stmt = $conn->prepare('SELECT b.booking_id, b.user_id, b.assigned_vehicle FROM bookings b WHERE b.booking_id = ? LIMIT 1');
    if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
    $stmt->bind_param('i', $booking_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        http_response_code(404);
        send(false, ['error' => 'Booking not found']);
    }
    $booking = $res->fetch_assoc();
    if ((int)$booking['user_id'] !== (int)$_SESSION['user_id']) {
        http_response_code(403);
        send(false, ['error' => 'Access denied for this booking']);
    }

    if (empty($booking['assigned_vehicle'])) {
        send(false, ['error' => 'No vehicle assigned yet']);
    }

    $number_plate = $booking['assigned_vehicle'];

    // 2) Resolve vehicle -> driver via phone
    $vstmt = $conn->prepare('SELECT v.number_plate, v.driver_phone, v.driver_name FROM vehicles v WHERE v.number_plate = ? LIMIT 1');
    if (!$vstmt) throw new Exception('Prepare failed: ' . $conn->error);
    $vstmt->bind_param('s', $number_plate);
    $vstmt->execute();
    $vres = $vstmt->get_result();
    if ($vres->num_rows === 0) {
        send(false, ['error' => 'Assigned vehicle not found']);
    }
    $vehicle = $vres->fetch_assoc();
    $driver_phone = $vehicle['driver_phone'] ?? '';

    if (empty($driver_phone)) {
        send(false, ['error' => 'No driver phone linked to assigned vehicle']);
    }

    // 3) Get driver ID from drivers table
    $driver_id = 0;
    $dstmt = $conn->prepare('SELECT id, name FROM drivers WHERE driver_phone = ? LIMIT 1');
    if ($dstmt) {
        $dstmt->bind_param('s', $driver_phone);
        $dstmt->execute();
        $dres = $dstmt->get_result();
        if ($dres->num_rows > 0) {
            $driver = $dres->fetch_assoc();
            $driver_id = (int)$driver['id'];
        }
    }

    if ($driver_id <= 0) {
        send(false, ['error' => 'No driver found with phone: ' . $driver_phone]);
    }

    // 3) Fetch latest driver location only if active
    $lstmt = $conn->prepare('SELECT latitude, longitude, last_updated, status FROM driver_locations WHERE driver_id = ? ORDER BY last_updated DESC LIMIT 1');
    if (!$lstmt) throw new Exception('Prepare failed: ' . $conn->error);
    $lstmt->bind_param('i', $driver_id);
    $lstmt->execute();
    $lres = $lstmt->get_result();

    if ($lres->num_rows === 0) {
        send(false, ['error' => 'Driver is not sharing location yet']);
    }

    $loc = $lres->fetch_assoc();

    if ($loc['status'] !== 'active') {
        send(false, ['error' => 'Driver is currently inactive']);
    }

    send(true, [
        'location' => [
            'lat' => (float)$loc['latitude'],
            'lng' => (float)$loc['longitude'],
            'last_updated' => $loc['last_updated'],
            'driver_name' => $vehicle['driver_name'] ?? null,
            'vehicle_plate' => $vehicle['number_plate'] ?? null,
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    send(false, ['error' => 'Server error', 'debug' => $e->getMessage()]);
}
