<?php
// Configure session to match the driver system
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    ini_set('session.use_only_cookies', 1);
    
    session_name('southrift_admin');
    
    $lifetime = 2592000; // 30 days
    $path = '/';
    $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $httponly = true;
    
    session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
    session_start();
}

header('Content-Type: application/json');

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo json_encode([
    'session_id' => session_id(),
    'csrf_token' => $_SESSION['csrf_token'] ?? 'NOT_SET',
    'session_data' => $_SESSION,
    'post_data' => $_POST,
    'cookies' => $_COOKIE,
    'user_authenticated' => isset($_SESSION['phone']) || isset($_SESSION['user_id']),
    'role' => $_SESSION['role'] ?? 'NOT_SET'
]);
?>