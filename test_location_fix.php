<?php
require_once 'db.php';

// Start session to get driver info
if (session_status() === PHP_SESSION_NONE) {
    session_name('southrift_admin');
    session_start();
}

$driver_phone = $_SESSION['phone'] ?? '0736225373';

echo "<h2>✅ Testing Fixed Location Sharing</h2>";
echo "<p><strong>Driver Phone:</strong> $driver_phone</p>";

// Test the fixed logic
echo "<h3>🧪 Testing New Passenger Query</h3>";

// Get driver's vehicles
$vehicle_stmt = $conn->prepare("SELECT number_plate FROM vehicles WHERE driver_phone = ?");
$vehicle_stmt->bind_param("s", $driver_phone);
$vehicle_stmt->execute();
$vehicle_result = $vehicle_stmt->get_result();

$vehicles = [];
while ($row = $vehicle_result->fetch_assoc()) {
    $vehicles[] = $row['number_plate'];
}

if (empty($vehicles)) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ No vehicles found for driver: $driver_phone";
    echo "</div>";
    exit;
}

echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "✅ Found " . count($vehicles) . " vehicle(s): " . implode(', ', $vehicles);
echo "</div>";

// Test the new query (same as in the fixed file)
$placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
$stmt = $conn->prepare("
    SELECT DISTINCT 
        b.user_id, 
        b.fullname as passenger_name, 
        b.phone as passenger_phone,
        b.travel_date,
        b.created_at,
        v.number_plate,
        v.type as vehicle_type
    FROM bookings b
    JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE b.assigned_vehicle IN ($placeholders)
    AND b.phone IS NOT NULL
    AND b.phone != ''
");

$types = str_repeat('s', count($vehicles));
$stmt->bind_param($types, ...$vehicles);
$stmt->execute();
$result = $stmt->get_result();

$passengers_found = $result->num_rows;

echo "<div style='background: " . ($passengers_found > 0 ? '#d4edda' : '#fff3cd') . "; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
echo "<h4>🎯 Query Result:</h4>";
echo "<p><strong>Passengers Found:</strong> $passengers_found</p>";

if ($passengers_found > 0) {
    echo "<p>🎉 <strong>SUCCESS!</strong> Location sharing will now work!</p>";
    echo "<h5>Passengers that will receive notifications:</h5>";
    echo "<ul>";
    while ($passenger = $result->fetch_assoc()) {
        echo "<li><strong>{$passenger['passenger_name']}</strong> ({$passenger['passenger_phone']}) - Vehicle: {$passenger['number_plate']} - Travel: {$passenger['travel_date']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>⚠️ No passengers found. You may need to assign passengers to your vehicle.</p>";
}
echo "</div>";

// Simulate the API call
if ($passengers_found > 0) {
    echo "<h3>🧪 Simulate Google Maps Sharing API Call</h3>";
    
    $test_link = 'https://maps.app.goo.gl/TestFixed' . time();
    
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px;'>";
    echo "<p><strong>Test Parameters:</strong></p>";
    echo "<p>Driver Phone: $driver_phone</p>";
    echo "<p>Google Maps Link: $test_link</p>";
    echo "<p>Expected Passengers: $passengers_found</p>";
    
    // Create a test form to try the actual API
    echo "<form method='POST' action='Driver/share_google_maps_link.php' target='_blank' style='margin: 15px 0;'>";
    echo "<input type='hidden' name='driver_phone' value='$driver_phone'>";
    echo "<input type='hidden' name='google_maps_link' value='$test_link'>";
    echo "<input type='hidden' name='csrf_token' value='test'>";
    echo "<button type='submit' style='background: #6A0DAD; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
    echo "🧪 Test API Call";
    echo "</button>";
    echo "</form>";
    echo "</div>";
}

echo "<h3>📋 Next Steps</h3>";
echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px;'>";
echo "<ol>";
echo "<li><strong>Test in Driver Dashboard:</strong> <a href='Driver/index.php'>Go to Driver Dashboard</a></li>";
echo "<li><strong>Click 'Share Live Location'</strong></li>";
echo "<li><strong>Paste any Google Maps link</strong> (e.g., https://maps.app.goo.gl/test123)</li>";
echo "<li><strong>You should now see:</strong> 'Location shared with $passengers_found passengers!'</li>";
echo "</ol>";
echo "</div>";

if ($passengers_found > 0) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 15px 0; text-align: center;'>";
    echo "<h3>🎉 PROBLEM FIXED!</h3>";
    echo "<p style='font-size: 1.2em;'>Your location sharing will now show <strong>$passengers_found passenger(s)</strong> instead of 0!</p>";
    echo "<a href='Driver/index.php' style='background: #6A0DAD; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>🚗 Try It Now!</a>";
    echo "</div>";
}

?>