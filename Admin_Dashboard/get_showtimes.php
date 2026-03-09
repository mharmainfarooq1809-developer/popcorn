<?php
require_once 'db_connect.php';

$movie_id = intval($_GET['movie_id'] ?? 0);
if (!$movie_id) {
    echo json_encode([]);
    exit;
}

// Join with theatres to get the price. Adjust join condition if showtimes.theatre is ID.
$stmt = $conn->prepare("
    SELECT 
        s.id, 
        s.show_date, 
        s.show_time, 
        s.theatre, 
        s.status, 
        t.price
    FROM showtimes s
    JOIN theatres t ON s.theatre = t.name   -- change to t.id if s.theatre stores ID
    WHERE s.movie_id = ? 
      AND s.status = 'active' 
      AND (s.show_date > CURDATE() OR (s.show_date = CURDATE() AND s.show_time >= CURTIME()))
    ORDER BY s.show_date, s.show_time
");
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$result = $stmt->get_result();

$showtimes = [];
while ($row = $result->fetch_assoc()) {
    $showtimes[] = $row;
}

echo json_encode($showtimes);
?>