<?php
// Secure tracking page for passengers using tokens
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

// Get token from URL
$token = $_GET['token'] ?? '';
$pin = $_GET['pin'] ?? '';

if (empty($token)) {
    http_response_code(400);
    die('Invalid tracking link. Please check your message for the correct link.');
}

// Validate token and get session information
$session_data = null;
$driver_info = null;
$error_message = null;

try {
    // Check if session exists and is active
    $stmt = $conn->prepare("
        SELECT 
            dss.id, dss.driver_id, dss.token, dss.status, dss.created_at, dss.expires_at,
            d.name as driver_name, d.driver_phone,
            v.number_plate, v.type as vehicle_type, v.color
        FROM driver_share_sessions dss
        JOIN drivers d ON dss.driver_id = d.id
        LEFT JOIN vehicles v ON d.driver_phone = v.driver_phone
        WHERE dss.token = ? AND dss.status = 'active'
        LIMIT 1
    ");
    
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $session_data = $result->fetch_assoc();
    
    if (!$session_data) {
        $error_message = 'This tracking link is no longer valid or has expired.';
    } elseif (new DateTime($session_data['expires_at']) < new DateTime()) {
        $error_message = 'This tracking link has expired.';
    } else {
        $driver_info = [
            'name' => $session_data['driver_name'],
            'phone' => $session_data['driver_phone'],
            'vehicle_plate' => $session_data['number_plate'],
            'vehicle_type' => $session_data['vehicle_type'],
            'vehicle_color' => $session_data['color']
        ];
    }
    
} catch (Exception $e) {
    error_log("Token validation error: " . $e->getMessage());
    $error_message = 'Unable to validate tracking link. Please try again later.';
}

// Get Maps API key
$MAPS_KEY = 'AIzaSyBFw0Qbyq9zTFTd-tUY6dpoWCjVOlY5g9U'; // Replace with your actual key
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Driver - SouthRift Services</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= bin2hex(random_bytes(16)) ?>">
    <meta name="session-token" content="<?= htmlspecialchars($token) ?>">
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

        .header {
            background: var(--purple);
            color: white;
            padding: 15px 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .driver-info {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .driver-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-item i {
            color: var(--purple);
            width: 20px;
        }

        #map {
            width: 100%;
            height: 400px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .status-panel {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .status-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-dot {
            width: 12px;
            height: 12px;
            background: #4CAF50;
            border-radius: 50%;
            animation: blink 1.5s infinite;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            background: var(--purple);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: var(--purple-dark);
        }

        .btn-call {
            background: #28a745;
        }

        .btn-call:hover {
            background: #218838;
        }

        .btn-directions {
            background: #007bff;
        }

        .btn-directions:hover {
            background: #0056b3;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #f5c6cb;
            text-align: center;
        }

        .loading {
            text-align: center;
            padding: 40px;
        }

        .loading i {
            font-size: 2rem;
            color: var(--purple);
            animation: spin 2s linear infinite;
        }

        @keyframes blink {
            0% { opacity: 0.2; }
            50% { opacity: 1; }
            100% { opacity: 0.2; }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .status-panel {
                flex-direction: column;
                align-items: stretch;
            }
            
            .action-buttons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-map-marker-alt"></i> Live Driver Tracking</h1>
        <p>Real-time location updates from your driver</p>
    </div>

    <div class="container">
        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Access Denied</h3>
                <p><?= htmlspecialchars($error_message) ?></p>
                <p style="margin-top: 10px;">
                    <small>If you believe this is an error, please contact SouthRift Services.</small>
                </p>
            </div>
        <?php else: ?>
            <?php if ($driver_info): ?>
                <div class="driver-info">
                    <h3><i class="fas fa-user-tie"></i> Your Driver</h3>
                    <div class="driver-details">
                        <div class="detail-item">
                            <i class="fas fa-user"></i>
                            <span><strong><?= htmlspecialchars($driver_info['name']) ?></strong></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-car"></i>
                            <span><?= htmlspecialchars($driver_info['vehicle_plate']) ?></span>
                        </div>
                        <?php if ($driver_info['vehicle_type']): ?>
                        <div class="detail-item">
                            <i class="fas fa-info-circle"></i>
                            <span><?= htmlspecialchars($driver_info['vehicle_type']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($driver_info['vehicle_color']): ?>
                        <div class="detail-item">
                            <i class="fas fa-palette"></i>
                            <span><?= htmlspecialchars($driver_info['vehicle_color']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div id="map">
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p style="margin-top: 10px;">Loading map...</p>
                </div>
            </div>

            <div class="status-panel">
                <div class="status-info">
                    <div class="status-dot"></div>
                    <div>
                        <strong id="statusText">Connecting to driver...</strong>
                        <div id="lastUpdate" style="font-size: 0.9rem; color: #666;"></div>
                    </div>
                </div>
                <div class="action-buttons">
                    <button id="refreshBtn" class="btn">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <?php if ($driver_info['phone']): ?>
                    <a href="tel:<?= htmlspecialchars($driver_info['phone']) ?>" class="btn btn-call">
                        <i class="fas fa-phone"></i> Call Driver
                    </a>
                    <?php endif; ?>
                    <button id="directionsBtn" class="btn btn-directions" style="display: none;">
                        <i class="fas fa-route"></i> Get Directions
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        let map, marker, accuracyCircle;
        const sessionToken = document.querySelector('meta[name="session-token"]').content;
        let updateInterval;
        let currentLocation = null;

        function initMap() {
            const defaultLocation = { lat: -1.286389, lng: 36.817223 }; // Nairobi
            
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 10,
                center: defaultLocation,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
                styles: [
                    {
                        featureType: "poi",
                        elementType: "labels",
                        stylers: [{ visibility: "off" }]
                    }
                ]
            });

            marker = new google.maps.Marker({
                position: defaultLocation,
                map: map,
                icon: {
                    url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png',
                    scaledSize: new google.maps.Size(40, 40)
                },
                title: 'Driver Location'
            });

            // Start fetching location updates
            fetchDriverLocation();
            updateInterval = setInterval(fetchDriverLocation, 10000); // Update every 10 seconds
        }

        function fetchDriverLocation() {
            fetch('get_driver_location_by_token.php?token=' + encodeURIComponent(sessionToken))
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.location) {
                        updateDriverLocation(data.location);
                    } else {
                        updateStatus('Driver location not available', false);
                    }
                })
                .catch(error => {
                    console.error('Error fetching location:', error);
                    updateStatus('Connection error', false);
                });
        }

        function updateDriverLocation(location) {
            if (location.latitude && location.longitude) {
                const position = {
                    lat: parseFloat(location.latitude),
                    lng: parseFloat(location.longitude)
                };

                currentLocation = position;
                
                // Update marker position
                marker.setPosition(position);
                
                // Center map on new position
                map.setCenter(position);
                map.setZoom(15);

                // Show accuracy circle if available
                if (location.accuracy) {
                    if (accuracyCircle) {
                        accuracyCircle.setMap(null);
                    }
                    
                    accuracyCircle = new google.maps.Circle({
                        center: position,
                        radius: parseFloat(location.accuracy),
                        fillColor: '#4CAF50',
                        fillOpacity: 0.1,
                        strokeColor: '#4CAF50',
                        strokeOpacity: 0.3,
                        strokeWeight: 1,
                        map: map
                    });
                }

                // Update status
                const timestamp = location.last_updated ? 
                    new Date(location.last_updated).toLocaleTimeString() : 
                    new Date().toLocaleTimeString();
                
                updateStatus('Live location active', true, timestamp);
                
                // Show directions button
                document.getElementById('directionsBtn').style.display = 'inline-flex';
                document.getElementById('directionsBtn').onclick = () => {
                    const url = `https://www.google.com/maps/dir/?api=1&destination=${position.lat},${position.lng}`;
                    window.open(url, '_blank');
                };
            }
        }

        function updateStatus(text, isActive, timestamp = null) {
            document.getElementById('statusText').textContent = text;
            
            const lastUpdate = document.getElementById('lastUpdate');
            if (timestamp) {
                lastUpdate.textContent = `Last updated: ${timestamp}`;
            } else {
                lastUpdate.textContent = '';
            }
            
            const statusDot = document.querySelector('.status-dot');
            if (isActive) {
                statusDot.style.background = '#4CAF50';
                statusDot.style.animation = 'blink 1.5s infinite';
            } else {
                statusDot.style.background = '#ff5722';
                statusDot.style.animation = 'none';
            }
        }

        // Refresh button
        document.getElementById('refreshBtn').addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Refreshing...';
            fetchDriverLocation();
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
            }, 1000);
        });

        // Load Google Maps
        function loadGoogleMaps() {
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=<?= $MAPS_KEY ?>&callback=initMap`;
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        }

        // Initialize when page loads
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadGoogleMaps);
        } else {
            loadGoogleMaps();
        }

        // Cleanup interval on page unload
        window.addEventListener('beforeunload', function() {
            if (updateInterval) {
                clearInterval(updateInterval);
            }
        });
    </script>
</body>
</html>