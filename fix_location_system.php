<?php
require_once 'db.php';

echo "<h1>Fix Location Sharing System</h1>";

// Step 0: Check if driver_locations table exists, create if it doesn't
echo "<h2>Step 0: Checking/Creating driver_locations table</h2>";

$check_table = $conn->query("SHOW TABLES LIKE 'driver_locations'");
if ($check_table->num_rows == 0) {
    // Create the driver_locations table
    $create_table_sql = "
        CREATE TABLE IF NOT EXISTS `driver_locations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `driver_id` INT NOT NULL,
            `latitude` DECIMAL(10, 8) NOT NULL DEFAULT 0,
            `longitude` DECIMAL(11, 8) NOT NULL DEFAULT 0,
            `status` ENUM('active', 'inactive') DEFAULT 'active',
            `accuracy` DECIMAL(10, 2),
            `speed` DECIMAL(10, 2),
            `heading` DECIMAL(5, 2),
            `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `driver_id` (`driver_id`),
            INDEX `idx_status` (`status`),
            INDEX `idx_last_updated` (`last_updated`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    
    if ($conn->query($create_table_sql)) {
        echo "✅ Successfully created driver_locations table<br>";
    } else {
        echo "❌ Error creating driver_locations table: " . $conn->error . "<br>";
    }
} else {
    echo "✅ driver_locations table already exists<br>";
}

// Step 1: Add google_maps_link column to driver_locations table if it doesn't exist
echo "<h2>Step 1: Adding google_maps_link column to driver_locations table</h2>";

$check_column = $conn->query("SHOW COLUMNS FROM driver_locations LIKE 'google_maps_link'");
if ($check_column->num_rows == 0) {
    $add_column_sql = "ALTER TABLE driver_locations ADD COLUMN google_maps_link TEXT NULL AFTER status";
    if ($conn->query($add_column_sql)) {
        echo "✅ Successfully added google_maps_link column to driver_locations table<br>";
    } else {
        echo "❌ Error adding google_maps_link column: " . $conn->error . "<br>";
    }
} else {
    echo "✅ google_maps_link column already exists<br>";
}

// Step 2: Add share_token column to driver_locations table if it doesn't exist
echo "<h2>Step 2: Adding share_token column to driver_locations table</h2>";

$check_token_column = $conn->query("SHOW COLUMNS FROM driver_locations LIKE 'share_token'");
if ($check_token_column->num_rows == 0) {
    $add_token_sql = "ALTER TABLE driver_locations ADD COLUMN share_token VARCHAR(64) NULL AFTER google_maps_link";
    if ($conn->query($add_token_sql)) {
        echo "✅ Successfully added share_token column to driver_locations table<br>";
    } else {
        echo "❌ Error adding share_token column: " . $conn->error . "<br>";
    }
} else {
    echo "✅ share_token column already exists<br>";
}

// Step 3: Verify driver_locations table structure
echo "<h2>Step 3: Current driver_locations table structure</h2>";
$structure = $conn->query("DESCRIBE driver_locations");
if ($structure) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Step 4: Add foreign key constraint if it doesn't exist
echo "<h2>Step 4: Adding foreign key constraints</h2>";

// Check if foreign key constraint exists
$check_fk = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = 'southrift' AND TABLE_NAME = 'driver_locations' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");

if ($check_fk->num_rows == 0) {
    // Add foreign key constraint
    $add_fk_sql = "ALTER TABLE driver_locations ADD CONSTRAINT fk_driver_locations_driver FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE ON UPDATE CASCADE";
    if ($conn->query($add_fk_sql)) {
        echo "✅ Successfully added foreign key constraint<br>";
    } else {
        echo "⚠️ Warning: Could not add foreign key constraint (this is OK if drivers table doesn't exist yet): " . $conn->error . "<br>";
    }
} else {
    echo "✅ Foreign key constraint already exists<br>";
}

// Step 5: Check current drivers with phone numbers
echo "<h2>Step 5: Current drivers in database</h2>";
$drivers = $conn->query("SELECT id, name, driver_phone, email FROM drivers LIMIT 5");
if ($drivers && $drivers->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Phone</th><th>Email</th></tr>";
    while ($row = $drivers->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['driver_phone']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No drivers found.<br>";
}

echo "<h2>✅ Database fixes completed!</h2>";
echo "<p><a href='Driver/index.php'>Go to Driver Dashboard</a> | <a href='Driver/share_location_new.php'>Test Location Sharing</a></p>";
?>