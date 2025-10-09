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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Existing Vehicles – Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/admin-styles.css">
<style>
:root {
  --primary: #6A0DAD;
  --primary-dark: #58009c;
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
  --body-bg: #f5f7fb;
}

* { 
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: 'Poppins', sans-serif;
  background-color: var(--body-bg);
  color: var(--dark);
  line-height: 1.6;
  display: flex;
  min-height: 100vh;
  flex-direction: column;
  padding-top: 80px;
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

@keyframes logoGlow {
  0%   { text-shadow: 0 0 8px #fff, 0 0 12px #0ff, 0 0 20px #0ff; }
  100% { text-shadow: 0 0 12px #fff, 0 0 20px #f0f, 0 0 28px #f0f; }
}

/* Container */
.container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
  flex: 1;
}

/* Page Header */
.page-header {
  margin: 5px 0 15px 0;
  text-align: center;
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
  margin-bottom: 10px;
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

/* Message */
.message {
  max-width: 1000px;
  margin: 0 auto 25px;
  background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
  color: #2e7d32;
  padding: 15px 20px;
  border-radius: 10px;
  border-left: 6px solid var(--success);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 500;
}

/* Stats Overview */
.stats-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: var(--card-bg);
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  text-align: center;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
}

.stat-card .icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 15px;
  font-size: 1.5rem;
  color: white;
}

.stat-card .icon.purple {
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
}

.stat-card .icon.green {
  background: linear-gradient(135deg, var(--success), #81c784);
}

.stat-card .icon.blue {
  background: linear-gradient(135deg, var(--info), #64b5f6);
}

.stat-card h3 {
  font-size: 1.8rem;
  margin-bottom: 5px;
  color: var(--dark);
}

.stat-card p {
  color: var(--gray);
  font-size: 0.95rem;
  margin: 0;
}

/* Table Section */
.table-container {
  background: var(--card-bg);
  border-radius: 16px;
  padding: 30px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(0, 0, 0, 0.05);
  margin-bottom: 30px;
  overflow: hidden;
  position: relative;
}

.table-container::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 5px;
  background: linear-gradient(90deg, var(--primary), var(--primary-light));
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  flex-wrap: wrap;
  gap: 15px;
}

.table-header h2 {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--primary-dark);
  margin: 0;
  position: relative;
  padding-left: 30px;
}

.table-header h2::before {
  content: '\f1b9';
  font-family: 'Font Awesome 5 Free';
  font-weight: 900;
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  color: var(--primary);
}

.search-box {
  position: relative;
  max-width: 300px;
}

.search-box input {
  width: 100%;
  padding: 12px 15px 12px 40px;
  border: 1px solid var(--border);
  border-radius: 8px;
  font-family: 'Poppins', sans-serif;
  font-size: 1rem;
  transition: all 0.3s;
  background: #fafafa;
}

.search-box input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(106, 13, 173, 0.15);
  background: white;
}

.search-box i {
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--gray);
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 900px;
  background: white;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

thead th {
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  color: white;
  padding: 16px 15px;
  text-align: left;
  font-weight: 600;
  font-size: 0.95rem;
  border-bottom: none;
  position: sticky;
  top: 0;
}

thead th:first-child {
  border-top-left-radius: 10px;
}

thead th:last-child {
  border-top-right-radius: 10px;
}

tbody td {
  padding: 14px 15px;
  border-bottom: 1px solid #eee;
  font-size: 0.95rem;
  color: var(--dark);
}

tbody tr {
  transition: background-color 0.2s, transform 0.2s;
}

tbody tr:hover {
  background-color: #f8f9ff;
  transform: scale(1.01);
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

tbody tr:last-child td {
  border-bottom: none;
}

tbody tr:nth-child(even) {
  background-color: #fafafa;
}

tbody tr:nth-child(even):hover {
  background-color: #f0f2ff;
}

.img-thumb {
  max-width: 80px;
  border-radius: 8px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--light);
  color: var(--gray);
  border: 1px solid var(--border);
  cursor: pointer;
  transition: all 0.3s;
  text-decoration: none;
}

.action-btn:hover {
  background: var(--danger);
  color: white;
  border-color: var(--danger);
  transform: translateY(-2px);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 50px 20px;
  color: var(--gray);
}

.empty-state i {
  font-size: 4rem;
  margin-bottom: 20px;
  color: var(--light-gray);
}

.empty-state h3 {
  font-size: 1.5rem;
  color: var(--dark);
  margin-bottom: 10px;
}

.empty-state p {
  max-width: 500px;
  margin: 0 auto 20px;
}

/* Bottom Navigation */
.bottom-nav {
  display: flex;
  justify-content: center;
  gap: 20px;
  margin-top: 20px;
  flex-wrap: wrap;
}

