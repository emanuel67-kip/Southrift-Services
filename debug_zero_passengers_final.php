<?php
require_once 'db.php';

echo "<h2>🔧 REAL-TIME DEBUG: Why Still 0 Passengers?</h2>";

// Check current session
session_start();
$session_driver_phone = $_SESSION['phone'] ?? 'NOT_SET';

echo "<h3>1. Session Check</h3>";
echo "<strong>Session driver phone:</strong> $session_driver_phone<br>";

// Use session phone if available, otherwise default
$driver_phone = $session_driver_phone !== 'NOT_SET' ? $session_driver_phone : '0736225373';
echo "<strong>Testing with driver phone:</strong> $driver_phone<br>";

echo "<h3>2. Step-by-Step Debug</h3>";

// Step 1: Check driver exists
$driver_stmt = $conn->prepare("SELECT id, name FROM drivers WHERE driver_phone = ?");
$driver_stmt->bind_param("s", $driver_phone);
$driver_stmt->execute();
$driver_result = $driver_stmt->get_result();
$driver = $driver_result->fetch_assoc();

if ($driver) {
    echo "✅ <strong>Step 1:</strong> Driver found - {$driver['name']} (ID: {$driver['id']})<br>";
} else {
    echo "❌ <strong>Step 1:</strong> Driver NOT found for phone: $driver_phone<br>";
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
    echo "<strong>Solution:</strong> Make sure you're logged in as the correct driver or the driver exists in the database.";
    echo "</div>";
    exit;
}

// Step 2: Check vehicles
$vehicle_stmt = $conn->prepare("SELECT number_plate FROM vehicles WHERE driver_phone = ?");
$vehicle_stmt->bind_param("s", $driver_phone);
$vehicle_stmt->execute();
$vehicle_result = $vehicle_stmt->get_result();
$vehicles = [];
while ($row = $vehicle_result->fetch_assoc()) {
    $vehicles[] = $row['number_plate'];
}

if (!empty($vehicles)) {
    echo "✅ <strong>Step 2:</strong> Vehicles found - " . implode(', ', $vehicles) . "<br>";
} else {
    echo "❌ <strong>Step 2:</strong> NO vehicles found for driver<br>";
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
    echo "<strong>Solution:</strong> Assign a vehicle to this driver in the vehicles table.";
    echo "</div>";
    exit;
}

// Step 3: Check exact query from share_google_maps_link.php
echo "✅ <strong>Step 3:</strong> Testing exact query from share_google_maps_link.php<br>";

$placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
echo "<strong>Vehicles to check:</strong> " . implode(', ', $vehicles) . "<br>";

$query = "
    SELECT DISTINCT 
        b.user_id, 
        b.fullname as passenger_name, 
        b.phone as passenger_phone,
        v.number_plate,
        v.type as vehicle_type,
        b.created_at,
        b.travel_date
    FROM bookings b
    JOIN vehicles v ON b.assigned_vehicle = v.number_plate
    WHERE b.assigned_vehicle IN ($placeholders)
    AND DATE(b.created_at) = CURDATE()
";

echo "<strong>Query being used:</strong><br>";
echo "<code style='background: #f8f9fa; padding: 5px; display: block; font-size: 12px;'>";
echo str_replace($placeholders, "'" . implode("', '", $vehicles) . "'", $query);
echo "</code>";

$stmt = $conn->prepare($query);
$types = str_repeat('s', count($vehicles));
$stmt->bind_param($types, ...$vehicles);
$stmt->execute();
$result = $stmt->get_result();

echo "<strong>Result:</strong> {$result->num_rows} passengers found<br>";

