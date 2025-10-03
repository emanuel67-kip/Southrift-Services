<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db.php';

echo "<h2>Database Column Fixes</h2>";

try {
    // 1. Add last_login column to users table
    echo "<h3>Step 1: Adding last_login column to users table</h3>";
    
    // Check if column exists first
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'last_login'");
    
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL AFTER updated_at";
        
        if ($conn->query($sql)) {
            echo "✅ Successfully added last_login column to users table<br>";
        } else {
            echo "❌ Error adding last_login column: " . $conn->error . "<br>";
        }
    } else {
        echo "✅ last_login column already exists in users table<br>";
    }
    
    // 2. Check current drivers table structure
    echo "<h3>Step 2: Checking drivers table structure</h3>";
    $result = $conn->query("DESCRIBE drivers");
    
    if ($result) {
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        $driver_columns = [];
        while ($row = $result->fetch_assoc()) {
            $driver_columns[] = $row['Field'];
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 3. Add missing columns to drivers table if needed
        echo "<h3>Step 3: Adding missing columns to drivers table</h3>";
        
        $required_columns = [
            'name' => "VARCHAR(100) NOT NULL DEFAULT ''",
            'driver_phone' => "VARCHAR(20) UNIQUE DEFAULT NULL",
            'email' => "VARCHAR(100) DEFAULT NULL",
            'number_plate' => "VARCHAR(20) DEFAULT NULL",
            'last_login' => "TIMESTAMP NULL DEFAULT NULL"
        ];
        
        foreach ($required_columns as $column => $definition) {
            if (!in_array($column, $driver_columns)) {
                $sql = "ALTER TABLE drivers ADD COLUMN $column $definition";
                
                if ($conn->query($sql)) {
                    echo "✅ Successfully added $column column to drivers table<br>";
                } else {
                    echo "❌ Error adding $column column: " . $conn->error . "<br>";
                }
            } else {
                echo "✅ $column column already exists in drivers table<br>";
            }
        }
        
    } else {
        echo "❌ Error describing drivers table: " . $conn->error . "<br>";
    }
    
    // 4. Update the users table ENUM to include 'passenger' if not already done
    echo "<h3>Step 4: Ensuring 'passenger' role exists in users table</h3>";
    
    $result = $conn->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
    if ($result && $row = $result->fetch_assoc()) {
        $type = $row['Type'];
        
        if (strpos($type, 'passenger') === false) {
            $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'driver', 'user', 'passenger') NOT NULL DEFAULT 'passenger'";
            
            if ($conn->query($sql)) {
                echo "✅ Successfully updated role ENUM to include 'passenger'<br>";
            } else {
                echo "❌ Error updating role ENUM: " . $conn->error . "<br>";
            }
        } else {
            echo "✅ 'passenger' role already exists in users table<br>";
        }
    }
    
    // 5. Show final table structures
    echo "<h3>Step 5: Final Table Structures</h3>";
    
    echo "<h4>Users Table:</h4>";
    $result = $conn->query("DESCRIBE users");
    if ($result) {
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h4>Drivers Table:</h4>";
    $result = $conn->query("DESCRIBE drivers");
    if ($result) {
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<br><h3>✅ Database structure update completed!</h3>";
    echo "<p>The login system should now work without the 'last_login' column error.</p>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

$conn->close();
?>