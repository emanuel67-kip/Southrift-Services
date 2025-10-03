-- Vehicles Table
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

