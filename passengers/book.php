<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Enhanced cache control
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Initialize response array
$response = [
    'success' => false,
    'message' => ''
];

try {
    // DB config
    $host = 'localhost';
    $user = 'root';
    $password = '';
    $database = 'southrift';

    // Connect to database
    $conn = new mysqli($host, $user, $password, $database);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

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
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in. Please log in to make a booking.');
    }
    
    // Check if session has expired for passengers
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'passenger') {
        // Check if expires_at is set and has passed
        if (isset($_SESSION['expires_at']) && time() > $_SESSION['expires_at']) {
            // Session expired, destroy session
            session_unset();
            session_destroy();
            throw new Exception('Session expired. Please log in again.');
        }
        
        // Update last activity and extend session
        $_SESSION['last_activity'] = time();
        $_SESSION['expires_at'] = time() + 1800; // Extend by 30 minutes
    }
    
    // Additional validation for passenger role
    if (isset($_SESSION['role']) && $_SESSION['role'] !== 'passenger') {
        throw new Exception('Access denied - Booking is only available for passengers.');
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Collect and validate inputs
    $fullname = $_POST['fullname'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $route = $_POST['route'] ?? '';
    $boarding_point = $_POST['boarding_point'] ?? '';
    $travel_date = $_POST['travel_date'] ?? '';
    $departure_time = $_POST['departure_time'] ?? '';
    $seats = $_POST['seats'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';

    // Basic validation
    if (empty($fullname) || empty($phone) || empty($route) || empty($boarding_point) || 
        empty($travel_date) || empty($departure_time) || empty($seats) || empty($payment_method)) {
        throw new Exception('Please fill in all fields.');
    }

    // Validate travel date format
    $date = DateTime::createFromFormat('Y-m-d', $travel_date);
    if (!$date || $date->format('Y-m-d') !== $travel_date) {
        throw new Exception('Invalid travel date format.');
    }

    // Validate departure time format (should be in H:MM am/pm format)
    // We'll accept the time as-is without conversion since we're storing it in the same format
    if (!preg_match('/^(1[0-2]|0?[1-9]):[0-5][0-9] (am|pm)$/i', $departure_time)) {
        // If format is invalid, default to 8:00 am
        $departure_time = '8:00 am';
    }

    // Calculate amount based on route and seats
    $routeFares = [
        'nairobi-bomet' => 1200,
        'nairobi-kisumu' => 2000,
        'nairobi-nakuru' => 500,
        'litein-nairobi' => 1200,
        'kisumu-nairobi' => 2000,
        'nakuru-nairobi' => 500,
        'bomet-nairobi' => 1200
    ];
    
    $farePerSeat = 600; // Default fare
    if (isset($routeFares[$route])) {
        $farePerSeat = $routeFares[$route];
    }
    
    $amount = 'KSh ' . number_format($farePerSeat * (int)$seats);

    // Check what columns actually exist in the bookings table
    $table_check = $conn->query("SHOW COLUMNS FROM bookings");
    $available_columns = [];
    while ($col = $table_check->fetch_assoc()) {
        $available_columns[] = $col['Field'];
    }
    
    // Determine the correct user column name
    $user_column = 'user_id';
    if (in_array('passenger_id', $available_columns)) {
        $user_column = 'passenger_id';
    } elseif (!in_array('user_id', $available_columns)) {
        // Try to auto-fix by adding the user_id column
        $alter_sql = "ALTER TABLE bookings ADD COLUMN user_id INT NOT NULL AFTER booking_id";
        if ($conn->query($alter_sql)) {
            // Column added successfully, refresh available columns
            $table_check = $conn->query("SHOW COLUMNS FROM bookings");
            $available_columns = [];
            while ($col = $table_check->fetch_assoc()) {
                $available_columns[] = $col['Field'];
            }
            $user_column = 'user_id';
        } else {
            // Auto-fix failed, provide helpful error message
            throw new Exception('Database setup required: The bookings table is missing a user reference column. Please visit check_bookings_structure.php to fix this issue, or contact your system administrator.');
        }
    }
    
    // Build the INSERT query based on available columns
    $insert_columns = [$user_column];
    $insert_values = ['?'];
    $bind_types = 'i';
    $bind_params = [$user_id];
    
    // Add other columns if they exist
    $field_mapping = [
        'fullname' => $fullname,
        'phone' => $phone,
        'route' => $route,
        'boarding_point' => $boarding_point,
        'travel_date' => $travel_date,
        'departure_time' => $departure_time, // Store as 12-hour format with AM/PM
        'num_seats' => $seats,
        'seats' => $seats,
        'payment_method' => $payment_method,
        'amount' => $amount
    ];
    
    foreach ($field_mapping as $column => $value) {
        if (in_array($column, $available_columns)) {
            $insert_columns[] = $column;
            $insert_values[] = '?';
            if ($column === 'num_seats' || $column === 'seats') {
                $bind_types .= 'i';
            } else {
                $bind_types .= 's';
            }
            $bind_params[] = $value;
        }
    }
    
    // Add vehicle_id as NULL if the column exists (will be assigned later by admin)
    if (in_array('vehicle_id', $available_columns)) {
        $insert_columns[] = 'vehicle_id';
        $insert_values[] = 'NULL';
    }
    
    $sql = "INSERT INTO bookings (" . implode(', ', $insert_columns) . ") VALUES (" . implode(', ', $insert_values) . ")";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param($bind_types, ...$bind_params);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Booking successful!';
    } else {
        throw new Exception('Failed to save booking: ' . $stmt->error);
    }

    // Close connection
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    // Log the error for debugging
    error_log("Booking error: " . $e->getMessage());
}

// Return JSON response
echo json_encode($response);
?>