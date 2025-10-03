<?php
require_once 'db.php';

echo "<h1>🧪 Add Test Booking for Today</h1>";

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { background: #e7f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
h2, h3 { color: #6A0DAD; }
</style>";

try {
    $today = date('Y-m-d');
    
    // First, check the actual table structure
    $columns_check = $conn->query("SHOW COLUMNS FROM bookings");
    $available_columns = [];
    while ($col = $columns_check->fetch_assoc()) {
        $available_columns[] = $col['Field'];
    }
    
    echo "<div class='info'>Available columns: " . implode(', ', $available_columns) . "</div>";
    
    // Build the insert based on available columns
    $insert_fields = ['user_id', 'fullname', 'phone', 'assigned_vehicle', 'travel_date', 'departure_time', 'payment_method'];
    $insert_values = ['?', '?', '?', '?', '?', '?', '?'];
    $bind_types = 'issssss';
    
    // Check if num_seats exists and add it if available
    if (in_array('num_seats', $available_columns)) {
        $insert_fields[] = 'num_seats';
        $insert_values[] = '?';
        $bind_types .= 'i';
    }
    
    $fields_str = implode(', ', $insert_fields);
    $values_str = implode(', ', $insert_values);
    
    $insert_stmt = $conn->prepare("
        INSERT INTO bookings ($fields_str) 
        VALUES ($values_str)
    ");
    
    $user_id = 13; // Using same user as existing booking
    $fullname = "Emanuel Kipruto";
    $phone = "0795428218";
    $assigned_vehicle = "KDR 645 J";
    $departure_time = "09:00:00";
    $payment_method = "cash";
    $num_seats = 1;
    
    // Bind parameters based on available columns
    if (in_array('num_seats', $available_columns)) {
        $insert_stmt->bind_param(
            $bind_types, 
            $user_id, 
            $fullname, 
            $phone, 
            $assigned_vehicle, 
            $today, 
            $departure_time, 
            $payment_method,
            $num_seats
        );
    } else {
        $insert_stmt->bind_param(
            $bind_types, 
            $user_id, 
            $fullname, 
            $phone, 
            $assigned_vehicle, 
            $today, 
            $departure_time, 
            $payment_method
        );
    }
    
    if ($insert_stmt->execute()) {
        $new_booking_id = $conn->insert_id;
        
        echo "<div class='success'>✅ Created test booking for today!</div>";
        echo "<div class='info'>";
        echo "<h3>📋 Test Booking Details:</h3>";
        echo "<ul>";
        echo "<li>Booking ID: $new_booking_id</li>";
        echo "<li>Passenger: $fullname</li>";
        echo "<li>Phone: $phone</li>";
        echo "<li>Vehicle: $assigned_vehicle</li>";
        echo "<li>Travel Date: $today (TODAY)</li>";
        echo "<li>Time: $departure_time</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<div class='success'>";
        echo "<h3>🎯 Now Test Location Sharing:</h3>";
        echo "<ol>";
        echo "<li><strong>Login as Driver:</strong> Use phone 0736225373</li>";
        echo "<li><strong>Go to Dashboard:</strong> <a href='Driver/index.php' target='_blank'>Driver Dashboard</a></li>";
        echo "<li><strong>Share Location:</strong> Paste a Google Maps link</li>";
        echo "<li><strong>Expected Result:</strong> Should show '1 passenger notified'</li>";
        echo "</ol>";
        echo "</div>";
        
    } else {
        echo "<div class='error'>❌ Failed to create test booking: " . $insert_stmt->error . "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}
?>