<?php
session_start();
require __DIR__ . '/auth.php';
require dirname(__DIR__) . '/db.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    // Resolve driver_id robustly
    $driver_id = 0;

    // 1) Prefer explicit session driver_id
    if (isset($_SESSION['driver_id']) && (int)$_SESSION['driver_id'] > 0) {
        $driver_id = (int)$_SESSION['driver_id'];
    }

    // 2) Try via phone in session
    if ($driver_id <= 0) {
        $driver_phone = $_SESSION['phone'] ?? '';
        if ($driver_phone) {
            $stmt = $conn->prepare('SELECT id FROM drivers WHERE phone = ? LIMIT 1');
            $stmt->bind_param('s', $driver_phone);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $driver_id = (int)$row['id'];
            }
        }
    }

    // 3) Fallback: map users->drivers by email
    if ($driver_id <= 0 && isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare('SELECT d.id FROM drivers d JOIN users u ON d.email = u.email WHERE u.id = ? LIMIT 1');
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $driver_id = (int)$row['id'];
        }
    }

    if ($driver_id <= 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Driver identity not resolved']);
        exit;
    }

    $duration = isset($_POST['duration_minutes']) ? (int)$_POST['duration_minutes'] : 60; // default 60 min
    if ($duration < 5) $duration = 5;
    if ($duration > 480) $duration = 480; // cap 8 hours

    $token = bin2hex(random_bytes(16));
    $expires_at = date('Y-m-d H:i:s', time() + ($duration * 60));

    // Ensure table exists (defensive; recommended to run SQL script instead)
    $conn->query("CREATE TABLE IF NOT EXISTS driver_share_sessions (
      id INT AUTO_INCREMENT PRIMARY KEY,
      driver_id INT NOT NULL,
      token VARCHAR(64) NOT NULL UNIQUE,
      status ENUM('active','stopped','expired') NOT NULL DEFAULT 'active',
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      expires_at DATETIME NOT NULL,
      INDEX (driver_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Expire any old active sessions past their expiry
    if ($upd = $conn->prepare('UPDATE driver_share_sessions SET status = "expired" WHERE driver_id = ? AND status = "active" AND expires_at < NOW()')) {
        $upd->bind_param('i', $driver_id);
        $upd->execute();
    }

    $stmt = $conn->prepare('INSERT INTO driver_share_sessions (driver_id, token, status, expires_at) VALUES (?, ?, "active", ?)');
    $stmt->bind_param('iss', $driver_id, $token, $expires_at);
    $stmt->execute();

    // Build share URL (token-based)
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // /Driver
    $share_url = $scheme . '://' . $host . $base . '/../view_driver_location.php?token=' . urlencode($token);

    echo json_encode([
        'success' => true,
        'token' => $token,
        'expires_at' => $expires_at,
        'share_url' => $share_url,
        'duration_minutes' => $duration,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error', 'debug' => $e->getMessage()]);
}
