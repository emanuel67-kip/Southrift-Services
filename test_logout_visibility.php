<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Logout Button Visibility</title>
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
        .test-content {
            height: 1000px;
            background: linear-gradient(to bottom, #e9ecef, #dee2e6);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .logout-section {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            text-align: center;
            margin: 20px 0;
            border: 1px solid #dee2e6;
        }
        .btn-test {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .btn-test:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Logout Button Visibility</h1>
        <p>This page demonstrates the new logout button placement that should be visible when scrolling.</p>
        
        <div class="test-content">
            <h2>Scrollable Content Area</h2>
            <p>This is a tall content area to simulate scrolling on the profile page.</p>
            <p>When you scroll down, you should be able to see the logout button at the end of the content, not fixed at the bottom of the viewport.</p>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
            <p>Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
            <p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</p>
            <p>Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
            <p>Scroll down to see the logout button...</p>
        </div>
        
        <!-- This simulates the new logout button placement -->
        <div class="logout-section">
            <h2>Logout Button Section</h2>
            <p>This is where the logout button will appear on the profile page:</p>
            <a href="#" class="btn-test">Logout</a>
            <p style="margin-top: 15px;">This button is part of the page content and scrolls with the page, rather than being fixed at the bottom of the viewport.</p>
        </div>
        
        <div class="test-content">
            <h2>More Content Below Logout Button</h2>
            <p>This shows that the logout button is now part of the normal page flow and not fixed.</p>
            <p>You can scroll past it and still see it in the page content.</p>
        </div>
    </div>
    
    <footer style="background: #6A0DAD; color: white; text-align: center; padding: 20px; margin-top: 20px;">
        <p>This is a static footer (not fixed) that appears at the end of the page content.</p>
    </footer>
</body>
</html>