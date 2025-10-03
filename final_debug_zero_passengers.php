<?php
echo "<h2>🎯 FINAL DEBUG: Why Zero Passengers?</h2>";

// Start session to simulate driver being logged in
session_start();
require_once 'db.php';

// Determine which driver to test
$driver_phone = $_SESSION['phone'] ?? '0736225373';
echo "<h3>Testing driver: $driver_phone</h3>";

if (!isset($_SESSION['phone'])) {
    echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "⚠️ <strong>No active driver session.</strong> You should be logged in as a driver to test this properly.<br>";
    echo "For testing, I'll use driver: $driver_phone";
    echo "</div>";
}

echo "<h3>🔍 Step-by-Step Analysis</h3>";

// Step 1: Check driver exists
echo "<h4>1. Driver Verification</h4>";
$driver_stmt = $conn->prepare("SELECT id, name FROM drivers WHERE driver_phone = ?");
$driver_stmt->bind_param("s", $driver_phone);
$driver_stmt->execute();
$driver_result = $driver_stmt->get_result();
$driver = $driver_result->fetch_assoc();

if ($driver) {
    echo "✅ Driver found: {$driver['name']} (ID: {$driver['id']})<br>";
} else {
    echo "❌ Driver not found for phone: $driver_phone<br>";
    exit;
}

// Step 2: Check vehicles
echo "<h4>2. Driver's Vehicles</h4>";
$vehicle_stmt = $conn->prepare("SELECT number_plate FROM vehicles WHERE driver_phone = ?");
$vehicle_stmt->bind_param("s", $driver_phone);
$vehicle_stmt->execute();
$vehicle_result = $vehicle_stmt->get_result();
$vehicles = [];
while ($row = $vehicle_result->fetch_assoc()) {
    $vehicles[] = $row['number_plate'];
}

if (!empty($vehicles)) {
    echo "✅ Vehicles: " . implode(', ', $vehicles) . "<br>";
} else {
    echo "❌ No vehicles found<br>";
    exit;
}

// Step 3: Test exact query from share_google_maps_link.php
echo "<h4>3. Exact Query Test (from share_google_maps_link.php)</h4>";
$placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
$exact_query = "
    SELECT DISTINCT 
        b.user_id, 
        b.fullname as passenger_name, 
        b.phone as passenger_phone,
        v.number_plate,
        v.type as vehicle_type
    FROM bookings b
    JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE b.assigned_vehicle IN ($placeholders)
    AND DATE(b.created_at) = CURDATE()
";

$stmt = $conn->prepare($exact_query);
$types = str_repeat('s', count($vehicles));
$stmt->bind_param($types, ...$vehicles);
$stmt->execute();
$result = $stmt->get_result();

echo "<strong>Query:</strong><br>";
echo "<code style='background: #f8f9fa; padding: 5px; display: block; margin: 5px 0;'>";
echo str_replace($placeholders, "'" . implode("', '", $vehicles) . "'", $exact_query);
echo "</code>";

echo "<strong>Result:</strong> Found {$result->num_rows} passengers<br>";

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>User ID</th><th>Name</th><th>Phone</th><th>Vehicle</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['passenger_name']}</td>";
        echo "<td>{$row['passenger_phone']}</td>";
        echo "<td>{$row['number_plate']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>No passengers found with current query!</strong>";
    echo "</div>";
}

// Step 4: Test without date filter
echo "<h4>4. Test Without Date Filter (All Assignments)</h4>";
$all_query = "
    SELECT DISTINCT 
        b.user_id, 
        b.fullname as passenger_name, 
        b.phone as passenger_phone,
        b.travel_date,
        DATE(b.created_at) as assignment_date,
        v.number_plate,
        v.type as vehicle_type
    FROM bookings b
    JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE b.assigned_vehicle IN ($placeholders)
    ORDER BY b.created_at DESC
";

$all_stmt = $conn->prepare($all_query);
$all_stmt->bind_param($types, ...$vehicles);
$all_stmt->execute();
$all_result = $all_stmt->get_result();

echo "<strong>All assignments for this driver:</strong> {$all_result->num_rows} found<br>";

if ($all_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>User ID</th><th>Name</th><th>Phone</th><th>Travel Date</th><th>Assignment Date</th><th>Vehicle</th></tr>";
    $today = date('Y-m-d');
    while ($row = $all_result->fetch_assoc()) {
        $highlight = ($row['assignment_date'] == $today) ? 'background: #d4edda;' : '';
        echo "<tr style='$highlight'>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['passenger_name']}</td>";
        echo "<td>{$row['passenger_phone']}</td>";
        echo "<td>{$row['travel_date']}</td>";
        echo "<td>{$row['assignment_date']}</td>";
        echo "<td>{$row['number_plate']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<small>Green rows = assigned today</small><br>";
}

// Step 5: Show current date/time info
echo "<h4>5. Date/Time Information</h4>";
echo "<strong>Current date:</strong> " . date('Y-m-d') . "<br>";
echo "<strong>Current time:</strong> " . date('Y-m-d H:i:s') . "<br>";
echo "<strong>Timezone:</strong> " . date_default_timezone_get() . "<br>";

// Step 6: Show recommendations
echo "<h3>🎯 Analysis & Recommendations</h3>";

if ($result->num_rows > 0) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
    echo "<h4>✅ Query is Working!</h4>";
    echo "<p>The query finds passengers correctly. If you're still seeing 0 passengers in the driver dashboard:</p>";
    echo "<ol>";
    echo "<li><strong>Clear browser cache</strong> - The JavaScript might be cached</li>";
    echo "<li><strong>Check which driver you're logged in as</strong> - Make sure it's $driver_phone</li>";
    echo "<li><strong>Test directly:</strong> <a href='test_live_sharing.php'>test_live_sharing.php</a></li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
    echo "<h4>⚠️ No Passengers Found Today</h4>";
    echo "<p>The system is working correctly, but there are no passengers assigned to this driver TODAY.</p>";
    
    if ($all_result->num_rows > 0) {
        echo "<p><strong>Solution:</strong> The driver has passengers assigned for other dates. You have two options:</p>";
        echo "<ol>";
        echo "<li><strong>Create a new booking for today</strong> via the admin panel</li>";
        echo "<li><strong>Update an existing booking</strong> to today's date</li>";
        echo "</ol>";
        
        echo "<p><a href='Admin/today_bookings.php' style='background: #6A0DAD; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>📅 Go to Admin Panel</a></p>";
    } else {
        echo "<p><strong>This driver has no passengers assigned at all.</strong> Please assign passengers via the admin panel.</p>";
    }
    echo "</div>";
}

?>