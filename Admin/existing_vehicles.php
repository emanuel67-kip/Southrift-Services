<?php
require __DIR__ . '/auth.php';
require dirname(__DIR__) . '/db.php';

$deletedMsg = "";
if (isset($_POST['delete_vehicle_id'])) {
    $id = (int)$_POST['delete_vehicle_id'];
    $stmt = $conn->prepare("DELETE FROM vehicles WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $deletedMsg = "✅ Vehicle deleted successfully.";
    }
}

$vehicles = $conn->query("SELECT * FROM vehicles ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Existing Vehicles – Admin</title>
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
html {
  animation: fadeIn 0.7s ease-in-out;
}
/* ── only opacity now, no vertical shift ── */
@keyframes fadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}

body {
  margin: 0;
  font-family: 'Poppins', sans-serif;
  background: var(--bg);
  padding: 40px;
}

/* === Navbar === (unchanged) */
nav.topnav {
  background: var(--purple);
  padding: 1rem 2rem;
  color: white;
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
  0%   { text-shadow: 0 0 8px #fff, 0 0 12px #0ff, 0 0 20px #0ff; }
  100% { text-shadow: 0 0 12px #fff, 0 0 20px #f0f, 0 0 28px #f0f; }
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

/* === Table Section === (unchanged) */
h2 { color: var(--purple); text-align: center; margin-bottom: 20px; }
.message {
  max-width: 1000px;
  margin: 0 auto 20px;
  background: #d4edda;
  color: #155724;
  padding: 12px;
  border-left: 6px solid #28a745;
  border-radius: 6px;
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
  padding: 10px;
  text-align: left;
  font-size: 0.9rem;
}
th { background: #f0f0f5; }
tbody tr:nth-child(even) { background: #fafafa; }
.delete-btn {
  background: red; border: none; color: white;
  padding: 6px 12px; border-radius: 5px; cursor: pointer;
}
.delete-btn:hover { background: darkred; }
.img-thumb { max-width: 80px; }

/* Bottom navigation buttons (unchanged) */
nav.bottomnav {
  max-width: 1000px; margin: 30px auto; text-align: center;
}
nav.bottomnav a {
  text-decoration: none;
  padding: 10px 20px;
  background: var(--purple); color: #fff;
  border-radius: 6px; margin: 0 10px;
  font-weight: 600; display: inline-block;
}
nav.bottomnav a:hover { background: var(--purple-dark); }

/* === Fixed Footer === */
footer{
  background: var(--purple);
  color: #fff;
  text-align: center;
  padding: 1rem;
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  z-index: 100;
}
</style>
</head>
<body>

<!-- Top Navbar -->
<nav class="topnav">
  <div class="logo">Southrift Services Limited</div>
  <div class="nav-right">
    <a href="index.php"><i class="fa fa-home"></i> Dashboard</a>
    <a href="#"><i class="fa fa-user-shield"></i> Super Admin</a>
    <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
  </div>
</nav>

<h2>Existing Vehicles</h2>

<?php if ($deletedMsg): ?>
  <div class="message"><?= $deletedMsg ?></div>
<?php endif; ?>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Number Plate</th>
        <th>Type</th>
        <th>Color</th>
        <th>Route</th>
        <th>Capacity</th>
        <th>Driver Name</th>
        <th>Driver Phone</th>
        <th>Owner Name</th>
        <th>Owner Phone</th>
      </tr>
    </thead>
    <tbody>
      <?php while($v = $vehicles->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($v['number_plate']) ?></td>
          <td><?= htmlspecialchars($v['type'] ?? '') ?></td>
          <td><?= htmlspecialchars($v['color'] ?? '') ?></td>
          <td><?= htmlspecialchars($v['route']) ?></td>
          <td><?= (int)$v['capacity'] ?></td>
          <td><?= htmlspecialchars($v['driver_name']) ?></td>
          <td><?= htmlspecialchars($v['driver_phone']) ?></td>
          <td><?= htmlspecialchars($v['owner_name']) ?></td>
          <td><?= htmlspecialchars($v['owner_phone']) ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<!-- Bottom Navigation -->
<nav class="bottomnav">
  <a href="index.php">← Dashboard</a>
  <a href="add_vehicle.php">➕ Add Vehicle</a>
</nav>

<!-- Footer -->
<footer>&copy; <?=date('Y')?> Southrift Services Limited | All Rights Reserved</footer>

</body>
</html>
