<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$user = 'root';
$password = '';
$db = 'southrift';

// Create connection
$conn = new mysqli($host, $user, $password, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user ID from GET parameter or use 1 as default
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1;

// Check if users table exists
$tables = $conn->query("SHOW TABLES LIKE 'bookings'");
if ($tables->num_rows === 0) {
    die("Error: 'bookings' table does not exist in the database.");
}

// Get user's bookings
$query = "
    SELECT 
        b.id,
        b.route,
        b.boarding_point,
        b.travel_date,
        b.departure_time,
        b.seats,
        b.payment_method,
        b.status,
        b.created_at
    FROM bookings b
    WHERE b.user_id = ? 
    ORDER BY b.created_at DESC";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing query: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    die("Error executing query: " . $stmt->error);
}

$result = $stmt->get_result();
$bookings = [];

while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

// Output results
echo "<h2>Bookings for User ID: $user_id</h2>";
if (count($bookings) > 0) {
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Route</th><th>Travel Date</th><th>Departure Time</th><th>Status</th></tr>";
    foreach ($bookings as $booking) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($booking['id']) . "</td>";
        echo "<td>" . htmlspecialchars($booking['route']) . "</td>";
        echo "<td>" . htmlspecialchars($booking['travel_date']) . "</td>";
        echo "<td>" . htmlspecialchars($booking['departure_time']) . "</td>";
        echo "<td>" . htmlspecialchars($booking['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No bookings found for this user.</p>";
    
    // Show sample data that would be expected
    echo "<h3>Expected Data Structure:</h3>";
    echo "<pre>" . htmlspecialchars('{
    "success": true,
    "user": {
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "1234567890"
    },
    "bookings": [
        {
            "id": 1,
            "route": "Nairobi to Mombasa",
            "travel_date": "2025-08-21",
            "departure_time": "08:00:00",
            "boarding_point": "Nairobi CBD",
            "seats": 2,
            "payment_method": "mpesa",
            "status": "confirmed"
        }
    ]
}') . "</pre>";
    
    // Show tables in the database
    echo "<h3>Tables in database:</h3>";
    $tables = $conn->query("SHOW TABLES");
    echo "<ul>";
    while ($table = $tables->fetch_array()) {
        echo "<li>" . $table[0] . "</li>";
    }
    echo "</ul>";
}

// Check for bookings in the database
echo "<h2>Checking for bookings in the database...</h2>";
$query = "SELECT COUNT(*) as count FROM bookings WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo "Number of bookings found for user ID $user_id: " . $row['count'] . "<br><br>";

// If there are bookings, show them
if ($row['count'] > 0) {
    $query = "SELECT * FROM bookings WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<h3>Bookings for user ID: $user_id</h3>";
    echo "<pre>";
    while ($booking = $result->fetch_assoc()) {
        print_r($booking);
        echo "\n\n";
    }
    echo "</pre>";
} else {
    echo "No bookings found for this user. Here's a sample of recent bookings in the system:<br>";
    
    $query = "SELECT * FROM bookings ORDER BY id DESC LIMIT 5";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        echo "<h4>Sample of recent bookings (any user):</h4>";
        echo "<pre>";
        while ($booking = $result->fetch_assoc()) {
            print_r($booking);
            echo "\n\n";
        }
        echo "</pre>";
    } else {
        echo "No bookings found in the system at all.";
    }
}

$conn->close();
?>
