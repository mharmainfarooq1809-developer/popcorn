<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? 'user';
$profile_image = trim($_POST['profile_image'] ?? '');
$points = intval($_POST['points'] ?? 0);
$password = $_POST['password'] ?? '';

if (!$id || empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Invalid data.']);
    exit;
}

// If password provided, hash it; otherwise keep current password
if (!empty($password)) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, profile_image = ?, points = ?, password = ? WHERE id = ?");
    $stmt->bind_param("ssssisi", $name, $email, $role, $profile_image, $points, $hashed, $id);
} else {
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, profile_image = ?, points = ? WHERE id = ?");
    $stmt->bind_param("ssssii", $name, $email, $role, $profile_image, $points, $id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
$stmt->close();