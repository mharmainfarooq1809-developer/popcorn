<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$booking_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if (!$booking_id || !in_array($status, ['confirmed', 'pending', 'cancelled'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

$stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $booking_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}