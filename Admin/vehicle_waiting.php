<?php
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/db.php';

// Map status from GET into a message so it shows on page load
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status === 'added') {
        $message = "✅ Vehicle marked as waiting successfully.";
    } elseif ($status === 'error') {
        $message = "❌ Failed to update vehicle.";
    } elseif ($status === 'notfound') {
        $message = "⚠️ Vehicle not found.";
    } elseif ($status === 'empty') {
        $message = "ℹ️ Please enter a number plate.";
    }
}

// Handle removal of a vehicle from waiting
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['remove'])) {
    $number_plate = $_POST['number_plate'];
    $stmt = $conn->prepare("UPDATE vehicles SET is_waiting = 0, is_active = 0 WHERE number_plate = ?");
    $stmt->bind_param("s", $number_plate);
    if ($stmt->execute()) {
        $message = "✅ Vehicle removed from waiting list successfully.";
    }
    $stmt->close();
}

// Fetch only vehicles that are in waiting
$result = $conn->query("SELECT * FROM vehicles WHERE is_waiting = 1 ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Vehicles in Waiting – Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root {
  --purple: #6A0DAD;
  --purple-dark: #58009c;
  --bg: #f4f4f7;
  --shadow: 0 4px 10px rgba(0,0,0,.08);
}

* { box-sizing: border-box }

body {
  margin: 0;
  font-family: 'Poppins', sans-serif;
  background: var(--bg);
  padding: 40px;
}

