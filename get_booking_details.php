<?php
// Script to fetch detailed booking information
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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

require_once 'db.php';

// Get booking ID from request
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if ($booking_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid booking ID']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // First, check what columns exist in the bookings table
    $columns_result = $conn->query("SHOW COLUMNS FROM bookings");
    $booking_columns = [];
    while ($col = $columns_result->fetch_assoc()) {
        $booking_columns[] = $col['Field'];
    }
    
    // Build the SELECT query dynamically based on available columns
    $select_fields = [
        "b.booking_id",
        "b.fullname",
        "b.phone",
        "b.route",
        "b.boarding_point",
        "b.travel_date",
        "b.departure_time",
        "b.seats",
        "b.payment_method",
        "b.assigned_vehicle",
        "b.created_at",
        "b.google_maps_link",
        "b.shared_location_updated",
        "u.name as passenger_name",
        "u.email as passenger_email"
    ];
    
    // Add amount column only if it exists
    if (in_array('amount', $booking_columns)) {
        $select_fields[] = "b.amount";
    }
    
    // Add vehicle join only if assigned_vehicle column exists
    $join_vehicle = "";
    if (in_array('assigned_vehicle', $booking_columns)) {
        $select_fields = array_merge($select_fields, [
            "v.number_plate",
            "v.type as vehicle_type",
            "v.color as vehicle_color",
            "v.driver_name",
            "v.driver_phone",
            "v.image_path"
        ]);
        $join_vehicle = "LEFT JOIN vehicles v ON b.assigned_vehicle = v.number_plate";
    }
    
    $select_sql = implode(", ", $select_fields);
    
    // Fetch detailed booking information with vehicle details
    $sql = "SELECT 
        $select_sql
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        $join_vehicle
        WHERE b.booking_id = ? AND b.user_id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $booking_id, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    
    if (!$booking) {
        echo json_encode(['success' => false, 'error' => 'Booking not found or access denied']);
        exit;
    }
    
    // Format the data for display
    $travel_date = new DateTime($booking['travel_date']);
    $booking['formatted_travel_date'] = $travel_date->format('F j, Y');
    
    $created_at = new DateTime($booking['created_at']);
    $booking['formatted_created_at'] = $created_at->format('F j, Y g:i A');
    
    if (isset($booking['shared_location_updated']) && $booking['shared_location_updated']) {
        $shared_updated = new DateTime($booking['shared_location_updated']);
        $booking['formatted_shared_updated'] = $shared_updated->format('F j, Y g:i A');
    }
    
    // Handle amount - use from database if exists, otherwise calculate
    if (isset($booking['amount']) && !empty($booking['amount'])) {
        $booking['amount'] = $booking['amount'];
    } else {
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
        
        // Calculate amount based on route and seats
        $farePerSeat = 600; // Default fare
        if (isset($booking['route']) && isset($routeFares[$booking['route']])) {
            $farePerSeat = $routeFares[$booking['route']];
        }
        
        $seats = isset($booking['seats']) ? (int)$booking['seats'] : 1;
        $booking['amount'] = 'KSh ' . number_format($farePerSeat * $seats);
    }
    
    // Determine status (completed 24 hours after travel date)
    $today = new DateTime();
    $completionTime = clone $travel_date;
    $completionTime->modify('+1 day'); // 24 hours after travel date
    
    if ($travel_date > $today) {
        $booking['status'] = 'Upcoming';
        $booking['status_class'] = 'status-confirmed';
    } else if ($completionTime < $today) {
        $booking['status'] = 'Completed';
        $booking['status_class'] = 'status-completed';
    } else {
        $booking['status'] = 'Today';
        $booking['status_class'] = 'status-confirmed';
    }
    
    echo json_encode([
        'success' => true,
        'booking' => $booking
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>