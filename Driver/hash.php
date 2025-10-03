<?php
$password = 'admin123'; // Replace with your actual password

// Hash the password using bcrypt
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Output the hashed password
echo "Hashed password: " . $hashedPassword;
?>
