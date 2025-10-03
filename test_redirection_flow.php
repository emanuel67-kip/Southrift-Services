<?php
// Start session
session_start();

// Check if we're testing the redirection
if (isset($_GET['test']) && $_GET['test'] === 'passenger') {
    // Simulate a passenger login
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'Test Passenger';
    $_SESSION['role'] = 'passenger';
    $_SESSION['email'] = 'test@example.com';
    
    // Redirect to homepage as per our new logic
    header('Location: index.php');
    exit;
}

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
    <title>Test Redirection Flow</title>
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
        .btn-success {
            background: linear-gradient(135deg, #28a745, #218838);
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
        }
        .test-section {
            background: #e9ecef;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Redirection Flow</h1>
        
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
        
        <p>This page tests the complete login redirection flow for passengers.</p>
        
        <div class="test-section">
            <h2>Redirection Logic Summary</h2>
            <ul>
                <li><strong>Passengers</strong> are redirected to: <code>index.html</code> (homepage)</li>
                <li><strong>Admins</strong> are redirected to: <code>Admin/index.php</code></li>
                <li><strong>Drivers</strong> are redirected to: <code>Driver/index.php</code></li>
            </ul>
        </div>
        
        <h2>Test Scenarios:</h2>
        
        <?php if (!$loggedIn): ?>
            <div class="test-section">
                <h3>1. Test Passenger Login Redirection</h3>
                <p>Click the button below to simulate a passenger login and verify redirection to homepage:</p>
                <a href="?test=passenger" class="btn btn-success">Simulate Passenger Login</a>
            </div>
            
            <div class="test-section">
                <h3>2. Manual Testing</h3>
                <p>For manual testing:</p>
                <ol>
                    <li>Go to <a href="login.html">Login Page</a> or <a href="test_passenger_login.html">Test Login Form</a></li>
                    <li>Login with valid passenger credentials</li>
                    <li>Verify that you are redirected to the homepage (index.html)</li>
                </ol>
            </div>
        <?php else: ?>
            <div class="test-section">
                <h3>Welcome, <?php echo htmlspecialchars($username); ?>!</h3>
                <p>You have been successfully redirected to the homepage after login.</p>
                <p>As a <?php echo htmlspecialchars($role); ?>, you should see a personalized welcome message on the homepage.</p>
                
                <a href="index.php" class="btn">Go to Homepage</a>
                <a href="passenger_logout.php" class="btn btn-secondary">Logout</a>
            </div>
        <?php endif; ?>
        
        <h2>Debug Information:</h2>
        <pre><?php print_r($_SESSION); ?></pre>
        
        <h2>Files Modified:</h2>
        <ul>
            <li><code>login.php</code> - Updated passenger redirection to index.html</li>
            <li><code>index.php</code> - Created PHP version with personalized welcome for logged-in users</li>
            <li><code>index.html</code> - Updated navigation</li>
        </ul>
    </div>
</body>
</html>