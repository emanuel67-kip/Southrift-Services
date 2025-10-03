-- Session Passengers Table for tracking which passengers are included in each sharing session
CREATE TABLE IF NOT EXISTS `session_passengers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `phone_number` VARCHAR(20) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `notified_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `message_status` ENUM('pending', 'sent', 'delivered', 'failed') DEFAULT 'pending',
    INDEX (`session_id`),
    INDEX (`user_id`),
    FOREIGN KEY (`session_id`) REFERENCES `driver_share_sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;