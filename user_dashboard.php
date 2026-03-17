<?php
session_start();
require_once 'db_connect.php';
require_once 'settings_init.php';
$public_pages = ['login.php', 'register.php', 'maintenance.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (($settings['maintenance_mode'] ?? '0') === '1' && !in_array($current_page, $public_pages, true)) {
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
        header("Location: /eproject2/maintenance.php");
        exit;
    }
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

function points_history_has_column($conn, $column) {
    $column = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM points_history LIKE '$column'");
    return $res && $res->num_rows > 0;
}

function points_history_order_by($conn) {
    if (points_history_has_column($conn, 'created_at')) return 'created_at';
    if (points_history_has_column($conn, 'id')) return 'id';
    return 'user_id';
}

// Handle ticket cancellation
if (isset($_POST['cancel_booking'])) {
    $booking_id = intval($_POST['booking_id']);

    // Start transaction
    $conn->begin_transaction();

    try {
        // Check if booking belongs to user and is not already cancelled
        $check = $conn->prepare("SELECT id, status, points_earned FROM bookings WHERE id = ? AND user_id = ?");
        $check->bind_param("ii", $booking_id, $user_id);
        $check->execute();
        $booking = $check->get_result()->fetch_assoc();

        if (!$booking) {
            throw new Exception("Booking not found or doesn't belong to you.");
        }

        if ($booking['status'] === 'cancelled') {
            throw new Exception("This booking is already cancelled.");
        }

        if ($booking['status'] !== 'confirmed') {
            throw new Exception("Only confirmed bookings can be cancelled.");
        }

        // Update booking status to cancelled
        $update = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        $update->bind_param("i", $booking_id);
        $update->execute();

        // Refund points (remove points earned from this booking)
        if ($booking['points_earned'] > 0) {
            $points_refund = -$booking['points_earned'];

            // Update user points
            $update_points = $conn->prepare("UPDATE users SET points = points - ? WHERE id = ?");
            $update_points->bind_param("ii", $booking['points_earned'], $user_id);
            $update_points->execute();

            // Add to points history
            $reason = "Booking #" . $booking_id . " cancelled - Refund";
            if (points_history_has_column($conn, 'created_at')) {
                $history = $conn->prepare("INSERT INTO points_history (user_id, points_change, reason, created_at) VALUES (?, ?, ?, NOW())");
                $history->bind_param("iis", $user_id, $points_refund, $reason);
            } else {
                $history = $conn->prepare("INSERT INTO points_history (user_id, points_change, reason) VALUES (?, ?, ?)");
                $history->bind_param("iis", $user_id, $points_refund, $reason);
            }
            $history->execute();
        }

        $conn->commit();
        $cancel_success = "Booking cancelled successfully. Points have been refunded.";

    } catch (Exception $e) {
        $conn->rollback();
        $cancel_error = $e->getMessage();
    }
}

