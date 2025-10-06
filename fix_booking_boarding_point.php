<?php
require_once 'db.php';

echo "<h2>Fix Booking Boarding Point</h2>";

// Show current booking details
echo "<h3>Current Booking Details</h3>";
$stmt = $conn->prepare("SELECT booking_id, fullname, phone, route, boarding_point FROM bookings WHERE booking_id = ?");
$stmt->bind_param("i", 35);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    echo "<p><strong>Booking ID 35:</strong></p>";
    echo "<ul>";
    echo "<li>Passenger: " . htmlspecialchars($row['fullname']) . "</li>";
    echo "<li>Phone: " . htmlspecialchars($row['phone']) . "</li>";
    echo "<li>Route: " . htmlspecialchars($row['route']) . "</li>";
    echo "<li>Boarding Point: <span style='color: red;'>" . htmlspecialchars($row['boarding_point']) . "</span></li>";
    echo "</ul>";
    
    // Ask user what they want to do
    echo "<h3>Options to Fix the Issue</h3>";
    
    // Option 1: Change boarding point to "Litein"
    echo "<h4>Option 1: Change Boarding Point to 'Litein'</h4>";
    echo "<p>This will make the booking visible to the Litein admin.</p>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='action' value='change_boarding_point'>";
    echo "<input type='hidden' name='booking_id' value='35'>";
    echo "<input type='hidden' name='new_boarding_point' value='Litein'>";
    echo "<button type='submit'>Change Boarding Point to 'Litein'</button>";
    echo "</form>";
    
    // Option 2: Change Litein admin's station to "Kaplong"
    echo "<h4>Option 2: Change Litein Admin's Station to 'Kaplong'</h4>";
    echo "<p>This will make the Litein admin see bookings from Kaplong.</p>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='action' value='change_admin_station'>";
    echo "<input type='hidden' name='new_station' value='Kaplong'>";
    echo "<button type='submit'>Change Litein Admin Station to 'Kaplong'</button>";
    echo "</form>";
    
    // Option 3: Add Kaplong as a separate station
    echo "<h4>Option 3: Add Kaplong as a Separate Station</h4>";
    echo "<p>Create a new admin for Kaplong station.</p>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='action' value='add_kaplong_admin'>";
    echo "<button type='submit'>Add Kaplong Admin</button>";
    echo "</form>";
    
} else {
    echo "<p>Booking ID 35 not found.</p>";
}

$stmt->close();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'change_boarding_point') {
        $booking_id = (int)$_POST['booking_id'];
        $new_boarding_point = $_POST['new_boarding_point'];
        
        $updateStmt = $conn->prepare("UPDATE bookings SET boarding_point = ? WHERE booking_id = ?");
        $updateStmt->bind_param("si", $new_boarding_point, $booking_id);
        
        if ($updateStmt->execute()) {
            echo "<p style='color: green;'>✅ Successfully changed boarding point to '$new_boarding_point'.</p>";
            echo "<p>The Litein admin should now see this booking.</p>";
        } else {
            echo "<p style='color: red;'>❌ Error updating boarding point: " . $conn->error . "</p>";
        }
        $updateStmt->close();
        
    } elseif ($action === 'change_admin_station') {
        $new_station = $_POST['new_station'];
        
        // Find the Litein admin
        $adminStmt = $conn->prepare("SELECT id, name FROM users WHERE email = 'adminlitein@gmail.com' AND role = 'admin'");
        $adminStmt->execute();
        $adminResult = $adminStmt->get_result();
        
        if ($adminResult && $admin = $adminResult->fetch_assoc()) {
            $admin_id = $admin['id'];
            $admin_name = $admin['name'];
            
            $updateStmt = $conn->prepare("UPDATE users SET station = ? WHERE id = ?");
            $updateStmt->bind_param("si", $new_station, $admin_id);
            
            if ($updateStmt->execute()) {
                echo "<p style='color: green;'>✅ Successfully changed $admin_name's station to '$new_station'.</p>";
                echo "<p>The Litein admin will now see bookings from $new_station.</p>";
            } else {
                echo "<p style='color: red;'>❌ Error updating admin station: " . $conn->error . "</p>";
            }
            $updateStmt->close();
        } else {
            echo "<p style='color: red;'>❌ Could not find Litein admin.</p>";
        }
        $adminStmt->close();
        
    } elseif ($action === 'add_kaplong_admin') {
        // Add a new admin for Kaplong
        $name = 'Kaplong Admin';
        $email = 'adminkaplong@gmail.com';
        $phone = '254700000006';
        $password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // 'password'
        $role = 'admin';
        $station = 'Kaplong';
        $status = 'active';
        
        // Check if admin already exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            echo "<p>⚠️ Kaplong admin already exists.</p>";
        } else {
            $insertStmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, station, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insertStmt->bind_param("sssssss", $name, $email, $phone, $password, $role, $station, $status);
            
            if ($insertStmt->execute()) {
                echo "<p style='color: green;'>✅ Successfully added Kaplong admin.</p>";
                echo "<p>Login credentials:</p>";
                echo "<ul>";
                echo "<li>Email: adminkaplong@gmail.com</li>";
                echo "<li>Password: password (change after first login)</li>";
                echo "</ul>";
            } else {
                echo "<p style='color: red;'>❌ Error adding Kaplong admin: " . $conn->error . "</p>";
            }
            $insertStmt->close();
        }
        $checkStmt->close();
    }
}

$conn->close();

echo "<h3>Verification</h3>";
echo "<p>After making your choice, run the <a href='diagnose_station_filtering.php'>diagnose_station_filtering.php</a> script again to verify the fix.</p>";
?>