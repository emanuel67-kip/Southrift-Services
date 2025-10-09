<?php
require __DIR__ . '/auth.php';
require dirname(__DIR__) . '/db.php';

date_default_timezone_set('Africa/Nairobi');

// Get driver's phone from session
$driver_phone = $_SESSION['phone'] ?? '';
$driver_name = $_SESSION['name'] ?? 'Driver';

// Get driver's assigned vehicle
$vehicle = [];
if (!empty($driver_phone)) {
    $stmt = $conn->prepare("SELECT * FROM vehicles WHERE driver_phone = ?");
    $stmt->bind_param("s", $driver_phone);
    $stmt->execute();
    $result = $stmt->get_result();
    $vehicle = $result->fetch_assoc() ?: [];
}

// Set default values for the dashboard
$vehicle_name = !empty($vehicle['type']) 
    ? htmlspecialchars($vehicle['type'])
    : (!empty($vehicle['make']) && !empty($vehicle['model']) 
        ? htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model'])
        : 'No vehicle assigned');
$vehicle_plate = !empty($vehicle['number_plate']) ? htmlspecialchars($vehicle['number_plate']) : 'N/A';

// If driver name is not in session, try to get it from the vehicle record
if (empty($driver_name) && !empty($vehicle['driver_name'])) {
    $driver_name = htmlspecialchars($vehicle['driver_name']);
    $_SESSION['name'] = $driver_name;
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Driver Dashboard – SouthRide</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root {
    --primary: #6A0DAD;
    --primary-dark: #4e0b8a;
    --secondary: #3498db;
    --accent: #4CAF50;
    --light: #f8f9fa;
    --dark: #2c3e50;
    --danger: #e74c3c;
    --warning: #f39c12;
    --info: #17a2b8;
    --success: #28a745;
    --gray: #6c757d;
    --light-gray: #e9ecef;
    --border-radius: 12px;
    --box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #f5f7fa 0%, #e4e7f1 100%);
    color: #333;
    line-height: 1.6;
    min-height: 100vh;
    padding-bottom: 6rem;
    position: relative;
    background-attachment: fixed;
}

/* Header & Navigation */
.header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 1rem 2rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 100;
}

.nav-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    max-width: 1400px;
    margin: 0 auto;
}

.logo {
    font-size: 1.8rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}

.logo i {
    color: #00ffff;
    text-shadow: 0 0 8px rgba(0,255,255,0.5);
}

.nav-links {
    display: flex;
    gap: 1.5rem;
    align-items: center;
    flex-wrap: wrap;
}

.nav-link {
    color: rgba(255,255,255,0.9);
    text-decoration: none;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 30px;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 8px;
}

.nav-link:hover {
    background: rgba(255,255,255,0.15);
    color: white;
    transform: translateY(-2px);
}

.nav-link i {
    font-size: 1.1rem;
}

/* Main Content */
.container {
    max-width: 1400px;
    margin: 2.5rem auto 6rem auto;
    padding: 0 2rem;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--primary);
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.page-title i {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    font-size: 2.2rem;
}

/* Dashboard Grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

/* Cards */
.card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 1.8rem;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.05);
}

.card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: linear-gradient(90deg, var(--primary), var(--secondary));
}

.card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.2);
}

.card-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
    box-shadow: 0 5px 15px rgba(106, 13, 173, 0.3);
    transition: var(--transition);
}

.card:hover .card-icon {
    transform: scale(1.1);
}

.card-icon i {
    font-size: 2rem;
    color: white;
}

.card-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 0.8rem;
    text-align: center;
}

.card-text {
    color: var(--gray);
    margin-bottom: 1.5rem;
    font-size: 1rem;
    text-align: center;
    line-height: 1.7;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0.9rem 1.8rem;
    border-radius: 30px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    font-size: 1rem;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    width: 100%;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-dark), var(--primary));
    transform: translateY(-3px);
    box-shadow: 0 7px 18px rgba(106, 13, 173, 0.4);
}