.nav-btn {
  text-decoration: none;
  padding: 14px 28px;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: #fff;
  border-radius: 8px;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  transition: all 0.3s;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.nav-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 12px rgba(106, 13, 173, 0.25);
}

.nav-btn.secondary {
  background: var(--light);
  color: var(--dark);
  border: 1px solid var(--border);
}

.nav-btn.secondary:hover {
  background: #e9ecef;
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
}

/* Responsive Design */
@media (max-width: 992px) {
  .container {
    padding: 0 15px;
  }
  
  .table-container {
    padding: 25px;
  }
  
  .stats-overview {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  }
  
  .page-header h1 {
    font-size: 2rem;
  }
  
  thead th, tbody td {
    padding: 12px 10px;
  }
}

@media (max-width: 768px) {
  body {
    padding-top: 70px;
  }
  
  .page-header h1 {
    font-size: 1.8rem;
  }
  
  .table-container {
    padding: 20px;
  }
  
  .table-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .search-box {
    max-width: 100%;
    width: 100%;
  }
  
  .bottom-nav {
    flex-direction: column;
    gap: 15px;
  }
  
  .nav-btn {
    width: 100%;
    justify-content: center;
  }
  
  .stats-overview {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 576px) {
  .page-header h1 {
    font-size: 1.6rem;
  }
  
  thead th, tbody td {
    padding: 10px 8px;
    font-size: 0.9rem;
  }
  
  .action-btn {
    width: 32px;
    height: 32px;
  }
}
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>

    
    <div class="page-header">
      <h1>Manage All Registered Vehicles</h1>
      <p>View and manage all vehicles registered in the system</p>
    </div>

    <!-- Stats Overview -->
    <div class="stats-overview">
      <?php
      // Get vehicle count
      $vehicleCount = $conn->query("SELECT COUNT(*) AS count FROM vehicles")->fetch_assoc()['count'] ?? 0;
      
      // Get drivers count
      $driverCount = $conn->query("SELECT COUNT(DISTINCT driver_phone) AS count FROM vehicles")->fetch_assoc()['count'] ?? 0;
      
      // Get routes count
      $routeCount = $conn->query("SELECT COUNT(DISTINCT route) AS count FROM vehicles")->fetch_assoc()['count'] ?? 0;
      ?>
      <div class="stat-card">
        <div class="icon purple">
          <i class="fas fa-car"></i>
        </div>
        <h3><?php echo $vehicleCount; ?></h3>
        <p>Total Vehicles</p>
      </div>
      <div class="stat-card">
        <div class="icon green">
          <i class="fas fa-user"></i>
        </div>
        <h3><?php echo $driverCount; ?></h3>
        <p>Drivers</p>
      </div>
      <div class="stat-card">
        <div class="icon blue">
          <i class="fas fa-route"></i>
        </div>
        <h3><?php echo $routeCount; ?></h3>
        <p>Routes</p>
      </div>
    </div>

    <?php if ($deletedMsg): ?>
      <div class="message">
        <i class="fas fa-check-circle"></i>
        <?php echo $deletedMsg; ?>
      </div>
    <?php endif; ?>

    <div class="table-container">
      <div class="table-header">
        <h2>Vehicle Records</h2>
        <div class="search-box">
          <i class="fas fa-search"></i>
          <input type="text" id="searchInput" placeholder="Search vehicles...">
        </div>
      </div>
      
      <div class="table-wrap">
        <?php if ($vehicles->num_rows > 0): ?>
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
                  <td><?php echo htmlspecialchars($v['number_plate']); ?></td>
                  <td><?php echo htmlspecialchars($v['type'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($v['color'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($v['route']); ?></td>
                  <td><?php echo (int)$v['capacity']; ?></td>
                  <td><?php echo htmlspecialchars($v['driver_name']); ?></td>
                  <td><?php echo htmlspecialchars($v['driver_phone']); ?></td>
                  <td><?php echo htmlspecialchars($v['owner_name']); ?></td>
                  <td><?php echo htmlspecialchars($v['owner_phone']); ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="empty-state">
            <i class="fas fa-car"></i>
            <h3>No Vehicles Found</h3>
            <p>There are currently no vehicles registered in the system.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Bottom Navigation -->
    <div class="bottom-nav">
      <a href="index.php" class="nav-btn secondary">
        <i class="fas fa-arrow-left"></i> Dashboard
      </a>
      <a href="add_vehicle.php" class="nav-btn">
        <i class="fas fa-plus"></i> Add Vehicle
      </a>
    </div>
  </div>
</main>

<!-- Footer -->
<footer>&copy; <?php echo date('Y'); ?> Southrift Services Limited | All Rights Reserved</footer>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
  const searchTerm = this.value.toLowerCase();
  const tableRows = document.querySelectorAll('tbody tr');
  
  tableRows.forEach(row => {
    const text = row.textContent.toLowerCase();
    if (text.includes(searchTerm)) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
});
</script>

</body>
</html>