<?php
// Script to set up the updated bookings table
require_once 'db.php';

// Read the SQL file
$sqlFile = __DIR__ . '/Database/tables/bookings_updated.sql';
if (!file_exists($sqlFile)) {
    die("SQL file not found: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);

// Execute the SQL
if ($conn->query($sql) === TRUE) {
    echo "Bookings table created/updated successfully\n";
    
    // Check if the table exists and show its structure
    $result = $conn->query("DESCRIBE bookings");
    if ($result) {
        echo "\nTable structure:\n";
        echo str_pad("Field", 25) . str_pad("Type", 30) . str_pad("Null", 10) . "Key\n";
        echo str_repeat("-", 75) . "\n";
        
        while ($row = $result->fetch_assoc()) {
            echo str_pad($row['Field'], 25) . 
                 str_pad($row['Type'], 30) . 
                 str_pad($row['Null'], 10) . 
                 $row['Key'] . "\n";
        }
    }
} else {
    echo "Error creating/updating bookings table: " . $conn->error . "\n";
    
    // Try to alter the existing table
    echo "\nAttempting to update existing table structure...\n";
    $alterSqlFile = __DIR__ . '/Database/tables/update_bookings_table.sql';
    if (file_exists($alterSqlFile)) {
        $alterSql = file_get_contents($alterSqlFile);
        if ($conn->multi_query($alterSql)) {
            // Process multi-query results
            do {
                // Store first result set
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->next_result());
            
            echo "Bookings table updated successfully\n";
        } else {
            echo "Error updating bookings table: " . $conn->error . "\n";
        }
    }
}

$conn->close();
?>