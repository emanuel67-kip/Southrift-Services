<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db.php';

echo "<h2>Test Login After Column Fix</h2>";

// Simulate a login request to test the system
if (isset($_POST['test_login'])) {
    // Simulate POST data
    $_POST['username'] = $_POST['test_username'];
    $_POST['password'] = $_POST['test_password'];
    
    echo "<h3>Testing Login Process...</h3>";
    
    // Start session for the test
    session_start();
    
    // Include the login logic (but capture output)
    ob_start();
    
    try {
        // Replicate the login logic here for testing
        $identifier = trim($_POST['username'] ?? '');
        $password_input = trim($_POST['password'] ?? '');
        
        if (empty($identifier) || empty($password_input)) {
            throw new Exception('Username and password are required');
        }
        
        // Check the users table for passenger/admin login
        $sql = "SELECT * FROM users WHERE (name = ? OR email = ?) AND role IN ('passenger', 'admin')";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password_input, $user['password'])) {
                echo "✅ User found and password verified: {$user['name']} (Role: {$user['role']})<br>";
                
                // Test last_login update
                try {
                    $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $update->bind_param("i", $user['id']);
                    $update->execute();
                    echo "✅ last_login column update successful<br>";
                } catch (Exception $e) {
                    echo "❌ last_login column update failed: " . $e->getMessage() . "<br>";
                }
                
                echo "🎉 <strong>Login test PASSED!</strong><br>";
                
            } else {
                echo "❌ Password verification failed<br>";
            }
        } else {
            echo "❌ No user found with that username/email<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Login test failed: " . $e->getMessage() . "<br>";
    }
    
    $output = ob_get_clean();
    echo $output;
}

// Show test form
echo "<h3>Test Login Form</h3>";
echo "<form method='POST' style='background: #f9f9f9; padding: 20px; border: 1px solid #ddd; max-width: 400px;'>";
echo "<p><label>Username/Email: <input type='text' name='test_username' placeholder='Enter existing username or email' style='width: 250px;'></label></p>";
echo "<p><label>Password: <input type='password' name='test_password' placeholder='Enter password' style='width: 250px;'></label></p>";
echo "<p><input type='submit' name='test_login' value='Test Login' style='background: #007cba; color: white; padding: 8px 16px; border: none;'></p>";
echo "</form>";

// Show current users for reference
echo "<h3>Available Test Users</h3>";
$result = $conn->query("SELECT id, name, email, role FROM users ORDER BY id");

if ($result) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td><strong>{$row['role']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Error fetching users: " . $conn->error;
}

echo "<p><strong>Note:</strong> You can use any name or email from the table above. The password for the admin user is typically 'password' or similar.</p>";

$conn->close();
?>