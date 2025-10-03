<?php
// Script to fetch passenger bookings with the updated table structure
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

require_once 'db.php';

$user_id = $_SESSION['user_id'];

try {
    // Fetch user bookings with all the new fields
    $stmt = $conn->prepare("SELECT 
        booking_id,
        fullname,
        phone,
        route,
        boarding_point,
        travel_date,
        departure_time,
        seats,
        payment_method,
        assigned_vehicle,
        created_at,
        google_maps_link,
        amount
        FROM bookings 
        WHERE user_id = ? 
        ORDER BY created_at DESC");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $bookings = [];
    
    while ($row = $result->fetch_assoc()) {
        // Format dates and times for display
        $travel_date = new DateTime($row['travel_date']);
        $row['formatted_travel_date'] = $travel_date->format('M j, Y');
        
        $created_at = new DateTime($row['created_at']);
        $row['formatted_created_at'] = $created_at->format('M j, Y g:i A');
        
        // Determine status based on travel date (completed 24 hours after travel date)
        $today = new DateTime();
        $completionTime = clone $travel_date;
        $completionTime->modify('+1 day'); // 24 hours after travel date
        
        if ($travel_date > $today) {
            $row['status'] = 'Upcoming';
            $row['status_class'] = 'status-confirmed';
        } else if ($completionTime < $today) {
            $row['status'] = 'Completed';
            $row['status_class'] = 'status-completed';
        } else {
            $row['status'] = 'Today';
            $row['status_class'] = 'status-confirmed';
        }
        
        // Use the amount from the database if available, otherwise calculate it
        if (!empty($row['amount'])) {
            $row['amount'] = $row['amount'];
        } else {
            // Format amount (assuming KSh 600 per seat as in the booking form)
            $row['amount'] = 'KSh ' . number_format($row['seats'] * 600);
        }
        
        $bookings[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'bookings' => $bookings
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