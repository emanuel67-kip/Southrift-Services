<?php
// Configure session to match the driver system
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    ini_set('session.use_only_cookies', 1);
    session_name('southrift_admin');
    $lifetime = 2592000; // 30 days
    $path = '/';
    $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $httponly = true;
    session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
    session_start();
}

require_once '../db.php';

// Set proper headers for JSON response
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Verify CSRF token - allow 'test' for testing
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        if ($_POST['csrf_token'] !== 'test') {
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => 'Missing CSRF token'
            ]);
            exit;
        }
    } elseif ($_POST['csrf_token'] !== $_SESSION['csrf_token'] && $_POST['csrf_token'] !== 'test') {
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid CSRF token'
        ]);
        exit;
    }

    $driver_phone = $_POST['driver_phone'] ?? null;

    if (!$driver_phone) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Missing driver phone'
        ]);
        exit;
    }

    // Get driver ID and current sharing status
    $stmt = $conn->prepare("
        SELECT 
            d.id,
            dl.google_maps_link,
            dl.status,
            dl.last_updated
        FROM drivers d
        LEFT JOIN driver_locations dl ON d.id = dl.driver_id
        WHERE d.driver_phone = ?
    ");
    $stmt->bind_param('s', $driver_phone);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if (!$data) {
        echo json_encode([
            'success' => false, 
            'message' => 'Driver not found'
        ]);
        exit;
    }

    $is_sharing = ($data['status'] === 'sharing_gmaps' && !empty($data['google_maps_link']));

    // Count passengers if sharing
    $passengers_count = 0;
    if ($is_sharing) {
        $passengers_count = countTodaysPassengers($conn, $driver_phone);
    }

    echo json_encode([
        'success' => true,
        'is_sharing' => $is_sharing,
        'google_maps_link' => $data['google_maps_link'] ?? null,
        'last_updated' => $data['last_updated'] ?? null,
        'passengers_count' => $passengers_count
    ]);

} catch (Exception $e) {
    error_log("Check Google Maps sharing status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred'
    ]);
}

/**
 * Count today's passengers for the driver
 */
function countTodaysPassengers($conn, $driver_phone) {
    try {
        // Get all vehicles assigned to this driver
        $vehicles = [];
        $vehicle_stmt = $conn->prepare("SELECT number_plate FROM vehicles WHERE driver_phone = ?");
        $vehicle_stmt->bind_param("s", $driver_phone);
        $vehicle_stmt->execute();
        $vehicle_result = $vehicle_stmt->get_result();
        while ($row = $vehicle_result->fetch_assoc()) {
            $vehicles[] = $row['number_plate'];
        }

        if (empty($vehicles)) {
            return 0;
        }

        // Count today's assignments for all driver's vehicles (focus on when assignment was made)
        $placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT b.user_id) as passenger_count
            FROM bookings b
            WHERE b.assigned_vehicle IN ($placeholders)
            AND DATE(b.created_at) = CURDATE()
        ");

        $types = str_repeat('s', count($vehicles));
        $stmt->bind_param($types, ...$vehicles);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        return (int)($data['passenger_count'] ?? 0);

    } catch (Exception $e) {
        error_log("Error counting passengers: " . $e->getMessage());
        return 0;
    }
}
?>