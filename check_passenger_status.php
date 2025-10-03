<?php
require_once 'db.php';

echo "<h1>🔍 Quick Check: Why 1 Passenger Became 0</h1>";

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { background: #e7f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
.warning { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
h2, h3 { color: #6A0DAD; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
td, th { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>";

$driver_phone = "0736225373";
$today = date('Y-m-d');

echo "<div class='info'>Current date: <strong>$today</strong></div>";
echo "<div class='info'>Driver phone: <strong>$driver_phone</strong></div>";

// Check all bookings for this driver's vehicles
$vehicles_query = $conn->prepare("SELECT number_plate FROM vehicles WHERE driver_phone = ?");
$vehicles_query->bind_param("s", $driver_phone);
$vehicles_query->execute();
$vehicles_result = $vehicles_query->get_result();

$vehicles = [];
while ($row = $vehicles_result->fetch_assoc()) {
    $vehicles[] = $row['number_plate'];
}

echo "<div class='info'>Vehicles: <strong>" . implode(', ', $vehicles) . "</strong></div>";

if (empty($vehicles)) {
    echo "<div class='error'>❌ No vehicles found!</div>";
    exit;
}

// Check ALL bookings for these vehicles
$placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
$all_bookings_query = $conn->prepare("
    SELECT 
        booking_id,
        user_id,
        fullname,
        phone,
        assigned_vehicle,
        travel_date,
        DATE(travel_date) = CURDATE() as is_today
    FROM bookings 
    WHERE assigned_vehicle IN ($placeholders)
    ORDER BY travel_date DESC
");

$types = str_repeat('s', count($vehicles));
$all_bookings_query->bind_param($types, ...$vehicles);
$all_bookings_query->execute();
$all_result = $all_bookings_query->get_result();

echo "<h2>All Bookings for Driver's Vehicles</h2>";
echo "<table>";
echo "<tr><th>Booking ID</th><th>User ID</th><th>Name</th><th>Phone</th><th>Vehicle</th><th>Travel Date</th><th>Is Today?</th></tr>";

$today_count = 0;
while ($booking = $all_result->fetch_assoc()) {
    $is_today = $booking['is_today'] ? 'YES' : 'NO';
    $row_class = $booking['is_today'] ? "style='background-color: #d4edda;'" : "";
    
    if ($booking['is_today']) $today_count++;
    
    echo "<tr $row_class>";
    echo "<td>{$booking['booking_id']}</td>";
    echo "<td>{$booking['user_id']}</td>";
    echo "<td>" . ($booking['fullname'] ?: 'NULL') . "</td>";
    echo "<td>" . ($booking['phone'] ?: 'NULL') . "</td>";
    echo "<td>{$booking['assigned_vehicle']}</td>";
    echo "<td>{$booking['travel_date']}</td>";
    echo "<td>$is_today</td>";
    echo "</tr>";
}
echo "</table>";

echo "<div class='info'>Total bookings for today: <strong>$today_count</strong></div>";

// Now test the exact query from share_google_maps_link.php
$passenger_query = $conn->prepare("
    SELECT DISTINCT 
        b.user_id, 
        b.fullname as passenger_name, 
        b.phone as passenger_phone,
        v.number_plate,
        v.type as vehicle_type,
        b.booking_id
    FROM bookings b
    JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE b.assigned_vehicle IN ($placeholders)
    AND b.phone IS NOT NULL
    AND b.phone != ''
    AND b.user_id IS NOT NULL
    AND DATE(b.travel_date) = CURDATE()
");

$passenger_query->bind_param($types, ...$vehicles);
$passenger_query->execute();
$passenger_result = $passenger_query->get_result();

echo "<h2>Query Result (with all filters)</h2>";
echo "<div class='info'>Passengers found: <strong>" . $passenger_result->num_rows . "</strong></div>";

if ($passenger_result->num_rows == 0) {
    echo "<div class='warning'>";
    echo "<h3>🔍 Diagnosis:</h3>";
    
    if ($today_count == 0) {
        echo "<p>❌ <strong>Issue:</strong> No bookings for today ($today)</p>";
        echo "<p>🔧 <strong>Solution:</strong> Create a test booking for today or wait until the travel date</p>";
        echo "<p><a href='create_test_booking_today.php'>Create Test Booking for Today</a></p>";
    } else {
        echo "<p>⚠️ <strong>Issue:</strong> Bookings exist for today but failing other filters (phone/user_id)</p>";
        echo "<p>Check the table above for NULL phone numbers or user_ids</p>";
    }
    echo "</div>";
} else {
    echo "<div class='success'>✅ Query is working! Passengers found for today.</div>";
}

?>