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
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{--purple:#6A0DAD;--purple-dark:#4e0b8a;--bg:#f4f4f4}
html{animation:fadeIn .7s ease-in-out}@keyframes fadeIn{from{opacity:0;transform:translateY(20px)}to{opacity:1}}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Poppins,sans-serif;background:var(--bg)}

/* NAVBAR */
nav{background:var(--purple);padding:1rem 2rem;color:#fff;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap}
.logo{font-size:1.5rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;animation:logoGlow 2s ease infinite alternate}
@keyframes logoGlow{0%{text-shadow:0 0 8px #fff,0 0 12px #0ff}100%{text-shadow:0 0 12px #fff,0 0 20px #f0f}}
.nav-right{display:flex;gap:20px;align-items:center}

/* Navigation Links */
.nav-right a{
  position:relative;
  color:paleturquoise;
  font-weight:600;
  text-decoration:none;
  padding:8px 10px;
  text-transform:uppercase;
  letter-spacing:1px;
  transition:color .3s;
}
.nav-right a::after{
  content:"";
  position:absolute;bottom:0;left:0;width:100%;height:2px;
  background:linear-gradient(to right,#ff6ec4,#7873f5);
  transform:scaleX(0);transform-origin:right;
  transition:transform .4s ease-in-out;
}
.nav-right a:hover{
  color:#00ffff;
  text-shadow:0 0 8px rgba(0,255,255,.6);
}
.nav-right a:hover::after{
  transform:scaleX(1);transform-origin:left;
}

/* Main Content */
main{max-width:1100px;margin:40px auto;padding:20px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:24px}

/* Cards */
.card{
  background:var(--purple);
  color:#fff;
  border-radius:14px;
  padding:40px 20px;
  text-align:center;
  box-shadow:0 8px 18px rgba(0,0,0,.15);
  transition:.2s;
  text-decoration:none!important;
  display:flex;
  flex-direction:column;
  align-items:center;
}
.card:hover{
  background:linear-gradient(to right,#6A0DAD,#b980ff);
  transform:translateY(-6px) scale(1.03);
  box-shadow:0 14px 28px rgba(0,0,0,.25);
}
.card i{
  font-size:2.5rem;
  margin-bottom:15px;
  color:rgba(255,255,255,.9);
}
.card h2{
  font-size:1.8rem;
  margin:0 0 8px;
  font-weight:700;
  text-shadow:0 2px 4px rgba(0,0,0,.2);
}
        
/* Driver Info Section */
.driver-section{
  background:#fff;
  border-radius:14px;
  padding:30px;
  margin-top:30px;
  box-shadow:0 4px 6px rgba(0,0,0,.1);
}
.driver-section h2{
  color:var(--purple);
  margin-bottom:20px;
  font-size:1.5rem;
  display:flex;
  align-items:center;
  gap:10px;
}
.driver-section h2 i{
  font-size:1.3em;
}
.info-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));
  gap:20px;
  margin-top:20px;
}
.info-item{
  background:#f8f9fa;
  padding:15px;
  border-radius:8px;
  border-left:4px solid var(--purple);
}
.info-label{
  font-size:.85rem;
  color:#666;
  margin-bottom:5px;
  text-transform:uppercase;
  letter-spacing:.5px;
}
.info-value{
  font-size:1.2rem;
  font-weight:600;
  color:#333;
}

/* Responsive Design */
@media (max-width: 1200px) {
  main {
    max-width: 95%;
    margin: 30px auto;
    padding: 15px;
  }
  
  .grid {
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
  }
  
  .card {
    padding: 35px 18px;
  }
  
  .card i {
    font-size: 2.2rem;
    margin-bottom: 12px;
  }
  
  .card h2 {
    font-size: 1.6rem;
    margin: 0 0 6px;
  }
  
  .driver-section {
    padding: 25px;
    margin-top: 25px;
  }
  
  .driver-section h2 {
    font-size: 1.4rem;
    margin-bottom: 15px;
  }
  
  .info-grid {
    gap: 15px;
    margin-top: 15px;
  }
  
  .info-item {
    padding: 12px;
  }
  
  .info-label {
    font-size: 0.8rem;
  }
  
  .info-value {
    font-size: 1.1rem;
  }
}

@media (max-width: 992px) {
  main {
    max-width: 90%;
    margin: 25px auto;
    padding: 12px;
  }
  
  .grid {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 18px;
  }
  
  .card {
    padding: 30px 15px;
  }
  
  .card i {
    font-size: 2rem;
    margin-bottom: 10px;
  }
  
  .card h2 {
    font-size: 1.5rem;
  }
  
  .driver-section {
    padding: 20px;
    margin-top: 20px;
  }
  
  .driver-section h2 {
    font-size: 1.3rem;
  }
  
  .info-grid {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  }
  
  .info-item {
    padding: 10px;
  }
  
  .info-value {
    font-size: 1rem;
  }
}

@media (max-width: 768px) {
  nav {
    padding: 1rem;
    flex-direction: column;
    align-items: flex-start;
  }
  
  .nav-right {
    margin-top: 15px;
    width: 100%;
    flex-wrap: wrap;
    gap: 10px;
  }
  
  .nav-right a {
    padding: 6px 8px;
    font-size: 0.9rem;
  }
  
  main {
    max-width: 100%;
    margin: 20px auto;
    padding: 10px;
  }
  
  .grid {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
  }
  
  .card {
    padding: 25px 12px;
    border-radius: 12px;
  }
  
  .card i {
    font-size: 1.8rem;
    margin-bottom: 8px;
  }
  
  .card h2 {
    font-size: 1.3rem;
    margin: 0 0 5px;
  }
  
  .driver-section {
    padding: 18px;
    margin-top: 18px;
    border-radius: 12px;
  }
  
  .driver-section h2 {
    font-size: 1.2rem;
    margin-bottom: 12px;
  }
  
  .info-grid {
    grid-template-columns: 1fr;
    gap: 12px;
    margin-top: 12px;
  }
  
  .info-item {
    padding: 8px;
  }
  
  .info-label {
    font-size: 0.75rem;
    margin-bottom: 3px;
  }
  
  .info-value {
    font-size: 0.95rem;
  }
  
  footer {
    padding: 0.8rem;
    margin-top: 30px;
  }
  
  footer p {
    font-size: 0.9rem;
  }
}

@media (max-width: 576px) {
  nav {
    padding: 0.8rem 0.5rem;
  }
  
  .logo {
    font-size: 1.3rem;
  }
  
  .nav-right {
    gap: 8px;
    margin-top: 10px;
  }
  
  .nav-right a {
    padding: 5px 6px;
    font-size: 0.8rem;
  }
  
  main {
    padding: 8px;
    margin: 15px auto;
  }
  
  .grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .card {
    padding: 20px 10px;
  }
  
  .card i {
    font-size: 1.6rem;
    margin-bottom: 6px;
  }
  
  .card h2 {
    font-size: 1.2rem;
  }
  
  .driver-section {
    padding: 15px;
    margin-top: 15px;
  }
  
  .driver-section h2 {
    font-size: 1.1rem;
    margin-bottom: 10px;
    gap: 8px;
  }
  
  .driver-section h2 i {
    font-size: 1.1em;
  }
  
  .info-item {
    padding: 6px;
  }
  
  .info-label {
    font-size: 0.7rem;
  }
  
  .info-value {
    font-size: 0.9rem;
  }
  
  footer {
    padding: 0.6rem;
    margin-top: 25px;
  }
  
  footer p {
    font-size: 0.85rem;
  }
}

@media (max-width: 480px) {
  .logo {
    font-size: 1.2rem;
  }
  
  .nav-right a {
    font-size: 0.75rem;
    padding: 4px 5px;
  }
  
  main {
    padding: 6px;
    margin: 12px auto;
  }
  
  .card {
    padding: 18px 8px;
  }
  
  .card i {
    font-size: 1.4rem;
  }
  
  .card h2 {
    font-size: 1.1rem;
  }
  
  .driver-section {
    padding: 12px;
    margin-top: 12px;
  }
  
  .driver-section h2 {
    font-size: 1rem;
    gap: 6px;
  }
  
  .info-grid {
    gap: 10px;
  }
  
  .info-item {
    padding: 5px;
  }
  
  .info-label {
    font-size: 0.65rem;
  }
  
  .info-value {
    font-size: 0.85rem;
  }
}

@media (max-width: 360px) {
  .logo {
    font-size: 1.1rem;
  }
  
  .nav-right a {
    font-size: 0.7rem;
  }
  
  .card {
    padding: 15px 6px;
  }
  
  .card i {
    font-size: 1.3rem;
  }
  
  .card h2 {
    font-size: 1rem;
  }
  
  .driver-section {
    padding: 10px;
  }
  
  .driver-section h2 {
    font-size: 0.95rem;
  }
  
  .info-label {
    font-size: 0.6rem;
  }
  
  .info-value {
    font-size: 0.8rem;
  }
}

.status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-assigned {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-picked_up {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-completed {
            background-color: #e2e3e5;
            color: #383d41;
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo">
            <i class="fas fa-bus"></i> SouthRift Driver
        </div>
        
        <div class="nav-right">
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
            <a href="#"><i class="fas fa-cog"></i> Settings</a>
            <a href="../logout.php" style="text-decoration: none; color: paleturquoise; font-weight: 600; padding: 8px 10px; text-transform: uppercase; letter-spacing: 1px; font-family: 'Poppins', sans-serif;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>

    <main>
        <div class="grid">
            <a href="profile.php" class="card clickable-card">
                <i class="fas fa-user"></i>
                <h2><?= htmlspecialchars($driver_name) ?></h2>
                <p>View Profile <i class="fas fa-arrow-right" style="margin-left: 5px;"></i></p>
            </a>
            
            <div id="locationCard" class="card clickable-card">
                <i class="fas fa-location-dot"></i>
                <h2 id="locationStatus">Share Live Location</h2>
                <p>Share your Google Maps live location with assigned passengers</p>
                
                <!-- Google Maps Link Input Form -->
                <div id="linkInputForm" style="display: none; margin-top: 15px; text-align: left;">
                    <div style="margin-bottom: 10px;">
                        <label for="googleMapsLink" style="display: block; margin-bottom: 5px; font-weight: 600; color: #fff;">Paste Google Maps Link:</label>
                        <input type="text" id="googleMapsLink" placeholder="https://maps.app.goo.gl/..." 
                               style="width: 100%; padding: 10px; border: none; border-radius: 5px; font-size: 14px; margin-bottom: 10px;">
                    </div>
                    <div style="text-align: center; gap: 10px; display: flex; justify-content: center;">
                        <button id="shareLocationBtn" style="background: #4CAF50; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: 600;">
                            <i class="fas fa-share"></i> Share with Passengers
                        </button>
                        <button id="cancelShareBtn" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: 600;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </div>
                
                <!-- Sharing Status -->
                <div id="locationStatusIndicator" style="display: none; margin-top: 10px;">
                    <span class="blinking-dot"></span>
                    <span id="statusText">Google Maps location is being shared</span>
                    <div id="sharedLinkPreview" style="margin-top: 10px; font-size: 0.85rem; opacity: 0.9; word-break: break-all;"></div>
                    <div style="margin-top: 10px;">
                        <button id="stopSharingBtn" style="background: #f44336; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer;">
                            <i class="fas fa-stop"></i> Stop Sharing
                        </button>
                    </div>
                </div>
            </div>
            

            
            <a href="todays_bookings.php" class="card clickable-card">
                <i class="fas fa-calendar-day"></i>
                <h2>Today's Bookings</h2>
                <p>View all bookings for today</p>
            </a>
        </div>
        
        <div class="driver-section">
            <h2><i class="fas fa-id-card"></i> Driver Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?= htmlspecialchars($driver_name) ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Phone Number</div>
                    <div class="info-value"><?= htmlspecialchars($driver_phone) ?></div>
                </div>
                
                <?php if (!empty($vehicle)): ?>
                <div class="info-item">
                    <div class="info-label">Vehicle Type</div>
                    <div class="info-value"><?= htmlspecialchars($vehicle_name) ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Number Plate</div>
                    <div class="info-value"><?= htmlspecialchars($vehicle_plate) ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Vehicle Type</div>
                    <div class="info-value"><?= htmlspecialchars($vehicle['type'] ?? 'N/A') ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Color</div>
                    <div class="info-value"><?= htmlspecialchars($vehicle['color'] ?? 'N/A') ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

   
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <meta name="driver-id" content="<?= $driver_phone ?>">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
    .blinking-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        background-color: #4CAF50;
        border-radius: 50%;
        margin-right: 8px;
        animation: blink 1.5s infinite;
    }
    
    @keyframes blink {
        0% { opacity: 0.2; }
        50% { opacity: 1; }
        100% { opacity: 0.2; }
    }
    
    .sharing-active {
        box-shadow: 0 0 15px rgba(76, 175, 80, 0.5);
        transition: box-shadow 0.3s ease;
        background: linear-gradient(135deg, #6A0DAD, #4CAF50) !important;
    }
    
    #locationCard:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
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
    </style>
    <footer style="background: var(--purple); color: white; text-align: center; padding: 1rem; margin-top: 2rem;">
        &copy; <?= date('Y') ?> Southrift Services Limited | All Rights Reserved
    </footer>
    
    <style>
    :root {
        --purple: #6A0DAD;
        --purple-dark: #4e0b8a;
        --bg: #f4f4f4;
    }
    
    .clickable-card {
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        text-decoration: none;
        color: inherit;
        display: block;
    }
    
    .clickable-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    .dashboard-footer {
        background: #2c3e50;
        color: #ecf0f1;
        padding: 2rem 0 0;
        margin-top: 3rem;
    }
    
    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
    }
    
    .footer-section h4 {
        color: #3498db;
        margin-bottom: 1rem;
    }
    
    .footer-section ul {
        list-style: none;
        padding: 0;
    }
    
    .footer-section a {
        color: #bdc3c7;
        text-decoration: none;
        transition: color 0.2s;
    }
    
    .footer-section a:hover {
        color: #3498db;
    }
    
    .footer-section i {
        margin-right: 8px;
        width: 20px;
        text-align: center;
    }
    
    .footer-bottom {
        text-align: center;
        padding: 1rem 0;
        margin-top: 2rem;
        border-top: 1px solid rgba(255,255,255,0.1);
        font-size: 0.9rem;
        color: #7f8c8d;
    }
    
    @media (max-width: 768px) {
        .footer-content {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
    }
    </style>
    
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
            const locationCard = document.getElementById('locationCard');
            const shareBtn = document.getElementById('shareLocationBtn');
            const cancelBtn = document.getElementById('cancelShareBtn');
            const stopBtn = document.getElementById('stopSharingBtn');
            const linkInput = document.getElementById('googleMapsLink');

            // Location card click handler
            locationCard.addEventListener('click', (e) => {
                // Don't trigger if clicking on buttons or input
                if (e.target.tagName === 'BUTTON' || e.target.tagName === 'INPUT') return;
                
                if (!this.isSharing) {
                    this.showLinkInputForm();
                }
            });

            // Share button click
            shareBtn.addEventListener('click', () => {
                this.shareGoogleMapsLink();
            });

            // Cancel button click
            cancelBtn.addEventListener('click', () => {
                this.hideLinkInputForm();
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

        showLinkInputForm() {
            document.getElementById('linkInputForm').style.display = 'block';
            document.getElementById('googleMapsLink').focus();
        }

        hideLinkInputForm() {
            document.getElementById('linkInputForm').style.display = 'none';
            document.getElementById('googleMapsLink').value = '';
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
                    this.hideLinkInputForm();
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
                shareBtn.innerHTML = '<i class="fas fa-share"></i> Share with Passengers';
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
            const cardElement = document.getElementById('locationCard');

            if (isSharing) {
                statusElement.innerHTML = 'Google Maps Location Shared <i class="fas fa-check-circle" style="color: #4CAF50;"></i>';
                indicatorElement.style.display = 'block';
                cardElement.classList.add('sharing-active');
            } else {
                statusElement.innerHTML = 'Share Live Location';
                indicatorElement.style.display = 'none';
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
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 5px;
                color: white;
                font-weight: 500;
                z-index: 1000;
                max-width: 300px;
                word-wrap: break-word;
                animation: slideIn 0.3s ease-out;
            `;
            
            // Set background color based on type
            switch (type) {
                case 'success':
                    notification.style.backgroundColor = '#4CAF50';
                    break;
                case 'error':
                    notification.style.backgroundColor = '#f44336';
                    break;
                default:
                    notification.style.backgroundColor = '#2196F3';
            }
            
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
