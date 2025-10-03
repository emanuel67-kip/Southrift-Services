-- Payments Table
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `transaction_id` VARCHAR(100),
    `payment_method` ENUM('cash', 'card', 'mobile_money') NOT NULL,
    `status` ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    `payment_details` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
