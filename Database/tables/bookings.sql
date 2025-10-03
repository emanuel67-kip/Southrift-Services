-- Bookings Table
CREATE TABLE IF NOT EXISTS `bookings` (
    `booking_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `vehicle_id` INT NOT NULL,
    `travel_date` DATE NOT NULL,
    `departure_time` TIME NOT NULL,
    `num_seats` INT NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
    FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
