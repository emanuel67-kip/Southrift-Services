-- Driver Locations Table
CREATE TABLE IF NOT EXISTS `driver_locations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `driver_id` INT NOT NULL,
    `latitude` DECIMAL(10, 8) NOT NULL,
    `longitude` DECIMAL(11, 8) NOT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `accuracy` DECIMAL(10, 2),
    `speed` DECIMAL(10, 2),
    `heading` DECIMAL(5, 2),
    `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `driver_id` (`driver_id`),
    SPATIAL INDEX(`latitude`, `longitude`),
    FOREIGN KEY (`driver_id`) REFERENCES `drivers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
