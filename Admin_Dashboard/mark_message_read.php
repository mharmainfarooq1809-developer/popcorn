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
    $stmt = $conn->prepare("UPDATE feedback SET status = 'read' WHERE id = ?");
    $stmt->bind_param('i', $id);
    $success = $stmt->execute();
    echo json_encode(['success' => $success]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false]);