<?php
require_once 'db.php';

echo "<h1>🔍 Debug: Why Zero Passengers Found</h1>";

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

// Get the driver phone from session or use a test driver
session_start();
$driver_phone = $_SESSION['phone'] ?? null;

if (!$driver_phone) {
    // Get first driver for testing
    $test_driver = $conn->query("SELECT driver_phone FROM drivers LIMIT 1")->fetch_assoc();
    $driver_phone = $test_driver['driver_phone'] ?? null;
    echo "<div class='warning'>⚠️ No driver phone in session, using test driver: $driver_phone</div>";
} else {
    echo "<div class='success'>✅ Using session driver phone: $driver_phone</div>";
}

if (!$driver_phone) {
    echo "<div class='error'>❌ No driver phone available for testing</div>";
    exit;
}

echo "<h2>Step 1: Check Driver's Vehicles</h2>";

// Get all vehicles assigned to this driver
$vehicles = [];
$vehicle_stmt = $conn->prepare("SELECT number_plate, type FROM vehicles WHERE driver_phone = ?");
$vehicle_stmt->bind_param("s", $driver_phone);
$vehicle_stmt->execute();
$vehicle_result = $vehicle_stmt->get_result();

echo "<table><tr><th>Number Plate</th><th>Type</th></tr>";
while ($row = $vehicle_result->fetch_assoc()) {
    $vehicles[] = $row['number_plate'];
    echo "<tr><td>{$row['number_plate']}</td><td>{$row['type']}</td></tr>";
}
echo "</table>";

if (empty($vehicles)) {
    echo "<div class='error'>❌ No vehicles found for driver phone: $driver_phone</div>";
    exit;
} else {
    echo "<div class='success'>✅ Found " . count($vehicles) . " vehicles: " . implode(', ', $vehicles) . "</div>";
}

echo "<h2>Step 2: Check All Bookings (Any Date)</h2>";

$placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
$all_bookings_stmt = $conn->prepare("
    SELECT 
        b.booking_id,
        b.user_id, 
        b.fullname as passenger_name, 
        b.phone as passenger_phone,
        b.travel_date,
        b.assigned_vehicle,
        v.number_plate,
        v.type as vehicle_type
    FROM bookings b
    JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE b.assigned_vehicle IN ($placeholders)
    ORDER BY b.travel_date DESC
");

$types = str_repeat('s', count($vehicles));
$all_bookings_stmt->bind_param($types, ...$vehicles);
$all_bookings_stmt->execute();
$all_result = $all_bookings_stmt->get_result();

echo "<table>";
echo "<tr><th>Booking ID</th><th>User ID</th><th>Passenger</th><th>Phone</th><th>Travel Date</th><th>Vehicle</th></tr>";

$today_count = 0;
while ($booking = $all_result->fetch_assoc()) {
    $is_today = date('Y-m-d', strtotime($booking['travel_date'])) === date('Y-m-d');
    $row_class = $is_today ? "style='background-color: #d4edda;'" : "";
    
    if ($is_today) $today_count++;
    
    echo "<tr $row_class>";
    echo "<td>{$booking['booking_id']}</td>";
    echo "<td>{$booking['user_id']}</td>";
    echo "<td>{$booking['passenger_name']}</td>";
    echo "<td>{$booking['passenger_phone']}</td>";
    echo "<td>{$booking['travel_date']}" . ($is_today ? " (TODAY)" : "") . "</td>";
    echo "<td>{$booking['number_plate']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<div class='info'>📊 Total bookings: {$all_result->num_rows}, Today's bookings: $today_count</div>";

echo "<h2>Step 3: Check Today's Bookings with Filters</h2>";

// Test the exact query used in the notification function
$today_stmt = $conn->prepare("
    SELECT DISTINCT 
        b.user_id, 
        b.fullname as passenger_name, 
        b.phone as passenger_phone,
        v.number_plate,
        v.type as vehicle_type,
        b.booking_id,
        b.travel_date
    FROM bookings b
    JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE b.assigned_vehicle IN ($placeholders)
    AND b.phone IS NOT NULL
    AND b.phone != ''
    AND b.user_id IS NOT NULL
    AND DATE(b.travel_date) = CURDATE()
");

$today_stmt->bind_param($types, ...$vehicles);
$today_stmt->execute();
$today_result = $today_stmt->get_result();

echo "<div class='info'>Query filters applied:</div>";
echo "<ul>";
echo "<li>✅ assigned_vehicle IN (" . implode(', ', $vehicles) . ")</li>";
echo "<li>✅ phone IS NOT NULL AND phone != ''</li>";
echo "<li>✅ user_id IS NOT NULL</li>";
echo "<li>✅ DATE(travel_date) = CURDATE() [" . date('Y-m-d') . "]</li>";
echo "</ul>";

if ($today_result->num_rows > 0) {
    echo "<div class='success'>✅ Found {$today_result->num_rows} passengers for today</div>";
    
    echo "<table>";
    echo "<tr><th>Booking ID</th><th>User ID</th><th>Passenger</th><th>Phone</th><th>Vehicle</th></tr>";
    while ($passenger = $today_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$passenger['booking_id']}</td>";
        echo "<td>{$passenger['user_id']}</td>";
        echo "<td>{$passenger['passenger_name']}</td>";
        echo "<td>{$passenger['passenger_phone']}</td>";
        echo "<td>{$passenger['number_plate']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ No passengers found for today with all filters applied</div>";
    
    echo "<h3>🔍 Let's check each filter individually:</h3>";
    
    // Check without date filter
    $no_date_stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM bookings b
        JOIN vehicles v ON b.assigned_vehicle = v.number_plate
        WHERE b.assigned_vehicle IN ($placeholders)
        AND b.phone IS NOT NULL
        AND b.phone != ''
        AND b.user_id IS NOT NULL
    ");
    $no_date_stmt->bind_param($types, ...$vehicles);
    $no_date_stmt->execute();
    $no_date_result = $no_date_stmt->get_result()->fetch_assoc();
    echo "<div class='info'>Without date filter: {$no_date_result['count']} bookings</div>";
    
    // Check with just vehicle filter
    $vehicle_only_stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM bookings b
        WHERE b.assigned_vehicle IN ($placeholders)
    ");
    $vehicle_only_stmt->bind_param($types, ...$vehicles);
    $vehicle_only_stmt->execute();
    $vehicle_only_result = $vehicle_only_stmt->get_result()->fetch_assoc();
    echo "<div class='info'>With only vehicle filter: {$vehicle_only_result['count']} bookings</div>";
    
    // Check today's date format
    echo "<div class='info'>Today's date: " . date('Y-m-d') . " (CURDATE(): " . $conn->query("SELECT CURDATE() as today")->fetch_assoc()['today'] . ")</div>";
}

echo "<h2>Step 4: Solution</h2>";

if ($today_count == 0) {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Issue Found: No bookings for today</h3>";
    echo "<p>The passenger booking exists but the travel_date is not today.</p>";
    echo "<p><strong>Solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Update the booking's travel_date to today</li>";
    echo "<li>Or modify the query to include recent bookings</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<p><strong>Quick fix - Update booking to today:</strong></p>";
    echo "<p><a href='#' onclick=\"updateBookingDate()\">Click here to update the most recent booking to today's date</a></p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>❌ Issue: Filters too strict</h3>";
    echo "<p>There are today's bookings but they don't pass all the filters.</p>";
    echo "<p>Check if phone numbers or user_ids are NULL/empty.</p>";
    echo "</div>";
}

?>

<script>
function updateBookingDate() {
    if (confirm('Update the most recent booking to today\'s date?')) {
        window.open('update_booking_for_testing.php', '_blank');
    }
}
</script>