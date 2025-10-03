<?php
// Test script for WhatsApp location sender
session_start();
require_once '../db.php';

// Set proper headers for debugging
header('Content-Type: application/json');

// Enable error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

echo json_encode([
    'status' => 'test_started',
    'session_id' => session_id(),
    'csrf_token_exists' => isset($_SESSION['csrf_token']),
    'database_connected' => isset($conn) && $conn instanceof mysqli,
    'mysql_error' => $conn ? $conn->error : 'No connection',
    'post_data' => $_POST,
    'test_driver_phone' => '0712345678',
    'test_coordinates' => ['lat' => -1.286389, 'lng' => 36.817223]
]);
?>