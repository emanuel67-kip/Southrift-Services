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
      --purple-light: #8A2BE2;
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
    
    @keyframes sparkle {
      0% { opacity: 0; transform: scale(0); }
      50% { opacity: 1; transform: scale(1); }
      100% { opacity: 0; transform: scale(0); }
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* Header Styles */
    header {
      background-color: var(--purple);
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      color: white;
      flex-shrink: 0;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);

      /* 👇 Fix header at top */
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 1000;
    }

    /* 👇 Prevent content from hiding under header */
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #e4edf9 100%);
      color: #333;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      overflow-x: hidden;

      padding-top: 80px; /* adjust to match header height */
    }

    .hero-banner {
      background: linear-gradient(135deg, var(--purple), var(--purple-light));
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
      background-color: rgba(0,0,0,0.7);
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(5px);
      animation: fadeIn 0.3s ease-out;
    }

    .modal-content {
      background: linear-gradient(135deg, #ffffff, #f8f9fa);
      border-radius: 20px;
      width: 90%;
      max-width: 600px;
      box-shadow: 0 25px 50px rgba(0,0,0,0.4);
      position: relative;
      animation: modalPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      max-height: 95vh;
      overflow: hidden;
      border: 1px solid rgba(106, 13, 173, 0.2);
      transform: scale(0.9);
    }

    @keyframes modalPop {
      0% { transform: scale(0.8); opacity: 0; }
      100% { transform: scale(1); opacity: 1; }
    }

    .modal-header {
      padding: 1.5rem;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: linear-gradient(135deg, var(--purple), var(--purple-dark));
      color: white;
      border-radius: 19px 19px 0 0;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .modal-header h2 {
      color: white;
      margin: 0;
      font-size: 1.8rem;
      font-weight: 600;
      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
      letter-spacing: 0.5px;
    }

    .close-btn {
      font-size: 2.5rem;
      font-weight: bold;
      color: #aaa;
      cursor: pointer;
      background: none;
      border: none;
      width: 45px;
      height: 45px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      transition: all 0.3s ease;
    }

    .close-btn:hover {
      background: #f5f5f5;
      color: #333;
      transform: scale(1.1) rotate(90deg);
    }

    .modal-body {
      padding: 1.5rem;
      background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
      border-radius: 0 0 19px 19px;
      max-height: 80vh;
      overflow-y: auto;
      overflow-x: hidden;
    }

    .detail-item {
      display: flex;
      justify-content: space-between;
      padding: 1.2rem 0;
      border-bottom: 1px dashed #d0d0d0;
      align-items: center;
      transition: all 0.3s ease;
      position: relative;
    }

    .detail-item:hover {
      background: linear-gradient(to right, rgba(106, 13, 173, 0.05), transparent);
      padding-left: 1rem;
      padding-right: 1rem;
      border-radius: 10px;
      border-bottom: 1px solid var(--purple);
    }

    .detail-item:last-child {
      border-bottom: none;
    }

    .detail-label {
      font-weight: 600;
      color: #666;
      font-size: 1.1rem;
      position: relative;
      padding-left: 20px;
    }

    .detail-label::before {
      content: "•";
      color: var(--purple);
      position: absolute;
      left: 0;
      font-size: 1.5rem;
      top: 50%;
      transform: translateY(-50%);
    }

    .detail-value {
      font-weight: 500;
      color: #333;
      text-align: right;
      padding: 0.7rem 1rem;
      border-radius: 10px;
      background: white;
      box-shadow: 0 4px 8px rgba(0,0,0,0.08);
      border: 1px solid #eee;
      transition: all 0.3s ease;
    }

    .detail-value:hover {
      box-shadow: 0 6px 12px rgba(0,0,0,0.12);
      transform: translateY(-2px);
    }

    /* Vehicle Info Styles */
    .vehicle-info {
      background: linear-gradient(135deg, rgba(0, 191, 255, 0.2), rgba(0, 150, 200, 0.2));
      border-radius: 15px;
      padding: 1.5rem;
      margin: 1.5rem 0;
      border-left: 5px solid var(--accent);
      box-shadow: 0 8px 16px rgba(0,0,0,0.08);
      position: relative;
      overflow: hidden;
    }

    .vehicle-info::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, var(--accent), var(--purple));
    }

    /* Color coding for specific values */
    .driver-name {
      color: var(--purple);
      font-weight: 700;
      font-size: 1.3rem;
      text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .driver-phone {
      color: #28a745;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      display: inline-block;
    }

    .driver-phone:hover {
      color: #1e7e34;
      transform: scale(1.03);
      text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .driver-phone::after {
      content: "📱";
      margin-left: 5px;
      font-size: 0.9rem;
    }

    .vehicle-plate {
      color: #dc3545;
      font-weight: 700;
      font-size: 1.4rem;
      text-align: center;
      display: block;
      padding: 1rem;
      background: linear-gradient(135deg, rgba(220, 53, 69, 0.15), rgba(200, 40, 50, 0.2));
      border-radius: 12px;
      margin: 0.7rem 0;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      border: 2px dashed #dc3545;
      letter-spacing: 2px;
      text-transform: uppercase;
    }

    .vehicle-type {
      color: #007bff;
      font-weight: 600;
      font-size: 1.1rem;
    }

    .vehicle-color {
      color: #fd7e14;
      font-weight: 600;
      font-size: 1.1rem;
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
      
      /* Table responsive adjustments */
      th, td {
        padding: 0.75rem;
        font-size: 0.9rem;
      }
      
      /* Adjust padding for fixed header on mobile */
      body {
        padding-top: 80px;
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
      
      /* Table responsive adjustments */
      th, td {
        padding: 0.6rem;
        font-size: 0.85rem;
      }
      
      .table-responsive {
        font-size: 0.9rem;
      }
      
      /* Adjust padding for fixed header on small mobile */
      body {
        padding-top: 80px;
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
      
      /* Table responsive adjustments */
      th, td {
        padding: 0.5rem;
        font-size: 0.8rem;
      }
      
      .table-responsive {
        font-size: 0.85rem;
      }
      
      /* Adjust padding for fixed header on extra small devices */
      body {
        padding-top: 80px;
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
      <a href="passenger_profile.php" class="active">Profile</a>
    </nav>
  </header>

  <div class="hero-banner">
    <h1>My Profile</h1>
    <p>Manage your account, bookings, and preferences all in one place</p>
  </div>

  <main>
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
    </div>
  </main>

  <!-- Logout Button - Moved outside container but before footer -->
  <div class="text-center" style="padding: 20px; background: #f8f9fa; margin: 20px auto; max-width: 1200px;">
    <a href="passenger_logout.php" class="btn btn-secondary">Logout</a>
  </div>

  <!-- Modal for Booking Details -->
  <div id="bookingModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>My Ride</h2>
        <button class="close-btn" onclick="document.getElementById('bookingModal').style.display='none'">&times;</button>
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
            
            // Update Track My Ride link based on today's bookings
            updateTrackRideLink(data.bookings);
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

    // Check if there's a booking for today and update the Track My Ride link
    function updateTrackRideLink(bookings) {
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      
      let hasTodayBooking = false;
      
      if (bookings && bookings.length > 0) {
        for (const booking of bookings) {
          if (booking.travel_date) {
            const travelDate = new Date(booking.travel_date);
            travelDate.setHours(0, 0, 0, 0);
            
            if (travelDate.getTime() === today.getTime()) {
              hasTodayBooking = true;
              break;
            }
          }
        }
      }
      
      // Update the Track My Ride link
      const trackRideLink = document.querySelector('a[href="track_my_driver.php"]');
      if (trackRideLink) {
        if (hasTodayBooking) {
          trackRideLink.href = "track_my_driver.php";
          trackRideLink.classList.remove("disabled");
          trackRideLink.style.opacity = "1";
          trackRideLink.style.cursor = "pointer";
        } else {
          trackRideLink.href = "#";
          trackRideLink.classList.add("disabled");
          trackRideLink.style.opacity = "0.5";
          trackRideLink.style.cursor = "not-allowed";
          trackRideLink.onclick = function(e) {
            e.preventDefault();
            alert("You don't have any bookings for today. Track My Ride is only available for today's bookings.");
          };
        }
      }
    }

    // Display bookings in the UI
    function displayBookings(bookings) {
      const container = document.getElementById('bookings-container');
      
      if (!bookings || bookings.length === 0) {
        container.innerHTML = '<div class="no-bookings">You have no bookings yet. <a href="booking.html">Book a seat now</a></div>';
        return;
      }
      
      // Create table HTML
      let tableHTML = `
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Booking ID</th>
                <th>Route</th>
                <th>Date</th>
                <th>Time</th>
                <th>Seats</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
      `;
      
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
        
        tableHTML += `
          <tr>
            <td>#${booking.booking_id}</td>
            <td>${booking.route || 'Route not specified'}</td>
            <td>${formattedDate}</td>
            <td>${booking.departure_time || 'Not specified'}</td>
            <td>${booking.seats || 1}</td>
            <td><span class="booking-status ${statusClass}">${statusText}</span></td>
            <td>
              <div style="display: flex; gap: 5px;">
                <button class="btn btn-secondary" onclick="trackRide(${booking.booking_id})">Track ride</button>
                <button class="btn" onclick="viewBookingDetails(${booking.booking_id})">Check ride</button>
              </div>
            </td>
          </tr>
        `;
      });
      
      tableHTML += `
            </tbody>
          </table>
        </div>
      `;
      
      container.innerHTML = tableHTML;
    }

    // View booking details
    function viewBookingDetails(bookingId) {
      // Fetch detailed booking information
      fetch('get_booking_details.php?booking_id=' + bookingId)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const booking = data.booking;
            const modalContent = document.getElementById("modalContent");
            
            // Vehicle information section with only requested fields
            let vehicleInfo = '';
            if (booking.assigned_vehicle) {
              vehicleInfo = `
                <div class="vehicle-info">
                  ${booking.driver_name ? `
                  <div class="detail-item">
                    <span class="detail-label">Driver Name:</span>
                    <span class="detail-value driver-name">${booking.driver_name}</span>
                  </div>` : ''}
                  ${booking.driver_phone ? `
                  <div class="detail-item">
                    <span class="detail-label">Driver Phone:</span>
                    <span class="detail-value driver-phone" style="cursor: pointer;" onclick="copyToClipboard('${booking.driver_phone}', this)">${booking.driver_phone}</span>
                  </div>` : ''}
                  <div class="detail-item">
                    <span class="detail-label">Number Plate:</span>
                    <span class="detail-value vehicle-plate">${booking.assigned_vehicle}</span>
                  </div>
                  ${booking.vehicle_type ? `
                  <div class="detail-item">
                    <span class="detail-label">Vehicle Type:</span>
                    <span class="detail-value vehicle-type">${booking.vehicle_type}</span>
                  </div>` : ''}
                  ${booking.vehicle_color ? `
                  <div class="detail-item">
                    <span class="detail-label">Vehicle Color:</span>
                    <span class="detail-value vehicle-color">${booking.vehicle_color}</span>
                  </div>` : ''}
                </div>
              `;
            } else {
              vehicleInfo = `
                <div class="vehicle-info">
                  <div class="detail-item">
                    <span class="detail-label" style="text-align: center; width: 100%; font-size: 1.2rem; color: #6A0DAD; font-weight: 600;">You have not been assigned to a ride yet</span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-value" style="text-align: center; width: 100%; font-style: italic; color: #666;">Please wait for vehicle assignment</span>
                  </div>
                </div>
              `;
            }
            
            // Display only vehicle information in the modal
            modalContent.innerHTML = `
              ${vehicleInfo}
              <div class="text-center mt-2">
                <button class="btn" onclick="document.getElementById('bookingModal').style.display='none'">Close</button>
              </div>
            `;
            
            document.getElementById('bookingModal').style.display = "flex";
            
            // Add sparkle effects
            addSparkleEffects();
          } else {
            alert('Error loading booking details: ' + data.error);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error loading booking details');
        });
    }
    
    // Function to add sparkle effects to the modal
    function addSparkleEffects() {
      const modalContent = document.querySelector('.modal-content');
      if (!modalContent) return;
      
      // Clear existing sparkles
      const existingSparkles = modalContent.querySelectorAll('.sparkle');
      existingSparkles.forEach(sparkle => sparkle.remove());
      
      // Add new sparkles
      for (let i = 0; i < 15; i++) {
        const sparkle = document.createElement('div');
        sparkle.classList.add('sparkle');
        sparkle.style.left = Math.random() * 100 + '%';
        sparkle.style.top = Math.random() * 100 + '%';
        sparkle.style.animationDelay = Math.random() * 2 + 's';
        sparkle.style.width = (Math.random() * 8 + 4) + 'px';
        sparkle.style.height = sparkle.style.width;
        modalContent.appendChild(sparkle);
      }
    }
    
    // Function to copy text to clipboard
    function copyToClipboard(text, element) {
      navigator.clipboard.writeText(text).then(() => {
        // Show visual feedback
        const originalText = element.textContent;
        element.textContent = 'Copied! ✅';
        element.style.color = '#28a745';
        element.style.transform = 'scale(1.1)';
        
        // Add temporary glow effect
        element.style.textShadow = '0 0 10px #28a745';
        
        // Reset after 2 seconds
        setTimeout(() => {
          element.textContent = originalText;
          element.style.color = ''; // Reset to original color
          element.style.transform = '';
          element.style.textShadow = '';
        }, 2000);
      }).catch(err => {
        console.error('Failed to copy: ', err);
        element.textContent = 'Failed! ❌';
        element.style.color = '#dc3545';
        setTimeout(() => {
          element.textContent = text;
          element.style.color = '';
        }, 2000);
      });
    }
    
    // Track ride function - redirects to tracking page
    function trackRide(bookingId) {
      // Redirect to the tracking page with the booking ID
      window.location.href = 'track_my_driver.php?booking_id=' + bookingId;
    }

    // Initialize the page
    document.addEventListener('DOMContentLoaded', function() {
      fetchUserProfile();
    });
  </script>
</body>
</html>