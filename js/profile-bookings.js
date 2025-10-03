// JavaScript file to handle booking display in passenger profile

// Add modal styles dynamically
(function() {
  const modalStyles = `
    <style>
      /* Enhanced Modal Styles for JavaScript-loaded content */
      .vehicle-info {
        background: linear-gradient(135deg, rgba(0, 191, 255, 0.2), rgba(0, 150, 200, 0.2));
        border-radius: 15px;
        padding: 1rem;
        margin: 1rem 0;
        border-left: 5px solid #00BFFF;
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
        background: linear-gradient(90deg, #00BFFF, #6A0DAD);
      }
      
      .detail-item {
        display: flex;
        justify-content: space-between;
        padding: 0.8rem 0;
        border-bottom: 1px dashed #d0d0d0;
        align-items: center;
        transition: all 0.3s ease;
        position: relative;
      }
      
      .detail-item:hover {
        background: linear-gradient(to right, rgba(106, 13, 173, 0.05), transparent);
        padding-left: 0.5rem;
        padding-right: 0.5rem;
        border-radius: 8px;
        border-bottom: 1px solid #6A0DAD;
      }
      
      .detail-item:last-child {
        border-bottom: none;
      }
      
      .detail-label {
        font-weight: 600;
        color: #666;
        font-size: 1.05rem;
        position: relative;
        padding-left: 15px;
        flex: 1;
      }
      
      .detail-label::before {
        content: "•";
        color: #6A0DAD;
        position: absolute;
        left: 0;
        font-size: 1.2rem;
        top: 50%;
        transform: translateY(-50%);
      }
      
      .detail-value {
        font-weight: 500;
        color: #333;
        text-align: right;
        padding: 0.5rem 0.8rem;
        border-radius: 8px;
        background: white;
        box-shadow: 0 4px 8px rgba(0,0,0,0.08);
        border: 1px solid #eee;
        transition: all 0.3s ease;
        font-size: 1rem;
        flex: 1;
        margin-left: 1rem;
      }
      
      .detail-value:hover {
        box-shadow: 0 6px 12px rgba(0,0,0,0.12);
        transform: translateY(-2px);
      }
      
      .driver-name {
        color: #6A0DAD;
        font-weight: 700;
        font-size: 1.1rem;
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
        margin-left: 3px;
        font-size: 0.8rem;
      }
      
      .vehicle-plate {
        color: #dc3545;
        font-weight: 700;
        font-size: 1.2rem;
        text-align: center;
        display: block;
        padding: 0.7rem;
        background: linear-gradient(135deg, rgba(220, 53, 69, 0.15), rgba(200, 40, 50, 0.2));
        border-radius: 10px;
        margin: 0.5rem 0;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        border: 2px dashed #dc3545;
        letter-spacing: 1px;
        text-transform: uppercase;
      }
      
      .vehicle-type {
        color: #007bff;
        font-weight: 600;
        font-size: 1rem;
      }
      
      .vehicle-color {
        color: #fd7e14;
        font-weight: 600;
        font-size: 1rem;
      }
      
      /* Add sparkle effect */
      .sparkle {
        position: absolute;
        width: 10px;
        height: 10px;
        background: white;
        border-radius: 50%;
        animation: sparkle 2s infinite;
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
    </style>
  `;
  
  // Insert styles into the document head
  if (document.head) {
    document.head.insertAdjacentHTML('beforeend', modalStyles);
  }
})();

// Fetch user bookings
function fetchUserBookings() {
    fetch('get_passenger_bookings.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayBookings(data.bookings);
                updateBookingStats(data.bookings);
                // Update Track My Ride link based on today's bookings
                updateTrackRideLink(data.bookings);
            } else {
                console.error('Error fetching bookings:', data.error);
                document.getElementById('bookings-container').innerHTML = 
                    '<div class="no-bookings">Error loading bookings. Please try again later.</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('bookings-container').innerHTML = 
                '<div class="no-bookings">Error loading bookings. Please try again later.</div>';
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
        bookingsHTML += `
          <div class="booking-card">
            <div class="booking-info">
              <h3>${booking.route || 'Route not specified'}</h3>
              <p><strong>Booking ID:</strong> #${booking.booking_id}</p>
              <p><strong>Date:</strong> ${booking.formatted_travel_date || 'Not specified'}</p>
              <p><strong>Time:</strong> ${booking.departure_time || 'Not specified'}</p>
              <p><strong>Seats:</strong> ${booking.seats || 1}</p>
              <p><strong>Amount:</strong> ${booking.amount || 'Not specified'}</p>
            </div>
            <div>
              <span class="booking-status ${booking.status_class}">${booking.status}</span>
            </div>
            <div class="booking-actions">
              <div style="display: flex; gap: 5px;">
                <button class="btn btn-secondary" onclick="trackRide(${booking.booking_id})">Track ride</button>
                <button class="btn" onclick="viewBookingDetails(${booking.booking_id})">Check ride</button>
              </div>
            </div>
          </div>
        `;
    });
    
    container.innerHTML = bookingsHTML;
}

// Update booking statistics
function updateBookingStats(bookings) {
    if (!bookings) return;
    
    // Total bookings
    document.getElementById('total-bookings').textContent = bookings.length;
    
    // Calculate ride stats
    let completedRides = 0;
    let upcomingRides = 0;
    
    bookings.forEach(booking => {
        if (booking.status === 'Completed') {
            completedRides++;
        } else {
            upcomingRides++;
        }
    });
    
    document.getElementById('completed-rides').textContent = completedRides;
    document.getElementById('upcoming-rides').textContent = upcomingRides;
}

// Check if there's a booking for today and update the Track My Ride link
function updateTrackRideLink(bookings) {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  
  let hasTodayBooking = false;
  
  if (bookings && bookings.length > 0) {
    for (const booking of bookings) {
      if (booking.travel_date) {
        // Parse the travel date
        const dateParts = booking.formatted_travel_date.split(' ');
        if (dateParts.length === 3) {
          const month = dateParts[1];
          const day = parseInt(dateParts[2].replace(',', ''));
          const year = parseInt(dateParts[3]);
          
          // Convert month name to month index (0-11)
          const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
          const monthIndex = months.indexOf(month.substring(0, 3));
          
          if (monthIndex !== -1 && !isNaN(day) && !isNaN(year)) {
            const travelDate = new Date(year, monthIndex, day);
            travelDate.setHours(0, 0, 0, 0);
            
            if (travelDate.getTime() === today.getTime()) {
              hasTodayBooking = true;
              break;
            }
          }
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

// View booking details
function viewBookingDetails(bookingId) {
    // Fetch detailed booking information
    fetch(`get_booking_details.php?booking_id=${bookingId}`)
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

// Initialize when the page loads
document.addEventListener('DOMContentLoaded', function() {
    fetchUserBookings();
});