// Get user points and name
$stmt = $conn->prepare("SELECT name, email, points FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get points history
$orderBy = points_history_order_by($conn);
$stmt = $conn->prepare("SELECT * FROM points_history WHERE user_id = ? ORDER BY $orderBy DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$history = $stmt->get_result();

// Get recent bookings with full details
$stmt = $conn->prepare("
    SELECT
        b.id AS booking_id,
        b.seats,
        b.adults,
        b.children,
        b.total_price,
        b.points_earned,
        b.status,
        b.booking_date,
        COALESCE(m.title, 'Movie unavailable') AS movie_title,
        COALESCE(s.show_date, 'N/A') AS show_date,
        COALESCE(s.show_time, 'N/A') AS show_time,
        COALESCE(s.theatre, 'N/A') AS theatre,
        s.id AS showtime_id
    FROM bookings b
    LEFT JOIN showtimes s ON b.showtime_id = s.id
    LEFT JOIN movies m ON s.movie_id = m.id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC
    LIMIT 10
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();

// Get movies the user has voted for
try {
    $votes = $conn->prepare("
        SELECT m.title, m.genre, m.year, v.created_at AS voted_at
        FROM movie_votes v
        JOIN movies m ON v.movie_id = m.id
        WHERE v.user_id = ?
        ORDER BY v.created_at DESC
    ");
    $votes->bind_param("i", $user_id);
    $votes->execute();
    $votedMovies = $votes->get_result();
} catch (mysqli_sql_exception $e) {
    $votedMovies = $conn->query("SELECT 1 WHERE 1=0");
}

// If user has points but no history, create initial history entry
if ($user['points'] > 0 && $history->num_rows == 0) {
    $initial_reason = "Welcome points / Account registration";
    if (points_history_has_column($conn, 'created_at')) {
        $insert_initial = $conn->prepare("INSERT INTO points_history (user_id, points_change, reason, created_at) VALUES (?, ?, ?, NOW())");
        $insert_initial->bind_param("iis", $user_id, $user['points'], $initial_reason);
    } else {
        $insert_initial = $conn->prepare("INSERT INTO points_history (user_id, points_change, reason) VALUES (?, ?, ?)");
        $insert_initial->bind_param("iis", $user_id, $user['points'], $initial_reason);
    }
    $insert_initial->execute();

    // Refresh history
    $orderBy = points_history_order_by($conn);
    $stmt = $conn->prepare("SELECT * FROM points_history WHERE user_id = ? ORDER BY $orderBy DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $history = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
    <?php if (!empty($settings['theme_color'])): ?>
        <style>
            :root {
                --theme-primary: <?= htmlspecialchars($settings['theme_color']) ?>
                ;
            }

            a,
            .btn-primary {
                color: var(--primary);
            }

            .btn-sm {
                background: var(--primary);
            }
        </style>
    <?php endif; ?>
    <!-- Google Fonts: Heebo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* ================= BASE & PARALLAX BACKGROUND ================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
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
            background: url('https://images.unsplash.com/photo-1444703686981-a3abbc4d4fe3') center/cover no-repeat;
            z-index: -2;
            transform: translateZ(0);
            filter: brightness(0.5) blur(4px);
        }

        body::after {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(11, 22, 35, 0.85);
            z-index: -1;
        }

        :root {
            --dark-navy: #0B1623;
            --deep-navy: #0F1C2B;
            --white: #F2F2F2;
            --popcorn-gold: #FFD966;
            --popcorn-orange: #FFA500;
            --popcorn-dark: #cc7f00;
            --dark-gray: #1F2732;
            --gray-1: #3A414D;
            --gray-2: #555C68;
            --gray-3: #7A808A;
            --light-gray: #A7ADB6;
            --very-light-gray: #C9CED6;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 120px 30px 60px;
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

        .logo img {
            max-height: 60px;
            width: auto;
            display: block;
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

        /* ================= ALERTS ================= */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border-left: 4px solid #4CAF50;
            color: #4CAF50;
        }

        .alert-danger {
            background: rgba(255, 77, 77, 0.2);
            border-left: 4px solid #ff4d4d;
            color: #ff4d4d;
        }

        .alert i {
            font-size: 20px;
        }

        .alert .close-btn {
            margin-left: auto;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.3s;
        }

        .alert .close-btn:hover {
            opacity: 1;
        }

        /* ================= CARDS ================= */
        .dashboard-card {
            background: rgba(15, 28, 43, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--gray-1);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s, border-color 0.3s;
        }

        .dashboard-card:hover {
            border-color: var(--popcorn-orange);
            transform: translateY(-2px);
        }

        .card-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--white);
            position: relative;
            padding-left: 15px;
        }

        .card-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 6px;
            bottom: 6px;
            width: 5px;
            background: var(--popcorn-orange);
            border-radius: 4px;
        }

        .points-badge {
            font-size: 48px;
            font-weight: 800;
            color: var(--popcorn-gold);
            display: inline-block;
            margin-left: 15px;
        }

        .progress-bar {
            width: 100%;
            height: 12px;
            background: var(--gray-1);
            border-radius: 20px;
            margin: 15px 0 5px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--popcorn-orange), var(--popcorn-gold));
            border-radius: 20px;
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: var(--white);
        }

        th {
            text-align: left;
            padding: 15px 10px;
            border-bottom: 2px solid var(--gray-1);
            color: var(--light-gray);
            font-weight: 600;
        }

        td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--gray-1);
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .text-success {
            color: #4CAF50;
        }

        .text-danger {
            color: #ff4d4d;
        }

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-confirmed {
            background: rgba(40, 167, 69, 0.2);
            color: #4CAF50;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .status-cancelled {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
        }

        .btn-sm {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--popcorn-orange);
            color: #000;
            transition: 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-sm:hover {
            background: var(--popcorn-gold);
            transform: translateY(-2px);
        }

        .btn-sm.btn-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .btn-sm.btn-danger:hover {
            background: rgba(220, 53, 69, 0.3);
            color: #ff8a92;
        }

        .btn-sm:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* ================= VOTE SECTION ================= */
        .vote-item {
            display: flex;
            align-items: center;
            gap: 15px;
            background: var(--dark-gray);
            padding: 10px 15px;
            border-radius: 12px;
            margin-bottom: 10px;
        }

        .vote-item .movie-title {
            font-weight: 600;
            flex: 1;
        }

        .vote-item .vote-date {
            color: var(--light-gray);
            font-size: 14px;
        }

        /* ================= FOOTER (FULL WIDTH) ================= */
        footer {
            background: var(--deep-navy);
            border-top: 1px solid var(--gray-1);
            padding: 60px 0 30px;
            margin-top: 80px;
            width: 100%;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-col h4 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--white);
            position: relative;
            padding-bottom: 8px;
        }

        .footer-col h4::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--popcorn-orange);
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col ul li {
            margin-bottom: 12px;
        }

        .footer-col ul li a {
            color: var(--light-gray);
            text-decoration: none;
            transition: color 0.2s, padding-left 0.2s;
        }

        .footer-col ul li a:hover {
            color: var(--popcorn-gold);
            padding-left: 6px;
        }

        .contact-info {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .contact-info h5 {
            color: var(--popcorn-gold);
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            letter-spacing: 0.5px;
        }

        .contact-info p {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
            color: var(--light-gray);
            font-size: 14px;
            line-height: 1.5;
        }

        .contact-info p i {
            color: var(--popcorn-orange);
            font-size: 16px;
            min-width: 20px;
            margin-top: 3px;
        }

        .contact-info p a {
            color: var(--light-gray);
            text-decoration: none;
            transition: color 0.2s;
        }

        .contact-info p a:hover {
            color: var(--popcorn-gold);
        }

        .social-links {
            display: flex;
            gap: 18px;
            margin-top: 20px;
        }

        .social-links a {
            color: var(--light-gray);
            font-size: 22px;
            transition: all 0.3s;
        }

        .social-links a:hover {
            color: var(--popcorn-orange);
            transform: translateY(-3px);
        }

        .newsletter {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .newsletter input {
            width: 100%;
            padding: 12px 16px;
            border-radius: 40px;
            border: 1px solid var(--gray-1);
            background: var(--dark-gray);
            color: var(--white);
        }

        .newsletter button {
            width: 100%;
        }

        .copyright {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid var(--gray-1);
            color: var(--gray-2);
            font-size: 14px;
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

            .container {
                padding: 100px 20px 40px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-sm {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    <link rel="stylesheet" href="public_theme.php">
</head>

<body>
    <!-- Floating Navbar with Burger Menu -->
    <header id="navbar">
        <a href="first_page.php" class="logo">
            <?php if (!empty($settings['site_logo'])): ?>
                <img src="<?= htmlspecialchars($settings['site_logo']) ?>"
                    alt="<?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?>">
            <?php else: ?>
                <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?>
            <?php endif; ?>
        </a>
        <div class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></div>
        <nav class="nav-links" id="navLinks">
            <a href="first_page.php">Home</a>
            <a href="showtimes.php">Showtimes</a>
            <a href="theatre.php">Theatres</a>
            <a href="booking.php">Booking</a>
            <a href="about.php">About</a>
            <a href="user_dashboard.php" class="active">Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <!-- Welcome Header -->
        <div style="margin-bottom: 30px;">
            <h1 style="font-size: 42px; font-weight: 800;">Welcome back, <?= htmlspecialchars($user['name']) ?>!</h1>
            <p style="color: var(--light-gray);"><?= htmlspecialchars($user['email']) ?> - Your loyalty dashboard</p>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($cancel_success)): ?>
            <div class="alert alert-success" id="successAlert">
                <i class="bi bi-check-circle-fill"></i>
                <?= htmlspecialchars($cancel_success) ?>
                <span class="close-btn" onclick="this.parentElement.style.display='none'"><i class="bi bi-x"></i></span>
            </div>
        <?php endif; ?>

        <?php if (isset($cancel_error)): ?>
            <div class="alert alert-danger" id="errorAlert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?= htmlspecialchars($cancel_error) ?>
                <span class="close-btn" onclick="this.parentElement.style.display='none'"><i class="bi bi-x"></i></span>
            </div>
        <?php endif; ?>

        <!-- Points Card -->
        <div class="dashboard-card">
            <h2 class="card-title">Your Points</h2>
            <div style="display: flex; align-items: baseline; gap: 15px; flex-wrap: wrap;">
                <span class="points-badge"><?= $user['points'] ?></span>
                <?php if ($user['points'] >= 10): ?>
                    <span
                        style="background: rgba(255,165,0,0.2); border-left: 4px solid var(--popcorn-orange); padding: 10px 16px; border-radius: 8px; color: var(--popcorn-gold);">
                        <i class="bi bi-gift"></i> You have 10+ points! 10% off your next booking.
                    </span>
                <?php else: ?>
                    <span style="color: var(--light-gray);"><?= (10 - ($user['points'] % 10)) ?> more points until next
                        discount.</span>
                <?php endif; ?>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= ($user['points'] % 10) * 10 ?>%;"></div>
            </div>
            <p style="color: var(--light-gray); font-size: 14px; margin-top: 10px;">Every ticket earns 1 point. 10
                points = 10% discount.</p>
        </div>

        <!-- Points History -->
        <div class="dashboard-card">
            <h2 class="card-title">Points History</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Change</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($history && $history->num_rows > 0):
                            while ($row = $history->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
                                <td class="<?= $row['points_change'] > 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= $row['points_change'] > 0 ? '+' : '' ?><?= $row['points_change'] ?>
                                </td>
                                <td><?= htmlspecialchars($row['reason']) ?></td>
                            </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                            <tr>
                                <td colspan="3" style="text-align:center; color: var(--light-gray);">
                                    No points history yet. Start booking movies to earn points!
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Bookings with Cancel Button -->
        <div class="dashboard-card">
            <h2 class="card-title">Recent Bookings</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Movie</th>
                            <th>Theatre</th>
                            <th>Time</th>
                            <th>Seats</th>
                            <th>Total</th>
                            <th>Points</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($bookings->num_rows > 0): ?>
                            <?php while ($row = $bookings->fetch_assoc()):
                                $statusClass = '';
                                if ($row['status'] == 'confirmed')
                                    $statusClass = 'status-confirmed';
                                elseif ($row['status'] == 'pending')
                                    $statusClass = 'status-pending';
                                elseif ($row['status'] == 'cancelled')
                                    $statusClass = 'status-cancelled';

                                $canCancel = ($row['status'] === 'confirmed');
                                $showtimeDate = $row['show_date'] != 'N/A' ? strtotime($row['show_date']) : 0;
                                $isPastShow = $showtimeDate && $showtimeDate < strtotime('today');
                                if ($isPastShow)
                                    $canCancel = false;
                                ?>
                                <tr>
                                    <td><?= date('Y-m-d', strtotime($row['booking_date'])) ?></td>
                                    <td><?= htmlspecialchars($row['movie_title']) ?></td>
                                    <td><?= htmlspecialchars($row['theatre']) ?></td>
                                    <td><?= $row['show_time'] != 'N/A' ? date('g:i A', strtotime($row['show_time'])) : 'N/A' ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['seats']) ?></td>
                                    <td>$<?= number_format($row['total_price'], 2) ?></td>
                                    <td><?= $row['points_earned'] ?></td>
                                    <td><span class="status-badge <?= $statusClass ?>"><?= ucfirst($row['status']) ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="ticket.php?booking_id=<?= $row['booking_id'] ?>" class="btn-sm"
                                                title="View Ticket">
                                                <i class="bi bi-ticket-perforated"></i> View
                                            </a>
                                            <?php if ($canCancel): ?>
                                                <button class="btn-sm btn-danger"
                                                    onclick="confirmCancel(<?= $row['booking_id'] ?>, '<?= htmlspecialchars($row['movie_title']) ?>')"
                                                    title="Cancel Booking">
                                                    <i class="bi bi-x-circle"></i> Cancel
                                                </button>
                                            <?php elseif ($row['status'] === 'cancelled'): ?>
                                                <span class="btn-sm"
                                                    style="background: var(--gray-1); color: var(--light-gray); cursor: not-allowed;"
                                                    disabled>
                                                    <i class="bi bi-check-circle"></i> Cancelled
                                                </span>
                                            <?php elseif ($isPastShow): ?>
                                                <span class="btn-sm"
                                                    style="background: var(--gray-1); color: var(--light-gray); cursor: not-allowed;"
                                                    disabled title="Past shows cannot be cancelled">
                                                    <i class="bi bi-clock-history"></i> Expired
                                                </span>
                                            <?php else: ?>
                                                <span class="btn-sm"
                                                    style="background: var(--gray-1); color: var(--light-gray); cursor: not-allowed;"
                                                    disabled>
                                                    <i class="bi bi-hourglass-split"></i> Pending
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align:center; color: var(--light-gray);">No bookings yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <!-- Footer (full width) -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <!-- Column 1: About & Contact -->
                <div class="footer-col">
                    <h4>Popcorn Hub</h4>
                    <ul>
                        <li><a href="footer_links/abouts.php">About Us</a></li>
                        <li><a href="footer_links/careers.php">Careers</a></li>
                        <li><a href="footer_links/press.php">Press</a></li>
                        <li><a href="footer_links/contact.php">Contact</a></li>
                    </ul>
                    <?php if (!empty($settings['contact_email']) || !empty($settings['contact_phone']) || !empty($settings['address'])): ?>
                        <div class="contact-info">
                            <h5>Get in touch</h5>
                            <?php if (!empty($settings['contact_email'])): ?>
                                <p><i class="fas fa-envelope"></i> <a
                                        href="mailto:<?php echo htmlspecialchars($settings['contact_email']) ?>"><?php echo htmlspecialchars($settings['contact_email']) ?></a>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($settings['contact_phone'])): ?>
                                <p><i class="fas fa-phone-alt"></i> <a
                                        href="tel:<?php echo htmlspecialchars($settings['contact_phone']) ?>"><?php echo htmlspecialchars($settings['contact_phone']) ?></a>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($settings['address'])): ?>
                                <p><i class="fas fa-map-marker-alt"></i>
                                    <?php echo nl2br(htmlspecialchars($settings['address'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Column 2: Movies -->
                <div class="footer-col">
                    <h4>Movies</h4>
                    <ul>
                        <li><a href="footer_links/now%20showing.php">Now Showing</a></li>
                        <li><a href="footer_links/coming_soon.php">Coming Soon</a></li>
                        <li><a href="footer_links/exclusive.php">Exclusive</a></li>
                        <li><a href="footer_links/3d_imax.php">3D/IMAX</a></li>
                    </ul>
                </div>

                <!-- Column 3: Support -->
                <div class="footer-col">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="footer_links/help_center.php">Help Center</a></li>
                        <li><a href="footer_links/terms.php">Terms of Use</a></li>
                        <li><a href="footer_links/privacy.php">Privacy Policy</a></li>
                        <li><a href="footer_links/faq.php">FAQ</a></li>
                    </ul>
                </div>

                <!-- Column 4: Newsletter & Social -->
                <div class="footer-col">
                    <h4>Stay Connected</h4>
                    <div class="social-links">
                        <?php if (!empty($settings['facebook_url'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['facebook_url']) ?>" target="_blank"
                                rel="noopener"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($settings['twitter_url'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['twitter_url']) ?>" target="_blank"
                                rel="noopener"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($settings['instagram_url'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['instagram_url']) ?>" target="_blank"
                                rel="noopener"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <?php echo htmlspecialchars($settings['footer_text'] ?? '&copy; ' . date('Y') . ' ' . ($settings['site_name'] ?? 'Popcorn Hub') . ' Cinemas. All rights reserved.') ?>
            </div>
        </div>
    </footer>

    <!-- Hidden form for cancellation -->
    <form id="cancelForm" method="POST" style="display: none;">
        <input type="hidden" name="booking_id" id="cancelBookingId">
        <input type="hidden" name="cancel_booking" value="1">
    </form>

    <!-- Scripts -->
    <script>
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });

        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const navLinks = document.getElementById('navLinks');
        if (menuToggle && navLinks) {
            menuToggle.addEventListener('click', () => {
                navLinks.classList.toggle('active');
                menuToggle.innerHTML = navLinks.classList.contains('active') ? '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
            });
            navLinks.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    navLinks.classList.remove('active');
                    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                });
            });
        }

        // Cancel confirmation with SweetAlert2
        function confirmCancel(bookingId, movieTitle) {
            Swal.fire({
                title: 'Cancel Booking?',
                html: `Are you sure you want to cancel your booking for <strong>${movieTitle}</strong>?<br><br>This action cannot be undone and your seats will be released.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff4d4d',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, cancel it!',
                cancelButtonText: 'No, keep it',
                background: '#0F1C2B',
                color: '#F2F2F2',
                iconColor: '#ff4d4d'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('cancelBookingId').value = bookingId;
                    document.getElementById('cancelForm').submit();
                }
            });
        }

        // Smooth page transitions
        document.querySelectorAll("a:not([target='_blank'])").forEach(link => {
            link.addEventListener("click", function (e) {
                if (this.href && this.href.indexOf("#") === -1 && !this.hasAttribute('target') && !this.classList.contains('btn-sm')) {
                    e.preventDefault();
                    document.body.classList.add("fade-out");
                    setTimeout(() => { window.location = this.href; }, 600);
                }
            });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.style.display = 'none', 500);
            });
        }, 5000);
    </script>
    <style>
        body.fade-out {
            opacity: 0;
            transition: opacity 0.6s ease;
        }

        .alert {
            transition: opacity 0.5s;
        }

        .btn-sm {
            cursor: pointer;
        }

        .btn-sm:disabled {
            cursor: not-allowed;
        }
    </style>
</body>

</html>
