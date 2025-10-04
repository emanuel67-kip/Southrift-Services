<?php
require_once 'db.php';

if (isset($_GET['booking_id'])) {
    $booking_id = $_GET['booking_id'];

    $stmt = $conn->prepare("
        SELECT v.number_plate, v.driver_name, v.driver_phone, v.capacity
        FROM bookings b
        JOIN vehicles v ON b.vehicle_number_plate = v.number_plate
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($vehicle = $result->fetch_assoc()) {
        echo json_encode($vehicle);
    } else {
        echo json_encode(['error' => 'No vehicle assigned']);
    }
}
?>
