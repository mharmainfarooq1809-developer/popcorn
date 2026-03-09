<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$bookingId = (int)$data['booking_id'];
$userId = $_SESSION['user_id'];

// Update status to confirmed and add points
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed', expires_at = NULL WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $bookingId, $userId);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        throw new Exception("Booking not found or already confirmed.");
    }

    // Add points to user (example: 1 point per ticket)
    $booking = $conn->prepare("SELECT adults + children AS tickets FROM bookings WHERE id = ?");
    $booking->bind_param("i", $bookingId);
    $booking->execute();
    $tickets = $booking->get_result()->fetch_assoc()['tickets'];

    $updatePoints = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
    $updatePoints->bind_param("ii", $tickets, $userId);
    $updatePoints->execute();

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}