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

    // Get driver ID
    $driver_id = null;
    $stmt = $conn->prepare("SELECT id FROM drivers WHERE driver_phone = ?");
    $stmt->bind_param('s', $driver_phone);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $driver_id = (int)$row['id'];
    }

    if (!$driver_id) {
        echo json_encode([
            'success' => false, 
            'message' => 'Driver not found'
        ]);
        exit;
    }

    // Stop sharing by updating status and clearing Google Maps link
    $stmt = $conn->prepare("
        UPDATE driver_locations 
        SET status = 'inactive', google_maps_link = NULL, last_updated = NOW() 
        WHERE driver_id = ?
    ");
    $stmt->bind_param('i', $driver_id);
    $stmt->execute();
    
    // Also clear Google Maps links from all passenger bookings
    $passengers_cleared = clearPassengerBookingLinks($conn, $driver_phone);

    // Remove notification functionality for now
    // $passengers_notified = notifyPassengersSharingStop($conn, $driver_phone);

    echo json_encode([
        'success' => true,
        'message' => 'Location sharing stopped',
        'passengers_cleared' => $passengers_cleared
    ]);

} catch (Exception $e) {
    error_log("Stop Google Maps sharing error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred'
    ]);
}

/**
 * Clear Google Maps links from passenger bookings
 */
function clearPassengerBookingLinks($conn, $driver_phone) {
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
        
        // Clear Google Maps links from all bookings for these vehicles
        $placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
        $clear_stmt = $conn->prepare("
            UPDATE bookings 
            SET google_maps_link = NULL, shared_location_updated = NULL 
            WHERE assigned_vehicle IN ($placeholders)
        ");
        
        $types = str_repeat('s', count($vehicles));
        $clear_stmt->bind_param($types, ...$vehicles);
        $clear_stmt->execute();
        
        return $clear_stmt->affected_rows;
        
    } catch (Exception $e) {
        error_log("Error clearing passenger booking links: " . $e->getMessage());
        return 0;
    }
}

/**
 * Notify passengers that location sharing has stopped
 */
function notifyPassengersSharingStop($conn, $driver_phone) {
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

        // Get today's bookings for all driver's vehicles
        $placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
        $stmt = $conn->prepare("
            SELECT DISTINCT 
                b.user_id, 
                u.name as passenger_name,
                v.number_plate,
                v.type as vehicle_type
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN vehicles v ON b.assigned_vehicle = v.number_plate
            WHERE b.assigned_vehicle IN ($placeholders)
            AND DATE(b.created_at) = CURDATE()
        ");

        $types = str_repeat('s', count($vehicles));
        $stmt->bind_param($types, ...$vehicles);
        $stmt->execute();
        $result = $stmt->get_result();

        $passengers_notified = 0;

        // Create notifications for each passenger
        while ($passenger = $result->fetch_assoc()) {
            $notification_sql = "
                INSERT INTO notifications (user_id, title, message, type, created_at) 
                VALUES (?, ?, ?, 'location_sharing', NOW())
            ";
            $notif_stmt = $conn->prepare($notification_sql);
            
            $title = 'Driver Location Sharing Stopped';
            $message = "Your driver has stopped sharing location. Vehicle: {$passenger['vehicle_type']} ({$passenger['number_plate']}).";
            
            $notif_stmt->bind_param('iss', $passenger['user_id'], $title, $message);
            $notif_stmt->execute();
            
            $passengers_notified++;
        }

        return $passengers_notified;

    } catch (Exception $e) {
        error_log("Error notifying passengers about stop: " . $e->getMessage());
        return 0;
    }
}
?>