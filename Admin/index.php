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
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root{--purple:#6A0DAD;--purple-dark:#4e0b8a;--bg:#f4f4f4}
html{animation:fadeIn .7s ease-in-out}@keyframes fadeIn{from{opacity:0;transform:translateY(20px)}to{opacity:1}}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Poppins,sans-serif;background:var(--bg);display: flex;min-height: 100vh;flex-direction: column}
nav{background:var(--purple);padding:1rem 2rem;color:#fff;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap}
.logo{font-size:1.5rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;animation:logoGlow 2s ease-in-out infinite alternate}
@keyframes logoGlow{0%{text-shadow:0 0 8px #fff,0 0 12px #0ff;}100%{text-shadow:0 0 12px #fff,0 0 20px #f0f}}
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

main{max-width:1100px;margin:40px auto;padding:20px;flex: 1;padding-bottom: 80px; /* Add padding to prevent content from being hidden behind fixed footer */}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:24px}
.card{background:var(--purple);color:#fff;border-radius:14px;padding:40px 20px;text-align:center;box-shadow:0 8px 18px rgba(0,0,0,.15);transition:.2s}
.card:hover{transform:translateY(-6px);box-shadow:0 12px 24px rgba(0,0,0,.25)}
.card{text-decoration: none !important;}
.card:hover{background:linear-gradient(to right,#6A0DAD,#b980ff);transform:translateY(-6px) scale(1.03);box-shadow:0 14px 28px rgba(0,0,0,0.25)}
.card h2{font-size:2.5rem;margin:0 0 12px}

/* Chart section only (changed) */
h3.chart-title{text-align:center;margin-top:60px;color:var(--purple)}
.toggle-buttons{text-align:center;margin:10px 0 20px}
.toggle-buttons button{background:var(--purple);border:none;color:#fff;padding:10px 18px;margin:0 8px;border-radius:6px;font-weight:600;cursor:pointer;transition:background .3s}
.toggle-buttons button.active,.toggle-buttons button:hover{background:var(--purple-dark)}
.chart-container{background:#eee;padding:30px 20px;border-radius:12px;margin-top:20px}
canvas{background:#fff;padding:20px;border-radius:10px;box-shadow:0 4px 10px rgba(0,0,0,.1)}

/* Add-to-waiting form */
.waiting-form{margin-top:10px;background:#fff;padding:16px;border-radius:10px;box-shadow:0 4px 10px rgba(0,0,0,.08)}
.waiting-form label{display:block;font-weight:600;margin-bottom:8px;color:var(--purple)}
.waiting-form .row{display:flex;gap:12px}
.waiting-form input{flex:1;padding:12px 14px;border-radius:8px;border:1px solid #ddd}
.waiting-form button{background:var(--purple);border:none;color:#fff;padding:12px 16px;border-radius:8px;font-weight:600;cursor:pointer}
.waiting-form button:hover{background:var(--purple-dark)}
@media(max-width:600px){.waiting-form .row{flex-direction:column}}

/* Alerts */
.alert{margin:12px 0;padding:10px 12px;border-radius:8px}
.alert.success{background:#e7f9ef;color:#0f7b3f;border:1px solid #bcebd2}
.alert.error{background:#fdecea;color:#b00020;border:1px solid #f5c2c7}
.alert.info{background:#e7f0ff;color:#0b4db3;border:1px solid #c6d6ff}

footer{background:var(--purple);color:#fff;text-align:center;padding:1rem;position: fixed;bottom: 0;width: 100%;flex-shrink: 0}

/* Responsive Design */
@media (max-width: 1200px) {
  main {
    max-width: 95%;
    margin: 30px auto;
    padding: 15px;
    padding-bottom: 80px;
  }
}

@media (max-width: 992px) {
  main {
    max-width: 90%;
    margin: 25px auto;
    padding: 12px;
    padding-bottom: 100px; /* Increase padding for mobile */
  }
}

@media (max-width: 768px) {
  nav {
    padding: 1rem;
    flex-direction: column;
    align-items: flex-start;
  }
  
  .nav-right {
    margin-top: 15px;
    width: 100%;
    flex-wrap: wrap;
    gap: 10px;
  }
  
  .nav-right a {
    padding: 6px 8px;
    font-size: 0.9rem;
  }
  
  main {
    max-width: 100%;
    margin: 20px auto;
    padding: 10px;
    padding-bottom: 120px; /* Increase padding for small mobile */
  }
  
  footer {
    padding: 0.8rem;
  }
  
  footer p {
    font-size: 0.9rem;
  }
}

@media (max-width: 576px) {
  nav {
    padding: 0.8rem 0.5rem;
  }
  
  .logo {
    font-size: 1.3rem;
  }
  
  .nav-right {
    gap: 8px;
    margin-top: 10px;
  }
  
  .nav-right a {
    padding: 5px 6px;
    font-size: 0.8rem;
  }
  
  main {
    padding: 8px;
    margin: 15px auto;
    padding-bottom: 130px; /* Increase padding for very small mobile */
  }
}

@media (max-width: 480px) {
  nav {
    padding: 0.7rem 0.4rem;
  }
  
  .logo {
    font-size: 1.2rem;
  }
  
  .nav-right a {
    font-size: 0.75rem;
  }
  
  main {
    padding: 6px;
    margin: 12px auto;
    padding-bottom: 140px; /* Increase padding for extra small devices */
  }
}

@media (max-width: 360px) {
  nav {
    padding: 0.6rem 0.3rem;
  }
  
  .logo {
    font-size: 1.1rem;
  }
  
  .nav-right a {
    font-size: 0.7rem;
  }
  
  main {
    padding: 5px;
    margin: 10px auto;
    padding-bottom: 150px; /* Increase padding for extra small devices */
  }
}
</style>
</head>
<body>

<!-- Navbar -->
<nav>
  <div class="logo">Southrift Services Limited</div>
  <div class="nav-right">
    <a href="index.php"><i class="fa fa-home"></i> Dashboard</a>
    <a href="#"><i class="fa fa-user-shield"></i> Super Admin</a>
    <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
  </div>
</nav>

<main>
  <!-- Quick cards -->
  <div class="grid">
    <a class="card" href="add_vehicle.php"><h2><i class="fa fa-plus-circle"></i></h2><p>Add New Vehicle</p></a>
    <a class="card" href="existing_vehicles.php"><h2><i class="fa fa-bus"></i></h2><p>Existing Vehicles</p></a>
    <a class="card" href="today_bookings.php"><h2><i class="fa fa-calendar-day"></i></h2><p>Today's Bookings</p></a>
    <a class="card" href="vehicle_waiting.php"><h2><i class="fa fa-hourglass-half"></i></h2><p>Vehicles in Waiting</p></a>
    <a class="card" href="manage_admin_stations.php"><h2><i class="fa fa-map-marker-alt"></i></h2><p>Manage Stations</p></a>
  </div>

  <?php if(isset($_GET['status'])): 
        $s = $_GET['status'];
        $class = 'info'; $msg = 'Ready.';
        if($s==='added'){ $class='success'; $msg='Vehicle marked as waiting.'; }
        elseif($s==='notfound'){ $class='error'; $msg='Vehicle not found.'; }
        elseif($s==='error'){ $class='error'; $msg='Failed to update vehicle.'; }
        elseif($s==='empty'){ $class='info'; $msg='Please enter a number plate.'; }
  ?>
    <div class="alert <?=htmlspecialchars($class)?>"><?=htmlspecialchars($msg)?></div>
  <?php endif; ?>

  <form action="add_to_waiting.php" method="POST" class="waiting-form" autocomplete="off" id="add-to-waiting">
    <label for="number_plate"><i class="fa fa-hourglass-half"></i> Add vehicle to waiting (enter number plate)</label>
    <div class="row">
      <input type="text" id="number_plate" name="number_plate" placeholder="e.g. KDA 123A" required>
      <button type="submit"><i class="fa fa-plus"></i> Add to Waiting</button>
    </div>
  </form>

  <!-- Chart -->
  <h3 class="chart-title">📊 Booking Chart</h3>
  <div class="toggle-buttons">
    <button id="btnWeek" class="active" onclick="showChart('week')">Weekly View</button>
    <button id="btnMonth" onclick="showChart('month')">Monthly View</button>
  </div>
  <div class="chart-container">
    <canvas id="bookingChart" height="160"></canvas>
  </div>
</main>

<footer>&copy; <?=date('Y')?> Southrift Services Limited | All Rights Reserved</footer>

<script>
const weekLabels  = <?=json_encode($weekLabels)?>;
const weekData    = <?=json_encode($weekData)?>;
const monthLabels = <?=json_encode(array_keys($monthData))?>;
const monthData   = <?=json_encode(array_values($monthData))?>;

const ctx = document.getElementById('bookingChart').getContext('2d');
let chart = new Chart(ctx,{
  type:'bar',
  data:{labels:weekLabels,datasets:[{
    label:'Weekly Bookings',
    data:weekData,
    backgroundColor:'rgba(106,13,173,.3)',
    borderColor:'#6A0DAD',
    borderWidth:2,
    borderRadius:6,
    barThickness:40
  }]},
  options:{
    responsive:true,
    scales:{
      y:{
        beginAtZero:true,
        min:0,
        max:50,
        ticks:{precision:0}
      }
    },
    plugins:{legend:{display:false}}
  }
});

function showChart(mode){
  btnWeek.classList.remove('active'); btnMonth.classList.remove('active');
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
const btnWeek  = document.getElementById('btnWeek');
const btnMonth = document.getElementById('btnMonth');
</script>
</body>
</html>
