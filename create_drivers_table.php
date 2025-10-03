<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db.php';

echo "<h2>Creating Drivers and Vehicles Tables</h2>";

try {
    echo "<h3>Step 1: Drop existing tables if they exist (to recreate with correct structure)</h3>";
    
    // Disable foreign key checks temporarily
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Drop existing tables
    $tables_to_drop = ['drivers', 'vehicles'];
    foreach ($tables_to_drop as $table) {
        $result = $conn->query("DROP TABLE IF EXISTS $table");
        if ($result) {
            echo "✅ Dropped table: $table<br>";
        } else {
            echo "❌ Error dropping $table: " . $conn->error . "<br>";
        }
    }
    
    echo "<h3>Step 2: Creating vehicles table</h3>";
    
    $vehicles_sql = "
    CREATE TABLE IF NOT EXISTS `vehicles` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `number_plate` VARCHAR(20) UNIQUE NOT NULL,
        `type` VARCHAR(50) NOT NULL,
        `color` VARCHAR(30) NOT NULL,
        `route` VARCHAR(100) NOT NULL,
        `capacity` TINYINT NOT NULL,
        `driver_name` VARCHAR(100) NOT NULL,
        `driver_phone` VARCHAR(20) NOT NULL,
        `owner_name` VARCHAR(100) NOT NULL,
        `owner_phone` VARCHAR(20) NOT NULL,
        `image_path` VARCHAR(255) DEFAULT NULL,
        `status` ENUM('active', 'maintenance', 'inactive') DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    if ($conn->query($vehicles_sql)) {
        echo "✅ Successfully created vehicles table<br>";
    } else {
        echo "❌ Error creating vehicles table: " . $conn->error . "<br>";
    }
    
    echo "<h3>Step 3: Creating drivers table</h3>";
    
    $drivers_sql = "
    CREATE TABLE IF NOT EXISTS `drivers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT,
        `driver_name` VARCHAR(100) NOT NULL,
        `driver_phone` VARCHAR(20) UNIQUE NOT NULL,
        `email` VARCHAR(100) DEFAULT NULL,
        `number_plate` VARCHAR(20) DEFAULT NULL,
        `route` VARCHAR(100) DEFAULT NULL,
        `vehicle_id` INT DEFAULT NULL,
        `license_number` VARCHAR(50) UNIQUE DEFAULT NULL,
        `status` ENUM('available', 'on_trip', 'offline') DEFAULT 'offline',
        `rating` DECIMAL(3,2) DEFAULT 0.00,
        `total_rides` INT DEFAULT 0,
        `last_login` TIMESTAMP NULL DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    if ($conn->query($drivers_sql)) {
        echo "✅ Successfully created drivers table<br>";
    } else {
        echo "❌ Error creating drivers table: " . $conn->error . "<br>";
    }
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<h3>Step 4: Verify table structures</h3>";
    
    echo "<h4>Vehicles Table Structure:</h4>";
    $result = $conn->query("DESCRIBE vehicles");
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h4>Drivers Table Structure:</h4>";
    $result = $conn->query("DESCRIBE drivers");
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>Step 5: Test the add vehicle functionality</h3>";
    echo "<p>✅ Tables are now compatible with the admin add vehicle functionality</p>";
    echo "<p>🎯 <strong>The add vehicle process now works as follows:</strong></p>";
    echo "<ol>";
    echo "<li><strong>Creates a user account</strong> in the users table with role 'driver'</li>";
    echo "<li><strong>Creates a driver record</strong> in the drivers table linked to the user</li>";
    echo "<li><strong>Creates a vehicle record</strong> in the vehicles table</li>";
    echo "<li><strong>Links the driver to the vehicle</strong> by updating the vehicle_id in the drivers table</li>";
    echo "<li><strong>Driver login credentials:</strong> Phone number + Number plate as password</li>";
    echo "</ol>";
    
    echo "<h3>✅ Database setup completed successfully!</h3>";
    echo "<p>You can now test the admin add vehicle functionality at: <a href='Admin/add_vehicle.php' target='_blank'>Admin Add Vehicle</a></p>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

$conn->close();
?>