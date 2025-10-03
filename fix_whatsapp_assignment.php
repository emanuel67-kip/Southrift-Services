<?php
require_once 'db.php';

echo "<h2>🔧 Fix WhatsApp Assignment Issue</h2>";

try {
    // Get the current booking for today
    $booking_query = "SELECT booking_id, user_id, fullname, assigned_vehicle FROM bookings WHERE DATE(travel_date) = CURDATE()";
    $booking_result = $conn->query($booking_query);
    
    if ($booking_result && $booking = $booking_result->fetch_assoc()) {
        echo "<h3>📋 Current Booking Details</h3>";
        echo "<p><strong>Booking ID:</strong> " . $booking['booking_id'] . "</p>";
        echo "<p><strong>Passenger:</strong> " . $booking['fullname'] . "</p>";
        echo "<p><strong>Assigned Vehicle:</strong> " . $booking['assigned_vehicle'] . "</p>";
        
        // Check if this vehicle exists
        $vehicle_check = $conn->prepare("SELECT number_plate, driver_name, driver_phone FROM vehicles WHERE number_plate = ?");
        $vehicle_check->bind_param('s', $booking['assigned_vehicle']);
        $vehicle_check->execute();
        $vehicle_result = $vehicle_check->get_result();
        
        if ($vehicle_result->num_rows == 0) {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
            echo "<h4>❌ Problem Found!</h4>";
            echo "<p>The booking is assigned to vehicle '<strong>" . $booking['assigned_vehicle'] . "</strong>' but this vehicle doesn't exist in the vehicles table.</p>";
            echo "</div>";
            
            // Show available vehicles
            echo "<h4>🚗 Available Vehicles:</h4>";
            $all_vehicles = $conn->query("SELECT number_plate, driver_name, driver_phone FROM vehicles");
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
            echo "<tr style='background: #6A0DAD; color: white;'><th>Number Plate</th><th>Driver Name</th><th>Driver Phone</th><th>Action</th></tr>";
            
            while ($vehicle = $all_vehicles->fetch_assoc()) {
                $highlight = ($vehicle['driver_phone'] == '0736225373') ? "style='background: #fff3cd;'" : "";
                echo "<tr $highlight>";
                echo "<td>" . $vehicle['number_plate'] . "</td>";
                echo "<td>" . $vehicle['driver_name'] . "</td>";
                echo "<td>" . $vehicle['driver_phone'] . "</td>";
                
                if ($vehicle['driver_phone'] == '0736225373') {
                    echo "<td>";
                    echo "<form method='post' style='display: inline;'>";
                    echo "<input type='hidden' name='booking_id' value='" . $booking['booking_id'] . "'>";
                    echo "<input type='hidden' name='new_vehicle' value='" . $vehicle['number_plate'] . "'>";
                    echo "<button type='submit' name='fix_assignment' style='background: #28a745; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer;'>";
                    echo "✅ Assign This Vehicle";
                    echo "</button>";
                    echo "</form>";
                    echo "</td>";
                } else {
                    echo "<td>-</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<p><strong>💡 Solution:</strong> Click '✅ Assign This Vehicle' next to the vehicle with your phone number (highlighted in yellow).</p>";
            
        } else {
            $vehicle = $vehicle_result->fetch_assoc();
            echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>🚗 Vehicle Details</h4>";
            echo "<p><strong>Number Plate:</strong> " . $vehicle['number_plate'] . "</p>";
            echo "<p><strong>Driver Name:</strong> " . $vehicle['driver_name'] . "</p>";
            echo "<p><strong>Driver Phone:</strong> " . $vehicle['driver_phone'] . "</p>";
            echo "</div>";
            
            if ($vehicle['driver_phone'] != '0736225373') {
                echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<h4>⚠️ Phone Number Mismatch</h4>";
                echo "<p>The vehicle is assigned to driver phone: <strong>" . $vehicle['driver_phone'] . "</strong></p>";
                echo "<p>But you're logged in with phone: <strong>0736225373</strong></p>";
                echo "<p><strong>Solutions:</strong></p>";
                echo "<ol>";
                echo "<li>Login with phone number: <strong>" . $vehicle['driver_phone'] . "</strong>, or</li>";
                echo "<li>Update the vehicle's driver phone to: <strong>0736225373</strong></li>";
                echo "</ol>";
                
                echo "<form method='post' style='margin: 10px 0;'>";
                echo "<input type='hidden' name='vehicle_plate' value='" . $vehicle['number_plate'] . "'>";
                echo "<input type='hidden' name='new_phone' value='0736225373'>";
                echo "<button type='submit' name='update_vehicle_phone' style='background: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer;'>";
                echo "🔧 Update Vehicle Phone to 0736225373";
                echo "</button>";
                echo "</form>";
                echo "</div>";
            } else {
                echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
                echo "<h4>✅ Everything looks correct!</h4>";
                echo "<p>The vehicle phone matches your session. There might be another issue.</p>";
                echo "</div>";
            }
        }
        
    } else {
        echo "<p style='color: red;'>❌ No bookings found for today!</p>";
        
        // Create a test booking
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>🛠️ Creating Test Booking</h4>";
        
        // First, get a vehicle with the current driver phone
        $driver_vehicle = $conn->prepare("SELECT number_plate FROM vehicles WHERE driver_phone = ? LIMIT 1");
        $driver_vehicle->bind_param('s', '0736225373');
        $driver_vehicle->execute();
        $vehicle_result = $driver_vehicle->get_result();
        
        if ($vehicle_result->num_rows > 0) {
            $vehicle = $vehicle_result->fetch_assoc();
            
            $today = date('Y-m-d');
            $update_booking = "UPDATE bookings SET travel_date = ?, assigned_vehicle = ? WHERE booking_id = 1";
            $stmt = $conn->prepare($update_booking);
            $stmt->bind_param('ss', $today, $vehicle['number_plate']);
            
            if ($stmt->execute()) {
                echo "<p>✅ Updated booking 1 to today with vehicle: " . $vehicle['number_plate'] . "</p>";
                
                // Also ensure user has phone
                $conn->query("UPDATE users SET phone = '0712345678' WHERE id = (SELECT user_id FROM bookings WHERE booking_id = 1)");
                echo "<p>✅ Updated user phone number</p>";
                
                echo "<p><strong>🎉 Test booking created! Try the WhatsApp button again.</strong></p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to create test booking</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ No vehicle found with your phone number</p>";
        }
        echo "</div>";
    }
    
    // Handle form submissions
    if (isset($_POST['fix_assignment'])) {
        $booking_id = $_POST['booking_id'];
        $new_vehicle = $_POST['new_vehicle'];
        
        $update = $conn->prepare("UPDATE bookings SET assigned_vehicle = ? WHERE booking_id = ?");
        $update->bind_param('si', $new_vehicle, $booking_id);
        
        if ($update->execute()) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
            echo "<h4>✅ Assignment Fixed!</h4>";
            echo "<p>Booking " . $booking_id . " is now assigned to vehicle: " . $new_vehicle . "</p>";
            echo "<p><strong>🎉 WhatsApp location sharing should work now!</strong></p>";
            echo "<p><a href='Driver/index.php' style='background: #25D366; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Test WhatsApp Button</a></p>";
            echo "</div>";
        } else {
            echo "<p style='color: red;'>❌ Failed to update assignment</p>";
        }
    }
    
    if (isset($_POST['update_vehicle_phone'])) {
        $vehicle_plate = $_POST['vehicle_plate'];
        $new_phone = $_POST['new_phone'];
        
        $update = $conn->prepare("UPDATE vehicles SET driver_phone = ? WHERE number_plate = ?");
        $update->bind_param('ss', $new_phone, $vehicle_plate);
        
        if ($update->execute()) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
            echo "<h4>✅ Vehicle Phone Updated!</h4>";
            echo "<p>Vehicle " . $vehicle_plate . " now has driver phone: " . $new_phone . "</p>";
            echo "<p><strong>🎉 WhatsApp location sharing should work now!</strong></p>";
            echo "<p><a href='Driver/index.php' style='background: #25D366; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Test WhatsApp Button</a></p>";
            echo "</div>";
        } else {
            echo "<p style='color: red;'>❌ Failed to update vehicle phone</p>";
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