<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header('Location: login.html');
    exit;
}

// Set the content type
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Profile - Southrift Services Limited</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/responsive-framework.css">
  <style>
    :root {
      --purple: #6A0DAD;
      --purple-dark: #4e0b8a;
      --accent: #00BFFF;
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

    body {
      background: #f5f5f5;
      color: #333;
      font-family: 'Poppins', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .hero-banner {
      background: linear-gradient(135deg, var(--purple), var(--purple-dark));
      color: white;
      padding: 3rem 1rem;
      text-align: center;
      margin-bottom: 2rem;
    }

    .hero-banner h1 {
      font-size: 2.5rem;
      margin-bottom: 1rem;
      font-weight: 600;
    }

    .hero-banner p {
      font-size: 1.2rem;
      max-width: 700px;
      margin: 0 auto;
      opacity: 0.9;
    }

    .profile-header {
      display: flex;
      align-items: center;
      gap: 2rem;
      margin-bottom: 2rem;
      flex-wrap: wrap;
    }

    .profile-avatar {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--purple), var(--accent));
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 3rem;
      color: white;
      border: 4px solid white;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .profile-info h2 {
      font-size: 2rem;
      color: var(--purple);
      margin-bottom: 0.5rem;
    }

    .profile-info p {
      margin: 0.5rem 0;
      font-size: 1.1rem;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin: 2rem 0;
    }

    .stat-card {
      background: white;
      border-radius: 12px;
      padding: 1.5rem;
      text-align: center;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      border: 1px solid rgba(0,0,0,0.05);
      transition: transform 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--purple);
      margin-bottom: 0.5rem;
    }

    .stat-label {
      font-size: 1rem;
      color: #666;
      font-weight: 500;
    }

    .quick-actions {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
      margin: 2rem 0;
    }

    .action-card {
      background: white;
      border-radius: 12px;
      padding: 1.5rem;
      text-align: center;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      border: 1px solid rgba(0,0,0,0.05);
      transition: all 0.3s ease;
      text-decoration: none;
      color: inherit;
    }

    .action-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(106, 13, 173, 0.15);
      border-color: rgba(106, 13, 173, 0.2);
    }

    .action-icon {
      font-size: 2.5rem;
      margin-bottom: 1rem;
      color: var(--purple);
    }

    .action-card h3 {
      color: var(--purple);
      margin-bottom: 0.5rem;
      font-size: 1.3rem;
    }

    .action-card p {
      color: #666;
      font-size: 0.95rem;
    }

    .booking-history {
      margin-top: 2rem;
    }

    .booking-card {
      background: white;
      border-radius: 12px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      border: 1px solid rgba(0,0,0,0.05);
      display: flex;
      flex-wrap: wrap;
      gap: 1.5rem;
      align-items: center;
    }

    .booking-info {
      flex: 1;
      min-width: 250px;
    }

    .booking-info h3 {
      color: var(--purple);
      margin-bottom: 0.5rem;
    }

    .booking-info p {
      margin: 0.3rem 0;
      color: #555;
    }

    .booking-status {
      padding: 0.5rem 1rem;
      border-radius: 50px;
      font-weight: 600;
      font-size: 0.9rem;
    }

    .status-confirmed {
      background: rgba(40, 167, 69, 0.1);
      color: #28a745;
    }

    .status-completed {
      background: rgba(255, 193, 7, 0.1);
      color: #ffc107;
    }

    .status-upcoming {
      background: rgba(13, 110, 253, 0.1);
      color: #0d6efd;
    }

    .booking-actions {
      display: flex;
      gap: 1rem;
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
      align-items: center;
      justify-content: center;
    }

    .modal-content {
      background: white;
      border-radius: 16px;
      width: 90%;
      max-width: 600px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.3);
      position: relative;
      animation: fadeInUp 0.4s ease-out;
      max-height: 90vh;
      overflow-y: auto;
    }

    .modal-header {
      padding: 1.5rem;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .modal-header h2 {
      color: var(--purple);
      margin: 0;
      font-size: 1.5rem;
    }

    .close-btn {
      font-size: 2rem;
      font-weight: bold;
      color: #aaa;
      cursor: pointer;
      background: none;
      border: none;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      transition: all 0.3s ease;
    }

    .close-btn:hover {
      background: #f5f5f5;
      color: #333;
    }

    .modal-body {
      padding: 1.5rem;
    }

    .detail-item {
      display: flex;
      justify-content: space-between;
      padding: 0.8rem 0;
      border-bottom: 1px solid #f5f5f5;
    }

    .detail-item:last-child {
      border-bottom: none;
    }

    .detail-label {
      font-weight: 600;
      color: #666;
    }

    .detail-value {
      font-weight: 500;
      color: #333;
      text-align: right;
    }

    .no-bookings {
      text-align: center;
      padding: 2rem;
      color: #666;
      font-style: italic;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .hero-banner {
        padding: 2rem 1rem;
      }
      
      .hero-banner h1 {
        font-size: 2rem;
      }
      
      .hero-banner p {
        font-size: 1rem;
      }
      
      .profile-header {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
      }
      
      .profile-info h2 {
        font-size: 1.8rem;
      }
      
      .stats-grid,
      .quick-actions {
        grid-template-columns: 1fr;
      }
      
      .booking-card {
        flex-direction: column;
        text-align: center;
      }
      
      .booking-actions {
        justify-content: center;
      }
    }

    @media (max-width: 576px) {
      .hero-banner {
        padding: 1.5rem 0.75rem;
      }
      
      .hero-banner h1 {
        font-size: 1.8rem;
      }
      
      .profile-avatar {
        width: 100px;
        height: 100px;
        font-size: 2.5rem;
      }
      
      .booking-actions {
        flex-direction: column;
        width: 100%;
      }
      
      .btn {
        width: 100%;
      }
      
      .detail-item {
        flex-direction: column;
        gap: 0.3rem;
      }
      
      .detail-value {
        text-align: left;
      }
    }

    @media (max-width: 480px) {
      .hero-banner h1 {
        font-size: 1.6rem;
      }
      
      .profile-info h2 {
        font-size: 1.6rem;
      }
      
      .stat-number {
        font-size: 2rem;
      }
    }
  </style>
</head>
<body>
  <header>
    <div class="logo">Southrift Services Limited</div>
    <nav>
      <a href="index.html">Home</a>
      <a href="fleet.html">Fleet</a>
      <a href="join.html">Join</a>
      <a href="contact.html">Contact</a>
      <a href="about.html">About</a>
      <a href="profile.html" class="active">Profile</a>
    </nav>
  </header>

  <div class="hero-banner">
    <h1>My Profile</h1>
    <p>Manage your account, bookings, and preferences all in one place</p>
  </div>

  <div class="container">
    <!-- Profile Header -->
    <div class="section">
      <div class="profile-header">
        <div class="profile-avatar">👤</div>
        <div class="profile-info">
          <h2 id="user-name">Loading...</h2>
          <p><strong>Member Since:</strong> <span id="member-since">-</span></p>
          <p><strong>Email:</strong> <span id="user-email">-</span></p>
          <p><strong>Phone:</strong> <span id="user-phone">-</span></p>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-number" id="total-bookings">0</div>
          <div class="stat-label">Total Bookings</div>
        </div>
        <div class="stat-card">
          <div class="stat-number" id="completed-rides">0</div>
          <div class="stat-label">Completed Rides</div>
        </div>
        <div class="stat-card">
          <div class="stat-number" id="upcoming-rides">0</div>
          <div class="stat-label">Upcoming Rides</div>
        </div>
        <div class="stat-card">
          <div class="stat-number" id="avg-rating">0.0</div>
          <div class="stat-label">Avg. Rating</div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="section">
      <h2 class="section-title">Quick Actions</h2>
      <div class="quick-actions">
        <a href="track_my_driver.php" class="action-card">
          <div class="action-icon">📍</div>
          <h3>Track My Ride</h3>
          <p>Real-time location tracking</p>
        </a>
        <a href="booking.html" class="action-card">
          <div class="action-icon">🎫</div>
          <h3>Book a Seat</h3>
          <p>Reserve your next journey</p>
        </a>
        <a href="routes.html" class="action-card">
          <div class="action-icon">🗺️</div>
          <h3>View Routes</h3>
          <p>Explore our destinations</p>
        </a>
      </div>
    </div>

    <!-- Booking History -->
    <div class="section booking-history">
      <h2 class="section-title">My Bookings</h2>
      <div id="bookings-container">
        <div class="no-bookings">Loading bookings...</div>
      </div>
    </div>

    <div class="text-center mt-2">
      <a href="logout.php" class="btn btn-secondary">Logout</a>
    </div>
  </div>

  <!-- Modal for Booking Details -->
  <div id="bookingModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Booking Details</h2>
        <button class="close-btn">&times;</button>
      </div>
      <div class="modal-body" id="modalContent">
        <!-- Booking details will be loaded here -->
      </div>
    </div>
  </div>

  <footer>
    <p>&copy; 2025 Southrift Services Limited. All rights reserved.</p>
  </footer>

  <script>
    // Modal functionality
    const modal = document.getElementById("bookingModal");
    const closeBtn = document.querySelector(".close-btn");

    closeBtn.onclick = function() {
      modal.style.display = "none";
    }

    window.onclick = function(event) {
      if (event.target == modal) {
        modal.style.display = "none";
      }
    }

    // Fetch user profile data
    function fetchUserProfile() {
      fetch('profile.php')
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Update user info
            document.getElementById('user-name').textContent = data.user.name;
            document.getElementById('user-email').textContent = data.user.email;
            document.getElementById('user-phone').textContent = data.user.phone;
            
            // Format member since date
            const createdAt = new Date(data.user.created_at);
            document.getElementById('member-since').textContent = createdAt.toLocaleDateString('en-US', {
              year: 'numeric',
              month: 'long',
              day: 'numeric'
            });
            
            // Update stats
            document.getElementById('total-bookings').textContent = data.bookings.length;
            
            // Calculate ride stats
            let completedRides = 0;
            let upcomingRides = 0;
            
            data.bookings.forEach(booking => {
              const travelDate = new Date(booking.travel_date);
              const today = new Date();
              
              if (travelDate < today) {
                completedRides++;
              } else {
                upcomingRides++;
              }
            });
            
            document.getElementById('completed-rides').textContent = completedRides;
            document.getElementById('upcoming-rides').textContent = upcomingRides;
            
            // Display bookings
            displayBookings(data.bookings);
          } else {
            console.error('Error fetching profile:', data.error);
            document.getElementById('user-name').textContent = 'Error loading profile';
          }
        })
        .catch(error => {
          console.error('Error:', error);
          document.getElementById('user-name').textContent = 'Error loading profile';
        });
    }

    // Display bookings in the UI
    function displayBookings(bookings) {
      const container = document.getElementById('bookings-container');
      
      if (!bookings || bookings.length === 0) {
        container.innerHTML = '<div class="no-bookings">You have no bookings yet. <a href="booking.html">Book a seat now</a></div>';
        return;
      }
      
      let bookingsHTML = '';
      
      bookings.forEach(booking => {
        // Format travel date
        const travelDate = new Date(booking.travel_date);
        const formattedDate = travelDate.toLocaleDateString('en-US', {
          month: 'short',
          day: 'numeric',
          year: 'numeric'
        });
        
        // Determine status
        const today = new Date();
        let statusText = 'Upcoming';
        let statusClass = 'status-upcoming';
        
        if (travelDate < today) {
          statusText = 'Completed';
          statusClass = 'status-completed';
        } else if (travelDate.toDateString() === today.toDateString()) {
          statusText = 'Today';
          statusClass = 'status-confirmed';
        }
        
        bookingsHTML += `
          <div class="booking-card">
            <div class="booking-info">
              <h3>${booking.route || 'Route not specified'}</h3>
              <p><strong>Booking ID:</strong> #${booking.booking_id}</p>
              <p><strong>Date:</strong> ${formattedDate}</p>
              <p><strong>Time:</strong> ${booking.departure_time || 'Not specified'}</p>
              <p><strong>Seats:</strong> ${booking.seats || 1}</p>
            </div>
            <div>
              <span class="booking-status ${booking.status_class || statusClass}">${statusText}</span>
            </div>
            <div class="booking-actions">
              <button class="btn" onclick="viewDetails(${booking.booking_id})">View Details</button>
            </div>
          </div>
        `;
      });
      
      container.innerHTML = bookingsHTML;
    }

    // View booking details
    function viewDetails(bookingId) {
      // Fetch detailed booking information
      fetch(`get_booking_details.php?booking_id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success)