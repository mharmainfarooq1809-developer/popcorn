<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

// Remove previous featured flag from all movies
$conn->query("UPDATE movies SET is_featured = 0");

// Set new featured
$stmt = $conn->prepare("UPDATE movies SET is_featured = 1 WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}