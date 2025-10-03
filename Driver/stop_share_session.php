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

    // Resolve driver_id
    $driver_id = 0;
    $driver_phone = $_SESSION['phone'] ?? '';
    if ($driver_phone) {
        $stmt = $conn->prepare('SELECT id FROM drivers WHERE phone = ? LIMIT 1');
        $stmt->bind_param('s', $driver_phone);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) $driver_id = (int)$row['id'];
    }
    if ($driver_id <= 0 && isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare('SELECT d.id FROM drivers d JOIN users u ON d.email = u.email WHERE u.id = ? LIMIT 1');
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) $driver_id = (int)$row['id'];
    }
    if ($driver_id <= 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Driver identity not resolved']);
        exit;
    }

    $token = isset($_POST['token']) ? trim($_POST['token']) : '';

    if ($token !== '') {
        $stmt = $conn->prepare('UPDATE driver_share_sessions SET status = "stopped" WHERE token = ? AND driver_id = ? AND status = "active"');
        $stmt->bind_param('si', $token, $driver_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare('UPDATE driver_share_sessions SET status = "stopped" WHERE driver_id = ? AND status = "active"');
        $stmt->bind_param('i', $driver_id);
        $stmt->execute();
    }

    echo json_encode(['success' => true, 'message' => 'Share session stopped']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error', 'debug' => $e->getMessage()]);
}
