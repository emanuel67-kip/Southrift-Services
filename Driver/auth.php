<?php
// Configure session to match the main system
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters to match login.php
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    ini_set('session.use_only_cookies', 1);
    
    // Set the same session name as admin system
    session_name('southrift_admin');
    
    // Set session cookie parameters for drivers (30 days)
    $lifetime = 2592000; // 30 days
    $path = '/';
    $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $httponly = true;
    
    session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
    
    // Start the session
    session_start();
}

// Check if user is logged in and is a driver
// Support both user_id and phone-based authentication for compatibility
$is_authenticated = false;
$auth_method = '';

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'driver') {
    $is_authenticated = true;
    $auth_method = 'user_id';
} elseif (isset($_SESSION['phone']) && $_SESSION['role'] === 'driver') {
    // Fallback: check if driver exists in database with this phone
    require_once dirname(__DIR__) . '/db.php';
    $stmt = $conn->prepare("SELECT id FROM drivers WHERE driver_phone = ? LIMIT 1");
    $stmt->bind_param("s", $_SESSION['phone']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $driver = $result->fetch_assoc();
        $_SESSION['user_id'] = $driver['id']; // Set user_id for consistency
        $is_authenticated = true;
        $auth_method = 'phone';
    }
}

if (!$is_authenticated) {
    // Store the current URL for redirecting back after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    // Set error message
    $_SESSION['error'] = 'Please log in as a driver to access this page.';
    
    // Log debug info
    error_log('Driver auth failed. Session: ' . print_r($_SESSION, true));
    
    // Redirect to login page
    header("Location: ../login.html");
    exit;
}

// Regenerate session ID periodically for security (without logging the user out)
if (empty($_SESSION['last_regen']) || time() - $_SESSION['last_regen'] > 3600) { // every hour
    session_regenerate_id(true);
    $_SESSION['last_regen'] = time();
}

// Check for CSRF token on POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        // Invalid CSRF token
        http_response_code(403);
        die('Invalid CSRF token');
    }
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
