<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB config
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'southrift';

// Connect to database
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get submitted form data
$fullname = $_POST['fullname'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$role = $_POST['role'] ?? '';
$message = $_POST['message'] ?? '';

// Validate required fields
if (empty($fullname) || empty($email) || empty($phone) || empty($role)) {
    echo "<script>alert('Please fill all required fields.'); window.history.back();</script>";
    exit;
}

// Insert into database
$sql = "INSERT INTO join_requests (fullname, email, phone, role, message) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "<script>alert('Database error'); window.history.back();</script>";
    exit;
}

$stmt->bind_param("sssss", $fullname, $email, $phone, $role, $message);
if ($stmt->execute()) {
    echo "<script>alert('Thank you! Your application has been submitted successfully.'); window.location.href='../index.php';</script>";
} else {
    echo "<script>alert('Submission failed. Please try again.'); window.history.back();</script>";
}

$conn->close();
?>
