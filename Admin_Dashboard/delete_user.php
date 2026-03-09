<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID.']);
    exit;
}

// Prevent admin from deleting themselves
if ($id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'error' => 'You cannot delete your own account.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
$stmt->close();