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

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

foreach ($input as $item) {
    $id = intval($item['id']);
    $order = intval($item['order']);
    $stmt = $conn->prepare("UPDATE hero_slides SET slide_order = ? WHERE id = ?");
    $stmt->bind_param("ii", $order, $id);
    $stmt->execute();
    $stmt->close();
}
echo json_encode(['success' => true]);