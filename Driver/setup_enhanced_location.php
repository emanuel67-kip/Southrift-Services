<?php
require_once '../db.php';

echo "<h1>Setting up Enhanced Location Sharing Database</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; }</style>";

// Create driver_share_sessions table
$sql1 = "
CREATE TABLE IF NOT EXISTS `driver_share_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `driver_id` INT NOT NULL,
    `token` VARCHAR(64) NOT NULL UNIQUE,
    `status` ENUM('active', 'stopped', 'expired') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (`driver_id`),
    INDEX idx_share_sessions_token (`token`, `status`, `expires_at`),
    FOREIGN KEY (`driver_id`) REFERENCES `drivers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

if ($conn->query($sql1)) {
    echo "<span class='success'>✅ Created driver_share_sessions table</span><br>";
} else {
    echo "<span class='error'>❌ Error creating driver_share_sessions: " . $conn->error . "</span><br>";
}

// Create session_passengers table
$sql2 = "
CREATE TABLE IF NOT EXISTS `session_passengers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `phone_number` VARCHAR(20) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `notified_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `message_status` ENUM('pending', 'sent', 'delivered', 'failed') DEFAULT 'pending',
    INDEX (`session_id`),
    INDEX (`user_id`),
    FOREIGN KEY (`session_id`) REFERENCES `driver_share_sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

if ($conn->query($sql2)) {
    echo "<span class='success'>✅ Created session_passengers table</span><br>";
} else {
    echo "<span class='error'>❌ Error creating session_passengers: " . $conn->error . "</span><br>";
}

// Check if we have the required tables
$required_tables = ['drivers', 'vehicles', 'bookings', 'driver_locations', 'notifications'];
foreach ($required_tables as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check->num_rows > 0) {
        echo "<span class='success'>✅ Table '$table' exists</span><br>";
    } else {
        echo "<span class='error'>❌ Table '$table' missing - please run setup_complete_database.php first</span><br>";
    }
}

echo "<h2>✅ Enhanced Location Sharing Setup Complete!</h2>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li><a href='debug_location_test.html'>Test JavaScript Functionality</a></li>";
echo "<li><a href='index.php'>Go to Driver Dashboard</a></li>";
echo "<li><a href='../track.php?token=test'>Test Tracking Page</a> (will show error - normal)</li>";
echo "</ul>";
?>