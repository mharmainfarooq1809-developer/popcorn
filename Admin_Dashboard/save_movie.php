<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$sessionRole = strtolower(trim((string)($_SESSION['user_role'] ?? '')));
$isAdmin = ($userId > 0 && $sessionRole === 'admin');

// Fallback: if role is missing in session, verify directly from DB once.
if (!$isAdmin && $userId > 0) {
    $roleStmt = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    if ($roleStmt) {
        $roleStmt->bind_param("i", $userId);
        $roleStmt->execute();
        $roleRes = $roleStmt->get_result();
        if ($roleRow = $roleRes->fetch_assoc()) {
            $dbRole = strtolower(trim((string)($roleRow['role'] ?? '')));
            if ($dbRole === 'admin') {
                $_SESSION['user_role'] = 'admin';
                $isAdmin = true;
            }
        }
        $roleStmt->close();
    }
}

if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$title = trim($_POST['title']);
$category = trim($_POST['category']);
$genre = trim($_POST['genre']);
$language = trim($_POST['language']);
$image_url = trim($_POST['image_url']);
$trailer_url = trim($_POST['trailer_url']);
$is_premium = isset($_POST['is_premium']) ? 1 : 0;

if (empty($title) || empty($category) || empty($genre) || empty($language) || empty($image_url)) {
    echo json_encode(['success' => false, 'error' => 'All fields except trailer are required.']);
    exit;
}

if ($id) {
    $stmt = $conn->prepare("UPDATE movies SET title=?, category=?, genre=?, language=?, image_url=?, trailer_url=?, is_premium=? WHERE id=?");
    $stmt->bind_param("ssssssii", $title, $category, $genre, $language, $image_url, $trailer_url, $is_premium, $id);
} else {
    $stmt = $conn->prepare("INSERT INTO movies (title, category, genre, language, image_url, trailer_url, is_premium) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $title, $category, $genre, $language, $image_url, $trailer_url, $is_premium);
}

if ($stmt->execute()) {
    // if we just inserted (id was 0), add notification
    if ($id === 0) {
        $movie_id = $conn->insert_id;
        $movie_title = $conn->real_escape_string($title);
        $notif_msg = "New movie added: " . $movie_title;
        $notif_link = "Admin_Dashboard/edit_movie.php?id=" . $movie_id;
        $conn->query("INSERT INTO notifications (type, message, link, created_at) VALUES ('movie', '$notif_msg', '$notif_link', NOW())");
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

