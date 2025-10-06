-- SQL script to add the 'station' column to the users table
-- This is required for the station-based booking filtering feature

-- Add the station column to the users table
ALTER TABLE users 
ADD COLUMN station VARCHAR(100) DEFAULT NULL AFTER role;

-- Verify the column was added successfully
DESCRIBE users;

-- Show the updated table structure
SHOW CREATE TABLE users;

-- Example of how to update existing admins with stations (optional)
-- UPDATE users SET station = 'Nairobi' WHERE id = 1 AND role = 'admin';

-- Verify the station column is working
SELECT id, name, email, role, station FROM users LIMIT 5;