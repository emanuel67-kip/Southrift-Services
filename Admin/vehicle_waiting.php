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

// Get count of waiting vehicles
$waitingCount = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vehicles in Waiting – Admin</title>
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
  padding-bottom: 0;
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

@keyframes logoGlow {
  0% { text-shadow: 0 0 8px #fff, 0 0 12px #0ff; }
  100% { text-shadow: 0 0 12px #fff, 0 0 20px #f0f; }
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
  flex: 1;
}

/* Page Header */
.page-header {
  margin: 20px 0 30px 0;
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
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--primary-dark);
  margin-bottom: 10px;
  letter-spacing: -0.5px;
}

.page-header p {
  color: var(--gray);
  font-size: 1.2rem;
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

/* Stats Overview */
.stats-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 25px;
  margin-bottom: 35px;
}

.stat-card {
  background: var(--card-bg);
  border-radius: 16px;
  padding: 25px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 5px;
  background: linear-gradient(90deg, var(--primary), var(--primary-light));
}

.stat-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
}

.stat-card .icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  font-size: 1.8rem;
  color: white;
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  box-shadow: 0 5px 15px rgba(106, 13, 173, 0.3);
}

.stat-card h3 {
  font-size: 2.2rem;
  margin-bottom: 5px;
  color: var(--dark);
  font-weight: 700;
}

.stat-card p {
  color: var(--gray);
  font-size: 1rem;
  margin: 0;
  font-weight: 500;
}

/* Alerts */
.alert {
  padding: 20px 25px;
  border-radius: 12px;
  margin-bottom: 30px;
  display: flex;
  align-items: center;
  gap: 15px;
  font-weight: 500;
  font-size: 1.05rem;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
  border: none;
}

.alert.success {
  background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
  color: #2e7d32;
}

