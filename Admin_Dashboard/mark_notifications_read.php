<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

// mark all unread notifications as read (global)
$conn->query("UPDATE notifications SET is_read = 1 WHERE is_read = 0");

echo json_encode(['success' => true]);
?>