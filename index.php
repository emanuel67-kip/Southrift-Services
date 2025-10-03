<?php
// Start session
session_start();

// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']);
$username = $loggedIn ? ($_SESSION['username'] ?? 'Passenger') : null;
$role = $loggedIn ? ($_SESSION['role'] ?? 'passenger') : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Southrift Services SACCO</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/responsive-framework.css">
  <style>
    :root {
      --purple: #6A0DAD;
      --purple-dark: #4e0b8a;
      --purple-light: #8a4bff;
      --accent: #00BFFF;
      --accent-dark: #008FC7;
      --light: #f8f9fa;
      --dark: #2c3e50;
      --success: #28a745;
      --warning: #ffc107;
      --info: #17a2b8;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(40px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes logoGlow {
      0% { text-shadow: 0 0 8px #fff, 0 0 12px #0ff; }
      100% { text-shadow: 0 0 12px #fff, 0 0 20px #f0f; }
    }

    @keyframes typing {
      from { width: 0 }
      to { width: 100% }
    }

    @keyframes float {
      0% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
      100% { transform: translateY(0px); }
    }

    body {
      background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                  url('images/webimages/nairobi1.jpg') center/cover no-repeat fixed;
      color: white;
      font-family: 'Poppins', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .hero {
      padding: 80px 20px;
      text-align: center;
      margin: 0 auto;
      max-width: 1200px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 80vh;
    }

    .hero-content {
      max-width: 800px;
    }

    .hero h1 {
      font-size: 3.5rem;
      font-weight: 700;
      margin-bottom: 20px;
      overflow: hidden;
      white-space: nowrap;
      width: 0;
      margin: 0 auto 20px;
      animation: typing 4s steps(40, end) forwards,
                 fadeIn 1s ease-out;
      background: linear-gradient(to right, #00f0ff, #00ff88, #ff00cc);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .highlight-line {
      background: rgba(106, 13, 173, 0.8);
      backdrop-filter: blur(10px);
      color: white;
      font-size: 1.3rem;
      font-weight: 500;
      padding: 15px 25px;
      border-radius: 50px;
      display: inline-block;
      box-shadow: 0 8px 16px rgba(0,0,0,0.3);
      text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.5);
      margin: 20px 0;
      border: 1px solid rgba(255,255,255,0.1);
      animation: fadeInUp 1s ease-out 0.5s both;
    }

    .action-tabs {
      display: flex;
      justify-content: center;
      gap: 25px;
      flex-wrap: wrap;
      margin-top: 40px;
      padding: 20px;
      animation: fadeInUp 1s ease-out 1s both;
    }

    .tab {
      background: linear-gradient(135deg, var(--purple), var(--purple-dark));
      color: white;
      text-align: center;
      padding: 30px 25px;
      border-radius: 16px;
      font-size: 1.1rem;
      font-weight: 600;
      text-decoration: none;
      width: 260px;
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
      transition: all 0.4s ease;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 15px;
      border: 1px solid rgba(255,255,255,0.1);
      position: relative;
      overflow: hidden;
    }

    .tab::before {
      content: '';
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0));
      transform: translateX(-100%);
      transition: transform 0.6s ease;
    }

    .tab:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
    }

    .tab:hover::before {
      transform: translateX(100%);
    }

    .tab-icon {
      font-size: 2.5rem;
      margin-bottom: 10px;
    }

    /* Welcome message for logged-in users */
    .welcome-banner {
      background: rgba(106, 13, 173, 0.9);
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 30px;
      text-align: center;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
      animation: fadeInUp 0.8s ease-out;
    }

    .welcome-banner h2 {
      margin: 0 0 10px 0;
      font-size: 2rem;
    }

    .welcome-banner p {
      margin: 0;
      font-size: 1.1rem;
      opacity: 0.9;
    }

    /* Features Section */
    .features {
      background: rgba(255, 255, 255, 0.95);
      padding: 80px 20px;
      color: var(--dark);
      text-align: center;
    }

    .section-title {
      color: var(--purple);
      font-size: 2.5rem;
      margin-bottom: 50px;
      position: relative;
      display: inline-block;
    }

    .section-title::after {
      content: '';
      position: absolute;
      bottom: -15px;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 4px;
      background: linear-gradient(to right, var(--purple), var(--accent));
      border-radius: 2px;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 30px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .feature-card {
      background: white;
      border-radius: 16px;
      padding: 40px 30px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
      border: 1px solid rgba(0,0,0,0.05);
    }

    .feature-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
    }

    .feature-icon {
      font-size: 3rem;
      margin-bottom: 20px;
      color: var(--purple);
    }

    .feature-card h3 {
      color: var(--purple);
      margin-bottom: 15px;
      font-size: 1.5rem;
    }

    .feature-card p {
      color: #666;
      line-height: 1.7;
    }

    /* Stats Section */
    .stats {
      background: linear-gradient(135deg, var(--purple), var(--purple-dark));
      padding: 60px 20px;
      text-align: center;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 30px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .stat-item {
      padding: 20px;
    }

    .stat-number {
      font-size: 3rem;
      font-weight: 700;
      margin-bottom: 10px;
      color: white;
    }

    .stat-label {
      font-size: 1.1rem;
      color: rgba(255, 255, 255, 0.8);
      font-weight: 500;
    }

    /* CTA Section */
    .cta {
      background: rgba(255, 255, 255, 0.95);
      padding: 80px 20px;
      text-align: center;
      color: var(--dark);
    }

    .cta h2 {
      color: var(--purple);
      font-size: 2.5rem;
      margin-bottom: 20px;
    }

    .cta p {
      font-size: 1.2rem;
      max-width: 700px;
      margin: 0 auto 40px;
      color: #666;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .hero h1 {
        font-size: 2.5rem;
      }
      
      .highlight-line {
        font-size: 1.1rem;
        padding: 12px 20px;
      }
      
      .action-tabs {
        gap: 15px;
      }
      
      .tab {
        width: 220px;
        padding: 25px 20px;
      }
      
      .section-title {
        font-size: 2rem;
      }
      
      .stat-number {
        font-size: 2.5rem;
      }
    }

    @media (max-width: 576px) {
      .hero {
        padding: 60px 15px;
      }
      
      .hero h1 {
        font-size: 2rem;
      }
      
      .highlight-line {
        font-size: 1rem;
        padding: 10px 15px;
      }
      
      .action-tabs {
        flex-direction: column;
        align-items: center;
      }
      
      .tab {
        width: 100%;
        max-width: 300px;
      }
      
      .features, .stats, .cta {
        padding: 60px 15px;
      }
    }
  </style>
