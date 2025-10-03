-- Sample data for the updated bookings table
-- This script inserts sample booking records for testing purposes

INSERT INTO `bookings` (
  `user_id`, 
  `fullname`, 
  `phone`, 
  `route`, 
  `boarding_point`, 
  `travel_date`, 
  `departure_time`, 
  `seats`, 
  `payment_method`, 
  `assigned_vehicle`, 
  `google_maps_link`
) VALUES
(1, 'John Doe', '254712345678', 'Nairobi - Kisumu', 'Madaraka Bus Stage', '2025-10-15', '08:00 AM', 2, 'mpesa', 'KBX 456T', 'https://maps.google.com/maps?q=vehicle_location'),
(2, 'Jane Smith', '254723456789', 'Nairobi - Nakuru', 'Syokimau Bus Stage', '2025-10-16', '06:00 AM', 1, 'pay-onboarding', NULL, NULL),
(3, 'Robert Johnson', '254734567890', 'Nairobi - Bomet', 'CBD Bus Station', '2025-10-17', '12:00 PM', 3, 'card', 'KBC 123R', 'https://maps.google.com/maps?q=vehicle_location_2');

-- Sample query to verify the data
SELECT 
  b.booking_id,
  u.name as passenger_name,
  b.fullname,
  b.phone,
  b.route,
  b.boarding_point,
  b.travel_date,
  b.departure_time,
  b.seats,
  b.payment_method,
  b.assigned_vehicle,
  b.created_at
FROM bookings b
JOIN users u ON b.user_id = u.id
ORDER BY b.created_at DESC;