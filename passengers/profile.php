<?php
// Enhanced cache control
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Configure session to match the login system
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters to match login.php
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    ini_set('session.use_only_cookies', 1);
    
    // Set the correct session name (same as login.php)
    session_name('southrift_admin');
    
    // Set enhanced session cookie parameters
    $lifetime = 60 * 60; // 1 hour for passengers
    $path = '/';
    $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $httponly = true;
    $samesite = 'Lax';
    
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => $path,
        'domain' => $domain,
        'secure' => $secure,
        'httponly' => $httponly,
        'samesite' => $samesite
    ]);
    
    // Start the session
    session_start();
} else {
    session_start();
}

header('Content-Type: application/json');

// Add debugging
error_log("Profile.php accessed. Session data: " . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in - session user_id not set");
    header('Location: ../login.html?session_expired=1');
    exit;
}

// Check if session has expired for passengers
if (isset($_SESSION['role']) && $_SESSION['role'] === 'passenger') {
    // Check if expires_at is set and has passed
    if (isset($_SESSION['expires_at']) && time() > $_SESSION['expires_at']) {
        // Session expired, destroy session
        session_unset();
        session_destroy();
        header('Location: ../login.html?session_expired=1');
        exit;
    }
    
    // Update last activity and extend session
    $_SESSION['last_activity'] = time();
    $_SESSION['expires_at'] = time() + 1800; // Extend by 30 minutes
}

// Additional validation for passenger role
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'passenger') {
    error_log("Access denied - user role is " . $_SESSION['role']);
    header('Location: ../login.html?access_denied=1');
    exit;
}

// Include database connection with error handling
if (!file_exists('../db.php')) {
    error_log("Database connection file not found");
    header('Location: ../login.html?error=db_connection');
    exit;
}

require_once '../db.php';

// Check if $conn is properly initialized
if (!isset($conn) || !$conn) {
    error_log("Database connection failed");
    header('Location: ../login.html?error=db_connection');
    exit;
}

// Log connection success for debugging
error_log("Database connection successful for profile.php");

$user_id = $_SESSION['user_id'];
$response = [];

try {
    // Get user info with proper error handling
    $user_stmt = $conn->prepare("SELECT name, email, phone, created_at FROM users WHERE id = ?");
    if (!$user_stmt) {
        error_log("User query prepare failed: " . $conn->error);
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $user_stmt->bind_param("i", $user_id);
    if (!$user_stmt->execute()) {
        error_log("User query execute failed: " . $user_stmt->error);
        throw new Exception("Execute failed: " . $user_stmt->error);
    }
    
    $user_result = $user_stmt->get_result();
    $user = $user_result->fetch_assoc();
    
    if (!$user) {
        error_log("User not found with ID: " . $user_id);
        throw new Exception("User not found with ID: " . $user_id . ". Please check if user exists in database.");
    }
    
    // Log user data for debugging
    error_log("User data retrieved: " . print_r($user, true));

    // Format the created_at date
    if (isset($user['created_at'])) {
        $date = new DateTime($user['created_at']);
        $user['created_at'] = $date->format('F j, Y');
    }

    // Get user bookings with safe column handling
    $bookings = [];
    $todays_bookings = [];
    
    // Use the updated table structure
    $book_stmt = $conn->prepare("SELECT 
        booking_id,
        route,
        boarding_point,
        travel_date,
        departure_time,
        seats,
        payment_method,
        assigned_vehicle,
        created_at,
        'active' AS status
        FROM bookings 
        WHERE user_id = ?
        ORDER BY created_at DESC");
    
    if (!$book_stmt) {
        error_log("Bookings query prepare failed: " . $conn->error);
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $book_stmt->bind_param("i", $user_id);
    if (!$book_stmt->execute()) {
        error_log("Bookings query execute failed: " . $book_stmt->error);
        throw new Exception("Execute failed: " . $book_stmt->error);
    }
    
    $book_result = $book_stmt->get_result();
    
    // Log booking query result for debugging
    error_log("Booking query executed successfully. Number of rows: " . $book_result->num_rows);
    
    // Define route fares (matching the fares from routes.html)
    $routeFares = [
        'nairobi-bomet' => 1200,
        'nairobi-kisumu' => 2000,
        'nairobi-nakuru' => 500,
        'litein-nairobi' => 1200,
        'kisumu-nairobi' => 2000,
        'nakuru-nairobi' => 500,
        'bomet-nairobi' => 1200
    ];
    
    while ($row = $book_result->fetch_assoc()) {
        // Log each booking row for debugging
        error_log("Booking row data: " . print_r($row, true));
        
        // Format dates for display
        if (isset($row['travel_date'])) {
            $date = new DateTime($row['travel_date']);
            $row['travel_date'] = $date->format('M j, Y');
            
            // Check if this is a booking for today
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            $travelDate = new DateTime($row['travel_date']);
            $travelDate->setTime(0, 0, 0);
            
            if ($today == $travelDate) {
                $todays_bookings[] = $row;
            }
        }
        
        // Format time for display
        if (isset($row['departure_time'])) {
            // If the departure_time is already in 12-hour format with AM/PM, use it as-is
            // Otherwise, format it properly
            if (preg_match('/^(1[0-2]|0?[1-9]):[0-5][0-9] (am|pm)$/i', $row['departure_time'])) {
                // Already in correct format, keep as-is
                $row['departure_time'] = $row['departure_time'];
            } else {
                // Try to format it as a time
                $time = new DateTime($row['departure_time']);
                $row['departure_time'] = $time->format('g:i A');
            }
        }
        
        // Calculate amount based on route and seats
        $farePerSeat = 600; // Default fare
        if (isset($row['route']) && isset($routeFares[$row['route']])) {
            $farePerSeat = $routeFares[$row['route']];
        }
        
        $seats = isset($row['seats']) ? (int)$row['seats'] : 1;
        $row['amount'] = 'KSh ' . number_format($farePerSeat * $seats);
        
        $bookings[] = $row;
    }

    $response = [
        'success' => true,
        'user' => $user,
        'bookings' => $bookings,
        'todays_bookings' => $todays_bookings
    ];
    
    // Log successful response for debugging
    error_log("Profile data successfully prepared: " . print_r($response, true));
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'user_id' => $user_id,
            'session_keys' => array_keys($_SESSION),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    error_log("Profile.php error: " . $e->getMessage());
} finally {
    // Close statements and connection
    if (isset($user_stmt)) $user_stmt->close();
    if (isset($book_stmt)) $book_stmt->close();
    if (isset($conn)) $conn->close();
}

echo json_encode($response);
?>