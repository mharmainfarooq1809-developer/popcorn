<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Get 6 random movies from the movies table (including year)
$result = $conn->query("SELECT id, title, genre, year FROM movies ORDER BY RAND() LIMIT 6");
if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed: ' . $conn->error]);
    exit;
}

$movies = [];
while ($row = $result->fetch_assoc()) {
    $movies[] = $row;
}

$output = [];
foreach ($movies as $movie) {
    $movie_id = $movie['id'];
    $vote_count = $conn->query("SELECT COUNT(*) FROM user_votes WHERE movie_id = $movie_id")->fetch_row()[0];
    $user_voted = false;
    if ($user_id) {
        $check = $conn->prepare("SELECT id FROM user_votes WHERE user_id = ? AND movie_id = ?");
        if ($check) {
            $check->bind_param("ii", $user_id, $movie_id);
            $check->execute();
            $user_voted = $check->get_result()->num_rows > 0;
            $check->close();
        }
    }
    $output[] = [
        'id' => $movie_id,
        'title' => $movie['title'],
        'genre' => $movie['genre'],
        'year' => $movie['year'],
        'votes' => (int)$vote_count,
        'userVoted' => $user_voted
    ];
}

echo json_encode($output);
?>