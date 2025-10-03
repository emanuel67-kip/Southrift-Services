<?php
require_once 'db.php';

// Get approach from URL parameter
$approach = $_GET['approach'] ?? 'all';

echo "<h2>🔧 Fix Passenger Assignment Logic</h2>";
echo "<p><strong>Selected Approach:</strong> " . ucfirst(str_replace('_', ' ', $approach)) . "</p>";

// Define the new query based on approach
$new_query = '';
switch ($approach) {
    case 'all':
        $new_query = '
            SELECT DISTINCT 
                b.user_id, 
                b.fullname as passenger_name, 
                b.phone as passenger_phone,
                v.number_plate,
                v.type as vehicle_type
            FROM bookings b
            JOIN vehicles v ON b.assigned_vehicle = v.number_plate
            WHERE b.assigned_vehicle IN ($placeholders)
            AND b.phone IS NOT NULL
            AND b.phone != \'\'
        ';
        $description = "Show all passengers assigned to the driver's vehicles (recommended)";
        break;
        
    case 'travel_today':
        $new_query = '
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
            AND b.phone IS NOT NULL
            AND b.phone != \'\'
        ';
        $description = "Show passengers traveling today";
        break;
        
    default:
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>❌ Invalid approach selected</div>";
        exit;
}

echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
echo "<h4>📋 What this fix does:</h4>";
echo "<p>$description</p>";
echo "<p><strong>Current problem:</strong> Looking for assignments made today only</p>";
echo "<p><strong>New solution:</strong> " . ucfirst(str_replace('_', ' ', $approach)) . "</p>";
echo "</div>";

// Backup and update the share_google_maps_link.php file
$file_path = 'Driver/share_google_maps_link.php';
$backup_path = 'Driver/share_google_maps_link.php.backup.' . date('Y-m-d-H-i-s');

echo "<h3>1. Creating Backup</h3>";
if (copy($file_path, $backup_path)) {
    echo "✅ Backup created: $backup_path<br>";
} else {
    echo "❌ Failed to create backup<br>";
    exit;
}

echo "<h3>2. Updating Query Logic</h3>";

// Read the current file
$content = file_get_contents($file_path);

// Find and replace the problematic query
$old_pattern = '/SELECT DISTINCT\s+.*?\s+FROM bookings b\s+JOIN vehicles v ON b\.assigned_vehicle = v\.number_plate\s+WHERE b\.assigned_vehicle IN \(\$placeholders\)\s+AND DATE\(b\.created_at\) = CURDATE\(\)/s';

$new_replacement = trim($new_query);

if (preg_match($old_pattern, $content)) {
    $updated_content = preg_replace($old_pattern, $new_replacement, $content);
    
    if (file_put_contents($file_path, $updated_content)) {
        echo "✅ Query updated successfully<br>";
        
        echo "<h3>3. Testing New Logic</h3>";
        
        // Test the new logic
        if (session_status() === PHP_SESSION_NONE) {
            session_name('southrift_admin');
            session_start();
        }
        
        $driver_phone = $_SESSION['phone'] ?? '0736225373';
        
        // Get driver's vehicles
        $vehicle_stmt = $conn->prepare("SELECT number_plate FROM vehicles WHERE driver_phone = ?");
        $vehicle_stmt->bind_param("s", $driver_phone);
        $vehicle_stmt->execute();
        $vehicle_result = $vehicle_stmt->get_result();
        
        $vehicles = [];
        while ($row = $vehicle_result->fetch_assoc()) {
            $vehicles[] = $row['number_plate'];
        }
        
        if (!empty($vehicles)) {
            // Test the new query
            $placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
            $test_query = str_replace('$placeholders', $placeholders, $new_replacement);
            
            $stmt = $conn->prepare($test_query);
            $types = str_repeat('s', count($vehicles));
            $stmt->bind_param($types, ...$vehicles);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $passenger_count = $result->num_rows;
            
            echo "<div style='background: " . ($passenger_count > 0 ? '#d4edda' : '#fff3cd') . "; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>🧪 Test Result:</h4>";
            echo "<p><strong>Passengers found:</strong> $passenger_count</p>";
            
            if ($passenger_count > 0) {
                echo "<p>✅ <strong>SUCCESS!</strong> The location sharing should now work!</p>";
                echo "<h5>Passengers found:</h5>";
                echo "<ul>";
                while ($passenger = $result->fetch_assoc()) {
                    echo "<li>{$passenger['passenger_name']} ({$passenger['passenger_phone']}) - Vehicle: {$passenger['number_plate']}</li>";
                }
                echo "</ul>";
            } else {
                echo "<p>⚠️ Still no passengers found. You may need to assign passengers to your vehicle first.</p>";
            }
            echo "</div>";
        }
        
    } else {
        echo "❌ Failed to update file<br>";
    }
} else {
    echo "❌ Could not find the target query pattern in the file<br>";
    echo "<h4>Manual Update Required:</h4>";
    echo "<p>Please manually update the query in the notifyPassengersGoogleMapsLink function around line 250.</p>";
    echo "<p><strong>Replace the query with:</strong></p>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>" . htmlspecialchars($new_replacement) . "</pre>";
}

echo "<h3>4. Next Steps</h3>";
echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
echo "<h4>🎯 Test the Fix:</h4>";
echo "<ol>";
echo "<li>Go to the <a href='Driver/index.php'>Driver Dashboard</a></li>";
echo "<li>Click 'Share Live Location'</li>";
echo "<li>Enter a Google Maps link</li>";
echo "<li>You should now see more than 0 passengers notified!</li>";
echo "</ol>";
echo "</div>";

echo "<div style='margin: 20px 0;'>";
echo "<a href='Driver/index.php' style='background: #6A0DAD; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🚗 Test in Driver Dashboard</a>";
echo "<a href='debug_passenger_assignment.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔍 Debug Again</a>";
echo "<a href='$backup_path' style='background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>📄 View Backup</a>";
echo "</div>";

if ($passenger_count > 0) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 15px 0;'>";
    echo "<h4>🎉 PROBLEM SOLVED!</h4>";
    echo "<p>Your location sharing should now work and show <strong>$passenger_count passenger(s)</strong> instead of 0!</p>";
    echo "</div>";
}

?>