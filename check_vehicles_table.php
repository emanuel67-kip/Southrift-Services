<?php
// Database connection
$host = 'localhost';
$dbname = 'southrift';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if vehicles table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'vehicles'");
    if ($stmt->rowCount() === 0) {
        die("The 'vehicles' table does not exist in the database.\n");
    }
    
    // Get table structure
    $stmt = $conn->query("DESCRIBE vehicles");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Vehicles table structure:\n";
    echo str_repeat("-", 50) . "\n";
    echo sprintf("%-20s | %-20s | %-10s\n", "Column", "Type", "Null");
    echo str_repeat("-", 50) . "\n";
    
    foreach ($columns as $column) {
        echo sprintf("%-20s | %-20s | %-10s\n", 
            $column['Field'], 
            $column['Type'],
            $column['Null']
        );
    }
    
    // Get sample data
    echo "\nSample data (first 5 rows):\n";
    $stmt = $conn->query("SELECT * FROM vehicles LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rows) === 0) {
        echo "No data found in the vehicles table.\n";
    } else {
        $headers = array_keys($rows[0]);
        echo implode(" | ", $headers) . "\n";
        echo str_repeat("-", 100) . "\n";
        
        foreach ($rows as $row) {
            echo implode(" | ", array_values($row)) . "\n";
        }
    }
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
