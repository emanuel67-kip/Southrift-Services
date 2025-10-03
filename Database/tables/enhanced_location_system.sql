-- Driver Share Sessions Table for tracking location sharing sessions
CREATE TABLE IF NOT EXISTS `driver_share_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `driver_id` INT NOT NULL,
    `session_token` VARCHAR(64) NOT NULL,
    `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `ended_at` TIMESTAMP NULL,
    `total_duration` INT NULL COMMENT 'Duration in minutes',
    `passengers_notified` INT DEFAULT 0,
    KEY `driver_id` (`driver_id`),
    KEY `started_at` (`started_at`),
    KEY `session_token` (`session_token`),
    FOREIGN KEY (`driver_id`) REFERENCES `drivers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tracks driver location sharing sessions';

-- Add missing columns to driver_locations table if they don't exist
ALTER TABLE `driver_locations` 
ADD COLUMN IF NOT EXISTS `share_token` VARCHAR(64) NULL AFTER `heading`,
ADD COLUMN IF NOT EXISTS `google_maps_link` TEXT NULL AFTER `share_token`;

-- Add index for share_token
ALTER TABLE `driver_locations` ADD INDEX IF NOT EXISTS `idx_share_token` (`share_token`);

-- Update notifications table to support location sharing notifications
ALTER TABLE `notifications` 
MODIFY COLUMN `type` ENUM('system', 'booking', 'payment', 'location_sharing', 'general') DEFAULT 'system';