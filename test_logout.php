<?php
// Test logout functionality
session_start();

echo "<h1>Session Test</h1>\n";

if (isset($_SESSION['user_id'])) {
    echo "<p>User is logged in with ID: " . $_SESSION['user_id'] . "</p>\n";
    echo "<p><a href='passenger_logout.php'>Logout</a></p>\n";
} else {
    echo "<p>User is not logged in</p>\n";
    echo "<p><a href='login.html'>Login</a></p>\n";
}

echo "<h2>Session Data:</h2>\n";
echo "<pre>\n";
print_r($_SESSION);
echo "</pre>\n";
?>