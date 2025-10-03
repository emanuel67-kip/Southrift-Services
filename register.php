<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'southrift';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Collect input
$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$password_raw = $_POST['password'];
$role = 'passenger'; // ✅ Automatically assign passenger role

// Hash password
$password_hashed = password_hash($password_raw, PASSWORD_DEFAULT);

// Insert user
$sql = "INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("sssss", $name, $email, $phone, $password_hashed, $role);

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful! Please log in.'); window.location.href='login.html';</script>";
    } else {
        echo "<script>alert('Registration failed. Try again.'); window.history.back();</script>";
    }

    $stmt->close();
} else {
    echo "<script>alert('Failed to prepare statement.'); window.history.back();</script>";
}

$conn->close();
?>
