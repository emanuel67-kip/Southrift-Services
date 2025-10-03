<?php
// Connect to database
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'southrift';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set admin password to 'admin123'
$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Check if admin exists
$sql = "SELECT id FROM users WHERE email = 'admin@southrift.com' OR role = 'admin' LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Update existing admin
    $sql = "UPDATE users SET password = ? WHERE email = 'admin@southrift.com' OR role = 'admin' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $hashed_password);
    $stmt->execute();
    echo "Admin password has been reset to: admin123<br>";
} else {
    // Create new admin
    $sql = "INSERT INTO users (name, email, phone, password, role, status) 
            VALUES ('Admin', 'admin@southrift.com', '254700000000', ?, 'admin', 'active')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $hashed_password);
    $stmt->execute();
    echo "New admin created with password: admin123<br>";
}

echo "<br>Try logging in with:<br>";
echo "Email: admin@southrift.com<br>";
echo "Password: admin123<br>";
echo "<br><a href='login.php'>Go to Login Page</a>";

$conn->close();
?>
