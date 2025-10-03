<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db.php';

echo "<h2>Fix Database Schema Issues</h2>";

try {
    echo "<h3>Step 1: Check and Fix Users Table</h3>";
    
    // Check users table structure
    $columns_result = $conn->query("DESCRIBE users");
    $existing_columns = [];
    
    if ($columns_result) {
        while ($col = $columns_result->fetch_assoc()) {
            $existing_columns[] = $col['Field'];
        }
        
        echo "<p>✅ Users table exists with columns: <strong>" . implode(', ', $existing_columns) . "</strong></p>";
        
        // Define required columns for users table
        $required_columns = [
            'status' => "ENUM('active', 'inactive', 'suspended') DEFAULT 'active'",
            'last_login' => "TIMESTAMP NULL DEFAULT NULL"
        ];
        
        foreach ($required_columns as $column => $definition) {
            if (!in_array($column, $existing_columns)) {
                echo "<p>⚠️ Adding missing column: <strong>$column</strong></p>";
                
                $alter_sql = "ALTER TABLE users ADD COLUMN $column $definition";
                if ($conn->query($alter_sql)) {
                    echo "<p>✅ Successfully added <strong>$column</strong> column</p>";
                } else {
                    echo "<p>❌ Failed to add $column: " . $conn->error . "</p>";
                }
            } else {
                echo "<p>✅ Column <strong>$column</strong> already exists</p>";
            }
        }
        
        // Check role ENUM values
        $role_check = $conn->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
        if ($role_check && $row = $role_check->fetch_assoc()) {
            $type = $row['Type'];
            echo "<p>✅ Role column type: <strong>$type</strong></p>";
            
            if (strpos($type, 'passenger') === false) {
                echo "<p>⚠️ Updating role ENUM to include 'passenger'</p>";
                $update_role = "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'driver', 'user', 'passenger') NOT NULL DEFAULT 'passenger'";
                if ($conn->query($update_role)) {
                    echo "<p>✅ Successfully updated role ENUM</p>";
                } else {
                    echo "<p>❌ Failed to update role ENUM: " . $conn->error . "</p>";
                }
            }
        }
    } else {
        echo "<p>❌ Could not describe users table: " . $conn->error . "</p>";
    }
    
    echo "<h3>Step 2: Check Admin Users</h3>";
    
    // Check if admin users exist
    $admin_count_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $admin_count = $admin_count_result->fetch_assoc()['count'];
    
    echo "<p>Current admin users: <strong>$admin_count</strong></p>";
    
    if ($admin_count == 0) {
        echo "<p>⚠️ No admin users found. Creating default admin...</p>";
        
        $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
        $insert_admin = "INSERT INTO users (name, email, phone, password, role, status) 
                        VALUES ('Administrator', 'admin@southrift.com', '254700000000', ?, 'admin', 'active')";
        
        $stmt = $conn->prepare($insert_admin);
        $stmt->bind_param("s", $admin_password);
        
        if ($stmt->execute()) {
            echo "<p>✅ Default admin user created successfully!</p>";
            echo "<div style='background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>Admin Credentials:</strong><br>";
            echo "Email: <strong>admin@southrift.com</strong><br>";
            echo "Phone: <strong>254700000000</strong><br>";
            echo "Password: <strong>admin123</strong>";
            echo "</div>";
        } else {
            echo "<p>❌ Failed to create admin user: " . $stmt->error . "</p>";
        }
    }
    
    echo "<h3>Step 3: Final Database Structure Check</h3>";
    
    // Show final users table structure
    echo "<h4>Final Users Table Structure:</h4>";
    $final_result = $conn->query("DESCRIBE users");
    if ($final_result) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        while ($row = $final_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td><strong>{$row['Field']}</strong></td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Show current admin users
    echo "<h4>Current Admin Users:</h4>";
    $admin_result = $conn->query("SELECT id, name, email, phone, role, status, created_at FROM users WHERE role = 'admin'");
    if ($admin_result && $admin_result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Created</th></tr>";
        
        while ($row = $admin_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['name']}</td>";
            echo "<td>{$row['email']}</td>";
            echo "<td>{$row['phone']}</td>";
            echo "<td><strong style='color: red;'>{$row['role']}</strong></td>";
            echo "<td><strong style='color: green;'>{$row['status']}</strong></td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No admin users found after fix attempt</p>";
    }
    
    echo "<h3>✅ Database Schema Fix Complete!</h3>";
    echo "<p><a href='verify_admin_redirect.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Admin Redirect Again</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?>