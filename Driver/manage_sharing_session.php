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
        echo json_encode(['success' => false, 'error' => 'Driver not found in database']);
        exit;
    }

    if ($action === 'create_session') {
        // Get parameters
        $duration_minutes = $_POST['duration_minutes'] ?? '';
        $selected_passengers = json_decode($_POST['selected_passengers'] ?? '[]', true);
        
        // Generate unique session token
        $session_token = bin2hex(random_bytes(32));
        
        // Calculate expiry time
        $expires_at = null;
        if (!empty($duration_minutes) && is_numeric($duration_minutes)) {
            $expires_at = date('Y-m-d H:i:s', time() + ($duration_minutes * 60));
        } else {
            // Default to 4 hours if no duration specified (until trip end)
            $expires_at = date('Y-m-d H:i:s', time() + (4 * 60 * 60));
        }
        
        // Create sharing session
        $stmt = $conn->prepare("
            INSERT INTO driver_share_sessions (driver_id, token, status, expires_at, created_at)
            VALUES (?, ?, 'active', ?, NOW())
        ");
        $stmt->bind_param('iss', $driver_id, $session_token, $expires_at);
        $stmt->execute();
        $session_id = $conn->insert_id;
        
        // Store passenger list (we'll create a session_passengers table for this)
        foreach ($selected_passengers as $passenger_id) {
            // Get passenger phone number
            $passenger_stmt = $conn->prepare("
                SELECT b.phone, b.fullname 
                FROM bookings b 
                WHERE b.user_id = ? 
                AND DATE(b.travel_date) = CURDATE() 
                LIMIT 1
            ");
            $passenger_stmt->bind_param('s', $passenger_id);
            $passenger_stmt->execute();
            $passenger_result = $passenger_stmt->get_result();
            
            if ($passenger_row = $passenger_result->fetch_assoc()) {
                // Store in session_passengers table (we'll create this)
                $insert_passenger = $conn->prepare("
                    INSERT IGNORE INTO session_passengers (session_id, user_id, phone_number, name)
                    VALUES (?, ?, ?, ?)
                ");
                $insert_passenger->bind_param('isss', $session_id, $passenger_id, 
                    $passenger_row['phone'], $passenger_row['fullname']);
                $insert_passenger->execute();
            }
        }
        
        // Send WhatsApp notifications to passengers
        $tracking_url = "https://{$_SERVER['HTTP_HOST']}/track?token={$session_token}";
        $message_count = $this->sendTrackingNotifications($selected_passengers, $tracking_url, $session_token);
        
        echo json_encode([
            'success' => true,
            'session' => [
                'id' => $session_id,
                'token' => $session_token,
                'expires_at' => $expires_at,
                'tracking_url' => $tracking_url
            ],
            'passengers_notified' => $message_count,
            'message' => 'Sharing session created successfully'
        ]);
        
    } elseif ($action === 'end_session') {
        $session_token = $_POST['session_token'] ?? '';
        
        if (empty($session_token)) {
            echo json_encode(['success' => false, 'error' => 'Session token required']);
            exit;
        }
        
        // End the sharing session
        $stmt = $conn->prepare("
            UPDATE driver_share_sessions 
            SET status = 'stopped', updated_at = NOW() 
            WHERE token = ? AND driver_id = ? AND status = 'active'
        ");
        $stmt->bind_param('si', $session_token, $driver_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Sharing session ended successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Session not found or already ended'
            ]);
        }
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Session management error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

// Helper function to send tracking notifications
function sendTrackingNotifications($passenger_ids, $tracking_url, $session_token) {
    global $conn;
    
    $sent_count = 0;
    
    foreach ($passenger_ids as $passenger_id) {
        // Get passenger details
        $stmt = $conn->prepare("
            SELECT b.phone, b.fullname, v.driver_name, v.number_plate
            FROM bookings b
            JOIN vehicles v ON b.assigned_vehicle = v.number_plate
            WHERE b.user_id = ?
            AND DATE(b.travel_date) = CURDATE()
            LIMIT 1
        ");
        $stmt->bind_param('s', $passenger_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $phone = $row['phone'];
            $passenger_name = $row['fullname'];
            $driver_name = $row['driver_name'];
            $vehicle_plate = $row['number_plate'];
            
            // Create WhatsApp message
            $message = "🚗 *SouthRift Services - Live Tracking*\n\n";
            $message .= "Hello {$passenger_name}!\n\n";
            $message .= "Your driver {$driver_name} (Vehicle: {$vehicle_plate}) is now sharing live location.\n\n";
            $message .= "📍 Track your ride here:\n{$tracking_url}\n\n";
            $message .= "🔒 This link will expire automatically for your security.\n\n";
            $message .= "_SouthRift Services - Your trusted transport partner_";
            
            // For now, log the message (actual sending depends on WhatsApp API setup)
            error_log("WhatsApp tracking message for {$phone}: {$message}");
            
            // Store notification in database
            $notif_stmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, created_at)
                VALUES (?, ?, ?, 'location_tracking', NOW())
            ");
            $title = 'Live Location Tracking Available';
            $notif_stmt->bind_param('iss', $passenger_id, $title, $message);
            $notif_stmt->execute();
            
            $sent_count++;
        }
    }
    
    return $sent_count;
}
?>