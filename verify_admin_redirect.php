<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Admin Login Redirect Verification</h2>";

// Check if admin user exists
require_once 'db.php';

echo "<h3>✅ Current System Status</h3>";

// 1. Check users table structure first
echo "<h4>Users Table Structure:</h4>";
$columns_result = $conn->query("DESCRIBE users");
if ($columns_result) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $available_columns = [];
    while ($col = $columns_result->fetch_assoc()) {
        $available_columns[] = $col['Field'];
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if status column exists
    if (!in_array('status', $available_columns)) {
        echo "<div style='background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "⚠️ <strong>Warning:</strong> 'status' column missing from users table. Adding it now...";
        echo "</div>";
        
        // Add the missing status column
        $add_status = "ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive', 'suspended') DEFAULT 'active' AFTER role";
        if ($conn->query($add_status)) {
            echo "<div style='background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "✅ Successfully added 'status' column to users table";
            echo "</div>";
            $available_columns[] = 'status';
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "❌ Failed to add 'status' column: " . $conn->error;
            echo "</div>";
        }
    }
} else {
    echo "❌ Could not describe users table: " . $conn->error;
}

// 2. Check admin users with available columns
$select_columns = 'id, name, email, phone, role';
if (in_array('status', $available_columns ?? [])) {
    $select_columns .= ', status';
}
if (in_array('created_at', $available_columns ?? [])) {
    $select_columns .= ', created_at';
}

$result = $conn->query("SELECT $select_columns FROM users WHERE role = 'admin'");
echo "<h4>Admin Users in Database:</h4>";

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    
    // Dynamic headers based on available columns
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th>";
    if (in_array('status', $available_columns ?? [])) {
        echo "<th>Status</th>";
    }
    if (in_array('created_at', $available_columns ?? [])) {
        echo "<th>Created</th>";
    }
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['phone']}</td>";
        echo "<td><strong>{$row['role']}</strong></td>";
        if (isset($row['status'])) {
            echo "<td><strong>{$row['status']}</strong></td>";
        }
        if (isset($row['created_at'])) {
            echo "<td>{$row['created_at']}</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No admin users found!</p>";
}

// 2. Check login redirect logic
echo "<h4>Login Redirect Logic:</h4>";
echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; margin: 10px 0;'>";
echo "<p><strong>✅ Admin login redirect path:</strong> <code>./Admin/index.php</code></p>";
echo "<p><strong>✅ Passenger login redirect path:</strong> <code>./profile.html</code></p>";
echo "</div>";

// 3. Check admin dashboard
echo "<h4>Admin Dashboard Check:</h4>";
$admin_dashboard = __DIR__ . '/Admin/index.php';
if (file_exists($admin_dashboard)) {
    echo "<p>✅ <strong>Admin dashboard exists:</strong> Admin/index.php</p>";
    echo "<p>📄 <strong>File size:</strong> " . number_format(filesize($admin_dashboard)) . " bytes</p>";
} else {
    echo "<p>❌ <strong>Admin dashboard missing:</strong> Admin/index.php</p>";
}

// 4. Test login flow
echo "<h3>🎯 Expected Login Flow for Admin</h3>";
echo "<div style='background: #e8f4f8; padding: 15px; border: 1px solid #bee5eb; margin: 10px 0;'>";
echo "<ol>";
echo "<li><strong>Admin enters credentials</strong> in login form</li>";
echo "<li><strong>System verifies</strong> username/password against users table</li>";
echo "<li><strong>System checks role</strong> = 'admin'</li>";
echo "<li><strong>Session variables set:</strong>";
echo "<ul>";
echo "<li><code>\$_SESSION['user_id']</code> = Admin's user ID</li>";
echo "<li><code>\$_SESSION['username']</code> = Admin's name</li>";
echo "<li><code>\$_SESSION['role']</code> = 'admin'</li>";
echo "<li><code>\$_SESSION['email']</code> = Admin's email</li>";
echo "</ul></li>";
echo "<li><strong>JavaScript redirects</strong> to <code>./Admin/index.php</code></li>";
echo "<li><strong>Admin dashboard loads</strong> and auth.php validates session</li>";
echo "</ol>";
echo "</div>";

// 5. Troubleshooting
echo "<h3>🔧 Troubleshooting Steps</h3>";
echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h4>If admin login is not redirecting:</h4>";
echo "<ol>";
echo "<li><strong>Check console errors:</strong> Open browser dev tools (F12) and check for JavaScript errors</li>";
echo "<li><strong>Check network tab:</strong> Verify the login.php response contains correct redirect URL</li>";
echo "<li><strong>Check session:</strong> Ensure session variables are being set correctly</li>";
echo "<li><strong>Test direct access:</strong> Try accessing Admin/index.php directly after login</li>";
echo "</ol>";
echo "</div>";

// 6. Test buttons
echo "<h3>🧪 Test System</h3>";
echo "<p><a href='login.html' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔐 Test Admin Login</a></p>";
echo "<p><a href='test_admin_redirect.php' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Debug Login Flow</a></p>";
echo "<p><a href='Admin/index.php' target='_blank' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📊 Admin Dashboard (Direct)</a></p>";

echo "<h3>📋 Expected Admin Credentials</h3>";
echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<p><strong>Default Admin (if created by fix script):</strong></p>";
echo "<ul>";
echo "<li><strong>Email:</strong> admin@southrift.com</li>";
echo "<li><strong>Phone:</strong> 254700000000</li>";
echo "<li><strong>Password:</strong> admin123</li>";
echo "</ul>";
echo "<p><em>Use either email or phone as username</em></p>";
echo "</div>";

$conn->close();
?>