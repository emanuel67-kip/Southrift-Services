-- Southrift Services Database Installation
-- This script creates the database and all required tables

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `southrift` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE `southrift`;

-- Disable foreign key checks temporarily to avoid reference issues
SET FOREIGN_KEY_CHECKS = 0;

-- Import all table creation scripts in the correct order
-- 1. Users table first (no dependencies)
SOURCE tables/users.sql;

-- 2. Drivers table (depends on users)
SOURCE tables/drivers.sql;

-- 3. Vehicles table (depends on drivers)
SOURCE tables/vehicles.sql;

-- 4. Bookings table (depends on users, drivers, and vehicles)
SOURCE tables/bookings.sql;

-- 5. Driver locations (depends on drivers)
SOURCE tables/driver_locations.sql;

-- 6. Driver location history (depends on drivers)
SOURCE tables/driver_location_history.sql;

-- 7. Driver share sessions (depends on drivers)
SOURCE tables/driver_share_sessions.sql;

-- 8. Payments (depends on bookings)
SOURCE tables/payments.sql;

-- 9. Reviews (depends on bookings, users, and drivers)
SOURCE tables/reviews.sql;

-- 10. Notifications (depends on users and drivers)
SOURCE tables/notifications.sql;

-- 11. Add foreign key constraints after all tables are created
SOURCE tables/add_foreign_keys.sql;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Display success message
SELECT 'Database and tables created successfully!' AS message;
