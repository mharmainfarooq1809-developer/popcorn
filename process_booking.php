<?php
session_start();
require_once 'db_connect.php';

function points_history_has_column($conn, $column) {
    $column = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM points_history LIKE '$column'");
    return $res && $res->num_rows > 0;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'You must be logged in to book.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid request data']);
    exit;
}

$holderName = trim($data['holderName'] ?? '');
$adults = (int)($data['adults'] ?? 0);
$children = (int)($data['children'] ?? 0);
$total = (float)($data['total'] ?? 0);
$showtimeId = (int)($data['showtimeId'] ?? 0);
$seats = trim($data['seatList'] ?? '');
$discountEligible = isset($data['discountEligible']) ? (int)$data['discountEligible'] : 0;

if (!$holderName || ($adults + $children) === 0 || !$showtimeId || empty($seats)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
    exit;
}

// Fetch showtime details including theatre price and movie title
$stmt = $conn->prepare("
    SELECT s.id, s.theatre, s.show_date, s.show_time, m.title, t.price
    FROM showtimes s
    JOIN movies m ON s.movie_id = m.id
    JOIN theatres t ON s.theatre = t.name   -- adjust if s.theatre is ID
    WHERE s.id = ?
");
$stmt->bind_param("i", $showtimeId);
$stmt->execute();
$showtime = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$showtime) {
    echo json_encode(['success' => false, 'error' => 'Showtime not found']);
    exit;
}

$theatrePrice = $showtime['price'];
$movieTitle = $showtime['title'];

// Calculate expected total based on server-side values
$expectedTotal = ($adults * $theatrePrice) + ($children * $theatrePrice * 0.5);
if ($discountEligible) {
    $expectedTotal *= 0.9;
}
$expectedTotal = round($expectedTotal, 2);

// Allow tiny rounding difference (e.g., 0.01)
if (abs($expectedTotal - $total) > 0.01) {
    echo json_encode(['success' => false, 'error' => 'Price mismatch. Please refresh and try again.']);
    exit;
}

// Check seat availability
$seatArray = explode(',', $seats);
foreach ($seatArray as $seat) {
    $seat = trim($seat);
    $check = $conn->prepare(
        "SELECT id FROM bookings WHERE showtime_id = ?
         AND status IN ('pending', 'confirmed')
         AND FIND_IN_SET(?, seats)"
    );
    $check->bind_param("is", $showtimeId, $seat);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => "Seat $seat is already taken."]);
        exit;
    }
    $check->close();
}

$pointsEarned = $adults + $children;

$conn->begin_transaction();

try {
    // Insert booking without holder_name
    $stmt = $conn->prepare(
        "INSERT INTO bookings
            (showtime_id, user_id, seats, adults, children, total_price, points_earned, discount_applied, status, booking_date)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', NOW())"
    );
    $stmt->bind_param("iisiiidi",
        $showtimeId,
        $user_id,
        $seats,
        $adults,
        $children,
        $total,
        $pointsEarned,
        $discountEligible
    );
    $stmt->execute();
    $bookingId = $conn->insert_id;
    $stmt->close();

    // Update user points
    $updatePoints = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
    $updatePoints->bind_param("ii", $pointsEarned, $user_id);
    $updatePoints->execute();
    $updatePoints->close();

    // Add to points history
    $reason = "Booking #" . $bookingId . " confirmed";
    if (points_history_has_column($conn, 'created_at')) {
        $history = $conn->prepare("INSERT INTO points_history (user_id, points_change, reason, created_at) VALUES (?, ?, ?, NOW())");
        $history->bind_param("iis", $user_id, $pointsEarned, $reason);
    } else {
        $history = $conn->prepare("INSERT INTO points_history (user_id, points_change, reason) VALUES (?, ?, ?)");
        $history->bind_param("iis", $user_id, $pointsEarned, $reason);
    }
    $history->execute();
    $history->close();

    // Create admin notification
    $notifMsg = "New booking for " . $conn->real_escape_string($movieTitle) . " (Booking #$bookingId)";
    $notifLink = "Admin_Dashboard/view_booking.php?id=" . $bookingId; // adjust
    $conn->query(
        "INSERT INTO notifications (user_id, type, message, link, is_read, created_at)
         VALUES (1, 'booking', '$notifMsg', '$notifLink', 0, NOW())"
    );

    $conn->commit();
    echo json_encode(['success' => true, 'booking_id' => $bookingId]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
