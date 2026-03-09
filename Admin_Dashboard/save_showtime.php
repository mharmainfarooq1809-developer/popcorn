<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$movie_id = isset($_POST['movie_id']) ? intval($_POST['movie_id']) : 0;
$theatre = trim($_POST['theatre']);
$show_date = $_POST['show_date'];
$show_time = $_POST['show_time'];

if (!$movie_id || empty($theatre) || empty($show_date) || empty($show_time)) {
    echo json_encode(['success' => false, 'error' => 'All fields required.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO showtimes (movie_id, theatre, show_date, show_time, status) VALUES (?, ?, ?, ?, 'active')");
$stmt->bind_param("isss", $movie_id, $theatre, $show_date, $show_time);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}