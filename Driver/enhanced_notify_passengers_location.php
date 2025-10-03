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
$action = $_POST['action'] ?? '';

if (empty($driver_phone)) {
    echo json_encode(['success' => false, 'error' => 'Driver not logged in']);
    exit;
}

try {
    // Get driver ID with multiple fallback methods
    $driver_id = null;
    
    // Method 1: Try driver_phone field first
    $stmt = $conn->prepare("SELECT id FROM drivers WHERE driver_phone = ? LIMIT 1");
    $stmt->bind_param('s', $driver_phone);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $driver_id = (int)$row['id'];
    }
    
    // Method 2: Try phone field as fallback
    if (!$driver_id) {
        $stmt = $conn->prepare("SELECT id FROM drivers WHERE phone = ? LIMIT 1");
        $stmt->bind_param('s', $driver_phone);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $driver_id = (int)$row['id'];
        }
    }
    
    if (!$driver_id) {
        echo json_encode(['success' => false, 'error' => 'Driver not found in database']);
        exit;
    }

    if ($action === 'start') {
        // Generate a unique sharing token
        $share_token = bin2hex(random_bytes(16));
        
        // Update or insert driver location sharing status
        $stmt = $conn->prepare("
            INSERT INTO driver_locations (driver_id, latitude, longitude, status, share_token, last_updated) 
            VALUES (?, 0, 0, 'active', ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
                status = 'active', 
                share_token = VALUES(share_token), 
                last_updated = NOW()
        ");
        $stmt->bind_param('is', $driver_id, $share_token);
        $stmt->execute();
        
        // Find passengers assigned to this driver's vehicles for today
        $passengers_notified = 0;
        $passenger_details = [];
        
        $notification_stmt = $conn->prepare("
            SELECT DISTINCT 
                b.user_id, 
                u.name as passenger_name, 
                u.phone as passenger_phone, 
                u.email as passenger_email,
                b.pickup_location,
                b.destination,
                b.travel_date,
                b.status as booking_status,
                v.number_plate,
                v.type as vehicle_type
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN vehicles v ON b.assigned_vehicle = v.number_plate
            WHERE (v.driver_phone = ? OR v.driver_phone = ?)
            AND DATE(b.travel_date) = CURDATE()
        ");
        $notification_stmt->bind_param('ss', $driver_phone, $driver_phone);
        $notification_stmt->execute();
        $passengers_result = $notification_stmt->get_result();
        
        // Create notifications for each passenger
        while ($passenger = $passengers_result->fetch_assoc()) {
            // Create notification in database
            $notification_sql = "
                INSERT INTO notifications (user_id, title, message, type, created_at) 
                VALUES (?, ?, ?, 'location_sharing', NOW())
            ";
            $notif_stmt = $conn->prepare($notification_sql);
            
            $title = 'Driver Location Sharing Started';
            $message = "Your driver has started sharing live location. You can now track your ride in real-time. Vehicle: {$passenger['vehicle_type']} ({$passenger['number_plate']})";
            
            $notif_stmt->bind_param('iss', $passenger['user_id'], $title, $message);
            $notif_stmt->execute();
            
            // Store passenger details for response
            $passenger_details[] = [
                'user_id' => $passenger['user_id'],
                'name' => $passenger['passenger_name'],
                'phone' => $passenger['passenger_phone'],
                'pickup_location' => $passenger['pickup_location'],
                'destination' => $passenger['destination'],
                'booking_status' => $passenger['booking_status']
            ];
            
            $passengers_notified++;
        }
        
        // Log the location sharing session start (with error handling)
        try {
            $session_stmt = $conn->prepare("
                INSERT INTO driver_share_sessions (driver_id, session_token, started_at, passengers_notified) 
                VALUES (?, ?, NOW(), ?)
            ");
            $session_stmt->bind_param('isi', $driver_id, $share_token, $passengers_notified);
            $session_stmt->execute();
            $session_id = $conn->insert_id;
        } catch (Exception $e) {
            // Table might not exist yet, continue without session logging
            error_log("Session logging failed: " . $e->getMessage());
            $session_id = null;
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Location sharing started successfully',
            'passengers_notified' => $passengers_notified,
            'passenger_details' => $passenger_details,
            'share_token' => $share_token,
            'session_id' => $session_id ?? null
        ]);
        
    } elseif ($action === 'stop') {
        // Update driver location sharing status to inactive
        $stmt = $conn->prepare("
            UPDATE driver_locations 
            SET status = 'inactive', last_updated = NOW() 
            WHERE driver_id = ?
        ");
        $stmt->bind_param('i', $driver_id);
        $stmt->execute();
        
        // Find passengers assigned to this driver's vehicles for today
        $passengers_notified = 0;
        $passenger_details = [];
        
        $notification_stmt = $conn->prepare("
            SELECT DISTINCT 
                b.user_id, 
                u.name as passenger_name, 
                u.phone as passenger_phone,
                b.pickup_location,
                b.destination,
                b.status as booking_status,
                v.number_plate,
                v.type as vehicle_type
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN vehicles v ON b.assigned_vehicle = v.number_plate
            WHERE (v.driver_phone = ? OR v.driver_phone = ?)
            AND DATE(b.travel_date) = CURDATE()
        ");
        $notification_stmt->bind_param('ss', $driver_phone, $driver_phone);
        $notification_stmt->execute();
        $passengers_result = $notification_stmt->get_result();
        
        // Create notifications for each passenger
        while ($passenger = $passengers_result->fetch_assoc()) {
            $notification_sql = "
                INSERT INTO notifications (user_id, title, message, type, created_at) 
                VALUES (?, ?, ?, 'location_sharing', NOW())
            ";
            $notif_stmt = $conn->prepare($notification_sql);
            
            $title = 'Driver Location Sharing Stopped';
            $message = "Your driver has stopped sharing location. Vehicle: {$passenger['vehicle_type']} ({$passenger['number_plate']})";
            
            $notif_stmt->bind_param('iss', $passenger['user_id'], $title, $message);
            $notif_stmt->execute();
            
            // Store passenger details for response
            $passenger_details[] = [
                'user_id' => $passenger['user_id'],
                'name' => $passenger['passenger_name'],
                'phone' => $passenger['passenger_phone'],
                'pickup_location' => $passenger['pickup_location'],
                'destination' => $passenger['destination'],
                'booking_status' => $passenger['booking_status']
            ];
            
            $passengers_notified++;
        }
        
        // Update the sharing session end time (with error handling)
        try {
            $session_stmt = $conn->prepare("
                UPDATE driver_share_sessions 
                SET ended_at = NOW(), total_duration = TIMESTAMPDIFF(MINUTE, started_at, NOW())
                WHERE driver_id = ? AND ended_at IS NULL
                ORDER BY started_at DESC
                LIMIT 1
            ");
            $session_stmt->bind_param('i', $driver_id);
            $session_stmt->execute();
        } catch (Exception $e) {
            // Table might not exist yet, continue without session logging
            error_log("Session update failed: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Location sharing stopped successfully',
            'passengers_notified' => $passengers_notified,
            'passenger_details' => $passenger_details
        ]);
        
    } elseif ($action === 'status') {
        // Get current sharing status
        $stmt = $conn->prepare("
            SELECT status, last_updated, share_token, latitude, longitude
            FROM driver_locations 
            WHERE driver_id = ?
            ORDER BY last_updated DESC
            LIMIT 1
        ");
        $stmt->bind_param('i', $driver_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $location_data = $result->fetch_assoc();
        
        $is_sharing = $location_data && $location_data['status'] === 'active';
        
        // Count current passengers
        $passenger_count = 0;
        if ($is_sharing) {
            $passenger_stmt = $conn->prepare("
                SELECT COUNT(DISTINCT b.user_id) as passenger_count
                FROM bookings b
                JOIN vehicles v ON b.assigned_vehicle = v.number_plate
                WHERE (v.driver_phone = ? OR v.driver_phone = ?)
                AND DATE(b.travel_date) = CURDATE()
                AND b.status IN ('assigned', 'picked_up')
            ");
            $passenger_stmt->bind_param('ss', $driver_phone, $driver_phone);
            $passenger_stmt->execute();
            $passenger_result = $passenger_stmt->get_result();
            if ($passenger_row = $passenger_result->fetch_assoc()) {
                $passenger_count = (int)$passenger_row['passenger_count'];
            }
        }
        
        echo json_encode([
            'success' => true,
            'is_sharing' => $is_sharing,
            'passenger_count' => $passenger_count,
            'last_updated' => $location_data['last_updated'] ?? null,
            'share_token' => $location_data['share_token'] ?? null,
            'location' => $is_sharing && $location_data['latitude'] && $location_data['longitude'] ? [
                'latitude' => $location_data['latitude'],
                'longitude' => $location_data['longitude']
            ] : null
        ]);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action. Use: start, stop, or status']);
    }
    
} catch (Exception $e) {
    error_log("Location sharing error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error occurred', 'debug' => $e->getMessage()]);
}
?>