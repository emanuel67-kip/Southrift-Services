<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please log in first.'); window.location.href='login.html';</script>";
    exit;
}

$host = 'localhost';
$user = 'root';
$password = '';
$db = 'southrift';

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$fullname = $_POST['fullname'];
$phone = $_POST['phone'];
$route = $_POST['route'];
$travel_date = $_POST['travel_date'];
$departure_time = $_POST['departure_time'];
$seats = $_POST['seats'];
$seat_number = $_POST['seat_number'] ?? '';
$payment_method = $_POST['payment_method'];

$sql = "INSERT INTO bookings (user_id, fullname, phone, route, travel_date, departure_time, seats, seat_number, payment_method)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("issssssss", $user_id, $fullname, $phone, $route, $travel_date, $departure_time, $seats, $seat_number, $payment_method);

if ($stmt->execute()) {
    echo "<script>alert('Thank you, your booking has been received! Check your profile to confirm your booking.'); window.location.href='profile.html';</script>";
} else {
    echo "Booking failednop: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
