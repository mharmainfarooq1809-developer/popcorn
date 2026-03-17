<?php
session_start();
require_once '../db_connect.php'; // adjust path

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['notifications' => []]);
    exit;
}

// Fetch unread notifications (global)
$result = $conn->query("SELECT id, type, message, link, created_at FROM notifications WHERE is_read = 0 ORDER BY created_at DESC LIMIT 10");

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode(['notifications' => $notifications]);
?>
