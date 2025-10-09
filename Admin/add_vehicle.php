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
        $message = "<div class='alert alert-error'><i class='fas fa-exclamation-circle'></i> A vehicle with this number plate already exists.</div>";
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
                
                $message = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Vehicle and driver added successfully. Driver can login with phone: $driver_phone and password: $number_plate</div>";
                // Clear form
                $_POST = array();
            } else {
                throw new Exception("Error adding vehicle: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $message = "<div class='alert alert-error'><i class='fas fa-exclamation-circle'></i> Error: " . $e->getMessage() . "</div>";
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Vehicle – Southrift Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root {
  --primary: #6A0DAD;
  --primary-dark: #4e0b8a;
  --primary-light: #8a2be2;
  --secondary: #FF6B6B;
  --success: #4CAF50;
  --warning: #FFC107;
  --danger: #F44336;
  --info: #2196F3;
  --light: #f8f9fa;
  --dark: #343a40;
  --gray: #6c757d;
  --light-gray: #e9ecef;
  --border: #dee2e6;
  --card-bg: #ffffff;
  --sidebar-bg: #f8f9fa;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Poppins', sans-serif;
  background-color: #f5f7fb;
  color: #333;
  line-height: 1.6;
  display: flex;
  min-height: 100vh;
  flex-direction: column;
  padding-top: 80px;
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes logoGlow {
  0% { text-shadow: 0 0 8px #fff, 0 0 12px #0ff, 0 0 20px #0ff; }
  100% { text-shadow: 0 0 12px #fff, 0 0 20px #f0f, 0 0 28px #f0f; }
}

@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.05); }
  100% { transform: scale(1); }
}

/* Container */
.container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

/* Navbar */
nav {
  background: var(--primary);
  color: #fff;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 2rem;
  flex-wrap: wrap;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15);
}

.logo {
  font-size: 1.5rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  animation: logoGlow 2s ease-in-out infinite alternate;
  margin: 0;
}

.nav-right {
  display: flex;
  gap: 20px;
  align-items: center;
  flex-wrap: wrap;
}

.nav-right a {
  position: relative;
  color: paleturquoise;
  font-weight: 600;
  text-decoration: none;
  padding: 8px 12px;
  border-radius: 4px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 8px;
}

.nav-right a::after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 2px;
  background: linear-gradient(to right, #ff6ec4, #7873f5);
  transform: scaleX(0);
  transform-origin: right;
  transition: transform 0.3s ease-in-out;
}

.nav-right a:hover,
.nav-right a.active {
  color: #00ffff;
  text-shadow: 0 0 8px rgba(0, 255, 255, 0.6);
  background: rgba(255, 255, 255, 0.1);
}

.nav-right a:hover::after,
.nav-right a.active::after {
  transform: scaleX(1);
  transform-origin: left;
}

/* Main Content */
main {
  animation: fadeIn 0.7s ease-in-out;
  flex: 1;
  padding: 30px 0;
}

/* Page Header */
.page-header {
  margin-bottom: 30px;
  text-align: center;
  position: relative;
}

.page-header::after {
  content: '';
  position: absolute;
  bottom: -10px;
  left: 50%;
  transform: translateX(-50%);
  width: 80px;
  height: 4px;
  background: linear-gradient(to right, var(--primary), var(--primary-light));
  border-radius: 2px;
}

.page-header h1 {
  font-size: 2.2rem;
  font-weight: 700;
  color: var(--primary-dark);
  margin-bottom: 10px;
  letter-spacing: -0.5px;
}

.page-header p {
  color: var(--gray);
  font-size: 1.1rem;
  max-width: 600px;
  margin: 0 auto;
}

/* Breadcrumb */
.breadcrumb {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 25px;
  color: var(--gray);
  font-size: 0.95rem;
}

.breadcrumb a {
  color: var(--primary);
  text-decoration: none;
  transition: color 0.3s;
}

.breadcrumb a:hover {
  color: var(--primary-dark);
  text-decoration: underline;
}

.breadcrumb span {
  color: var(--gray);
}

/* Form Container */
.form-container {
  background: var(--card-bg);
  border-radius: 16px;
  padding: 40px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(0, 0, 0, 0.05);
  max-width: 900px;
  margin: 0 auto;
  position: relative;
  overflow: hidden;
}

.form-container::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 5px;
  background: linear-gradient(90deg, var(--primary), var(--primary-light));
}

.form-header {
  margin-bottom: 30px;
  text-align: center;
}

.form-header h2 {
  font-size: 1.8rem;
  font-weight: 600;
  color: var(--primary-dark);
  margin: 0 0 10px;
}

.form-header p {
  color: var(--gray);
  margin: 0;
  font-size: 1.05rem;
}

/* Form Sections */
.form-section {
  margin-bottom: 30px;
  padding-bottom: 25px;
  border-bottom: 1px solid var(--border);
}

.form-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.form-section-title {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
  color: var(--primary-dark);
  font-size: 1.3rem;
  font-weight: 600;
}

.form-section-title i {
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.1rem;
}

