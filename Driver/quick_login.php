<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Login Test - Southrift</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #6A0DAD; color: white; padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #4e0b8a; }
        .drivers-list { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .driver-item { margin: 10px 0; padding: 10px; background: white; border-radius: 3px; }
        .success { color: green; background: #e7f9ef; padding: 10px; border-radius: 5px; }
        .error { color: red; background: #fdecea; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🚗 Driver Login Test</h1>
    
    <?php
    // Set the correct session configuration
    if (session_status() === PHP_SESSION_NONE) {
        session_name('southrift_admin');
        session_start();
    }
    
    require dirname(__DIR__) . '/db.php';
    
    $message = '';
    $phone = '';
    $plate = '';
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_login'])) {
        $phone = trim($_POST['phone']);
        $plate = strtoupper(trim($_POST['plate']));
        
        if ($phone && $plate) {
            // Try to authenticate driver
            $stmt = $conn->prepare("SELECT * FROM drivers WHERE driver_phone = ? AND number_plate = ?");
            $stmt->bind_param("ss", $phone, $plate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($driver = $result->fetch_assoc()) {
                // Clear any existing session data
                $_SESSION = [];
                
                // Set driver session variables (exactly like login.php)
                $_SESSION['user_id'] = $driver['id'];
                $_SESSION['username'] = $driver['name'];
                $_SESSION['name'] = $driver['name'];
                $_SESSION['phone'] = $driver['driver_phone'];
                $_SESSION['role'] = 'driver';
                $_SESSION['email'] = $driver['email'] ?? '';
                $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                $_SESSION['last_activity'] = time();
                
                $message = "<div class='success'>✅ Login successful! Session variables set:<br>";
                $message .= "- user_id: {$driver['id']}<br>";
                $message .= "- name: {$driver['name']}<br>";
                $message .= "- phone: {$driver['driver_phone']}<br>";
                $message .= "- role: driver<br><br>";
                $message .= "<strong>Now try accessing:</strong><br>";
                $message .= "<a href='profile.php' target='_blank'>Driver Profile</a> | ";
                $message .= "<a href='index.php' target='_blank'>Driver Dashboard</a> | ";
                $message .= "<a href='debug_auth.php' target='_blank'>Debug Auth</a>";
                $message .= "</div>";
                
            } else {
                $message = "<div class='error'>❌ Invalid credentials. No driver found with phone: $phone and plate: $plate</div>";
            }
        } else {
            $message = "<div class='error'>❌ Please fill in both phone and number plate</div>";
        }
    }
    
    // Show available drivers
    echo "<div class='drivers-list'>";
    echo "<h3>📋 Available Drivers:</h3>";
    
    $stmt = $conn->prepare("SELECT name, driver_phone, number_plate, route FROM drivers ORDER BY id LIMIT 5");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($driver = $result->fetch_assoc()) {
            echo "<div class='driver-item'>";
            echo "<strong>{$driver['name']}</strong> - {$driver['route']}<br>";
            echo "📞 Phone: <code>{$driver['driver_phone']}</code><br>";
            echo "🚗 Plate: <code>{$driver['number_plate']}</code>";
            echo "</div>";
        }
    } else {
        echo "<p>No drivers found. <a href='../Admin/add_vehicle.php'>Add a vehicle with driver first</a></p>";
    }
    echo "</div>";
    
    if ($message) {
        echo $message;
    }
    ?>
    
    <h3>🔐 Test Driver Login</h3>
    <form method="POST">
        <div class="form-group">
            <label for="phone">Phone Number (Username):</label>
            <input type="text" id="phone" name="phone" placeholder="e.g., 0798365356" value="<?= htmlspecialchars($phone) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="plate">Number Plate (Password):</label>
            <input type="text" id="plate" name="plate" placeholder="e.g., KDK 547 L" value="<?= htmlspecialchars($plate) ?>" required>
        </div>
        
        <button type="submit" name="test_login">🔐 Test Login</button>
    </form>
    
    <hr style="margin: 30px 0;">
    <h3>🔧 Current Session Status</h3>
    <p><strong>Session Name:</strong> <?= session_name() ?></p>
    <p><strong>Session ID:</strong> <?= session_id() ?></p>
    <p><strong>Logged In:</strong> <?= isset($_SESSION['user_id']) && $_SESSION['role'] === 'driver' ? '✅ Yes' : '❌ No' ?></p>
    
    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'driver'): ?>
        <div class="success">
            <strong>Driver Info:</strong><br>
            - ID: <?= $_SESSION['user_id'] ?><br>
            - Name: <?= $_SESSION['name'] ?? 'N/A' ?><br>
            - Phone: <?= $_SESSION['phone'] ?? 'N/A' ?><br>
            - Role: <?= $_SESSION['role'] ?? 'N/A' ?>
        </div>
    <?php endif; ?>
    
    <p style="margin-top: 20px;">
        <a href="../login.html">← Back to Main Login</a> | 
        <a href="debug_auth.php">🔍 Debug Auth</a>
    </p>
</body>
</html>