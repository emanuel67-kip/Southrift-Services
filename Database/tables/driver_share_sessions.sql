-- Driver Share Sessions Table
CREATE TABLE IF NOT EXISTS `driver_share_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `driver_id` INT NOT NULL,
    `token` VARCHAR(64) NOT NULL UNIQUE,
    `status` ENUM('active', 'stopped', 'expired') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NOT NULL,
    INDEX (`driver_id`),
    INDEX idx_share_sessions_token ON `driver_share_sessions`(`token`, `status`, `expires_at`),
    FOREIGN KEY (`driver_id`) REFERENCES `drivers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