/* Form Grid */
.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 25px;
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: var(--dark);
  display: flex;
  align-items: center;
  gap: 8px;
}

.form-group label.required::after {
  content: '*';
  color: var(--danger);
  margin-left: 4px;
}

.form-control {
  width: 100%;
  padding: 14px 16px;
  border: 1px solid var(--border);
  border-radius: 8px;
  font-family: 'Poppins', sans-serif;
  font-size: 1rem;
  transition: all 0.3s;
  background: #fafafa;
}

.form-control:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(106, 13, 173, 0.15);
  background: white;
  transform: translateY(-2px);
}

/* File Upload */
.file-upload {
  position: relative;
  display: flex;
  align-items: center;
  gap: 10px;
}

.file-upload input[type="file"] {
  position: absolute;
  opacity: 0;
  width: 100%;
  height: 100%;
  cursor: pointer;
}

.file-upload-label {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  background: #fafafa;
  border: 1px solid var(--border);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s;
  width: 100%;
}

.file-upload-label:hover {
  background: #f0f0f0;
  border-color: var(--primary);
}

.file-upload-text {
  color: var(--gray);
  flex: 1;
}

.file-upload-icon {
  color: var(--primary);
  font-size: 1.2rem;
}

/* Buttons */
.btn {
  padding: 14px 28px;
  border: none;
  border-radius: 8px;
  font-family: 'Poppins', sans-serif;
  font-size: 1.05rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.btn-primary {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
}

.btn-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 12px rgba(106, 13, 173, 0.3);
  animation: pulse 1s infinite;
}

.btn-secondary {
  background: var(--light);
  color: var(--dark);
  border: 1px solid var(--border);
}

.btn-secondary:hover {
  background: #e9ecef;
  transform: translateY(-3px);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
}

.form-buttons {
  display: flex;
  gap: 20px;
  margin-top: 30px;
  justify-content: center;
  flex-wrap: wrap;
}

/* Alerts */
.alert {
  padding: 18px 22px;
  border-radius: 10px;
  margin-bottom: 25px;
  display: flex;
  align-items: center;
  gap: 15px;
  font-weight: 500;
  font-size: 1.05rem;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
  border: none;
}

.alert-success {
  background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
  color: #2e7d32;
}

