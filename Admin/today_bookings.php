<?php
require __DIR__ . '/auth.php';
require dirname(__DIR__) . '/db.php';
date_default_timezone_set('Africa/Nairobi');

// Handle assignment form submit
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
    $vehicle_plate = isset($_POST['vehicle_number_plate']) ? trim($_POST['vehicle_number_plate']) : '';

    if ($booking_id > 0 && $vehicle_plate !== '') {
        // Assign the vehicle to the booking
        $stmtA = $conn->prepare("UPDATE bookings SET assigned_vehicle = ? WHERE booking_id = ?");
        $stmtA->bind_param('si', $vehicle_plate, $booking_id);
        if ($stmtA->execute()) {
            // Mark vehicle as active but keep it in waiting until capacity is filled
            $stmtV = $conn->prepare("UPDATE vehicles SET is_active = 1 WHERE number_plate = ?");
            $stmtV->bind_param('s', $vehicle_plate);
            $stmtV->execute();
            $stmtV->close();
            $message = "<div class='alert success'>✅ Assigned $vehicle_plate to booking #$booking_id</div>";
        } else {
            $message = "<div class='alert error'>❌ Failed to assign vehicle. Please try again.</div>";
        }
        $stmtA->close();
    } else {
        $message = "<div class='alert info'>ℹ️ Please select a vehicle.</div>";
    }
}

// Handle undo (unassign) action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unassign'])) {
    $booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
    if ($booking_id > 0) {
        // Find current assigned vehicle
        $stmtGet = $conn->prepare("SELECT assigned_vehicle FROM bookings WHERE booking_id = ?");
        $stmtGet->bind_param('i', $booking_id);
        if ($stmtGet->execute()) {
            $res = $stmtGet->get_result();
            $row = $res->fetch_assoc();
            $plate = $row['assigned_vehicle'] ?? null;
            $stmtGet->close();

            // Clear assignment
            $stmtClr = $conn->prepare("UPDATE bookings SET assigned_vehicle = NULL WHERE booking_id = ?");
            $stmtClr->bind_param('i', $booking_id);
            if ($stmtClr->execute()) {
                $stmtClr->close();
                if ($plate) {
                    // Return vehicle to waiting pool
                    $stmtVeh = $conn->prepare("UPDATE vehicles SET is_waiting = 1, is_active = 0 WHERE number_plate = ?");
                    $stmtVeh->bind_param('s', $plate);
                    $stmtVeh->execute();
                    $stmtVeh->close();
                }
                $message = "<div class='alert info'>↩️ Unassigned vehicle from booking #$booking_id</div>";
            } else {
                $message = "<div class='alert error'>❌ Failed to unassign. Please try again.</div>";
            }
        } else {
            $message = "<div class='alert error'>❌ Failed to load assignment.</div>";
        }
    }
}

// Preload vehicles for selector (check if is_waiting column exists)
$waitingVehicles = [];

// Check if is_waiting column exists in vehicles table
$columnsResult = $conn->query("SHOW COLUMNS FROM vehicles LIKE 'is_waiting'");
$hasWaitingColumn = $columnsResult && $columnsResult->num_rows > 0;

if ($hasWaitingColumn) {
    // Use is_waiting column if it exists
    $resWait = $conn->query("SELECT number_plate, type, color, capacity, driver_name FROM vehicles WHERE is_waiting = 1 ORDER BY number_plate");
} else {
    // Fallback: get all vehicles (you may want to adjust this query based on your needs)
    $resWait = $conn->query("SELECT number_plate, type, color, capacity, driver_name FROM vehicles ORDER BY number_plate");
}

if ($resWait) {
    while ($v = $resWait->fetch_assoc()) { $waitingVehicles[] = $v; }
}

/* ── bookings created today ── */
// Check bookings table structure for dynamic column detection
$bookingsColumns = [];
$columnsResult = $conn->query("SHOW COLUMNS FROM bookings");
if ($columnsResult) {
    while ($col = $columnsResult->fetch_assoc()) {
        $bookingsColumns[] = $col['Field'];
    }
}

// Map required fields to actual column names
$idField = in_array('booking_id', $bookingsColumns) ? 'booking_id' : 
           (in_array('id', $bookingsColumns) ? 'id' : 
           (in_array('bookingid', $bookingsColumns) ? 'bookingid' : 'phone')); // fallback to phone for uniqueness

