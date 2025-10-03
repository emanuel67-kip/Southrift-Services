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
// Optional: you can enforce passenger login here if desired.
// if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
if ($booking_id <= 0 && $token === '') {
    http_response_code(400);
    echo "Missing booking_id or token";
    exit;
}

// First, try to get the driver's shared Google Maps link
require_once __DIR__ . '/db.php';

// Function to check if URL is a Google Maps link
function isGoogleMapsUrl($url) {
    $parsed = parse_url($url);
    if (!isset($parsed['host'])) return false;
    
    $host = strtolower($parsed['host']);
    return (strpos($host, 'google.com') !== false || 
            strpos($host, 'google.co.ke') !== false ||
            strpos($host, 'maps.app.goo.gl') !== false ||
            strpos($host, 'goo.gl') !== false) && 
           (strpos($url, '/maps/') !== false || 
            strpos($url, 'maps.google.') !== false);
}

// Try to get the driver's shared link from the database
$googleMapsLink = null;
if ($token) {
    // If we have a token, try to get the location with the shared link
    // First check if google_maps_link column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM driver_locations LIKE 'google_maps_link'");
    $hasGoogleMapsLinkColumn = ($checkColumn->num_rows > 0);
    
    if ($hasGoogleMapsLinkColumn) {
        $stmt = $conn->prepare("SELECT google_maps_link FROM driver_locations WHERE share_token = ? AND status = 'active' LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $token);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (!empty($row['google_maps_link']) && isGoogleMapsUrl($row['google_maps_link'])) {
                    $googleMapsLink = $row['google_maps_link'];
                }
            }
        }
    }
} elseif ($booking_id > 0) {
    // If we have a booking ID, try to get the assigned vehicle and then the driver's location
    // First check if google_maps_link column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM driver_locations LIKE 'google_maps_link'");
    $hasGoogleMapsLinkColumn = ($checkColumn->num_rows > 0);
    
    if ($hasGoogleMapsLinkColumn) {
        $query = "
            SELECT dl.google_maps_link 
            FROM driver_locations dl
            JOIN drivers d ON dl.driver_id = d.id
            JOIN vehicles v ON d.driver_phone = v.driver_phone
            JOIN bookings b ON v.number_plate = b.assigned_vehicle
            WHERE b.booking_id = ? AND dl.status = 'active'
            LIMIT 1
        ";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("SQL Prepare Error: " . $conn->error . " | Query: " . $query);
            die("Database error in view_driver_location.php on line " . __LINE__);
        }
        
        if ($stmt) {
            $stmt->bind_param('i', $booking_id);
            if (!$stmt->execute()) {
                error_log("SQL Execute Error: " . $stmt->error . " | Query: " . $query);
                die("Database execution error in view_driver_location.php on line " . __LINE__);
            }
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (!empty($row['google_maps_link']) && isGoogleMapsUrl($row['google_maps_link'])) {
                    $googleMapsLink = $row['google_maps_link'];
                }
            }
        }
    }
}

// If we have a valid Google Maps link, redirect to it
if ($googleMapsLink) {
    header('Location: ' . $googleMapsLink);
    exit;
}