</head>
<body>
  <header>
    <div class="logo">Southrift Services Limited</div>
    <nav>
      <a href="index.php" class="active">Home</a>
      <a href="fleet.html">Fleet</a>
      <a href="join.html">Join</a>
      <a href="contact.html">Contact</a>
      <a href="about.html">About</a>
      <?php if ($loggedIn): ?>
        <?php if ($role === 'admin'): ?>
          <a href="Admin/index.php">Admin Panel</a>
        <?php elseif ($role === 'driver'): ?>
          <a href="Driver/index.php">Driver Panel</a>
        <?php else: ?>
          <a href="passenger_profile.php">My Profile</a>
        <?php endif; ?>
      <?php else: ?>
        <a href="login.html">Login</a>
      <?php endif; ?>
    </nav>
  </header>

  <div class="hero">
    <div class="hero-content">
      <?php if ($loggedIn): ?>
        <div class="welcome-banner">
          <h2>Welcome back, <?php echo htmlspecialchars($username); ?>!</h2>
          <p>We're glad to see you again. Ready to book your next journey?</p>
        </div>
      <?php endif; ?>
      
      <h1>Southrift Services SACCO</h1>
      <div class="highlight-line">
        Reliable, Comfortable, and Affordable Transportation Solutions
      </div>
      
      <div class="action-tabs">
        <a href="booking.html" class="tab">
          <div class="tab-icon">🎫</div>
          <div>Book a Seat</div>
        </a>
        <a href="track_my_driver.php" class="tab">
          <div class="tab-icon">📍</div>
          <div>Track My Ride</div>
        </a>
        <a href="routes.html" class="tab">
          <div class="tab-icon">🗺️</div>
          <div>View Routes</div>
        </a>
      </div>
    </div>
  </div>

  <div class="features">
    <h2 class="section-title">Why Choose Us</h2>
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">⏱️</div>
        <h3>On-Time Service</h3>
        <p>Punctuality is our priority. We ensure all our vehicles depart and arrive on schedule.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🛡️</div>
        <h3>Safety First</h3>
        <p>Our vehicles undergo regular maintenance and our drivers are professionally trained.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">💰</div>
        <h3>Affordable Pricing</h3>
        <p>Competitive rates without compromising on comfort and service quality.</p>
      </div>
    </div>
  </div>

  <div class="stats">
    <div class="stats-grid">
      <div class="stat-item">
        <div class="stat-number">50+</div>
        <div class="stat-label">Vehicles</div>
      </div>
      <div class="stat-item">
        <div class="stat-number">1000+</div>
        <div class="stat-label">Daily Passengers</div>
      </div>
      <div class="stat-item">
        <div class="stat-number">15+</div>
        <div class="stat-label">Routes</div>
      </div>
      <div class="stat-item">
        <div class="stat-number">50+</div>
        <div class="stat-label">Professional Drivers</div>
      </div>
    </div>
  </div>

  <div class="cta">
    <h2>Ready to Experience Quality Transport?</h2>
    <p>Join thousands of satisfied passengers who trust Southrift Services for their daily transportation needs.</p>
    <a href="booking.html" class="btn" style="display: inline-block; padding: 15px 40px; font-size: 1.2rem;">Book Your Ride Now</a>
  </div>

  <footer>
    <p>&copy; 2025 Southrift Services Limited. All rights reserved.</p>
  </footer>
</body>
</html>