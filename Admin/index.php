<?php
require __DIR__ . '/auth.php';
require dirname(__DIR__) . '/db.php';

/* ─── quick stats (unchanged) ─── */
$totalVehicles = $conn->query("SELECT COUNT(*) AS n FROM vehicles")->fetch_assoc()['n'] ?? 0;
$today = date('Y-m-d');

/* ─── last‑7‑days (Sunday‑first) ─── */
$weekLabels = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$weekData   = array_fill(0,7,0);
$todayIndex = date('w');
$lastSunday = strtotime("-$todayIndex day");
$map = [];
for($i=0;$i<7;$i++){
    $date = date('Y-m-d', $lastSunday + $i*86400);
    $map[$date] = $i;
}
$startSql = date('Y-m-d', $lastSunday);
$endSql   = date('Y-m-d', $lastSunday + 6*86400);
$res = $conn->query("
    SELECT DATE(created_at) AS d, COUNT(*) AS c
    FROM bookings
    WHERE DATE(created_at) BETWEEN '$startSql' AND '$endSql'
    GROUP BY DATE(created_at)
");
while($row=$res->fetch_assoc()){
    $idx = $map[$row['d']] ?? null;
    if($idx!==null) $weekData[$idx]=(int)$row['c'];
}

/* ─── month data (unchanged) ─── */
$daysInMonth = date('t');
$monthData   = array_fill(1,$daysInMonth,0);
$resM = $conn->query("
    SELECT DAY(created_at) AS d, COUNT(*) AS c
    FROM bookings
    WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())
    GROUP BY DAY(created_at)
");
while($r=$resM->fetch_assoc()){
    $monthData[(int)$r['d']] = (int)$r['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard – Southrift</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="css/admin-styles.css">
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

/* Container */
.container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

/* Dashboard Header */
.dashboard-header {
  margin-bottom: 30px;
}

.dashboard-header h1 {
  font-size: 2rem;
  font-weight: 600;
  color: var(--primary-dark);
  margin-bottom: 5px;
}

.dashboard-header p {
  color: var(--gray);
  font-size: 1.1rem;
}

/* Stats Overview */
.stats-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  border: 1px solid var(--border);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
}

.stat-card .icon {
  width: 50px;
  height: 50px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 15px;
  font-size: 1.5rem;
  color: white;
}

.stat-card .icon.blue {
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
}

.stat-card .icon.green {
  background: linear-gradient(135deg, var(--success), #81c784);
}

.stat-card .icon.orange {
  background: linear-gradient(135deg, var(--warning), #ffd54f);
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

/* Section */
.section {
  background: white;
  border-radius: 10px;
  padding: 25px;
  margin-bottom: 30px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  border: 1px solid var(--border);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid var(--border);
}

.section-header h2 {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--primary-dark);
  margin: 0;
}

.view-all {
  color: var(--primary);
  text-decoration: none;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 5px;
  transition: color 0.3s;
}

.view-all:hover {
  color: var(--primary-dark);
}

/* Quick Actions Grid */
.quick-actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
}

.action-card {
  background: #f8f9ff;
  border-radius: 8px;
  padding: 25px 20px;
  text-align: center;
  transition: all 0.3s ease;
  border: 1px solid var(--border);
  text-decoration: none;
  color: inherit;
  display: block;
}

.action-card:hover {
  background: white;
  transform: translateY(-3px);
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
  border-color: var(--primary-light);
}

.action-card i {
  font-size: 2.5rem;
  margin-bottom: 15px;
  color: var(--primary);
}

.action-card h3 {
  font-size: 1.2rem;
  margin-bottom: 10px;
  color: var(--dark);
}

.action-card p {
  color: var(--gray);
  font-size: 0.95rem;
  margin: 0;
}

/* Chart Section */
.chart-container {
  height: 300px;
  position: relative;
}

.toggle-chart-view {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.toggle-btn {
  background: var(--light);
  border: 1px solid var(--border);
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s;
}

.toggle-btn.active {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
}

/* Form Section */
.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: var(--dark);
}

.form-row {
  display: flex;
  gap: 15px;
}

.form-control {
  flex: 1;
  padding: 12px 15px;
  border: 1px solid var(--border);
  border-radius: 6px;
  font-family: 'Poppins', sans-serif;
  font-size: 1rem;
  transition: border-color 0.3s;
}

.form-control:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(106, 13, 173, 0.1);
}

.btn {
  padding: 12px 20px;
  border: none;
  border-radius: 6px;
  font-family: 'Poppins', sans-serif;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-primary {
  background: var(--primary);
  color: white;
}

.btn-primary:hover {
  background: var(--primary-dark);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(106, 13, 173, 0.2);
}

/* Alerts */
.alert {
  padding: 15px 20px;
  border-radius: 6px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.alert-success {
  background: #e8f5e9;
  color: #2e7d32;
  border: 1px solid #c8e6c9;
}

.alert-danger {
  background: #ffebee;
  color: #c62828;
  border: 1px solid #ffcdd2;
}

.alert-info {
  background: #e3f2fd;
  color: #1565c0;
  border: 1px solid #bbdefb;
}

/* Responsive Design */
@media (max-width: 768px) {
  .container {
    padding: 0 15px;
  }
  
  .dashboard-header h1 {
    font-size: 1.7rem;
  }
  
  .stats-overview {
    grid-template-columns: 1fr;
  }
  
  .section {
    padding: 20px;
  }
  
  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
  
  .quick-actions-grid {
    grid-template-columns: 1fr;
  }
  
  .form-row {
    flex-direction: column;
    gap: 15px;
  }
  
  .chart-container {
    height: 250px;
  }
}

@media (max-width: 576px) {
  body {
    padding-top: 70px;
  }
  
  .dashboard-header h1 {
    font-size: 1.5rem;
  }
  
  .stat-card {
    padding: 15px;
  }
  
  .stat-card h3 {
    font-size: 1.5rem;
  }
  
  .section {
    padding: 15px;
  }
  
  .action-card {
    padding: 20px 15px;
  }
  
  .action-card i {
    font-size: 2rem;
  }
}
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container">
  <!-- Dashboard Header -->
  <div class="dashboard-header">
    <h1>Admin Dashboard</h1>
    <p>Welcome back! Here's what's happening today.</p>
  </div>

  <!-- Stats Overview -->
  <div class="stats-overview">
    <div class="stat-card">
      <div class="icon blue">
        <i class="fas fa-car"></i>
      </div>
      <h3><?php echo $totalVehicles; ?></h3>
      <p>Total Vehicles</p>
    </div>
    <div class="stat-card">
      <div class="icon green">
        <i class="fas fa-calendar-check"></i>
      </div>
      <h3><?php echo array_sum($weekData); ?></h3>
      <p>Bookings This Week</p>
    </div>
    <div class="stat-card">
      <div class="icon orange">
        <i class="fas fa-chart-line"></i>
      </div>
      <h3><?php echo array_sum($monthData); ?></h3>
      <p>Bookings This Month</p>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="section">
    <div class="section-header">
      <h2>Quick Actions</h2>
    </div>
    <div class="quick-actions-grid">
      <a href="add_vehicle.php" class="action-card">
        <i class="fas fa-plus-circle"></i>
        <h3>Add New Vehicle</h3>
        <p>Register a new vehicle</p>
      </a>
      <a href="existing_vehicles.php" class="action-card">
        <i class="fas fa-car"></i>
        <h3>Existing Vehicles</h3>
        <p>Manage all vehicles</p>
      </a>
      <a href="today_bookings.php" class="action-card">
        <i class="fas fa-calendar-day"></i>
        <h3>Today's Bookings</h3>
        <p>View today's reservations</p>
      </a>
      <a href="vehicle_waiting.php" class="action-card">
        <i class="fas fa-hourglass-half"></i>
        <h3>Vehicles in Waiting</h3>
        <p>Manage waiting list</p>
      </a>
    </div>
  </div>

  <?php if(isset($_GET['status'])): 
        $s = $_GET['status'];
        $class = 'alert-info'; $msg = 'Ready.';
        if($s==='added'){ $class='alert-success'; $msg='Vehicle marked as waiting.'; }
        elseif($s==='notfound'){ $class='alert-danger'; $msg='Vehicle not found.'; }
        elseif($s==='error'){ $class='alert-danger'; $msg='Failed to update vehicle.'; }
        elseif($s==='empty'){ $class='alert-info'; $msg='Please enter a number plate.'; }
  ?>
    <div class="alert <?php echo htmlspecialchars($class); ?>">
      <i class="fas fa-info-circle"></i>
      <span><?php echo htmlspecialchars($msg); ?></span>
    </div>
  <?php endif; ?>

  <!-- Add to Waiting Form -->
  <div class="section">
    <div class="section-header">
      <h2>Add Vehicle to Waiting List</h2>
    </div>
    <form action="add_to_waiting.php" method="POST" autocomplete="off">
      <div class="form-group">
        <label for="number_plate">
          <i class="fas fa-id-card"></i> Vehicle Number Plate
        </label>
        <div class="form-row">
          <input type="text" id="number_plate" name="number_plate" class="form-control" placeholder="e.g. KDA 123A" required>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add to Waiting
          </button>
        </div>
      </div>
    </form>
  </div>

  <!-- Analytics Section -->
  <div class="section">
    <div class="section-header">
      <h2>Booking Analytics</h2>
    </div>
    <div class="toggle-chart-view">
      <button id="btnWeek" class="toggle-btn active" onclick="showChart('week')">Weekly View</button>
      <button id="btnMonth" class="toggle-btn" onclick="showChart('month')">Monthly View</button>
    </div>
    <div class="chart-container">
      <canvas id="bookingChart"></canvas>
    </div>
  </div>
</div>

<footer>&copy; <?php echo date('Y'); ?> Southrift Services Limited | All Rights Reserved</footer>

<script>
const weekLabels  = <?php echo json_encode($weekLabels); ?>;
const weekData    = <?php echo json_encode($weekData); ?>;
const monthLabels = <?php echo json_encode(array_keys($monthData)); ?>;
const monthData   = <?php echo json_encode(array_values($monthData)); ?>;

const ctx = document.getElementById('bookingChart').getContext('2d');
let chart = new Chart(ctx,{
  type:'bar',
  data:{labels:weekLabels,datasets:[{
    label:'Bookings',
    data:weekData,
    backgroundColor:'rgba(106,13,173,0.7)',
    borderColor:'rgba(106,13,173,1)',
    borderWidth:1,
    borderRadius: 4
  }]},
  options:{
    responsive:true,
    maintainAspectRatio: false,
    scales:{
      y:{
        beginAtZero:true,
        grid: {
          color: 'rgba(0, 0, 0, 0.05)'
        }
      },
      x: {
        grid: {
          display: false
        }
      }
    },
    plugins:{
      legend:{display:false},
      tooltip: {
        backgroundColor: 'rgba(106, 13, 173, 0.9)',
        padding: 12
      }
    }
  }
});

function showChart(mode){
  const btnWeek = document.getElementById('btnWeek');
  const btnMonth = document.getElementById('btnMonth');
  
  btnWeek.classList.remove('active'); 
  btnMonth.classList.remove('active');
  
  if(mode==='week'){
    chart.data.labels = weekLabels;
    chart.data.datasets[0].data = weekData;
    chart.data.datasets[0].label='Weekly Bookings';
    btnWeek.classList.add('active');
  }else{
    chart.data.labels = monthLabels;
    chart.data.datasets[0].data = monthData;
    chart.data.datasets[0].label='Monthly Bookings';
    btnMonth.classList.add('active');
  }
  chart.update();
}
</script>
</body>
</html>