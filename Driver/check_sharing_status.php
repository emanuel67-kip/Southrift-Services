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
        echo json_encode(['success' => true, 'is_sharing' => false, 'message' => 'Driver not found']);
        exit;
    }

    // Check if there's an active sharing session
    $stmt = $conn->prepare("
        SELECT id, token, status, created_at, expires_at
        FROM driver_share_sessions 
        WHERE driver_id = ? AND status = 'active' AND expires_at > NOW()
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->bind_param('i', $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $session = $result->fetch_assoc();
    
    if ($session) {
        // Count passengers in this session
        $passenger_stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM session_passengers 
            WHERE session_id = ?
        ");
        $passenger_stmt->bind_param('i', $session['id']);
        $passenger_stmt->execute();
        $passenger_result = $passenger_stmt->get_result();
        $passenger_count = $passenger_result->fetch_assoc()['count'];
        
        echo json_encode([
            'success' => true,
            'is_sharing' => true,
            'session' => [
                'id' => $session['id'],
                'token' => $session['token'],
                'expires_at' => $session['expires_at']
            ],
            'passenger_count' => $passenger_count
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'is_sharing' => false,
            'message' => 'No active sharing session'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Check sharing status error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>