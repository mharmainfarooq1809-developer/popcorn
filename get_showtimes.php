<?php
require_once 'db_connect.php';

$movie_id = intval($_GET['movie_id'] ?? 0);
if (!$movie_id) {
    echo json_encode([]);
    exit;
}

// Determine whether showtimes.theatre stores name or ID
// We'll assume it stores the theatre name (as in your sample). If it's an ID, change to t.id = s.theatre
$stmt = $conn->prepare("
    SELECT
        s.id,
        s.show_date,
        s.show_time,
        s.theatre,
        s.status,
        t.price
    FROM showtimes s
    LEFT JOIN theatres t ON s.theatre = t.name   -- change to t.id if s.theatre is ID
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
    // Ensure price is set (if no match, use default)
    if ($row['price'] === null) $row['price'] = 15.00;
    $showtimes[] = $row;
}

echo json_encode($showtimes);
?>