$fullnameField = in_array('fullname', $bookingsColumns) ? 'fullname' : 
                (in_array('full_name', $bookingsColumns) ? 'full_name' : 
                (in_array('name', $bookingsColumns) ? 'name' : 'phone')); // fallback

$phoneField = in_array('phone', $bookingsColumns) ? 'phone' : 
             (in_array('phonenumber', $bookingsColumns) ? 'phonenumber' : 
             (in_array('phone_number', $bookingsColumns) ? 'phone_number' : 'phone'));

$routeField = in_array('route', $bookingsColumns) ? 'route' : 
             (in_array('destination', $bookingsColumns) ? 'destination' : 'route');

$boardingField = in_array('boarding_point', $bookingsColumns) ? 'boarding_point' : 
                (in_array('pickup_point', $bookingsColumns) ? 'pickup_point' : 
                (in_array('pickup', $bookingsColumns) ? 'pickup' : 'boarding_point'));

$travelDateField = in_array('travel_date', $bookingsColumns) ? 'travel_date' : 
                  (in_array('date', $bookingsColumns) ? 'date' : 
                  (in_array('booking_date', $bookingsColumns) ? 'booking_date' : 'travel_date'));

$departureField = in_array('departure_time', $bookingsColumns) ? 'departure_time' : 
                 (in_array('time', $bookingsColumns) ? 'time' : 
                 (in_array('departure', $bookingsColumns) ? 'departure' : 'departure_time'));

$seatsField = in_array('seats', $bookingsColumns) ? 'seats' : 
             (in_array('num_seats', $bookingsColumns) ? 'num_seats' : 
             (in_array('seat_count', $bookingsColumns) ? 'seat_count' : 'seats'));

$paymentField = in_array('payment_method', $bookingsColumns) ? 'payment_method' : 
               (in_array('payment', $bookingsColumns) ? 'payment' : 
               (in_array('payment_type', $bookingsColumns) ? 'payment_type' : 'payment_method'));

$createdField = in_array('created_at', $bookingsColumns) ? 'created_at' : 
               (in_array('timestamp', $bookingsColumns) ? 'timestamp' : 
               (in_array('date_created', $bookingsColumns) ? 'date_created' : 'created_at'));

$assignedVehicleField = in_array('assigned_vehicle', $bookingsColumns) ? 'assigned_vehicle' : 
                       (in_array('vehicle', $bookingsColumns) ? 'vehicle' : 
                       (in_array('car', $bookingsColumns) ? 'car' : 
                       (in_array('vehicle_assigned', $bookingsColumns) ? 'vehicle_assigned' : null)));

// Build dynamic query - only include assigned_vehicle if the column exists
// Add station filtering based on admin's station (extracted from route)
$stationFilter = "";
if (!empty($_SESSION['admin_station'])) {
    // Extract the starting station from the route (e.g., 'litein-nairobi' -> 'litein')
    $stationFilter = " AND SUBSTRING_INDEX($routeField, '-', 1) = '" . $conn->real_escape_string(strtolower($_SESSION['admin_station'])) . "'";
}

