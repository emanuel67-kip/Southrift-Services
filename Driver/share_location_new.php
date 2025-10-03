<?php
session_start();
require __DIR__ . '/auth.php';
require dirname(__DIR__) . '/db.php';

// Debug: Log session data
error_log("Session data: " . print_r($_SESSION, true));

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];
$driver_phone = $_SESSION['phone'] ?? '';
$driver_name = $_SESSION['name'] ?? 'Driver';
$message = '';
$message_type = '';
$location_link = '';

// Debug: Log driver phone
error_log("Driver phone from session: " . $driver_phone);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['share_location'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }

    $location_link = trim($_POST['location_link']);
    
    // Validate Google Maps link
    if (empty($location_link) || !filter_var($location_link, FILTER_VALIDATE_URL)) {
        $error = "Please enter a valid URL";
    } else {
        // Check if it's a Google Maps link
        $isGoogleMaps = (strpos($location_link, 'maps.google.') !== false || 
                        strpos($location_link, 'maps.app.goo.gl') !== false ||
                        strpos($location_link, 'goo.gl/maps') !== false);
        
        if (!$isGoogleMaps) {
            $error = "Please enter a valid Google Maps link";
        } else {
            // Debug: Log all session variables
            error_log("Session variables: " . print_r($_SESSION, true));
            
            // Get driver ID from session or database
            $driver_id = null;
            
            // First, try to get driver_id from session
            if (isset($_SESSION['driver_id'])) {
                $driver_id = (int)$_SESSION['driver_id'];
                error_log("Found driver_id in session: " . $driver_id);
                
                // Verify the driver exists
                $stmt = $conn->prepare("SELECT id FROM drivers WHERE id = ? LIMIT 1");
                $stmt->bind_param('i', $driver_id);
                $stmt->execute();
                if (!$stmt->get_result()->fetch_assoc()) {
                    error_log("Driver ID $driver_id not found in database");
                    $driver_id = null; // Reset if not found
                }
            }
            
            // If still no driver_id, try to find by email
            if (!$driver_id && !empty($_SESSION['email'])) {
                $email = trim($_SESSION['email']);
                error_log("Looking up driver by email: " . $email);
                
                $stmt = $conn->prepare("SELECT id FROM drivers WHERE email = ? LIMIT 1");
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($driver = $result->fetch_assoc()) {
                    $driver_id = (int)$driver['id'];
                    $_SESSION['driver_id'] = $driver_id;
                    error_log("Found driver by email with ID: " . $driver_id);
                } else {
                    error_log("No driver found with email: " . $email);
                }
            }
            
            // If still no driver_id, try to find by phone
            if (!$driver_id && !empty($_SESSION['phone'])) {
                $phone = trim($_SESSION['phone']);
                error_log("Looking up driver by phone: " . $phone);
                
                $stmt = $conn->prepare("SELECT id FROM drivers WHERE phone = ? LIMIT 1");
                $stmt->bind_param('s', $phone);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($driver = $result->fetch_assoc()) {
                    $driver_id = (int)$driver['id'];
                    $_SESSION['driver_id'] = $driver_id;
                    error_log("Found driver by phone with ID: " . $driver_id);
                } else {
                    error_log("No driver found with phone: " . $phone);
                }
            }
            
            if (!$driver_id) {
                $error_msg = "Driver not found. Please make sure you are logged in as a driver. ";
                $error_msg .= "Session data: " . print_r($_SESSION, true);
                error_log($error_msg);
                $error = "Driver not found. Please make sure you are logged in as a driver";
            } else {
                // Generate a unique token for this sharing session
                $token = bin2hex(random_bytes(16));
                
                // Check if driver already has a location entry
                $check = $conn->prepare("SELECT id FROM driver_locations WHERE driver_id = ?");
                $check->bind_param('i', $driver_id);
                $check->execute();
                $result = $check->get_result();
                
                if ($result->num_rows > 0) {
                    // Update existing location
                    $stmt = $conn->prepare("UPDATE driver_locations SET status = 'active', share_token = ?, google_maps_link = ?, last_updated = NOW() WHERE driver_id = ?");
                    $stmt->bind_param('ssi', $token, $location_link, $driver_id);
                } else {
                    // Insert new location
                    $stmt = $conn->prepare("INSERT INTO driver_locations (driver_id, status, share_token, google_maps_link, last_updated) VALUES (?, 'active', ?, ?, NOW())");
                    $stmt->bind_param('iss', $driver_id, $token, $location_link);
                }
                
                if ($stmt->execute()) {
                    $success = true;
                    $shareable_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . 
                                    $_SERVER['HTTP_HOST'] . 
                                    dirname($_SERVER['PHP_SELF']) . 
                                    "/../track_driver.php?token=" . $token;
                } else {
                    $error = "Error saving location: " . $conn->error;
                }
            }
        }
    }
}

