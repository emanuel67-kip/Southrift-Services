<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/login_errors.log');

// Enhanced cache control
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Start session with enhanced security
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_only_cookies', 1);
    
    // Set the same session name as admin system
    session_name('southrift_admin');
    
    // Set enhanced session cookie parameters
    $lifetime = 60 * 60; // 1 hour
    $path = '/';
    $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $httponly = true;
    $samesite = 'Lax'; // Helps with back button issues
    
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => $path,
        'domain' => $domain,
        'secure' => $secure,
        'httponly' => $httponly,
        'samesite' => $samesite
    ]);
    
    // Start the session
    session_start();
} else {
    session_start();
}

// Set JSON response header
header('Content-Type: application/json');

// Include database connection
require_once 'db.php';

// Initialize response
$response = ['success' => false, 'message' => ''];

try {
    // Check if request is POST for login processing
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get and validate input
        $identifier = trim($_POST['username'] ?? '');
        $password_input = trim($_POST['password'] ?? '');
        
        if (empty($identifier) || empty($password_input)) {
            throw new Exception('Username and password are required');
        }
    
    // First, check the users table for passenger/admin login
    $sql = "SELECT * FROM users WHERE (name = ? OR email = ?) AND role IN ('passenger', 'admin')";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password_input, $user['password'])) {
            // Clear any existing session data
            $_SESSION = [];
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['last_activity'] = time();
            
            // Set expiry for passenger sessions (30 minutes) and auto-extend on activity
            if ($user['role'] === 'passenger') {
                $_SESSION['expires_at'] = time() + 1800;
            }
            
            // Update last login (if column exists)
            try {
                $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update->bind_param("i", $user['id']);
                $update->execute();
            } catch (Exception $e) {
                // Log error but don't fail login if last_login update fails
                error_log('Failed to update last_login for user ' . $user['id'] . ': ' . $e->getMessage());
            }
            
            // Set response
            $response['success'] = true;
            $response['message'] = 'Login successful';
            
            // Set proper redirect for admin vs passenger
            if ($user['role'] === 'admin') {
                $response['redirect'] = './Admin/index.php';
            } else {
                $response['redirect'] = './index.html';
            }
            
            echo json_encode($response);
            exit;
        } else {
            throw new Exception('Invalid username or password');
        }
    }
    
    // If not a passenger/admin, check drivers table
    $stmt = $conn->prepare("SELECT * FROM drivers WHERE driver_phone = ? AND number_plate = ?");
    $number_plate = strtoupper($password_input);
    $stmt->bind_param("ss", $identifier, $number_plate);
    $stmt->execute();
    $driver_result = $stmt->get_result();
    
    if ($driver = $driver_result->fetch_assoc()) {
        // Clear any existing session data
        $_SESSION = [];
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set driver session variables
        $_SESSION['user_id'] = $driver['id'];
        $_SESSION['username'] = $driver['name'];
        $_SESSION['name'] = $driver['name'];
        $_SESSION['phone'] = $driver['driver_phone'];
        $_SESSION['role'] = 'driver';
        $_SESSION['email'] = $driver['email'] ?? '';
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['last_activity'] = time();
        
        // Update last login for driver (if column exists)
        try {
            $update = $conn->prepare("UPDATE drivers SET last_login = NOW() WHERE id = ?");
            $update->bind_param("i", $driver['id']);
            $update->execute();
        } catch (Exception $e) {
            // Log error but don't fail login if last_login update fails
            error_log('Failed to update last_login for driver ' . $driver['id'] . ': ' . $e->getMessage());
        }
        
        $response['success'] = true;
        $response['message'] = 'Driver login successful';
        $response['redirect'] = 'Driver/index.php';
        
        echo json_encode($response);
        exit;
    }
    
    // If we reach here, no valid user was found
    throw new Exception('Invalid username or password');
    
    } else {
        // Handle GET requests - redirect to login form
        header('Location: login.html');
        exit;
    }
    
} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    $response['message'] = $e->getMessage();
    echo json_encode($response);
    exit;
} finally {
    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }
}
?>