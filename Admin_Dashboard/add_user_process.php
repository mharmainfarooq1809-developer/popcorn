<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
$role = $_POST['role'] ?? 'user';
$profile_image = trim($_POST['profile_image'] ?? '');
$points = intval($_POST['points'] ?? 0);

if (empty($name) || empty($email) || empty($_POST['password'])) {
    echo json_encode(['success' => false, 'error' => 'Name, email and password are required.']);
    exit;
}

// Check if email exists
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Email already exists.']);
    $check->close();
    exit;
}
$check->close();

$stmt = $conn->prepare("INSERT INTO users (name, email, password, role, profile_image, points, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("sssssi", $name, $email, $password, $role, $profile_image, $points);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
$stmt->close();