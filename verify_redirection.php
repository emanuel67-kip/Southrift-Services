<?php
// Start session
session_start();

// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']);
$username = $loggedIn ? ($_SESSION['username'] ?? 'Passenger') : null;
$role = $loggedIn ? ($_SESSION['role'] ?? 'passenger') : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Redirection</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/responsive-framework.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
            color: #333;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .status {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #6A0DAD, #8A2BE2);
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            margin: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #495057);
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Verify Login Redirection</h1>
        
        <div class="status <?php echo $loggedIn ? 'success' : 'error'; ?>">
            <strong>Login Status:</strong> 
            <?php 
            if ($loggedIn) {
                echo "User is logged in as: " . htmlspecialchars($username) . " (" . htmlspecialchars($role) . ")";
            } else {
                echo "User is not logged in";
            }
            ?>
        </div>
        
        <p>This page verifies that the login redirection logic is working correctly.</p>
        
        <h2>Current Configuration:</h2>
        <ul>
            <li>Passengers are redirected to: <strong>index.html</strong> (homepage)</li>
            <li>Admins are redirected to: <strong>Admin/index.php</strong></li>
            <li>Drivers are redirected to: <strong>Driver/index.php</strong></li>
        </ul>
        
        <h2>Test Actions:</h2>
        <div style="margin: 20px 0;">
            <?php if ($loggedIn): ?>
                <a href="passenger_logout.php" class="btn btn-secondary">Logout</a>
                <a href="index.php" class="btn">Go to Homepage</a>
                <a href="passenger_profile.php" class="btn">Go to Profile</a>
            <?php else: ?>
                <a href="login.html" class="btn">Go to Login Page</a>
                <a href="test_passenger_login.html" class="btn">Test Passenger Login</a>
            <?php endif; ?>
        </div>
        
        <h2>Session Debug Information:</h2>
        <pre><?php print_r($_SESSION); ?></pre>
        
        <h2>How to Test:</h2>
        <ol>
            <li>Click on "Go to Login Page" or "Test Passenger Login"</li>
            <li>Login with valid passenger credentials</li>
            <li>Verify that you are redirected to the homepage (index.html or index.php)</li>
            <li>Check that the welcome message appears on the homepage for logged-in users</li>
        </ol>
    </div>
</body>
</html>