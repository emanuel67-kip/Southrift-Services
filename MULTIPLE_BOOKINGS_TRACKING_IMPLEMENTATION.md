# Multiple Bookings Tracking Implementation

## Overview
This implementation allows passengers to track multiple bookings on the same day, each assigned to different vehicles with different drivers.

## Changes Made

### 1. Enhanced Profile Data Retrieval (`profile.php`)
- Modified to include `assigned_vehicle` field in booking data
- Added logic to identify today's bookings for tracking purposes

### 2. Multiple Bookings Tracking (`track_my_driver.php`)
- Updated to fetch ALL bookings for the current day, not just one
- Added booking selection interface for passengers with multiple bookings
- Implemented booking-specific tracking using `booking_id` parameter
- Maintained backward compatibility for single booking scenarios

### 3. Profile Page Enhancements (`profile.html`)
- Updated booking table to show "Track ride" button only for today's bookings
- Added booking ID parameter to tracking links for precise tracking
- Enhanced quick actions to handle multiple bookings scenario
- Improved date comparison logic for better accuracy

### 4. Booking Details Enhancement (`get_booking_details.php`)
- Added booking_id to response for tracking purposes

### 5. Test File (`test_multiple_bookings.php`)
- Created test file to verify functionality

## How It Works

### For Passengers with Multiple Bookings:
1. When a passenger visits their profile, they see all their bookings
2. For today's bookings with assigned vehicles, they can click "Track ride"
3. If they have only one booking for today, they go directly to tracking
4. If they have multiple bookings for today, they go to a selection page

### Tracking Page Functionality:
1. Shows a dropdown to select which booking to track (if multiple exist)
2. Displays tracking information specific to the selected booking
3. Shows driver details, vehicle information, and location data
4. Provides call-to-action buttons (Call Driver, Get Directions)

## Key Features

### 1. Multiple Booking Support
- Passengers can have multiple bookings on the same day
- Each booking can be assigned to a different vehicle/driver
- Independent tracking for each booking

### 2. User-Friendly Interface
- Clear booking selection for multiple bookings
- Visual indicators for tracking status
- Responsive design for all devices

### 3. Backward Compatibility
- Single booking users experience no changes
- Existing tracking functionality preserved
- No breaking changes to existing APIs

### 4. Enhanced Tracking Options
- Google Maps integration
- Real-time location updates
- Direct calling to drivers
- Navigation and directions

## Technical Implementation Details

### Database Queries
- Fetches all bookings for the current day
- Joins with vehicles table to get driver information
- Filters by assigned vehicles only

### URL Parameters
- `booking_id` parameter used for specific booking tracking
- `redirect` parameter for direct Google Maps redirection

### Session Management
- Maintains existing session handling
- Preserves security measures (CSRF tokens)

## Testing
The implementation has been tested with:
- Single booking scenarios
- Multiple booking scenarios
- Edge cases (no bookings, unassigned vehicles)
- Date boundary conditions

## Usage Examples

### Example 1: John's Multiple Bookings
John has two bookings for today:
1. Litein to Nairobi (assigned to KCA 123A)
2. Litein to Kisumu (assigned to KCB 456B)

When John visits his profile:
- Both bookings appear in his booking history
- Both show "Track ride" buttons
- Clicking either button takes him to the tracking page
- The tracking page shows which booking he's tracking
- He can switch between bookings using the selector

### Example 2: Single Booking User
Jane has one booking for today:
1. Nairobi to Mombasa (assigned to KCC 789C)

When Jane visits her profile:
- Her booking appears in booking history
- "Track ride" button takes her directly to tracking
- No booking selector is shown (single booking)
- All existing functionality preserved

## Security Considerations
- Maintains existing authentication and authorization
- Preserves CSRF protection
- Validates booking ownership (users can only track their own bookings)
- Secure session handling

## Performance
- Efficient database queries with proper indexing
- Minimal additional overhead
- Caching considerations maintained
- Responsive loading indicators

## Future Enhancements
- Notification system for booking updates
- Estimated arrival times
- Booking status updates in real-time
- Enhanced mapping features