-- SQL script to add station admins for Southrift Services
-- This script adds admins for each station with appropriate station assignments

-- Add admin for Litein station
INSERT INTO users (name, email, phone, password, role, station, status) 
VALUES (
    'Litein Admin', 
    'adminlitein@gmail.com', 
    '254700000001', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'admin', 
    'Litein', 
    'active'
);

-- Add admin for Nairobi station
INSERT INTO users (name, email, phone, password, role, station, status) 
VALUES (
    'Nairobi Admin', 
    'adminnairobi@gmail.com', 
    '254700000002', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'admin', 
    'Nairobi', 
    'active'
);

-- Add admin for Kisumu station
INSERT INTO users (name, email, phone, password, role, station, status) 
VALUES (
    'Kisumu Admin', 
    'adminkisumu@gmail.com', 
    '254700000003', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'admin', 
    'Kisumu', 
    'active'
);

-- Add admin for Nakuru station
INSERT INTO users (name, email, phone, password, role, station, status) 
VALUES (
    'Nakuru Admin', 
    'adminnakuru@gmail.com', 
    '254700000004', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'admin', 
    'Nakuru', 
    'active'
);

-- Add admin for Bomet station
INSERT INTO users (name, email, phone, password, role, station, status) 
VALUES (
    'Bomet Admin', 
    'adminbomet@gmail.com', 
    '254700000005', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'admin', 
    'Bomet', 
    'active'
);

-- Verify the inserted admins
SELECT id, name, email, station, role, status FROM users WHERE role = 'admin' ORDER BY station;

-- Sample update statements (in case you need to update existing admins)
-- UPDATE users SET station = 'Litein' WHERE email = 'adminlitein@gmail.com';
-- UPDATE users SET station = 'Nairobi' WHERE email = 'adminnairobi@gmail.com';
-- UPDATE users SET station = 'Kisumu' WHERE email = 'adminkisumu@gmail.com';
-- UPDATE users SET station = 'Nakuru' WHERE email = 'adminnakuru@gmail.com';
-- UPDATE users SET station = 'Bomet' WHERE email = 'adminbomet@gmail.com';