.alert-error {
  background: linear-gradient(135deg, #ffebee, #ffcdd2);
  color: #c62828;
}

/* Success View */
.success-view {
  text-align: center;
  padding: 50px 20px;
}

.success-view i {
  font-size: 5rem;
  color: var(--success);
  margin-bottom: 25px;
  animation: pulse 2s infinite;
}

.success-view h3 {
  font-size: 2rem;
  color: var(--primary-dark);
  margin-bottom: 15px;
}

.success-view p {
  color: var(--gray);
  margin-bottom: 35px;
  font-size: 1.1rem;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
  line-height: 1.7;
}

.success-buttons {
  display: flex;
  justify-content: center;
  gap: 25px;
  flex-wrap: wrap;
}

.success-buttons a {
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 14px 30px;
  border-radius: 8px;
  font-weight: 500;
  font-size: 1.05rem;
  transition: all 0.3s;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.btn-dashboard {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
}

.btn-dashboard:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 16px rgba(106, 13, 173, 0.3);
  animation: pulse 1s infinite;
}

.btn-vehicles {
  background: var(--light);
  color: var(--dark);
  border: 1px solid var(--border);
}

.btn-vehicles:hover {
  background: #e9ecef;
  transform: translateY(-3px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

/* Footer */
footer {
  background: var(--primary);
  color: #fff;
  text-align: center;
  padding: 1.2rem;
  position: relative;
  z-index: 100;
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
}

/* Responsive Design */
@media (max-width: 992px) {
  .container {
    padding: 0 15px;
  }
  
  .form-container {
    padding: 30px;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  
  .page-header h1 {
    font-size: 2rem;
  }
}

@media (max-width: 768px) {
  body {
    padding-top: 70px;
  }
  
  nav {
    padding: 0.8rem 1rem;
  }
  
  .logo {
    font-size: 1.3rem;
  }
  
  .nav-right {
    gap: 10px;
  }
  
  .nav-right a {
    padding: 6px 8px;
    font-size: 0.9rem;
  }
  
  .page-header h1 {
    font-size: 1.8rem;
  }
  
  .form-container {
    padding: 25px;
  }
  
  .form-header h2 {
    font-size: 1.6rem;
  }
  
  .form-section-title {
    font-size: 1.2rem;
  }
  
  .form-buttons {
    flex-direction: column;
    gap: 15px;
  }
  
  .success-buttons {
    flex-direction: column;
    gap: 15px;
  }
  
  .success-buttons a {
    width: 100%;
    justify-content: center;
  }
  
  .alert {
    padding: 15px;
    font-size: 1rem;
  }
}

@media (max-width: 576px) {
  .page-header h1 {
    font-size: 1.6rem;
  }
  
  .nav-right {
    gap: 8px;
  }
  
  .nav-right a {
    font-size: 0.8rem;
    padding: 5px 6px;
  }
  
  .form-container {
    padding: 20px;
  }
  
  .form-control, .file-upload-label {
    padding: 12px 14px;
  }
  
  .btn {
    padding: 12px 20px;
    font-size: 1rem;
  }
  
  .success-view i {
    font-size: 4rem;
  }
  
  .success-view h3 {
    font-size: 1.7rem;
  }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<?php include 'includes/navbar.php'; ?>
<main>
    
    <div class="page-header">
      <h1>Add New Vehicle</h1>
      <p>Register a new vehicle and driver in the system</p>
    </div>

    <div class="form-container">
      <?php if (!empty($message)) echo $message; ?>
      
      <?php if ($success): ?>
        <div class="success-view">
          <i class="fas fa-check-circle"></i>
          <h3>Vehicle Added Successfully!</h3>
          <p>Vehicle and driver have been successfully registered in the system. The driver can now login using their phone number and the vehicle number plate as password.</p>
          <div class="success-buttons">
            <a href="index.php" class="btn-dashboard">
              <i class="fas fa-tachometer-alt"></i> Go to Dashboard
            </a>
            <a href="existing_vehicles.php" class="btn-vehicles">
              <i class="fas fa-car"></i> View All Vehicles
            </a>
          </div>
        </div>
      <?php else: ?>
        <div class="form-header">
          <h2>Vehicle Registration Form</h2>
          <p>Please fill in all required fields to register a new vehicle</p>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
          <div class="form-section">
            <div class="form-section-title">
              <i class="fas fa-car"></i>
              <h3>Vehicle Information</h3>
            </div>
            
            <div class="form-grid">
              <div class="form-group">
                <label for="number_plate" class="required">Number Plate</label>
                <input type="text" id="number_plate" name="number_plate" class="form-control" required placeholder="e.g. KDA 123A">
              </div>
              
              <div class="form-group">
                <label for="route" class="required">Route</label>
                <input type="text" id="route" name="route" class="form-control" required placeholder="e.g. Nairobi - Kisumu">
              </div>
              
              <div class="form-group">
                <label for="type" class="required">Vehicle Type</label>
                <input type="text" id="type" name="type" class="form-control" required placeholder="e.g. saloon, suv, van, bus">
              </div>
              
              <div class="form-group">
                <label for="color" class="required">Color</label>
                <input type="text" id="color" name="color" class="form-control" required placeholder="e.g. white, blue, silver">
              </div>
              
              <div class="form-group">
                <label for="capacity" class="required">Capacity</label>
                <input type="number" id="capacity" name="capacity" class="form-control" min="1" required placeholder="Number of passengers">
              </div>
            </div>
          </div>
          
          <div class="form-section">
            <div class="form-section-title">
              <i class="fas fa-user"></i>
              <h3>Driver Information</h3>
            </div>
            
            <div class="form-grid">
              <div class="form-group">
                <label for="driver_name" class="required">Driver Name</label>
                <input type="text" id="driver_name" name="driver_name" class="form-control" required placeholder="Full name">
              </div>
              
              <div class="form-group">
                <label for="driver_phone" class="required">Driver Phone</label>
                <input type="text" id="driver_phone" name="driver_phone" class="form-control" required placeholder="e.g. 0712345678">
              </div>
            </div>
          </div>
          
          <div class="form-section">
            <div class="form-section-title">
              <i class="fas fa-user-tie"></i>
              <h3>Owner Information</h3>
            </div>
            
            <div class="form-grid">
              <div class="form-group">
                <label for="owner_name" class="required">Owner Name</label>
                <input type="text" id="owner_name" name="owner_name" class="form-control" required placeholder="Full name">
              </div>
              
              <div class="form-group">
                <label for="owner_phone" class="required">Owner Phone</label>
                <input type="text" id="owner_phone" name="owner_phone" class="form-control" required placeholder="e.g. 0712345678">
              </div>
            </div>
          </div>
          
          <div class="form-section">
            <div class="form-section-title">
              <i class="fas fa-image"></i>
              <h3>Vehicle Image</h3>
            </div>
            
            <div class="form-group">
              <label class="required">Upload Vehicle Image</label>
              <div class="file-upload">
                <input type="file" name="vehicle_image" id="vehicle_image" required accept="image/*">
                <label for="vehicle_image" class="file-upload-label">
                  <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                  <span class="file-upload-text">Choose an image file...</span>
                </label>
              </div>
            </div>
          </div>
          
          <div class="form-buttons">
            <button type="submit" name="add_vehicle" class="btn btn-primary">
              <i class="fas fa-save"></i> Register Vehicle
            </button>
            <a href="existing_vehicles.php" class="btn btn-secondary">
              <i class="fas fa-times"></i> Cancel
            </a>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</main>

<footer>&copy; <?php echo date('Y'); ?> Southrift Services Limited | All Rights Reserved</footer>

<script>
// Update file upload label text when file is selected
document.getElementById('vehicle_image').addEventListener('change', function(e) {
  const fileName = e.target.files[0] ? e.target.files[0].name : 'Choose an image file...';
  document.querySelector('.file-upload-text').textContent = fileName;
});
</script>

</body>
</html>