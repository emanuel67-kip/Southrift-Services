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
    
    // Set the same session name as login system
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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'error' => 'Unauthorized - Please log in', 
        'debug' => 'Session user_id not set',
        'session_data' => isset($_SESSION) ? array_keys($_SESSION) : 'No session'
    ]);
    exit;
}

// Check if session has expired for passengers
if (isset($_SESSION['role']) && $_SESSION['role'] === 'passenger') {
    // Check if expires_at is set and has passed
    if (isset($_SESSION['expires_at']) && time() > $_SESSION['expires_at']) {
        // Session expired, destroy session
        session_unset();
        session_destroy();
        echo json_encode([
            'error' => 'Session expired - Please log in again'
        ]);
        exit;
    }
    
    // Update last activity and extend session
    $_SESSION['last_activity'] = time();
    $_SESSION['expires_at'] = time() + 1800; // Extend by 30 minutes
}

// Additional validation for passenger role
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'passenger') {
    echo json_encode([
        'error' => 'Access denied - This profile is for passengers only',
        'debug' => 'User role: ' . $_SESSION['role']
    ]);
    exit;
}

$host = 'localhost';
$user = 'root';
$password = '';
$db = 'southrift';

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
    echo json_encode([
        'error' => 'Database connection failed: ' . $conn->connect_error,
        'debug' => 'Check database server and credentials'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$response = [];

try {
    // Get user info with proper error handling
    $user_stmt = $conn->prepare("SELECT name, email, phone, created_at FROM users WHERE id = ?");
    if (!$user_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $user_stmt->bind_param("i", $user_id);
    if (!$user_stmt->execute()) {
        throw new Exception("Execute failed: " . $user_stmt->error);
    }
    
    $user_result = $user_stmt->get_result();
    $user = $user_result->fetch_assoc();
    
    if (!$user) {
        throw new Exception("User not found with ID: " . $user_id . ". Please check if user exists in database.");
    }
    
    // Format the created_at date
    if (isset($user['created_at'])) {
        $date = new DateTime($user['created_at']);
        $user['created_at'] = $date->format('F j, Y');
    }

    // Get user bookings with safe column handling
    // First, determine the correct user column name and check all available columns
    $user_column = 'user_id';
    $check_columns = $conn->query("SHOW COLUMNS FROM bookings");
    $available_columns = [];
    $found_user_column = false;
    
    while ($col = $check_columns->fetch_assoc()) {
        $available_columns[] = $col['Field'];
        if ($col['Field'] === 'user_id') {
            $user_column = 'user_id';
            $found_user_column = true;
        } elseif ($col['Field'] === 'passenger_id') {
            $user_column = 'passenger_id';
            $found_user_column = true;
        }
    }
    
    if (!$found_user_column) {
        // If no user reference column found, return empty bookings
        $bookings = [];
    } else {
        // Build dynamic SELECT query based on available columns
        $select_fields = ['booking_id'];
        
        // Add fields that exist in the table, with fallbacks for missing ones
        $field_mappings = [
            'route' => ['route', 'pickup_location', 'from_location'],
            'boarding_point' => ['boarding_point', 'pickup_location', 'pickup_point'],
            'travel_date' => ['travel_date', 'booking_date', 'date'],
            'departure_time' => ['departure_time', 'time', 'pickup_time'],
            'seats' => ['num_seats', 'seats', 'passenger_count'],
            'payment_method' => ['payment_method', 'payment_type']
        ];
        
        foreach ($field_mappings as $alias => $possible_columns) {
            $found = false;
            foreach ($possible_columns as $col) {
                if (in_array($col, $available_columns)) {
                    if ($alias === 'seats' && $col === 'num_seats') {
                        $select_fields[] = "$col AS seats";
                    } else {
                        $select_fields[] = ($col === $alias) ? $col : "$col AS $alias";
                    }
                    $found = true;
                    break;
                }
            }
            
            // Add fallback if column not found
            if (!$found) {
                switch ($alias) {
                    case 'route':
                        $select_fields[] = "'Route not specified' AS route";
                        break;
                    case 'boarding_point':
                        $select_fields[] = "'Boarding point not specified' AS boarding_point";
                        break;
                    case 'travel_date':
                        $select_fields[] = "DATE(created_at) AS travel_date";
                        break;
                    case 'departure_time':
                        $select_fields[] = "TIME(created_at) AS departure_time";
                        break;
                    case 'seats':
                        $select_fields[] = "1 AS seats";
                        break;
                    case 'payment_method':
                        $select_fields[] = "'Not specified' AS payment_method";
                        break;
                }
            }
        }
        
        // Always include created_at and status
        $select_fields[] = 'created_at';
        $select_fields[] = "'active' AS status";
        // Include assigned_vehicle for tracking functionality
        $select_fields[] = 'assigned_vehicle';
        
        $select_sql = implode(', ', $select_fields);
        
        $book_stmt = $conn->prepare("SELECT 
            $select_sql
            FROM bookings 
            WHERE $user_column = ?
            ORDER BY created_at DESC");
        
        if (!$book_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $book_stmt->bind_param("i", $user_id);
        if (!$book_stmt->execute()) {
            throw new Exception("Execute failed: " . $book_stmt->error);
        }
        
        $book_result = $book_stmt->get_result();
        $bookings = [];
        $todays_bookings = [];
        
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
    }

    $response = [
        'success' => true,
        'user' => $user,
        'bookings' => $bookings,
        'todays_bookings' => $todays_bookings
    ];
    
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
} finally {
    // Close statements and connection
    if (isset($user_stmt)) $user_stmt->close();
    if (isset($book_stmt)) $book_stmt->close();
    $conn->close();
}

echo json_encode($response);
?>