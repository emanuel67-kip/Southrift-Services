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

// Include database connection with error handling
if (!file_exists('../db.php')) {
    echo json_encode(['success' => false, 'error' => 'Database connection file not found']);
    exit;
}

require_once '../db.php';

// Check if $conn is properly initialized
if (!isset($conn) || !$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get booking ID from request
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if ($booking_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid booking ID']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Add debugging
error_log("Fetching booking details for booking_id: $booking_id, user_id: $user_id");

try {
    // Fetch detailed booking information
    $sql = "SELECT 
        b.booking_id,
        b.user_id,
        b.fullname,
        b.phone,
        b.route,
        b.boarding_point,
        b.travel_date,
        b.departure_time,
        b.seats,
        b.payment_method,
        b.assigned_vehicle,
        b.created_at,
        b.google_maps_link,
        b.shared_location_updated
        FROM bookings b
        WHERE b.booking_id = ? AND b.user_id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $booking_id, $user_id);
    
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    
    if (!$booking) {
        error_log("Booking not found or access denied for booking_id: $booking_id, user_id: $user_id");
        echo json_encode(['success' => false, 'error' => 'Booking not found or access denied']);
        exit;
    }
    
    error_log("Booking found: " . print_r($booking, true));
    
    // Format the data for display
    $travel_date = new DateTime($booking['travel_date']);
    $booking['formatted_travel_date'] = $travel_date->format('F j, Y');
    
    $created_at = new DateTime($booking['created_at']);
    $booking['formatted_created_at'] = $created_at->format('F j, Y g:i A');
    
    if (isset($booking['shared_location_updated']) && $booking['shared_location_updated']) {
        $shared_updated = new DateTime($booking['shared_location_updated']);
        $booking['formatted_shared_updated'] = $shared_updated->format('F j, Y g:i A');
    }
    
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
    
    // Add booking_id to the response for tracking purposes
    $booking['booking_id'] = $booking_id;
    
    // Get vehicle details if assigned
    if (!empty($booking['assigned_vehicle'])) {
        error_log("Fetching vehicle details for: " . $booking['assigned_vehicle']);
        $vehicle_sql = "SELECT number_plate, type as vehicle_type, color as vehicle_color, driver_name, driver_phone FROM vehicles WHERE number_plate = ?";
        $vehicle_stmt = $conn->prepare($vehicle_sql);
        
        if ($vehicle_stmt) {
            $vehicle_stmt->bind_param("s", $booking['assigned_vehicle']);
            if ($vehicle_stmt->execute()) {
                $vehicle_result = $vehicle_stmt->get_result();
                $vehicle = $vehicle_result->fetch_assoc();
                if ($vehicle) {
                    error_log("Vehicle found: " . print_r($vehicle, true));
                    $booking = array_merge($booking, $vehicle);
                } else {
                    error_log("No vehicle found with plate: " . $booking['assigned_vehicle']);
                }
            } else {
                error_log("Vehicle query execute failed: " . $vehicle_stmt->error);
            }
            $vehicle_stmt->close();
        } else {
            error_log("Vehicle query prepare failed: " . $conn->error);
        }
    } else {
        error_log("No assigned vehicle for this booking");
    }
    
    error_log("Final booking data: " . print_r($booking, true));
    
    echo json_encode([
        'success' => true,
        'booking' => $booking
    ]);
    
} catch (Exception $e) {
    error_log("Exception in get_booking_details.php: " . $e->getMessage());
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