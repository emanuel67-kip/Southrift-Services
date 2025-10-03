<?php
// Database connection
require_once 'db.php';

// Check vehicles table
$result = $conn->query("SHOW COLUMNS FROM vehicles");
if ($result) {
    echo "Vehicles table columns:\n";
    while ($row = $result->fetch_assoc()) {
        echo "{$row['Field']} ({$row['Type']})\n";
    }
} else {
    echo "Error describing vehicles table: " . $conn->error . "\n";
}

// Check bookings table
$result = $conn->query("SHOW COLUMNS FROM bookings");
if ($result) {
    echo "\nBookings table columns:\n";
    while ($row = $result->fetch_assoc()) {
        echo "{$row['Field']} ({$row['Type']})\n";
    }
} else {
    echo "\nError describing bookings table: " . $conn->error . "\n";
}

// Show sample data from vehicles
$result = $conn->query("SELECT * FROM vehicles LIMIT 1");
if ($result && $result->num_rows > 0) {
    echo "\nSample vehicle data:\n";
    print_r($result->fetch_assoc());
} else {
    echo "\nNo data in vehicles table or error: " . $conn->error . "\n";
}
?>
