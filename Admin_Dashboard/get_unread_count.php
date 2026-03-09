<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['count' => 0]);
    exit;
}

$result = $conn->query("SELECT COUNT(*) AS count FROM feedback WHERE status = 'unread'");
$row = $result->fetch_assoc();
echo json_encode(['count' => $row['count']]);