<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Admin Login Redirect Test</h2>";

// Include database connection
require_once 'db.php';

echo "<h3>Current Setup Status</h3>";

// 1. Check if admin user exists
$admin_result = $conn->query("SELECT id, name, email, phone, role FROM users WHERE role = 'admin' LIMIT 1");

if ($admin_result && $admin_result->num_rows > 0) {
    $admin = $admin_result->fetch_assoc();
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>✅ Admin User Found</h4>";
    echo "<p><strong>Name:</strong> {$admin['name']}</p>";
    echo "<p><strong>Email:</strong> {$admin['email']}</p>";
    echo "<p><strong>Phone:</strong> {$admin['phone']}</p>";
    echo "<p><strong>Role:</strong> {$admin['role']}</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>❌ No Admin User Found</h4>";
    echo "<p>You need to create an admin user first.</p>";
    echo "</div>";
}

// 2. Check admin dashboard file
$dashboard_path = __DIR__ . '/Admin/index.php';
if (file_exists($dashboard_path)) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>✅ Admin Dashboard Exists</h4>";
    echo "<p><strong>Path:</strong> Admin/index.php</p>";
    echo "<p><strong>Size:</strong> " . number_format(filesize($dashboard_path)) . " bytes</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>❌ Admin Dashboard Missing</h4>";
    echo "<p>Admin/index.php file not found</p>";
    echo "</div>";
}

// 3. Show current login logic
echo "<h3>Current Login Redirect Logic</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; margin: 10px 0;'>";
echo "<p><strong>Admin redirect path:</strong> <code>./Admin/index.php</code></p>";
echo "<p><strong>Passenger redirect path:</strong> <code>./profile.html</code></p>";
echo "<p><strong>Driver redirect path:</strong> <code>Driver/index.php</code></p>";
echo "</div>";

// 4. Expected login flow
echo "<h3>Expected Admin Login Flow</h3>";
echo "<div style='background: #e8f4f8; padding: 15px; border: 1px solid #bee5eb; margin: 10px 0;'>";
echo "<ol>";
echo "<li>Admin enters credentials at <code>login.html</code></li>";
echo "<li>JavaScript sends POST request to <code>login.php</code></li>";
echo "<li>PHP validates credentials and checks role = 'admin'</li>";
echo "<li>PHP returns JSON: <code>{'success': true, 'redirect': './Admin/index.php'}</code></li>";
echo "<li>JavaScript redirects browser to <code>./Admin/index.php</code></li>";
echo "<li>Admin dashboard loads and <code>auth.php</code> validates session</li>";
echo "</ol>";
echo "</div>";

// 5. Test buttons
echo "<h3>Test System</h3>";
echo "<p><a href='login.html' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔐 Login Page</a></p>";

if ($admin_result && $admin_result->num_rows > 0) {
    echo "<p><a href='Admin/index.php' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>📊 Admin Dashboard (Direct)</a></p>";
}

// 6. Default credentials info
echo "<h3>Expected Admin Credentials</h3>";
echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<p><strong>Default Admin (if exists):</strong></p>";
echo "<ul>";
echo "<li><strong>Email:</strong> admin@southrift.com</li>";
echo "<li><strong>Phone:</strong> 254700000000</li>";
echo "<li><strong>Password:</strong> admin123</li>";
echo "</ul>";
echo "<p><em>Use either email or phone as username</em></p>";
echo "</div>";

// 7. Troubleshooting tips
echo "<h3>Troubleshooting Tips</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; margin: 10px 0;'>";
echo "<h4>If admin redirect is not working:</h4>";
echo "<ol>";
echo "<li><strong>Check browser console (F12)</strong> for JavaScript errors</li>";
echo "<li><strong>Check Network tab</strong> to see the response from login.php</li>";
echo "<li><strong>Verify session variables</strong> are set correctly</li>";
echo "<li><strong>Test direct dashboard access</strong> after successful login</li>";
echo "</ol>";
echo "</div>";

$conn->close();
?>