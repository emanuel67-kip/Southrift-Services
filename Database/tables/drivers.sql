-- Drivers Table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add indexes for better performance
CREATE INDEX `idx_driver_phone` ON `drivers` (`driver_phone`);
CREATE INDEX `idx_number_plate` ON `drivers` (`number_plate`);
CREATE INDEX `idx_route` ON `drivers` (`route`);
CREATE INDEX `idx_status` ON `drivers` (`status`);
