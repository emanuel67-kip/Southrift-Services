<?php
// Configure session to match the driver system
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters to match login.php
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    ini_set('session.use_only_cookies', 1);
    
    // Set the same session name as admin system
    session_name('southrift_admin');
    
    // Set session cookie parameters for drivers (30 days)
    $lifetime = 2592000; // 30 days
    $path = '/';
    $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $httponly = true;
    
    session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
    
    // Start the session
    session_start();
}

// Include database connection
require_once '../db.php';

// Set proper headers for JSON response
header('Content-Type: application/json');

// Turn off error display to prevent HTML in JSON response
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

class WhatsAppLocationSender {
    private $conn;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    /**
     * Send live location to all assigned passengers via WhatsApp
     */
    public function sendLocationToPassengers($driver_phone, $latitude, $longitude, $message = null) {
        try {
            // Get assigned passengers for this driver
            $passengers = $this->getAssignedPassengers($driver_phone);
            
            if (empty($passengers)) {
                return [
                    'success' => false,
                    'message' => 'No assigned passengers found',
                    'sent_count' => 0
                ];
            }
            
            // Create location message
            $location_url = "https://www.google.com/maps?q={$latitude},{$longitude}";
            $default_message = "🚗 Your driver is sharing live location!\n\n📍 Current Location: {$location_url}\n\n🕒 Updated: " . date('H:i:s d/m/Y');
            $whatsapp_message = $message ?: $default_message;
            
            $sent_count = 0;
            $failed_numbers = [];
            
            foreach ($passengers as $passenger) {
                $phone = $this->formatPhoneNumber($passenger['user_phone']);
                
                if ($this->sendWhatsAppMessage($phone, $whatsapp_message)) {
                    $sent_count++;
                    $this->logLocationShare($driver_phone, $passenger['user_id'], $latitude, $longitude, 'whatsapp', 'sent');
                } else {
                    $failed_numbers[] = $phone;
                    $this->logLocationShare($driver_phone, $passenger['user_id'], $latitude, $longitude, 'whatsapp', 'failed');
                }
                
                // Small delay to avoid rate limiting
                usleep(500000); // 0.5 second delay
            }
            
            return [
                'success' => true,
                'message' => "Location sent to {$sent_count} passengers via WhatsApp",
                'sent_count' => $sent_count,
                'total_passengers' => count($passengers),
                'failed_numbers' => $failed_numbers
            ];
            
        } catch (Exception $e) {
            error_log("WhatsApp Location Sender Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error sending location: ' . $e->getMessage(),
                'sent_count' => 0
            ];
        }
    }
    
