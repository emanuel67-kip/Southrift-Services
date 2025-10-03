<?php
require __DIR__ . '/auth.php';
require dirname(__DIR__) . '/db.php';

date_default_timezone_set('Africa/Nairobi');

$driver_phone = $_SESSION['phone'];
$driver_name = $_SESSION['name'] ?? 'Driver';

// Get driver's vehicle information
$vehicle = [];
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE driver_phone = ?");
$stmt->bind_param("s", $driver_phone);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $vehicle = $result->fetch_assoc();
    
    // If driver name is in vehicle record but not in session, update session
    if (!empty($vehicle['driver_name']) && empty($_SESSION['name'])) {
        $driver_name = $vehicle['driver_name'];
        $_SESSION['name'] = $driver_name;
    }
}

// Get additional driver information from drivers table if it exists
$driver_info = [];
$stmt_driver = $conn->prepare("SELECT * FROM drivers WHERE driver_phone = ?");
if ($stmt_driver) {
    $stmt_driver->bind_param("s", $driver_phone);
    $stmt_driver->execute();
    $result_driver = $stmt_driver->get_result();
    
    if ($result_driver && $result_driver->num_rows > 0) {
        $driver_info = $result_driver->fetch_assoc();
    }
}

// Set default values if not found
$driver_phone_display = $driver_phone;

// Set driver information (prefer drivers table, fallback to vehicle table)
$driver_email = $driver_info['email'] ?? 'N/A';
$driver_license = $driver_info['license_number'] ?? 'N/A';
$driver_status = $driver_info['status'] ?? ($vehicle ? 'active' : 'inactive');
$driver_rating = $driver_info['rating'] ?? '0.00';
$total_rides = $driver_info['total_rides'] ?? 0;
$is_verified = $driver_info['is_verified'] ?? false;
$last_login = $driver_info['last_login'] ?? 'Never';
$created_at = $driver_info['created_at'] ?? ($vehicle['created_at'] ?? 'N/A');

// Set vehicle information
$vehicle_plate = $vehicle['number_plate'] ?? 'N/A (No vehicle assigned)';
$vehicle_route = $vehicle['route'] ?? $driver_info['route'] ?? 'N/A';
$vehicle_color = $vehicle['color'] ?? $driver_info['vehicle_color'] ?? 'N/A';
$vehicle_type = $vehicle['type'] ?? $driver_info['vehicle_type'] ?? 'N/A';
$vehicle_make = $vehicle['make'] ?? $driver_info['vehicle_make'] ?? 'N/A';
$vehicle_model = $vehicle['model'] ?? $driver_info['vehicle_model'] ?? 'N/A';
$vehicle_capacity = $vehicle['capacity'] ?? 'N/A';
$vehicle_image = $vehicle['image_path'] ?? 'default-vehicle.jpg';
$vehicle_status = $vehicle['status'] ?? 'inactive';
$owner_name = $vehicle['owner_name'] ?? 'N/A';
$owner_phone = $vehicle['owner_phone'] ?? 'N/A';

