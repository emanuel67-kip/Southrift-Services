<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

function out($ok, $payload = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['success' => $ok], $payload));
    exit;
}

try {
    $token = isset($_GET['token']) ? trim($_GET['token']) : '';
    if ($token === '') out(false, ['error' => 'Missing token'], 400);

    // Validate live-share session
    $stmt = $conn->prepare('SELECT driver_id, status, expires_at FROM driver_share_sessions WHERE token = ? LIMIT 1');
    if (!$stmt) out(false, ['error' => 'Server error'], 500);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) out(false, ['error' => 'Invalid token'], 404);
    $row = $res->fetch_assoc();

    if ($row['status'] !== 'active') out(false, ['error' => 'Session inactive'], 403);
    if (strtotime($row['expires_at']) < time()) out(false, ['error' => 'Session expired'], 403);

    $driver_id = (int)$row['driver_id'];

    // Fetch latest active location
    $lstmt = $conn->prepare('SELECT latitude, longitude, last_updated, status FROM driver_locations WHERE driver_id = ? ORDER BY last_updated DESC LIMIT 1');
    $lstmt->bind_param('i', $driver_id);
    $lstmt->execute();
    $lres = $lstmt->get_result();
    if ($lres->num_rows === 0) out(false, ['error' => 'Driver is not sharing location yet']);
    $loc = $lres->fetch_assoc();
    if ($loc['status'] !== 'active') out(false, ['error' => 'Driver is currently inactive']);

    out(true, [
        'location' => [
            'lat' => (float)$loc['latitude'],
            'lng' => (float)$loc['longitude'],
            'last_updated' => $loc['last_updated'],
        ]
    ]);
} catch (Throwable $e) {
    out(false, ['error' => 'Server error', 'debug' => $e->getMessage()], 500);
}
