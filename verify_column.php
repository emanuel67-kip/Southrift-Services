<?php
require 'db.php';

echo "Testing if assigned_vehicle column now works...\n\n";

// Check if column exists
$check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'assigned_vehicle'");
if ($check && $check->num_rows > 0) {
    echo "✅ assigned_vehicle column EXISTS\n\n";
    
    // Show recent bookings
    $result = $conn->query("SELECT booking_id, fullname, phone, assigned_vehicle, created_at FROM bookings ORDER BY booking_id DESC LIMIT 5");
    
    if ($result && $result->num_rows > 0) {
        echo "Recent bookings:\n";
        echo "ID\tName\t\tPhone\t\tAssigned Vehicle\n";
        echo "----------------------------------------\n";
        
        while ($row = $result->fetch_assoc()) {
            printf("%d\t%s\t%s\t%s\n", 
                $row['booking_id'], 
                substr($row['fullname'], 0, 15), 
                $row['phone'], 
                $row['assigned_vehicle'] ?: '—'
            );
        }
    } else {
        echo "No bookings found\n";
    }
} else {
    echo "❌ assigned_vehicle column does NOT exist\n";
}

$conn->close();
?>