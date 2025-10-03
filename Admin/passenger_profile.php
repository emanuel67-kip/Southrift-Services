<?php
require __DIR__ . '/auth.php';
require dirname(__DIR__) . '/db.php';

date_default_timezone_set('Africa/Nairobi');

// Get passenger phone from query parameter
$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';

if (empty($phone)) {
    header('Location: today_bookings.php');
    exit;
}

// Get passenger details
$passenger = null;
$bookings = [];

try {
    // Try to get user account if exists
    $stmt = $conn->prepare("SELECT id, name, email, phone FROM users WHERE phone = ?");
    $stmt->bind_param('s', $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    $passenger = $result->fetch_assoc();
    $stmt->close();

    // Get all bookings for this phone number
    $stmt = $conn->prepare("
        SELECT b.id, 
               CONCAT(b.pickup_location, ' to ', b.dropoff_location) as route,
               b.pickup_location as boarding_point,
               DATE(b.booking_time) as travel_date,
               DATE_FORMAT(b.booking_time, '%h:%i %p') as departure_time,
               b.estimated_fare as fare,
               b.payment_method,
               b.booking_time as created_at,
               b.vehicle_id as assigned_vehicle
        FROM bookings b
        JOIN users u ON b.user_id = u.id 
        WHERE u.phone = ? 
        ORDER BY b.booking_time DESC
    ");
    $stmt->bind_param('s', $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    $error = "Error loading passenger data: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passenger Profile - Admin | Southrift Services</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6A0DAD;
            --primary-dark: #58009c;
            --light-gray: #f5f5f5;
            --dark-gray: #333;
            --border: #ddd;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--light-gray);
            color: var(--dark-gray);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .back-btn:hover {
            background: var(--primary-dark);
        }
        
        .back-btn i {
            margin-right: 8px;
        }
        
        .profile-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin-right: 20px;
        }
        
        .profile-info h2 {
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .profile-meta {
            color: #666;
            font-size: 14px;
        }
        
        .profile-meta span {
            display: block;
            margin-bottom: 3px;
        }
        
        .section-title {
            color: var(--primary);
            margin: 25px 0 15px;
            font-size: 20px;
            font-weight: 600;
        }
        
        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .bookings-table th,
        .bookings-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        .bookings-table th {
            background: var(--primary);
            color: white;
            font-weight: 500;
        }
        
        .bookings-table tr:last-child td {
            border-bottom: none;
        }
        
        .bookings-table tr:hover {
            background: #f9f9f9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .no-bookings {
            text-align: center;
            padding: 30px;
            color: #666;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Passenger Profile</h1>
            <a href="today_bookings.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Bookings
            </a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php else: ?>
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($passenger['name'] ?? 'Guest User'); ?></h2>
                        <div class="profile-meta">
                            <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($phone); ?></span>
                            <?php if (isset($passenger['email'])): ?>
                                <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($passenger['email']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <h3 class="section-title">Booking History</h3>
                
                <?php if (!empty($bookings)): ?>
                    <div style="overflow-x: auto;">
                        <table class="bookings-table">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Route</th>
                                    <th>Boarding Point</th>
                                    <th>Travel Date</th>
                                    <th>Departure</th>
                                    <th>Seats</th>
                                    <th>Payment</th>
                                    <th>Booked On</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($booking['id']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['route']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['boarding_point'] ?? $booking['pickup_location'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($booking['travel_date']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['departure_time']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['seats']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['payment_method']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></td>
                                        <td>
                                            <?php 
                                            // Default status since table doesn't have status column
                                            $status = 'active';
                                            $statusClass = 'status-confirmed';
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-bookings">
                        <i class="fas fa-calendar-times" style="font-size: 40px; margin-bottom: 15px; color: #aaa;"></i>
                        <p>No bookings found for this passenger.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
