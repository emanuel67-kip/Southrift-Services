<?php
require_once "../db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $number_plate = isset($_POST["number_plate"]) ? trim($_POST["number_plate"]) : '';
    // Normalize basic spacing and case (optional but helpful)
    $number_plate = strtoupper(preg_replace('/\s+/', ' ', $number_plate));

    if (!empty($number_plate)) {
        // Check if the vehicle exists in the vehicles table
        $check = $conn->prepare("SELECT id FROM vehicles WHERE number_plate = ?");
        $check->bind_param("s", $number_plate);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            // Vehicle exists — mark it as waiting (set is_waiting = 1)
            $stmt = $conn->prepare("UPDATE vehicles SET is_waiting = 1 WHERE number_plate = ?");
            $stmt->bind_param("s", $number_plate);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                // Redirect to waiting list so admin immediately sees the update
                header("Location: vehicle_waiting.php?status=added");
            } else {
                header("Location: index.php?status=error"); // update failed
            }
            $stmt->close();
        } else {
            // Vehicle doesn't exist
            header("Location: index.php?status=notfound");
        }
        $check->close();
    } else {
        header("Location: index.php?status=empty");
    }
}
?>
