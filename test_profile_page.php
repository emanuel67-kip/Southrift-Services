<?php
// Start session
session_start();

// Set test session data to simulate a logged in passenger
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'Test Passenger';
$_SESSION['role'] = 'passenger';
$_SESSION['email'] = 'test@example.com';

// Redirect to the passenger profile page
header('Location: passenger_profile.php');
exit;
?>