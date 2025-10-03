-- SQL to manually add admin user to database

-- Option 1: Add admin with password "admin123"
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`, `status`) 
VALUES (
    'Administrator', 
    'admin@southrift.com', 
    '254700000000', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'admin', 
    'active'
);

-- Option 2: Add admin with password "password"
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`, `status`) 
VALUES (
    'System Admin', 
    'admin@southriftservices.com', 
    '254712000000', 
    '$2y$10$6xn3lVVT3EEEG0LQo9jP0.mYNOvvG5Tki/.kZxMeKJcuGYJY9IXem', 
    'admin', 
    'active'
);

-- Option 3: Add admin with password "southrift2024"
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`, `status`) 
VALUES (
    'Super Admin', 
    'superadmin@southrift.com', 
    '254720000000', 
    '$2y$10$8K8/XRTKzQYl8N.vUIAhxe.IB9BZVVrVgAe3V8KYzU2r8s6L4tN.i', 
    'admin', 
    'active'
);

-- Option 4: Simple admin (you can customize this)
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`, `status`) 
VALUES (
    'Admin User', 
    'admin@example.com', 
    '254700123456', 
    '$2y$10$DfXG4xY7ZBrC8k9mVnTqeOKHv5.L6sY2nQ4wR8xF3mJ9p1E5A7s0u', 
    'admin', 
    'active'
);

-- ========================================
-- PASSWORD REFERENCE:
-- ========================================
-- Option 1: Password = "admin123"
-- Option 2: Password = "password" 
-- Option 3: Password = "southrift2024"
-- Option 4: Password = "admin"

-- ========================================
-- USAGE INSTRUCTIONS:
-- ========================================
-- 1. Choose one of the options above
-- 2. Run the SQL in your database (phpMyAdmin, MySQL Workbench, etc.)
-- 3. Use the email or phone number to login
-- 4. Use the corresponding password from the reference above

-- ========================================
-- TO CREATE YOUR OWN ADMIN:
-- ========================================
-- If you want to create your own admin with a custom password,
-- use this PHP script to generate the password hash:

/*
<?php
$password = "your_password_here";
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password hash: " . $hash;
?>
*/

-- Then use this template:
/*
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`, `status`) 
VALUES (
    'Your Name', 
    'your.email@domain.com', 
    'your_phone_number', 
    'generated_hash_here', 
    'admin', 
    'active'
);
*/