.alert.error {
  background: linear-gradient(135deg, #ffebee, #ffcdd2);
  color: #c62828;
}

.alert.info {
  background: linear-gradient(135deg, #e3f2fd, #bbdefb);
  color: #1565c0;
}

/* Table Section */
.table-container {
  background: var(--card-bg);
  border-radius: 20px;
  padding: 40px;
  box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(0, 0, 0, 0.05);
  margin-bottom: 40px;
  overflow: hidden;
  position: relative;
}

.table-container::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 8px;
  background: linear-gradient(90deg, var(--primary), var(--primary-light));
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  flex-wrap: wrap;
  gap: 20px;
}

.table-header h2 {
  font-size: 1.8rem;
  font-weight: 700;
  color: var(--primary-dark);
  margin: 0;
  position: relative;
  padding-left: 40px;
}

.table-header h2::before {
  content: '\f017';
  font-family: 'Font Awesome 5 Free';
  font-weight: 900;
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  color: var(--primary);
  font-size: 1.5rem;
  width: 36px;
  height: 36px;
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.search-box {
  position: relative;
  max-width: 350px;
}

.search-box input {
  width: 100%;
  padding: 14px 20px 14px 50px;
  border: 1px solid var(--border);
  border-radius: 10px;
  font-family: 'Poppins', sans-serif;
  font-size: 1.05rem;
  transition: all 0.3s;
  background: #fafafa;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.search-box input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 4px rgba(106, 13, 173, 0.15);
  background: white;
  transform: translateY(-2px);
}

.search-box i {
  position: absolute;
  left: 20px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--gray);
  font-size: 1.2rem;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 1000px;
  background: white;
  border-radius: 15px;
  overflow: hidden;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
}

thead th {
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  color: white;
  padding: 20px 15px;
  text-align: left;
  font-weight: 600;
  font-size: 1rem;
  border-bottom: none;
  position: sticky;
  top: 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

thead th:first-child {
  border-top-left-radius: 15px;
}

thead th:last-child {
  border-top-right-radius: 15px;
}

tbody td {
  padding: 18px 15px;
  border-bottom: 1px solid #eee;
  font-size: 1rem;
  color: var(--dark);
  font-weight: 400;
}

tbody tr {
  transition: all 0.3s ease;
}

tbody tr:hover {
  background-color: #f8f9ff;
  transform: scale(1.005);
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
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

/* Status Indicators */
.status-badge {
  padding: 8px 16px;
  border-radius: 25px;
  font-size: 0.9rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.status-waiting {
  background: linear-gradient(135deg, #fff8e1, #ffecb3);
  color: #333;
  border: 1px solid #ffd54f;
}

/* Action Buttons */
.action-btn {
  padding: 10px 16px;
  border: none;
  border-radius: 8px;
  color: white;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.95rem;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-width: 120px;
  text-align: center;
  justify-content: center;
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
}

.remove-btn {
  background: linear-gradient(135deg, var(--danger), #e53935);
}

.remove-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 12px rgba(244, 67, 54, 0.4);
  animation: pulse 1s infinite;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 80px 20px;
  color: var(--gray);
}

.empty-state i {
  font-size: 5rem;
  margin-bottom: 30px;
  color: var(--light-gray);
  animation: pulse 2s infinite;
}

.empty-state h3 {
  font-size: 2rem;
  color: var(--dark);
  margin-bottom: 20px;
  font-weight: 700;
}

.empty-state p {
  font-size: 1.2rem;
  max-width: 600px;
  margin: 0 auto 35px;
  line-height: 1.7;
}

.empty-state .info-box {
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  padding: 25px;
  border-radius: 15px;
  display: inline-block;
  max-width: 600px;
  font-size: 1.05rem;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

/* Bottom Navigation */
.bottom-nav {
  display: flex;
  justify-content: center;
  gap: 30px;
  margin-top: 30px;
  flex-wrap: wrap;
}

.nav-btn {
  text-decoration: none;
  padding: 16px 32px;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: #fff;
  border-radius: 10px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 12px;
  transition: all 0.3s ease;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  font-size: 1.05rem;
}

.nav-btn:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 20px rgba(106, 13, 173, 0.3);
  animation: pulse 1s infinite;
}

.nav-btn.secondary {
  background: linear-gradient(135deg, var(--light), #e9ecef);
  color: var(--dark);
  border: 1px solid var(--border);
}

.nav-btn.secondary:hover {
  background: linear-gradient(135deg, #e9ecef, #dde0e3);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

/* Footer */
footer {
  background: var(--primary);
  color: #fff;
  text-align: center;
  padding: 1.5rem;
  margin-top: 50px;
  position: relative;
  z-index: 100;
  width: 100%;
  box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.1);
}

/* Responsive Design */
@media (max-width: 992px) {
  .container {
    padding: 0 15px;
  }
  
  .table-container {
    padding: 30px;
  }
  
  .stats-overview {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
  }
  
  .page-header h1 {
    font-size: 2.2rem;
  }
  
  .page-header p {
    font-size: 1.1rem;
  }
}

@media (max-width: 768px) {
  body {
    padding-top: 70px;
    padding-bottom: 0;
  }
  
  .page-header h1 {
    font-size: 2rem;
  }
  
  .table-container {
    padding: 25px;
  }
  
  .table-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .search-box {
    max-width: 100%;
    width: 100%;
  }
  
  .action-btn {
    padding: 8px 12px;
    font-size: 0.9rem;
    min-width: 100px;
  }
  
  .stats-overview {
    grid-template-columns: 1fr;
  }
  
  .bottom-nav {
    flex-direction: column;
    gap: 20px;
  }
  
  .nav-btn {
    width: 100%;
    justify-content: center;
  }
  
  thead th, tbody td {
    padding: 15px 10px;
    font-size: 0.95rem;
  }
}

@media (max-width: 576px) {
  .page-header h1 {
    font-size: 1.8rem;
  }
  
  .page-header p {
    font-size: 1rem;
  }
  
  .stat-card {
    padding: 20px;
  }
  
  .stat-card h3 {
    font-size: 1.8rem;
  }
  
  .action-btn {
    min-width: 90px;
    padding: 6px 10px;
    font-size: 0.85rem;
  }
  
  .empty-state i {
    font-size: 4rem;
  }
  
  .empty-state h3 {
    font-size: 1.7rem;
  }
  
  .empty-state p {
    font-size: 1rem;
  }
}
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>


  <div class="page-header">
    <h1>Vehicles in Waiting</h1>
    <p>Manage vehicles that are currently waiting for assignment</p>
  </div>

  <!-- Stats Overview -->
  <div class="stats-overview">
    <div class="stat-card">
      <div class="icon">
        <i class="fas fa-car"></i>
      </div>
      <h3><?php echo $waitingCount; ?></h3>
      <p>Vehicles Waiting</p>
    </div>
    <div class="stat-card">
      <div class="icon">
        <i class="fas fa-clock"></i>
      </div>
      <h3><?php echo $waitingCount; ?></h3>
      <p>Pending Assignment</p>
    </div>
  </div>

  <?php if (isset($message)): ?>
    <div class="alert success">
      <i class="fas fa-check-circle"></i>
      <?php echo $message; ?>
    </div>
  <?php endif; ?>

  <div class="table-container">
    <div class="table-header">
      <h2>Waiting Vehicles</h2>
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Search vehicles by plate, driver, or route...">
      </div>
    </div>
    
    <div class="table-wrap">
      <?php if ($result->num_rows > 0): ?>
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
                <td><?php echo htmlspecialchars($row['number_plate']); ?></td>
                <td><?php echo htmlspecialchars($row['type']); ?></td>
                <td><?php echo htmlspecialchars($row['color']); ?></td>
                <td><?php echo htmlspecialchars($row['route'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($row['driver_name']); ?></td>
                <td><?php echo htmlspecialchars($row['driver_phone']); ?></td>
                <td>
                  <span class="status-badge status-waiting">
                    <i class="fas fa-clock"></i> Waiting
                  </span>
                </td>
                <td>
                  <form method="post" style="display:inline-block;">
                    <input type="hidden" name="number_plate" value="<?php echo $row['number_plate']; ?>">
                    <button type="submit" name="remove" class="action-btn remove-btn" title="Remove from waiting list">
                      <i class="fas fa-times"></i> Remove
                    </button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state">
          <i class="fas fa-car"></i>
          <h3>No Vehicles in Waiting</h3>
          <p>There are currently no vehicles in the waiting list.</p>
          <div class="info-box">
            <i class="fas fa-info-circle" style="color: var(--info); margin-right: 8px;"></i>
            Vehicles will appear here when added to the waiting list
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="bottom-nav">
    <a href="index.php#add-to-waiting" class="nav-btn secondary">
      <i class="fas fa-plus"></i> Add Vehicle to Waiting
    </a>
    <a href="existing_vehicles.php" class="nav-btn">
      <i class="fas fa-car"></i> View All Vehicles
    </a>
  </div>
</div>

<footer>
  &copy; <?php echo date('Y'); ?> Southrift Services Limited. All rights reserved.
</footer>

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