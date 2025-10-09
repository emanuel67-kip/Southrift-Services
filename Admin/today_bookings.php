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

// Get booking statistics
$totalBookings = $result->num_rows;
$assignedBookings = 0;
$unassignedBookings = 0;

// Reset result pointer to calculate stats
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if (!empty($row['assigned_vehicle'])) {
        $assignedBookings++;
    } else {
        $unassignedBookings++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Today's Bookings – Southrift Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/admin-styles.css">
  <style>
    :root {
      --primary: #6A0DAD;
      --primary-dark: #58009c;
      --primary-light: #8a2be2;
      --secondary: #FF6B6B;
      --success: #4CAF50;
      --warning: #FFC107;
      --danger: #F44336;
      --info: #2196F3;
      --light: #f8f9fa;
      --dark: #343a40;
      --gray: #6c757d;
      --light-gray: #e9ecef;
      --border: #dee2e6;
      --card-bg: #ffffff;
      --body-bg: #f5f7fb;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background-color: var(--body-bg);
      color: var(--dark);
      line-height: 1.6;
      display: flex;
      min-height: 100vh;
      flex-direction: column;
      padding-top: 80px;
      padding-bottom: 0;
    }

    /* Animations */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes logoGlow {
      0% { text-shadow: 0 0 8px #fff, 0 0 12px #0ff; }
      100% { text-shadow: 0 0 12px #fff, 0 0 20px #f0f; }
    }

    /* Container */
    .container {
      width: 100%;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
      flex: 1;
    }

    /* Page Header */
    .page-header {
      margin: 20px 0 30px 0;
      text-align: center;
    }

    .page-header h1 {
      font-size: 2.2rem;
      font-weight: 700;
      color: var(--primary-dark);
      margin-bottom: 10px;
      letter-spacing: -0.5px;
    }

    .page-header p {
      color: var(--gray);
      font-size: 1.1rem;
      max-width: 600px;
      margin: 0 auto;
    }

    /* Breadcrumb */
    .breadcrumb {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 25px;
      color: var(--gray);
      font-size: 0.95rem;
    }

    .breadcrumb a {
      color: var(--primary);
      text-decoration: none;
      transition: color 0.3s;
    }

    .breadcrumb a:hover {
      color: var(--primary-dark);
      text-decoration: underline;
    }

    .breadcrumb span {
      color: var(--gray);
    }

    /* Stats Overview */
    .stats-overview {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: var(--card-bg);
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
      border: 1px solid var(--border);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      text-align: center;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
    }

    .stat-card .icon {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 15px;
      font-size: 1.5rem;
      color: white;
    }

    .stat-card .icon.purple {
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
    }

    .stat-card .icon.green {
      background: linear-gradient(135deg, var(--success), #81c784);
    }

    .stat-card .icon.orange {
      background: linear-gradient(135deg, var(--warning), #ffd54f);
    }

    .stat-card .icon.blue {
      background: linear-gradient(135deg, var(--info), #64b5f6);
    }

    .stat-card h3 {
      font-size: 1.8rem;
      margin-bottom: 5px;
      color: var(--dark);
    }

    .stat-card p {
      color: var(--gray);
      font-size: 0.95rem;
      margin: 0;
    }

    /* Alerts */
    .alert {
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 25px;
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 500;
    }

    .alert.success {
      background: #e8f5e9;
      color: #2e7d32;
      border: 1px solid #c8e6c9;
    }

    .alert.error {
      background: #ffebee;
      color: #c62828;
      border: 1px solid #ffcdd2;
    }

    .alert.info {
      background: #e3f2fd;
      color: #1565c0;
      border: 1px solid #bbdefb;
    }

    /* Table Section */
    .table-container {
      background: var(--card-bg);
      border-radius: 16px;
      padding: 30px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      border: 1px solid rgba(0, 0, 0, 0.05);
      margin-bottom: 30px;
      overflow: hidden;
      position: relative;
    }

    .table-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: linear-gradient(90deg, var(--primary), var(--primary-light));
    }

    .table-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      flex-wrap: wrap;
      gap: 15px;
    }

    .table-header h2 {
      font-size: 1.5rem;
      font-weight: 600;
      color: var(--primary-dark);
      margin: 0;
      position: relative;
      padding-left: 30px;
    }

    .table-header h2::before {
      content: '\f073';
      font-family: 'Font Awesome 5 Free';
      font-weight: 900;
      position: absolute;
      left: 0;
      top: 50%;
      transform: translateY(-50%);
      color: var(--primary);
    }

    .search-box {
      position: relative;
      max-width: 300px;
    }

    .search-box input {
      width: 100%;
      padding: 12px 15px 12px 40px;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-family: 'Poppins', sans-serif;
      font-size: 1rem;
      transition: all 0.3s;
      background: #fafafa;
    }

    .search-box input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(106, 13, 173, 0.15);
      background: white;
    }

    .search-box i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--gray);
    }

    .table-wrap {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 1000px;
      background: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    thead th {
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      color: white;
      padding: 16px 15px;
      text-align: left;
      font-weight: 600;
      font-size: 0.95rem;
      border-bottom: none;
      position: sticky;
      top: 0;
    }

    thead th:first-child {
      border-top-left-radius: 10px;
    }

    thead th:last-child {
      border-top-right-radius: 10px;
    }

    tbody td {
      padding: 14px 15px;
      border-bottom: 1px solid #eee;
      font-size: 0.95rem;
      color: var(--dark);
    }

    tbody tr {
      transition: background-color 0.2s, transform 0.2s;
    }

    tbody tr:hover {
      background-color: #f8f9ff;
      transform: scale(1.01);
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    tbody tr:last-child td {
      border-bottom: none;
    }

    tbody tr:nth-child(even) {
      background-color: #fafafa;
    }

    tbody tr:nth-child(even):hover {
      background-color: #f0f2ff;
    }

    /* Enhanced Forms and Buttons */
    .assign-form {
      display: flex;
      gap: 8px;
      align-items: center;
      flex-wrap: wrap;
      max-width: 200px;
    }

    .assign-form select {
      padding: 8px 10px;
      border-radius: 6px;
      border: 1px solid var(--border);
      background: white;
      font-family: 'Poppins', sans-serif;
      font-size: 0.9rem;
      min-width: 130px;
      transition: all 0.2s ease;
    }

    .assign-form select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(106, 13, 173, 0.1);
    }

    .assign-form button {
      padding: 8px 12px;
      border-radius: 6px;
      border: none;
      font-weight: 500;
      cursor: pointer;
      font-size: 0.9rem;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .assign-form button[name="assign"] {
      background: var(--success);
      color: white;
    }

    .assign-form button[name="assign"]:hover {
      background: #43a047;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(76, 175, 80, 0.3);
    }

    .assign-form .undo-btn {
      background: var(--danger);
      color: white;
    }

    .assign-form .undo-btn:hover {
      background: #e53935;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(244, 67, 54, 0.3);
    }

    /* Phone Number Styling */
    .copy-btn {
      background: transparent;
      border: 1px solid var(--border);
      border-radius: 4px;
      padding: 4px 8px;
      cursor: pointer;
      color: var(--gray);
      transition: all 0.2s ease;
      font-size: 0.8rem;
    }

    .copy-btn:hover {
      background: var(--light);
      color: var(--dark);
      border-color: var(--dark);
    }

    .copy-btn.copied {
      background: var(--success);
      color: white;
      border-color: var(--success);
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
      background: linear-gradient(135deg, var(--success), #40c057);
      color: white;
    }

    .vehicle-unassigned {
      background: linear-gradient(135deg, #e9ecef, #dee2e6);
      color: #6c757d;
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--gray);
    }

    .empty-state i {
      font-size: 4rem;
      margin-bottom: 20px;
      color: var(--light-gray);
    }

    .empty-state h3 {
      font-size: 1.8rem;
      color: var(--dark);
      margin-bottom: 15px;
    }

    .empty-state p {
      font-size: 1.1rem;
      max-width: 500px;
      margin: 0 auto 30px;
    }

    .empty-state .info-box {
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      padding: 20px;
      border-radius: 10px;
      display: inline-block;
      max-width: 500px;
    }

    /* Back Button */
    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      margin-top: 30px;
      padding: 12px 25px;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: white;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .back-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 12px rgba(106, 13, 173, 0.3);
      text-decoration: none;
      color: white;
    }

    /* Responsive Design */
    @media (max-width: 992px) {
      .container {
        padding: 0 15px;
      }
      
      .table-container {
        padding: 25px;
      }
      
      .stats-overview {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      }
      
      .page-header h1 {
        font-size: 2rem;
      }
    }

    @media (max-width: 768px) {
      body {
        padding-top: 70px;
        padding-bottom: 0;
        min-height: 100vh;
        flex-direction: column;
      }
      
      .page-header h1 {
        font-size: 1.8rem;
      }
      
      .table-container {
        padding: 20px;
      }
      
      .table-header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .search-box {
        max-width: 100%;
        width: 100%;
      }
      
      .assign-form {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
        max-width: 100%;
      }
      
      .assign-form select,
      .assign-form button {
        width: 100%;
      }
      
      .stats-overview {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 576px) {
      body {
        padding-top: 70px;
        padding-bottom: 0;
        min-height: 100vh;
      }
      
      .page-header h1 {
        font-size: 1.6rem;
      }
      
      thead th, tbody td {
        padding: 10px 8px;
        font-size: 0.9rem;
      }
      
      .stat-card {
        padding: 15px;
      }
      
      .stat-card h3 {
        font-size: 1.5rem;
      }
      
      .container {
        padding: 0 10px;
      }
    }

    /* Footer styling */
    footer {
      background: var(--purple);
      color: #fff;
      text-align: center;
      padding: 1rem;
      margin-top: 40px;
      position: relative;
      z-index: 100;
      width: 100%;
    }

    @media (max-width: 768px) {
      footer {
        margin-top: 30px;
        padding: 0.8rem;
        font-size: 0.9rem;
      }
    }

    @media (max-width: 576px) {
      footer {
        margin-top: 20px;
        padding: 0.7rem;
        font-size: 0.8rem;
      }
    }
  </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

  
  <div class="page-header">
    <h1>Today's Bookings</h1>
    <p>Manage all bookings made today - <?php echo date('F j, Y'); ?></p>
  </div>

  <!-- Stats Overview -->
  <div class="stats-overview">
    <div class="stat-card">
      <div class="icon purple">
        <i class="fas fa-calendar-day"></i>
      </div>
      <h3><?php echo $totalBookings; ?></h3>
      <p>Total Bookings</p>
    </div>
    <div class="stat-card">
      <div class="icon green">
        <i class="fas fa-check-circle"></i>
      </div>
      <h3><?php echo $assignedBookings; ?></h3>
      <p>Assigned</p>
    </div>
    <div class="stat-card">
      <div class="icon orange">
        <i class="fas fa-clock"></i>
      </div>
      <h3><?php echo $unassignedBookings; ?></h3>
      <p>Pending</p>
    </div>
  </div>

  <?php echo $message; ?>

  <div class="table-container">
    <div class="table-header">
      <h2>Booking Records</h2>
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Search bookings...">
      </div>
    </div>
    
    <div class="table-wrap">
      <?php 
      // Reset result pointer for display
      $stmt->execute();
      $result = $stmt->get_result();
      
      if ($result->num_rows): ?>
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
              <td><?php echo htmlspecialchars($row['fullname']); ?></td>
              <td>
                <div style="display: flex; align-items: center; gap: 8px;">
                  <span><?php echo htmlspecialchars($row['phone']); ?></span>
                  <button type="button" class="copy-btn" data-phone="<?php echo htmlspecialchars($row['phone']); ?>" title="Copy to clipboard">
                    <i class="fas fa-copy"></i>
                  </button>
                </div>
              </td>
              <td><?php echo htmlspecialchars($row['route']); ?></td>
              <td><?php echo htmlspecialchars(ucfirst(substr($row['route'], 0, strpos($row['route'], '-') !== false ? strpos($row['route'], '-') : strlen($row['route'])))); ?></td>
              <td><?php echo htmlspecialchars($row['travel_date']); ?></td>
              <td><?php echo htmlspecialchars($row['departure_time']); ?></td>
              <td><?php echo htmlspecialchars($row['seats']); ?></td>
              <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
              <td><?php echo date('H:i', strtotime($row['created_at'])); ?></td>
              <td>
                <?php if (!empty($row['assigned_vehicle'])): ?>
                  <span class="vehicle-status vehicle-assigned">
                    <?php echo htmlspecialchars($row['assigned_vehicle']); ?>
                  </span>
                <?php else: ?>
                  <span class="vehicle-status vehicle-unassigned">Unassigned</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($row['assigned_vehicle'])): ?>
                  <form method="post" class="assign-form">
                    <input type="hidden" name="booking_id" value="<?php echo (int)$row['id']; ?>">
                    <button type="submit" name="unassign" class="undo-btn" title="Unassign vehicle">
                      <i class="fas fa-undo"></i> Undo
                    </button>
                  </form>
                <?php elseif (count($waitingVehicles) === 0): ?>
                  <em>No vehicles</em>
                <?php else: ?>
                  <form method="post" class="assign-form">
                    <input type="hidden" name="booking_id" value="<?php echo (int)$row['id']; ?>">
                    <select name="vehicle_number_plate" required>
                      <option value="">Select vehicle</option>
                      <?php foreach ($waitingVehicles as $v): ?>
                        <option 
                          value="<?php echo htmlspecialchars($v['number_plate']); ?>"
                          title="<?php echo htmlspecialchars(($v['type'] ?? '').' '.($v['color'] ?? '').' · '.(int)($v['capacity'] ?? 0).' seats · '.($v['driver_name'] ?? '')); ?>">
                          <?php echo htmlspecialchars($v['number_plate']); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <button type="submit" name="assign">
                      <i class="fas fa-check"></i> Assign
                    </button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state">
          <i class="fas fa-calendar-times"></i>
          <h3>No Bookings Today</h3>
          <p>No bookings have been made today yet. Check back later!</p>
          <div class="info-box">
            <i class="fas fa-info-circle" style="color: var(--info); margin-right: 8px;"></i>
            Bookings will appear here as customers make reservations
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div style="text-align: center;">
    <a class="back-btn" href="index.php">
      <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
  </div>
</div>

<footer>&copy; <?php echo date('Y'); ?> Southrift Services Limited | All Rights Reserved</footer>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
  const searchTerm = this.value.toLowerCase();
  const tableRows = document.querySelectorAll('tbody tr');
  
  tableRows.forEach(row => {
    const text = row.textContent.toLowerCase();
    if (text.includes(searchTerm)) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
});

// Copy to clipboard functionality
document.querySelectorAll('.copy-btn').forEach(button => {
  button.addEventListener('click', function() {
    const phone = this.getAttribute('data-phone');
    const originalText = this.innerHTML;
    
    navigator.clipboard.writeText(phone).then(() => {
      // Show success feedback
      this.innerHTML = '<i class="fas fa-check"></i>';
      this.classList.add('copied');
      
      // Reset button after 2 seconds
      setTimeout(() => {
        this.innerHTML = originalText;
        this.classList.remove('copied');
      }, 2000);
    }).catch(err => {
      console.error('Failed to copy: ', err);
    });
  });
});
</script>

</body>
</html>