<?php
session_start();
header('Content-Type: application/json');

require __DIR__ . '/auth.php'; // ensures driver is authenticated and CSRF enforced
require dirname(__DIR__) . '/db.php';

echo json_encode((function() use ($conn) {
    try {
        // Only accept POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return ['success' => false, 'error' => 'Method not allowed'];
        }

        // Resolve driver identity (driver_id)
        $driver_id = 0;
        // 1) Prefer an explicit session driver_id if present
        if (isset($_SESSION['driver_id']) && (int)$_SESSION['driver_id'] > 0) {
            $driver_id = (int)$_SESSION['driver_id'];
        }

        // 2) Next try resolving via phone stored in session
        if ($driver_id <= 0) {
            $driver_phone = $_SESSION['phone'] ?? '';
            if ($driver_phone) {
                // Try drivers.driver_phone first
                if ($stmt = $conn->prepare('SELECT id FROM drivers WHERE driver_phone = ? LIMIT 1')) {
                    $stmt->bind_param('s', $driver_phone);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($row = $res->fetch_assoc()) {
                        $driver_id = (int)$row['id'];
                    }
                }
                
                // Fallback to drivers.phone
                if ($driver_id <= 0) {
                    if ($stmt = $conn->prepare('SELECT id FROM drivers WHERE phone = ? LIMIT 1')) {
                        $stmt->bind_param('s', $driver_phone);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        if ($row = $res->fetch_assoc()) {
                            $driver_id = (int)$row['id'];
                        }
                    }
                }
                
                // Fallback: resolve via vehicles table -> drivers by phone (legacy)
                if ($driver_id <= 0) {
                    if ($vstmt = $conn->prepare('SELECT d.id as did FROM vehicles v JOIN drivers d ON v.driver_phone = d.phone WHERE v.driver_phone = ? LIMIT 1')) {
                        $vstmt->bind_param('s', $driver_phone);
                        $vstmt->execute();
                        $vres = $vstmt->get_result();
                        if ($vrow = $vres->fetch_assoc()) {
                            $driver_id = (int)$vrow['did'];
                        }
                    }
                }
            }
        }

        // 3) As a final fallback, map logged-in user to driver by email
        if ($driver_id <= 0 && isset($_SESSION['user_id'])) {
            if ($jstmt = $conn->prepare('SELECT d.id FROM drivers d JOIN users u ON d.email = u.email WHERE u.id = ? LIMIT 1')) {
                $uid = (int)$_SESSION['user_id'];
                $jstmt->bind_param('i', $uid);
                $jstmt->execute();
                $jres = $jstmt->get_result();
                if ($jrow = $jres->fetch_assoc()) {
                    $driver_id = (int)$jrow['id'];
                }
            }
        }

        if ($driver_id <= 0) {
            http_response_code(403);
            return ['success' => false, 'error' => 'Driver identity not resolved (no matching driver account). Ensure your driver profile exists and your session has phone/email set.'];
        }

        $status = isset($_POST['status']) ? trim($_POST['status']) : '';
        if ($status === 'stopped') {
            // Mark as inactive
            if ($ustmt = $conn->prepare('INSERT INTO driver_locations (driver_id, latitude, longitude, status) VALUES (?, 0, 0, "inactive") ON DUPLICATE KEY UPDATE status = VALUES(status), last_updated = CURRENT_TIMESTAMP')) {
                $ustmt->bind_param('i', $driver_id);
                $ustmt->execute();
            }
            return ['success' => true, 'message' => 'Sharing stopped'];
        }

        // Read lat/lng
        $lat = isset($_POST['latitude']) ? (float)$_POST['latitude'] : null;
        $lng = isset($_POST['longitude']) ? (float)$_POST['longitude'] : null;
        $accuracy = isset($_POST['accuracy']) ? (float)$_POST['accuracy'] : null;
        $speed = isset($_POST['speed']) ? (float)$_POST['speed'] : null;
        $heading = isset($_POST['heading']) ? (float)$_POST['heading'] : null;

        if ($lat === null || $lng === null) {
            http_response_code(400);
            return ['success' => false, 'error' => 'Missing latitude/longitude'];
        }

        // Upsert latest location (mark as active)
        if ($stmt = $conn->prepare('INSERT INTO driver_locations (driver_id, latitude, longitude, status, accuracy, speed, heading) VALUES (?, ?, ?, "active", ?, ?, ?) ON DUPLICATE KEY UPDATE latitude = VALUES(latitude), longitude = VALUES(longitude), status = VALUES(status), accuracy = VALUES(accuracy), speed = VALUES(speed), heading = VALUES(heading), last_updated = CURRENT_TIMESTAMP')) {
            $stmt->bind_param('iddddd', $driver_id, $lat, $lng, $accuracy, $speed, $heading);
            $stmt->execute();
        } else {
            throw new Exception('Prepare failed: ' . $conn->error);
        }

        // Insert history (best-effort)
        if ($hstmt = $conn->prepare('INSERT INTO driver_location_history (driver_id, latitude, longitude) VALUES (?, ?, ?)')) {
            $hstmt->bind_param('idd', $driver_id, $lat, $lng);
            $hstmt->execute();
        }

        return [
            'success' => true,
            'message' => 'Location updated successfully',
            'driver_id' => $driver_id,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } catch (Throwable $e) {
        http_response_code(500);
        return ['success' => false, 'error' => 'Server error', 'debug' => $e->getMessage()];
    }
})());
