<?php
// Use the same session configuration as the admin system
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_only_cookies', 1);
    
    // Set the same session name as admin system
    session_name('southrift_admin');
    
    // Set session cookie parameters
    $lifetime = 60 * 60; // 1 hour
    $path = '/';
    $domain = $_SERVER['HTTP_HOST'];
    $secure = isset($_SERVER['HTTPS']);
    $httponly = true;
    
    session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
    
    // Start the session
    session_start();
}

echo "<h2>Session Debug Information</h2>\n";
echo "<h3>Session Status</h3>\n";
echo "<p><strong>Session Status:</strong> " . session_status() . "</p>\n";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>\n";
echo "<p><strong>Session Name:</strong> " . session_name() . "</p>\n";

echo "<h3>Session Variables</h3>\n";
if (empty($_SESSION)) {
    echo "<p style='color: orange;'>⚠️ No session variables found!</p>\n";
} else {
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr style='background: #f0f0f0;'><th>Key</th><th>Value</th></tr>\n";
    foreach ($_SESSION as $key => $value) {
        $displayValue = is_array($value) ? json_encode($value) : htmlspecialchars((string)$value);
        echo "<tr><td><strong>$key</strong></td><td>$displayValue</td></tr>\n";
    }
    echo "</table>\n";
}

echo "<h3>Authentication Check</h3>\n";
if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        echo "<p style='color: green;'>✅ Valid admin session found!</p>\n";
        echo "<p><strong>Username:</strong> {$_SESSION['username']}</p>\n";
        echo "<p><strong>Role:</strong> {$_SESSION['role']}</p>\n";
    } else {
        echo "<p style='color: orange;'>⚠️ User logged in but not as admin</p>\n";
        echo "<p><strong>Username:</strong> {$_SESSION['username']}</p>\n";
        echo "<p><strong>Role:</strong> {$_SESSION['role']}</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ No valid authentication session</p>\n";
    if (!isset($_SESSION['username'])) {
        echo "<p>Missing: username</p>\n";
    }
    if (!isset($_SESSION['role'])) {
        echo "<p>Missing: role</p>\n";
    }
}

echo "<h3>Test Links</h3>\n";
echo "<p><a href='login.html'>Go to Login</a></p>\n";
echo "<p><a href='Admin/index.php'>Go to Admin Dashboard</a></p>\n";
?>