// If no Google Maps link is available, fall back to the map view
// Check for API key in multiple locations
$MAPS_KEY = null;
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    $MAPS_KEY = $env['GOOGLE_MAPS_API_KEY'] ?? null;
}
if (!$MAPS_KEY) {
    $MAPS_KEY = getenv('GOOGLE_MAPS_API_KEY');
}
if (!$MAPS_KEY) {
    // Demo key for testing - replace with your actual key
    $MAPS_KEY = 'AIzaSyBFw0Qbyq9zTFTd-tUY6dpoWCjVOlY5g9U';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Track Driver – SouthRift</title>
    <style>
        body { margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, "Helvetica Neue", Arial; background:#f6f7fb; }
        header { background:#6A0DAD; color:#fff; padding:14px 18px; }
        header h1 { font-size:18px; margin:0; }
        #map { width:100%; height: calc(100vh - 120px); }
        .panel { padding:12px 18px; background:#fff; border-top:1px solid #eee; display:flex; gap:12px; align-items:center; }
        .status { font-size:14px; color:#555; }
        .dot { width:10px; height:10px; background:#4CAF50; border-radius:50%; display:inline-block; margin-right:8px; animation: blink 1.5s infinite; }
        @keyframes blink { 0%{opacity:.2} 50%{opacity:1} 100%{opacity:.2} }
        .error { color:#c0392b; }
        .muted { color:#888; }
        .btn { appearance:none; border:0; background:#6A0DAD; color:#fff; padding:8px 12px; border-radius:6px; cursor:pointer; }
    </style>
    <script>
        // Load Google Maps API dynamically with proper async loading
        function loadGoogleMaps() {
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($MAPS_KEY) ?>&loading=async&callback=initMap`;
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        }
        
        // Load maps when page is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadGoogleMaps);
        } else {
            loadGoogleMaps();
        }
    </script>
</head>
<body>
<header>
    <h1>Live Driver Location</h1>
</header>
<div id="map"></div>
<div class="panel">
    <div id="status" class="status">
        <span class="dot"></span>Driver has not shared their location yet. Please ask them to share their location.
    </div>
    <button class="btn" onclick="window.location.reload()">Refresh</button>
</div>

<script>
let map, marker, accuracyCircle;
const bookingId = <?= json_encode($booking_id) ?>;
const shareToken = <?= json_encode($token) ?>;

function initMap() {
    // Set a default location (Nairobi)
    const defaultLocation = { lat: -1.286389, lng: 36.817223 };
    
    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 10,
        center: defaultLocation,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: true
    });

    // Try to use AdvancedMarkerElement, fallback to Marker if not available
    try {
        if (google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
            marker = new google.maps.marker.AdvancedMarkerElement({
                position: defaultLocation,
                map: map,
                title: 'Driver Location'
            });
        } else {
            // Fallback to traditional Marker
            marker = new google.maps.Marker({
                position: defaultLocation,
                map: map,
                icon: {
                    url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png'
                },
                title: 'Driver'
            });
        }
    } catch (error) {
        console.warn('Advanced marker not available, using standard marker:', error);
        // Fallback to traditional Marker
        marker = new google.maps.Marker({
            position: defaultLocation,
            map: map,
            icon: {
                url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png'
            },
            title: 'Driver'
        });
    }

    // Try to get the current location
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                // Center map on user's location
                map.setCenter(userLocation);
            },
            (error) => {
                console.error('Error getting user location:', error);
            }
        );
    }

    // Try to fetch driver's location
    fetchLocation();
}

function setStatus(msg, isError = false) {
    const el = document.getElementById('status');
    el.innerHTML = (isError ? '' : '<span class="dot"></span>') + msg;
    el.className = 'status' + (isError ? ' error' : '');
}

function fetchLocation() {
    const url = shareToken && shareToken.length > 0
        ? `get_driver_location_by_token.php?token=${encodeURIComponent(shareToken)}`
        : `get_driver_location.php?booking_id=${encodeURIComponent(bookingId)}`;
        
    fetch(url, { credentials: 'same-origin' })
        .then(async (res) => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) {
                const err = (data && (data.error || (data.debug && data.debug))) || 'Unable to fetch location';
                setStatus(err, true);
                return;
            }
            
            if (data.location && data.location.lat && data.location.lng) {
                const pos = {
                    lat: parseFloat(data.location.lat),
                    lng: parseFloat(data.location.lng)
                };
                
                // Update marker position - handle both AdvancedMarkerElement and Marker
                if (marker.position !== undefined) {
                    // AdvancedMarkerElement
                    marker.position = pos;
                } else if (marker.setPosition) {
                    // Traditional Marker
                    marker.setPosition(pos);
                }
                
                // Center map on marker
                map.setCenter(pos);
                map.setZoom(14);
                
                // Update status
                setStatus('Driver location updated');
                
                // If there's a Google Maps link, show a button to open it
                if (data.location.google_maps_link) {
                    const openInMapsBtn = document.createElement('button');
                    openInMapsBtn.className = 'btn';
                    openInMapsBtn.style.marginLeft = '10px';
                    openInMapsBtn.textContent = 'Open in Google Maps';
                    openInMapsBtn.onclick = () => window.open(data.location.google_maps_link, '_blank');
                    
                    const panel = document.querySelector('.panel');
                    if (!document.querySelector('.btn-open-maps')) {
                        openInMapsBtn.classList.add('btn-open-maps');
                        panel.appendChild(openInMapsBtn);
                    }
                }
            } else {
                setStatus('Driver location not available', true);
            }
        })
        .catch(err => {
            console.error('Error:', err);
            setStatus('Error fetching location', true);
        });
}

function refreshNow() {
    setStatus('Refreshing...');
    fetchLocation();
}
</script>
</body>
</html>
