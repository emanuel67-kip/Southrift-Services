<?php
// Quick diagnostic script to debug passenger assignment
require_once 'db.php';

echo "<h2>🔍 Passenger Assignment Diagnosis</h2>";

// Test both drivers
$test_drivers = [
    '0736225373' => 'Driver1 (logged in)',
    '0798365350' => 'Kibet Bett'
];

foreach ($test_drivers as $driver_phone => $driver_name) {
    echo "<div style='border: 2px solid #6A0DAD; padding: 15px; margin: 10px 0; border-radius: 8px;'>";
    echo "<h3>Testing Driver: $driver_phone ($driver_name)</h3>";

// Step 1: Check driver exists
echo "<h3>1. Driver Check</h3>";
$driver_stmt = $conn->prepare("SELECT id FROM drivers WHERE driver_phone = ?");
$driver_stmt->bind_param('s', $driver_phone);
$driver_stmt->execute();
$driver_result = $driver_stmt->get_result();
if ($driver_row = $driver_result->fetch_assoc()) {
    $driver_id = $driver_row['id'];
    echo "✅ Driver found: ID = $driver_id<br>";
} else {
    echo "❌ Driver not found<br>";
    exit;
}

// Step 2: Check vehicles for this driver
echo "<h3>2. Vehicle Check</h3>";
$vehicle_stmt = $conn->prepare("SELECT number_plate FROM vehicles WHERE driver_phone = ?");
$vehicle_stmt->bind_param('s', $driver_phone);
$vehicle_stmt->execute();
$vehicle_result = $vehicle_stmt->get_result();
$vehicles = [];
while ($v = $vehicle_result->fetch_assoc()) {
    $vehicles[] = $v['number_plate'];
}

if (!empty($vehicles)) {
    echo "✅ Vehicles found: " . implode(', ', $vehicles) . "<br>";
} else {
    echo "❌ No vehicles found for this driver<br>";
    exit;
}

// Step 3: Check ALL bookings (not just today)
echo "<h3>3. All Bookings Check</h3>";
$all_bookings = $conn->query("SELECT COUNT(*) as total FROM bookings");
$total_bookings = $all_bookings->fetch_assoc()['total'];
echo "📊 Total bookings in database: $total_bookings<br>";

// Step 4: Check bookings with assigned vehicles matching our driver
echo "<h3>4. Vehicle Assignment Check</h3>";
$placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
$assign_stmt = $conn->prepare("
    SELECT COUNT(*) as count, assigned_vehicle
    FROM bookings 
    WHERE assigned_vehicle IN ($placeholders)
    GROUP BY assigned_vehicle
");
$types = str_repeat('s', count($vehicles));
$assign_stmt->bind_param($types, ...$vehicles);
$assign_stmt->execute();
$assign_result = $assign_stmt->get_result();

$found_assignments = false;
while ($assign_row = $assign_result->fetch_assoc()) {
    echo "✅ Vehicle {$assign_row['assigned_vehicle']}: {$assign_row['count']} bookings<br>";
    $found_assignments = true;
}

if (!$found_assignments) {
    echo "❌ No bookings found assigned to driver's vehicles<br>";
    
    // Show what vehicles ARE in assigned_vehicle column
    echo "<strong>Debug: Checking what's in assigned_vehicle column...</strong><br>";
    $debug_vehicles = $conn->query("SELECT DISTINCT assigned_vehicle FROM bookings WHERE assigned_vehicle IS NOT NULL AND assigned_vehicle != ''");
    if ($debug_vehicles && $debug_vehicles->num_rows > 0) {
        echo "Vehicles found in bookings: ";
        while ($dv = $debug_vehicles->fetch_assoc()) {
            echo "'" . $dv['assigned_vehicle'] . "' ";
        }
        echo "<br>";
    } else {
        echo "No vehicles found in assigned_vehicle column<br>";
    }
}

// Step 5: Check today's bookings specifically
echo "<h3>5. Today's Bookings Check</h3>";
$today_stmt = $conn->prepare("
    SELECT b.user_id, b.fullname, b.phone, b.assigned_vehicle, b.travel_date
    FROM bookings b
    WHERE b.assigned_vehicle IN ($placeholders)
    AND DATE(b.travel_date) = CURDATE()
");
$today_stmt->bind_param($types, ...$vehicles);
$today_stmt->execute();
$today_result = $today_stmt->get_result();

if ($today_result->num_rows > 0) {
    echo "✅ Found {$today_result->num_rows} bookings for today:<br>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>User ID</th><th>Name</th><th>Phone</th><th>Vehicle</th><th>Date</th></tr>";
    while ($booking = $today_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$booking['user_id']}</td>";
        echo "<td>{$booking['fullname']}</td>";
        echo "<td>{$booking['phone']}</td>";
        echo "<td>{$booking['assigned_vehicle']}</td>";
        echo "<td>{$booking['travel_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ No bookings found for today<br>";
    
    // Check if there are any bookings for other dates
    $other_dates = $conn->prepare("
        SELECT COUNT(*) as count, DATE(travel_date) as date
        FROM bookings b
        WHERE b.assigned_vehicle IN ($placeholders)
        GROUP BY DATE(travel_date)
        ORDER BY date DESC
        LIMIT 5
    ");
    $other_dates->bind_param($types, ...$vehicles);
    $other_dates->execute();
    $other_result = $other_dates->get_result();
    
    if ($other_result->num_rows > 0) {
        echo "<strong>Bookings found for other dates:</strong><br>";
        while ($date_row = $other_result->fetch_assoc()) {
            echo "Date: {$date_row['date']} - {$date_row['count']} bookings<br>";
        }
    }
}

// Step 6: Test the exact query from share_google_maps_link.php
echo "<h3>6. Share Function Query Test</h3>";
$share_stmt = $conn->prepare("
    SELECT DISTINCT 
        b.user_id, 
        b.fullname as passenger_name, 
        b.phone as passenger_phone,
        v.number_plate,
        v.type as vehicle_type
    FROM bookings b
    JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE b.assigned_vehicle IN ($placeholders)
    AND DATE(b.travel_date) = CURDATE()
");
$share_stmt->bind_param($types, ...$vehicles);
$share_stmt->execute();
$share_result = $share_stmt->get_result();

echo "🔍 Share query result: {$share_result->num_rows} passengers found<br>";

if ($share_result->num_rows > 0) {
    echo "<strong>Passengers that would be notified:</strong><br>";
    while ($passenger = $share_result->fetch_assoc()) {
        echo "- User ID: {$passenger['user_id']}, Name: {$passenger['passenger_name']}, Phone: {$passenger['passenger_phone']}<br>";
    }
} else {
    echo "❌ No passengers would be notified by the share function<br>";
}

echo "<h3>🎯 Summary</h3>";
echo "If you see 0 passengers in step 6, the issue is either:<br>";
echo "1. No bookings for TODAY (check step 5)<br>";
echo "2. Vehicle assignment mismatch (check step 4)<br>";
echo "3. Missing travel_date = today<br>";
echo "</div>"; // Close driver div
} // Close foreach loop

// Additional check: Show ALL today's bookings
echo "<div style='background: #f0f8ff; padding: 15px; margin: 10px 0; border-radius: 8px;'>";
echo "<h3>📅 ALL Today's Bookings (Any Driver)</h3>";
$all_today = $conn->query("
    SELECT b.user_id, b.fullname, b.phone, b.assigned_vehicle, b.travel_date, v.driver_phone, v.driver_name
    FROM bookings b
    LEFT JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE DATE(b.travel_date) = CURDATE()
    ORDER BY b.assigned_vehicle
");

if ($all_today && $all_today->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>User ID</th><th>Name</th><th>Phone</th><th>Vehicle</th><th>Driver Phone</th><th>Driver Name</th></tr>";
    while ($booking = $all_today->fetch_assoc()) {
        $highlight = ($booking['driver_phone'] == '0736225373') ? 'background: yellow;' : '';
        echo "<tr style='$highlight'>";
        echo "<td>{$booking['user_id']}</td>";
        echo "<td>{$booking['fullname']}</td>";
        echo "<td>{$booking['phone']}</td>";
        echo "<td>{$booking['assigned_vehicle']}</td>";
        echo "<td>{$booking['driver_phone']}</td>";
        echo "<td>{$booking['driver_name']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<br><small>Yellow rows = assigned to your logged-in driver (0736225373)</small>";
} else {
    echo "❌ No bookings found for today at all!<br>";
}
echo "</div>";

?>