if ($result->num_rows > 0) {
    echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "✅ <strong>PASSENGERS FOUND!</strong> The query is working.<br>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
    echo "<tr><th>User ID</th><th>Name</th><th>Phone</th><th>Vehicle</th><th>Created</th><th>Travel Date</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['passenger_name']}</td>";
        echo "<td>{$row['passenger_phone']}</td>";
        echo "<td>{$row['number_plate']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "<td>{$row['travel_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>NO PASSENGERS FOUND</strong> with the current query.<br>";
    echo "</div>";
    
    // Debug: Check all bookings for this vehicle
    echo "<h4>🔍 Debug: All bookings for vehicles</h4>";
    $all_stmt = $conn->prepare("
        SELECT b.*, DATE(b.created_at) as assignment_date 
        FROM bookings b 
        WHERE b.assigned_vehicle IN ($placeholders)
        ORDER BY b.created_at DESC
    ");
    $all_stmt->bind_param($types, ...$vehicles);
    $all_stmt->execute();
    $all_result = $all_stmt->get_result();
    
    if ($all_result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
        echo "<tr><th>Booking ID</th><th>Name</th><th>Phone</th><th>Vehicle</th><th>Assignment Date</th><th>Travel Date</th><th>Match Today?</th></tr>";
        $today = date('Y-m-d');
        while ($row = $all_result->fetch_assoc()) {
            $is_today = ($row['assignment_date'] == $today) ? '✅ YES' : '❌ NO';
            $highlight = ($row['assignment_date'] == $today) ? 'background: #d4edda;' : '';
            echo "<tr style='$highlight'>";
            echo "<td>{$row['booking_id']}</td>";
            echo "<td>{$row['fullname']}</td>";
            echo "<td>{$row['phone']}</td>";
            echo "<td>{$row['assigned_vehicle']}</td>";
            echo "<td>{$row['assignment_date']}</td>";
            echo "<td>{$row['travel_date']}</td>";
            echo "<td>$is_today</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<br><strong>Today's date:</strong> $today<br>";
    } else {
        echo "❌ No bookings found at all for these vehicles.<br>";
    }
}

echo "<h3>3. Test the Actual API Call</h3>";

if ($session_driver_phone !== 'NOT_SET') {
    echo "<p>🧪 <strong>Testing actual Google Maps sharing API...</strong></p>";
    
    // Simulate API call
    $google_maps_link = 'https://maps.app.goo.gl/DebugTest' . time();
    
    // Test the notification function directly
    echo "<h4>📡 Direct function test:</h4>";
    
    // Include the function from share_google_maps_link.php
    function testNotifyPassengersGoogleMapsLink($conn, $driver_phone, $google_maps_link) {
        try {
            // Get all vehicles assigned to this driver
            $vehicles = [];
            $vehicle_stmt = $conn->prepare("SELECT number_plate FROM vehicles WHERE driver_phone = ?");
            $vehicle_stmt->bind_param("s", $driver_phone);
            $vehicle_stmt->execute();
            $vehicle_result = $vehicle_stmt->get_result();
            while ($row = $vehicle_result->fetch_assoc()) {
                $vehicles[] = $row['number_plate'];
            }
            $vehicle_stmt->close();
            
            echo "Function found " . count($vehicles) . " vehicles: " . implode(', ', $vehicles) . "<br>";

            if (empty($vehicles)) {
                echo "Function returned 0 - no vehicles found<br>";
                return 0;
            }

            // Get today's bookings for all driver's vehicles - focus on assignments made today
            $placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
            $stmt = $conn->prepare("
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
            ");

            $types = str_repeat('s', count($vehicles));
            $stmt->bind_param($types, ...$vehicles);
            $stmt->execute();
            $result = $stmt->get_result();

            $passengers_notified = 0;
            
            echo "Function query found " . $result->num_rows . " passengers<br>";

            // Create notifications for each passenger
            while ($passenger = $result->fetch_assoc()) {
                echo "Processing passenger: {$passenger['passenger_name']}<br>";
                $passengers_notified++;
            }

            return $passengers_notified;

        } catch (Exception $e) {
            echo "Function error: " . $e->getMessage() . "<br>";
            return 0;
        }
    }
    
    $test_result = testNotifyPassengersGoogleMapsLink($conn, $driver_phone, $google_maps_link);
    
    echo "<div style='background: " . ($test_result > 0 ? '#d4edda' : '#f8d7da') . "; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Function Result:</strong> $test_result passengers<br>";
    
    if ($test_result > 0) {
        echo "✅ The function SHOULD return $test_result passengers when called from the API!<br>";
        echo "<strong>If you're still seeing 0, check:</strong><br>";
        echo "- Are you logged in as driver $driver_phone?<br>";
        echo "- Clear browser cache (Ctrl+F5)<br>";
        echo "- Check browser console for JavaScript errors<br>";
    } else {
        echo "❌ The function returns 0 passengers - this is the problem!<br>";
    }
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px;'>";
    echo "⚠️ <strong>Not logged in as driver.</strong> Please login to test the actual API call.";
    echo "</div>";
}

echo "<h3>4. Quick Fix Test</h3>";
echo "<p><a href='test_live_sharing.php' style='background: #6A0DAD; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🧪 Try Direct API Test</a></p>";

?>