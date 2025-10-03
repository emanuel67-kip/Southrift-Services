<?php
require_once 'db.php';

echo "<h2>🔍 Vehicle-Passenger Assignment Check</h2>";

try {
    // 1. Find YOUR vehicle (driver with phone 0736225373)
    echo "<h3>🚗 Your Vehicle Details</h3>";
    $driver_phone = '0736225373';
    $your_vehicle = $conn->prepare("SELECT number_plate, driver_name, driver_phone FROM vehicles WHERE driver_phone = ?");
    $your_vehicle->bind_param('s', $driver_phone);
    $your_vehicle->execute();
    $vehicle_result = $your_vehicle->get_result();
    
    if ($vehicle = $vehicle_result->fetch_assoc()) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p><strong>Your Vehicle:</strong> " . $vehicle['number_plate'] . "</p>";
        echo "<p><strong>Driver Name:</strong> " . $vehicle['driver_name'] . "</p>";
        echo "<p><strong>Driver Phone:</strong> " . $vehicle['driver_phone'] . "</p>";
        echo "</div>";
        
        $your_vehicle_plate = $vehicle['number_plate'];
        
        // 2. Check what bookings are assigned to YOUR vehicle
        echo "<h3>📋 Passengers Assigned to YOUR Vehicle ($your_vehicle_plate)</h3>";
        $your_passengers = $conn->prepare("
            SELECT b.booking_id, b.user_id, b.fullname, b.assigned_vehicle, u.phone as user_phone
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.id
            WHERE b.assigned_vehicle = ?
            AND DATE(b.travel_date) = CURDATE()
        ");
        $your_passengers->bind_param('s', $your_vehicle_plate);
        $your_passengers->execute();
        $passengers_result = $your_passengers->get_result();
        
        if ($passengers_result->num_rows > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
            echo "<tr style='background: #25D366; color: white;'>";
            echo "<th>Booking ID</th><th>Passenger</th><th>User Phone</th><th>Assigned Vehicle</th></tr>";
            
            while ($passenger = $passengers_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $passenger['booking_id'] . "</td>";
                echo "<td>" . $passenger['fullname'] . "</td>";
                echo "<td>" . ($passenger['user_phone'] ?? 'NO_PHONE') . "</td>";
                echo "<td>" . $passenger['assigned_vehicle'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
            echo "<h4>✅ Perfect! You have passengers assigned to your vehicle!</h4>";
            echo "<p>WhatsApp should work now. If it doesn't, there might be a phone number issue.</p>";
            echo "</div>";
            
        } else {
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<p>❌ No passengers assigned to your vehicle today.</p>";
            echo "</div>";
            
            // 3. Show what bookings exist and what vehicles they're assigned to
            echo "<h3>🔍 All Today's Bookings</h3>";
            $all_bookings = $conn->query("
                SELECT b.booking_id, b.fullname, b.assigned_vehicle, u.phone as user_phone
                FROM bookings b
                LEFT JOIN users u ON b.user_id = u.id
                WHERE DATE(b.travel_date) = CURDATE()
            ");
            
            if ($all_bookings->num_rows > 0) {
                echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
                echo "<tr style='background: #6A0DAD; color: white;'>";
                echo "<th>Booking ID</th><th>Passenger</th><th>Assigned Vehicle</th><th>User Phone</th><th>Action</th></tr>";
                
                while ($booking = $all_bookings->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $booking['booking_id'] . "</td>";
                    echo "<td>" . $booking['fullname'] . "</td>";
                    echo "<td>" . $booking['assigned_vehicle'] . "</td>";
                    echo "<td>" . ($booking['user_phone'] ?? 'NO_PHONE') . "</td>";
                    echo "<td>";
                    
                    // Add button to reassign to your vehicle
                    echo "<form method='post' style='display: inline;'>";
                    echo "<input type='hidden' name='booking_id' value='" . $booking['booking_id'] . "'>";
                    echo "<input type='hidden' name='new_vehicle' value='$your_vehicle_plate'>";
                    echo "<button type='submit' name='reassign' style='background: #007bff; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer;'>";
                    echo "🔄 Assign to My Vehicle";
                    echo "</button>";
                    echo "</form>";
                    
                    echo "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<h4>💡 Solution</h4>";
                echo "<p>Click '<strong>🔄 Assign to My Vehicle</strong>' to assign a passenger to your vehicle (<strong>$your_vehicle_plate</strong>).</p>";
                echo "<p>This will make you the driver responsible for sending location updates to that passenger.</p>";
                echo "</div>";
            } else {
                echo "<p>❌ No bookings found for today at all!</p>";
            }
        }
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
        echo "<h4>❌ No vehicle found for your phone number!</h4>";
        echo "<p>You're logged in as driver with phone: <strong>0736225373</strong></p>";
        echo "<p>But no vehicle in the database has this driver phone.</p>";
        echo "</div>";
        
        // Show all vehicles
        echo "<h3>🚗 All Vehicles in System</h3>";
        $all_vehicles = $conn->query("SELECT number_plate, driver_name, driver_phone FROM vehicles");
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #6A0DAD; color: white;'><th>Number Plate</th><th>Driver Name</th><th>Driver Phone</th></tr>";
        
        while ($v = $all_vehicles->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $v['number_plate'] . "</td>";
            echo "<td>" . $v['driver_name'] . "</td>";
            echo "<td>" . $v['driver_phone'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Handle reassignment
    if (isset($_POST['reassign'])) {
        $booking_id = $_POST['booking_id'];
        $new_vehicle = $_POST['new_vehicle'];
        
        $update = $conn->prepare("UPDATE bookings SET assigned_vehicle = ? WHERE booking_id = ?");
        $update->bind_param('ss', $new_vehicle, $booking_id);
        
        if ($update->execute()) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
            echo "<h4>✅ Reassignment Successful!</h4>";
            echo "<p>Booking $booking_id is now assigned to your vehicle: $new_vehicle</p>";
            echo "<p><strong>🎉 Now try the WhatsApp location button!</strong></p>";
            echo "<p><a href='Driver/index.php' style='background: #25D366; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Go to Driver Dashboard</a></p>";
            echo "</div>";
        } else {
            echo "<p style='color: red;'>❌ Failed to reassign booking</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; max-width: 1000px; }
h2, h3, h4 { color: #6A0DAD; }
table { width: 100%; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background: #f8f9fa; }
tr:nth-child(even) { background: #f9f9f9; }
</style>