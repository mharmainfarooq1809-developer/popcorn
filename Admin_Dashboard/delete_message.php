<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    // Delete replies first (cascade should handle if foreign key set)
    $conn->query("DELETE FROM feedback_replies WHERE feedback_id = $id");
    $stmt = $conn->prepare("DELETE FROM feedback WHERE id = ?");
    $stmt->bind_param('i', $id);
    $success = $stmt->execute();
    echo json_encode(['success' => $success]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false]);