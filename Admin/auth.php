<?php
// Ensure session is started with secure settings
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_only_cookies', 1);
    
    // Set a custom session name
    session_name('southrift_admin');
    
    // Set session cookie parameters
    $lifetime = 60 * 60; // 1 hour
    $path = '/';
    $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $httponly = true;
    
    session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
    
    // Start the session
    if (!session_start()) {
        error_log('Failed to start session');
        header('HTTP/1.1 500 Internal Server Error');
        exit('Unable to start session');
    }
}

// Regenerate session ID to prevent session fixation
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) { // Regenerate every 30 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Check if user is logged in and has admin role
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Clear any existing session data
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header('Location: ../login.html?error=admin_required');
    exit();
}

// Verify session IP and user agent to prevent session hijacking
if (isset($_SESSION['ip']) && $_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']) {
    // IP address changed, destroy session
    session_unset();
    session_destroy();
    header('Location: ../login.html?error=session_error');
    exit();
}

if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    // User agent changed, destroy session
    session_unset();
    session_destroy();
    header('Location: ../login.html?error=session_error');
    exit();
}

// If we got here, the user is properly authenticated as an admin
