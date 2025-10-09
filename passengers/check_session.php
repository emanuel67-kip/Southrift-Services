<?php
// Endpoint to check session status
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Configure session to match the login system
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters to match login.php
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    ini_set('session.use_only_cookies', 1);
    
    // Set the same session name as login system
    session_name('southrift_admin');
    
    // Set enhanced session cookie parameters
    $lifetime = 60 * 60; // 1 hour for passengers
    $path = '/';
    $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $httponly = true;
    $samesite = 'Lax';
    
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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'logged_in' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Check if session has expired for passengers
if (isset($_SESSION['role']) && $_SESSION['role'] === 'passenger') {
    // Check if expires_at is set and has passed
    if (isset($_SESSION['expires_at']) && time() > $_SESSION['expires_at']) {
        // Session expired, destroy session
        session_unset();
        session_destroy();
        echo json_encode([
            'logged_in' => false,
            'message' => 'Session expired'
        ]);
        exit;
    }
    
    // Update last activity and extend session
    $_SESSION['last_activity'] = time();
    $_SESSION['expires_at'] = time() + 1800; // Extend by 30 minutes
}

echo json_encode([
    'logged_in' => true,
    'role' => $_SESSION['role'] ?? 'unknown',
    'user_id' => $_SESSION['user_id'] ?? null
]);
?>