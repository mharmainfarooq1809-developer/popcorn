<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$result = $conn->query("SELECT * FROM movies ORDER BY created_at DESC");
$movies = [];
while ($row = $result->fetch_assoc()) {
    $movies[] = $row;
}
header('Content-Type: application/json');
echo json_encode($movies);