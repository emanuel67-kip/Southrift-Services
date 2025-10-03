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

// Get user's active booking with assigned vehicle
$booking_stmt = $conn->prepare("
    SELECT b.*, v.driver_phone, v.driver_name, v.number_plate, v.type as vehicle_type, v.color
    FROM bookings b
    JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE b.user_id = ?
    AND b.assigned_vehicle IS NOT NULL
    AND b.assigned_vehicle != ''
    AND DATE(b.travel_date) = CURDATE()
    ORDER BY b.created_at DESC
    LIMIT 1
");
$booking_stmt->bind_param('i', $user_id);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();
$booking = $booking_result->fetch_assoc();

if ($booking) {
    // Check if this booking has a direct Google Maps link attached by the driver
    if (!empty($booking['google_maps_link']) && isset($_GET['redirect']) && $_GET['redirect'] === 'true') {
        // Redirect directly to the Google Maps link if redirect parameter is set
        header('Location: ' . $booking['google_maps_link']);
        exit();
    }
    
    // Check if driver is sharing Google Maps link
    $google_maps_link = null;
    $driver_location = null;
    $gmaps_data = null;
    
    // First, check for Google Maps link sharing
    $gmaps_stmt = $conn->prepare("
        SELECT dl.google_maps_link, dl.status, dl.last_updated,
               d.name as driver_name, d.driver_phone
        FROM driver_locations dl
        JOIN drivers d ON dl.driver_id = d.id
        WHERE d.driver_phone = ?
        AND dl.status = 'sharing_gmaps'
        AND dl.google_maps_link IS NOT NULL
        AND dl.google_maps_link != ''
        ORDER BY dl.last_updated DESC
        LIMIT 1
    ");
    $gmaps_stmt->bind_param('s', $booking['driver_phone']);
    $gmaps_stmt->execute();
    $gmaps_result = $gmaps_stmt->get_result();
    $gmaps_data = $gmaps_result->fetch_assoc();
    
    if ($gmaps_data && !empty($gmaps_data['google_maps_link'])) {
        // Driver is sharing Google Maps link
        $google_maps_link = $gmaps_data['google_maps_link'];
    } else {
        // Fallback: Check for GPS location sharing (existing system)
        $location_stmt = $conn->prepare("
            SELECT dl.latitude, dl.longitude, dl.status, dl.last_updated, dl.accuracy, dl.speed,
                   d.name as driver_name, d.driver_phone
            FROM driver_locations dl
            JOIN drivers d ON dl.driver_id = d.id
            WHERE d.driver_phone = ?
            AND dl.status = 'active'
            AND dl.last_updated >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ORDER BY dl.last_updated DESC
            LIMIT 1
        ");
        $location_stmt->bind_param('s', $booking['driver_phone']);
        $location_stmt->execute();
        $location_result = $location_stmt->get_result();
        $driver_location = $location_result->fetch_assoc();
    }
    
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
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: var(--bg);
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: var(--purple);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }

        .content {
            background: white;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .status-card {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .status-active {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid #28a745;
            color: #28a745;
        }

        .status-inactive {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid #dc3545;
            color: #dc3545;
        }

        .map-container {
            width: 100%;
            height: 400px;
            border-radius: 8px;
            overflow: hidden;
            margin: 20px 0;
            border: 1px solid #ddd;
        }

        .map-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .btn {
            background: var(--purple);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }

        .btn:hover {
            background: var(--purple-dark);
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }

        .blinking-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: #28a745;
            border-radius: 50%;
            margin-right: 8px;
            animation: blink 1.5s infinite;
        }

        @keyframes blink {
            0% { opacity: 0.2; }
            50% { opacity: 1; }
            100% { opacity: 0.2; }
        }
        
        .google-maps-container a:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3) !important;
            background: #f8f9fa !important;
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .container {
                max-width: 700px;
                padding: 15px;
            }
            
            .header {
                padding: 18px;
            }
            
            .content {
                padding: 25px;
            }
            
            .map-container {
                height: 350px;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 0.95rem;
            }
        }

        @media (max-width: 768px) {
            .container {
                max-width: 100%;
                padding: 10px;
            }
            
            .header {
                padding: 15px;
                border-radius: 8px 8px 0 0;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .content {
                padding: 20px;
                border-radius: 0 0 8px 8px;
            }
            
            .status-card {
                padding: 15px;
            }
            
            .map-container {
                height: 300px;
                margin: 15px 0;
            }
            
            .btn {
                padding: 10px 15px;
                font-size: 0.9rem;
                margin: 5px 0;
                width: 100%;
                margin-bottom: 10px;
            }
            
            .google-maps-container a {
                display: block;
                margin: 10px 0 !important;
                padding: 12px 20px !important;
                font-size: 1rem !important;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding: 8px;
            }
            
            .header {
                padding: 12px;
            }
            
            .header h1 {
                font-size: 1.3rem;
            }
            
            .content {
                padding: 15px;
            }
            
            .status-card {
                padding: 12px;
            }
            
            .map-container {
                height: 250px;
                margin: 12px 0;
            }
            
            .btn {
                padding: 8px 12px;
                font-size: 0.85rem;
            }
            
            .alert {
                padding: 12px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 1.2rem;
            }
            
            .content {
                padding: 12px;
            }
            
            .status-card {
                padding: 10px;
            }
            
            .map-container {
                height: 200px;
                margin: 10px 0;
            }
            
            .btn {
                padding: 6px 10px;
                font-size: 0.8rem;
            }
            
            .google-maps-container a {
                padding: 10px 15px !important;
                font-size: 0.9rem !important;
            }
        }

        @media (max-width: 360px) {
            .header h1 {
                font-size: 1.1rem;
            }
            
            .content {
                padding: 10px;
            }
            
            .status-card {
                padding: 8px;
                font-size: 0.85rem;
            }
            
            .map-container {
                height: 180px;
            }
            
            .btn {
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-map-marker-alt"></i> Track Your Ride</h1>
            <p>Real-time location tracking for your journey</p>
        </div>
        
        <div class="content">
            <?php if (!$booking): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    You don't have any active bookings with assigned vehicles for today. <a href="book.php">Make a booking</a> to track your ride.
                </div>
            <?php else: ?>
                <?php if (!empty($booking['google_maps_link'])): ?>
                <!-- Direct Google Maps Link from Driver -->
                <div class="status-card status-active pulse">
                    <span class="blinking-dot"></span>
                    <strong><i class="fas fa-link"></i> Driver has shared live location</strong>
                    <p style="margin-top: 10px;">
                        Your driver has shared their live Google Maps location for today's trip.
                        <br><small>Updated: <?= !empty($booking['shared_location_updated']) ? date('H:i:s', strtotime($booking['shared_location_updated'])) : 'Recently' ?></small>
                    </p>
                </div>
                
                <div class="google-maps-container">
                    <div style="text-align: center; padding: 30px; background: linear-gradient(135deg, #4285f4, #34a853); color: white; border-radius: 10px; margin: 20px 0;">
                        <i class="fab fa-google" style="font-size: 3rem; margin-bottom: 15px;"></i>
                        <h3 style="margin-bottom: 15px;">Driver's Live Location</h3>
                        <p style="margin-bottom: 20px; opacity: 0.9;">Click below to view your driver's real-time location on Google Maps</p>
                        <a href="<?= htmlspecialchars($booking['google_maps_link']) ?>" target="_blank" 
                           style="display: inline-block; background: white; color: #4285f4; padding: 15px 30px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 1.1rem; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.2); margin-right: 10px;">
                            <i class="fas fa-external-link-alt"></i> Open Live Location
                        </a>
                        <a href="?redirect=true" 
                           style="display: inline-block; background: rgba(255,255,255,0.2); color: white; padding: 15px 30px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 1.1rem; transition: all 0.3s ease; border: 2px solid white;">
                            <i class="fas fa-arrow-right"></i> Go Direct
                        </a>
                    </div>
                </div>
                
                <?php elseif ($google_maps_link): ?>
                <div class="status-card status-active pulse">
                    <span class="blinking-dot"></span>
                    <strong><i class="fas fa-link"></i> Driver is sharing Google Maps location</strong>
                    <p style="margin-top: 10px;">
                        Last updated: <span id="lastUpdateTime"><?= date('H:i:s', strtotime($gmaps_data['last_updated'])) ?></span>
                        | Shared via Google Maps
                    </p>
                </div>
                
                <div class="google-maps-container">
                    <div style="text-align: center; padding: 30px; background: linear-gradient(135deg, #4285f4, #34a853); color: white; border-radius: 10px; margin: 20px 0;">
                        <i class="fab fa-google" style="font-size: 3rem; margin-bottom: 15px;"></i>
                        <h3 style="margin-bottom: 15px;">Google Maps Live Location</h3>
                        <p style="margin-bottom: 20px; opacity: 0.9;">Your driver is sharing their live location via Google Maps</p>
                        <a href="<?= htmlspecialchars($google_maps_link) ?>" target="_blank" 
                           style="display: inline-block; background: white; color: #4285f4; padding: 15px 30px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 1.1rem; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                            <i class="fas fa-external-link-alt"></i> Open in Google Maps
                        </a>
                    </div>
                </div>
                
            <?php elseif ($driver_location): ?>
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
                        <iframe 
                            id="mapFrame"
                            src="https://www.google.com/maps?q=<?= $driver_location['latitude'] ?>,<?= $driver_location['longitude'] ?>&z=16&output=embed"
                            allowfullscreen=""
                            loading="lazy">
                        </iframe>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="https://www.google.com/maps?q=<?= $driver_location['latitude'] ?>,<?= $driver_location['longitude'] ?>&z=16" 
                           target="_blank" class="btn">
                            <i class="fas fa-external-link-alt"></i> Open in Google Maps
                        </a>
                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $driver_location['latitude'] ?>,<?= $driver_location['longitude'] ?>" 
                           target="_blank" class="btn" style="margin-left: 10px; background: #28a745;">
                            <i class="fas fa-route"></i> Get Directions
                        </a>
                        <button onclick="refreshLocation()" class="btn" id="refreshBtn" style="margin-left: 10px; background: #6c757d;">
                            <i class="fas fa-sync-alt"></i> Refresh Location
                        </button>
                    </div>
                <?php else: ?>
                    <div class="status-card status-inactive">
                        <i class="fas fa-location-slash"></i>
                        <strong>Your driver has not shared his live location</strong>
                        <p style="margin-top: 10px;">Please wait for your driver to start sharing their location or contact them directly</p>
                        <div style="margin-top: 15px; padding: 15px; background: rgba(255,255,255,0.1); border-radius: 8px;">
                            <p style="font-size: 0.9rem; opacity: 0.8;">
                                <i class="fas fa-info-circle"></i> 
                                Your driver can share location using:
                            </p>
                            <ul style="margin: 10px 0; padding-left: 20px; font-size: 0.9rem; opacity: 0.8;">
                                <li>Google Maps live location sharing</li>
                                <li>Built-in GPS tracking</li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="profile.php" class="btn">
                        <i class="fas fa-arrow-left"></i> Back to Profile
                    </a>
                    <a href="tel:<?= htmlspecialchars($driver_info['phone']) ?>" class="btn" style="margin-left: 10px; background: #007bff;">
                        <i class="fas fa-phone"></i> Call Driver
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Enhanced auto-refresh with better UX
        let refreshInterval = null;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
        function refreshLocation() {
            const refreshBtn = document.getElementById('refreshBtn');
            if (refreshBtn) {
                const originalText = refreshBtn.innerHTML;
                refreshBtn.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Refreshing...';
                refreshBtn.disabled = true;
                
                setTimeout(() => {
                    location.reload();
                }, 500);
            } else {
                location.reload();
            }
        }
        
        // Auto-refresh based on driver sharing status
        <?php if ($google_maps_link): ?>
        // Google Maps link is being shared - refresh every 60 seconds to check if still active
        refreshInterval = setInterval(function() {
            location.reload();
        }, 60000);
        <?php elseif ($driver_location): ?>
        // GPS location is being shared - refresh every 15 seconds for real-time updates
        refreshInterval = setInterval(function() {
            location.reload();
        }, 15000);
        <?php else: ?>
        // No location sharing - check every 30 seconds if driver starts sharing
        refreshInterval = setInterval(function() {
            location.reload();
        }, 30000);
        <?php endif; ?>
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });
    </script>
</body>
</html>