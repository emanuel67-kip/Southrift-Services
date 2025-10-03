<?php
require __DIR__ . '/auth.php';
require dirname(__DIR__) . '/db.php';
date_default_timezone_set('Africa/Nairobi');

// Get driver's phone from session
$driver_phone = $_SESSION['phone'] ?? '';
$driver_name = $_SESSION['name'] ?? 'Driver';

// First, get the vehicle(s) assigned to this driver
$vehicles = [];
$vehicle_stmt = $conn->prepare("SELECT number_plate FROM vehicles WHERE driver_phone = ?");
$vehicle_stmt->bind_param("s", $driver_phone);
$vehicle_stmt->execute();
$vehicle_result = $vehicle_stmt->get_result();
while ($row = $vehicle_result->fetch_assoc()) {
    $vehicles[] = $row['number_plate'];
}
$vehicle_stmt->close();

// Get today's bookings for this driver's vehicle(s)
$bookings = [];
$total_passengers = 0;
$total_seats = 0;
if (!empty($vehicles)) {
    // Create placeholders for the IN clause
    $placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
    
    $stmt = $conn->prepare("
        SELECT b.booking_id, b.fullname, b.phone, b.route, b.boarding_point, b.seats,
               b.travel_date, b.departure_time, b.created_at, b.assigned_vehicle,
               b.payment_method, v.type as vehicle_type, v.capacity as vehicle_capacity
        FROM bookings b
        LEFT JOIN vehicles v ON b.assigned_vehicle = v.number_plate
        WHERE b.assigned_vehicle IN ($placeholders)
        AND DATE(b.travel_date) = CURDATE()
        ORDER BY b.departure_time ASC, b.created_at ASC
    ");
    
    // Bind parameters dynamically
    $types = str_repeat('s', count($vehicles));
    $stmt->bind_param($types, ...$vehicles);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
        $total_passengers++;
        $total_seats += (int)($row['seats'] ?? 1);
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Today's Bookings – SouthRide Driver</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --purple: #6A0DAD;
      --purple-dark: #4e0b8a;
      --bg: #f4f4f4;
    }

    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
    }

    .fade-in {
      animation: fadeIn 0.6s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    nav {
      background: var(--purple);
      padding: 1rem 2rem;
      color: #fff;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }

    .logo {
      font-size: 1.5rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .nav-right {
      display: flex;
      gap: 20px;
      align-items: center;
    }

    .nav-right a {
      position: relative;
      color: paleturquoise;
      font-weight: 600;
      text-decoration: none;
      padding: 8px 10px;
      text-transform: uppercase;
      letter-spacing: 1px;
      transition: color 0.3s ease;
    }

    .nav-right a:hover {
      color: #fff;
    }

    .container {
      max-width: 1200px;
      margin: 2rem auto;
      padding: 0 1rem;
    }

    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }

    .page-title {
      font-size: 1.8rem;
      color: #333;
      margin: 0;
    }

    .back-btn {
      background: var(--purple);
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 5px;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 0.9rem;
    }

    .bookings-table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .bookings-table th,
    .bookings-table td {
      padding: 1rem;
      text-align: left;
      border-bottom: 1px solid #eee;
    }

    .bookings-table th {
      background-color: var(--purple);
      color: white;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.8rem;
      letter-spacing: 0.5px;
    }

    .bookings-table tr:last-child td {
      border-bottom: none;
    }

    .bookings-table tr:hover {
      background-color: #f9f9f9;
    }

    .status-badge {
      display: inline-block;
      padding: 0.25rem 0.5rem;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .status-pending {
      background-color: #fff3cd;
      color: #856404;
    }

    .status-confirmed {
      background-color: #d4edda;
      color: #155724;
    }

    .no-bookings {
      text-align: center;
      padding: 3rem 1rem;
      color: #666;
    }

    .no-bookings i {
      font-size: 3rem;
      color: #ddd;
      margin-bottom: 1rem;
      display: block;
    }

    /* Stats Section */
    .stats-section {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: white;
      border-radius: 12px;
      padding: 1.5rem;
      text-align: center;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      border-left: 4px solid var(--purple);
    }

    .stat-card.passengers {
      border-left-color: #4ECDC4;
    }

    .stat-card.seats {
      border-left-color: #45B7D1;
    }

    .stat-card.vehicle {
      border-left-color: #96CEB4;
    }

    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--purple);
      display: block;
    }

    .stat-label {
      color: #666;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-top: 0.5rem;
    }

    /* Contact Actions */
    .contact-actions {
      display: flex;
      gap: 0.5rem;
    }

    .contact-btn {
      background: #25D366;
      color: white;
      border: none;
      padding: 0.4rem 0.8rem;
      border-radius: 20px;
      text-decoration: none;
      font-size: 0.8rem;
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
      transition: all 0.3s ease;
    }

    .contact-btn.call {
      background: #007BFF;
    }

    .contact-btn:hover {
      transform: scale(1.05);
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }

    /* Enhanced Table Styling */
    .passenger-name {
      font-weight: 600;
      color: #333;
    }

    .route-info {
      color: var(--purple);
      font-weight: 500;
    }

    .seat-count {
      background: #E3F2FD;
      color: #1976D2;
      padding: 0.2rem 0.6rem;
      border-radius: 12px;
      font-weight: 600;
      font-size: 0.8rem;
    }

    @media (max-width: 768px) {
      .bookings-table {
        display: block;
        overflow-x: auto;
      }
      
      .container {
        padding: 0 0.5rem;
      }
      
      .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
      }
      
      .nav-right {
        flex-wrap: wrap;
        gap: 10px;
      }
    }
  </style>
