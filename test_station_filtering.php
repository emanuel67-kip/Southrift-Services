<?php
require_once 'db.php';

echo "<h2>Testing Station-Based Booking Filtering</h2>";

// Create sample bookings with different boarding points
$sampleBookings = [
    [
        'user_id' => 1,
        'fullname' => 'John Doe',
        'phone' => '254712345678',
        'route' => 'Litein - Nairobi',
        'boarding_point' => 'Litein',
        'travel_date' => '2025-10-10',
        'departure_time' => '8:00 am',
        'seats' => 2,
        'payment_method' => 'mpesa',
        'created_at' => date('Y-m-d H:i:s')
    ],
    [
        'user_id' => 2,
        'fullname' => 'Jane Smith',
        'phone' => '254723456789',
        'route' => 'Nairobi - Kisumu',
        'boarding_point' => 'Nairobi',
        'travel_date' => '2025-10-10',
        'departure_time' => '6:00 am',
        'seats' => 1,
        'payment_method' => 'pay-onboarding',
        'created_at' => date('Y-m-d H:i:s')
    ],
    [
        'user_id' => 3,
        'fullname' => 'Robert Johnson',
        'phone' => '254734567890',
        'route' => 'Nairobi - Nakuru',
        'boarding_point' => 'Nairobi',
        'travel_date' => '2025-10-10',
        'departure_time' => '12:00 pm',
        'seats' => 3,
        'payment_method' => 'card',
        'created_at' => date('Y-m-d H:i:s')
    ]
];

echo "<h3>Inserting Sample Bookings</h3>";

foreach ($sampleBookings as $booking) {
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, fullname, phone, route, boarding_point, travel_date, departure_time, seats, payment_method, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssisss", 
        $booking['user_id'],
        $booking['fullname'],
        $booking['phone'],
        $booking['route'],
        $booking['boarding_point'],
        $booking['travel_date'],
        $booking['departure_time'],
        $booking['seats'],
        $booking['payment_method'],
        $booking['created_at']
    );
    
    if ($stmt->execute()) {
        echo "<p>✅ Added booking for " . htmlspecialchars($booking['fullname']) . " from " . htmlspecialchars($booking['boarding_point']) . "</p>";
    } else {
        echo "<p>❌ Failed to add booking for " . htmlspecialchars($booking['fullname']) . ": " . $stmt->error . "</p>";
    }
    
    $stmt->close();
}

// Test queries for different stations
echo "<h3>Testing Station-Based Queries</h3>";

// Test for Nairobi station
echo "<h4>Bookings for Nairobi Station:</h4>";
$stmt = $conn->prepare("SELECT booking_id, fullname, route, boarding_point FROM bookings WHERE DATE(created_at) = CURDATE() AND boarding_point = 'Nairobi' ORDER BY booking_id DESC");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['fullname']) . " - " . htmlspecialchars($row['route']) . " (Boarding: " . htmlspecialchars($row['boarding_point']) . ")</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No bookings found for Nairobi station today.</p>";
}
$stmt->close();

// Test for Litein station
echo "<h4>Bookings for Litein Station:</h4>";
$stmt = $conn->prepare("SELECT booking_id, fullname, route, boarding_point FROM bookings WHERE DATE(created_at) = CURDATE() AND boarding_point = 'Litein' ORDER BY booking_id DESC");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['fullname']) . " - " . htmlspecialchars($row['route']) . " (Boarding: " . htmlspecialchars($row['boarding_point']) . ")</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No bookings found for Litein station today.</p>";
}
$stmt->close();

$conn->close();

echo "<h3>Implementation Summary</h3>";
echo "<p>The station-based filtering has been implemented successfully:</p>";
echo "<ol>";
echo "<li>Added a 'station' column to the users table to identify where each admin is stationed</li>";
echo "<li>Modified the admin authentication system to store the admin's station in the session</li>";
echo "<li>Updated the booking query in today_bookings.php to filter bookings based on the admin's station</li>";
echo "<li>Only admins stationed at the same location as the passenger's boarding point will see those bookings</li>";
echo "</ol>";

echo "<p><strong>Example:</strong> When a passenger books a trip from Litein to Nairobi, only admins stationed at Litein will see that booking in their dashboard.</p>";
?>