
<?php
session_start();
require_once 'db.php';

// Check if token is provided
$token = $_GET['token'] ?? '';
$message = '';
$message_type = '';
$driver_name = '';
$location_link = '';

if (empty($token)) {
    header('Location: index.html');
    exit();
}

// Function to validate Google Maps URL
function isValidGoogleMapsUrl($url) {
    $pattern = '/^(https?:\/\/)?(www\.)?(google\.com\/maps|maps\.google\.com|maps\.app\.goo\.gl|goo\.gl\/maps)/i';
    return preg_match($pattern, $url) === 1;
}

// Get driver's location using the token
$stmt = $conn->prepare("
    SELECT dl.google_maps_link, d.name as driver_name 
    FROM driver_locations dl
    JOIN drivers d ON dl.driver_id = d.id
    WHERE dl.share_token = ? AND dl.status = 'active'
    LIMIT 1
");

$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $location_link = trim($row['google_maps_link']);
    $driver_name = $row['driver_name'];
    
    // Validate the Google Maps URL before redirecting
    if (!empty($location_link) && filter_var($location_link, FILTER_VALIDATE_URL) && isValidGoogleMapsUrl($location_link)) {
        // Add https:// if not present
        if (!preg_match("~^(?:f|ht)tps?://~i", $location_link)) {
            $location_link = "https://" . $location_link;
        }
        
        // Log the redirection for debugging
        error_log("Redirecting to driver's location: " . $location_link);
        
        // Redirect to the Google Maps link
        header("Location: " . $location_link);
        exit();
    } else {
        $message = 'The driver\'s location link is invalid or not properly formatted.';
        $message_type = 'error';
        
        // Log the error for debugging
        error_log("Invalid Google Maps URL: " . $location_link);
    }
} else {
    $message = 'Invalid or expired tracking link.';
    $message_type = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Driver - SouthRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --purple: #6A0DAD;
            --bg: #f4f4f4;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: var(--bg);
            color: #333;
            line-height: 1.6;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }

        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 40px 30px;
            margin-top: 40px;
        }

        h1 {
            color: var(--purple);
            margin-bottom: 20px;
        }

        .message {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 500;
            background: #f8f9fa;
            border-left: 4px solid #6A0DAD;
        }

        .error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
            padding: 20px;
            text-align: left;
        }

        .error h3 {
            margin-top: 0;
            color: #c62828;
        }

        .btn {
            display: inline-block;
            background: var(--purple);
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .btn i {
            margin-right: 8px;
        }

        .btn:hover {
            background: #5a0b9c;
        }

        .troubleshoot {
            margin-top: 20px;
            padding: 15px;
            background: #e3f2fd;
            border-radius: 6px;
            text-align: left;
        }

        .troubleshoot h4 {
            margin-top: 0;
            color: #1565c0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-location-dot"></i> Driver Location</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?= $message_type ?>">
                <?php if ($message_type === 'error'): ?>
                    <h3><i class="fas fa-exclamation-triangle"></i> Unable to Show Driver's Location</h3>
                    <p><?= htmlspecialchars($message) ?></p>
                    
                    <div class="troubleshoot">
                        <h4><i class="fas fa-tools"></i> Troubleshooting Tips:</h4>
                        <ul style="margin-left: 20px; margin-top: 10px;">
                            <li>Ask the driver to share their location again</li>
                            <li>Make sure the driver has an active internet connection</li>
                            <li>Try again in a few moments</li>
                        </ul>
                    </div>
                    
                    <a href="index.html" class="btn">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($location_link)): ?>
    <script>
        // Fallback redirect in case header redirect fails
        window.onload = function() {
            // Only attempt redirect if we have a valid URL
            if (window.location.href.indexOf('?') > -1) {
                setTimeout(function() {
                    window.location.href = '<?= addslashes($location_link) ?>';
                }, 2000);
            }
        };
    </script>
    <?php endif; ?>
</body>
</html>
