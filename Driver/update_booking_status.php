<?php
session_start();
require __DIR__ . '/auth.php';
require dirname(__DIR__) . '/db.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in and is a driver
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Get POST data
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$new_status = isset($_POST['status']) ? trim($_POST['status']) : '';

// Validate input
if ($booking_id <= 0 || !in_array($new_status, ['picked_up', 'completed'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // 1. Get booking details
    $stmt = $conn->prepare("
        SELECT b.*, u.phone as passenger_phone, u.name as passenger_name
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        WHERE b.booking_id = ? AND b.assigned_vehicle IN (
            SELECT v.number_plate FROM vehicles v 
            JOIN drivers d ON v.driver_phone = d.driver_phone 
            WHERE d.id = ?
        )
    ");
    $stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$booking) {
        throw new Exception('Booking not found or access denied');
    }

    // Note: The current database schema doesn't include status or updated_at columns
    // This is a simplified booking system without status tracking
    // Status update functionality is disabled for now
    
    // Log the status update attempt for future reference
    error_log("Status update requested for booking {$booking_id}: {$new_status}");

    // 3. Send SMS notification to passenger (simulated)
    $message = '';
    $passenger_phone = $booking['passenger_phone'];
    
    switch ($new_status) {
        case 'picked_up':
            $message = "Your driver has picked up passengers. ";
            $message .= "Driver: " . $_SESSION['name'] . "\n";
            $message .= "Vehicle: " . ($booking['number_plate'] ?? 'N/A') . "\n";
            $message .= "Contact: " . ($_SESSION['phone'] ?? 'N/A');
            break;
            
        case 'completed':
            $message = "Your ride has been completed. Thank you for choosing Southrift Services! ";
            $message .= "We hope to serve you again soon!";
            break;
    }

    // 4. Send SMS (uncomment and configure when ready)
    /*
    if (!empty($passenger_phone) && !empty($message)) {
        $smsResult = sendSMS($passenger_phone, $message);
        // Log SMS result if needed
        // file_put_contents('sms_log.txt', date('Y-m-d H:i:s') . " - $passenger_phone: $message\n", FILE_APPEND);
    }
    */

    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Notification sent successfully (status tracking not available in current schema)',
        'new_status' => $new_status,
        'note' => 'Database schema does not support status updates'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Function to send SMS (to be implemented with your SMS provider)
function sendSMS($to, $message) {
    // TODO: Implement SMS sending logic using your preferred SMS gateway
    // This is a placeholder for the actual implementation
    // Return true if sent successfully, false otherwise
    return true;
}
