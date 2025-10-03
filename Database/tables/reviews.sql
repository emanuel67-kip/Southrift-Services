-- Reviews Table
CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `driver_id` INT NOT NULL,
    `rating` TINYINT(1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
    `comment` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`driver_id`) REFERENCES `drivers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
