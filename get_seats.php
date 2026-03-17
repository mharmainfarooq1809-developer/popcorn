<?php
// Turn off error display to prevent HTML in output
ini_set('display_errors', 0);
error_reporting(E_ALL); // Still log errors

require_once 'db_connect.php';

header('Content-Type: application/json');

function sendError($message) {
    echo json_encode(['error' => $message]);
    exit;
}

if (!isset($_GET['showtime_id']) || !is_numeric($_GET['showtime_id'])) {
    sendError('Invalid showtime ID');
}

$showtime_id = intval($_GET['showtime_id']);

$booked = [];
$pending = [];

try {
    // Query confirmed bookings (extract seats from the comma-separated string)
    $stmt = $conn->prepare("SELECT seats FROM bookings WHERE showtime_id = ? AND status = 'confirmed'");
    $stmt->bind_param("i", $showtime_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['seats'])) {
            $seats = explode(',', $row['seats']);
            $booked = array_merge($booked, $seats);
        }
    }
    $stmt->close();

    // Query pending bookings
    $stmt = $conn->prepare("SELECT seats FROM bookings WHERE showtime_id = ? AND status = 'pending'");
    $stmt->bind_param("i", $showtime_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['seats'])) {
            $seats = explode(',', $row['seats']);
            $pending = array_merge($pending, $seats);
        }
    }
    $stmt->close();

    echo json_encode(['booked' => $booked, 'pending' => $pending]);

} catch (Exception $e) {
    error_log("get_seats.php exception: " . $e->getMessage());
    sendError('Server error');
}