.btn-outline {
    background: transparent;
    border: 2px solid var(--primary);
    color: var(--primary);
}

.btn-outline:hover {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 7px 18px rgba(106, 13, 173, 0.4);
}

/* Driver Info Section */
.driver-info {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 2rem;
    margin-bottom: 2rem;
}

.driver-info-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--light-gray);
}

.driver-info-header i {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.driver-info-title {
    font-size: 1.6rem;
    font-weight: 600;
    color: var(--dark);
    margin: 0;
}

.driver-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.detail-card {
    background: var(--light);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    border-left: 4px solid var(--primary);
    transition: var(--transition);
}

.detail-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.detail-label {
    font-size: 0.85rem;
    color: var(--gray);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.detail-value {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--dark);
    word-break: break-word;
}

/* Location Sharing Card */
.location-sharing-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    color: var(--dark);
    border: 1px solid rgba(76, 175, 80, 0.2);
}

.location-sharing-card::before {
    background: linear-gradient(90deg, #4CAF50, #8BC34A);
}

.location-sharing-card .card-icon {
    background: linear-gradient(135deg, #4CAF50, #2E7D32);
    box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
    animation: pulse 2s infinite;
}

.location-sharing-card:hover .card-icon {
    transform: scale(1.1);
}

.location-sharing-card .card-title {
    color: var(--dark);
    font-weight: 700;
}

.location-sharing-card .card-text {
    color: var(--gray);
    font-weight: 500;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(76, 175, 80, 0); }
    100% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0); }
}

