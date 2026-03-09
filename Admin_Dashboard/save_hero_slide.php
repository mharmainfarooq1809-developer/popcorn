<?php
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$title = trim($_POST['title'] ?? '');
$image = trim($_POST['image'] ?? '');

if (empty($title) || empty($image)) {
    echo json_encode(['success' => false, 'error' => 'Title and image are required']);
    exit;
}

if ($id) {
    $stmt = $conn->prepare("UPDATE hero_slides SET title=?, image_url=? WHERE id=?");
    $stmt->bind_param("ssi", $title, $image, $id);
} else {
    $result = $conn->query("SELECT MAX(slide_order) as max_order FROM hero_slides");
    $row = $result->fetch_assoc();
    $order = ($row['max_order'] ?? 0) + 1;
    $stmt = $conn->prepare("INSERT INTO hero_slides (title, image_url, slide_order) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $title, $image, $order);
}

if ($stmt->execute()) {
    $new_id = $id ?: $conn->insert_id;
    echo json_encode(['success' => true, 'id' => $new_id]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}