if ($assignedVehicleField) {
    $query = "SELECT $idField as id, $fullnameField as fullname, $phoneField as phone, 
              $routeField as route, $boardingField as boarding_point, $travelDateField as travel_date, 
              $departureField as departure_time, $seatsField as seats, $paymentField as payment_method, 
              $createdField as created_at, $assignedVehicleField as assigned_vehicle
              FROM bookings 
              WHERE DATE($createdField) = CURDATE() 
              $stationFilter
              ORDER BY $idField DESC";
} else {
    // Fallback query without assigned_vehicle field
    $query = "SELECT $idField as id, $fullnameField as fullname, $phoneField as phone, 
              $routeField as route, $boardingField as boarding_point, $travelDateField as travel_date, 
              $departureField as departure_time, $seatsField as seats, $paymentField as payment_method, 
              $createdField as created_at, '' as assigned_vehicle
              FROM bookings 
              WHERE DATE($createdField) = CURDATE() 
              $stationFilter
              ORDER BY $idField DESC";
}

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Bookings Made Today – Southrift Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      --purple: #6A0DAD;
      --purple-dark: #58009c;
      --accent: #ff6b6b;
      --success: #51cf66;
      --warning: #ffd43b;
      --info: #74c0fc;
      --bg: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      --card-shadow: 0 10px 30px rgba(0,0,0,0.1);
      --card-hover: 0 15px 40px rgba(0,0,0,0.15);
    }

    * {
      box-sizing: border-box;
    }

    html {
      animation: fadeIn 0.8s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      overflow-x: hidden;
    }

    /* Enhanced Navigation */
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
      animation: logoGlow 2s ease-in-out infinite alternate;
    }

    @keyframes logoGlow {
      0% { text-shadow: 0 0 8px #fff, 0 0 12px #0ff; }
      100% { text-shadow: 0 0 12px #fff, 0 0 20px #f0f; }
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

    .nav-right a::after {
      content: "";
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 2px;
      background: linear-gradient(to right, #ff6ec4, #7873f5);
      transform: scaleX(0);
      transform-origin: right;
      transition: transform 0.4s ease-in-out;
    }

    .nav-right a:hover {
      color: #00ffff;
      text-shadow: 0 0 8px rgba(0, 255, 255, 0.6);
    }

    .nav-right a:hover::after {
      transform: scaleX(1);
      transform-origin: left;
    }

    /* Main Content Container */
    main {
      max-width: 1200px;
      margin: 40px auto;
      background: rgba(255,255,255,0.95);
      padding: 40px;
      border-radius: 20px;
      box-shadow: var(--card-shadow);
      backdrop-filter: blur(10px);
      transition: box-shadow 0.3s ease;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    main:hover {
      box-shadow: var(--card-hover);
    }

    /* Header Section */
    .page-header {
      background: var(--primary-gradient);
      color: white;
      padding: 30px;
      border-radius: 15px;
      margin-bottom: 30px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .page-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="40" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="80" r="1" fill="rgba(255,255,255,0.1)"/></svg>');
      animation: float 6s ease-in-out infinite;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px) rotate(0deg); }
      50% { transform: translateY(-10px) rotate(180deg); }
    }

    .page-header h2 {
      margin: 0;
      font-size: 2.2rem;
      font-weight: 700;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
      position: relative;
      z-index: 1;
    }

    .page-subtitle {
      margin-top: 10px;
      font-size: 1.1rem;
      opacity: 0.9;
      position: relative;
      z-index: 1;
    }

    /* Stats Cards */
    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 25px;
      border-radius: 15px;
      text-align: center;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 35px rgba(0,0,0,0.2);
    }

    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 10px;
    }

    .stat-label {
      font-size: 0.9rem;
      opacity: 0.9;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    /* Enhanced Alerts */
    .alert {
      margin: 15px 0;
      padding: 15px 20px;
      border-radius: 12px;
      border: none;
      font-weight: 500;
      position: relative;
      overflow: hidden;
      animation: slideIn 0.5s ease-out;
    }

    @keyframes slideIn {
      from { opacity: 0; transform: translateX(-20px); }
      to { opacity: 1; transform: translateX(0); }
    }

    .alert::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 4px;
      height: 100%;
      background: currentColor;
    }

    .alert.success {
      background: linear-gradient(135deg, #e7f9ef 0%, #d4edda 100%);
      color: #0f7b3f;
      border-left: 4px solid var(--success);
    }

    .alert.error {
      background: linear-gradient(135deg, #fdecea 0%, #f8d7da 100%);
      color: #b00020;
      border-left: 4px solid var(--accent);
    }

    .alert.info {
      background: linear-gradient(135deg, #e7f0ff 0%, #cce7ff 100%);
      color: #0b4db3;
      border-left: 4px solid var(--info);
    }

    /* Enhanced Table */
    .table-container {
      background: white;
      border-radius: 15px;
      overflow: hidden;
      overflow-x: auto;
      box-shadow: 0 8px 25px rgba(0,0,0,0.08);
      margin-top: 25px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      flex: 1;
      min-width: 1000px;
    }

    th {
      background: var(--primary-gradient);
      color: white;
      padding: 12px 8px;
      text-align: left;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-size: 0.75rem;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    th:last-child {
      width: 180px;
      min-width: 180px;
      max-width: 180px;
    }

    td {
      padding: 10px 8px;
      border-bottom: 1px solid rgba(0,0,0,0.05);
      vertical-align: middle;
      transition: background-color 0.2s ease;
      font-size: 0.85rem;
    }

    td:last-child {
      width: 180px;
      min-width: 180px;
      max-width: 180px;
      padding: 8px 4px;
    }

    tbody tr {
      transition: all 0.2s ease;
    }

    tbody tr:nth-child(even) {
      background: rgba(102, 126, 234, 0.02);
    }

    tbody tr:hover {
      background: rgba(102, 126, 234, 0.08);
      transform: scale(1.001);
    }

    /* Enhanced Forms and Buttons */
    .assign-form {
      display: flex;
      gap: 4px;
      align-items: center;
      flex-wrap: wrap;
      max-width: 180px;
    }

    .assign-form select {
      padding: 6px 8px;
      border-radius: 6px;
      border: 1px solid rgba(102, 126, 234, 0.2);
      background: white;
      font-size: 0.8rem;
      min-width: 120px;
      max-width: 120px;
      transition: all 0.2s ease;
    }

    .assign-form select:focus {
      outline: none;
      border-color: var(--purple);
      box-shadow: 0 0 0 3px rgba(106, 13, 173, 0.1);
    }

    .assign-form button {
      padding: 6px 10px;
      border-radius: 6px;
      border: none;
      font-weight: 600;
      cursor: pointer;
      font-size: 0.75rem;
      transition: all 0.2s ease;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      white-space: nowrap;
    }

    .assign-form button[name="assign"] {
      background: linear-gradient(135deg, var(--success) 0%, #40c057 100%);
      color: white;
    }

    .assign-form button[name="assign"]:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(81, 207, 102, 0.3);
    }

    .assign-form .undo-btn {
      background: linear-gradient(135deg, var(--accent) 0%, #ff5252 100%);
      color: white;
    }

    .assign-form .undo-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
    }

    /* Phone Button Styling */
    .btn-info {
      background: linear-gradient(135deg, var(--info) 0%, #339af0 100%);
      color: white;
      padding: 6px 12px;
      border-radius: 20px;
      text-decoration: none;
      font-size: 0.85rem;
      font-weight: 500;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .btn-info:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(116, 192, 252, 0.3);
      text-decoration: none;
      color: white;
    }

    /* Vehicle Status Indicators */
    .vehicle-status {
      padding: 4px 10px;
      border-radius: 15px;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .vehicle-assigned {
      background: linear-gradient(135deg, var(--success) 0%, #40c057 100%);
      color: white;
    }

    .vehicle-unassigned {
      background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
      color: #6c757d;
    }

    /* Back Button */
    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      margin-top: 30px;
      padding: 12px 25px;
      background: var(--primary-gradient);
      color: white;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .back-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
      text-decoration: none;
      color: white;
    }

    /* Footer */
    footer {
      background: var(--purple);
      color: #fff;
      text-align: center;
      padding: 1rem;
      margin-top: auto;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      main {
        margin: 20px;
        padding: 25px;
        min-height: calc(100vh - 160px);
      }

      .page-header h2 {
        font-size: 1.8rem;
      }

      .stats-container {
        grid-template-columns: 1fr;
        gap: 15px;
      }

      .table-container {
        overflow-x: auto;
        margin: 20px -25px;
        border-radius: 0;
      }

      table {
        min-width: 1200px;
      }

      .assign-form {
        flex-direction: column;
        align-items: stretch;
        gap: 3px;
        max-width: 150px;
      }

      .assign-form select,
      .assign-form button {
        width: 100%;
        font-size: 0.7rem;
        padding: 4px 6px;
      }
    }

    @media (max-width: 600px) {
      .nav-right {
        flex-direction: column;
        gap: 10px;
        align-items: flex-end;
      }
    }

    @media (max-width: 480px) {
      main {
        margin: 10px;
        padding: 20px;
      }

      .page-header {
        padding: 20px;
      }

      .page-header h2 {
        font-size: 1.5rem;
      }

      .stats-container {
        grid-template-columns: repeat(2, 1fr);
      }

      .stat-card {
        padding: 20px;
      }

      .stat-number {
        font-size: 2rem;
      }
    }

    @media (max-width: 360px) {
      main {
        padding: 15px;
      }
    }

    /* Loading Animation */
    .loading {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 3px solid rgba(255,255,255,.3);
      border-radius: 50%;
      border-top-color: #fff;
      animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>
</head>
<body>

<nav>
  <div class="logo">Southrift Services Limited</div>
  <div class="nav-right">
    <a href="index.php"><i class="fa fa-home"></i> Dashboard</a>
    <a href="#"><i class="fa fa-user-shield"></i> Super Admin</a>
    <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
  </div>
</nav>

<main>
  <h2>📅 Bookings Made Today (<?= date('F j, Y') ?>)</h2>
  <?= $message ?>

  <?php if ($result->num_rows): ?>
    <table>
      <thead>
        <tr>
          <th>Full Name</th>
          <th>Phone</th>
          <th>Route</th>
          <th>Starting Station</th>
          <th>Travel Date</th>
          <th>Departure</th>
          <th>Seats</th>
          <th>Payment</th>
          <th>Booked At</th>
          <th>Assigned Car</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['fullname']) ?></td>
          <td>
            <a href="passenger_profile.php?phone=<?= urlencode($row['phone']) ?>" class="btn btn-sm btn-info">
              <i class="fas fa-user"></i> <?= htmlspecialchars($row['phone']) ?>
            </a>
          </td>
          <td><?= htmlspecialchars($row['route']) ?></td>
          <td><?= htmlspecialchars(ucfirst(substr($row['route'], 0, strpos($row['route'], '-') !== false ? strpos($row['route'], '-') : strlen($row['route'])))) ?></td>
          <td><?= htmlspecialchars($row['travel_date']) ?></td>
          <td><?= htmlspecialchars($row['departure_time']) ?></td>
          <td><?= htmlspecialchars($row['seats']) ?></td>
          <td><?= htmlspecialchars($row['payment_method']) ?></td>
          <td><?= date('H:i', strtotime($row['created_at'])) ?></td>
          <td>
            <?php if (!empty($row['assigned_vehicle'])): ?>
              <?= htmlspecialchars($row['assigned_vehicle']) ?>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($row['assigned_vehicle'])): ?>
              <form method="post" class="assign-form" style="gap:4px">
                <input type="hidden" name="booking_id" value="<?= (int)$row['id'] ?>">
                <button type="submit" name="unassign" class="undo-btn" title="Unassign vehicle">Undo</button>
              </form>
            <?php elseif (count($waitingVehicles) === 0): ?>
              <em>No vehicles in waiting</em>
            <?php else: ?>
              <form method="post" class="assign-form">
                <input type="hidden" name="booking_id" value="<?= (int)$row['id'] ?>">
                <select name="vehicle_number_plate" required>
                  <option value="">Select vehicle</option>
                  <?php foreach ($waitingVehicles as $v): ?>
                    <option 
                      value="<?= htmlspecialchars($v['number_plate']) ?>"
                      title="<?= htmlspecialchars(($v['type'] ?? '').' '.($v['color'] ?? '').' · '.(int)($v['capacity'] ?? 0).' seats · '.($v['driver_name'] ?? '')) ?>">
                      <?= htmlspecialchars($v['number_plate']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" name="assign">Assign</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div style="text-align: center; padding: 60px 20px; color: #6c757d;">
      <i class="fas fa-calendar-times" style="font-size: 4rem; margin-bottom: 20px; opacity: 0.5;"></i>
      <h3 style="margin-bottom: 10px; color: #495057;">No Bookings Today</h3>
      <p style="font-size: 1.1rem; margin-bottom: 30px;">No bookings have been made today yet. Check back later!</p>
      <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 20px; border-radius: 10px; display: inline-block;">
        <i class="fas fa-info-circle" style="color: var(--info); margin-right: 8px;"></i>
        Bookings will appear here as customers make reservations
      </div>
    </div>
  <?php endif; ?>

  <div style="text-align: center; margin-top: 40px;">
    <a class="back-btn" href="index.php">
      <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
  </div>
</main>

<footer>&copy; <?=date('Y')?> Southrift Services Limited | All Rights Reserved</footer>
</body>
</html>
