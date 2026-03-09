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
    echo json_encode(['success' => false, 'error' => 'Invalid movie ID']);
    exit;
}

// Toggle the is_featured value
$stmt = $conn->prepare("UPDATE movies SET is_featured = NOT is_featured WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    // Fetch the new value to return
    $result = $conn->query("SELECT is_featured FROM movies WHERE id = $id");
    $row = $result->fetch_assoc();
    echo json_encode(['success' => true, 'is_featured' => (int)$row['is_featured']]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}