<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get booking ID
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
if ($booking_id <= 0) die('Invalid booking ID');

// Fetch booking with vehicle details
$stmt = $conn->prepare("
    SELECT 
        b.*,
        v.color,
        v.type,
        v.driver_name,
        v.driver_phone
    FROM bookings b
    LEFT JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE b.id = ? AND b.user_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) die('Booking not found');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Your Ride Details - SouthRift Services</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #6A0DAD; --light-bg: #f8f9fa; }
        body { font-family: 'Poppins', sans-serif; margin: 0; padding: 20px; background: var(--light-bg); }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; }
        .card-header { background: var(--primary); color: white; padding: 20px; text-align: center; }
        .card-body { padding: 20px; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .info-item { background: var(--light-bg); padding: 15px; border-radius: 8px; border-left: 4px solid var(--primary); }
        .driver-card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-top: 20px; }
        .contact-btn { display: inline-flex; align-items: center; gap: 5px; background: var(--primary); color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; margin-right: 10px; }
        .back-btn { display: inline-block; margin-top: 20px; color: var(--primary); text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>Your Ride Details</h1>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <h3>Route</h3>
                        <p><?= htmlspecialchars($booking['route']) ?></p>
                    </div>
                    <div class="info-item">
                        <h3>Travel Date</h3>
                        <p><?= date('F j, Y', strtotime($booking['travel_date'])) ?></p>
                    </div>
                    <div class="info-item">
                        <h3>Departure Time</h3>
                        <p><?= date('h:i A', strtotime($booking['departure_time'])) ?></p>
                    </div>
                    <div class="info-item">
                        <h3>Vehicle Number</h3>
                        <p><?= $booking['assigned_vehicle'] ?: 'Not assigned yet' ?></p>
                    </div>
                </div>
                
                <?php if ($booking['assigned_vehicle']): ?>
                    <div class="driver-card">
                        <h2>Your Driver</h2>
                        <p><strong>Name:</strong> <?= htmlspecialchars($booking['driver_name']) ?></p>
                        <p><strong>Vehicle:</strong> <?= htmlspecialchars($booking['type']) ?> (<?= htmlspecialchars($booking['color']) ?>)</p>
                        <p><strong>Plate:</strong> <?= htmlspecialchars($booking['assigned_vehicle']) ?></p>
                        
                        <?php if ($booking['driver_phone']): ?>
                        <div style="margin-top: 15px;">
                            <a href="tel:<?= htmlspecialchars($booking['driver_phone']) ?>" class="contact-btn">
                                <i class="fas fa-phone-alt"></i> Call Driver
                            </a>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $booking['driver_phone']) ?>" class="contact-btn" target="_blank">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <a href="my-bookings.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to My Bookings
                </a>
            </div>
        </div>
    </div>
</body>
</html>
