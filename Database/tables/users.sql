-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `phone` VARCHAR(20) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'driver', 'user', 'passenger') NOT NULL DEFAULT 'passenger',
    `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add initial admin user
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`, `status`) 
VALUES ('Admin', 'admin@southrift.com', '254700000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');
