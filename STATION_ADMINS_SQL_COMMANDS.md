# SQL Commands for Adding Station Admins

## Overview
This document contains the SQL commands needed to add station admins to your Southrift Services database. Each admin is assigned to a specific station, which determines which bookings they can see in the admin dashboard.

## Prerequisites
Before adding station admins, you must first add the `station` column to the `users` table. This is a required step for the station-based booking filtering feature to work.

### Add Station Column to Users Table

```sql
-- Add the station column to the users table
ALTER TABLE users 
ADD COLUMN station VARCHAR(100) DEFAULT NULL AFTER role;
```

After running this command, you can proceed with adding the station admins.

## SQL Commands

### Add Admins for Each Station

```sql
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
```

### Verify Admins

```sql
-- Check all admins and their assigned stations
SELECT id, name, email, station, role, status 
FROM users 
WHERE role = 'admin' 
ORDER BY station;
```

### Update Existing Admins (if needed)

```sql
-- Update station assignments for existing admins
UPDATE users SET station = 'Litein' WHERE email = 'adminlitein@gmail.com';
UPDATE users SET station = 'Nairobi' WHERE email = 'adminnairobi@gmail.com';
UPDATE users SET station = 'Kisumu' WHERE email = 'adminkisumu@gmail.com';
UPDATE users SET station = 'Nakuru' WHERE email = 'adminnakuru@gmail.com';
UPDATE users SET station = 'Bomet' WHERE email = 'adminbomet@gmail.com';
```

### Reset Passwords for All Station Admins

```sql
-- Reset passwords for all station admins to the default password "password"
UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE role = 'admin' AND email LIKE 'admin%@gmail.com';
```

## Default Login Credentials

| Station | Email | Default Password |
|---------|-------|------------------|
| Litein | adminlitein@gmail.com | password |
| Nairobi | adminnairobi@gmail.com | password |
| Kisumu | adminkisumu@gmail.com | password |
| Nakuru | adminnakuru@gmail.com | password |
| Bomet | adminbomet@gmail.com | password |

## Important Notes

1. **Security Warning**: The default password is "password" for all newly created admins. Ensure each admin changes their password immediately after first login.

2. **Password Hash**: The password hash `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi` corresponds to the word "password".

3. **Phone Numbers**: The phone numbers used are placeholder values. Replace with actual phone numbers if needed.

4. **Testing**: After adding the admins, test the station-based filtering by:
   - Logging in as each admin
   - Creating bookings with different boarding points
   - Verifying that each admin only sees bookings from their assigned station

## Alternative: Using the PHP Scripts

Instead of running these SQL commands directly, you can use the provided PHP scripts:

1. `add_station_admins.php` - Adds the station admins to the database
2. `update_admin_passwords.php` - Updates all station admin passwords to the default
3. `test_station_filtering_with_sample_data.php` - Tests the filtering with sample data

Run these scripts by accessing them through your web browser:

```
http://localhost/southrift/add_station_admins.php
http://localhost/southrift/update_admin_passwords.php
http://localhost/southrift/test_station_filtering_with_sample_data.php
```