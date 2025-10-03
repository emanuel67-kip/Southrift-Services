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

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    // Debug logging
    error_log("Google Maps Share Request: " . print_r($_POST, true));
    error_log("Session CSRF: " . ($_SESSION['csrf_token'] ?? 'NOT_SET'));
    error_log("Posted CSRF: " . ($_POST['csrf_token'] ?? 'NOT_SET'));
    
    // Verify CSRF token - allow 'test' for testing and better debugging
    $posted_token = $_POST['csrf_token'] ?? null;
    $session_token = $_SESSION['csrf_token'] ?? null;
    
    // Enhanced debugging
    error_log("CSRF Debug - Posted token: " . ($posted_token ? substr($posted_token, 0, 10) . '...' : 'NULL'));
    error_log("CSRF Debug - Session token: " . ($session_token ? substr($session_token, 0, 10) . '...' : 'NULL'));
    
    // Allow 'test' token for testing, or validate against session
    if ($posted_token === 'test') {
        error_log("CSRF: Using test token - bypassing validation");
    } elseif (!$posted_token || !$session_token) {
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => 'Missing CSRF token. Please refresh the page and try again.',
            'debug' => [
                'error' => 'csrf_missing',
                'posted_token_exists' => !empty($posted_token),
                'session_token_exists' => !empty($session_token),
                'session_id' => session_id()
            ]
        ]);
        exit;
    } elseif ($posted_token !== $session_token) {
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid CSRF token. Please refresh the page and try again.',
            'debug' => [
                'error' => 'csrf_mismatch',
                'posted_length' => strlen($posted_token),
                'session_length' => strlen($session_token),
                'session_id' => session_id()
            ]
        ]);
        exit;
    }

    $driver_phone = $_POST['driver_phone'] ?? null;
    $google_maps_link = $_POST['google_maps_link'] ?? null;
    
    error_log("Received params - Driver: $driver_phone, Link: $google_maps_link");

    if (!$driver_phone || !$google_maps_link) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Missing required parameters',
            'debug' => [
                'driver_phone' => $driver_phone ? 'provided' : 'missing',
                'google_maps_link' => $google_maps_link ? 'provided' : 'missing'
            ]
        ]);
        exit;
    }

    // Validate Google Maps link
    if (!isValidGoogleMapsLink($google_maps_link)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid Google Maps link format',
            'debug' => 'Link validation failed'
        ]);
        exit;
    }
    
    // Check database connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed: ' . ($conn->connect_error ?? 'Connection not established'));
    }

    // Get driver ID
    $driver_id = null;
    error_log("Searching for driver with phone: $driver_phone");
    
    // First try with driver_phone column only
    $stmt = $conn->prepare("SELECT id FROM drivers WHERE driver_phone = ?");
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('s', $driver_phone);
    if (!$stmt->execute()) {
        throw new Exception('Database execute failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $driver_id = (int)$row['id'];
        error_log("Found driver ID: $driver_id");
    } else {
        error_log("No driver found for phone: $driver_phone");
    }

    if (!$driver_id) {
        echo json_encode([
            'success' => false, 
            'message' => 'Driver not found in database',
            'debug' => "No driver found with phone: $driver_phone"
        ]);
        exit;
    }

    // Store or update the Google Maps link
    error_log("Updating driver_locations for driver_id: $driver_id");
    
    $stmt = $conn->prepare("
        INSERT INTO driver_locations (driver_id, google_maps_link, status, last_updated) 
        VALUES (?, ?, 'sharing_gmaps', NOW()) 
        ON DUPLICATE KEY UPDATE 
            google_maps_link = VALUES(google_maps_link), 
            status = 'sharing_gmaps', 
            last_updated = NOW()
    ");
    
    if (!$stmt) {
        throw new Exception('Database prepare failed for location update: ' . $conn->error);
    }
    
    $stmt->bind_param('is', $driver_id, $google_maps_link);
    if (!$stmt->execute()) {
        throw new Exception('Database execute failed for location update: ' . $stmt->error);
    }
    
    error_log("Successfully updated driver_locations");

    // Get today's passengers assigned to this driver
    $passengers_notified = notifyPassengersGoogleMapsLink($conn, $driver_phone, $google_maps_link);

    echo json_encode([
        'success' => true,
        'message' => 'Google Maps location shared successfully',
        'passengers_notified' => $passengers_notified,
        'google_maps_link' => $google_maps_link
    ]);

} catch (Exception $e) {
    error_log("Google Maps sharing error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
}

/**
 * Validate if the provided link is a valid Google Maps link
 */
function isValidGoogleMapsLink($link) {
    $patterns = [
        '/^https:\/\/maps\.app\.goo\.gl\/.+/',
        '/^https:\/\/www\.google\.com\/maps\/.+/',
        '/^https:\/\/goo\.gl\/maps\/.+/',
        '/^https:\/\/maps\.google\.com\/.+/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $link)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Notify passengers about the shared Google Maps link
 */
function notifyPassengersGoogleMapsLink($conn, $driver_phone, $google_maps_link) {
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
        $vehicle_stmt->close();
        
        error_log("Google Maps: Found " . count($vehicles) . " vehicles for driver: " . implode(', ', $vehicles));

        if (empty($vehicles)) {
            error_log("Google Maps: No vehicles found for driver phone: $driver_phone");
            return 0;
        }
        
        // Debug: Check what bookings exist for today
        $debug_bookings = $conn->query("
            SELECT COUNT(*) as total_today, 
                   COUNT(CASE WHEN assigned_vehicle IS NOT NULL THEN 1 END) as with_vehicle
            FROM bookings 
            WHERE DATE(travel_date) = CURDATE()
        ");
        if ($debug_bookings) {
            $debug_data = $debug_bookings->fetch_assoc();
            error_log("Google Maps Debug: Total bookings today: {$debug_data['total_today']}, With vehicle: {$debug_data['with_vehicle']}");
        }

        // Get TODAY'S passengers assigned to this driver's vehicles
        $placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
        $stmt = $conn->prepare("
            SELECT DISTINCT 
                b.user_id, 
                b.fullname as passenger_name, 
                b.phone as passenger_phone,
                v.number_plate,
                v.type as vehicle_type,
                b.booking_id
            FROM bookings b
            JOIN vehicles v ON b.assigned_vehicle = v.number_plate
            WHERE b.assigned_vehicle IN ($placeholders)
            AND b.phone IS NOT NULL
            AND b.phone != ''
            AND b.user_id IS NOT NULL
            AND DATE(b.travel_date) = CURDATE()
        ");

        $types = str_repeat('s', count($vehicles));
        $stmt->bind_param($types, ...$vehicles);
        $stmt->execute();
        $result = $stmt->get_result();

        $passengers_notified = 0;
        
        error_log("Google Maps: Passenger query executed, found " . $result->num_rows . " passengers");
        error_log("Google Maps: Query used - SELECT DISTINCT b.user_id, b.fullname, b.phone, v.number_plate, v.type, b.booking_id FROM bookings b JOIN vehicles v ON b.assigned_vehicle = v.number_plate WHERE b.assigned_vehicle IN (" . implode(',', $vehicles) . ") AND b.phone IS NOT NULL AND b.phone != '' AND b.user_id IS NOT NULL AND DATE(b.travel_date) = CURDATE()");
        error_log("Google Maps: Current date for comparison: " . date('Y-m-d'));
        
        // Additional debug: Check if there are any bookings at all for these vehicles
        $debug_all_stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE assigned_vehicle IN ($placeholders)");
        $debug_all_stmt->bind_param($types, ...$vehicles);
        $debug_all_stmt->execute();
        $debug_all_result = $debug_all_stmt->get_result()->fetch_assoc();
        error_log("Google Maps: Total bookings for these vehicles (any date): " . $debug_all_result['total']);

        // Create/update booking records for each passenger (no notifications)
        while ($passenger = $result->fetch_assoc()) {
            error_log("Google Maps: Processing passenger: " . print_r($passenger, true));
            
            // Store the Google Maps link directly in the passenger's booking record
            $update_booking_sql = "
                UPDATE bookings 
                SET google_maps_link = ?, shared_location_updated = NOW() 
                WHERE user_id = ? AND booking_id = ?
            ";
            $update_stmt = $conn->prepare($update_booking_sql);
            $update_stmt->bind_param('sii', $google_maps_link, $passenger['user_id'], $passenger['booking_id']);
            
            if ($update_stmt->execute()) {
                $passengers_notified++;
                error_log("Google Maps: Link attached to booking {$passenger['booking_id']} for user {$passenger['user_id']}");
            } else {
                error_log("Google Maps: Failed to attach link to booking {$passenger['booking_id']}: " . $update_stmt->error);
            }
        }

        return $passengers_notified;

    } catch (Exception $e) {
        error_log("Error notifying passengers: " . $e->getMessage());
        return 0;
    }
}
?>