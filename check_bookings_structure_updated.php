<?php
// Script to verify the updated bookings table structure
require_once 'db.php';

// Function to describe table structure
function describeTable($connection, $tableName) {
    $query = "DESCRIBE `$tableName`";
    $result = mysqli_query($connection, $query);
    
    if ($result) {
        echo "Table structure for `$tableName`:\n";
        echo str_pad("Field", 25) . str_pad("Type", 30) . str_pad("Null", 10) . "Key\n";
        echo str_repeat("-", 75) . "\n";
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo str_pad($row['Field'], 25) . 
                 str_pad($row['Type'], 30) . 
                 str_pad($row['Null'], 10) . 
                 $row['Key'] . "\n";
        }
        echo "\n";
    } else {
        echo "Error describing table: " . mysqli_error($connection) . "\n";
    }
}

// Function to check if all required columns exist
function checkRequiredColumns($connection) {
    $requiredColumns = [
        'booking_id',
        'user_id',
        'fullname',
        'phone',
        'route',
        'boarding_point',
        'travel_date',
        'departure_time',
        'seats',
        'payment_method',
        'assigned_vehicle',
        'created_at',
        'google_maps_link',
        'shared_location_updated'
    ];
    
    $query = "DESCRIBE `bookings`";
    $result = mysqli_query($connection, $query);
    
    if ($result) {
        $existingColumns = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $existingColumns[] = $row['Field'];
        }
        
        echo "Checking required columns:\n";
        $allPresent = true;
        foreach ($requiredColumns as $column) {
            if (in_array($column, $existingColumns)) {
                echo "✓ $column\n";
            } else {
                echo "✗ $column (MISSING)\n";
                $allPresent = false;
            }
        }
        
        if ($allPresent) {
            echo "\n✓ All required columns are present in the bookings table.\n";
        } else {
            echo "\n✗ Some required columns are missing.\n";
        }
        
        return $allPresent;
    } else {
        echo "Error checking table structure: " . mysqli_error($connection) . "\n";
        return false;
    }
}

// Main execution
echo "Verifying updated bookings table structure...\n";
echo "==========================================\n\n";

// Describe the bookings table
describeTable($connection, 'bookings');

// Check required columns
$columnsOK = checkRequiredColumns($connection);

// Check foreign key constraints
echo "\nChecking foreign key constraints:\n";
$fkQuery = "SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'bookings' 
AND REFERENCED_TABLE_NAME IS NOT NULL";

$fkResult = mysqli_query($connection, $fkQuery);
if ($fkResult && mysqli_num_rows($fkResult) > 0) {
    while ($row = mysqli_fetch_assoc($fkResult)) {
        echo "✓ Foreign key: {$row['COLUMN_NAME']} -> {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}\n";
    }
} else {
    echo "No foreign key constraints found or error: " . mysqli_error($connection) . "\n";
}

// Check enum values for payment_method
echo "\nChecking payment_method enum values:\n";
$enumQuery = "SHOW COLUMNS FROM `bookings` LIKE 'payment_method'";
$enumResult = mysqli_query($connection, $enumQuery);
if ($enumResult) {
    $row = mysqli_fetch_assoc($enumResult);
    if (preg_match("/enum\((.*)\)/", $row['Type'], $matches)) {
        $values = str_getcsv($matches[1], ",", "'");
        foreach ($values as $value) {
            echo "✓ $value\n";
        }
    }
}

echo "\nVerification complete.\n";

mysqli_close($connection);
?>