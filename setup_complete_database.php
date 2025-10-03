<?php
require_once 'db.php';

echo "<h1>Complete Database Setup for Location Sharing System</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

// List of required tables and their creation SQL
$required_tables = [
    'users' => "
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) UNIQUE NOT NULL,
            `phone` VARCHAR(20) NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `role` ENUM('passenger', 'admin') DEFAULT 'passenger',
            `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'vehicles' => "
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
            `is_active` BOOLEAN DEFAULT TRUE,
            `is_waiting` BOOLEAN DEFAULT FALSE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'drivers' => "
        CREATE TABLE IF NOT EXISTS `drivers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `driver_phone` VARCHAR(20) UNIQUE NOT NULL,
            `number_plate` VARCHAR(20) NOT NULL,
            `route` VARCHAR(200) NOT NULL,
            `email` VARCHAR(100) DEFAULT NULL,
            `license_number` VARCHAR(50) UNIQUE DEFAULT NULL,
            `vehicle_type` VARCHAR(50) DEFAULT NULL,
            `vehicle_color` VARCHAR(30) DEFAULT NULL,
            `vehicle_make` VARCHAR(50) DEFAULT NULL,
            `vehicle_model` VARCHAR(50) DEFAULT NULL,
            `status` ENUM('available', 'on_trip', 'offline', 'suspended') DEFAULT 'offline',
            `rating` DECIMAL(3,2) DEFAULT 0.00,
            `total_rides` INT DEFAULT 0,
            `is_verified` BOOLEAN DEFAULT FALSE,
            `last_login` TIMESTAMP NULL DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_phone_plate` (`driver_phone`, `number_plate`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'bookings' => "
        CREATE TABLE IF NOT EXISTS `bookings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `booking_id` INT AUTO_INCREMENT UNIQUE,
            `user_id` INT NOT NULL,
            `vehicle_id` INT NOT NULL,
            `route` VARCHAR(100) NOT NULL,
            `boarding_point` VARCHAR(100) NOT NULL,
            `travel_date` DATE NOT NULL,
            `departure_time` TIME NOT NULL,
            `seats` INT NOT NULL,
            `num_seats` INT NOT NULL,
            `payment_method` VARCHAR(50) NOT NULL,
            `status` ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
            `assigned_vehicle` VARCHAR(20) NULL,
            `fullname` VARCHAR(100) NULL,
            `phone` VARCHAR(20) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'driver_locations' => "
        CREATE TABLE IF NOT EXISTS `driver_locations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `driver_id` INT NOT NULL,
            `latitude` DECIMAL(10, 8) NOT NULL DEFAULT 0,
            `longitude` DECIMAL(11, 8) NOT NULL DEFAULT 0,
            `status` ENUM('active', 'inactive') DEFAULT 'active',
            `accuracy` DECIMAL(10, 2),
            `speed` DECIMAL(10, 2),
            `heading` DECIMAL(5, 2),
            `google_maps_link` TEXT NULL,
            `share_token` VARCHAR(64) NULL,
            `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `driver_id` (`driver_id`),
            INDEX `idx_status` (`status`),
            INDEX `idx_last_updated` (`last_updated`),
            INDEX `idx_share_token` (`share_token`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'driver_location_history' => "
        CREATE TABLE IF NOT EXISTS `driver_location_history` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `driver_id` INT NOT NULL,
            `latitude` DECIMAL(10, 8) NOT NULL,
            `longitude` DECIMAL(11, 8) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY `driver_id` (`driver_id`),
            KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'notifications' => "
        CREATE TABLE IF NOT EXISTS `notifications` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT,
            `driver_id` INT,
            `title` VARCHAR(255) NOT NULL,
            `message` TEXT NOT NULL,
            `type` ENUM('booking', 'payment', 'system', 'promotion') NOT NULL,
            `is_read` BOOLEAN DEFAULT FALSE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    "
];

