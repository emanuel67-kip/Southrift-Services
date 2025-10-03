<?php
// Configure session to match the login system
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters to match login.php
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    ini_set('session.use_only_cookies', 1);
    
    // Set the same session name as login system
    session_name('southrift_admin');
    
    // Set session cookie parameters
    $lifetime = 60 * 60; // 1 hour for passengers
    $path = '/';
    $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $httponly = true;
    
    session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
    
    // Start the session
    session_start();
} else {
    session_start();
}
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$driver_location = null;
$driver_info = null;
$booking = null;

// Get user's active booking for today
$booking_stmt = $conn->prepare("
    SELECT b.*, v.driver_phone, v.driver_name, v.number_plate, v.type as vehicle_type, v.color
    FROM bookings b
    JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE b.user_id = ?
    AND DATE(b.travel_date) = CURDATE()
    ORDER BY b.created_at DESC
    LIMIT 1
");
$booking_stmt->bind_param('i', $user_id);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();
$booking = $booking_result->fetch_assoc();

if ($booking) {
    // Get driver's current location if they're sharing
    $location_stmt = $conn->prepare("
        SELECT dl.latitude, dl.longitude, dl.status, dl.last_updated, dl.accuracy, dl.speed,
               d.name as driver_name, d.driver_phone, d.phone
        FROM driver_locations dl
        JOIN drivers d ON dl.driver_id = d.id
        WHERE (d.driver_phone = ? OR d.phone = ?)
        AND dl.status = 'active'
        AND dl.last_updated >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ORDER BY dl.last_updated DESC
        LIMIT 1
    ");
    $location_stmt->bind_param('ss', $booking['driver_phone'], $booking['driver_phone']);
    $location_stmt->execute();
    $location_result = $location_stmt->get_result();
    $driver_location = $location_result->fetch_assoc();
    
    $driver_info = [
        'name' => $booking['driver_name'],
        'phone' => $booking['driver_phone'],
        'vehicle_plate' => $booking['number_plate'],
        'vehicle_type' => $booking['vehicle_type'],
        'vehicle_color' => $booking['color'] ?? 'N/A'
    ];
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">
    <meta name="user-id" content="<?= $user_id ?>">
    <title>Track Your Ride - SouthRift Services</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --purple: #6A0DAD;
            --purple-dark: #4e0b8a;
            --purple-light: #8e44ad;
            --bg: #f8f9fa;
            --card-bg: #ffffff;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --text-dark: #2c3e50;
            --text-light: #6c757d;
            --border: #e9ecef;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--bg) 0%, #e8f4f8 100%);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, var(--purple) 0%, var(--purple-light) 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 15px 15px 0 0;
            box-shadow: 0 4px 20px rgba(106, 13, 173, 0.3);
        }

        .header h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .content {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .status-card {
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            border: 2px solid;
            position: relative;
            overflow: hidden;
        }

        .status-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .status-card:hover::before {
            left: 100%;
        }

        .status-active {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-color: var(--success);
            color: #155724;
        }

        .status-inactive {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border-color: var(--danger);
            color: #721c24;
        }

        .status-waiting {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-color: var(--warning);
            color: #856404;
        }

        .driver-info {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 2px solid var(--info);
        }

        .driver-info h3 {
            color: var(--info);
            margin-bottom: 15px;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.8);
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid var(--info);
        }

        .info-label {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .map-container {
            width: 100%;
            height: 450px;
            border-radius: 12px;
            overflow: hidden;
            margin: 25px 0;
            border: 3px solid var(--border);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .map-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .map-overlay {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.95);
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 10;
        }

        .btn {
            background: linear-gradient(135deg, var(--purple) 0%, var(--purple-light) 100%);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(106, 13, 173, 0.3);
            color: white;
            text-decoration: none;
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--text-light) 0%, #5a6268 100%);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #218838 100%);
        }

        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin: 25px 0;
        }

        .alert {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 2px solid;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .alert-info {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            border-color: var(--info);
            color: #0c5460;
        }

        .blinking-dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            background-color: var(--success);
            border-radius: 50%;
            margin-right: 8px;
            animation: blink 1.5s infinite;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
        }

        @keyframes blink {
            0% { opacity: 0.3; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1); }
            100% { opacity: 0.3; transform: scale(0.8); }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 1.8rem;
            }
            
            .content {
                padding: 20px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .btn-group {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
            
            .map-container {
                height: 350px;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 1.6rem;
            }
            
            .map-overlay {
                top: 10px;
                right: 10px;
                font-size: 0.8rem;
                padding: 8px 12px;
            }
        }

        /* Auto-refresh indicator */
        .refresh-indicator {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(106, 13, 173, 0.9);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .refresh-indicator.active {
            background: rgba(40, 167, 69, 0.9);
        }
    </style>
</head>
<body>
    <div class="refresh-indicator" id="refreshIndicator">
        <i class="fas fa-sync-alt"></i>
        <span>Auto-refresh: ON</span>
    </div>

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-map-marker-alt"></i> Track Your Ride</h1>
            <p>Real-time location tracking for your journey with SouthRift Services</p>
        </div>
        
        <div class="content">
            <?php if (!$booking): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle fa-2x"></i>
                    <div>
                        <strong>No Active Booking Found</strong><br>
                        You don't have any active bookings for today. <a href="book.php" style="color: #0c5460; text-decoration: underline;">Make a booking</a> to track your ride.
                    </div>
                </div>
            <?php else: ?>
                <!-- Driver Information Card -->
                <div class="driver-info">
                    <h3><i class="fas fa-user-tie"></i> Your Driver Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Driver Name</div>
                            <div class="info-value"><?= htmlspecialchars($driver_info['name']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Phone Number</div>
                            <div class="info-value"><?= htmlspecialchars($driver_info['phone']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Vehicle Type</div>
                            <div class="info-value"><?= htmlspecialchars($driver_info['vehicle_type']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Number Plate</div>
                            <div class="info-value"><?= htmlspecialchars($driver_info['vehicle_plate']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Vehicle Color</div>
                            <div class="info-value"><?= htmlspecialchars($driver_info['vehicle_color']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Booking Status</div>
                            <div class="info-value status-<?= $booking['status'] ?>"><?= ucfirst(str_replace('_', ' ', $booking['status'])) ?></div>
                        </div>
                    </div>
                </div>

                <?php if ($driver_location): ?>
                    <div class="status-card status-active pulse">
                        <span class="blinking-dot"></span>
                        <strong><i class="fas fa-satellite-dish"></i> Driver is sharing live location</strong>
                        <p style="margin-top: 10px;">
                            Last updated: <span id="lastUpdateTime"><?= date('H:i:s', strtotime($driver_location['last_updated'])) ?></span>
                            <?php if ($driver_location['accuracy']): ?>
                                | Accuracy: <?= round($driver_location['accuracy']) ?>m
                            <?php endif; ?>
                            <?php if ($driver_location['speed']): ?>
                                | Speed: <?= round($driver_location['speed'] * 3.6, 1) ?> km/h
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div class="map-container">
                        <div class="map-overlay">
                            <i class="fas fa-map-marker-alt" style="color: var(--danger);"></i>
                            Driver Location
                        </div>
                        <iframe 
                            id="mapFrame"
                            src="https://www.google.com/maps?q=<?= $driver_location['latitude'] ?>,<?= $driver_location['longitude'] ?>&z=16&output=embed"
                            allowfullscreen=""
                            loading="lazy">
                        </iframe>
                    </div>
                    
                    <div class="btn-group">
                        <a href="https://www.google.com/maps?q=<?= $driver_location['latitude'] ?>,<?= $driver_location['longitude'] ?>&z=16" 
                           target="_blank" class="btn">
                            <i class="fas fa-external-link-alt"></i> Open in Google Maps
                        </a>
                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $driver_location['latitude'] ?>,<?= $driver_location['longitude'] ?>" 
                           target="_blank" class="btn btn-success">
                            <i class="fas fa-route"></i> Get Directions
                        </a>
                        <button onclick="refreshLocation()" class="btn btn-secondary" id="refreshBtn">
                            <i class="fas fa-sync-alt"></i> Refresh Location
                        </button>
                    </div>
                <?php else: ?>
                    <div class="status-card status-inactive">
                        <i class="fas fa-location-slash fa-2x" style="margin-bottom: 15px;"></i>
                        <strong>Driver is not currently sharing location</strong>
                        <p style="margin-top: 10px;">Location will appear here when your driver starts sharing. We'll check automatically every 30 seconds.</p>
                    </div>
                    
                    <div class="status-card status-waiting">
                        <i class="fas fa-clock fa-2x" style="margin-bottom: 15px;"></i>
                        <strong>Waiting for driver to start sharing...</strong>
                        <p style="margin-top: 10px;">Your driver will share their location when they're ready to start your trip.</p>
                    </div>
                <?php endif; ?>
                
                <div class="btn-group">
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Profile
                    </a>
                    <a href="tel:<?= htmlspecialchars($driver_info['phone']) ?>" class="btn">
                        <i class="fas fa-phone"></i> Call Driver
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        class DriverTracker {
            constructor() {
                this.refreshInterval = null;
                this.isTracking = <?= $driver_location ? 'true' : 'false' ?>;
                this.lastUpdateTime = '<?= $driver_location ? date('H:i:s', strtotime($driver_location['last_updated'])) : '' ?>';
                this.csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                
                this.initializeTracking();
            }

            initializeTracking() {
                // Start auto-refresh if driver is sharing location
                if (this.isTracking) {
                    this.startAutoRefresh();
                } else {
                    // Check every 30 seconds if driver starts sharing
                    this.startLocationCheck();
                }

                // Update UI indicators
                this.updateRefreshIndicator();
            }

            startAutoRefresh() {
                // Refresh every 15 seconds when actively tracking
                this.refreshInterval = setInterval(() => {
                    this.refreshLocation(false);
                }, 15000);
                
                this.updateRefreshIndicator(true);
            }

            startLocationCheck() {
                // Check every 30 seconds if driver starts sharing
                this.refreshInterval = setInterval(() => {
                    this.refreshLocation(false);
                }, 30000);
                
                this.updateRefreshIndicator(false);
            }

            async refreshLocation(manual = true) {
                const refreshBtn = document.getElementById('refreshBtn');
                const refreshIndicator = document.getElementById('refreshIndicator');
                
                if (manual && refreshBtn) {
                    refreshBtn.innerHTML = '<div class="loading-spinner"></div> Refreshing...';
                    refreshBtn.disabled = true;
                }

                if (refreshIndicator) {
                    refreshIndicator.classList.add('active');
                    refreshIndicator.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> <span>Refreshing...</span>';
                }

                try {
                    // Add cache busting parameter
                    const response = await fetch(`${window.location.href}?refresh=${Date.now()}`);
                    
                    if (response.ok) {
                        // Parse the response to check for location updates
                        const html = await response.text();
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        
                        // Check if location is now available or updated
                        const newStatusCard = doc.querySelector('.status-card');
                        const currentStatusCard = document.querySelector('.status-card');
                        
                        if (newStatusCard && currentStatusCard) {
                            const hasLocation = newStatusCard.classList.contains('status-active');
                            const hadLocation = currentStatusCard.classList.contains('status-active');
                            
                            if (hasLocation !== hadLocation || 
                                (hasLocation && this.hasLocationChanged(doc))) {
                                // Reload the page to show updated location
                                window.location.reload();
                                return;
                            }
                        }
                        
                        // Update last update time if available
                        const newTimeElement = doc.getElementById('lastUpdateTime');
                        const currentTimeElement = document.getElementById('lastUpdateTime');
                        
                        if (newTimeElement && currentTimeElement && 
                            newTimeElement.textContent !== currentTimeElement.textContent) {
                            currentTimeElement.textContent = newTimeElement.textContent;
                            this.showNotification('Location updated', 'success');
                        }
                        
                    } else {
                        throw new Error('Failed to refresh location');
                    }
                } catch (error) {
                    console.error('Error refreshing location:', error);
                    if (manual) {
                        this.showNotification('Failed to refresh location', 'error');
                    }
                } finally {
                    if (manual && refreshBtn) {
                        refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh Location';
                        refreshBtn.disabled = false;
                    }

                    if (refreshIndicator) {
                        refreshIndicator.classList.remove('active');
                        this.updateRefreshIndicator(this.isTracking);
                    }
                }
            }

            hasLocationChanged(doc) {
                const newMapFrame = doc.getElementById('mapFrame');
                const currentMapFrame = document.getElementById('mapFrame');
                
                if (newMapFrame && currentMapFrame) {
                    return newMapFrame.src !== currentMapFrame.src;
                }
                
                return false;
            }

            updateRefreshIndicator(isActive = false) {
                const indicator = document.getElementById('refreshIndicator');
                if (!indicator) return;

                if (isActive) {
                    indicator.innerHTML = '<i class="fas fa-sync-alt"></i> <span>Auto-refresh: Active</span>';
                    indicator.classList.add('active');
                } else {
                    indicator.innerHTML = '<i class="fas fa-sync-alt"></i> <span>Auto-refresh: Checking</span>';
                    indicator.classList.remove('active');
                }
            }

            showNotification(message, type = 'info') {
                const notification = document.createElement('div');
                notification.style.cssText = `
                    position: fixed;
                    top: 70px;
                    right: 20px;
                    padding: 15px 20px;
                    border-radius: 8px;
                    color: white;
                    font-weight: 500;
                    z-index: 1001;
                    max-width: 300px;
                    transform: translateX(100%);
                    transition: transform 0.3s ease;
                `;
                
                switch (type) {
                    case 'success':
                        notification.style.background = 'linear-gradient(135deg, #28a745, #20c997)';
                        break;
                    case 'error':
                        notification.style.background = 'linear-gradient(135deg, #dc3545, #e74c3c)';
                        break;
                    default:
                        notification.style.background = 'linear-gradient(135deg, #17a2b8, #20c997)';
                }
                
                notification.textContent = message;
                document.body.appendChild(notification);
                
                // Slide in
                setTimeout(() => {
                    notification.style.transform = 'translateX(0)';
                }, 100);
                
                // Slide out and remove
                setTimeout(() => {
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }, 3000);
            }

            destroy() {
                if (this.refreshInterval) {
                    clearInterval(this.refreshInterval);
                    this.refreshInterval = null;
                }
            }
        }

        // Global refresh function for manual button
        function refreshLocation() {
            if (window.driverTracker) {
                window.driverTracker.refreshLocation(true);
            }
        }

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            window.driverTracker = new DriverTracker();
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (window.driverTracker) {
                window.driverTracker.destroy();
            }
        });
    </script>
</body>
</html>