// Check sharing status from session
$is_sharing = false;
if (isset($success) && $success) {
    $is_sharing = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Live Location - SouthRide</title>
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
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }

        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-top: 20px;
        }

        h1 {
            color: var(--purple);
            margin-bottom: 20px;
            text-align: center;
        }

        .map-container {
            width: 100%;
            height: 500px;
            margin-bottom: 20px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        #map {
            width: 100%;
            height: 100%;
            border: none;
            position: absolute;
            top: 0;
            left: 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus {
            border-color: var(--purple);
            outline: none;
            box-shadow: 0 0 0 3px rgba(106, 13, 173, 0.1);
        }

        .btn {
            display: inline-block;
            background: var(--purple);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            transition: background 0.3s;
            width: 100%;
            margin-top: 10px;
        }

        .btn-stop {
            background: #e74c3c;
        }

        .btn-stop:hover {
            background: #c0392b;
        }

        .btn i {
            margin-right: 8px;
        }

        .status {
            margin: 20px 0;
            padding: 15px;
            border-radius: 6px;
            background: #f8f9fa;
            text-align: center;
        }

        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        .message {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        .error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--purple);
            text-decoration: none;
            font-weight: 600;
        }

        .back-link i {
            margin-right: 5px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .instructions {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
        }

        .instructions h3 {
            margin-top: 0;
            color: var(--purple);
            font-size: 16px;
        }

        .instructions ol {
            padding-left: 20px;
            margin: 10px 0 0;
        }

        .instructions li {
            margin-bottom: 8px;
        }
        
        .location-sharing {
            margin: 30px 0;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .location-actions {
            margin-top: 20px;
        }
        
        .status-message {
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #6c757d;
        }
        
        .status-message i {
            margin-right: 10px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #c3e6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffeeba;
        }
        
        .fa-spin {
            animation: fa-spin 1s infinite linear;
        }
        
        @keyframes fa-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <div class="container">
        <h1><i class="fas fa-location-dot"></i> Share Live Location</h1>
        
        <?php if (isset($error)): ?>
            <div class="message error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php elseif (isset($success)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> Your live location is now being shared with passengers.
            </div>
        <?php endif; ?>

        <?php if ($is_sharing): ?>
            <div class="status status-active">
                <i class="fas fa-check-circle"></i> You are currently sharing your live location.
            </div>
        <?php endif; ?>

        <div class="map-container">
            <?php if ($is_sharing && !empty($location_link)): ?>
                <iframe 
                    id="map"
                    src="<?= htmlspecialchars($location_link) ?>"
                    allowfullscreen="" 
                    loading="lazy"
                    style="border:0;">
                </iframe>
            <?php else: ?>
                <iframe 
                    id="map"
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3988.808156423717!2d36.82115991475393!3d-1.292365535980471!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x182f10d5c9e0c1a5%3A0x7f1d9d9c9c9c9c9c!2sNairobi!5e0!3m2!1sen!2ske!4v1620000000000!5m2!1sen!2ske"
                    allowfullscreen="" 
                    loading="lazy"
                    style="border:0;">
                </iframe>
            <?php endif; ?>
        </div>

        <div class="location-sharing">
            <h2>Share Your Live Location</h2>
            
            <div id="locationStatus" class="status-message">
                <p>Click the button below to share your current location.</p>
            </div>
            
            <div class="location-actions">
                <button id="shareLiveLocation" class="btn btn-primary">
                    <i class="fas fa-location-dot"></i> Share Live Location
                </button>
                
                <div id="mapContainer" style="display: none; height: 400px; margin: 20px 0; border-radius: 8px; overflow: hidden;">
                    <div id="map" style="height: 100%;"></div>
                </div>
                
                <form id="locationForm" method="POST" style="display: none;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="location_link" id="locationLink">
                    <button type="submit" name="share_location" class="btn btn-success" id="confirmShareBtn">
                        <i class="fas fa-share"></i> Confirm & Share This Location
                    </button>
                </form>
            </div>
        </div>

        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
            <div class="form-group">
                <label for="location_link">Google Maps Location Link</label>
                <input 
                    type="text" 
                    id="location_link" 
                    name="location_link" 
                    placeholder="Paste your Google Maps location link here"
                    <?= $is_sharing ? 'readonly' : '' ?>
                    required>
            </div>

            <?php if ($is_sharing): ?>
                <input type="hidden" name="action" value="stop">
                <button type="submit" class="btn btn-stop">
                    <i class="fas fa-stop"></i> Stop Sharing Location
                </button>
            <?php else: ?>
                <input type="hidden" name="share_location" value="true">
                <button type="submit" name="share_submit" class="btn btn-primary">
                    <i class="fas fa-share-alt"></i> Share Location
                </button>
            <?php endif; ?>
        </form>
    </div>

    <script>
        // Auto-update the map iframe when a location link is pasted
        document.getElementById('location_link').addEventListener('input', function(e) {
            const link = e.target.value.trim();
            if (link.includes('maps.google.com') || link.includes('maps.app.goo.gl')) {
                document.getElementById('map').src = link;
            }
        });

        // If sharing is active, disable the input field
        <?php if ($is_sharing && !empty($location_link)): ?>
            document.getElementById('location_link').value = '<?= htmlspecialchars($location_link) ?>';
            document.getElementById('location_link').readOnly = true;
        <?php endif; ?>
    </script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const shareBtn = document.getElementById('shareLiveLocation');
            const locationStatus = document.getElementById('locationStatus');
            const mapContainer = document.getElementById('mapContainer');
            const locationForm = document.getElementById('locationForm');
            const locationLink = document.getElementById('locationLink');
            let map;
            let marker;
            let watchId;
            
            // Initialize Google Maps
            function initMap(position) {
                const { latitude, longitude } = position.coords;
                const location = { lat: latitude, lng: longitude };
                
                // Create Google Maps link
                const mapsUrl = `https://www.google.com/maps?q=${latitude},${longitude}`;
                locationLink.value = mapsUrl;
                
                // Show map container
                mapContainer.style.display = 'block';
                
                // Initialize map
                map = new google.maps.Map(document.getElementById('map'), {
                    center: location,
                    zoom: 15,
                    mapTypeId: 'roadmap',
                    styles: [
                        {
                            featureType: 'poi',
                            elementType: 'labels',
                            stylers: [{ visibility: 'off' }]
                        }
                    ]
                });
                
                // Add marker with fallback to handle both old and new APIs
                try {
                    // Try to use AdvancedMarkerElement first
                    if (google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
                        marker = new google.maps.marker.AdvancedMarkerElement({
                            position: location,
                            map: map,
                            title: 'Your Location'
                        });
                    } else {
                        // Fallback to traditional Marker
                        marker = new google.maps.Marker({
                            position: location,
                            map: map,
                            title: 'Your Location',
                            icon: {
                                url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png',
                                scaledSize: new google.maps.Size(40, 40)
                            },
                            animation: google.maps.Animation.DROP
                        });
                    }
                } catch (error) {
                    console.warn('Advanced marker not available, using standard marker:', error);
                    // Fallback to traditional Marker
                    marker = new google.maps.Marker({
                        position: location,
                        map: map,
                        title: 'Your Location',
                        icon: {
                            url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png',
                            scaledSize: new google.maps.Size(40, 40)
                        },
                        animation: google.maps.Animation.DROP
                    });
                }
                
                // Show form
                locationForm.style.display = 'block';
                
                // Scroll to map
                mapContainer.scrollIntoView({ behavior: 'smooth' });
            }
            
            // Handle location error
            function handleLocationError(error) {
                let errorMessage = 'Error getting your location. ';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage += 'Please allow location access to share your location.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage += 'Location information is unavailable.';
                        break;
                    case error.TIMEOUT:
                        errorMessage += 'The request to get your location timed out.';
                        break;
                    default:
                        errorMessage += 'An unknown error occurred.';
                }
                
                locationStatus.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> ${errorMessage}
                    </div>
                    <p>You can still share your location manually by entering a Google Maps URL below.</p>
                `;
            }
            
            // Share location button click handler
            shareBtn.addEventListener('click', function() {
                // Update status
                locationStatus.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Getting your location...</p>';
                
                if (navigator.geolocation) {
                    // Get current position
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            initMap(position);
                            
                            // Start watching position for updates
                            watchId = navigator.geolocation.watchPosition(
                                function(updatedPosition) {
                                    const newPos = {
                                        lat: updatedPosition.coords.latitude,
                                        lng: updatedPosition.coords.longitude
                                    };
                                    
                                    // Update marker position - handle both AdvancedMarkerElement and Marker
                                    if (marker) {
                                        if (marker.position !== undefined) {
                                            // AdvancedMarkerElement
                                            marker.position = newPos;
                                        } else if (marker.setPosition) {
                                            // Traditional Marker
                                            marker.setPosition(newPos);
                                        }
                                    }
                                    
                                    // Update map center
                                    if (map) {
                                        map.panTo(newPos);
                                    }
                                    
                                    // Update the location link
                                    locationLink.value = `https://www.google.com/maps?q=${newPos.lat},${newPos.lng}`;
                                },
                                function(error) {
                                    console.error('Error watching position:', error);
                                },
                                {
                                    enableHighAccuracy: true,
                                    maximumAge: 10000,
                                    timeout: 5000
                                }
                            );
                            
                            // Update status
                            locationStatus.innerHTML = `
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> Location found! Confirm below to share.
                                </div>
                                <p>Your location will update automatically as you move.</p>
                            `;
                        },
                        handleLocationError,
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 0
                        }
                    );
                } else {
                    locationStatus.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Geolocation is not supported by your browser.
                        </div>
                        <p>Please enter your location manually below.</p>
                    `;
                }
            });
            
            // Clean up geolocation watcher when leaving the page
            window.addEventListener('beforeunload', function() {
                if (watchId && navigator.geolocation) {
                    navigator.geolocation.clearWatch(watchId);
                }
            });
        });
    </script>
    
    <!-- Load Google Maps API with proper async loading -->
    <script>
        // Load Google Maps API dynamically with proper async loading
        function loadGoogleMaps() {
            const script = document.createElement('script');
            
            // Try to get API key from multiple sources
            let apiKey = 'AIzaSyBFw0Qbyq9zTFTd-tUY6dpoWCjVOlY5g9U'; // Demo key - replace with your actual key
            
            script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places&loading=async&callback=initializeGoogleMaps`;
            script.async = true;
            script.defer = true;
            
            // Add error handling for script loading
            script.onerror = function() {
                console.error('Failed to load Google Maps API script');
                console.log('Please check your API key and internet connection');
            };
            
            document.head.appendChild(script);
        }
        
        // Initialize Google Maps when loaded
        function initializeGoogleMaps() {
            console.log('Google Maps API loaded successfully');
            // Check if the API loaded properly
            if (typeof google === 'undefined' || !google.maps) {
                console.error('Google Maps API failed to load properly');
                return;
            }
            
            // Verify API key is working
            if (google.maps.version) {
                console.log('Google Maps API version:', google.maps.version);
            }
        }
        
        // Load maps when page is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadGoogleMaps);
        } else {
            loadGoogleMaps();
        }
    </script>
</body>
</html>
