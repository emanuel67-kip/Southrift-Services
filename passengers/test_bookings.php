<?php
// Test script to check bookings table structure
require_once 'db.php';

echo "<h2>Bookings Table Test</h2>";

// Check if bookings table exists and show its structure
$table_check = $conn->query("SHOW TABLES LIKE 'bookings'");
if ($table_check->num_rows > 0) {
    echo "<p>Bookings table exists.</p>";
    
    // Show table structure
    $columns_result = $conn->query("SHOW COLUMNS FROM bookings");
    echo "<h3>Table Structure:</h3>";
    echo "<ul>";
    while ($column = $columns_result->fetch_assoc()) {
        echo "<li>" . $column['Field'] . " (" . $column['Type'] . ")</li>";
    }
    echo "</ul>";
    
    // Check if there are any bookings
    $bookings_result = $conn->query("SELECT COUNT(*) as count FROM bookings");
    $count = $bookings_result->fetch_assoc();
    echo "<p>Total bookings: " . $count['count'] . "</p>";
    
    // Show first booking if exists
    if ($count['count'] > 0) {
        $first_booking = $conn->query("SELECT * FROM bookings LIMIT 1");
        $booking = $first_booking->fetch_assoc();
        echo "<h3>Sample Booking Data:</h3>";
        echo "<pre>";
        print_r($booking);
        echo "</pre>";
    }
} else {
    echo "<p>Bookings table does not exist.</p>";
}

$conn->close();
?>