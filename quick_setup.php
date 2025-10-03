<?php
// Simple database setup for location sharing system
require_once 'db.php';

echo "<h2>Database Setup for Location Sharing</h2>";

try {
    // 1. Ensure driver_share_sessions table exists
    $sql = "
    CREATE TABLE IF NOT EXISTS `driver_share_sessions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `driver_id` INT NOT NULL,
        `session_token` VARCHAR(64) NOT NULL,
        `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `ended_at` TIMESTAMP NULL,
        `total_duration` INT NULL,
        `passengers_notified` INT DEFAULT 0,
        KEY `driver_id` (`driver_id`),
        KEY `started_at` (`started_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    
    if ($conn->query($sql)) {
        echo "✅ driver_share_sessions table ready<br>";
    } else {
        echo "❌ Error with driver_share_sessions: " . $conn->error . "<br>";
    }

    // 2. Add missing columns to driver_locations
    $columns_to_add = [
        "ALTER TABLE `driver_locations` ADD COLUMN `share_token` VARCHAR(64) NULL",
        "ALTER TABLE `driver_locations` ADD COLUMN `google_maps_link` TEXT NULL"
    ];

    foreach ($columns_to_add as $sql) {
        $result = $conn->query($sql);
        if ($result) {
            echo "✅ Added column successfully<br>";
        } else {
            if (strpos($conn->error, 'Duplicate column') !== false) {
                echo "✅ Column already exists<br>";
            } else {
                echo "⚠️ Column addition issue: " . $conn->error . "<br>";
            }
        }
    }

    // 3. Check tables structure
    echo "<h3>Table Status:</h3>";
    
    $tables = ['drivers', 'driver_locations', 'driver_share_sessions', 'notifications', 'bookings', 'vehicles'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "✅ $table exists<br>";
        } else {
            echo "❌ $table missing<br>";
        }
    }

    echo "<br><strong>Setup complete! You can now test the location sharing system.</strong><br>";
    echo "<a href='Driver/index.php'>Go to Driver Dashboard</a><br>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>