// Format dates and status for display
if ($last_login && $last_login !== 'Never' && $last_login !== 'N/A') {
    $last_login = date('Y-m-d H:i:s', strtotime($last_login));
}
if ($created_at && $created_at !== 'N/A') {
    $created_at = date('Y-m-d H:i:s', strtotime($created_at));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile – SouthRide</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --purple: #6A0DAD;
      --purple-dark: #4e0b8a;
      --bg: #f4f4f4;
    }
    
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
      line-height: 1.6;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    
    /* Navigation */
    nav {
      background: var(--purple);
      padding: 1rem 2rem;
      color: #fff;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      flex-shrink: 0;
    }
    
    .logo {
      font-size: 1.5rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      animation: logoGlow 2s ease infinite alternate;
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
      transition: color .3s;
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
      transition: transform .4s ease-in-out;
    }
    
    .nav-right a:hover {
      color: #00ffff;
      text-shadow: 0 0 8px rgba(0,255,255,.6);
    }
    
    .nav-right a:hover::after {
      transform: scaleX(1);
      transform-origin: left;
    }
    
    /* Main Content */
    .container {
      max-width: 1100px;
      margin: 40px auto;
      padding: 0 20px;
      animation: fadeIn .7s ease-in-out;
      flex: 1;
      padding-bottom: 80px; /* Add padding to prevent content from being hidden behind fixed footer */
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; }
    }
    
    .profile-header {
      margin-bottom: 30px;
      text-align: center;
    }
    
    .profile-title {
      font-size: 2.2rem;
      color: var(--purple);
      margin-bottom: 15px;
      font-weight: 700;
      text-shadow: 0 2px 4px rgba(0,0,0,.1);
    }
    
    .profile-subtitle {
      color: #666;
      font-size: 1.1rem;
    }
    
    .profile-edit-btn {
      padding: 0.6rem 1.2rem;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.3s ease;
      text-decoration: none;
    }
    
    .profile-edit-btn:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
    }
    
    .profile-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      margin-bottom: 2rem;
    }
    
    .profile-card {
      background: white;
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
      padding: 1.8rem;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .profile-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .card-header {
      display: flex;
      align-items: center;
      margin-bottom: 1.5rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid var(--border);
    }
    
    .card-header i {
      font-size: 1.5rem;
      margin-right: 12px;
      color: var(--primary);
      background: rgba(106, 13, 173, 0.1);
      width: 45px;
      height: 45px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .card-header h3 {
      font-size: 1.3rem;
      font-weight: 600;
      color: var(--primary);
      margin: 0;
    }
    
    .info-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 1.2rem;
    }
    
    .info-item {
      display: flex;
      flex-direction: column;
    }
    
    .info-label {
      font-size: 0.8rem;
      color: var(--text-light);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 4px;
      font-weight: 500;
    }
    
    .info-value {
      font-size: 1rem;
      color: var(--text);
      font-weight: 500;
      word-break: break-word;
    }
    
    .status-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .status-active, .status-available {
      background: rgba(40, 167, 69, 0.1);
      color: #28a745;
    }
    
    .status-inactive, .status-offline {
      background: rgba(220, 53, 69, 0.1);
      color: #dc3545;
    }
    
    .status-on-trip {
      background: rgba(255, 193, 7, 0.1);
      color: #ffc107;
    }
    
    .status-maintenance {
      background: rgba(255, 152, 0, 0.1);
      color: #ff9800;
    }
    
    .status-verified {
      background: rgba(40, 167, 69, 0.1);
      color: #28a745;
    }
    
    .status-unverified {
      background: rgba(255, 193, 7, 0.1);
      color: #ffc107;
    }
    
    .vehicle-image {
      width: 100%;
      height: 200px;
      background: #f5f7fa;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1.5rem;
      overflow: hidden;
    }
    
    .vehicle-image i {
      font-size: 4rem;
      color: #d1d5db;
    }
    
    .vehicle-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    /* Responsive Design */
    /* Large desktops */
    @media (max-width: 1200px) {
      .profile-grid {
        grid-template-columns: 1fr;
      }
      
      .container {
        margin: 30px auto;
        padding: 0 15px;
      }
      
      .profile-title {
        font-size: 2rem;
      }
      
      .profile-card {
        padding: 1.5rem;
      }
    }
    
    /* Medium devices (tablets) */
    @media (max-width: 992px) {
      nav {
        padding: 1rem 1.5rem;
      }
      
      .nav-right {
        gap: 15px;
      }
      
      .container {
        margin: 25px auto;
        padding: 0 15px;
        padding-bottom: 100px; /* Increase padding for mobile */
      }
      
      .profile-title {
        font-size: 1.8rem;
        margin-bottom: 12px;
      }
      
      .profile-card {
        padding: 1.3rem;
      }
      
      .card-header h3 {
        font-size: 1.2rem;
      }
      
      .info-value {
        font-size: 0.95rem;
      }
    }
    
    /* Small tablets */
    @media (max-width: 768px) {
      nav {
        flex-direction: column;
        gap: 1rem;
        padding: 1rem;
      }
      
      .nav-right {
        width: 100%;
        justify-content: space-around;
      }
      
      .nav-right a {
        padding: 6px 8px;
        font-size: 0.9rem;
      }
      
      .profile-header {
        margin-bottom: 20px;
      }
      
      .profile-title {
        font-size: 1.6rem;
        margin-bottom: 10px;
      }
      
      .container {
        margin: 20px auto;
        padding: 0 12px;
        padding-bottom: 120px; /* Increase padding for small mobile */
      }
      
      .profile-card {
        padding: 1.2rem;
      }
      
      .card-header {
        margin-bottom: 1.2rem;
        padding-bottom: 0.8rem;
      }
      
      .card-header i {
        font-size: 1.3rem;
        width: 40px;
        height: 40px;
      }
      
      .info-label {
        font-size: 0.75rem;
      }
      
      .info-value {
        font-size: 0.9rem;
      }
      
      .status-badge {
        padding: 3px 8px;
        font-size: 0.7rem;
      }
    }
    
    /* Mobile devices */
    @media (max-width: 576px) {
      nav {
        flex-direction: column;
        gap: 0.8rem;
        padding: 0.8rem;
      }
      
      .logo {
        font-size: 1.3rem;
      }
      
      .nav-right {
        flex-wrap: wrap;
        gap: 5px;
      }
      
      .nav-right a {
        padding: 5px 6px;
        font-size: 0.8rem;
        flex: 1 1 auto;
        text-align: center;
        min-width: 70px;
      }
      
      .profile-header {
        margin-bottom: 15px;
      }
      
      .profile-title {
        font-size: 1.4rem;
        margin-bottom: 8px;
      }
      
      .container {
        margin: 15px auto;
        padding: 0 10px;
        padding-bottom: 130px; /* Increase padding for very small mobile */
      }
      
      .profile-card {
        padding: 1rem;
      }
      
      .card-header {
        margin-bottom: 1rem;
        padding-bottom: 0.7rem;
      }
      
      .card-header i {
        font-size: 1.2rem;
        width: 35px;
        height: 35px;
      }
      
      .card-header h3 {
        font-size: 1.1rem;
      }
      
      .info-label {
        font-size: 0.7rem;
      }
      
      .info-value {
        font-size: 0.85rem;
      }
      
      .status-badge {
        padding: 2px 6px;
        font-size: 0.65rem;
      }
    }
    
    /* Small mobile devices */
    @media (max-width: 480px) {
      nav {
        padding: 0.7rem 0.5rem;
      }
      
      .logo {
        font-size: 1.2rem;
      }
      
      .nav-right {
        gap: 3px;
      }
      
      .nav-right a {
        padding: 4px 5px;
        font-size: 0.75rem;
        min-width: 60px;
      }
      
      .profile-title {
        font-size: 1.3rem;
        margin-bottom: 6px;
      }
      
      .container {
        margin: 12px auto;
        padding: 0 8px;
        padding-bottom: 140px; /* Increase padding for extra small devices */
      }
      
      .profile-card {
        padding: 0.9rem;
      }
      
      .card-header {
        margin-bottom: 0.8rem;
        padding-bottom: 0.6rem;
      }
      
      .card-header i {
        font-size: 1.1rem;
        width: 30px;
        height: 30px;
      }
      
      .card-header h3 {
        font-size: 1rem;
      }
      
      .info-label {
        font-size: 0.65rem;
      }
      
      .info-value {
        font-size: 0.8rem;
      }
      
      .status-badge {
        padding: 2px 5px;
        font-size: 0.6rem;
      }
    }
    
    /* Extra small devices */
    @media (max-width: 360px) {
      nav {
        padding: 0.6rem 0.4rem;
      }
      
      .logo {
        font-size: 1.1rem;
      }
      
      .nav-right a {
        padding: 3px 4px;
        font-size: 0.7rem;
        min-width: 55px;
      }
      
      .profile-title {
        font-size: 1.2rem;
        margin-bottom: 5px;
      }
      
      .container {
        margin: 10px auto;
        padding: 0 6px;
        padding-bottom: 150px; /* Increase padding for extra small devices */
      }
      
      .profile-card {
        padding: 0.8rem;
      }
      
      .card-header {
        margin-bottom: 0.7rem;
        padding-bottom: 0.5rem;
      }
      
      .card-header i {
        font-size: 1rem;
        width: 25px;
        height: 25px;
      }
      
      .card-header h3 {
        font-size: 0.9rem;
      }
      
      .info-label {
        font-size: 0.6rem;
      }
      
      .info-value {
        font-size: 0.75rem;
      }
      
      .status-badge {
        padding: 1px 4px;
        font-size: 0.55rem;
      }
    }
    
    .vehicle-info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      grid-template-rows: repeat(3, auto);
      gap: 15px;
    }
    
    .info-item {
      background: #f8f9fa;
      padding: 15px;
      border-left: 4px solid #6A0DAD;
      border-radius: 8px;
    }
    
    .info-label {
      font-size: 0.8rem;
      color: #555;
      margin-bottom: 4px;
      text-transform: uppercase;
    }
    
    .info-value {
      font-size: 1.2rem;
      color: #333;
      font-weight: 600;
    }
    
    footer {
      text-align: center;
      padding: 1rem;
      background: #6A0DAD;
      color: white;
      position: fixed;
      bottom: 0;
      width: 100%;
      flex-shrink: 0;
    }
  </style>
