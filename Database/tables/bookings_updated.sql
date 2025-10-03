-- Updated Bookings Table with all required fields
CREATE TABLE IF NOT EXISTS `bookings` (
  `booking_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `route` varchar(100) NOT NULL,
  `boarding_point` varchar(150) NOT NULL,
  `travel_date` date NOT NULL,
  `departure_time` varchar(20) NOT NULL,
  `seats` int(11) NOT NULL,
  `payment_method` enum('pay-onboarding','mpesa','card') NOT NULL,
  `assigned_vehicle` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `google_maps_link` text DEFAULT NULL,
  `shared_location_updated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`booking_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;