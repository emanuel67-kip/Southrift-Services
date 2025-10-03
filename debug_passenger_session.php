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

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Passenger Session</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>🔍 Passenger Session Debug</h2>
    
    <div class="status">
        <strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?>
    </div>

    <h3>Session Information</h3>
    <table>
        <tr><th>Session Status</th><td><?php echo session_status(); ?> (1=disabled, 2=active)</td></tr>
        <tr><th>Session ID</th><td><?php echo session_id(); ?></td></tr>
        <tr><th>Session Name</th><td><?php echo session_name(); ?></td></tr>
    </table>

    <h3>Session Variables</h3>
    <?php if (empty($_SESSION)): ?>
        <div class="error">❌ No session variables found! User is not logged in.</div>
    <?php else: ?>
        <table>
            <tr><th>Key</th><th>Value</th></tr>
            <?php foreach ($_SESSION as $key => $value): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($key); ?></strong></td>
                    <td><?php echo htmlspecialchars(is_array($value) ? json_encode($value) : (string)$value); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <h3>Authentication Status</h3>
    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role'])): ?>
        <?php if ($_SESSION['role'] === 'passenger'): ?>
            <div class="success">✅ Valid passenger session found!</div>
            <table>
                <tr><th>User ID</th><td><?php echo $_SESSION['user_id']; ?></td></tr>
                <tr><th>Username</th><td><?php echo $_SESSION['username'] ?? 'N/A'; ?></td></tr>
                <tr><th>Role</th><td><?php echo $_SESSION['role']; ?></td></tr>
                <tr><th>Email</th><td><?php echo $_SESSION['email'] ?? 'N/A'; ?></td></tr>
            </table>
        <?php else: ?>
            <div class="warning">⚠️ User logged in but role is: <?php echo $_SESSION['role']; ?> (not passenger)</div>
        <?php endif; ?>
    <?php else: ?>
        <div class="error">❌ User not authenticated properly</div>
        <p>Missing: 
            <?php if (!isset($_SESSION['user_id'])): ?>user_id <?php endif; ?>
            <?php if (!isset($_SESSION['role'])): ?>role <?php endif; ?>
        </p>
    <?php endif; ?>

    <h3>Test Profile.php Endpoint</h3>
    <button onclick="testProfileEndpoint()">Test profile.php</button>
    <div id="profileResult" style="margin-top: 10px;"></div>

    <h3>Quick Actions</h3>
    <p>
        <a href="login.html" style="background: #007cba; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;">Go to Login</a>
        <a href="profile.html" style="background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-left: 10px;">Go to Profile</a>
    </p>

    <script>
        async function testProfileEndpoint() {
            const resultDiv = document.getElementById('profileResult');
            resultDiv.innerHTML = '<p>Testing profile.php endpoint...</p>';
            
            try {
                const response = await fetch('profile.php');
                const data = await response.json();
                
                if (data.error) {
                    resultDiv.innerHTML = `<div class="error">❌ Error: ${data.error}</div>`;
                    if (data.debug) {
                        resultDiv.innerHTML += `<div class="warning">Debug: ${data.debug}</div>`;
                    }
                } else {
                    resultDiv.innerHTML = `<div class="success">✅ Profile data loaded successfully!</div>
                        <table>
                            <tr><th>Name</th><td>${data.user.name || 'N/A'}</td></tr>
                            <tr><th>Email</th><td>${data.user.email || 'N/A'}</td></tr>
                            <tr><th>Phone</th><td>${data.user.phone || 'N/A'}</td></tr>
                            <tr><th>Member Since</th><td>${data.user.created_at || 'N/A'}</td></tr>
                            <tr><th>Bookings</th><td>${data.bookings.length} found</td></tr>
                        </table>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="error">❌ Network Error: ${error.message}</div>`;
            }
        }
    </script>
</body>
</html>