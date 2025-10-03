<?php
require __DIR__ . '/auth.php';

echo "<h2>Driver Session Debug</h2>";
echo "<p><strong>Session Status:</strong> " . session_status() . "</p>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Name:</strong> " . session_name() . "</p>";

echo "<h3>Session Variables:</h3>";
if (empty($_SESSION)) {
    echo "<p style='color: red;'>❌ No session variables found!</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Key</th><th>Value</th></tr>";
    foreach ($_SESSION as $key => $value) {
        echo "<tr><td>" . htmlspecialchars($key) . "</td><td>" . htmlspecialchars(print_r($value, true)) . "</td></tr>";
    }
    echo "</table>";
}

echo "<h3>Authentication Status:</h3>";
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'driver') {
    echo "<p style='color: green;'>✅ Driver authentication: SUCCESS</p>";
    echo "<p><strong>Driver ID:</strong> " . $_SESSION['user_id'] . "</p>";
    echo "<p><strong>Driver Name:</strong> " . ($_SESSION['name'] ?? 'N/A') . "</p>";
    echo "<p><strong>Driver Phone:</strong> " . ($_SESSION['phone'] ?? 'N/A') . "</p>";
    echo "<p><strong>Role:</strong> " . $_SESSION['role'] . "</p>";
} else {
    echo "<p style='color: red;'>❌ Driver authentication: FAILED</p>";
}
?>