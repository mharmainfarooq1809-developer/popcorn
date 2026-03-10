<?php
session_start();
require_once 'db_connect.php';
require_once 'settings_init.php'; // load global settings

$public_pages = ['login.php', 'register.php', 'maintenance.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (($settings['maintenance_mode'] ?? '0') === '1' && !in_array($current_page, $public_pages, true)) {
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
        header("Location: /eproject2/maintenance.php");
        exit;
    }
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch current points
$stmt = $conn->prepare("SELECT points FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$current_points = $user['points'];
$discount_eligible = ($current_points >= 10);
$points_to_next = 10 - ($current_points % 10);
$progress_percent = ($current_points % 10) * 10;

// Fetch only movies that have at least one active upcoming showtime
$movies_result = $conn->query("
    SELECT DISTINCT m.id, m.title, m.image_url, m.category, m.language, m.is_premium
    FROM movies m
    INNER JOIN showtimes s ON m.id = s.movie_id
    WHERE s.status = 'active' 
      AND (s.show_date > CURDATE() OR (s.show_date = CURDATE() AND s.show_time >= CURTIME()))
    ORDER BY m.title
");

$movies = [];
while ($row = $movies_result->fetch_assoc()) {
    $movies[] = $row;
}

$no_movies = empty($movies);
$default_movie = null;
$default_showtimes = [];

if (!$no_movies) {
    $default_movie = $movies[0];
    $default_movie_id = $default_movie['id'];

    // Fetch showtimes with theatre price – adjust join condition if needed
    $showtimes_query = $conn->prepare("
        SELECT s.*, t.price
        FROM showtimes s
        LEFT JOIN theatres t ON s.theatre = t.name   -- change to t.id if needed
        WHERE s.movie_id = ? 
          AND s.status = 'active' 
          AND (s.show_date > CURDATE() OR (s.show_date = CURDATE() AND s.show_time >= CURTIME()))
        ORDER BY s.show_date, s.show_time
    ");
    $showtimes_query->bind_param("i", $default_movie_id);
    $showtimes_query->execute();
    $showtimes_result = $showtimes_query->get_result();
    while ($st = $showtimes_result->fetch_assoc()) {
        // Ensure price is set
        if ($st['price'] === null) $st['price'] = 15.00;
        $default_showtimes[] = $st;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking · <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
    <?php if (!empty($settings['theme_color'])): ?>
        <style>
            :root { --primary: <?= htmlspecialchars($settings['theme_color']) ?>; }
            .btn-primary { background: linear-gradient(145deg, var(--primary), var(--primary-dark)); }
        </style>
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ================= BASE & PARALLAX BACKGROUND ================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Heebo', sans-serif;
            background-color: #0B1623;
            color: #F2F2F2;
            line-height: 1.6;
            overflow-x: hidden;
            position: relative;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?q=80&w=2070&auto=format&fit=crop') center/cover no-repeat;
            filter: brightness(0.5) blur(4px);
            z-index: -2;
        }

        body::after {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(11, 22, 35, 0.75);
            z-index: -1;
        }

        :root {
            --dark-navy: #0B1623;
            --deep-navy: #0F1C2B;
            --white: #F2F2F2;
            --popcorn-gold: #FFD966;
            --popcorn-orange: #FFA500;
            --dark-orange: #cc7f00;
            --light-gold: #ffe68f;
            --dark-gray: #1F2732;
            --gray-1: #3A414D;
            --gray-2: #555C68;
            --gray-3: #7A808A;
            --light-gray: #A7ADB6;
            --very-light-gray: #C9CED6;
            --error: #ff4d4d;
            --success: #4CAF50;
            --seat-available: #2a323f;
            --seat-pending: #ffaa00;
            --seat-booked: #d32f2f;
            --seat-selected: #4CAF50;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 30px;
            width: 100%;
        }

        /* ================= FLOATING NAVBAR ================= */
        header {
            position: fixed;
            width: 100%;
            top: 0;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            background: rgba(15, 28, 43, 0.3);
            padding: 18px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            transition: all 0.4s ease;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        header.scrolled {
            padding: 12px 40px;
            background: rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .logo {
            font-size: 28px;
            font-weight: 800;
            color: var(--popcorn-gold);
            letter-spacing: 2px;
            text-transform: uppercase;
            text-decoration: none;
            transition: color 0.3s;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        .logo:hover {
            color: var(--popcorn-orange);
        }

        /* Mobile menu toggle */
        .menu-toggle {
            display: none;
            font-size: 24px;
            color: var(--white);
            cursor: pointer;
        }

        .nav-links {
            display: flex;
            gap: 32px;
            flex-wrap: wrap;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            font-weight: 500;
            color: var(--white);
            transition: all 0.3s ease;
            position: relative;
            padding: 4px 0;
            font-size: 16px;
            letter-spacing: 0.3px;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0%;
            height: 2px;
            background: var(--popcorn-gold);
            transition: width 0.3s;
        }

        .nav-links a:hover {
            color: var(--popcorn-orange);
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .nav-links a.active {
            color: var(--popcorn-orange);
        }

        .nav-links a.active::after {
            width: 100%;
        }

        .btn {
            display: inline-block;
            padding: 10px 28px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
        }

        .btn-primary {
            background: linear-gradient(145deg, var(--popcorn-orange), var(--dark-orange));
            color: var(--white);
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 165, 0, 0.5);
        }

        .btn-outline {
            background: transparent;
            color: var(--popcorn-gold);
            border: 2px solid var(--popcorn-orange);
        }

        .btn-outline:hover {
            background: var(--popcorn-orange);
            color: white;
            border-color: var(--popcorn-orange);
            transform: translateY(-3px);
        }

        .btn-small {
            padding: 6px 18px;
            font-size: 14px;
        }

        /* Booking card */
        .booking-card {
            background: rgba(15, 28, 43, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            padding: 40px;
            margin: 120px 0 50px;
            border: 1px solid var(--gray-1);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
        }

        .booking-title {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 30px;
            color: var(--white);
            position: relative;
            padding-left: 20px;
        }

        .booking-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 8px;
            bottom: 8px;
            width: 6px;
            background: var(--popcorn-orange);
            border-radius: 4px;
        }

        /* ========== PROFESSIONAL MOVIE DROPDOWN ========== */
        .movie-selector {
            margin-bottom: 30px;
            position: relative;
        }

        .movie-selector label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--light-gray);
            font-size: 18px;
        }

        .custom-dropdown {
            position: relative;
            width: 100%;
            cursor: pointer;
        }

        .dropdown-selected {
            display: flex;
            align-items: center;
            gap: 15px;
            background: var(--dark-gray);
            border: 2px solid var(--gray-1);
            border-radius: 60px;
            padding: 10px 20px 10px 10px;
            transition: all 0.3s;
        }

        .dropdown-selected:hover {
            border-color: var(--popcorn-orange);
        }

        .selected-poster {
            width: 50px;
            height: 70px;
            border-radius: 12px;
            overflow: hidden;
            background: var(--gray-2);
            flex-shrink: 0;
        }

        .selected-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .selected-info {
            flex: 1;
        }

        .selected-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--white);
        }

        .selected-language {
            font-size: 14px;
            color: var(--light-gray);
        }

        .dropdown-arrow {
            font-size: 24px;
            color: var(--popcorn-orange);
            transition: transform 0.3s;
        }

        .dropdown-selected.open .dropdown-arrow {
            transform: rotate(180deg);
        }

        .dropdown-options {
            position: absolute;
            top: calc(100% + 10px);
            left: 0;
            width: 100%;
            background: var(--dark-gray);
            border: 2px solid var(--gray-1);
            border-radius: 30px;
            padding: 10px;
            max-height: 350px;
            overflow-y: auto;
            z-index: 100;
            display: none;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
        }

        .dropdown-options.show {
            display: block;
        }

        .dropdown-option {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px;
            border-radius: 50px;
            transition: background 0.2s;
            cursor: pointer;
            margin-bottom: 5px;
        }

        .dropdown-option:hover {
            background: rgba(255, 165, 0, 0.2);
        }

        .dropdown-option.selected {
            background: rgba(255, 165, 0, 0.4);
            border: 1px solid var(--popcorn-orange);
        }

        .option-poster {
            width: 40px;
            height: 56px;
            border-radius: 8px;
            overflow: hidden;
            background: var(--gray-2);
            flex-shrink: 0;
        }

        .option-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .option-info {
            flex: 1;
        }

        .option-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--white);
        }

        .option-language {
            font-size: 13px;
            color: var(--light-gray);
        }

        #movieSelect {
            display: none;
        }

        /* Movie summary */
        .movie-summary {
            display: flex;
            gap: 25px;
            padding: 25px;
            background: var(--dark-gray);
            border-radius: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            border: 1px solid var(--gray-1);
            transition: 0.3s;
        }

        .movie-summary:hover {
            border-color: var(--popcorn-orange);
        }

        .movie-summary-poster {
            width: 100px;
            height: 140px;
            background: linear-gradient(145deg, var(--gray-2), var(--gray-1));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-3);
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            flex-shrink: 0;
            border: 2px solid transparent;
            transition: border-color 0.3s;
            overflow: hidden;
        }

        .movie-summary-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .movie-summary-info h3 {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 10px;
            color: var(--white);
        }

        .movie-summary-info p {
            color: var(--light-gray);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .movie-summary-info i {
            color: var(--popcorn-orange);
            width: 20px;
        }

        /* Section styles */
        .section-subtitle {
            font-size: 24px;
            font-weight: 700;
            margin: 40px 0 20px;
            color: var(--white);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-subtitle i {
            color: var(--popcorn-orange);
            font-size: 24px;
        }

        /* Date/time selection */
        .datetime-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .datetime-card {
            background: var(--dark-gray);
            border: 2px solid var(--gray-1);
            border-radius: 16px;
            padding: 16px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        .datetime-card:hover {
            border-color: var(--popcorn-orange);
            transform: translateY(-2px);
        }

        .datetime-card.selected {
            border-color: var(--popcorn-orange);
            background: rgba(255, 165, 0, 0.1);
            box-shadow: 0 0 15px rgba(255, 165, 0, 0.3);
        }

        .datetime-card .date {
            font-size: 18px;
            font-weight: 700;
            color: var(--white);
        }

        .datetime-card .time {
            font-size: 24px;
            font-weight: 800;
            color: var(--popcorn-orange);
            margin: 8px 0;
        }

        .datetime-card .theatre {
            font-size: 14px;
            color: var(--light-gray);
        }

        /* Ticket selector (Adults/Children) */
        .ticket-panel {
            background: var(--dark-gray);
            border-radius: 20px;
            padding: 25px;
            margin: 20px 0 30px;
            border: 1px solid var(--gray-1);
        }

        .ticket-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }

        .ticket-type {
            display: flex;
            align-items: center;
            gap: 20px;
            flex: 1;
            min-width: 250px;
        }

        .ticket-type label {
            font-weight: 600;
            color: var(--light-gray);
            min-width: 100px;
        }

        .ticket-type input {
            width: 80px;
            padding: 10px;
            background: var(--deep-navy);
            border: 1px solid var(--gray-1);
            border-radius: 10px;
            color: var(--white);
            font-size: 16px;
            text-align: center;
        }

        .ticket-type input:focus {
            outline: none;
            border-color: var(--popcorn-orange);
        }

        .discount-note {
            background: rgba(255, 165, 0, 0.1);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 14px;
            color: var(--popcorn-gold);
            border: 1px solid var(--popcorn-orange);
        }

        .validation-error {
            color: var(--error);
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }

        /* ================= SEAT GRID (PREMIUM) ================= */
        .seat-container {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 24px;
            padding: 30px 20px;
            margin: 20px 0;
        }

        .screen {
            background: linear-gradient(180deg, var(--popcorn-orange) 0%, transparent 100%);
            height: 60px;
            width: 80%;
            margin: 0 auto 30px;
            border-radius: 50% 50% 0 0;
            text-align: center;
            line-height: 60px;
            font-weight: 600;
            color: var(--white);
            text-transform: uppercase;
            letter-spacing: 4px;
            box-shadow: 0 -10px 20px rgba(255, 165, 0, 0.3);
        }

        .legend {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 6px;
        }

        .legend-color.available {
            background: var(--seat-available);
        }

        .legend-color.selected {
            background: var(--seat-selected);
        }

        .legend-color.pending {
            background: var(--seat-pending);
        }

        .legend-color.booked {
            background: var(--seat-booked);
        }

        .seat-grid {
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: center;
        }

        .seat-row {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .seat {
            width: 50px;
            height: 50px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 12px 12px 8px 8px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: linear-gradient(145deg, var(--seat-available), #1e2632);
            color: var(--white);
            box-shadow: 0 4px 0 #0f1219, 0 6px 8px rgba(0, 0, 0, 0.4);
            position: relative;
        }

        .seat i {
            font-size: 18px;
            margin-bottom: 2px;
            color: rgba(255, 255, 255, 0.7);
        }

        .seat span {
            line-height: 1;
        }

        .seat.pending {
            background: linear-gradient(145deg, var(--seat-pending), #cc8800);
            color: #000;
            cursor: not-allowed;
            box-shadow: 0 4px 0 #996600;
        }

        .seat.booked {
            background: linear-gradient(145deg, var(--seat-booked), #a52424);
            cursor: not-allowed;
            box-shadow: 0 4px 0 #7a1b1b;
        }

        .seat.selected {
            background: linear-gradient(145deg, var(--seat-selected), #2e7d32);
            border-color: var(--white);
            transform: scale(1.05);
            box-shadow: 0 4px 0 #1b5e20, 0 8px 12px rgba(0, 0, 0, 0.5);
        }

        .seat.available:hover {
            background: linear-gradient(145deg, #3a4555, #2a323f);
            transform: scale(1.05);
        }

        .aisle {
            width: 30px;
        }

        .selected-counter {
            text-align: center;
            margin: 15px 0;
            color: var(--light-gray);
            font-size: 16px;
        }

        /* Points section */
        .points-section {
            background: var(--dark-gray);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .points-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .points-header span:first-child i {
            color: var(--popcorn-orange);
            margin-right: 8px;
        }

        .points-header .points-value {
            font-size: 24px;
            font-weight: 800;
            color: var(--popcorn-gold);
        }

        .points-message {
            background: rgba(255, 165, 0, 0.2);
            border-left: 4px solid var(--popcorn-orange);
            padding: 10px 16px;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .progress-bar-container {
            width: 100%;
            height: 8px;
            background: var(--gray-1);
            border-radius: 4px;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--popcorn-orange), var(--popcorn-gold));
            border-radius: 4px;
        }

        /* Total price */
        .total-section {
            background: linear-gradient(145deg, var(--deep-navy), var(--dark-navy));
            border-radius: 20px;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin: 30px 0;
            border: 1px solid var(--gray-1);
        }

        .total-label {
            font-size: 20px;
            font-weight: 600;
            color: var(--light-gray);
        }

        .total-amount {
            font-size: 42px;
            font-weight: 800;
            color: var(--popcorn-gold);
        }

        /* Payment methods */
        .payment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .payment-card {
            background: var(--dark-gray);
            border: 2px solid var(--gray-1);
            border-radius: 16px;
            padding: 18px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        .payment-card:hover {
            border-color: var(--popcorn-orange);
        }

        .payment-card.selected {
            border-color: var(--popcorn-orange);
            background: rgba(255, 165, 0, 0.1);
        }

        .payment-card i {
            font-size: 32px;
            color: var(--popcorn-orange);
            margin-bottom: 8px;
        }

        .payment-card span {
            display: block;
            font-weight: 600;
            color: var(--white);
        }

        .payment-details {
            background: var(--dark-gray);
            border-radius: 20px;
            padding: 25px;
            margin: 20px 0;
            border: 1px solid var(--gray-1);
            display: none;
        }

        .payment-details.active {
            display: block;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--light-gray);
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 18px;
            background: var(--deep-navy);
            border: 1px solid var(--gray-1);
            border-radius: 12px;
            color: var(--white);
            font-family: 'Heebo', sans-serif;
            font-size: 16px;
        }

        .form-group select {
            cursor: pointer;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--popcorn-orange);
        }

        .form-group input.error {
            border-color: var(--error);
        }

        /* Expiry row */
        .expiry-row {
            display: flex;
            gap: 10px;
        }

        .expiry-row select {
            flex: 1;
        }

        /* Timer bar */
        .timer-container {
            margin: 20px 0;
            display: none;
        }

        .timer-bar {
            width: 100%;
            height: 10px;
            background: var(--gray-1);
            border-radius: 10px;
            overflow: hidden;
        }

        .timer-progress {
            height: 100%;
            width: 100%;
            background: linear-gradient(90deg, var(--popcorn-orange), var(--popcorn-gold));
            transition: width 1s linear;
        }

        .timer-text {
            text-align: center;
            margin-top: 8px;
            color: var(--light-gray);
            font-size: 16px;
        }

        .terms {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 25px 0;
        }

        .terms input {
            width: 20px;
            height: 20px;
            accent-color: var(--popcorn-orange);
        }

        .terms label {
            color: var(--light-gray);
        }

        .terms a {
            color: var(--popcorn-gold);
            text-decoration: none;
        }

        .booking-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
        }

        footer {
            text-align: center;
            padding: 30px;
            color: var(--gray-2);
            border-top: 1px solid var(--gray-1);
            margin-top: 60px;
        }

        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s, transform 0.8s;
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        body.fade-out {
            opacity: 0;
            transition: opacity 0.6s ease;
        }

        /* ================= RESPONSIVE ================= */
        @media (max-width: 768px) {
            header {
                padding: 12px 20px;
            }

            header.scrolled {
                padding: 8px 20px;
            }

            .menu-toggle {
                display: block;
            }

            .nav-links {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: rgba(15, 28, 43, 0.95);
                backdrop-filter: blur(12px);
                flex-direction: column;
                align-items: center;
                gap: 15px;
                padding: 20px;
                border-bottom: 1px solid var(--gray-1);
                transform: translateY(-100%);
                opacity: 0;
                visibility: hidden;
                transition: all 0.4s;
                pointer-events: none;
            }

            .nav-links.active {
                transform: translateY(0);
                opacity: 1;
                visibility: visible;
                pointer-events: all;
            }

            .seat {
                width: 40px;
                height: 40px;
                font-size: 10px;
            }

            .seat i {
                font-size: 14px;
            }
        }

        /* logo image sizing */
        .logo img { max-height: 60px; width: auto; display: block; }
        @media (max-width: 768px) { .logo img { max-height: 45px; } }
    </style>
</head>

<body>
    <!-- Floating Navbar with Burger Menu -->
    <header id="navbar">
        <a href="first_page.php" class="logo">
            <?php if (!empty($settings['site_logo'])): ?>
                <img src="<?= htmlspecialchars($settings['site_logo']) ?>" alt="<?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?>">
            <?php else: ?>
                <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?>
            <?php endif; ?>
        </a>
        <div class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></div>
        <nav class="nav-links" id="navLinks">
            <a href="first_page.php">Home</a>
            <a href="showtimes.php">Showtimes</a>
            <a href="theatre.php">Theatres</a>
            <a href="booking.php" class="active">Booking</a>
            <a href="about.php">About</a>
            <a href="user_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <div class="booking-card reveal">
            <?php if ($no_movies): ?>
                <!-- Friendly message when no movies are available -->
                <div style="text-align: center; padding: 40px 20px;">
                    <i class="fas fa-film" style="font-size: 60px; color: var(--popcorn-orange); margin-bottom: 20px;"></i>
                    <h2 style="font-size: 28px; margin-bottom: 15px;">No movies available for booking</h2>
                    <p style="color: var(--light-gray); margin-bottom: 30px;">There are no showtimes scheduled at the
                        moment. Please check back later or explore our other sections.</p>
                    <a href="first_page.php" class="btn btn-primary">Go to Homepage</a>
                </div>
            <?php else: ?>
                <h2 class="booking-title">Complete Your Booking</h2>

                <!-- Loyalty Points Display -->
                <div class="points-section">
                    <div class="points-header">
                        <span><i class="fas fa-star"></i> Your Points</span>
                        <span class="points-value"><?= $current_points ?></span>
                    </div>
                    <?php if ($discount_eligible): ?>
                        <div class="points-message">
                            <i class="fas fa-gift"></i> Congratulations! You have a 10% discount on this booking!
                        </div>
                    <?php else: ?>
                        <div style="margin-bottom: 5px;"><?= $points_to_next ?> more points until next discount</div>
                        <div class="progress-bar-container">
                            <div class="progress-bar" style="width: <?= $progress_percent ?>%;"></div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Movie selector - Professional custom dropdown -->
                <div class="movie-selector">
                    <label><i class="fas fa-film" style="color: var(--popcorn-orange); margin-right: 8px;"></i> Select
                        Movie</label>

                    <!-- Hidden native select (kept for functionality) -->
                    <select id="movieSelect">
                        <?php foreach ($movies as $movie): ?>
                            <option value="<?= $movie['id'] ?>" <?= $movie['id'] == $default_movie_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($movie['title']) ?> (<?= htmlspecialchars($movie['language'] ?? 'N/A') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Custom dropdown -->
                    <div class="custom-dropdown" id="customMovieDropdown">
                        <div class="dropdown-selected" id="dropdownSelected">
                            <div class="selected-poster">
                                <img src="<?= htmlspecialchars($default_movie['image_url'] ?? '') ?>" alt="Poster"
                                    onerror="this.src='https://via.placeholder.com/50x70?text=No+Image'">
                            </div>
                            <div class="selected-info">
                                <div class="selected-title" id="selectedTitle">
                                    <?= htmlspecialchars($default_movie['title']) ?></div>
                                <div class="selected-language" id="selectedLanguage">
                                    <?= htmlspecialchars($default_movie['language'] ?? 'N/A') ?></div>
                            </div>
                            <i class="fas fa-chevron-down dropdown-arrow" id="dropdownArrow"></i>
                        </div>
                        <div class="dropdown-options" id="dropdownOptions">
                            <?php foreach ($movies as $movie): ?>
                                <div class="dropdown-option <?= $movie['id'] == $default_movie_id ? 'selected' : '' ?>"
                                    data-id="<?= $movie['id'] ?>" data-title="<?= htmlspecialchars($movie['title']) ?>"
                                    data-language="<?= htmlspecialchars($movie['language'] ?? 'N/A') ?>"
                                    data-image="<?= htmlspecialchars($movie['image_url'] ?? '') ?>">
                                    <div class="option-poster">
                                        <img src="<?= htmlspecialchars($movie['image_url'] ?? '') ?>" alt="Poster"
                                            onerror="this.src='https://via.placeholder.com/40x56?text=No+Image'">
                                    </div>
                                    <div class="option-info">
                                        <div class="option-title"><?= htmlspecialchars($movie['title']) ?></div>
                                        <div class="option-language"><?= htmlspecialchars($movie['language'] ?? 'N/A') ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Movie summary (dynamic) -->
                <div class="movie-summary" id="movieSummary">
                    <div class="movie-summary-poster" id="moviePoster">
                        <?php if (!empty($default_movie['image_url'])): ?>
                            <img src="<?= htmlspecialchars($default_movie['image_url']) ?>" alt="Poster">
                        <?php else: ?>
                            NO IMAGE
                        <?php endif; ?>
                    </div>
                    <div class="movie-summary-info">
                        <h3 id="movieTitle"><?= htmlspecialchars($default_movie['title']) ?></h3>
                        <p><i class="fas fa-tag"></i> <span
                                id="movieCategory"><?= htmlspecialchars($default_movie['category'] ?? 'N/A') ?></span></p>
                        <p><i class="fas fa-map-pin"></i> <span
                                id="movieTheatre"><?= htmlspecialchars($default_showtimes[0]['theatre'] ?? 'Select a showtime') ?></span>
                        </p>
                        <p><i class="fas fa-language"></i> <span
                                id="movieLanguage"><?= htmlspecialchars($default_movie['language'] ?? 'N/A') ?></span></p>
                    </div>
                </div>

                <!-- Ticket Holder Name -->
                <div class="section-subtitle"><i class="fas fa-user"></i> Ticket Holder</div>
                <div class="ticket-panel" style="padding: 20px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Full Name (as on ticket)</label>
                        <input type="text" id="ticketHolderName" placeholder="John Doe"
                            value="<?= htmlspecialchars($user_name) ?>">
                        <div id="name-error" class="validation-error">Please enter a valid name (letters only).</div>
                    </div>
                </div>

                <!-- Showtime selection (dynamic) -->
                <div class="section-subtitle"><i class="fas fa-calendar-alt"></i> Select Showtime</div>
                <div class="datetime-grid" id="showtimeGrid">
                    <?php foreach ($default_showtimes as $st): ?>
                        <div class="datetime-card <?= $st === $default_showtimes[0] ? 'selected' : '' ?>"
                            data-showtime-id="<?= $st['id'] ?>"
                            data-price="<?= $st['price'] ?? 15.00 ?>"
                            data-date="<?= htmlspecialchars(date('D, M j', strtotime($st['show_date']))) ?>"
                            data-time="<?= htmlspecialchars(date('g:i A', strtotime($st['show_time']))) ?>"
                            data-theatre="<?= htmlspecialchars($st['theatre'] ?? '') ?>" onclick="selectShowtime(this)">
                            <div class="date"><?= date('D, M j', strtotime($st['show_date'])) ?></div>
                            <div class="time"><?= date('g:i A', strtotime($st['show_time'])) ?></div>
                            <div class="theatre"><?= htmlspecialchars($st['theatre'] ?? '') ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="selectedShowtimeId" value="<?= $default_showtimes[0]['id'] ?? '' ?>">

                <!-- Ticket quantities (Adults/Children) -->
                <div class="section-subtitle"><i class="fas fa-ticket-alt"></i> Number of Tickets</div>
                <div class="ticket-panel">
                    <div class="ticket-row">
                        <div class="ticket-type">
                            <label>Adults (12+):</label>
                            <input type="number" id="adults" min="0" value="2" oninput="updateRequiredSeats()">
                        </div>
                        <div class="ticket-type">
                            <label>Children (3-12):</label>
                            <input type="number" id="children" min="0" value="0" oninput="updateRequiredSeats()">
                        </div>
                    </div>
                    <div class="discount-note">
                        <i class="fas fa-gift"></i> Children (3-12) get 50% discount on all seats!
                    </div>
                    <div id="ticket-error" class="validation-error">At least one ticket required.</div>
                </div>

                <!-- Seat Selection -->
                <div class="section-subtitle"><i class="fas fa-chair"></i> Select Your Seats</div>
                <div class="seat-container">
                    <div class="screen">SCREEN</div>
                    <div class="legend">
                        <div class="legend-item"><span class="legend-color available"></span> Available</div>
                        <div class="legend-item"><span class="legend-color selected"></span> Selected</div>
                        <div class="legend-item"><span class="legend-color pending"></span> Pending</div>
                        <div class="legend-item"><span class="legend-color booked"></span> Booked</div>
                    </div>
                    <div id="seat-grid" class="seat-grid">
                        <!-- Generated by JavaScript after showtime selection -->
                    </div>
                    <div id="selected-counter" class="selected-counter">Selected seats: 0 / <span
                            id="required-seats">2</span></div>
                    <div id="seat-error" class="validation-error" style="text-align:center;">Please select exactly <span
                            id="required-seats-error">2</span> seats.</div>
                </div>

                <!-- Total price -->
                <div class="total-section">
                    <span class="total-label">Total Amount:</span>
                    <span class="total-amount" id="totalAmount">$30.00</span>
                </div>

                <!-- Payment method -->
                <div class="section-subtitle"><i class="fas fa-credit-card"></i> Payment Method</div>
                <div class="payment-grid">
                    <div class="payment-card selected" data-payment="card">
                        <i class="fas fa-credit-card"></i>
                        <span>Credit Card</span>
                    </div>
                    <div class="payment-card" data-payment="jazzcash">
                        <i class="fas fa-mobile-alt"></i>
                        <span>JazzCash</span>
                    </div>
                    <div class="payment-card" data-payment="ewallet">
                        <i class="fas fa-wallet"></i>
                        <span>e-Wallet</span>
                    </div>
                </div>

                <!-- Payment details: Credit Card -->
                <div class="payment-details active" id="cardDetails">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Card Number</label>
                            <input type="text" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19"
                                oninput="formatCardNumber(this)">
                            <div id="card-error" class="validation-error">Invalid card number (16 digits).</div>
                        </div>
                        <div class="form-group">
                            <label>Cardholder Name</label>
                            <input type="text" id="cardName" placeholder="John Doe">
                            <div id="cardname-error" class="validation-error">Please enter a valid name.</div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <div class="expiry-row">
                                <select id="expiryMonth">
                                    <option value="">Month</option>
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?= sprintf('%02d', $m) ?>"><?= sprintf('%02d', $m) ?></option>
                                    <?php endfor; ?>
                                </select>
                                <select id="expiryYear">
                                    <option value="">Year</option>
                                    <?php for ($y = date('Y'); $y <= date('Y') + 10; $y++): ?>
                                        <option value="<?= $y ?>"><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div id="expiry-error" class="validation-error">Please select month and year.</div>
                        </div>
                        <div class="form-group">
                            <label>CVV</label>
                            <input type="text" id="cvv" placeholder="123" maxlength="4">
                            <div id="cvv-error" class="validation-error">CVV must be 3-4 digits.</div>
                        </div>
                    </div>
                </div>

                <!-- Payment details: JazzCash -->
                <div class="payment-details" id="jazzcashDetails">
                    <div class="form-group">
                        <label>JazzCash Number</label>
                        <input type="text" id="jazzcashNumber" placeholder="03XXXXXXXXX" maxlength="11">
                        <div id="jazzcash-error" class="validation-error">Enter a valid 11-digit JazzCash number (starting
                            with 03).</div>
                    </div>
                </div>

                <!-- Payment details: e-Wallet -->
                <div class="payment-details" id="ewalletDetails">
                    <div class="form-group">
                        <label>Select e-Wallet Provider</label>
                        <select id="ewalletProvider">
                            <option value="">Choose provider</option>
                            <option value="paytm">Paytm</option>
                            <option value="easypaisa">Easypaisa</option>
                            <option value="skrill">Skrill</option>
                            <option value="payoneer">Payoneer</option>
                        </select>
                        <div id="provider-error" class="validation-error">Please select a provider.</div>
                    </div>
                    <div class="form-group">
                        <label>Account ID / Email</label>
                        <input type="text" id="ewalletAccount" placeholder="account@example.com">
                        <div id="account-error" class="validation-error">Please enter a valid account ID.</div>
                    </div>
                </div>

                <!-- Timer display -->
                <div class="timer-container" id="timerContainer">
                    <div class="timer-bar">
                        <div class="timer-progress" id="timerProgress" style="width: 100%;"></div>
                    </div>
                    <div class="timer-text" id="timerText">Processing booking... 5s</div>
                </div>

                <!-- Terms and conditions -->
                <div class="terms">
                    <input type="checkbox" id="terms">
                    <label for="terms">I agree to the <a href="#">Terms & Conditions</a> and <a href="#">Cancellation
                            Policy</a></label>
                </div>

                <!-- Actions -->
                <div class="booking-actions">
                    <a href="showtimes.php" class="btn btn-outline">Cancel</a>
                    <button class="btn btn-primary" onclick="validateAndStartTimer()" id="confirmBtn">Confirm & Pay</button>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <p><?= htmlspecialchars($settings['footer_text'] ?? '© '.date('Y').' Popcorn Hub Cinemas. Secure payment • Best seat guarantee') ?></p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.27/bundled/lenis.min.js"></script>
    <script>
        // Lenis smooth scroll
        const lenis = new Lenis({ duration: 1.6, smooth: true, smoothTouch: true });
        function raf(time) { lenis.raf(time); requestAnimationFrame(raf); }
        requestAnimationFrame(raf);

        // ================= REVEAL ON SCROLL =================
        const reveals = document.querySelectorAll('.reveal');
        const navbar = document.getElementById('navbar');

        function checkReveals() {
            const windowHeight = window.innerHeight;
            reveals.forEach(el => {
                const elementTop = el.getBoundingClientRect().top;
                if (elementTop < windowHeight - 100) {
                    el.classList.add('active');
                }
            });
            if (window.scrollY > 50) navbar.classList.add('scrolled');
            else navbar.classList.remove('scrolled');
        }
        window.addEventListener('scroll', checkReveals);
        window.addEventListener('DOMContentLoaded', checkReveals);

        // ================= MOBILE MENU TOGGLE =================
        const menuToggle = document.getElementById('menuToggle');
        const navLinks = document.getElementById('navLinks');
        if (menuToggle && navLinks) {
            menuToggle.addEventListener('click', () => {
                navLinks.classList.toggle('active');
                menuToggle.innerHTML = navLinks.classList.contains('active') ? '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
            });
            // Close menu when a link is clicked
            navLinks.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    navLinks.classList.remove('active');
                    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                });
            });
        }

        <?php if (!$no_movies): ?>
            // ================= BOOKING LOGIC =================
            let selectedPayment = 'card';
            let timerInterval = null;
            let selectedSeats = [];
            let currentPrice = <?= $default_showtimes[0]['price'] ?? 15.00 ?>; // will be updated on showtime change
            const discountEligible = <?= json_encode($discount_eligible) ?>;

            // Movies array from PHP
            const movies = <?= json_encode($movies) ?>;
            console.log('Movies from PHP:', movies.map(m => ({ id: m.id, title: m.title })));

            // Current state
            let currentMovieId = <?= $default_movie_id ?>;
            let currentShowtimeId = <?= $default_showtimes[0]['id'] ?? 'null' ?>;

            console.log('Initial movie ID:', currentMovieId, 'Type:', typeof currentMovieId);
            console.log('Initial showtime ID:', currentShowtimeId);
            console.log('Initial price:', currentPrice);

            // Helper function to show a message in the seat area when no showtimes
            function showNoShowtimesMessage() {
                const seatGrid = document.getElementById('seat-grid');
                const seatContainer = document.querySelector('.seat-container');
                if (seatGrid) {
                    seatGrid.innerHTML = ''; // clear any seats
                }
                // Create a message element if it doesn't exist
                let msgEl = document.getElementById('no-showtime-msg');
                if (!msgEl) {
                    msgEl = document.createElement('div');
                    msgEl.id = 'no-showtime-msg';
                    msgEl.className = 'text-muted text-center py-5';
                    msgEl.style.padding = '40px 20px';
                    msgEl.innerHTML = '<i class="fas fa-calendar-times fa-3x mb-3"></i><br>No showtimes available for this movie. Please select another movie.';
                    seatContainer?.appendChild(msgEl);
                }
                // Hide the seat grid (if any) and show the message
                if (seatGrid) seatGrid.style.display = 'none';
                msgEl.style.display = 'block';
            }

            // Helper function to restore seat grid after showtimes are loaded
            function hideNoShowtimesMessage() {
                const msgEl = document.getElementById('no-showtime-msg');
                const seatGrid = document.getElementById('seat-grid');
                if (msgEl) msgEl.style.display = 'none';
                if (seatGrid) seatGrid.style.display = 'flex'; // or 'block' depending on your layout
            }

            // ================= FUNCTION DEFINITIONS =================
            function loadSeats(showtimeId) {
                console.log('Loading seats for showtime:', showtimeId);
                hideNoShowtimesMessage(); // ensure message is hidden
                fetch('get_seats.php?showtime_id=' + showtimeId)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Seat data received:', data);
                        generateSeats(data.booked || [], data.pending || []);
                    })
                    .catch(error => {
                        console.error('Error loading seats, using default empty grid:', error);
                        generateSeats([], []);
                    });
            }

            function generateSeats(bookedSeats = [], pendingSeats = []) {
                const rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
                const seatsPerRow = 10;
                const grid = document.getElementById('seat-grid');
                grid.innerHTML = '';

                for (let r of rows) {
                    const rowDiv = document.createElement('div');
                    rowDiv.className = 'seat-row';
                    for (let i = 1; i <= seatsPerRow; i++) {
                        if (i === 5 || i === 9) {
                            const aisle = document.createElement('span');
                            aisle.className = 'aisle';
                            aisle.innerHTML = '&nbsp;&nbsp;&nbsp;';
                            rowDiv.appendChild(aisle);
                        }
                        const seatId = r + i;
                        const seat = document.createElement('div');
                        seat.className = 'seat';
                        seat.dataset.seatId = seatId;
                        seat.innerHTML = `<i class="fas fa-chair"></i><span>${seatId}</span>`;

                        if (bookedSeats.includes(seatId)) {
                            seat.classList.add('booked');
                        } else if (pendingSeats.includes(seatId)) {
                            seat.classList.add('pending');
                        } else {
                            seat.classList.add('available');
                            seat.addEventListener('click', toggleSeat);
                        }
                        rowDiv.appendChild(seat);
                    }
                    grid.appendChild(rowDiv);
                }
                selectedSeats = [];
                updateCounter();
            }

            function selectShowtime(card) {
                document.querySelectorAll('.datetime-card').forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                currentShowtimeId = parseInt(card.dataset.showtimeId);
                currentPrice = parseFloat(card.dataset.price) || 15.00; // update price
                document.getElementById('selectedShowtimeId').value = currentShowtimeId;
                document.getElementById('movieTheatre').innerText = card.dataset.theatre;
                console.log('Showtime selected, ID:', currentShowtimeId, 'Price:', currentPrice);
                loadSeats(currentShowtimeId);
                calculateTotal(); // recalculate total with new price
            }

            function getRequiredSeats() {
                const adults = parseInt(document.getElementById('adults').value) || 0;
                const children = parseInt(document.getElementById('children').value) || 0;
                return adults + children;
            }

            function updateRequiredSeats() {
                const required = getRequiredSeats();
                document.getElementById('required-seats').innerText = required;
                document.getElementById('required-seats-error').innerText = required;
                document.getElementById('ticket-error').style.display = required === 0 ? 'block' : 'none';
                updateCounter();
                calculateTotal();
            }

            function toggleSeat(e) {
                const seat = e.currentTarget;
                const seatId = seat.dataset.seatId;
                if (!seat.classList.contains('available')) return;

                if (seat.classList.contains('selected')) {
                    seat.classList.remove('selected');
                    selectedSeats = selectedSeats.filter(id => id !== seatId);
                } else {
                    const required = getRequiredSeats();
                    if (selectedSeats.length >= required) {
                        alert(`You can only select ${required} seat(s).`);
                        return;
                    }
                    seat.classList.add('selected');
                    selectedSeats.push(seatId);
                }
                updateCounter();
                calculateTotal();
            }

            function updateCounter() {
                const required = getRequiredSeats();
                document.getElementById('selected-counter').innerHTML = `Selected seats: ${selectedSeats.length} / <span id="required-seats">${required}</span>`;
                if (selectedSeats.length === required && required > 0) {
                    document.getElementById('seat-error').style.display = 'none';
                } else {
                    document.getElementById('seat-error').style.display = 'block';
                }
            }

            function calculateTotal() {
                const adults = parseInt(document.getElementById('adults').value) || 0;
                const children = parseInt(document.getElementById('children').value) || 0;
                if (adults < 0) document.getElementById('adults').value = 0;
                if (children < 0) document.getElementById('children').value = 0;
                let total = (adults * currentPrice) + (children * currentPrice * 0.5);
                if (discountEligible) {
                    total = total * 0.9;
                }
                // Optional: console.log('Calculating total: price='+currentPrice+', adults='+adults+', children='+children+', total='+total);
                document.getElementById('totalAmount').textContent = '$' + total.toFixed(2);
            }

            function formatCardNumber(input) {
                let value = input.value.replace(/\s/g, '').replace(/[^0-9]/g, '');
                let formatted = '';
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0) formatted += ' ';
                    formatted += value[i];
                }
                input.value = formatted;
            }

            function validateName(name) { return /^[A-Za-z\s]{2,50}$/.test(name); }
            function validateCardNumber(num) { return /^\d{16}$/.test(num.replace(/\s/g, '')); }
            function validateCVV(cvv) { return /^\d{3,4}$/.test(cvv); }
            function validateJazzCash(num) { return /^03\d{9}$/.test(num); }
            function validateEmail(email) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email); }

            function validateAll() {
                const holderName = document.getElementById('ticketHolderName').value.trim();
                if (!validateName(holderName)) {
                    document.getElementById('name-error').style.display = 'block';
                    return false;
                } else {
                    document.getElementById('name-error').style.display = 'none';
                }

                const adults = parseInt(document.getElementById('adults').value) || 0;
                const children = parseInt(document.getElementById('children').value) || 0;
                if (adults + children === 0) {
                    document.getElementById('ticket-error').style.display = 'block';
                    return false;
                } else {
                    document.getElementById('ticket-error').style.display = 'none';
                }

                const required = getRequiredSeats();
                if (selectedSeats.length !== required) {
                    document.getElementById('seat-error').style.display = 'block';
                    return false;
                } else {
                    document.getElementById('seat-error').style.display = 'none';
                }

                if (!document.getElementById('terms').checked) {
                    alert('Please agree to the Terms & Conditions.');
                    return false;
                }

                if (selectedPayment === 'card') {
                    const cardNum = document.getElementById('cardNumber').value.trim();
                    const cardName = document.getElementById('cardName').value.trim();
                    const month = document.getElementById('expiryMonth').value;
                    const year = document.getElementById('expiryYear').value;
                    const cvv = document.getElementById('cvv').value.trim();

                    let valid = true;
                    if (!validateCardNumber(cardNum)) {
                        document.getElementById('card-error').style.display = 'block';
                        valid = false;
                    } else {
                        document.getElementById('card-error').style.display = 'none';
                    }
                    if (!validateName(cardName)) {
                        document.getElementById('cardname-error').style.display = 'block';
                        valid = false;
                    } else {
                        document.getElementById('cardname-error').style.display = 'none';
                    }
                    if (!month || !year) {
                        document.getElementById('expiry-error').style.display = 'block';
                        valid = false;
                    } else {
                        document.getElementById('expiry-error').style.display = 'none';
                    }
                    if (!validateCVV(cvv)) {
                        document.getElementById('cvv-error').style.display = 'block';
                        valid = false;
                    } else {
                        document.getElementById('cvv-error').style.display = 'none';
                    }
                    if (!valid) return false;

                } else if (selectedPayment === 'jazzcash') {
                    const jazzNum = document.getElementById('jazzcashNumber').value.trim();
                    if (!validateJazzCash(jazzNum)) {
                        document.getElementById('jazzcash-error').style.display = 'block';
                        return false;
                    } else {
                        document.getElementById('jazzcash-error').style.display = 'none';
                    }

                } else if (selectedPayment === 'ewallet') {
                    const provider = document.getElementById('ewalletProvider').value;
                    const account = document.getElementById('ewalletAccount').value.trim();
                    let valid = true;
                    if (!provider) {
                        document.getElementById('provider-error').style.display = 'block';
                        valid = false;
                    } else {
                        document.getElementById('provider-error').style.display = 'none';
                    }
                    if (!account) {
                        document.getElementById('account-error').style.display = 'block';
                        valid = false;
                    } else {
                        document.getElementById('account-error').style.display = 'none';
                    }
                    if (!valid) return false;
                }
                return true;
            }

            function startTimer() {
                const timerContainer = document.getElementById('timerContainer');
                const timerProgress = document.getElementById('timerProgress');
                const timerText = document.getElementById('timerText');
                const confirmBtn = document.getElementById('confirmBtn');
                let seconds = 5;

                timerContainer.style.display = 'block';
                confirmBtn.disabled = true;
                confirmBtn.style.opacity = '0.5';

                timerInterval = setInterval(() => {
                    seconds--;
                    timerProgress.style.width = (seconds / 5) * 100 + '%';
                    timerText.innerText = `Processing booking... ${seconds}s`;

                    if (seconds <= 0) {
                        clearInterval(timerInterval);
                        timerContainer.style.display = 'none';
                        confirmBtn.disabled = false;
                        confirmBtn.style.opacity = '1';

                        const holderName = document.getElementById('ticketHolderName').value.trim();
                        const adults = document.getElementById('adults').value;
                        const children = document.getElementById('children').value;
                        const total = document.getElementById('totalAmount').textContent.replace('$', '');
                        const showtimeId = document.getElementById('selectedShowtimeId').value;
                        const seatList = selectedSeats.join(',');

                        fetch('process_booking.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                holderName,
                                adults,
                                children,
                                total,
                                showtimeId,
                                seatList,
                                discountEligible
                            })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    window.location.href = 'ticket.php?booking_id=' + data.booking_id;
                                } else {
                                    alert('Booking failed: ' + data.error);
                                }
                            })
                            .catch(error => {
                                alert('Error: ' + error);
                            });
                    }
                }, 1000);
            }

            function validateAndStartTimer() {
                if (validateAll()) startTimer();
            }

            // Payment method selection
            document.querySelectorAll('.payment-card').forEach(card => {
                card.addEventListener('click', function () {
                    document.querySelectorAll('.payment-card').forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedPayment = this.dataset.payment;

                    document.getElementById('cardDetails').classList.remove('active');
                    document.getElementById('jazzcashDetails').classList.remove('active');
                    document.getElementById('ewalletDetails').classList.remove('active');

                    if (selectedPayment === 'card') {
                        document.getElementById('cardDetails').classList.add('active');
                    } else if (selectedPayment === 'jazzcash') {
                        document.getElementById('jazzcashDetails').classList.add('active');
                    } else if (selectedPayment === 'ewallet') {
                        document.getElementById('ewalletDetails').classList.add('active');
                    }
                });
            });

            // ================= CUSTOM DROPDOWN LOGIC =================
            const dropdownSelected = document.getElementById('dropdownSelected');
            const dropdownOptions = document.getElementById('dropdownOptions');
            const dropdownArrow = document.getElementById('dropdownArrow');
            const selectedTitle = document.getElementById('selectedTitle');
            const selectedLanguage = document.getElementById('selectedLanguage');
            const selectedPoster = document.querySelector('.selected-poster img');
            const hiddenSelect = document.getElementById('movieSelect');

            dropdownSelected.addEventListener('click', function (e) {
                e.stopPropagation();
                dropdownOptions.classList.toggle('show');
                dropdownSelected.classList.toggle('open');
            });

            document.addEventListener('click', function (e) {
                if (!dropdownSelected.contains(e.target) && !dropdownOptions.contains(e.target)) {
                    dropdownOptions.classList.remove('show');
                    dropdownSelected.classList.remove('open');
                }
            });

            document.querySelectorAll('.dropdown-option').forEach(option => {
                option.addEventListener('click', function () {
                    const id = this.dataset.id;
                    const title = this.dataset.title;
                    const language = this.dataset.language;
                    const image = this.dataset.image;

                    selectedTitle.textContent = title;
                    selectedLanguage.textContent = language;
                    selectedPoster.src = image || 'https://via.placeholder.com/50x70?text=No+Image';

                    hiddenSelect.value = id;
                    updateMovie();

                    document.querySelectorAll('.dropdown-option').forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');

                    dropdownOptions.classList.remove('show');
                    dropdownSelected.classList.remove('open');
                });
            });

            // ================= AJAX-BASED UPDATE MOVIE =================
            function updateMovie() {
                const select = document.getElementById('movieSelect');
                currentMovieId = parseInt(select.value);
                console.log('updateMovie called for movie ID:', currentMovieId);

                const movie = movies.find(m => Number(m.id) === currentMovieId);
                if (!movie) {
                    console.error('Movie not found!');
                    return;
                }

                document.getElementById('movieTitle').innerText = movie.title;
                document.getElementById('movieCategory').innerText = movie.category || 'N/A';
                document.getElementById('movieLanguage').innerText = movie.language || 'N/A';
                document.getElementById('movieTheatre').innerText = 'Select a showtime';

                const posterDiv = document.getElementById('moviePoster');
                if (movie.image_url) {
                    posterDiv.innerHTML = `<img src="${movie.image_url}" alt="Poster">`;
                } else {
                    posterDiv.innerText = 'NO IMAGE';
                }

                // Fetch fresh showtimes for this movie (including price)
                fetch('get_showtimes.php?movie_id=' + currentMovieId)
                    .then(response => response.json())
                    .then(showtimes => {
                        console.log('Fetched showtimes for movie', currentMovieId, ':', showtimes);
                        const grid = document.getElementById('showtimeGrid');
                        grid.innerHTML = '';
                        if (showtimes.length === 0) {
                            // Show message in showtime grid
                            grid.innerHTML = '<p class="text-muted" style="text-align:center;">No upcoming showtimes for this movie.</p>';
                            document.getElementById('selectedShowtimeId').value = '';
                            currentShowtimeId = null;
                            // Clear seat grid and show friendly message
                            const seatGrid = document.getElementById('seat-grid');
                            seatGrid.innerHTML = ''; // clear any existing seats
                            showNoShowtimesMessage(); // display the no-showtime message
                            return;
                        } else {
                            // Hide the no-showtime message if visible
                            hideNoShowtimesMessage();
                        }
                        showtimes.forEach((st, index) => {
                            const card = document.createElement('div');
                            card.className = 'datetime-card' + (index === 0 ? ' selected' : '');
                            card.dataset.showtimeId = st.id;
                            card.dataset.price = st.price || 15.00;  // <-- store price
                            card.dataset.date = new Date(st.show_date).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
                            card.dataset.time = new Date('1970-01-01T' + st.show_time).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
                            card.dataset.theatre = st.theatre || '';
                            card.setAttribute('onclick', 'selectShowtime(this)');
                            card.innerHTML = `
                                <div class="date">${card.dataset.date}</div>
                                <div class="time">${card.dataset.time}</div>
                                <div class="theatre">${st.theatre || ''}</div>
                            `;
                            grid.appendChild(card);
                        });

                        // Select first showtime
                        const firstCard = grid.querySelector('.datetime-card');
                        if (firstCard) {
                            selectShowtime(firstCard);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching showtimes:', error);
                        document.getElementById('showtimeGrid').innerHTML = '<p class="text-muted">Error loading showtimes.</p>';
                    });
            }

            // ================= INITIALIZATION =================
            document.addEventListener('DOMContentLoaded', function () {
                console.log('DOM fully loaded. Initial movie ID:', currentMovieId);
                calculateTotal();
                updateRequiredSeats();
                if (currentShowtimeId) {
                    console.log('Loading initial showtime:', currentShowtimeId);
                    loadSeats(currentShowtimeId);
                } else {
                    // No showtimes for the default movie – show a friendly message in the seat area
                    showNoShowtimesMessage();
                }
            });

        <?php endif; ?>

        // ================= PAGE TRANSITIONS =================
        document.querySelectorAll("a").forEach(link => {
            link.addEventListener("click", function (e) {
                if (this.href && this.href.indexOf("#") === -1 && !this.hasAttribute('target')) {
                    e.preventDefault();
                    document.body.classList.add("fade-out");
                    setTimeout(() => { window.location = this.href; }, 600);
                }
            });
        });
    </script>
</body>
</html>
