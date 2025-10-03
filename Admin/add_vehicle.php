<?php
$message = '';

require __DIR__ . '/auth.php';
require dirname(__DIR__) . '/db.php';

$success = "";
$error   = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_vehicle'])) {
    $number_plate = strtoupper(trim($_POST['number_plate']));
    $route        = trim($_POST['route']);
    $type         = trim($_POST['type']);
    $color       = trim($_POST['color']);
    $capacity     = intval($_POST['capacity']);
    $driver_name  = trim($_POST['driver_name']);
    $driver_phone = trim($_POST['driver_phone']);
    $owner_name   = trim($_POST['owner_name']);
    $owner_phone  = trim($_POST['owner_phone']);
    $image_path   = null;

    // Handle file upload
    if (!empty($_FILES['vehicle_image']['name']) && $_FILES['vehicle_image']['error'] === UPLOAD_ERR_OK) {
        $dir = __DIR__ . "/uploads/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $filename = time() . "_" . basename($_FILES['vehicle_image']['name']);
        if (move_uploaded_file($_FILES['vehicle_image']['tmp_name'], $dir . $filename)) {
            $image_path = "uploads/" . $filename;
        }
    }

    // Check if vehicle with this number plate already exists
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM vehicles WHERE number_plate = ?");
    $checkStmt->bind_param("s", $number_plate);
    $checkStmt->execute();
    $checkStmt->bind_result($vehicleCount);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($vehicleCount > 0) {
        $message = "<div style='color: red; font-weight: bold; margin-bottom: 10px;'>A vehicle with this number plate already exists.</div>";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Check if user with this phone already exists
            $checkUser = $conn->prepare("SELECT id FROM users WHERE phone = ?");
            $checkUser->bind_param("s", $driver_phone);
            $checkUser->execute();
            $userResult = $checkUser->get_result();
            
            $user_id = null;
            if ($userResult->num_rows === 0) {
                // Create new user
                $driver_email = strtolower(str_replace(' ', '.', $driver_name) . "@southrift.com");
                $temp_password = password_hash($number_plate, PASSWORD_DEFAULT); // Using number plate as initial password
                
                $insertUser = $conn->prepare("
                    INSERT INTO users (name, email, phone, password, role, status) 
                    VALUES (?, ?, ?, ?, 'driver', 'active')
                ") or die($conn->error);
                
                $insertUser->bind_param("ssss", $driver_name, $driver_email, $driver_phone, $temp_password);
                
                if (!$insertUser->execute()) {
                    throw new Exception("Error creating user: " . $insertUser->error);
                }
                
                $user_id = $conn->insert_id;
                $insertUser->close();
            } else {
                $user_data = $userResult->fetch_assoc();
                $user_id = $user_data['id'];
            }
            $checkUser->close();

            // Check if a driver already exists for this phone
            // First, check if drivers table has vehicle_id column and other field variations
            $driversColumns = [];
            $columnsResult = $conn->query("SHOW COLUMNS FROM drivers");
            if ($columnsResult) {
                while ($col = $columnsResult->fetch_assoc()) {
                    $driversColumns[] = $col['Field'];
                }
            }
            $hasVehicleIdColumn = in_array('vehicle_id', $driversColumns);
            
            // Map driver table field names to actual columns
            $driverNameField = in_array('driver_name', $driversColumns) ? 'driver_name' : 
                              (in_array('name', $driversColumns) ? 'name' : 
                              (in_array('full_name', $driversColumns) ? 'full_name' : 'name'));
            
            $driverPhoneField = in_array('driver_phone', $driversColumns) ? 'driver_phone' : 
                               (in_array('phone', $driversColumns) ? 'phone' : 
                               (in_array('phonenumber', $driversColumns) ? 'phonenumber' : 'phone'));
            
            $numberPlateField = in_array('number_plate', $driversColumns) ? 'number_plate' : 
                               (in_array('numberplate', $driversColumns) ? 'numberplate' : 
                               (in_array('car_number', $driversColumns) ? 'car_number' : 'number_plate'));
            
            $userIdField = in_array('user_id', $driversColumns) ? 'user_id' : 
                          (in_array('userid', $driversColumns) ? 'userid' : 
                          (in_array('driver_id', $driversColumns) ? 'driver_id' : null));
            
            $hasUserIdField = ($userIdField !== null);
            
            if ($hasVehicleIdColumn) {
                $checkDriver = $conn->prepare("SELECT id, vehicle_id FROM drivers WHERE $driverPhoneField = ?");
            } else {
                $checkDriver = $conn->prepare("SELECT id FROM drivers WHERE $driverPhoneField = ?");
            }
            $checkDriver->bind_param("s", $driver_phone);
            $checkDriver->execute();
            $driverResult = $checkDriver->get_result();

            $driver_id = null;
            if ($row = $driverResult->fetch_assoc()) {
                // Driver exists
                if ($hasVehicleIdColumn && !empty($row['vehicle_id'])) {
                    throw new Exception("This driver is already assigned to a vehicle.");
                }
                $driver_id = (int)$row['id'];

                // Optionally sync latest details
                $upd = $conn->prepare("
                    UPDATE drivers 
                    SET $driverNameField = ?, $numberPlateField = ?, route = ?
                    WHERE id = ?
                ");
                $upd->bind_param("sssi", $driver_name, $number_plate, $route, $driver_id);
                $upd->execute();
                $upd->close();
            } else {
                // Insert into drivers table with dynamic field mapping
                if ($hasUserIdField) {
                    // Include user_id if the column exists
                    $insertDriver = $conn->prepare("
                        INSERT INTO drivers 
                        ($userIdField, $driverNameField, $driverPhoneField, $numberPlateField, route, status) 
                        VALUES (?, ?, ?, ?, ?, 'available')
                    ") or die($conn->error);
                    
                    $insertDriver->bind_param(
                        "issss",
                        $user_id,
                        $driver_name,
                        $driver_phone,
                        $number_plate,
                        $route
                    );
                } else {
                    // Skip user_id if the column doesn't exist
                    $insertDriver = $conn->prepare("
                        INSERT INTO drivers 
                        ($driverNameField, $driverPhoneField, $numberPlateField, route, status) 
                        VALUES (?, ?, ?, ?, 'available')
                    ") or die($conn->error);
                    
                    $insertDriver->bind_param(
                        "ssss",
                        $driver_name,
                        $driver_phone,
                        $number_plate,
                        $route
                    );
                }
                
                if (!$insertDriver->execute()) {
                    throw new Exception("Error creating driver record: " . $insertDriver->error);
                }
                
                $driver_id = $conn->insert_id;
                $insertDriver->close();
            }
            $checkDriver->close();

            // Now insert the vehicle
            $stmt = $conn->prepare("
                INSERT INTO vehicles 
                (number_plate, type, color, route, capacity, driver_name, driver_phone, owner_name, owner_phone, image_path) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ") or die($conn->error);
            
            $stmt->bind_param(
                "ssssisssss", 
                $number_plate,
                $type,
                $color,
                $route, 
                $capacity,
                $driver_name, 
                $driver_phone,
                $owner_name, 
                $owner_phone,
                $image_path
            );

            if ($stmt->execute()) {
                // Update the driver with the vehicle ID only if the column exists
                $vehicle_id = $conn->insert_id;
                
                if ($hasVehicleIdColumn) {
                    $updateDriver = $conn->prepare("
                        UPDATE drivers 
                        SET vehicle_id = ?
                        WHERE id = ?
                    ") or die($conn->error);
                    
                    $updateDriver->bind_param("ii", $vehicle_id, $driver_id);
                    $updateDriver->execute();
                    $updateDriver->close();
                }
                
                // Commit transaction
                $conn->commit();
                
                $message = "<div style='color: green; font-weight: bold; margin-bottom: 10px;'>Vehicle and driver added successfully. Driver can login with phone: $driver_phone and password: $number_plate</div>";
                // Clear form
                $_POST = array();
            } else {
                throw new Exception("Error adding vehicle: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $message = "<div style='color: red; font-weight: bold; margin-bottom: 10px;'>Error: " . $e->getMessage() . "</div>";
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Vehicle – Southrift Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{--purple:#6A0DAD;--purple-dark:#4e0b8a}

@keyframes fadeInBody{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}

body{
  margin:0;
  font-family:Poppins,sans-serif;
  background:#f4f4f4;
  padding:0;
}

main {
  animation:fadeInBody .7s ease-in-out;
  padding: 20px;
  margin-top: 60px; /* Account for fixed navbar */
}

nav {
  background:var(--purple);
  color:#fff;
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:1rem 2rem;
  flex-wrap:wrap;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
}
.logo {
  font-size:1.5rem;font-weight:700;
  text-transform:uppercase;letter-spacing:1px;
  animation:logoGlow 2s ease-in-out infinite alternate;
}
@keyframes logoGlow {
  0%{text-shadow:0 0 8px #fff,0 0 12px #0ff,0 0 20px #0ff}
  100%{text-shadow:0 0 12px #fff,0 0 20px #f0f,0 0 28px #f0f}
}
.nav-right {
  display:flex;gap:20px;align-items:center;
}
.nav-right a {
  position:relative;
  color:paleturquoise;
  font-weight:600;
  text-decoration:none;
  padding:8px 10px;
  text-transform:uppercase;
  letter-spacing:1px;
  transition:color .3s ease;
}
.nav-right a::after {
  content:"";position:absolute;bottom:0;left:0;width:100%;height:2px;
  background:linear-gradient(to right,#ff6ec4,#7873f5);
  transform:scaleX(0);transform-origin:right;
  transition:transform .4s ease-in-out;
}
.nav-right a:hover {
  color:#00ffff;text-shadow:0 0 8px rgba(0,255,255,.6);
}
.nav-right a:hover::after {
  transform:scaleX(1);transform-origin:left;
}

.container {
  max-width:700px;margin:0 auto;background:rgb(245,237,247);
  padding:30px;border-radius:10px;
  box-shadow:0 4px 10px rgba(0,0,0,.1);
  margin-top: 20px;
}
h2 {
  color:var(--purple);
  text-align:center;
  margin-bottom:10px
}
label {
  display:block;
  margin-top:15px;
  font-weight:600
}
input[type=text],input[type=number],input[type=file] {
  width:100%;
  padding:10px;
  border:1px solid #ccc;
  border-radius:5px;
}
button {
  margin-top:25px;
  background:var(--purple);
  color:#fff;
  border:none;
  padding:12px 22px;
  border-radius:6px;
  cursor:pointer;
  font-weight:700;
}
.success,.error {
  margin-top:15px;
  padding:10px;
  border-radius:6px
}
.success {
  background:#d4edda;
  color:#155724
}
.error {
  background:#f8d7da;
  color:#721c24
}
.buttons {
  margin-top:20px;
  display:flex;
  justify-content:space-between
}
.buttons a {
  background:var(--purple);
  color:#fff;
  padding:10px 20px;
  border-radius:5px;
  text-decoration:none;
  font-weight:600;
}
.buttons a:hover,button:hover {
  background:var(--purple-dark)
}

footer {
  background:var(--purple);
  color:#fff;
  text-align:center;
  padding:1rem;
  position:fixed;
  bottom:0;
  left:0;
  width:100%;
  z-index:100;
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav>
  <div class="logo">SouthRift Services</div>
  <div class="nav-right">
    <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="existing_vehicles.php" class="active"><i class="fas fa-car"></i> Vehicles</a>
    <a href="vehicle_waiting.php"><i class="fas fa-clock"></i> Waiting List</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</nav>

<main>
  <div class="container">
    <?php if (!empty($message)) echo $message; ?>
    <h2>Add New Vehicle</h2>

    <?php if ($success): ?>
      <div class="success"><?= $success ?></div>
      <div class="buttons">
        <a href="index.php">← Dashboard</a>
        <a href="existing_vehicles.php">📋 View Vehicles</a>
      </div>
    <?php else: ?>
      <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
      <form method="POST" enctype="multipart/form-data">
        <label>Number Plate</label>
        <input type="text" name="number_plate" required>

        <label>Route</label>
        <input type="text" name="route" required>

        <label>Vehicle Type</label>
        <input type="text" name="type" placeholder="e.g. saloon, suv, van, bus" required>

        <label>Color</label>
        <input type="text" name="color" placeholder="e.g. white, blue, silver" required>

        <label>Capacity</label>
        <input type="number" name="capacity" min="1" required>

        <label>Driver Name</label>
        <input type="text" name="driver_name" required>

        <label>Driver Phone</label>
        <input type="text" name="driver_phone" required>

        <label>Owner Name</label>
        <input type="text" name="owner_name" required>

        <label>Owner Phone</label>
        <input type="text" name="owner_phone" required>

        <label>Vehicle Image</label>
        <input type="file" name="vehicle_image" required>

        <button type="submit" name="add_vehicle">Save Vehicle</button>
      </form>
    <?php endif; ?>
  </div>
</main>

<footer>&copy; <?=date('Y')?> Southrift Services Limited | All Rights Reserved</footer>

</body>
</html>
