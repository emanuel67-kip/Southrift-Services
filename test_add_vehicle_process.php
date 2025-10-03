<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db.php';

echo "<h2>Test Add Vehicle Process</h2>";

// Show current state
echo "<h3>Current Database State</h3>";

echo "<h4>Users Table (showing drivers):</h4>";
$result = $conn->query("SELECT id, name, email, phone, role, status FROM users WHERE role = 'driver' ORDER BY id DESC LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['phone']}</td>";
        echo "<td><strong>{$row['role']}</strong></td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No driver users found</p>";
}

echo "<h4>Drivers Table:</h4>";
$result = $conn->query("SELECT * FROM drivers ORDER BY id DESC LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>User ID</th><th>Driver Name</th><th>Phone</th><th>Number Plate</th><th>Route</th><th>Vehicle ID</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['driver_name']}</td>";
        echo "<td>{$row['driver_phone']}</td>";
        echo "<td>{$row['number_plate']}</td>";
        echo "<td>{$row['route']}</td>";
        echo "<td>{$row['vehicle_id']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No drivers found</p>";
}

echo "<h4>Vehicles Table:</h4>";
$result = $conn->query("SELECT * FROM vehicles ORDER BY id DESC LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Number Plate</th><th>Type</th><th>Color</th><th>Route</th><th>Capacity</th><th>Driver Name</th><th>Driver Phone</th><th>Owner Name</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td><strong>{$row['number_plate']}</strong></td>";
        echo "<td>{$row['type']}</td>";
        echo "<td>{$row['color']}</td>";
        echo "<td>{$row['route']}</td>";
        echo "<td>{$row['capacity']}</td>";
        echo "<td>{$row['driver_name']}</td>";
        echo "<td>{$row['driver_phone']}</td>";
        echo "<td>{$row['owner_name']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No vehicles found</p>";
}

echo "<h3>How the Add Vehicle Process Works</h3>";
echo "<div style='background: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin: 10px 0;'>";
echo "<h4>📝 Process Flow:</h4>";
echo "<ol>";
echo "<li><strong>Admin fills the form</strong> in Admin/add_vehicle.php with:";
echo "<ul><li>Vehicle details (number plate, type, color, route, capacity)</li>";
echo "<li>Driver details (name, phone)</li>";
echo "<li>Owner details (name, phone)</li>";
echo "<li>Vehicle image</li></ul></li>";

echo "<li><strong>System creates/finds user account:</strong>";
echo "<ul><li>Checks if user with driver phone exists</li>";
echo "<li>If not, creates new user with role 'driver'</li>";
echo "<li>Auto-generates email: firstname.lastname@southrift.com</li>";
echo "<li>Sets password as the vehicle's number plate</li></ul></li>";

echo "<li><strong>System creates/updates driver record:</strong>";
echo "<ul><li>Checks if driver with phone exists</li>";
echo "<li>If not, creates new driver record</li>";
echo "<li>Links driver to user account</li></ul></li>";

echo "<li><strong>System creates vehicle record:</strong>";
echo "<ul><li>Stores all vehicle details</li>";
echo "<li>Links vehicle to driver</li></ul></li>";

echo "<li><strong>Driver can now login:</strong>";
echo "<ul><li>Username: Driver phone number</li>";
echo "<li>Password: Vehicle number plate</li></ul></li>";
echo "</ol>";
echo "</div>";

echo "<h3>🎯 Ready to Test</h3>";
echo "<p><a href='Admin/add_vehicle.php' target='_blank' style='background: #6A0DAD; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Add Vehicle Form</a></p>";

echo "<h3>💡 Test Scenario</h3>";
echo "<div style='background: #e8f4f8; padding: 15px; border: 1px solid #bee5eb; margin: 10px 0;'>";
echo "<p><strong>Try adding a vehicle with these sample details:</strong></p>";
echo "<ul>";
echo "<li>Number Plate: <strong>KCA123B</strong></li>";
echo "<li>Route: <strong>Nairobi - Nakuru</strong></li>";
echo "<li>Type: <strong>Saloon</strong></li>";
echo "<li>Color: <strong>White</strong></li>";
echo "<li>Capacity: <strong>4</strong></li>";
echo "<li>Driver Name: <strong>John Kamau</strong></li>";
echo "<li>Driver Phone: <strong>254712345678</strong></li>";
echo "<li>Owner Name: <strong>Mary Wanjiku</strong></li>";
echo "<li>Owner Phone: <strong>254700123456</strong></li>";
echo "</ul>";
echo "<p><strong>After adding, the driver can login with:</strong></p>";
echo "<ul>";
echo "<li>Username: <strong>254712345678</strong></li>";
echo "<li>Password: <strong>KCA123B</strong></li>";
echo "</ul>";
echo "</div>";

$conn->close();
?>