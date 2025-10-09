<?php
// Test script to check session
session_name('southrift_admin');
session_start();

echo "<h2>Session Test</h2>";

echo "<p>Session ID: " . session_id() . "</p>";

echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['user_id'])) {
    echo "<p>User is logged in with ID: " . $_SESSION['user_id'] . "</p>";
    
    if (isset($_SESSION['role'])) {
        echo "<p>User role: " . $_SESSION['role'] . "</p>";
    }
    
    echo "<p><a href='profile.php'>Try Profile Page</a></p>";
} else {
    echo "<p>User is not logged in.</p>";
    echo "<p><a href='../login.html'>Go to Login</a></p>";
}
?>