    /**
     * Get all passengers assigned to a driver for today
     */
    private function getAssignedPassengers($driver_phone) {
        try {
            error_log("WhatsApp: Searching for passengers with driver phone: $driver_phone");
            
            // First, get ALL vehicles assigned to this driver (same as todays_bookings.php)
            $vehicles = [];
            $vehicle_stmt = $this->conn->prepare("SELECT number_plate FROM vehicles WHERE driver_phone = ?");
            $vehicle_stmt->bind_param("s", $driver_phone);
            $vehicle_stmt->execute();
            $vehicle_result = $vehicle_stmt->get_result();
            while ($row = $vehicle_result->fetch_assoc()) {
                $vehicles[] = $row['number_plate'];
            }
            $vehicle_stmt->close();
            
            error_log("WhatsApp: Found " . count($vehicles) . " vehicles for driver: " . implode(', ', $vehicles));
            
            if (empty($vehicles)) {
                error_log("WhatsApp: No vehicles found for driver phone: $driver_phone");
                return [];
            }
            
            // Get today's bookings for ALL driver's vehicles (same query as todays_bookings.php)
            $placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
            
            $stmt = $this->conn->prepare("
                SELECT b.user_id, b.fullname, b.phone as user_phone, b.booking_id
                FROM bookings b
                WHERE b.assigned_vehicle IN ($placeholders)
                AND DATE(b.created_at) = CURDATE()
                AND b.phone IS NOT NULL
                AND b.phone != ''
                ORDER BY b.booking_id
            ");
            
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . $this->conn->error);
            }
            
            // Bind parameters dynamically
            $types = str_repeat('s', count($vehicles));
            $stmt->bind_param($types, ...$vehicles);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $passengers = $result->fetch_all(MYSQLI_ASSOC);
            error_log("WhatsApp: Query returned " . count($passengers) . " passengers");
            
            if (count($passengers) > 0) {
                error_log("WhatsApp: Passengers found:");
                foreach ($passengers as $index => $passenger) {
                    error_log("   Passenger " . ($index + 1) . ": {$passenger['fullname']} | Phone: {$passenger['user_phone']} | Booking: {$passenger['booking_id']}");
                }
            } else {
                // Debug: Let's check what's actually in the database
                error_log("WhatsApp: No passengers found. Running debug queries...");
                
                // Check bookings for today with phone numbers
                $debug1 = $this->conn->query("SELECT COUNT(*) as count FROM bookings WHERE DATE(created_at) = CURDATE() AND phone IS NOT NULL AND phone != ''");
                $count1 = $debug1 ? $debug1->fetch_assoc()['count'] : 0;
                error_log("WhatsApp Debug: Bookings today with phones: $count1");
                
                // Check vehicles with this driver phone
                $debug2 = $this->conn->prepare("SELECT COUNT(*) as count FROM vehicles WHERE driver_phone = ?");
                $debug2->bind_param('s', $driver_phone);
                $debug2->execute();
                $count2 = $debug2->get_result()->fetch_assoc()['count'];
                error_log("WhatsApp Debug: Vehicles with driver phone $driver_phone: $count2");
                
                // Check bookings assigned to driver's vehicles
                if (!empty($vehicles)) {
                    $debug_placeholders = str_repeat('?,', count($vehicles) - 1) . '?';
                    $debug3 = $this->conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE assigned_vehicle IN ($debug_placeholders) AND DATE(created_at) = CURDATE()");
                    $debug_types = str_repeat('s', count($vehicles));
                    $debug3->bind_param($debug_types, ...$vehicles);
                    $debug3->execute();
                    $count3 = $debug3->get_result()->fetch_assoc()['count'];
                    error_log("WhatsApp Debug: Bookings for driver's vehicles today: $count3");
                }
            }
            
            return $passengers;
        } catch (Exception $e) {
            error_log("Error getting assigned passengers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Format phone number for WhatsApp (remove spaces, add country code if needed)
     */
    private function formatPhoneNumber($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add Kenya country code if not present (assuming Kenya based on project)
        if (strlen($phone) == 9 && !str_starts_with($phone, '254')) {
            $phone = '254' . $phone;
        } elseif (strlen($phone) == 10 && str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        }
        
        return $phone;
    }
    
    /**
     * Send WhatsApp message using WhatsApp Web API
     */
    private function sendWhatsAppMessage($phone, $message) {
        try {
            // Method 1: WhatsApp Web URL (opens in browser/app)
            $whatsapp_url = "https://wa.me/{$phone}?text=" . urlencode($message);
            
            // For automated sending, you would need to integrate with:
            // 1. WhatsApp Business API
            // 2. Third-party service like Twilio
            // 3. WhatsApp Web automation tool
            
            // For now, we'll use a simple approach that works with most setups
            return $this->sendViaWebAPI($phone, $message);
            
        } catch (Exception $e) {
            error_log("WhatsApp send error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send via WhatsApp Web API (requires additional setup)
     */
    private function sendViaWebAPI($phone, $message) {
        // OPTION 1: Twilio WhatsApp API (RECOMMENDED)
        // Uncomment and configure the following section:
        /*
        require_once 'vendor/autoload.php'; // Install via: composer require twilio/sdk
        
        $twilio_sid = 'your_twilio_account_sid';
        $twilio_token = 'your_twilio_auth_token';
        $twilio_whatsapp_number = 'whatsapp:+14155238886'; // Twilio sandbox number
        
        $client = new Twilio\Rest\Client($twilio_sid, $twilio_token);
        
        try {
            $result = $client->messages->create(
                "whatsapp:+{$phone}",
                [
                    'from' => $twilio_whatsapp_number,
                    'body' => $message
                ]
            );
            error_log("Twilio WhatsApp sent successfully: " . $result->sid);
            return true;
        } catch (Exception $e) {
            error_log("Twilio WhatsApp error: " . $e->getMessage());
            return false;
        }
        */
        
        // OPTION 2: WhatsApp Cloud API (Meta's Official API)
        // Uncomment and configure:
        /*
        $access_token = 'your_whatsapp_cloud_api_token';
        $phone_number_id = 'your_phone_number_id';
        
        $url = "https://graph.facebook.com/v18.0/{$phone_number_id}/messages";
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'text',
            'text' => ['body' => $message]
        ];
        
        $headers = [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            error_log("WhatsApp Cloud API sent successfully");
            return true;
        } else {
            error_log("WhatsApp Cloud API error: " . $response);
            return false;
        }
        */
        
        // OPTION 3: Simple browser redirect (for testing)
        // This will open WhatsApp Web with pre-filled message
        if (isset($_GET['test_whatsapp'])) {
            $whatsapp_url = "https://wa.me/{$phone}?text=" . urlencode($message);
            header("Location: $whatsapp_url");
            exit;
        }
        
        // OPTION 3: WhatsApp Web Integration (ACTIVE)
        // This will create JavaScript to open WhatsApp Web for each passenger
        $whatsapp_url = "https://wa.me/{$phone}?text=" . urlencode($message);
        
        // Store the URL in session for JavaScript to open
        if (!isset($_SESSION['whatsapp_urls'])) {
            $_SESSION['whatsapp_urls'] = [];
        }
        $_SESSION['whatsapp_urls'][] = $whatsapp_url;
        
        // Log for debugging
        error_log("WhatsApp URL created for +{$phone}: {$whatsapp_url}");
        
        // Save message to log file for reference
        $log_message = date('Y-m-d H:i:s') . " - To: +{$phone}\nMessage: {$message}\nURL: {$whatsapp_url}\n" . str_repeat('-', 50) . "\n";
        file_put_contents(__DIR__ . '/whatsapp_messages.log', $log_message, FILE_APPEND);
        
        return true;
    }
    
    /**
     * Log location sharing attempts
     */
    private function logLocationShare($driver_phone, $passenger_id, $latitude, $longitude, $method, $status) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO location_share_log (driver_phone, passenger_id, latitude, longitude, share_method, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            if ($stmt) {
                $stmt->bind_param('siddss', $driver_phone, $passenger_id, $latitude, $longitude, $method, $status);
                $stmt->execute();
            }
        } catch (Exception $e) {
            error_log("Error logging location share: " . $e->getMessage());
            // Don't throw exception, just log the error
        }
    }
    
    /**
     * Create location share log table if it doesn't exist
     */
    public function createLogTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS location_share_log (
                id INT PRIMARY KEY AUTO_INCREMENT,
                driver_phone VARCHAR(20) NOT NULL,
                passenger_id INT NOT NULL,
                latitude DECIMAL(10, 8) NOT NULL,
                longitude DECIMAL(11, 8) NOT NULL,
                share_method VARCHAR(20) DEFAULT 'whatsapp',
                status VARCHAR(20) DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_driver_passenger (driver_phone, passenger_id),
                INDEX idx_created_at (created_at)
            )
        ";
        
        return $this->conn->query($sql);
    }
    
    /**
     * Get debug information to help troubleshoot passenger assignment issues
     */
    private function getDebugInfo($driver_phone) {
        try {
            $debug = [];
            
            // Check bookings for today
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE DATE(travel_date) = CURDATE()");
            $stmt->execute();
            $result = $stmt->get_result();
            $debug['bookings_today'] = $result->fetch_assoc()['count'];
            
            // Check vehicles with this driver phone
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM vehicles WHERE driver_phone = ?");
            $stmt->bind_param('s', $driver_phone);
            $stmt->execute();
            $result = $stmt->get_result();
            $debug['vehicles_with_this_phone'] = $result->fetch_assoc()['count'];
            
            // Check users with phone numbers
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM users WHERE phone IS NOT NULL AND phone != ''");
            $stmt->execute();
            $result = $stmt->get_result();
            $debug['users_with_phones'] = $result->fetch_assoc()['count'];
            
            // Check if there are any bookings at all for this driver's vehicles
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM bookings b 
                JOIN vehicles v ON b.assigned_vehicle = v.number_plate 
                WHERE v.driver_phone = ?
            ");
            $stmt->bind_param('s', $driver_phone);
            $stmt->execute();
            $result = $stmt->get_result();
            $debug['total_bookings_for_driver'] = $result->fetch_assoc()['count'];
            
            return $debug;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Send bulk location update to all passengers
     */
    public function sendBulkLocationUpdate($driver_phone, $latitude, $longitude, $custom_message = null) {
        // Create log table if it doesn't exist
        $this->createLogTable();
        
        // Debug: Log the driver phone we're searching for
        error_log("WhatsApp: Searching for passengers assigned to driver phone: $driver_phone");
        
        $passengers = $this->getAssignedPassengers($driver_phone);
        
        error_log("WhatsApp: Found " . count($passengers) . " passengers");
        
        if (empty($passengers)) {
            // Try to provide more helpful error information
            $debug_info = $this->getDebugInfo($driver_phone);
            
            return [
                'success' => false,
                'message' => 'No passengers assigned to send location updates',
                'debug_info' => $debug_info,
                'suggestion' => 'Please ensure: 1) You have bookings for today, 2) Passengers have phone numbers, 3) Your driver phone matches vehicle records'
            ];
        }
        
        // Enhanced message with passenger-specific details
        $enhanced_message = $this->createEnhancedMessage($driver_phone, $latitude, $longitude, $custom_message);
        
        return $this->sendLocationToPassengers($driver_phone, $latitude, $longitude, $enhanced_message);
    }
    
    /**
     * Create enhanced message with driver and vehicle details
     */
    private function createEnhancedMessage($driver_phone, $latitude, $longitude, $custom_message = null) {
        try {
            error_log("WhatsApp Enhanced Message: Looking for driver with phone: $driver_phone");
            
            // Get driver and vehicle information
            $stmt = $this->conn->prepare("
                SELECT v.driver_name, v.driver_phone, v.number_plate, v.type, v.color
                FROM vehicles v
                WHERE v.driver_phone = ?
                LIMIT 1
            ");
            
            $driver_info = null;
            if ($stmt) {
                $stmt->bind_param('s', $driver_phone);
                $stmt->execute();
                $result = $stmt->get_result();
                $driver_info = $result->fetch_assoc();
                
                if ($driver_info) {
                    error_log("WhatsApp Enhanced Message: Found driver info: " . print_r($driver_info, true));
                } else {
                    error_log("WhatsApp Enhanced Message: No driver found for phone: $driver_phone");
                }
            } else {
                error_log("WhatsApp Enhanced Message: Failed to prepare statement");
            }
            
            $location_url = "https://www.google.com/maps?q={$latitude},{$longitude}";
            $directions_url = "https://www.google.com/maps/dir/?api=1&destination={$latitude},{$longitude}";
            
            $message = "🚗 *SouthRift Services - Live Location Update*\n\n";
            
            if ($driver_info) {
                if (!empty($driver_info['driver_name'])) {
                    $message .= "👨‍✈️ Driver: {$driver_info['driver_name']}\n";
                }
                if (!empty($driver_info['number_plate'])) {
                    $message .= "🚙 Vehicle: {$driver_info['number_plate']}";
                    if (!empty($driver_info['type'])) {
                        $message .= " ({$driver_info['type']})";
                    }
                    if (!empty($driver_info['color'])) {
                        $message .= " - {$driver_info['color']}";
                    }
                    $message .= "\n";
                }
                if (!empty($driver_info['driver_phone'])) {
                    $message .= "📞 Contact: {$driver_info['driver_phone']}\n\n";
                }
            }
            
            if ($custom_message) {
                $message .= "📢 *Message:* {$custom_message}\n\n";
            }
            
            $message .= "📍 *Current Location:*\n{$location_url}\n\n";
            $message .= "🧭 *Get Directions:*\n{$directions_url}\n\n";
            $message .= "🕒 Updated: " . date('H:i:s d/m/Y') . "\n\n";
            $message .= "_Track your ride in real-time!_";
            
            return $message;
        } catch (Exception $e) {
            error_log("Error creating enhanced message: " . $e->getMessage());
            // Return a basic message if enhanced message fails
            $location_url = "https://www.google.com/maps?q={$latitude},{$longitude}";
            return "🚗 SouthRift Services - Driver Location Update\n\n📍 Location: {$location_url}\n\n🕒 " . date('H:i:s d/m/Y');
        }
    }
}

// API endpoint for sending location via WhatsApp
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Debug: Log the request and session info
        error_log("WhatsApp Request: " . print_r($_POST, true));
        error_log("Session ID: " . session_id());
        error_log("Session CSRF: " . ($_SESSION['csrf_token'] ?? 'NOT_SET'));
        error_log("Posted CSRF: " . ($_POST['csrf_token'] ?? 'NOT_SET'));
        
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            error_log("CSRF token mismatch. Posted: " . ($_POST['csrf_token'] ?? 'none') . ", Session: " . ($_SESSION['csrf_token'] ?? 'none'));
            
            // Check if it's an empty session issue
            if (!isset($_SESSION['csrf_token'])) {
                error_log("Session CSRF token not set, generating new one");
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                echo json_encode([
                    'success' => false, 
                    'message' => 'Session expired. Please refresh the page and try again.',
                    'debug' => 'csrf_not_in_session'
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid CSRF token. Please refresh the page and try again.',
                    'debug' => 'csrf_mismatch'
                ]);
            }
            
            http_response_code(403);
            exit;
        }
        
        $driver_phone = $_POST['driver_id'] ?? null; // driver_id is actually the phone number
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;
        $custom_message = $_POST['message'] ?? null;
        
        error_log("WhatsApp params - Driver: $driver_phone, Lat: $latitude, Lng: $longitude");
        
        if (!$driver_phone || !$latitude || !$longitude) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Missing required parameters',
                'received' => [
                    'driver_phone' => $driver_phone,
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ]
            ]);
            exit;
        }
        
        if (!isset($conn) || !$conn instanceof mysqli) {
            throw new Exception("Database connection not available");
        }
        
        $whatsapp_sender = new WhatsAppLocationSender($conn);
        $result = $whatsapp_sender->sendBulkLocationUpdate($driver_phone, $latitude, $longitude, $custom_message);
        
        // Add WhatsApp URLs to the response for JavaScript to open
        if (isset($_SESSION['whatsapp_urls'])) {
            $result['whatsapp_urls'] = $_SESSION['whatsapp_urls'];
            unset($_SESSION['whatsapp_urls']); // Clear after use
        }
        
        error_log("WhatsApp result: " . print_r($result, true));
        echo json_encode($result);
        exit;
        
    } catch (Exception $e) {
        error_log("WhatsApp API Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Server error: ' . $e->getMessage(),
            'debug' => [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ]);
        exit;
    }
}