</head>
<body class="fade-in">
  <!-- Navbar -->
  <nav>
    <div class="logo">
      <i class="fas fa-bus"></i> SouthRift Driver
    </div>
    <div class="nav-right">
      <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
      <a href="../logout_new.php">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
  </nav>

  <div class="container">
    <div class="page-header">
      <h1 class="page-title">
        <i class="fas fa-users"></i> My Assigned Passengers (<?= date('M j, Y') ?>)
      </h1>
      <a href="index.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
      </a>
    </div>

    <!-- Statistics Section -->
    <?php if (!empty($bookings)): ?>
    <div class="stats-section">
      <div class="stat-card passengers">
        <span class="stat-number"><?= $total_passengers ?></span>
        <span class="stat-label">Total Passengers</span>
      </div>
      <div class="stat-card seats">
        <span class="stat-number"><?= $total_seats ?></span>
        <span class="stat-label">Total Seats Booked</span>
      </div>
      <div class="stat-card vehicle">
        <span class="stat-number"><?= count($vehicles) ?></span>
        <span class="stat-label">Vehicle(s) Assigned</span>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($bookings)): ?>
      <div class="table-responsive">
        <table class="bookings-table">
          <thead>
            <tr>
              <th>Passenger Details</th>
              <th>Contact</th>
              <th>Route</th>
              <th>Boarding Point</th>
              <th>Seats</th>
              <th>Departure Time</th>
              <th>Payment Method</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $booking): ?>
              <tr>
                <td>
                  <div class="passenger-name">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($booking['fullname']) ?>
                  </div>
                </td>
                <td>
                  <div style="margin-bottom: 0.5rem;">
                    <strong><?= htmlspecialchars($booking['phone']) ?></strong>
                  </div>
                  <div class="contact-actions">
                    <a href="tel:<?= htmlspecialchars($booking['phone']) ?>" class="contact-btn call">
                      <i class="fas fa-phone"></i> Call
                    </a>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $booking['phone']) ?>" 
                       class="contact-btn" target="_blank">
                      <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                  </div>
                </td>
                <td>
                  <div class="route-info">
                    <i class="fas fa-route"></i> <?= htmlspecialchars($booking['route']) ?>
                  </div>
                </td>
                <td><?= htmlspecialchars($booking['boarding_point']) ?></td>
                <td>
                  <span class="seat-count">
                    <i class="fas fa-chair"></i> <?= !empty($booking['seats']) ? htmlspecialchars($booking['seats']) : '1' ?>
                  </span>
                </td>
                <td>
                  <?php 
                    $departure_time = !empty($booking['departure_time']) ? 
                      date('h:i A', strtotime($booking['departure_time'])) : 'N/A';
                    echo '<i class="fas fa-clock"></i> ' . htmlspecialchars($departure_time);
                  ?>
                </td>
                <td>
                  <?= htmlspecialchars($booking['payment_method'] ?? 'Not specified') ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="no-bookings">
        <i class="fas fa-users-slash"></i>
        <h3>No passengers assigned today</h3>
        <p>You don't have any passengers assigned to your vehicle for today.</p>
        <p style="color: #999; font-size: 0.9rem; margin-top: 1rem;">
          <i class="fas fa-info-circle"></i> 
          Check back later or contact dispatch if you expect passengers.
        </p>
      </div>
    <?php endif; ?>
  </div>

  <script>
    // Add any necessary JavaScript here
    document.addEventListener('DOMContentLoaded', function() {
      // Add any initialization code here
    });
  </script>
</body>
</html>
