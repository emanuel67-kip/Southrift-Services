<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and set headers
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

// Function to send JSON response
function sendResponse($success, $message = '', $data = null) {
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data
    ];
    
    // Only include debug info in non-production environments
    if (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)) {
        $response['debug'] = [
            'session' => $_SESSION,
            'get' => $_GET,
            'post' => $_POST,
            'server' => [
                'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
                'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '',
                'QUERY_STRING' => $_SERVER['QUERY_STRING'] ?? ''
            ]
        ];
    }
    
    http_response_code($success ? 200 : 400);
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}

try {
    // Database connection - using the same credentials as other working files
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'southrift';
    
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error . " (Host: $db_host, User: $db_user, DB: $db_name)");
    }

    // Set charset
    if (!$conn->set_charset("utf8mb4")) {
        error_log("Error setting charset: " . $conn->error);
        // Don't throw exception for charset issue, just log it
    }

    // Log the incoming request for debugging
    error_log("Request received: " . print_r(['GET' => $_GET, 'SESSION' => $_SESSION], true));

    // Ensure user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in. Please log in and try again.');
    }

    // Get the booking ID from the request
    $booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
    if (!$booking_id) {
        throw new Exception('Invalid booking ID. Please provide a valid booking ID.');
    }

    $user_id = $_SESSION['user_id'];
    error_log("Processing request - User ID: $user_id, Booking ID: $booking_id");

    // First, verify the booking exists and belongs to the user
    $check_booking_sql = "SELECT booking_id, user_id, assigned_vehicle FROM bookings WHERE booking_id = ?";
    $check_stmt = $conn->prepare($check_booking_sql);
    
    if (!$check_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $check_stmt->bind_param("i", $booking_id);
    
    if (!$check_stmt->execute()) {
        throw new Exception("Execute failed: " . $check_stmt->error);
    }
    
    $booking_result = $check_stmt->get_result();
    
    if ($booking_result->num_rows === 0) {
        throw new Exception('Booking not found. The specified booking ID does not exist.');
    }
    
    $booking = $booking_result->fetch_assoc();
    
    // Check if the booking belongs to the current user
    if ($booking['user_id'] != $user_id) {
        throw new Exception('Access denied. This booking does not belong to your account.');
    }
    
    // If no vehicle is assigned
    if (empty($booking['assigned_vehicle'])) {
        sendResponse(true, 'No vehicle has been assigned to this booking yet', [
            'booking_id' => $booking_id,
            'has_vehicle' => false,
            'assigned_vehicle' => null
        ]);
    }
    
    // Get vehicle details
    $vehicle_sql = "SELECT * FROM vehicles WHERE number_plate = ?";
    $vehicle_stmt = $conn->prepare($vehicle_sql);
    
    if (!$vehicle_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $vehicle_stmt->bind_param("s", $booking['assigned_vehicle']);
    
    if (!$vehicle_stmt->execute()) {
        throw new Exception("Execute failed: " . $vehicle_stmt->error);
    }
    
    $vehicle_result = $vehicle_stmt->get_result();
    
    if ($vehicle_result->num_rows === 0) {
        // Vehicle was assigned but not found in the vehicles table
        sendResponse(true, 'Vehicle information not available', [
            'booking_id' => $booking_id,
            'has_vehicle' => false,
            'assigned_vehicle' => $booking['assigned_vehicle'],
            'message' => 'Vehicle was assigned but details could not be retrieved'
        ]);
    }
    
    $vehicle = $vehicle_result->fetch_assoc();
    
    // Format the response
    sendResponse(true, 'Vehicle details retrieved', [
        'vehicle' => [
            'number_plate' => $vehicle['number_plate'] ?? null,
            'type' => $vehicle['type'] ?? null,
            'model' => $vehicle['model'] ?? null,
            'color' => $vehicle['color'] ?? null,
            'driver_name' => $vehicle['driver_name'] ?? 'Not specified',
            'driver_phone' => $vehicle['driver_phone'] ?? null,
            'capacity' => $vehicle['capacity'] ?? null,
            'is_waiting' => $vehicle['is_waiting'] ?? null
        ],
        'booking' => [
            'id' => $booking_id,
            'assigned_vehicle' => $booking['assigned_vehicle']
        ]
    ]);
    
} catch (Exception $e) {
    // Log the full error
    error_log("Error in assigned_vehicle.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    // Send error response with detailed information
    sendResponse(false, $e->getMessage(), [
        'error' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'code' => $e->getCode()
        ]
    ]);
}

// Close database connection if it was opened
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