/* Fade-in effect only for main content */
main {
  animation: fadeIn 0.7s ease-in-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Navbar */
nav {
  background: var(--purple);
  padding: 1rem 2rem;
  color: #fff;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  margin: -40px -40px 40px -40px;
}
.logo {
  font-size: 1.5rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  animation: logoGlow 2s ease-in-out infinite alternate;
}
@keyframes logoGlow {
  0% { text-shadow: 0 0 8px #fff, 0 0 12px #0ff; }
  100% { text-shadow: 0 0 12px #fff, 0 0 20px #f0f; }
}
.nav-right {
  display: flex;
  gap: 20px;
  align-items: center;
}
.nav-right a {
  position: relative;
  color: paleturquoise;
  font-weight: 600;
  text-decoration: none;
  padding: 8px 10px;
  text-transform: uppercase;
  letter-spacing: 1px;
  transition: color 0.3s ease;
}
.nav-right a::after {
  content: "";
  position: absolute;
  bottom: 0; left: 0;
  width: 100%; height: 2px;
  background: linear-gradient(to right, #ff6ec4, #7873f5);
  transform: scaleX(0);
  transform-origin: right;
  transition: transform 0.4s ease-in-out;
}
.nav-right a:hover {
  color: #00ffff;
  text-shadow: 0 0 8px rgba(0, 255, 255, 0.6);
}
.nav-right a:hover::after {
  transform: scaleX(1);
  transform-origin: left;
}

/* Table Section */
h2 { 
  color: var(--purple); 
  text-align: center; 
  margin-bottom: 20px; 
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 600;
}

.message {
  max-width: 1000px;
  margin: 0 auto 20px;
  background: #d4edda;
  color: #155724;
  padding: 12px;
  border-left: 6px solid #28a745;
  border-radius: 6px;
  text-align: center;
}

.table-wrap {
  overflow-x: auto;
  max-width: 1000px;
  margin: 0 auto;
  background: #fff;
  padding: 20px;
  border-radius: 12px;
  box-shadow: var(--shadow);
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 900px;
}

th, td {
  border: 1px solid #ddd;
  padding: 12px 15px;
  text-align: left;
  font-size: 0.9rem;
}

th { 
  background: #f0f0f5; 
  font-weight: 600;
  color: #333;
  text-transform: uppercase;
  font-size: 0.8rem;
  letter-spacing: 0.5px;
}

tbody tr:nth-child(even) { background: #fafafa; }

tbody tr:hover {
  background-color: #f5f5ff;
  transition: background-color 0.2s ease;
}

.action-btn {
  padding: 6px 12px;
  border: none;
  border-radius: 5px;
  color: white;
  cursor: pointer;
  font-weight: 500;
  font-size: 0.85rem;
  transition: all 0.2s ease;
  margin: 2px;
  min-width: 80px; /* Set a minimum width for buttons */
  text-align: center;
}

.remove-btn {
  background: #dc3545;
}
.remove-btn:hover {
  background: #c82333;
  transform: translateY(-1px);
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.post-btn {
  background: #28a745;
}
.post-btn:hover {
  background: #218838;
  transform: translateY(-1px);
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.no-vehicles {
  text-align: center;
  color: #666;
  font-style: italic;
  padding: 20px;
  background: #fff;
  border-radius: 8px;
  max-width: 1000px;
  margin: 0 auto;
  box-shadow: var(--shadow);
}

/* Bottom nav buttons */
nav.bottomnav {
  max-width: 1000px; 
  margin: 30px auto 10px;
  text-align: center;
}
nav.bottomnav a {
  text-decoration: none;
  padding: 10px 20px;
  /* background: var(--purple); */
  color: white;
  border-radius: 6px; 
  margin: 0 10px;
  font-weight: 600; 
  display: inline-block;
  transition: all 0.2s ease;
}
nav.bottomnav a:hover { 
  background: blueviolet;
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

/* Fixed Footer */
footer {
  background: var(--purple);
  color: #fff;
  text-align: center;
  padding: 1rem;
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  z-index: 100;
  font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 768px) {
  body {
    padding: 20px 15px 80px;
  }

  nav {
    padding: 0.8rem 1rem;
    margin: -20px -15px 20px -15px;
  }

  .logo {
    font-size: 1.2rem;
  }

  .table-wrap {
    padding: 10px;
  }

  th, td {
    padding: 8px 10px;
    font-size: 0.8rem;
  }

  .action-btn {
    padding: 4px 8px;
    font-size: 0.75rem;
  }

  nav.bottomnav a {
    padding: 8px 15px;
    margin: 5px;
    font-size: 0.85rem;
  }
}

/* New style for posted status */
.posted-status {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 5px;
  background: #28a745; /* Changed from #6c757d (gray) to #28a745 (green) */
  color: white;
  font-weight: 500;
  font-size: 0.85rem;
  margin: 2px;
  white-space: nowrap;
  min-width: 80px;
  text-align: center;
  margin: 0;
}

/* Adjust the action buttons container */
.action-buttons {
  display: flex;
  gap: 5px;
  flex-wrap: nowrap; /* Changed from wrap to nowrap */
  justify-content: space-between; /* Added to space buttons to the left and right */
  align-items: center;
  min-width: 200px; /* Ensure minimum width for the actions column */
}
</style>
</head>
<body>

<!-- Top Navbar -->
<nav>
  <div class="logo">SouthRift Services</div>
  <div class="nav-right">
    <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="existing_vehicles.php"><i class="fas fa-car"></i> Vehicles</a>
    <a href="vehicle_waiting.php" class="active"><i class="fas fa-clock"></i> Waiting List</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</nav>

<main>
  <h2>Vehicles in Waiting</h2>

  <?php if (isset($message)): ?>
    <div class="message"><?= $message ?></div>
  <?php endif; ?>

  <?php if ($result->num_rows > 0): ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Number Plate</th>
            <th>Type</th>
            <th>Color</th>
            <th>Route</th>
            <th>Driver Name</th>
            <th>Driver Phone</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              
              <td><?= htmlspecialchars($row['number_plate']) ?></td>
              <td><?= htmlspecialchars($row['type']) ?></td>
              <td><?= htmlspecialchars($row['color']) ?></td>
              <td><?= htmlspecialchars($row['route'] ?? 'N/A') ?></td>
              <td><?= htmlspecialchars($row['driver_name']) ?></td>
              <td><?= htmlspecialchars($row['driver_phone']) ?></td>
              <td>
                <span style="color: #ffc107; font-weight: 500;">Waiting</span>
              </td>
              <td>
                <div class="action-buttons">
                  <form method='post' style='display:inline-block'>
                    <input type='hidden' name='number_plate' value='<?= $row['number_plate'] ?>'>
                    <button type='submit' name='remove' class='action-btn remove-btn' title='Remove from waiting list'>
                      <i class='fas fa-times'></i> Remove
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="no-vehicles">
      <p><i class="fas fa-info-circle" style="font-size: 2rem; color: #6A0DAD; margin-bottom: 10px;"></i></p>
      <p>No vehicles currently in waiting.</p>
    </div>
  <?php endif; ?>

  <nav class="bottomnav">
    <a href="index.php#add-to-waiting"><i class="fas fa-plus"></i> Add Vehicle to Waiting</a>
    <a href="existing_vehicles.php"><i class="fas fa-car"></i> View All Vehicles</a>
  </nav>
</main>

<footer>
  &copy; <?= date('Y') ?> Southrift Services Limited. All rights reserved.
</footer>

</body>
</html>
