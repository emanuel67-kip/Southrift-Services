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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Track Ride Authentication</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        button { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-primary { background: #6A0DAD; color: white; }
        .btn-primary:hover { background: #4e0b8a; }
    </style>
</head>
<body>
    <h1>Debug: Track Ride Authentication Flow</h1>
    
    <h2>1. Session Status</h2>
    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="status success">
            ✅ User is logged in!
            <br><strong>User ID:</strong> <?= $_SESSION['user_id'] ?>
            <br><strong>Role:</strong> <?= $_SESSION['role'] ?? 'No role set' ?>
            <br><strong>Email:</strong> <?= $_SESSION['email'] ?? 'No email set' ?>
            <br><strong>Username:</strong> <?= $_SESSION['username'] ?? 'No username set' ?>
        </div>
    <?php else: ?>
        <div class="status error">
            ❌ User is NOT logged in
            <br>Current session variables: <?= empty($_SESSION) ? 'No session data' : implode(', ', array_keys($_SESSION)) ?>
        </div>
    <?php endif; ?>
    
    <h2>2. Authentication Test</h2>
    <p>This simulates what happens when you click "Track Ride":</p>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="status success">
            ✅ Authentication passed - you should be able to access track_my_driver.php
        </div>
        
        <button class="btn-primary" onclick="window.open('track_my_driver.php', '_blank')">
            🚗 Test Track My Driver
        </button>
        
        <h3>Test with specific booking ID:</h3>
        <form method="GET" action="track_my_driver.php" target="_blank" style="margin: 10px 0;">
            <input type="number" name="booking_id" placeholder="Enter booking ID" min="1" style="padding: 8px; margin-right: 10px;">
            <button type="submit" class="btn-primary">Track with Booking ID</button>
        </form>
        
    <?php else: ?>
        <div class="status error">
            ❌ Authentication failed - you will be redirected to login page
        </div>
        
        <h3>Steps to fix:</h3>
        <ol>
            <li>Make sure you're logged in as a passenger</li>
            <li>Check that your session is using the correct session name</li>
            <li>Verify login.php sets the correct session variables</li>
        </ol>
        
        <button class="btn-primary" onclick="window.open('login.php', '_blank')">
            🔐 Go to Login
        </button>
    <?php endif; ?>
    
    <h2>3. Quick Tests</h2>
    <div style="margin: 20px 0;">
        <button class="btn-primary" onclick="window.open('profile.html', '_blank')">
            👤 Test Profile Page
        </button>
        
        <button class="btn-primary" onclick="window.open('profile.php', '_blank')">
            📊 Test Profile API
        </button>
        
        <button class="btn-primary" onclick="testTrackRideFunction()">
            🧪 Test Track Ride Function
        </button>
    </div>
    
    <h2>4. Session Details</h2>
    <div class="status info">
        <strong>Session ID:</strong> <?= session_id() ?><br>
        <strong>Session Name:</strong> <?= session_name() ?><br>
        <strong>Session Data:</strong>
        <pre><?= print_r($_SESSION, true) ?></pre>
    </div>
    
    <h2>5. Common Issues & Solutions</h2>
    <div class="status warning">
        <strong>If track ride is redirecting to login:</strong>
        <ul>
            <li>Check if you're logged in as a passenger (not admin/driver)</li>
            <li>Verify session name matches 'southrift_admin'</li>
            <li>Make sure login.php sets $_SESSION['user_id'] correctly</li>
            <li>Clear browser cookies and try logging in again</li>
        </ul>
    </div>

    <script>
        function testTrackRideFunction() {
            // Simulate the trackRide function from profile.html
            const bookingId = prompt("Enter a booking ID to test:");
            if (bookingId && bookingId.trim() !== '') {
                window.open(`track_my_driver.php?booking_id=${encodeURIComponent(bookingId)}`, '_blank');
            }
        }
    </script>
</body>
</html>