</head>
<body>
  <nav>
    <div class="logo">
      <i class="fas fa-bus"></i>
      <span>SouthRift Driver</span>
    </div>
    <div class="nav-right">
      <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
      <a href="profile.php" class="active"><i class="fas fa-user"></i> My Profile</a>
      <a href="../logout_new.php">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
  </nav>

  <main>
    <div class="container">
    <div class="profile-header">
      <h1 class="profile-title">Driver Profile</h1>
    </div>
    
    <div class="profile-grid">
      <!-- Personal Information Card -->
      <div class="profile-card">
        <div class="card-header">
          <i class="fas fa-user-circle"></i>
          <h3>Personal Information</h3>
        </div>
        <div class="info-grid">
          <div class="info-item">
            <span class="info-label">Full Name</span>
            <span class="info-value"><?php echo htmlspecialchars($driver_name); ?></span>
          </div>
          <div class="info-item">
            <span class="info-label">Phone Number</span>
            <span class="info-value"><?php echo htmlspecialchars($driver_phone_display); ?></span>
          </div>
          <div class="info-item">
            <span class="info-label">Email Address</span>
            <span class="info-value"><?php echo htmlspecialchars($driver_email); ?></span>
          </div>
          <div class="info-item">
            <span class="info-label">License Number</span>
            <span class="info-value"><?php echo htmlspecialchars($driver_license); ?></span>
          </div>
          <div class="info-item">
            <span class="info-label">Member Since</span>
            <span class="info-value"><?php echo htmlspecialchars($created_at); ?></span>
          </div>
        </div>
      </div>
      
      <!-- Driver Status Card -->
      <div class="profile-card">
        <div class="card-header">
          <i class="fas fa-chart-line"></i>
          <h3>Driver Status</h3>
        </div>
        <div class="info-grid">
          <div class="info-item">
            <span class="info-label">Current Status</span>
            <span class="info-value">
              <span class="status-badge <?php echo $driver_status === 'available' ? 'status-available' : ($driver_status === 'on_trip' ? 'status-on-trip' : 'status-offline'); ?>">
                <?php echo ucfirst(str_replace('_', ' ', $driver_status)); ?>
              </span>
            </span>
          </div>
          <div class="info-item">
            <span class="info-label">Verification Status</span>
            <span class="info-value">
              <span class="status-badge <?php echo $is_verified ? 'status-verified' : 'status-unverified'; ?>">
                <?php echo $is_verified ? 'Verified' : 'Pending Verification'; ?>
              </span>
            </span>
          </div>
          <div class="info-item">
            <span class="info-label">Driver Rating</span>
            <span class="info-value">
              <i class="fas fa-star" style="color: #ffd700;"></i>
              <?php echo number_format($driver_rating, 1); ?>/5.0
            </span>
          </div>
          <div class="info-item">
            <span class="info-label">Total Rides Completed</span>
            <span class="info-value"><?php echo number_format($total_rides); ?></span>
          </div>
          <div class="info-item">
            <span class="info-label">Last Login</span>
            <span class="info-value"><?php echo htmlspecialchars($last_login); ?></span>
          </div>
        </div>
      </div>
      
      <!-- Vehicle Information Card -->
      <div class="profile-card" style="grid-column: 1 / -1;">
        <div class="card-header">
          <i class="fas fa-car"></i>
          <h3>Vehicle Information</h3>
        </div>
        <div class="vehicle-info-grid">
          <div class="info-item">
            <span class="info-label">Number Plate</span>
            <span class="info-value"><?php echo htmlspecialchars($vehicle_plate); ?></span>
          </div>
          <div class="info-item">
            <span class="info-label">Vehicle Type</span>
            <span class="info-value"><?php echo htmlspecialchars($vehicle_type); ?></span>
          </div>
          <div class="info-item">
            <span class="info-label">Color</span>
            <span class="info-value"><?php echo htmlspecialchars($vehicle_color); ?></span>
          </div>
          <div class="info-item">
            <span class="info-label">Passenger Capacity</span>
            <span class="info-value"><?php echo htmlspecialchars($vehicle_capacity); ?> passengers</span>
          </div>
          <div class="info-item">
            <span class="info-label">Route</span>
            <span class="info-value"><?php echo htmlspecialchars($vehicle_route); ?></span>
          </div>
          <div class="info-item">
            <span class="info-label">Vehicle Status</span>
            <span class="info-value">
              <span class="status-badge <?php echo $vehicle_status === 'active' ? 'status-active' : ($vehicle_status === 'maintenance' ? 'status-maintenance' : 'status-inactive'); ?>">
                <?php echo ucfirst($vehicle_status); ?>
              </span>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  </main>

  <footer>
    <p>&copy; <?php echo date('Y'); ?> SouthRift Services. All rights reserved.</p>
  </footer>
</body>
</html>