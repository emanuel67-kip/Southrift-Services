<?php
session_start();
require __DIR__ . '/auth.php';
require dirname(__DIR__) . '/db.php';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];
$driver_phone = $_SESSION['phone'] ?? '';
$driver_name = $_SESSION['name'] ?? 'Driver';

// Check if we're starting or stopping sharing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'start') {
        // Start sharing - redirect to Google Maps
        $share_url = "https://www.google.com/maps?q=0,0&z=15&t=k";
        
        // If we have a current location, use it
        if (!empty($driver_phone)) {
            $stmt = $conn->prepare("
                SELECT latitude, longitude 
                FROM driver_locations 
                WHERE driver_id = (SELECT id FROM drivers WHERE phone = ?)
                ORDER BY last_updated DESC LIMIT 1
            ") or die($conn->error);
            $stmt->bind_param("s", $driver_phone);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($location = $result->fetch_assoc()) {
                    $share_url = "https://www.google.com/maps?q={$location['latitude']},{$location['longitude']}&z=15&t=k";
                }
            }
        }
        
        // Store sharing status in session
        $_SESSION['is_sharing_location'] = true;
        
        // Redirect to Google Maps
        header("Location: $share_url");
        exit();
    } elseif ($action === 'stop') {
        // Stop sharing - update status in database and session
        if (!empty($driver_phone)) {
            $stmt = $conn->prepare("
                DELETE FROM driver_locations 
                WHERE driver_id = (SELECT id FROM drivers WHERE phone = ?)
            ") or die($conn->error);
            $stmt->bind_param("s", $driver_phone);
            $stmt->execute();
        }
        
        // Clear sharing status
        $_SESSION['is_sharing_location'] = false;
        
        // Redirect back to dashboard
        header("Location: index.php");
        exit();
    }
}

// If it's a GET request, redirect to index.php
header("Location: index.php");
exit();
