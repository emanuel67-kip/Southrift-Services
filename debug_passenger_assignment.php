<?php
require_once 'db.php';

// Start session to get driver info
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    ini_set('session.use_only_cookies', 1);
    session_name('southrift_admin');
    session_start();
}

$driver_phone = $_SESSION['phone'] ?? '0736225373'; // Default for testing

echo "<h2>🔍 Debug: Why 0 Passengers Found?</h2>";
echo "<p><strong>Driver Phone:</strong> $driver_phone</p>";

// Step 1: Check if driver has vehicles
echo "<h3>1. Check Driver's Vehicles</h3>";
$vehicle_stmt = $conn->prepare("SELECT number_plate, type, driver_name FROM vehicles WHERE driver_phone = ?");
$vehicle_stmt->bind_param("s", $driver_phone);
$vehicle_stmt->execute();
$vehicle_result = $vehicle_stmt->get_result();

$vehicles = [];
if ($vehicle_result->num_rows > 0) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "✅ Found " . $vehicle_result->num_rows . " vehicle(s):<br>";
    while ($row = $vehicle_result->fetch_assoc()) {
        $vehicles[] = $row['number_plate'];
        echo "🚗 <strong>{$row['number_plate']}</strong> - {$row['type']} (Driver: {$row['driver_name']})<br>";
    }
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ No vehicles found for driver phone: $driver_phone<br>";
    echo "</div>";
    
    // Show all vehicles for debugging
    echo "<h4>All Vehicles in System:</h4>";
    $all_vehicles = $conn->query("SELECT number_plate, driver_phone, driver_name FROM vehicles LIMIT 10");
    while ($v = $all_vehicles->fetch_assoc()) {
        echo "- {$v['number_plate']} → {$v['driver_phone']} ({$v['driver_name']})<br>";
    }
    exit;
}

// Step 2: Check ALL bookings assigned to these vehicles
echo "<h3>2. ALL Bookings for Driver's Vehicles</h3>";
if (!empty($vehicles)) {
    $placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
    $stmt = $conn->prepare("
        SELECT 
            b.booking_id,
            b.user_id, 
            b.fullname, 
            b.phone, 
            b.assigned_vehicle,
            b.travel_date,
            b.created_at,
            DATE(b.created_at) as assignment_date
        FROM bookings b
        WHERE b.assigned_vehicle IN ($placeholders)
        ORDER BY b.created_at DESC
        LIMIT 20
    ");
    
    $types = str_repeat('s', count($vehicles));
    $stmt->bind_param($types, ...$vehicles);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px;'>";
        echo "✅ Found " . $result->num_rows . " booking(s):<br>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #6A0DAD; color: white;'>";
        echo "<th>Booking ID</th><th>Passenger</th><th>Phone</th><th>Vehicle</th><th>Travel Date</th><th>Assignment Date</th><th>Today Assignment?</th></tr>";
        
        $today = date('Y-m-d');
        $assigned_today_count = 0;
        
        while ($row = $result->fetch_assoc()) {
            $is_today_assignment = ($row['assignment_date'] == $today);
            if ($is_today_assignment) $assigned_today_count++;
            
            $highlight = $is_today_assignment ? 'background: #d4edda;' : '';
            echo "<tr style='$highlight'>";
            echo "<td>{$row['booking_id']}</td>";
            echo "<td>{$row['fullname']}</td>";
            echo "<td>{$row['phone']}</td>";
            echo "<td>{$row['assigned_vehicle']}</td>";
            echo "<td>{$row['travel_date']}</td>";
            echo "<td>{$row['assignment_date']}</td>";
            echo "<td>" . ($is_today_assignment ? '✅ YES' : '❌ NO') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<strong>Today's Date:</strong> $today<br>";
        echo "<strong>Assignments Made Today:</strong> $assigned_today_count<br>";
        echo "</div>";
        
        if ($assigned_today_count == 0) {
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>⚠️ PROBLEM IDENTIFIED!</h4>";
            echo "<p>No bookings were assigned to your vehicles TODAY. The current logic looks for assignments made today.</p>";
            echo "<p><strong>Solution Options:</strong></p>";
            echo "<ol>";
            echo "<li><strong>Show all assigned passengers</strong> (regardless of assignment date)</li>";
            echo "<li><strong>Show passengers traveling today</strong> (regardless of assignment date)</li>";
            echo "<li><strong>Create a test booking</strong> assigned today</li>";
            echo "</ol>";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "❌ No bookings found for any of your vehicles<br>";
        echo "</div>";
    }
}

// Step 3: Test different query approaches
echo "<h3>3. Test Different Query Approaches</h3>";

if (!empty($vehicles)) {
    // Approach 1: All assigned passengers (no date filter)
    echo "<h4>Approach 1: All Assigned Passengers</h4>";
    $stmt1 = $conn->prepare("
        SELECT COUNT(DISTINCT b.user_id) as count
        FROM bookings b
        WHERE b.assigned_vehicle IN ($placeholders)
        AND b.phone IS NOT NULL
        AND b.phone != ''
    ");
    $stmt1->bind_param($types, ...$vehicles);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    $count1 = $result1->fetch_assoc()['count'];
    echo "Result: <strong>$count1</strong> passengers<br>";
    
    // Approach 2: Passengers traveling today
    echo "<h4>Approach 2: Passengers Traveling Today</h4>";
    $stmt2 = $conn->prepare("
        SELECT COUNT(DISTINCT b.user_id) as count
        FROM bookings b
        WHERE b.assigned_vehicle IN ($placeholders)
        AND DATE(b.travel_date) = CURDATE()
        AND b.phone IS NOT NULL
        AND b.phone != ''
    ");
    $stmt2->bind_param($types, ...$vehicles);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $count2 = $result2->fetch_assoc()['count'];
    echo "Result: <strong>$count2</strong> passengers<br>";
    
    // Approach 3: Assignments made today (current logic)
    echo "<h4>Approach 3: Assignments Made Today (Current Logic)</h4>";
    $stmt3 = $conn->prepare("
        SELECT COUNT(DISTINCT b.user_id) as count
        FROM bookings b
        WHERE b.assigned_vehicle IN ($placeholders)
        AND DATE(b.created_at) = CURDATE()
        AND b.phone IS NOT NULL
        AND b.phone != ''
    ");
    $stmt3->bind_param($types, ...$vehicles);
    $stmt3->execute();
    $result3 = $stmt3->get_result();
    $count3 = $result3->fetch_assoc()['count'];
    echo "Result: <strong>$count3</strong> passengers (this is why you see 0)<br>";
}

echo "<h3>4. Recommended Solution</h3>";
echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h4>💡 Recommendation</h4>";
echo "<p>Since you have assigned passengers but they weren't assigned today, I recommend changing the logic to:</p>";
echo "<p><strong>Option 1:</strong> Show all assigned passengers (most practical)</p>";
echo "<p><strong>Option 2:</strong> Show passengers traveling today</p>";
echo "<p>Which would you prefer?</p>";
echo "</div>";

echo "<h3>5. Quick Fix Options</h3>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='fix_passenger_assignment_logic.php?approach=all' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔧 Fix: Show All Assigned</a>";
echo "<a href='fix_passenger_assignment_logic.php?approach=travel_today' style='background: #17a2b8; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔧 Fix: Show Travel Today</a>";
echo "<a href='create_test_booking.php' style='background: #6A0DAD; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🧪 Create Test Booking</a>";
echo "</div>";

?>