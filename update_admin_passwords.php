<?php
require_once 'db.php';

echo "<h2>Update Admin Passwords</h2>";

// Update all station admins to have a default password of "password"
$defaultPassword = 'password';
$hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

// Update statement for all admins
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE role = 'admin' AND email LIKE 'admin%@gmail.com'");
$stmt->bind_param("s", $hashedPassword);

if ($stmt->execute()) {
    $affectedRows = $conn->affected_rows;
    echo "<p>✅ Updated passwords for $affectedRows admin accounts</p>";
    echo "<p><strong>Default login credentials for station admins:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Email:</strong> adminlitein@gmail.com, <strong>Password:</strong> password</li>";
    echo "<li><strong>Email:</strong> adminnairobi@gmail.com, <strong>Password:</strong> password</li>";
    echo "<li><strong>Email:</strong> adminkisumu@gmail.com, <strong>Password:</strong> password</li>";
    echo "<li><strong>Email:</strong> adminnakuru@gmail.com, <strong>Password:</strong> password</li>";
    echo "<li><strong>Email:</strong> adminbomet@gmail.com, <strong>Password:</strong> password</li>";
    echo "</ul>";
    echo "<p style='color: red;'><strong>⚠️ IMPORTANT:</strong> Please ensure each admin changes their password immediately after first login!</p>";
} else {
    echo "<p>❌ Failed to update admin passwords: " . $conn->error . "</p>";
}

$stmt->close();
$conn->close();

echo "<h3>How to Test the Station-Based Filtering</h3>";
echo "<ol>";
echo "<li>Log in as adminlitein@gmail.com with password 'password'</li>";
echo "<li>Create a test booking with boarding point 'Litein'</li>";
echo "<li>Verify that only the Litein admin can see this booking in 'Today's Bookings'</li>";
echo "<li>Log in as adminnairobi@gmail.com with password 'password'</li>";
echo "<li>Verify that the Nairobi admin cannot see the Litein booking</li>";
echo "<li>Create a test booking with boarding point 'Nairobi'</li>";
echo "<li>Verify that only the Nairobi admin can see this booking</li>";
echo "</ol>";
?>