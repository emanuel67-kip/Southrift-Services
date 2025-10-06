<?php
require_once 'db.php';

// Array of station admins to add
$stationAdmins = [
    [
        'name' => 'Litein Admin',
        'email' => 'adminlitein@gmail.com',
        'phone' => '254700000001',
        'station' => 'Litein'
    ],
    [
        'name' => 'Nairobi Admin',
        'email' => 'adminnairobi@gmail.com',
        'phone' => '254700000002',
        'station' => 'Nairobi'
    ],
    [
        'name' => 'Kisumu Admin',
        'email' => 'adminkisumu@gmail.com',
        'phone' => '254700000003',
        'station' => 'Kisumu'
    ],
    [
        'name' => 'Nakuru Admin',
        'email' => 'adminnakuru@gmail.com',
        'phone' => '254700000004',
        'station' => 'Nakuru'
    ],
    [
        'name' => 'Bomet Admin',
        'email' => 'adminbomet@gmail.com',
        'phone' => '254700000005',
        'station' => 'Bomet'
    ]
];

echo "<h2>Adding Station Admins to Database</h2>";

$successCount = 0;
$errorCount = 0;

foreach ($stationAdmins as $admin) {
    // Check if admin already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $admin['email']);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<p>⚠️ Admin with email {$admin['email']} already exists. Skipping...</p>";
        $checkStmt->close();
        continue;
    }
    $checkStmt->close();
    
    // Insert new admin
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, station, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $name = $admin['name'];
    $email = $admin['email'];
    $phone = $admin['phone'];
    $password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // Default password
    $role = 'admin';
    $station = $admin['station'];
    $status = 'active';
    
    $stmt->bind_param("sssssss", $name, $email, $phone, $password, $role, $station, $status);
    
    if ($stmt->execute()) {
        echo "<p>✅ Successfully added {$admin['name']} ({$admin['email']}) for {$admin['station']} station</p>";
        $successCount++;
    } else {
        echo "<p>❌ Failed to add {$admin['name']}: " . $stmt->error . "</p>";
        $errorCount++;
    }
    
    $stmt->close();
}

echo "<h3>Summary</h3>";
echo "<p>✅ Successfully added: $successCount admins</p>";
echo "<p>❌ Errors: $errorCount</p>";

// Display all current admins
echo "<h3>Current Admin List</h3>";
$result = $conn->query("SELECT id, name, email, station, status FROM users WHERE role = 'admin' ORDER BY station");
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='8' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Station</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['station'] ?? 'Not assigned') . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No admins found.</p>";
}

$conn->close();

echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>Log in as each admin using the default password</li>";
echo "<li>Change the default password immediately after first login</li>";
echo "<li>Test the station-based filtering by creating bookings from different stations</li>";
echo "</ol>";

echo "<p><strong>Default Password:</strong> password (for all newly created admins)</p>";
echo "<p><strong>Note:</strong> The password hash used is for the word 'password'. Please ensure admins change their passwords after first login.</p>";
?>