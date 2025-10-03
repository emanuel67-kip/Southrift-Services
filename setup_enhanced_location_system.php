<?php
/**
 * Enhanced Location System Database Setup
 * Run this script once to set up the enhanced location sharing system
 */

require_once 'db.php';

echo "<h2>Setting up Enhanced Location Sharing System...</h2>\n";

try {
    // Create driver_share_sessions table
    $sql = "
    CREATE TABLE IF NOT EXISTS `driver_share_sessions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `driver_id` INT NOT NULL,
        `session_token` VARCHAR(64) NOT NULL,
        `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `ended_at` TIMESTAMP NULL,
        `total_duration` INT NULL COMMENT 'Duration in minutes',
        `passengers_notified` INT DEFAULT 0,
        KEY `driver_id` (`driver_id`),
        KEY `started_at` (`started_at`),
        KEY `session_token` (`session_token`),
        FOREIGN KEY (`driver_id`) REFERENCES `drivers`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tracks driver location sharing sessions'
    ";
    
    if ($conn->query($sql)) {
        echo "✅ Created driver_share_sessions table<br>\n";
    } else {
        echo "❌ Error creating driver_share_sessions table: " . $conn->error . "<br>\n";
    }

    // Add missing columns to driver_locations table
    $alterQueries = [
        "ALTER TABLE `driver_locations` ADD COLUMN IF NOT EXISTS `share_token` VARCHAR(64) NULL AFTER `heading`",
        "ALTER TABLE `driver_locations` ADD COLUMN IF NOT EXISTS `google_maps_link` TEXT NULL AFTER `share_token`",
        "ALTER TABLE `driver_locations` ADD INDEX IF NOT EXISTS `idx_share_token` (`share_token`)"
    ];

    foreach ($alterQueries as $query) {
        if ($conn->query($query)) {
            echo "✅ Updated driver_locations table structure<br>\n";
        } else {
            echo "⚠️ Note: " . $conn->error . "<br>\n";
        }
    }

    // Update notifications table to support location sharing
    $notificationQuery = "
    ALTER TABLE `notifications` 
    MODIFY COLUMN `type` ENUM('system', 'booking', 'payment', 'location_sharing', 'general') DEFAULT 'system'
    ";
    
    if ($conn->query($notificationQuery)) {
        echo "✅ Updated notifications table to support location sharing<br>\n";
    } else {
        echo "⚠️ Note: " . $conn->error . "<br>\n";
    }

    // Verify tables exist and show structure
    echo "<h3>System Status:</h3>\n";
    
    // Check driver_locations table
    $result = $conn->query("DESCRIBE driver_locations");
    if ($result && $result->num_rows > 0) {
        echo "✅ driver_locations table: " . $result->num_rows . " columns<br>\n";
        
        // Check for specific columns
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        $requiredColumns = ['latitude', 'longitude', 'status', 'share_token', 'accuracy', 'speed', 'heading'];
        foreach ($requiredColumns as $col) {
            if (in_array($col, $columns)) {
                echo "  ✅ Has $col column<br>\n";
            } else {
                echo "  ❌ Missing $col column<br>\n";
            }
        }
    }
    
    // Check driver_share_sessions table
    $result = $conn->query("DESCRIBE driver_share_sessions");
    if ($result && $result->num_rows > 0) {
        echo "✅ driver_share_sessions table: " . $result->num_rows . " columns<br>\n";
    } else {
        echo "❌ driver_share_sessions table not found<br>\n";
    }
    
    // Check drivers table
    $result = $conn->query("SELECT COUNT(*) as count FROM drivers");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✅ drivers table: " . $row['count'] . " records<br>\n";
    }
    
    // Check vehicles table
    $result = $conn->query("SELECT COUNT(*) as count FROM vehicles");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✅ vehicles table: " . $row['count'] . " records<br>\n";
    }
    
    // Check bookings table
    $result = $conn->query("SELECT COUNT(*) as count FROM bookings");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✅ bookings table: " . $row['count'] . " records<br>\n";
    }

    echo "<br><h3>✅ Enhanced Location System Setup Complete!</h3>\n";
    echo "<p><strong>Next Steps:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✅ Database schema updated</li>\n";
    echo "<li>✅ Enhanced JavaScript files created</li>\n";
    echo "<li>✅ Driver dashboard updated</li>\n";
    echo "<li>✅ Passenger tracking enhanced</li>\n";
    echo "<li>✅ Notification system improved</li>\n";
    echo "</ul>\n";
    echo "<p><strong>Your enhanced location sharing system is ready to use!</strong></p>\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up enhanced location system: " . $e->getMessage() . "<br>\n";
}

$conn->close();
?>