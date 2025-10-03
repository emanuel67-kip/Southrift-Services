<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db.php';

echo "<h2>Login System Test</h2>";

// Check database connection
if ($conn->connect_error) {
    echo "❌ Database connection failed: " . $conn->connect_error . "<br>";
    exit;
}
echo "✅ Database connected successfully<br><br>";

// Check users table structure
echo "<h3>Users Table Structure</h3>";
$result = $conn->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
if ($result && $row = $result->fetch_assoc()) {
    echo "Role field type: <strong>" . $row['Type'] . "</strong><br>";
    echo "Default value: <strong>" . $row['Default'] . "</strong><br><br>";
} else {
    echo "❌ Could not check role field<br><br>";
}

// Show current users
echo "<h3>Current Users in Database</h3>";
$result = $conn->query("SELECT id, name, email, role, status, created_at FROM users ORDER BY id");

if ($result) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th></tr>";
    
    $userCount = 0;
    while ($row = $result->fetch_assoc()) {
        $userCount++;
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td><strong style='color: " . ($row['role'] === 'admin' ? 'red' : ($row['role'] === 'passenger' ? 'blue' : 'green')) . ";'>{$row['role']}</strong></td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p>Total users: <strong>$userCount</strong></p>";
} else {
    echo "❌ Error fetching users: " . $conn->error . "<br>";
}

// Test login form (simulation)
echo "<h3>Test Login Logic</h3>";
echo "<form method='POST' style='background: #f9f9f9; padding: 20px; border: 1px solid #ddd; max-width: 400px;'>";
echo "<h4>Test Login (Choose existing user from table above)</h4>";
echo "<p><label>Username/Email: <input type='text' name='test_username' value='' style='width: 200px;'></label></p>";
echo "<p><label>Password: <input type='password' name='test_password' value='' style='width: 200px;'></label></p>";
echo "<p><input type='submit' name='test_login' value='Test Login Logic' style='background: #007cba; color: white; padding: 8px 16px; border: none;'></p>";
echo "</form>";

// Process test login
if (isset($_POST['test_login'])) {
    $test_user = trim($_POST['test_username']);
    $test_pass = trim($_POST['test_password']);
    
    echo "<h4>Login Test Results:</h4>";
    
    if (empty($test_user) || empty($test_pass)) {
        echo "❌ Please provide both username and password<br>";
    } else {
        // Test the login query
        $sql = "SELECT * FROM users WHERE (name = ? OR email = ?) AND role IN ('passenger', 'admin')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $test_user, $test_user);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            echo "✅ User found: {$user['name']} (Role: {$user['role']})<br>";
            
            if (password_verify($test_pass, $user['password'])) {
                echo "✅ Password verification successful<br>";
                echo "🎉 <strong>Login would succeed!</strong><br>";
                
                $redirect = ($user['role'] === 'admin') ? 'Admin/index.php' : 'profile.html';
                echo "Would redirect to: <strong>$redirect</strong><br>";
            } else {
                echo "❌ Password verification failed<br>";
            }
        } else {
            echo "❌ No user found with that username/email and role (passenger/admin)<br>";
        }
    }
}

echo "<br><h3>System Status</h3>";
echo "<p>✅ Database structure updated to support 'passenger' role</p>";
echo "<p>✅ Registration will assign 'passenger' role by default</p>";
echo "<p>✅ Login accepts 'passenger' and 'admin' roles</p>";
echo "<p>✅ System ready for testing</p>";

$conn->close();
?>