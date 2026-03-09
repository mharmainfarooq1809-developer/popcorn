<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$movie_id = isset($_POST['movie_id']) ? intval($_POST['movie_id']) : 0;

if (!$movie_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid movie ID']);
    exit;
}

// Check if already voted
$stmt = $conn->prepare("SELECT id FROM user_votes WHERE user_id = ? AND movie_id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database prepare error: ' . $conn->error]);
    exit;
}
$stmt->bind_param("ii", $user_id, $movie_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Already voted']);
    $stmt->close();
    exit;
}
$stmt->close();

// Insert vote
$stmt = $conn->prepare("INSERT INTO user_votes (user_id, movie_id) VALUES (?, ?)");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database prepare error: ' . $conn->error]);
    exit;
}
$stmt->bind_param("ii", $user_id, $movie_id);
if ($stmt->execute()) {
    $count = $conn->query("SELECT COUNT(*) FROM user_votes WHERE movie_id = $movie_id")->fetch_row()[0];
    echo json_encode(['success' => true, 'votes' => (int)$count]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
}
$stmt->close();
?>