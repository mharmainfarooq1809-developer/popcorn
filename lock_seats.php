<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$showtimeId = (int)$data['showtimeId'];
$seats = $data['seats']; // comma-separated string
$userId = $_SESSION['user_id'];
$adults = (int)$data['adults'];
$children = (int)$data['children'];
$total = (float)$data['total'];
$discountEligible = $data['discountEligible'] ? 1 : 0;

// Check if any of the selected seats are already pending or confirmed
$seatArray = explode(',', $seats);
$placeholders = implode(',', array_fill(0, count($seatArray), '?'));
$types = str_repeat('s', count($seatArray));
$params = $seatArray;

$checkStmt = $conn->prepare("
    SELECT seats FROM bookings
    WHERE showtime_id = ? AND status IN ('pending', 'confirmed')
    AND FIND_IN_SET(?, seats)
");
// Note: FIND_IN_SET works with comma-separated values. Alternative: use JSON or separate table.
// For simplicity, we'll loop through each seat (not efficient for many seats, but okay for small scale).
$conn->begin_transaction();
try {
    foreach ($seatArray as $seat) {
        $check = $conn->prepare("SELECT id FROM bookings WHERE showtime_id = ? AND status IN ('pending','confirmed') AND FIND_IN_SET(?, seats)");
        $check->bind_param("is", $showtimeId, $seat);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            throw new Exception("Seat $seat is already taken.");
        }
    }

    // Lock the seats by inserting a pending booking with expiry 5 minutes from now
    $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    $insert = $conn->prepare("
        INSERT INTO bookings (showtime_id, user_id, seats, adults, children, total_price, discount_applied, status, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)
    ");
    $insert->bind_param("iissiids", $showtimeId, $userId, $seats, $adults, $children, $total, $discountEligible, $expires);
    $insert->execute();
    $bookingId = $conn->insert_id;

    $conn->commit();
    echo json_encode(['success' => true, 'booking_id' => $bookingId]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}