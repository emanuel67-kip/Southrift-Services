<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db.php';

echo "<h2>Test Admin Login Redirect</h2>";

// Test admin login flow
if (isset($_POST['test_admin_redirect'])) {
    session_start();
    
    $test_user = trim($_POST['test_username']);
    $test_pass = trim($_POST['test_password']);
    
    echo "<h3>🔍 Testing Admin Login Flow</h3>";
    echo "<p><strong>Testing with:</strong> Username='$test_user', Password='$test_pass'</p>";
    
    try {
        // Simulate the exact login logic
        $sql = "SELECT * FROM users WHERE (name = ? OR email = ?) AND role IN ('passenger', 'admin')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $test_user, $test_user);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($test_pass, $user['password'])) {
                echo "✅ <strong style='color: green;'>Password verification successful!</strong><br>";
                
                // Clear any existing session data
                $_SESSION = [];
                
                // Set session variables exactly like login.php
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                $_SESSION['last_activity'] = time();
                
                echo "<h4>Session Variables Set:</h4>";
                echo "<ul>";
                echo "<li><strong>user_id:</strong> {$_SESSION['user_id']}</li>";
                echo "<li><strong>username:</strong> {$_SESSION['username']}</li>";
                echo "<li><strong>role:</strong> {$_SESSION['role']}</li>";
                echo "<li><strong>email:</strong> {$_SESSION['email']}</li>";
                echo "</ul>";
                
                // Test if admin auth would work
                echo "<h4>Admin Auth Check:</h4>";
                if (isset($_SESSION['username']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                    echo "✅ <strong style='color: green;'>Admin auth check would PASS!</strong><br>";
                    echo "🎯 <strong>Redirect should work to: Admin/index.php</strong><br>";
                    
                    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                    echo "<h4>✅ Login Flow Test SUCCESSFUL!</h4>";
                    echo "<p><strong>Admin can successfully:</strong></p>";
                    echo "<ul>";
                    echo "<li>✅ Login with credentials</li>";
                    echo "<li>✅ Pass admin authentication</li>";
                    echo "<li>✅ Access Admin/index.php dashboard</li>";
                    echo "</ul>";
                    echo "<p><a href='Admin/index.php' target='_blank' style='background: #6A0DAD; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Test Admin Dashboard Access</a></p>";
                    echo "</div>";
                    
                } else {
                    echo "❌ <strong style='color: red;'>Admin auth check would FAIL!</strong><br>";
                    echo "<p>Missing session variables or wrong role</p>";
                }
                
            } else {
                echo "❌ Password verification failed<br>";
            }
        } else {
            echo "❌ No admin user found<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage();
    }
}

// Show test form
echo "<h3>Test Admin Login Redirect</h3>";
echo "<form method='POST' style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; max-width: 400px;'>";
echo "<h4>Simulate Admin Login</h4>";
echo "<p><label>Username/Email: <input type='text' name='test_username' placeholder='admin@southrift.com' style='width: 200px;'></label></p>";
echo "<p><label>Password: <input type='password' name='test_password' placeholder='admin123' style='width: 200px;'></label></p>";
echo "<p><input type='submit' name='test_admin_redirect' value='Test Admin Login' style='background: #007bff; color: white; padding: 8px 16px; border: none;'></p>";
echo "</form>";

// Show current session
echo "<h3>Current Session Status</h3>";
if (session_status() === PHP_SESSION_ACTIVE) {
    session_start();
    echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
    echo "<p><strong>Session Data:</strong></p>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6;'>";
    print_r($_SESSION);
    echo "</pre>";
} else {
    echo "<p>No active session</p>";
}

// Quick links
echo "<h3>Quick Access</h3>";
echo "<p><a href='login.html' target='_blank' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Login Page</a></p>";
echo "<p><a href='Admin/index.php' target='_blank' style='background: #dc3545; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Admin Dashboard (Direct)</a></p>";

$conn->close();
?>