.sharing-form {
    background: linear-gradient(135deg, rgba(76, 175, 80, 0.1) 0%, rgba(139, 195, 74, 0.15) 100%);
    border-radius: var(--border-radius);
    padding: 1.8rem;
    margin-top: 1.5rem;
    display: none;
    border: 1px solid rgba(76, 175, 80, 0.3);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.form-header {
    text-align: center;
    margin-bottom: 1.5rem;
}

.form-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.form-description {
    font-size: 0.95rem;
    color: var(--gray);
    margin: 0;
    line-height: 1.5;
}

.form-group {
    margin-bottom: 1.2rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.6rem;
    font-weight: 600;
    color: var(--dark);
    font-size: 1.05rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-control {
    width: 100%;
    padding: 1rem 1.2rem;
    border-radius: 8px;
    border: 2px solid rgba(76, 175, 80, 0.3);
    background: rgba(255, 255, 255, 0.9);
    color: var(--dark);
    font-family: 'Poppins', sans-serif;
    font-size: 1.05rem;
    transition: all 0.3s ease;
}

.form-control::placeholder {
    color: var(--gray);
}

.form-control:focus {
    outline: none;
    border-color: #4CAF50;
    background: white;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
}

.form-hint {
    font-size: 0.85rem;
    color: var(--gray);
    margin-top: 0.5rem;
    font-style: italic;
    padding-left: 2px;
}

.btn-group {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-top: 0.5rem;
}

.btn-group .btn {
    flex: 1;
    min-width: 120px;
}

.sharing-status {
    background: linear-gradient(135deg, rgba(76, 175, 80, 0.15) 0%, rgba(139, 195, 74, 0.2) 100%);
    border-radius: var(--border-radius);
    padding: 1.8rem;
    margin-top: 1.5rem;
    display: none;
    border: 1px solid rgba(76, 175, 80, 0.4);
}

.blinking-dot {
    display: inline-block;
    width: 14px;
    height: 14px;
    background-color: #4CAF50;
    border-radius: 50%;
    margin-right: 10px;
    animation: blink 1.2s infinite;
    box-shadow: 0 0 5px rgba(76, 175, 80, 0.5);
}

@keyframes blink {
    0% { opacity: 0.3; }
    50% { opacity: 1; box-shadow: 0 0 10px rgba(76, 175, 80, 0.8); }
    100% { opacity: 0.3; }
}

.link-preview {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1.2rem;
    font-size: 0.95rem;
    word-break: break-all;
    color: var(--dark);
    border: 1px solid rgba(76, 175, 80, 0.3);
    font-weight: 500;
}

/* Footer */
footer {
  background: var(--primary);
  color: #fff;
  text-align: center;
  padding: 0.5rem;
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  z-index: 100;
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
  font-size: 0.9rem;
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeIn 0.7s ease-in-out;
}

.slide-in {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

/* Notification Styles */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    z-index: 1000;
    max-width: 300px;
    word-wrap: break-word;
    animation: slideIn 0.3s ease-out;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.notification-success {
    background-color: var(--success);
}

.notification-error {
    background-color: var(--danger);
}

.notification-info {
    background-color: var(--info);
}

/* Responsive Design */
@media (max-width: 992px) {
    .container {
        padding: 0 1.5rem;
    }
    
    .dashboard-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }
    
    .page-title {
        font-size: 1.7rem;
    }
}

@media (max-width: 768px) {
    .nav-container {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .nav-links {
        width: 100%;
        justify-content: center;
    }
    
    .container {
        padding: 0 1rem;
        margin: 1.5rem auto;
    }
    
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .driver-details-grid {
        grid-template-columns: 1fr;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
    
    .card {
        padding: 1.5rem;
    }
}

@media (max-width: 576px) {
    .header {
        padding: 1rem;
    }
    
    .logo {
        font-size: 1.5rem;
    }
    
    .nav-link {
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
    }
    
    .container {
        margin: 1rem auto;
    }
    
    .page-title {
        font-size: 1.3rem;
    }
    
    .card {
        padding: 1.2rem;
    }
    
    .card-title {
        font-size: 1.2rem;
    }
    
    .btn-group {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .sharing-form, .sharing-status {
        padding: 1.2rem;
    }
    
    .form-control {
        padding: 0.8rem 1rem;
        font-size: 1rem;
    }
    
    .link-preview {
        font-size: 0.85rem;
    }
    
    .form-title {
        font-size: 1.2rem;
    }
    
    .form-description {
        font-size: 0.85rem;
    }
}

@media (max-width: 400px) {
    .logo {
        font-size: 1.3rem;
    }
    
    .nav-link span {
        display: none;
    }
    
    .nav-link i {
        margin-right: 0;
    }
    
    .card-icon {
        width: 60px;
        height: 60px;
    }
    
    .card-icon i {
        font-size: 1.5rem;
    }
    
    .sharing-form, .sharing-status {
        padding: 1rem;
    }
    
    .form-group label {
        font-size: 0.95rem;
    }
    
    .form-title {
        font-size: 1.1rem;
    }
    
    .form-description {
        font-size: 0.8rem;
    }
}
</style>
</head>
<body>
    <header class="header">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-bus"></i>
                <span>SouthRift Driver</span>
            </div>
            
            <div class="nav-links">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="profile.php" class="nav-link">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="#" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="../logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-tachometer-alt"></i>
            Driver Dashboard
        </h1>
        
        <div class="dashboard-grid">
            <div class="card fade-in">
                <div class="card-icon">
                    <i class="fas fa-user"></i>
                </div>
                <h2 class="card-title"><?= htmlspecialchars($driver_name) ?></h2>
                <p class="card-text">View and update your personal information and profile settings.</p>
                <a href="profile.php" class="btn btn-primary">
                    <i class="fas fa-eye"></i>
                    View Profile
                </a>
            </div>
            
            <div class="card location-sharing-card fade-in" id="locationCard">
                <div class="card-icon">
                    <i class="fas fa-location-dot"></i>
                </div>
                <h2 class="card-title" id="locationStatus">Share Live Location</h2>
                <p class="card-text">Share your Google Maps live location with assigned passengers for better coordination.</p>
                <button class="btn btn-outline" id="toggleSharingForm">
                    <i class="fas fa-share"></i>
                    Share Location
                </button>
                
                <!-- Google Maps Link Input Form -->
                <div class="sharing-form" id="linkInputForm">
                    <div class="form-header">
                        <h3 class="form-title">
                            <i class="fas fa-link"></i>
                            Share Your Location
                        </h3>
                        <p class="form-description">Paste your Google Maps link to share your live location with passengers</p>
                    </div>
                    <div class="form-group">
                        <label for="googleMapsLink">
                            <i class="fas fa-map-marker-alt"></i>
                            Google Maps Link
                        </label>
                        <input type="text" id="googleMapsLink" class="form-control" placeholder="https://maps.app.goo.gl/...">
                        <div class="form-hint">Tip: Open Google Maps on your phone, tap share, and copy the link</div>
                    </div>
                    <div class="btn-group">
                        <button id="shareLocationBtn" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
                            Share with Passengers
                        </button>
                        <button id="cancelShareBtn" class="btn" style="background: linear-gradient(135deg, #95a5a6, #7f8c8d); color: white; border: none;">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
                
                <!-- Sharing Status -->
                <div class="sharing-status" id="locationStatusIndicator">
                    <div style="display: flex; align-items: center; margin-bottom: 1rem; padding: 0.5rem; background: rgba(255, 255, 255, 0.3); border-radius: 20px; justify-content: center;">
                        <span class="blinking-dot"></span>
                        <span id="statusText">Google Maps location is being shared</span>
                    </div>
                    <div class="link-preview" id="sharedLinkPreview"></div>
                    <div style="margin-top: 1rem;">
                        <button id="stopSharingBtn" class="btn" style="background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; border: none;">
                            <i class="fas fa-stop"></i>
                            Stop Sharing
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card fade-in">
                <div class="card-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <h2 class="card-title">Today's Bookings</h2>
                <p class="card-text">View all bookings assigned to you for today and manage their status.</p>
                <a href="todays_bookings.php" class="btn btn-primary">
                    <i class="fas fa-list"></i>
                    View Bookings
                </a>
            </div>
        </div>
        
        <div class="driver-info fade-in">
            <div class="driver-info-header">
                <i class="fas fa-id-card"></i>
                <h2 class="driver-info-title">Driver Information</h2>
            </div>
            
            <div class="driver-details-grid">
                <div class="detail-card">
                    <div class="detail-label">Full Name</div>
                    <div class="detail-value"><?= htmlspecialchars($driver_name) ?></div>
                </div>
                
                <div class="detail-card">
                    <div class="detail-label">Phone Number</div>
                    <div class="detail-value"><?= htmlspecialchars($driver_phone) ?></div>
                </div>
                
                <?php if (!empty($vehicle)): ?>
                <div class="detail-card">
                    <div class="detail-label">Vehicle Type</div>
                    <div class="detail-value"><?= htmlspecialchars($vehicle_name) ?></div>
                </div>
                
                <div class="detail-card">
                    <div class="detail-label">Number Plate</div>
                    <div class="detail-value"><?= htmlspecialchars($vehicle_plate) ?></div>
                </div>
                
                <div class="detail-card">
                    <div class="detail-label">Color</div>
                    <div class="detail-value"><?= htmlspecialchars($vehicle['color'] ?? 'N/A') ?></div>
                </div>
                <?php else: ?>
                <div class="detail-card">
                    <div class="detail-label">Vehicle Status</div>
                    <div class="detail-value">No vehicle assigned</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Southrift Services Limited. All rights reserved.</p>
    </footer>
    
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <meta name="driver-id" content="<?= $driver_phone ?>">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    // Google Maps Link Sharing System
    class GoogleMapsLinkSharing {
        constructor() {
            this.driverId = document.querySelector('meta[name="driver-id"]')?.content || '';
            this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            this.isSharing = false;
            
            // Debug CSRF token
            console.log('CSRF Token loaded:', this.csrfToken ? this.csrfToken.substring(0, 10) + '...' : 'NOT FOUND');
            console.log('Driver ID:', this.driverId);
            
            if (!this.csrfToken) {
                console.error('Warning: CSRF token not found. Please refresh the page.');
                this.showNotification('Please refresh the page to load security tokens', 'error');
            }
            
            this.initializeUI();
            this.checkCurrentSharingStatus();
        }

        initializeUI() {
            const toggleBtn = document.getElementById('toggleSharingForm');
            const shareBtn = document.getElementById('shareLocationBtn');
            const cancelBtn = document.getElementById('cancelShareBtn');
            const stopBtn = document.getElementById('stopSharingBtn');
            const linkInput = document.getElementById('googleMapsLink');

            // Toggle sharing form
            toggleBtn.addEventListener('click', () => {
                if (!this.isSharing) {
                    this.toggleSharingForm();
                }
            });

            // Share button click
            shareBtn.addEventListener('click', () => {
                this.shareGoogleMapsLink();
            });

            // Cancel button click
            cancelBtn.addEventListener('click', () => {
                this.toggleSharingForm();
            });

            // Stop sharing button click
            stopBtn.addEventListener('click', () => {
                this.stopSharing();
            });

            // Enter key in input field
            linkInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.shareGoogleMapsLink();
                }
            });
        }

        toggleSharingForm() {
            const form = document.getElementById('linkInputForm');
            const isDisplayed = form.style.display === 'block';
            form.style.display = isDisplayed ? 'none' : 'block';
        }

        async shareGoogleMapsLink() {
            const linkInput = document.getElementById('googleMapsLink');
            const link = linkInput.value.trim();

            if (!link) {
                this.showNotification('Please enter a Google Maps link', 'error');
                return;
            }

            if (!this.isValidGoogleMapsLink(link)) {
                this.showNotification('Please enter a valid Google Maps link', 'error');
                return;
            }
            
            // Check if CSRF token exists
            if (!this.csrfToken) {
                this.showNotification('Security token missing. Please refresh the page.', 'error');
                return;
            }

            try {
                // Show loading state
                const shareBtn = document.getElementById('shareLocationBtn');
                const originalText = shareBtn.innerHTML;
                shareBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sharing...';
                shareBtn.disabled = true;

                console.log('Sending request with CSRF token:', this.csrfToken.substring(0, 10) + '...');
                console.log('Driver phone:', this.driverId);
                console.log('Google Maps link:', link);

                // Send to server
                const response = await fetch('share_google_maps_link.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `csrf_token=${encodeURIComponent(this.csrfToken)}&google_maps_link=${encodeURIComponent(link)}&driver_phone=${encodeURIComponent(this.driverId)}`
                });

                console.log('Response status:', response.status);
                const responseText = await response.text();
                console.log('Response text:', responseText);
                
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    throw new Error('Invalid server response: ' + responseText.substring(0, 100));
                }

                if (data.success) {
                    this.isSharing = true;
                    this.updateSharingStatus(link, data.passengers_notified || 0);
                    document.getElementById('linkInputForm').style.display = 'none';
                    document.getElementById('googleMapsLink').value = '';
                    this.showNotification(`Location shared with ${data.passengers_notified} passengers!`, 'success');
                } else {
                    console.error('Server error response:', data);
                    throw new Error(data.message || 'Failed to share location');
                }

                // Restore button
                shareBtn.innerHTML = originalText;
                shareBtn.disabled = false;

            } catch (error) {
                console.error('Error sharing location:', error);
                this.showNotification('Failed to share location: ' + error.message, 'error');
                
                // Restore button
                const shareBtn = document.getElementById('shareLocationBtn');
                shareBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Share with Passengers';
                shareBtn.disabled = false;
            }
        }

        async stopSharing() {
            try {
                const response = await fetch('stop_google_maps_sharing.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `csrf_token=${encodeURIComponent(this.csrfToken)}&driver_phone=${encodeURIComponent(this.driverId)}`
                });

                const data = await response.json();

                if (data.success) {
                    this.isSharing = false;
                    this.updateUI(false);
                    this.showNotification('Location sharing stopped', 'success');
                } else {
                    throw new Error(data.message || 'Failed to stop sharing');
                }
            } catch (error) {
                console.error('Error stopping sharing:', error);
                this.showNotification('Failed to stop sharing: ' + error.message, 'error');
            }
        }

        async checkCurrentSharingStatus() {
            try {
                const response = await fetch('check_google_maps_sharing.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `csrf_token=${encodeURIComponent(this.csrfToken)}&driver_phone=${encodeURIComponent(this.driverId)}`
                });

                const data = await response.json();

                if (data.success && data.is_sharing) {
                    this.isSharing = true;
                    this.updateSharingStatus(data.google_maps_link, data.passengers_count || 0);
                }
            } catch (error) {
                console.error('Error checking sharing status:', error);
            }
        }

        updateSharingStatus(link, passengerCount) {
            this.updateUI(true);
            
            // Update status text
            document.getElementById('statusText').textContent = `Shared with ${passengerCount} passengers`;
            
            // Show link preview
            const preview = document.getElementById('sharedLinkPreview');
            preview.innerHTML = `<strong>Link:</strong> ${this.truncateLink(link)}`;
        }

        updateUI(isSharing) {
            const statusElement = document.getElementById('locationStatus');
            const indicatorElement = document.getElementById('locationStatusIndicator');
            const toggleBtn = document.getElementById('toggleSharingForm');
            const cardElement = document.getElementById('locationCard');

            if (isSharing) {
                statusElement.innerHTML = 'Google Maps Location Shared <i class="fas fa-check-circle" style="color: #4CAF50;"></i>';
                indicatorElement.style.display = 'block';
                toggleBtn.innerHTML = '<i class="fas fa-stop"></i> Stop Sharing';
                toggleBtn.style.background = 'var(--danger)';
                cardElement.classList.add('sharing-active');
            } else {
                statusElement.innerHTML = 'Share Live Location';
                indicatorElement.style.display = 'none';
                toggleBtn.innerHTML = '<i class="fas fa-share"></i> Share Location';
                toggleBtn.style.background = '';
                toggleBtn.style.color = '';
                cardElement.classList.remove('sharing-active');
            }
        }

        isValidGoogleMapsLink(link) {
            // Check for various Google Maps link formats
            const patterns = [
                /^https:\/\/maps\.app\.goo\.gl\/.+/,
                /^https:\/\/www\.google\.com\/maps\/.+/,
                /^https:\/\/goo\.gl\/maps\/.+/,
                /^https:\/\/maps\.google\.com\/.+/
            ];
            
            return patterns.some(pattern => pattern.test(link));
        }

        truncateLink(link) {
            return link.length > 50 ? link.substring(0, 50) + '...' : link;
        }

        showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            
            notification.innerHTML = message;
            
            // Add refresh button for CSRF errors
            if (message.includes('CSRF') || message.includes('refresh')) {
                const refreshBtn = document.createElement('button');
                refreshBtn.innerHTML = '<i class="fas fa-refresh"></i> Refresh Page';
                refreshBtn.style.cssText = `
                    background: rgba(255,255,255,0.2);
                    color: white;
                    border: 1px solid rgba(255,255,255,0.3);
                    padding: 5px 10px;
                    border-radius: 3px;
                    margin-left: 10px;
                    cursor: pointer;
                    font-size: 12px;
                `;
                refreshBtn.onclick = () => window.location.reload();
                notification.appendChild(refreshBtn);
            }
            
            document.body.appendChild(notification);
            
            // Auto remove after 8 seconds for error messages, 5 seconds for others
            const timeout = type === 'error' ? 8000 : 5000;
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.animation = 'slideOut 0.3s ease-in';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }
            }, timeout);
        }
    }

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        window.googleMapsSharing = new GoogleMapsLinkSharing();
    });
    </script>
</body>
</html>