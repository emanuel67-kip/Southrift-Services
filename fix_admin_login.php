<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db.php';

echo "<h2>Fix Admin Login Issues</h2>";

try {
    // 1. First, ensure the users table has the correct role enum
    echo "<h3>Step 1: Fix users table role enum</h3>";
    $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'driver', 'user', 'passenger') NOT NULL DEFAULT 'passenger'";
    
    if ($conn->query($sql)) {
        echo "✅ Updated users table role enum successfully<br>";
    } else {
        echo "⚠️ Role enum update: " . $conn->error . "<br>";
    }

    // 2. Check if any admin users exist
    echo "<h3>Step 2: Check existing admin users</h3>";
    $result = $conn->query("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
    $row = $result->fetch_assoc();
    $admin_count = $row['admin_count'];
    
    echo "Current admin users: <strong>$admin_count</strong><br>";

    // 3. Add admin user if none exists
    if ($admin_count == 0) {
        echo "<h3>Step 3: Adding default admin user</h3>";
        
        // Create admin with password "admin123"
        $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
        
        $insert_sql = "INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`, `status`) 
                      VALUES ('Administrator', 'admin@southrift.com', '254700000000', ?, 'admin', 'active')";
        
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("s", $admin_password);
        
        if ($stmt->execute()) {
            echo "✅ <strong>Admin user created successfully!</strong><br>";
            echo "<div style='background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>Admin Login Credentials:</strong><br>";
            echo "Username: <strong>admin@southrift.com</strong> or <strong>254700000000</strong><br>";
            echo "Password: <strong>admin123</strong>";
            echo "</div>";
        } else {
            echo "❌ Error creating admin user: " . $stmt->error . "<br>";
        }
    } else {
        echo "<h3>Step 3: Admin users already exist</h3>";
        
        // Show existing admin users
        $result = $conn->query("SELECT id, name, email, phone, status FROM users WHERE role = 'admin'");
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['name']}</td>";
            echo "<td>{$row['email']}</td>";
            echo "<td>{$row['phone']}</td>";
            echo "<td><strong>{$row['status']}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div style='background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>Try logging in with one of the above credentials.</strong><br>";
        echo "If you don't know the password, you can reset it below.";
        echo "</div>";
    }

    // 4. Password reset option
    echo "<h3>Step 4: Reset Admin Password (Optional)</h3>";
    echo "<form method='POST' style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; max-width: 400px;'>";
    echo "<h4>Reset Admin Password</h4>";
    echo "<p><label>Admin Email: <input type='email' name='admin_email' placeholder='admin@southrift.com' style='width: 200px;'></label></p>";
    echo "<p><label>New Password: <input type='password' name='new_password' placeholder='Enter new password' style='width: 200px;'></label></p>";
    echo "<p><input type='submit' name='reset_password' value='Reset Password' style='background: #ffc107; color: #212529; padding: 8px 16px; border: none;'></p>";
    echo "</form>";

    // Handle password reset
    if (isset($_POST['reset_password'])) {
        $admin_email = trim($_POST['admin_email']);
        $new_password = trim($_POST['new_password']);
        
        if (!empty($admin_email) && !empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $update_sql = "UPDATE users SET password = ? WHERE email = ? AND role = 'admin'";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ss", $hashed_password, $admin_email);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                echo "<div style='background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
                echo "✅ <strong>Password updated successfully!</strong><br>";
                echo "You can now login with: $admin_email / $new_password";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
                echo "❌ Failed to update password. Admin user with email '$admin_email' not found.";
                echo "</div>";
            }
        }
    }

    // 5. Test login link
    echo "<h3>Step 5: Test Login</h3>";
    echo "<p><a href='login.html' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Admin Login</a></p>";
    echo "<p><a href='debug_admin_login.php' target='_blank' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Debug Login Process</a></p>";

    echo "<h3>✅ Admin Login Fix Complete!</h3>";
    echo "<p>If you're still having issues, run the debug script to see what's happening.</p>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

$conn->close();
?>