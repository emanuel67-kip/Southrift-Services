<?php
// Start session with same configuration as main system
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

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo "<h2>🔒 CSRF Token Debug Information</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";

echo "<h3>Session Information:</h3>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Name:</strong> " . session_name() . "</p>";
echo "<p><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE') . "</p>";

echo "<h3>CSRF Token:</h3>";
echo "<p><strong>Token exists:</strong> " . (isset($_SESSION['csrf_token']) ? 'YES' : 'NO') . "</p>";
if (isset($_SESSION['csrf_token'])) {
    echo "<p><strong>Token value:</strong> " . $_SESSION['csrf_token'] . "</p>";
    echo "<p><strong>Token length:</strong> " . strlen($_SESSION['csrf_token']) . " characters</p>";
}

echo "<h3>Driver Session:</h3>";
echo "<p><strong>Driver logged in:</strong> " . (isset($_SESSION['phone']) ? 'YES' : 'NO') . "</p>";
if (isset($_SESSION['phone'])) {
    echo "<p><strong>Driver phone:</strong> " . htmlspecialchars($_SESSION['phone']) . "</p>";
}
if (isset($_SESSION['name'])) {
    echo "<p><strong>Driver name:</strong> " . htmlspecialchars($_SESSION['name']) . "</p>";
}

echo "</div>";

echo "<h3>🧪 Test CSRF Token</h3>";
echo "<form method='POST' style='background: #e7f3ff; padding: 20px; border-radius: 8px;'>";
echo "<p>This form will test if the CSRF token validation is working:</p>";
echo "<input type='hidden' name='csrf_token' value='" . ($_SESSION['csrf_token'] ?? '') . "'>";
echo "<input type='hidden' name='test_action' value='csrf_test'>";
echo "<button type='submit' style='background: #6A0DAD; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Test CSRF Token</button>";
echo "</form>";

if (isset($_POST['test_action']) && $_POST['test_action'] === 'csrf_test') {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
    echo "<h4>✅ CSRF Test Result:</h4>";
    
    $posted_token = $_POST['csrf_token'] ?? null;
    $session_token = $_SESSION['csrf_token'] ?? null;
    
    echo "<p><strong>Posted token:</strong> " . ($posted_token ?: 'NULL') . "</p>";
    echo "<p><strong>Session token:</strong> " . ($session_token ?: 'NULL') . "</p>";
    echo "<p><strong>Tokens match:</strong> " . (($posted_token && $session_token && $posted_token === $session_token) ? '✅ YES' : '❌ NO') . "</p>";
    
    if ($posted_token && $session_token && $posted_token === $session_token) {
        echo "<p style='color: green; font-weight: bold;'>🎉 CSRF token validation is working correctly!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ CSRF token validation failed!</p>";
    }
    echo "</div>";
}

echo "<h3>🔧 Actions:</h3>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='index.php' style='background: #6A0DAD; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🏠 Back to Dashboard</a>";
echo "<a href='debug_csrf_token.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔄 Refresh This Page</a>";
echo "</div>";

// Show recent error log entries
echo "<h3>📋 Recent Error Log (Last 10 lines):</h3>";
$log_file = ini_get('error_log');
if ($log_file && file_exists($log_file)) {
    $log_lines = file($log_file);
    $recent_lines = array_slice($log_lines, -10);
    echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 300px; overflow-y: auto; font-size: 12px;'>";
    echo htmlspecialchars(implode('', $recent_lines));
    echo "</pre>";
} else {
    echo "<p>Error log file not found or not configured.</p>";
}
?>