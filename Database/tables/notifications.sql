-- Notifications Table
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `driver_id` INT,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('booking', 'payment', 'system', 'promotion') NOT NULL,
    `is_read` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`driver_id`) REFERENCES `drivers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
