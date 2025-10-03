-- Migration script to update the existing bookings table to the new structure
-- This script will add the missing columns to the existing bookings table

-- First, check if the table exists and has the old structure
-- Then add the new columns

ALTER TABLE `bookings` 
ADD COLUMN `fullname` varchar(150) NOT NULL AFTER `user_id`,
ADD COLUMN `phone` varchar(20) NOT NULL AFTER `fullname`,
ADD COLUMN `route` varchar(100) NOT NULL AFTER `phone`,
ADD COLUMN `boarding_point` varchar(150) NOT NULL AFTER `route`,
ADD COLUMN `departure_time` varchar(20) NOT NULL AFTER `travel_date`,
ADD COLUMN `seats` int(11) NOT NULL AFTER `departure_time`,
ADD COLUMN `payment_method` enum('pay-onboarding','mpesa','card') NOT NULL AFTER `seats`,
ADD COLUMN `assigned_vehicle` varchar(20) DEFAULT NULL AFTER `payment_method`,
ADD COLUMN `google_maps_link` text DEFAULT NULL AFTER `assigned_vehicle`,
ADD COLUMN `shared_location_updated` timestamp NULL DEFAULT NULL AFTER `google_maps_link`;

-- Add amount column to store the calculated fare
ALTER TABLE `bookings` 
ADD COLUMN `amount` varchar(50) DEFAULT NULL AFTER `seats`;

-- Update the vehicle_id column to allow NULL values (as it might not be assigned immediately)
ALTER TABLE `bookings` 
MODIFY COLUMN `vehicle_id` INT NULL;

-- Add indexes for better performance
ALTER TABLE `bookings` 
ADD INDEX `idx_route` (`route`),
ADD INDEX `idx_travel_date` (`travel_date`),
ADD INDEX `idx_payment_method` (`payment_method`),
ADD INDEX `idx_assigned_vehicle` (`assigned_vehicle`);