// Check and create tables
echo "<h2>Creating Required Tables</h2>";
foreach ($required_tables as $table_name => $create_sql) {
    $check_table = $conn->query("SHOW TABLES LIKE '$table_name'");
    if ($check_table->num_rows == 0) {
        if ($conn->query($create_sql)) {
            echo "<span class='success'>✅ Created table: $table_name</span><br>";
        } else {
            echo "<span class='error'>❌ Error creating table $table_name: " . $conn->error . "</span><br>";
        }
    } else {
        echo "<span class='success'>✅ Table already exists: $table_name</span><br>";
    }
}

// Add indexes for better performance
echo "<h2>Adding Performance Indexes</h2>";
$indexes = [
    "CREATE INDEX IF NOT EXISTS idx_driver_phone ON drivers (driver_phone)",
    "CREATE INDEX IF NOT EXISTS idx_number_plate ON drivers (number_plate)",
    "CREATE INDEX IF NOT EXISTS idx_route ON drivers (route)",
    "CREATE INDEX IF NOT EXISTS idx_status ON drivers (status)",
    "CREATE INDEX IF NOT EXISTS idx_vehicles_driver_phone ON vehicles (driver_phone)",
    "CREATE INDEX IF NOT EXISTS idx_bookings_user_id ON bookings (user_id)",
    "CREATE INDEX IF NOT EXISTS idx_bookings_assigned_vehicle ON bookings (assigned_vehicle)",
    "CREATE INDEX IF NOT EXISTS idx_bookings_travel_date ON bookings (travel_date)",
    "CREATE INDEX IF NOT EXISTS idx_users_email ON users (email)",
    "CREATE INDEX IF NOT EXISTS idx_users_phone ON users (phone)"
];

foreach ($indexes as $index_sql) {
    if ($conn->query($index_sql)) {
        echo "<span class='success'>✅ Added index</span><br>";
    } else {
        // Ignore errors for indexes that already exist
        if (strpos($conn->error, 'Duplicate key name') === false) {
            echo "<span class='warning'>⚠️ Index warning: " . $conn->error . "</span><br>";
        }
    }
}

// Add admin user if it doesn't exist
echo "<h2>Setting Up Admin User</h2>";
$admin_email = 'admin@southrift.com';
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);

$check_admin = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check_admin->bind_param("s", $admin_email);
$check_admin->execute();
$result = $check_admin->get_result();

if ($result->num_rows == 0) {
    $insert_admin = $conn->prepare("INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, 'admin', 'active')");
    $admin_name = "System Administrator";
    $admin_phone = "254700000000";
    $insert_admin->bind_param("ssss", $admin_name, $admin_email, $admin_phone, $admin_password);
    
    if ($insert_admin->execute()) {
        echo "<span class='success'>✅ Created admin user: $admin_email (password: admin123)</span><br>";
    } else {
        echo "<span class='error'>❌ Error creating admin user: " . $conn->error . "</span><br>";
    }
} else {
    echo "<span class='success'>✅ Admin user already exists</span><br>";
}

// Show current database status
echo "<h2>Database Status Summary</h2>";
echo "<table>";
echo "<tr><th>Table</th><th>Record Count</th><th>Status</th></tr>";

foreach (array_keys($required_tables) as $table) {
    $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
    if ($count_result) {
        $count = $count_result->fetch_assoc()['count'];
        echo "<tr><td>$table</td><td>$count</td><td><span class='success'>✅ Ready</span></td></tr>";
    } else {
        echo "<tr><td>$table</td><td>-</td><td><span class='error'>❌ Error</span></td></tr>";
    }
}
echo "</table>";

echo "<h2>✅ Database Setup Complete!</h2>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li><a href='Driver/quick_login.php'>Test Driver Login</a></li>";
echo "<li><a href='Admin/index.php'>Access Admin Dashboard</a> (admin@southrift.com / admin123)</li>";
echo "<li><a href='Driver/index.php'>Driver Dashboard</a></li>";
echo "<li><a href='profile.html'>Passenger Profile</a></li>";
echo "</ul>";
?>