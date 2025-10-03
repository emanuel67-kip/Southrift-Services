<?php
// Set the correct session configuration to match the login system
if (session_status() === PHP_SESSION_NONE) {
    session_name('southrift_admin');
    session_start();
} else {
    session_start();
}

echo "<h2>Driver Authentication Debug</h2>";
echo "<p><strong>Session Status:</strong> " . session_status() . "</p>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Name:</strong> " . session_name() . "</p>";

echo "<h3>All Session Variables:</h3>";
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

echo "<h3>Required Variables Check:</h3>";
echo "<p><strong>user_id:</strong> " . (isset($_SESSION['user_id']) ? "✅ " . $_SESSION['user_id'] : "❌ NOT SET") . "</p>";
echo "<p><strong>role:</strong> " . (isset($_SESSION['role']) ? "✅ " . $_SESSION['role'] : "❌ NOT SET") . "</p>";
echo "<p><strong>phone:</strong> " . (isset($_SESSION['phone']) ? "✅ " . $_SESSION['phone'] : "❌ NOT SET") . "</p>";
echo "<p><strong>name:</strong> " . (isset($_SESSION['name']) ? "✅ " . $_SESSION['name'] : "❌ NOT SET") . "</p>";

echo "<h3>Authentication Test:</h3>";
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'driver') {
    echo "<p style='color: green;'>✅ Authentication should PASS</p>";
} else {
    echo "<p style='color: red;'>❌ Authentication would FAIL</p>";
    if (!isset($_SESSION['user_id'])) {
        echo "<p style='color: red;'>- Missing user_id</p>";
    }
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
        echo "<p style='color: red;'>- Role is not 'driver' (current: " . ($_SESSION['role'] ?? 'NOT SET') . ")</p>";
    }
}

echo "<h3>Database Check:</h3>";
require dirname(__DIR__) . '/db.php';

if (isset($_SESSION['phone'])) {
    $driver_phone = $_SESSION['phone'];
    $stmt = $conn->prepare("SELECT id, name, driver_phone, number_plate, status FROM drivers WHERE driver_phone = ?");
    $stmt->bind_param("s", $driver_phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($driver = $result->fetch_assoc()) {
        echo "<p style='color: green;'>✅ Driver found in database:</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        foreach ($driver as $key => $value) {
            echo "<tr><td><strong>" . htmlspecialchars($key) . "</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Driver NOT found in database with phone: " . htmlspecialchars($driver_phone) . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ No phone in session to check database</p>";
}

echo "<p><a href='index.php'>← Back to Dashboard</a></p>";
?>