<?php
// Force clear all session data and redirect to login
session_start();

// Log the current request for debugging
error_log("Logout requested. Method: " . $_SERVER['REQUEST_METHOD']);

// Clear all session data
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear any output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Force redirect to login page
header("Location: login.php?logged_out=1");
exit();
?>
