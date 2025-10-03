<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db.php';

echo "<h2>Fixing User Roles</h2>";

try {
    // 1. First, update the ENUM to include 'passenger'
    echo "<h3>Step 1: Updating role ENUM to include 'passenger'</h3>";
    $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'driver', 'user', 'passenger') NOT NULL DEFAULT 'passenger'";
    
    if ($conn->query($sql)) {
        echo "✅ Successfully updated role ENUM to include 'passenger'<br>";
    } else {
        echo "❌ Error updating ENUM: " . $conn->error . "<br>";
    }
    
    // 2. Update existing users with role 'user' to 'passenger'
    echo "<h3>Step 2: Converting existing 'user' roles to 'passenger'</h3>";
    $sql = "UPDATE users SET role = 'passenger' WHERE role = 'user'";
    
    if ($conn->query($sql)) {
        $affected = $conn->affected_rows;
        echo "✅ Successfully updated $affected users from 'user' to 'passenger'<br>";
    } else {
        echo "❌ Error updating users: " . $conn->error . "<br>";
    }
    
    // 3. Show current users
    echo "<h3>Step 3: Current users in database</h3>";
    $result = $conn->query("SELECT id, name, email, role, status FROM users");
    
    if ($result) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['name']}</td>";
            echo "<td>{$row['email']}</td>";
            echo "<td><strong>{$row['role']}</strong></td>";
            echo "<td>{$row['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ Error fetching users: " . $conn->error . "<br>";
    }
    
    echo "<br><h3>✅ Database update completed!</h3>";
    echo "<p>You can now test the login system. New signups will be assigned 'passenger' role.</p>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

$conn->close();
?>