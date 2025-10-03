<?php
require_once 'db.php';
date_default_timezone_set('Africa/Nairobi');

echo "<h2>🔍 Assignment Check - Focus on Today's Assignments</h2>";

// Test with driver 0736225373
$driver_phone = '0736225373';

echo "<h3>1. Driver Information</h3>";
$driver_stmt = $conn->prepare("SELECT * FROM drivers WHERE driver_phone = ?");
$driver_stmt->bind_param("s", $driver_phone);
$driver_stmt->execute();
$driver_result = $driver_stmt->get_result();
$driver = $driver_result->fetch_assoc();

if ($driver) {
    echo "✅ Driver found: {$driver['name']} ({$driver['driver_phone']})<br>";
} else {
    echo "❌ Driver not found<br>";
    exit;
}

echo "<h3>2. Driver's Vehicles</h3>";
$vehicle_stmt = $conn->prepare("SELECT number_plate, type, capacity FROM vehicles WHERE driver_phone = ?");
$vehicle_stmt->bind_param("s", $driver_phone);
$vehicle_stmt->execute();
$vehicle_result = $vehicle_stmt->get_result();

$vehicles = [];
while ($v = $vehicle_result->fetch_assoc()) {
    $vehicles[] = $v['number_plate'];
    echo "✅ Vehicle: {$v['number_plate']} ({$v['type']}, {$v['capacity']} seats)<br>";
}

if (empty($vehicles)) {
    echo "❌ No vehicles found for this driver<br>";
    exit;
}

// Check for bookings table structure first
echo "<h3>3. Database Structure Check</h3>";
$columns_query = "DESCRIBE bookings";
$columns_result = $conn->query($columns_query);
$columns = [];
while ($col = $columns_result->fetch_assoc()) {
    $columns[] = $col['Field'];
}
echo "📋 Bookings table columns: " . implode(', ', $columns) . "<br><br>";

// Check if there's an assignment date or updated_at field
$has_updated_at = in_array('updated_at', $columns);
$has_assigned_at = in_array('assigned_at', $columns);
$has_assignment_date = in_array('assignment_date', $columns);

echo "<h3>4. All Bookings for Driver's Vehicles (Any Date)</h3>";
$placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
$all_bookings_query = "
    SELECT b.booking_id, b.user_id, b.fullname, b.phone, b.assigned_vehicle, 
           b.travel_date, b.created_at" . 
           ($has_updated_at ? ", b.updated_at" : "") . 
           ($has_assigned_at ? ", b.assigned_at" : "") . 
           ($has_assignment_date ? ", b.assignment_date" : "") . "
    FROM bookings b
    WHERE b.assigned_vehicle IN ($placeholders)
    ORDER BY b.created_at DESC
";

$types = str_repeat('s', count($vehicles));
$all_stmt = $conn->prepare($all_bookings_query);
$all_stmt->bind_param($types, ...$vehicles);
$all_stmt->execute();
$all_result = $all_stmt->get_result();

if ($all_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Booking ID</th><th>Passenger</th><th>Phone</th><th>Vehicle</th><th>Travel Date</th><th>Created</th>";
    if ($has_updated_at) echo "<th>Updated</th>";
    if ($has_assigned_at) echo "<th>Assigned At</th>";
    if ($has_assignment_date) echo "<th>Assignment Date</th>";
    echo "</tr>";
    
    $today = date('Y-m-d');
    $bookings_for_today_travel = 0;
    $bookings_assigned_today = 0;
    
    while ($booking = $all_result->fetch_assoc()) {
        $is_today_travel = (date('Y-m-d', strtotime($booking['travel_date'])) == $today);
        $is_today_created = (date('Y-m-d', strtotime($booking['created_at'])) == $today);
        $is_today_updated = $has_updated_at ? (date('Y-m-d', strtotime($booking['updated_at'])) == $today) : false;
        $is_today_assigned = $has_assigned_at ? (date('Y-m-d', strtotime($booking['assigned_at'])) == $today) : false;
        
        if ($is_today_travel) $bookings_for_today_travel++;
        if ($is_today_created || $is_today_updated || $is_today_assigned) $bookings_assigned_today++;
        
        $row_style = '';
        if ($is_today_travel) $row_style .= 'background-color: #d4edda;'; // Green for today's travel
        if ($is_today_created || $is_today_updated || $is_today_assigned) $row_style .= 'border: 3px solid #007bff;'; // Blue border for today's assignment
        
        echo "<tr style='$row_style'>";
        echo "<td>{$booking['booking_id']}</td>";
        echo "<td>{$booking['fullname']}</td>";
        echo "<td>{$booking['phone']}</td>";
        echo "<td>{$booking['assigned_vehicle']}</td>";
        echo "<td>{$booking['travel_date']}</td>";
        echo "<td>" . date('Y-m-d H:i', strtotime($booking['created_at'])) . "</td>";
        if ($has_updated_at) echo "<td>" . ($booking['updated_at'] ? date('Y-m-d H:i', strtotime($booking['updated_at'])) : '-') . "</td>";
        if ($has_assigned_at) echo "<td>" . ($booking['assigned_at'] ? date('Y-m-d H:i', strtotime($booking['assigned_at'])) : '-') . "</td>";
        if ($has_assignment_date) echo "<td>" . ($booking['assignment_date'] ? $booking['assignment_date'] : '-') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><strong>Legend:</strong><br>";
    echo "🟢 Green background = Travel date is today<br>";
    echo "🔵 Blue border = Assignment happened today<br>";
    echo "<br><strong>Summary:</strong><br>";
    echo "📅 Bookings with travel date = today: $bookings_for_today_travel<br>";
    echo "📋 Bookings assigned today: $bookings_assigned_today<br>";
    
} else {
    echo "❌ No bookings found for any of driver's vehicles<br>";
}

echo "<h3>5. Suggested Query for Google Maps Sharing</h3>";
echo "<p>Based on your requirement to focus on today's assignments rather than today's travel, here are the options:</p>";

if ($has_updated_at) {
    echo "<strong>Option 1: Use updated_at (when assignment was modified)</strong><br>";
    echo "<code>WHERE b.assigned_vehicle IN (...) AND DATE(b.updated_at) = CURDATE()</code><br><br>";
}

if ($has_assigned_at) {
    echo "<strong>Option 2: Use assigned_at (if it tracks assignment date)</strong><br>";
    echo "<code>WHERE b.assigned_vehicle IN (...) AND DATE(b.assigned_at) = CURDATE()</code><br><br>";
}

echo "<strong>Option 3: Use created_at (when booking was made)</strong><br>";
echo "<code>WHERE b.assigned_vehicle IN (...) AND DATE(b.created_at) = CURDATE()</code><br><br>";

echo "<strong>Option 4: Remove date filter entirely (show all assigned passengers)</strong><br>";
echo "<code>WHERE b.assigned_vehicle IN (...)</code><br><br>";

echo "<p><strong>Which approach would you like me to implement?</strong></p>";

?>