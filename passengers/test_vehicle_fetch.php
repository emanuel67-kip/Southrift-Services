<?php
// Test script to check vehicle fetching
require_once 'db.php';

echo "<h2>Vehicle Fetch Test</h2>";

// Check if vehicles table exists and show its structure
$table_check = $conn->query("SHOW TABLES LIKE 'vehicles'");
if ($table_check->num_rows > 0) {
    echo "<p>Vehicles table exists.</p>";
    
    // Show table structure
    $columns_result = $conn->query("SHOW COLUMNS FROM vehicles");
    echo "<h3>Vehicles Table Structure:</h3>";
    echo "<ul>";
    while ($column = $columns_result->fetch_assoc()) {
        echo "<li>" . $column['Field'] . " (" . $column['Type'] . ")</li>";
    }
    echo "</ul>";
    
    // Check if there are any vehicles
    $vehicles_result = $conn->query("SELECT COUNT(*) as count FROM vehicles");
    $count = $vehicles_result->fetch_assoc();
    echo "<p>Total vehicles: " . $count['count'] . "</p>";
    
    // Show first vehicle if exists
    if ($count['count'] > 0) {
        $first_vehicle = $conn->query("SELECT * FROM vehicles LIMIT 1");
        $vehicle = $first_vehicle->fetch_assoc();
        echo "<h3>Sample Vehicle Data:</h3>";
        echo "<pre>";
        print_r($vehicle);
        echo "</pre>";
    }
    
    // Test fetching a specific vehicle by number plate
    echo "<h3>Test Vehicle Fetch by Number Plate:</h3>";
    $test_plate = "KBA 001A"; // Example plate, change as needed
    $stmt = $conn->prepare("SELECT number_plate, type as vehicle_type, color as vehicle_color, driver_name, driver_phone FROM vehicles WHERE number_plate = ?");
    $stmt->bind_param("s", $test_plate);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $vehicle = $result->fetch_assoc();
        if ($vehicle) {
            echo "<p>Found vehicle:</p>";
            echo "<pre>";
            print_r($vehicle);
            echo "</pre>";
        } else {
            echo "<p>No vehicle found with plate: $test_plate</p>";
        }
    } else {
        echo "<p>Error executing query: " . $stmt->error . "</p>";
    }
    $stmt->close();
} else {
    echo "<p>Vehicles table does not exist.</p>";
}

$conn->close();
?>