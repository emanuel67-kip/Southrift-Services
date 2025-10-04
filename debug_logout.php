<?php
// Debug script for logout issues
session_start();

echo "<pre>";
echo "=== Debug Information ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Data: " . print_r($_SESSION, true) . "\n";
echo "\n=== Request Information ===\n";
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "HTTP Referer: " . ($_SERVER['HTTP_REFERER'] ?? 'Not set') . "\n";

// Test session destruction
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    echo "\n=== Performing Logout ===\n";
    
    // Store session data before destruction
    $sessionData = $_SESSION;
    
    // Clear session data
    $_SESSION = [];
    
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
    
    echo "Session destroyed. <a href='debug_logout.php'>Test again</a> or <a href='login.php'>Go to login</a>";
    exit;
}
?>

<h1>Debug Logout</h1>
<p>This page helps debug logout issues.</p>

<h2>Session Information</h2>
<pre>Session ID: <?php echo session_id(); ?>
Session Status: <?php echo session_status(); ?>

Session Data:
<?php print_r($_SESSION); ?>
</pre>

<h2>Test Logout</h2>
<p><a href="debug_logout.php?action=logout">Click here to test logout</a></p>

<h2>Request Information</h2>
<pre>Request Method: <?php echo $_SERVER['REQUEST_METHOD']; ?>
Request URI: <?php echo $_SERVER['REQUEST_URI']; ?>
HTTP Referer: <?php echo $_SERVER['HTTP_REFERER'] ?? 'Not set'; ?>
</pre>
