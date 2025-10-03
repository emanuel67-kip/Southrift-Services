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
$session_token = $_POST['session_token'] ?? '';
$latitude = $_POST['latitude'] ?? '';
$longitude = $_POST['longitude'] ?? '';
$accuracy = $_POST['accuracy'] ?? '';
$speed = $_POST['speed'] ?? '';
$heading = $_POST['heading'] ?? '';

if (empty($driver_phone)) {
    echo json_encode(['success' => false, 'error' => 'Driver not logged in']);
    exit;
}

try {
    // Get driver ID
    $driver_id = null;
    $stmt = $conn->prepare("SELECT id FROM drivers WHERE driver_phone = ? OR phone = ? LIMIT 1");
    $stmt->bind_param('ss', $driver_phone, $driver_phone);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $driver_id = (int)$row['id'];
    }
    
    if (!$driver_id) {
        echo json_encode(['success' => false, 'error' => 'Driver not found']);
        exit;
    }

    // Verify session token if provided
    if ($session_token) {
        $session_stmt = $conn->prepare("
            SELECT id FROM driver_share_sessions 
            WHERE token = ? AND driver_id = ? AND status = 'active' AND expires_at > NOW()
        ");
        $session_stmt->bind_param('si', $session_token, $driver_id);
        $session_stmt->execute();
        $session_result = $session_stmt->get_result();
        
        if (!$session_result->fetch_assoc()) {
            echo json_encode(['success' => false, 'error' => 'Invalid or expired session']);
            exit;
        }
    }

    // Update driver location
    if ($latitude && $longitude) {
        $stmt = $conn->prepare("
            INSERT INTO driver_locations (driver_id, latitude, longitude, accuracy, speed, heading, status, last_updated) 
            VALUES (?, ?, ?, ?, ?, ?, 'active', NOW()) 
            ON DUPLICATE KEY UPDATE 
                latitude = VALUES(latitude), 
                longitude = VALUES(longitude),
                accuracy = VALUES(accuracy),
                speed = VALUES(speed),
                heading = VALUES(heading),
                status = 'active',
                last_updated = NOW()
        ");
        
        $accuracy_val = $accuracy ? floatval($accuracy) : null;
        $speed_val = $speed ? floatval($speed) : null;
        $heading_val = $heading ? floatval($heading) : null;
        
        $stmt->bind_param('iddddd', $driver_id, floatval($latitude), floatval($longitude), 
                         $accuracy_val, $speed_val, $heading_val);
        $stmt->execute();
        
        // Also insert into location history
        $history_stmt = $conn->prepare("
            INSERT INTO driver_location_history (driver_id, latitude, longitude, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $history_stmt->bind_param('idd', $driver_id, floatval($latitude), floatval($longitude));
        $history_stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Location updated successfully',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing location coordinates']);
    }
    
} catch (Exception $e) {
    error_log("Update location error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>