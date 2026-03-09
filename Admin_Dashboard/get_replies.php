<?php
session_start();
require_once '../db_connect.php'; // adjust path if needed

// Allow only GET requests (this is the correct method for fetching data)
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use GET.']);
    exit;
}

// Admin authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$feedback_id = intval($_GET['feedback_id'] ?? 0);
if (!$feedback_id) {
    echo json_encode(['replies' => []]);
    exit;
}

// Fetch replies for the given feedback
$stmt = $conn->prepare("SELECT reply_text, created_at FROM feedback_replies WHERE feedback_id = ? ORDER BY created_at ASC");
$stmt->bind_param('i', $feedback_id);
$stmt->execute();
$result = $stmt->get_result();

$replies = [];
while ($row = $result->fetch_assoc()) {
    $replies[] = $row;
}

// Return JSON
header('Content-Type: application/json');
echo json_encode(['replies' => $replies]);
?>