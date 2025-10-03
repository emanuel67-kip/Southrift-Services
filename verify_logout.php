<?php
// Start session
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Set a test user_id if not set (for testing purposes)
if (!$isLoggedIn) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'Test User';
    $_SESSION['role'] = 'passenger';
    $isLoggedIn = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Logout Button</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/responsive-framework.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
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
        .logout-section {
            margin-top: 40px;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #dee2e6;
        }
        .btn-test {
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
        .btn-test:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #495057);
        }
        .btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268, #343a40);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Verify Logout Button</h1>
        
        <div class="status <?php echo $isLoggedIn ? 'success' : 'error'; ?>">
            <strong>Status:</strong> 
            <?php 
            if ($isLoggedIn) {
                echo "User is logged in as: " . ($_SESSION['username'] ?? 'Unknown') . " (" . ($_SESSION['role'] ?? 'Unknown') . ")";
            } else {
                echo "User is not logged in";
            }
            ?>
        </div>
        
        <p>This page is designed to verify that the logout button is visible and functional.</p>
        
        <div class="logout-section">
            <h2>Logout Button Test</h2>
            <p>Below is the exact same logout button that appears on the passenger profile page:</p>
            
            <a href="passenger_logout.php" class="btn-test btn-secondary">Logout</a>
            
            <p style="margin-top: 20px;">
                <strong>Note:</strong> This button should redirect you to the login page after clicking.
            </p>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #e9ecef; border-radius: 8px;">
            <h3>Debug Information:</h3>
            <p><strong>Current Page:</strong> <?php echo $_SERVER['PHP_SELF']; ?></p>
            <p><strong>Session Status:</strong> <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'; ?></p>
            <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
            
            <h4>Session Data:</h4>
            <pre><?php print_r($_SESSION); ?></pre>
        </div>
    </div>
</body>
</html>