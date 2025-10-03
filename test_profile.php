<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Set a test user_id if not set
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'Test User';
    $_SESSION['role'] = 'passenger';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Profile Page</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .logout-section { 
            margin-top: 30px; 
            padding: 20px; 
            border: 1px solid #ccc; 
            background-color: #f9f9f9;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Test Profile Page</h1>
    <p>This is a test to verify the logout button appears correctly.</p>
    
    <div class="logout-section">
        <p>Scroll down to see the logout button:</p>
        <div style="height: 500px; background: linear-gradient(to bottom, #f0f0f0, #e0e0e0); 
             display: flex; align-items: center; justify-content: center;">
            <p>Space to scroll...</p>
        </div>
        
        <!-- This is the exact same logout button code from passenger_profile.php -->
        <div class="text-center mt-2">
            <a href="passenger_logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </div>
</body>
</html>