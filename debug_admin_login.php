<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db.php';

echo "<h2>Debug Admin Login Issue</h2>";

try {
    // Check database connection
    if ($conn->connect_error) {
        echo "❌ Database connection failed: " . $conn->connect_error . "<br>";
        exit;
    }
    echo "✅ Database connected successfully<br><br>";

    // 1. Check if users table exists and has admin role
    echo "<h3>Step 1: Check users table structure</h3>";
    $result = $conn->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
    if ($result && $row = $result->fetch_assoc()) {
        echo "✅ Role column type: <strong>" . $row['Type'] . "</strong><br>";
        echo "Default value: <strong>" . ($row['Default'] ?: 'NULL') . "</strong><br><br>";
    } else {
        echo "❌ Role column not found in users table<br><br>";
    }

    // 2. Check existing admin users
    echo "<h3>Step 2: Check existing admin users</h3>";
    $result = $conn->query("SELECT id, name, email, phone, role, status, created_at FROM users WHERE role = 'admin'");
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Created</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['name']}</td>";
            echo "<td>{$row['email']}</td>";
            echo "<td>{$row['phone']}</td>";
            echo "<td><strong style='color: red;'>{$row['role']}</strong></td>";
            echo "<td>{$row['status']}</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><strong>Found " . $result->num_rows . " admin user(s)</strong></p>";
    } else {
        echo "❌ <strong>No admin users found in database!</strong><br>";
        echo "<p style='color: red;'>This is likely the problem - you need to add an admin user first.</p>";
    }

    // 3. Test login query
    echo "<h3>Step 3: Test Admin Login Query</h3>";
    echo "<form method='POST' style='background: #f9f9f9; padding: 15px; border: 1px solid #ddd; max-width: 400px;'>";
    echo "<h4>Test Admin Login</h4>";
    echo "<p><label>Username/Email: <input type='text' name='test_username' placeholder='admin@southrift.com' style='width: 200px;'></label></p>";
    echo "<p><label>Password: <input type='password' name='test_password' placeholder='admin123' style='width: 200px;'></label></p>";
    echo "<p><input type='submit' name='test_admin_login' value='Test Admin Login' style='background: #dc3545; color: white; padding: 8px 16px; border: none;'></p>";
    echo "</form>";

    // Process test login
    if (isset($_POST['test_admin_login'])) {
        $test_user = trim($_POST['test_username']);
        $test_pass = trim($_POST['test_password']);
        
        echo "<h4>🔍 Login Test Results:</h4>";
        
        if (empty($test_user) || empty($test_pass)) {
            echo "❌ Please provide both username and password<br>";
        } else {
            echo "<p><strong>Testing with:</strong> Username='$test_user', Password='$test_pass'</p>";
            
            // Test the exact login query from login.php
            $sql = "SELECT * FROM users WHERE (name = ? OR email = ?) AND role IN ('passenger', 'admin')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $test_user, $test_user);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                echo "✅ User found: {$user['name']} (Role: {$user['role']}, Status: {$user['status']})<br>";
                
                // Show password hash for debugging
                echo "<p><strong>Stored password hash:</strong> " . substr($user['password'], 0, 30) . "...</p>";
                
                if (password_verify($test_pass, $user['password'])) {
                    echo "✅ <strong style='color: green;'>Password verification SUCCESSFUL!</strong><br>";
                    echo "🎉 <strong>Login should work!</strong><br>";
                    
                    if ($user['status'] !== 'active') {
                        echo "⚠️ <strong style='color: orange;'>Warning: User status is '{$user['status']}' not 'active'</strong><br>";
                    }
                    
                } else {
                    echo "❌ <strong style='color: red;'>Password verification FAILED!</strong><br>";
                    echo "<p style='color: red;'>The password '$test_pass' does not match the stored hash.</p>";
                    
                    // Test with common passwords
                    $common_passwords = ['password', 'admin', 'admin123', 'southrift2024'];
                    echo "<p><strong>Testing common passwords:</strong></p>";
                    foreach ($common_passwords as $pwd) {
                        if (password_verify($pwd, $user['password'])) {
                            echo "✅ <strong style='color: green;'>Password is: '$pwd'</strong><br>";
                            break;
                        } else {
                            echo "❌ Not: '$pwd'<br>";
                        }
                    }
                }
            } else {
                echo "❌ <strong style='color: red;'>No user found with that username/email and admin role</strong><br>";
                
                // Check if user exists but with different role
                $sql2 = "SELECT * FROM users WHERE (name = ? OR email = ?)";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->bind_param("ss", $test_user, $test_user);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                
                if ($user2 = $result2->fetch_assoc()) {
                    echo "<p>❌ User exists but role is '{$user2['role']}' not 'admin'</p>";
                } else {
                    echo "<p>❌ User does not exist in database at all</p>";
                }
            }
        }
    }

    // 4. Quick fix options
    echo "<h3>Step 4: Quick Fix Options</h3>";
    echo "<div style='background: #e8f4f8; padding: 15px; border: 1px solid #bee5eb; margin: 10px 0;'>";
    
    if ($result && $result->num_rows == 0) {
        echo "<h4>🔧 Solution: Add Admin User</h4>";
        echo "<p>Run this SQL to add an admin user:</p>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6;'>";
        echo "INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`, `status`) \n";
        echo "VALUES ('Administrator', 'admin@southrift.com', '254700000000', \n";
        echo "'\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');";
        echo "</pre>";
        echo "<p><strong>Login credentials:</strong> admin@southrift.com / admin123</p>";
    }
    
    echo "<h4>📋 Alternative Solutions:</h4>";
    echo "<ol>";
    echo "<li><strong>Check password:</strong> Try common passwords like 'password', 'admin', 'admin123'</li>";
    echo "<li><strong>Check username:</strong> Use email address instead of name</li>";
    echo "<li><strong>Check user status:</strong> Make sure user status is 'active'</li>";
    echo "<li><strong>Add new admin:</strong> Use the SQL command provided above</li>";
    echo "</ol>";
    echo "</div>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

$conn->close();
?>