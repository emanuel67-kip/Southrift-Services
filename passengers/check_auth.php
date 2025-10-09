<?php
ini_set('session.gc_maxlifetime', 1800); // 30 minutes
session_set_cookie_params([
    'lifetime' => 1800,
    'path' => '/',
    'domain' => '',
    'secure' => false, // set true if serving over HTTPS
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require_once "db.php";

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    // If passenger, also ensure absolute expiry has not passed
    if ($_SESSION['role'] === 'passenger') {
        // Migrate older sessions that don't have expires_at yet
        if (!isset($_SESSION['expires_at'])) {
            $_SESSION['expires_at'] = time() + 1800; // 30 minutes from first check
        }
        if (time() > $_SESSION['expires_at']) {
            session_unset();
            session_destroy();
        } else {
            redirectToDashboard($_SESSION['role']);
        }
    } else {
        redirectToDashboard($_SESSION['role']);
    }
}

// Check for remember me cookie
if (isset($_COOKIE['remember_token']) && isset($_COOKIE['user_id'])) {
    $token = $_COOKIE['remember_token'];
    $user_id = (int)$_COOKIE['user_id'];
    
    // Verify token from database
    $stmt = $conn->prepare("SELECT u.* FROM users u 
                          INNER JOIN user_tokens ut ON u.id = ut.user_id 
                          WHERE u.id = ? AND ut.token = ? AND ut.expires_at > NOW()
                          LIMIT 1");
    $stmt->bind_param("is", $user_id, $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Do NOT auto-login passengers via remember-me; enforce 30-min manual login window
        if ($user['role'] === 'passenger') {
            // Clear cookies and show login
            setcookie('remember_token', '', time() - 3600, '/');
            setcookie('user_id', '', time() - 3600, '/');
        } else {
            // Log the user in (non-passenger)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // Extend the token expiration for non-passenger users only
            $new_expiry = time() + (30 * 24 * 60 * 60); // 30 days from now
            $expiry_date = date('Y-m-d H:i:s', $new_expiry);
            
            $stmt = $conn->prepare("UPDATE user_tokens SET expires_at = ? WHERE token = ?");
            $stmt->bind_param("ss", $expiry_date, $token);
            $stmt->execute();
            
            // Update the cookie
            setcookie('remember_token', $token, $new_expiry, '/', '', false, true);
            setcookie('user_id', $user['id'], $new_expiry, '/', '', false, true);
            
            // Redirect to dashboard
            redirectToDashboard($user['role']);
        }
    } else {
        // Invalid or expired token, clear cookies
        setcookie('remember_token', '', time() - 3600, '/');
        setcookie('user_id', '', time() - 3600, '/');
    }
}

function redirectToDashboard($role) {
    switch ($role) {
        case 'admin':
            header("Location: ../Admin/index.php");
            break;
        case 'passenger':
            header("Location: index.html");
            break;
        case 'driver':
            header("Location: ../Driver/index.php");
            break;
        default:
            header("Location: ../login.html");
    }
    exit();
}
?>
