<?php
// Navbar for admin section
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav>
  <div class="logo">SouthRift Services</div>
  <div class="nav-right">
    <a href="index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">
      <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a href="existing_vehicles.php" class="<?= $current_page === 'existing_vehicles.php' ? 'active' : '' ?>">
      <i class="fas fa-car"></i> Vehicles
    </a>
    <a href="today_bookings.php" class="<?= $current_page === 'today_bookings.php' ? 'active' : '' ?>">
      <i class="fas fa-calendar-day"></i> Today's Bookings
    </a>
    <a href="vehicle_waiting.php" class="<?= $current_page === 'vehicle_waiting.php' ? 'active' : '' ?>">
      <i class="fas fa-clock"></i> Waiting List
    </a>
    <a href="logout.php">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </div>
</nav>
