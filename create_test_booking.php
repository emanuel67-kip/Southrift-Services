<?php
require_once 'db.php';

echo "<h2>Create Test Booking</h2>";

// Create a test booking with the correct format
$testBooking = [
    'user_id' => 1, // Use an existing user ID
    'fullname' => 'Test Passenger',
    'phone' => '254799999999',
    'route' => 'Litein - Nairobi',
    'boarding_point' => 'Litein', // Exact case and spelling
    'travel_date' => date('Y-m-d'),
    'departure_time' => '8:00 am',
    'seats' => 1,
    'payment_method' => 'mpesa',
    'created_at' => date('Y-m-d H:i:s')
];

// Check if this exact booking already exists
$checkStmt = $conn->prepare("SELECT booking_id FROM bookings WHERE fullname = ? AND phone = ? AND boarding_point = ?");
$checkStmt->bind_param("sss", $testBooking['fullname'], $testBooking['phone'], $testBooking['boarding_point']);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows > 0) {
    echo "<p>⚠️ Test booking already exists. Skipping creation...</p>";
    $checkStmt->close();
} else {
    $checkStmt->close();
    
    // Insert the test booking
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, fullname, phone, route, boarding_point, travel_date, departure_time, seats, payment_method, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssisss", 
        $testBooking['user_id'],
        $testBooking['fullname'],
        $testBooking['phone'],
        $testBooking['route'],
        $testBooking['boarding_point'],
        $testBooking['travel_date'],
        $testBooking['departure_time'],
        $testBooking['seats'],
        $testBooking['payment_method'],
        $testBooking['created_at']
    );
    
    if ($stmt->execute()) {
        $bookingId = $stmt->insert_id;
        echo "<p>✅ Successfully created test booking with ID: <strong>$bookingId</strong></p>";
        echo "<p>Details:</p>";
        echo "<ul>";
        echo "<li><strong>Passenger:</strong> " . htmlspecialchars($testBooking['fullname']) . "</li>";
        echo "<li><strong>Route:</strong> " . htmlspecialchars($testBooking['route']) . "</li>";
        echo "<li><strong>Boarding Point:</strong> " . htmlspecialchars($testBooking['boarding_point']) . "</li>";
        echo "<li><strong>Date:</strong> " . htmlspecialchars($testBooking['travel_date']) . "</li>";
        echo "</ul>";
    } else {
        echo "<p>❌ Failed to create test booking: " . $conn->error . "</p>";
    }
    
    $stmt->close();
}

// Verify the booking was created correctly
echo "<h3>Verification</h3>";
$verifyStmt = $conn->prepare("SELECT booking_id, fullname, route, boarding_point FROM bookings WHERE boarding_point = ? AND DATE(created_at) = CURDATE() ORDER BY booking_id DESC LIMIT 1");
$verifyStmt->bind_param("s", $testBooking['boarding_point']);
$verifyStmt->execute();
$verifyResult = $verifyStmt->get_result();

if ($verifyResult && $verifyResult->num_rows > 0) {
    $booking = $verifyResult->fetch_assoc();
    echo "<p>✅ Booking verified in database:</p>";
    echo "<ul>";
    echo "<li><strong>Booking ID:</strong> " . htmlspecialchars($booking['booking_id']) . "</li>";
    echo "<li><strong>Passenger:</strong> " . htmlspecialchars($booking['fullname']) . "</li>";
    echo "<li><strong>Route:</strong> " . htmlspecialchars($booking['route']) . "</li>";
    echo "<li><strong>Boarding Point:</strong> " . htmlspecialchars($booking['boarding_point']) . "</li>";
    echo "</ul>";
} else {
    echo "<p>❌ Could not verify booking in database.</p>";
}

$verifyStmt->close();

// Show what the Litein admin should see
echo "<h3>What the Litein Admin Should See</h3>";
$adminStmt = $conn->prepare("SELECT booking_id, fullname, route, boarding_point FROM bookings WHERE DATE(created_at) = CURDATE() AND boarding_point = ? ORDER BY booking_id DESC");
$adminStmt->bind_param("s", $testBooking['boarding_point']);
$adminStmt->execute();
$adminResult = $adminStmt->get_result();

if ($adminResult && $adminResult->num_rows > 0) {
    echo "<table border='1' cellpadding='8' cellspacing='0'>";
    echo "<tr><th>Booking ID</th><th>Passenger</th><th>Route</th><th>Boarding Point</th></tr>";
    while ($row = $adminResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['booking_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['fullname']) . "</td>";
        echo "<td>" . htmlspecialchars($row['route']) . "</td>";
        echo "<td>" . htmlspecialchars($row['boarding_point']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p>✅ This booking should now be visible to the Litein admin.</p>";
} else {
    echo "<p>❌ No bookings found for the Litein admin. There may still be an issue with the station filtering.</p>";
}

$adminStmt->close();
$conn->close();

echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>Log in as the Litein admin (adminlitein@gmail.com)</li>";
echo "<li>Go to 'Today's Bookings' page</li>";
echo "<li>You should now see the test booking</li>";
echo "<li>If you still don't see it, run the diagnose_station_filtering.php script to identify the issue</li>";
echo "</ol>";
?>