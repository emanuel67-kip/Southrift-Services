<?php
session_start();
require __DIR__ . '/auth.php';
require dirname(__DIR__) . '/db.php';

header('Content-Type: application/json');

$driver_phone = $_SESSION['phone'] ?? '';
$action = $_POST['action'] ?? 'test';

if (empty($driver_phone)) {
    echo json_encode(['success' => false, 'error' => 'No driver phone in session']);
    exit;
}

try {
    echo json_encode([
        'success' => true,
        'message' => 'Test notification system working',
        'driver_phone' => $driver_phone,
        'action' => $action,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>