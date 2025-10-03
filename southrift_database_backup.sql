-- ===================================================================
-- SOUTHRIFT SERVICES - COMPLETE DATABASE BACKUP
-- Created: 2025-09-16
-- Database: southrift
-- Description: Complete database structure and initial data
-- ===================================================================

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `southrift` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `southrift`;

-- Disable foreign key checks during creation
SET FOREIGN_KEY_CHECKS = 0;

-- ===================================================================
-- 1. USERS TABLE
-- ===================================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `phone` VARCHAR(20) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'driver', 'user', 'passenger') NOT NULL DEFAULT 'passenger',
    `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add initial admin user
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`, `status`) 
VALUES ('Admin', 'admin@southrift.com', '254700000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active')
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `password` = VALUES(`password`),
    `role` = VALUES(`role`),
    `status` = VALUES(`status`);

-- ===================================================================
-- 2. VEHICLES TABLE
-- ===================================================================
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

-- ===================================================================
-- 3. DRIVERS TABLE
-- ===================================================================
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

-- ===================================================================
-- 4. BOOKINGS TABLE
-- ===================================================================
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
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================================================
-- 5. DRIVER LOCATIONS TABLE
-- ===================================================================
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
    UNIQUE KEY `driver_id` (`driver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================================================
-- 6. DRIVER LOCATION HISTORY TABLE
-- ===================================================================
CREATE TABLE IF NOT EXISTS `driver_location_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `driver_id` INT NOT NULL,
    `latitude` DECIMAL(10, 8) NOT NULL,
    `longitude` DECIMAL(11, 8) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `driver_id` (`driver_id`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================================================
-- 7. DRIVER SHARE SESSIONS TABLE
-- ===================================================================
CREATE TABLE IF NOT EXISTS `driver_share_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `driver_id` INT NOT NULL,
    `token` VARCHAR(64) NOT NULL UNIQUE,
    `status` ENUM('active', 'stopped', 'expired') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NOT NULL,
    INDEX (`driver_id`),
    INDEX `idx_share_sessions_token` (`token`, `status`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================================================
-- 8. NOTIFICATIONS TABLE
-- ===================================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `driver_id` INT,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('booking', 'payment', 'system', 'promotion') NOT NULL,
    `is_read` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================================================
-- 9. PAYMENTS TABLE
-- ===================================================================
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `transaction_id` VARCHAR(100),
    `payment_method` ENUM('cash', 'card', 'mobile_money') NOT NULL,
    `status` ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    `payment_details` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================================================
-- 10. REVIEWS TABLE
-- ===================================================================
CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `driver_id` INT NOT NULL,
    `rating` TINYINT(1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
    `comment` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================================================
-- FOREIGN KEY CONSTRAINTS
-- ===================================================================

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Bookings foreign keys
ALTER TABLE `bookings` 
ADD CONSTRAINT `fk_bookings_user` 
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `bookings` 
ADD CONSTRAINT `fk_bookings_vehicle` 
    FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE;

-- Driver locations foreign keys
ALTER TABLE `driver_locations` 
ADD CONSTRAINT `fk_driver_locations_driver` 
    FOREIGN KEY (`driver_id`) REFERENCES `drivers`(`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE;

-- Driver location history foreign keys
ALTER TABLE `driver_location_history` 
ADD CONSTRAINT `fk_driver_location_history_driver` 
    FOREIGN KEY (`driver_id`) REFERENCES `drivers`(`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE;

-- Driver share sessions foreign keys
ALTER TABLE `driver_share_sessions` 
ADD CONSTRAINT `fk_driver_share_sessions_driver` 
    FOREIGN KEY (`driver_id`) REFERENCES `drivers`(`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE;

-- Notifications foreign keys
ALTER TABLE `notifications` 
ADD CONSTRAINT `fk_notifications_user` 
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `notifications` 
ADD CONSTRAINT `fk_notifications_driver` 
    FOREIGN KEY (`driver_id`) REFERENCES `drivers`(`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE;

-- Payments foreign keys
ALTER TABLE `payments` 
ADD CONSTRAINT `fk_payments_booking` 
    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE;

-- Reviews foreign keys
ALTER TABLE `reviews` 
ADD CONSTRAINT `fk_reviews_booking` 
    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `reviews` 
ADD CONSTRAINT `fk_reviews_user` 
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `reviews` 
ADD CONSTRAINT `fk_reviews_driver` 
    FOREIGN KEY (`driver_id`) REFERENCES `drivers`(`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE;

-- ===================================================================
-- ADDITIONAL INDEXES FOR PERFORMANCE
-- ===================================================================

-- Users table indexes
CREATE INDEX `idx_users_email` ON `users` (`email`);
CREATE INDEX `idx_users_phone` ON `users` (`phone`);
CREATE INDEX `idx_users_role` ON `users` (`role`);
CREATE INDEX `idx_users_status` ON `users` (`status`);

-- Vehicles table indexes
CREATE INDEX `idx_vehicles_route` ON `vehicles` (`route`);
CREATE INDEX `idx_vehicles_status` ON `vehicles` (`status`);
CREATE INDEX `idx_vehicles_driver_phone` ON `vehicles` (`driver_phone`);

-- Bookings table indexes
CREATE INDEX `idx_bookings_user_id` ON `bookings` (`user_id`);
CREATE INDEX `idx_bookings_vehicle_id` ON `bookings` (`vehicle_id`);
CREATE INDEX `idx_bookings_travel_date` ON `bookings` (`travel_date`);
CREATE INDEX `idx_bookings_status` ON `bookings` (`status`);
CREATE INDEX `idx_bookings_route` ON `bookings` (`route`);

-- Notifications table indexes
CREATE INDEX `idx_notifications_user_id` ON `notifications` (`user_id`);
CREATE INDEX `idx_notifications_driver_id` ON `notifications` (`driver_id`);
CREATE INDEX `idx_notifications_type` ON `notifications` (`type`);
CREATE INDEX `idx_notifications_is_read` ON `notifications` (`is_read`);

-- Payments table indexes
CREATE INDEX `idx_payments_booking_id` ON `payments` (`booking_id`);
CREATE INDEX `idx_payments_status` ON `payments` (`status`);
CREATE INDEX `idx_payments_method` ON `payments` (`payment_method`);
CREATE INDEX `idx_payments_transaction_id` ON `payments` (`transaction_id`);

-- Reviews table indexes
CREATE INDEX `idx_reviews_booking_id` ON `reviews` (`booking_id`);
CREATE INDEX `idx_reviews_user_id` ON `reviews` (`user_id`);
CREATE INDEX `idx_reviews_driver_id` ON `reviews` (`driver_id`);
CREATE INDEX `idx_reviews_rating` ON `reviews` (`rating`);

-- ===================================================================
-- TRIGGERS FOR AUTOMATIC UPDATES
-- ===================================================================

-- Update driver rating when new review is added
DELIMITER $$
CREATE TRIGGER `update_driver_rating` AFTER INSERT ON `reviews`
FOR EACH ROW
BEGIN
    UPDATE `drivers` 
    SET `rating` = (
        SELECT AVG(rating) 
        FROM `reviews` 
        WHERE `driver_id` = NEW.driver_id
    )
    WHERE `id` = NEW.driver_id;
END$$
DELIMITER ;

-- Update total rides count for drivers
DELIMITER $$
CREATE TRIGGER `update_driver_rides` AFTER UPDATE ON `bookings`
FOR EACH ROW
BEGIN
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        UPDATE `drivers` 
        SET `total_rides` = `total_rides` + 1 
        WHERE `id` = (
            SELECT `driver_id` 
            FROM `vehicles` 
            WHERE `id` = NEW.vehicle_id
        );
    END IF;
END$$
DELIMITER ;

-- ===================================================================
-- VIEWS FOR COMMON QUERIES
-- ===================================================================

-- View for complete booking information
CREATE VIEW `booking_details` AS
SELECT 
    b.id,
    b.booking_id,
    u.name as passenger_name,
    u.phone as passenger_phone,
    v.number_plate,
    v.type as vehicle_type,
    v.driver_name,
    v.driver_phone,
    b.route,
    b.boarding_point,
    b.travel_date,
    b.departure_time,
    b.seats,
    b.payment_method,
    b.status,
    b.created_at
FROM bookings b
JOIN users u ON b.user_id = u.id
JOIN vehicles v ON b.vehicle_id = v.id;

-- View for driver locations with driver info
CREATE VIEW `driver_location_view` AS
SELECT 
    dl.id,
    d.name as driver_name,
    d.driver_phone,
    d.number_plate,
    d.route,
    dl.latitude,
    dl.longitude,
    dl.status,
    dl.accuracy,
    dl.speed,
    dl.heading,
    dl.last_updated
FROM driver_locations dl
JOIN drivers d ON dl.driver_id = d.id;

-- ===================================================================
-- INITIAL SAMPLE DATA (OPTIONAL)
-- ===================================================================

-- Sample vehicles (uncomment if you want sample data)
/*
INSERT INTO `vehicles` (`number_plate`, `type`, `color`, `route`, `capacity`, `driver_name`, `driver_phone`, `owner_name`, `owner_phone`) VALUES
('KCA 001A', '14-Seater', 'White', 'Nairobi-Kisumu', 14, 'John Kamau', '254701234567', 'Mary Wanjiku', '254702345678'),
('KCB 002B', '11-Seater', 'Blue', 'Nairobi-Nakuru', 11, 'Peter Kiprotich', '254703456789', 'James Mwangi', '254704567890'),
('KCC 003C', '14-Seater', 'Green', 'Kisumu-Nairobi', 14, 'Grace Akinyi', '254705678901', 'David Kimani', '254706789012');
*/

-- ===================================================================
-- BACKUP COMPLETE
-- Database: southrift
-- Tables: 10
-- Views: 2
-- Triggers: 2
-- ===================================================================

SELECT 'Southrift Services Database Backup Complete!' as Status;