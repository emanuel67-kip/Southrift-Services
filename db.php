<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/db_errors.log');

// Database configuration
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'southrift';

error_log("Attempting to connect to database: $database on $host");

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    $error_msg = 'Database connection failed: ' . $conn->connect_error;
    error_log($error_msg);
    die(json_encode(['success' => false, 'error' => $error_msg]));
}

error_log("Successfully connected to database: $database");

// Set charset
if (!$conn->set_charset('utf8mb4')) {
    $error_msg = 'Error setting charset: ' . $conn->error;
    error_log($error_msg);
    die(json_encode(['success' => false, 'error' => $error_msg]));
}
?>
