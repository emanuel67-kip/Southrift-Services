<?php
require_once 'db.php';

echo "<h1>🔍 Deep Debug: Location Sharing Issue</h1>";

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
code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
</style>";

// Simulate the exact same process as share_google_maps_link.php
$driver_phone = "0736225373"; // From your debug output

echo "<h2>Step 1: Get Driver's Vehicles</h2>";

$vehicles = [];
$vehicle_stmt = $conn->prepare("SELECT number_plate FROM vehicles WHERE driver_phone = ?");
$vehicle_stmt->bind_param("s", $driver_phone);
$vehicle_stmt->execute();
$vehicle_result = $vehicle_stmt->get_result();
while ($row = $vehicle_result->fetch_assoc()) {
    $vehicles[] = $row['number_plate'];
}

echo "<div class='info'>Driver phone: <code>$driver_phone</code></div>";
echo "<div class='info'>Vehicles found: <code>" . implode(', ', $vehicles) . "</code></div>";

if (empty($vehicles)) {
    echo "<div class='error'>❌ No vehicles found! This is the problem.</div>";
    exit;
}

echo "<h2>Step 2: Check Today's Bookings</h2>";

$today = date('Y-m-d');
echo "<div class='info'>Today's date: <code>$today</code></div>";

// Show ALL bookings for today
$all_today_bookings = $conn->query("SELECT * FROM bookings WHERE DATE(travel_date) = '$today'");
echo "<div class='info'>Total bookings for today: <code>" . $all_today_bookings->num_rows . "</code></div>";

if ($all_today_bookings->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Booking ID</th><th>User ID</th><th>Name</th><th>Phone</th><th>Vehicle</th><th>Travel Date</th></tr>";
    while ($booking = $all_today_bookings->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$booking['booking_id']}</td>";
        echo "<td>{$booking['user_id']}</td>";
        echo "<td>" . ($booking['fullname'] ?? 'N/A') . "</td>";
        echo "<td>" . ($booking['phone'] ?? 'NULL') . "</td>";
        echo "<td>" . ($booking['assigned_vehicle'] ?? 'NULL') . "</td>";
        echo "<td>{$booking['travel_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>Step 3: Test the Exact Query from share_google_maps_link.php</h2>";

$placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
$query = "
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
";

echo "<div class='info'><strong>Exact query being used:</strong></div>";
echo "<code>" . str_replace($placeholders, "'" . implode("', '", $vehicles) . "'", $query) . "</code>";

$stmt = $conn->prepare($query);
$types = str_repeat('s', count($vehicles));
$stmt->bind_param($types, ...$vehicles);
$stmt->execute();
$result = $stmt->get_result();

echo "<div class='info'>Query result: <code>" . $result->num_rows . " passengers found</code></div>";

if ($result->num_rows > 0) {
    echo "<div class='success'>✅ Found passengers!</div>";
    echo "<table>";
    echo "<tr><th>User ID</th><th>Passenger</th><th>Phone</th><th>Vehicle</th><th>Booking ID</th></tr>";
    while ($passenger = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$passenger['user_id']}</td>";
        echo "<td>{$passenger['passenger_name']}</td>";
        echo "<td>{$passenger['passenger_phone']}</td>";
        echo "<td>{$passenger['number_plate']}</td>";
        echo "<td>{$passenger['booking_id']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ No passengers found with the exact query</div>";
    
    echo "<h3>🔍 Let's debug each condition:</h3>";
    
    // Test without date condition
    $query_no_date = str_replace("AND DATE(b.travel_date) = CURDATE()", "", $query);
    $stmt_no_date = $conn->prepare($query_no_date);
    $stmt_no_date->bind_param($types, ...$vehicles);
    $stmt_no_date->execute();
    $result_no_date = $stmt_no_date->get_result();
    echo "<div class='info'>Without date filter: <code>" . $result_no_date->num_rows . " passengers</code></div>";
    
    // Test without phone conditions
    $query_no_phone = str_replace(["AND b.phone IS NOT NULL", "AND b.phone != ''"], "", $query);
    $stmt_no_phone = $conn->prepare($query_no_phone);
    $stmt_no_phone->bind_param($types, ...$vehicles);
    $stmt_no_phone->execute();
    $result_no_phone = $stmt_no_phone->get_result();
    echo "<div class='info'>Without phone filters: <code>" . $result_no_phone->num_rows . " passengers</code></div>";
    
    // Test without user_id condition
    $query_no_user = str_replace("AND b.user_id IS NOT NULL", "", $query);
    $stmt_no_user = $conn->prepare($query_no_user);
    $stmt_no_user->bind_param($types, ...$vehicles);
    $stmt_no_user->execute();
    $result_no_user = $stmt_no_user->get_result();
    echo "<div class='info'>Without user_id filter: <code>" . $result_no_user->num_rows . " passengers</code></div>";
    
    // Test just vehicle matching
    $query_basic = "SELECT * FROM bookings WHERE assigned_vehicle IN ($placeholders)";
    $stmt_basic = $conn->prepare($query_basic);
    $stmt_basic->bind_param($types, ...$vehicles);
    $stmt_basic->execute();
    $result_basic = $stmt_basic->get_result();
    echo "<div class='info'>Just vehicle matching: <code>" . $result_basic->num_rows . " bookings</code></div>";
    
    if ($result_basic->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>Booking ID</th><th>User ID</th><th>Phone</th><th>Vehicle</th><th>Travel Date</th></tr>";
        while ($basic_booking = $result_basic->fetch_assoc()) {
            $phone_status = empty($basic_booking['phone']) ? "❌ EMPTY" : "✅ " . $basic_booking['phone'];
            $user_status = empty($basic_booking['user_id']) ? "❌ NULL" : "✅ " . $basic_booking['user_id'];
            $date_status = ($basic_booking['travel_date'] === $today) ? "✅ TODAY" : "❌ " . $basic_booking['travel_date'];
            
            echo "<tr>";
            echo "<td>{$basic_booking['booking_id']}</td>";
            echo "<td>$user_status</td>";
            echo "<td>$phone_status</td>";
            echo "<td>{$basic_booking['assigned_vehicle']}</td>";
            echo "<td>$date_status</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

echo "<h2>Step 4: Recommended Fix</h2>";

if ($result->num_rows == 0) {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Root Cause Identified</h3>";
    echo "<p>The query is working correctly but no bookings match ALL the conditions. Based on the debug above, you need to:</p>";
    echo "<ol>";
    echo "<li><strong>Check travel_date:</strong> Make sure there's a booking for today ($today)</li>";
    echo "<li><strong>Check phone field:</strong> Ensure phone number is not empty/null</li>";
    echo "<li><strong>Check user_id:</strong> Ensure user_id is not null</li>";
    echo "<li><strong>Check assigned_vehicle:</strong> Must match exactly: " . implode(', ', $vehicles) . "</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<p><strong>Quick test links:</strong></p>";
    echo "<ul>";
    echo "<li><a href='create_test_booking_today.php'>Create a proper test booking for today</a></li>";
    echo "<li><a href='debug_zero_passengers.php'>Run the original debug script</a></li>";
    echo "</ul>";
    echo "</div>";
}

?>