<?php
require __DIR__ . '/auth.php';
require dirname(__DIR__) . '/db.php';

// Only super admins can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_station'])) {
    $admin_id = (int)$_POST['admin_id'];
    $station = trim($_POST['station']);
    
    if ($admin_id > 0 && !empty($station)) {
        $stmt = $conn->prepare("UPDATE users SET station = ? WHERE id = ? AND role = 'admin'");
        $stmt->bind_param("si", $station, $admin_id);
        
        if ($stmt->execute()) {
            $message = "<div class='alert success'>✅ Station updated successfully!</div>";
        } else {
            $message = "<div class='alert error'>❌ Failed to update station: " . $stmt->error . "</div>";
        }
        
        $stmt->close();
    } else {
        $message = "<div class='alert error'>❌ Please provide both admin and station.</div>";
    }
}

// Get all admins
$admins = [];
$result = $conn->query("SELECT id, name, email, station FROM users WHERE role = 'admin' ORDER BY name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admin Stations - Southrift Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --purple: #6A0DAD;
            --purple-dark: #58009c;
            --bg: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        nav {
            background: var(--purple);
            padding: 1rem 2rem;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .nav-right {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .nav-right a {
            color: paleturquoise;
            font-weight: 600;
            text-decoration: none;
            padding: 8px 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: color 0.3s ease;
        }
        
        .nav-right a:hover {
            color: #00ffff;
        }
        
        main {
            max-width: 1200px;
            margin: 40px auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            flex: 1;
        }
        
        h2 {
            color: var(--purple);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert.success {
            background: #e7f9ef;
            color: #0f7b3f;
            border: 1px solid #bcebd2;
        }
        
        .alert.error {
            background: #fdecea;
            color: #b00020;
            border: 1px solid #f5c2c7;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        select, input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
        }
        
        button {
            background: var(--purple);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        button:hover {
            background: var(--purple-dark);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: var(--purple);
            color: white;
        }
        
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        tr:hover {
            background: #f0f0f0;
        }
        
        .station-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        footer {
            background: var(--purple);
            color: #fff;
            text-align: center;
            padding: 1rem;
            margin-top: auto;
        }
        
        @media (max-width: 768px) {
            main {
                margin: 20px;
                padding: 20px;
            }
            
            .nav-right {
                flex-direction: column;
                align-items: flex-end;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo">Southrift Services Limited</div>
        <div class="nav-right">
            <a href="index.php"><i class="fa fa-home"></i> Dashboard</a>
            <a href="today_bookings.php"><i class="fa fa-calendar-day"></i> Today's Bookings</a>
            <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
    
    <main>
        <h2><i class="fas fa-map-marker-alt"></i> Manage Admin Stations</h2>
        
        <?= $message ?>
        
        <div class="station-form">
            <h3>Update Admin Station</h3>
            <form method="post">
                <div class="form-group">
                    <label for="admin_id">Select Admin:</label>
                    <select name="admin_id" id="admin_id" required>
                        <option value="">-- Select Admin --</option>
                        <?php foreach ($admins as $admin): ?>
                            <option value="<?= htmlspecialchars($admin['id']) ?>">
                                <?= htmlspecialchars($admin['name']) ?> (<?= htmlspecialchars($admin['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="station">Station:</label>
                    <input type="text" name="station" id="station" placeholder="e.g., Nairobi, Litein, Kisumu" required>
                </div>
                
                <button type="submit" name="update_station">
                    <i class="fas fa-save"></i> Update Station
                </button>
            </form>
        </div>
        
        <h3>Current Admin Stations</h3>
        <?php if (count($admins) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Station</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?= htmlspecialchars($admin['name']) ?></td>
                            <td><?= htmlspecialchars($admin['email']) ?></td>
                            <td>
                                <?php if (!empty($admin['station'])): ?>
                                    <span style="background: #e7f9ef; color: #0f7b3f; padding: 4px 8px; border-radius: 4px;">
                                        <?= htmlspecialchars($admin['station']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="background: #fdecea; color: #b00020; padding: 4px 8px; border-radius: 4px;">
                                        Not assigned
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No admins found.</p>
        <?php endif; ?>
    </main>
    
    <footer>&copy; <?=date('Y')?> Southrift Services Limited | All Rights Reserved</footer>
</body>
</html>