<?php
session_start();
require_once '../db_connect.php';
require_once '../settings_init.php'; // load global settings

// Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$admin_name = $_SESSION['user_name'] ?? 'Admin';

$booking_id = intval($_GET['booking_id'] ?? 0);
if (!$booking_id) {
    header("Location: bookings.php");
    exit;
}

// Fetch booking details with correct joins
$stmt = $conn->prepare("
    SELECT 
        b.*, 
        u.name AS user_name,
        u.email AS user_email,
        m.title AS movie,
        s.show_date,
        s.show_time,
        s.theatre
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    WHERE b.id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    die("Booking not found.");
}

$status = $booking['status']; // e.g., 'confirmed', 'pending', 'cancelled'
$isConfirmed = ($status === 'confirmed');

// Prepare data for display (still needed for info)
$movie = htmlspecialchars($booking['movie']);
$holderName = htmlspecialchars($booking['holder_name'] ?? $booking['user_name']);
$seats = htmlspecialchars($booking['seats']);
$adults = $booking['adults'];
$children = $booking['children'];
$total = number_format($booking['total_price'], 2);
$date = htmlspecialchars($booking['show_date']);
$time = htmlspecialchars($booking['show_time']);
$theatre = htmlspecialchars($booking['theatre'] ?? 'Unknown Theatre');
$cinema = htmlspecialchars($booking['theatre'] ?? 'Bashundhara Shopping Mall, Panthapath');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket - <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
    <?php if (!empty($settings['theme_color'])): ?>
        <style>
        </style>
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?php if ($isConfirmed): ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
    <?php endif; ?>
    <style>
        /* ========== UNIFIED ADMIN STYLES ========== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Heebo', sans-serif;
            background-color: #F8F9FA;
            color: #212529;
            transition: all 0.3s ease;
            overflow-x: hidden;
            line-height: 1.6;
        }

        body.dark-mode {
            background-color: #0B1623;
            color: #FFFFFF;
        }

        :root {
            --primary: #FFA500;
            --primary-dark: #cc7f00;
            --primary-gold: #FFD966;
            --light-card: #FFFFFF;
            --dark-card: #1a2634;
            --light-text: #212529;
            --dark-text: #FFFFFF;
            --border-light: #E9ECEF;
            --border-dark: #3A414D;
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 80px;
            --transition: all 0.3s ease;
        }

        /* ===== HEADINGS ===== */
        h1, h2, h3, h4, h5, h6 {
            color: #212529;
            transition: color 0.3s ease;
        }

        body.dark-mode h1,
        body.dark-mode h2,
        body.dark-mode h3,
        body.dark-mode h4,
        body.dark-mode h5,
        body.dark-mode h6 {
            color: #FFFFFF;
        }

        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: #212529;
        }

        body.dark-mode .page-title {
            color: #FFFFFF;
        }

        /* Text utilities */
        .text-muted {
            color: #6c757d !important;
        }

        body.dark-mode .text-muted {
            color: #AAAAAA !important;
        }

        /* ===== SIDEBAR OVERLAY ===== */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .sidebar-overlay.active {
            display: block;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: #FFFFFF;
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.05);
            transition: transform var(--transition), width var(--transition);
            z-index: 1000;
            overflow-y: auto;
            border-right: 1px solid #E9ECEF;
            transform: translateX(-100%);
        }

        .sidebar.active {
            transform: translateX(0);
        }

        body.dark-mode .sidebar {
            background: #1a2634;
            border-right-color: #3A414D;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar .logo-area {
            padding: 24px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #E9ECEF;
        }

        body.dark-mode .sidebar .logo-area {
            border-bottom-color: #3A414D;
        }

        .sidebar .logo {
            font-size: 22px;
            font-weight: 700;
            color: #FFD966;
            white-space: nowrap;
            overflow: hidden;
        }

        .sidebar.collapsed .logo span {
            display: none;
        }

        .sidebar .toggle-btn {
            background: none;
            border: none;
            color: #212529;
            cursor: pointer;
            font-size: 20px;
        }

        body.dark-mode .sidebar .toggle-btn {
            color: #FFFFFF;
        }

        .sidebar .toggle-btn:hover {
            color: var(--primary);
        }

        .sidebar .nav {
            padding: 12px 0 96px;
            display: block;
        }

        .sidebar .nav-link {
            display: flex;
            align-items: center;
            padding: 9px 16px;
            color: #212529;
            text-decoration: none;
            border-radius: 0 30px 30px 0;
            margin-right: 10px;
            transition: var(--transition);
            white-space: nowrap;
        }

        body.dark-mode .sidebar .nav-link {
            color: #FFFFFF;
        }

        .sidebar .nav-link i {
            font-size: 17px;
            min-width: 24px;
            text-align: center;
        }

        .sidebar .nav-link span {
            transition: opacity 0.2s;
            opacity: 1;
            overflow: hidden;
            white-space: nowrap;
        }

        .sidebar.collapsed .nav-link span {
            opacity: 0;
            width: 0;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 165, 0, 0.1);
            color: var(--primary);
        }

        .sidebar .nav-link.active {
            background: var(--primary);
            color: #fff;
        }

        body.dark-mode .sidebar .nav-link.active {
            background: var(--primary-dark);
        }

        /* Submenu */
        .nav-item {
            width: 100%;
        }

        .nav-link[data-bs-toggle="collapse"] {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-link[data-bs-toggle="collapse"] i.bi-chevron-down {
            transition: transform 0.3s;
        }

        .nav-link[data-bs-toggle="collapse"][aria-expanded="true"] i.bi-chevron-down {
            transform: rotate(180deg);
        }

        .submenu-link {
            padding-left: 42px !important;
            font-size: 13px;
        }

        .sidebar .bottom-section {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 14px;
            border-top: 1px solid #E9ECEF;
            background: inherit;
        }

        body.dark-mode .sidebar .bottom-section {
            border-top-color: #3A414D;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: 0;
            padding: 20px;
            transition: margin-left var(--transition);
            min-height: 100vh;
            width: 100%;
            overflow-x: hidden;
        }

        @media (min-width: 992px) {
            .sidebar {
                transform: translateX(0);
            }

            .main-content {
                margin-left: var(--sidebar-width);
                width: calc(100% - var(--sidebar-width));
            }

            body.sidebar-collapsed .main-content {
                margin-left: var(--sidebar-collapsed-width);
                width: calc(100% - var(--sidebar-collapsed-width));
            }
        }

        /* ===== TOP NAVBAR ===== */
        .top-navbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 10px 0 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .menu-toggle, .menu-toggle-mobile {
            font-size: 24px;
            cursor: pointer;
        }

        @media (min-width: 992px) {
            .menu-toggle-mobile {
                display: none;
            }
        }

        .nav-icons {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-icons .icon {
            position: relative;
            font-size: 22px;
            color: #212529;
            cursor: pointer;
        }

        body.dark-mode .nav-icons .icon {
            color: #FFFFFF;
        }

        .nav-icons .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--primary);
            color: #fff;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar-icon {
            font-size: 2.2rem;
            color: var(--primary);
            cursor: pointer;
        }

        .avatar-icon:hover {
            color: var(--primary-dark);
        }

        .theme-toggle {
            cursor: pointer;
            font-size: 22px;
            color: #212529;
        }

        body.dark-mode .theme-toggle {
            color: #FFFFFF;
        }

        .theme-toggle:hover {
            color: var(--primary);
        }

        /* ===== TICKET CARD ===== */
        .ticket-container {
            margin: 20px 0 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .ticket-card {
            background: #FFFFFF;
            border-radius: 32px;
            padding: 30px 40px;
            max-width: 1000px;
            width: 100%;
            border: 1px solid #E9ECEF;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: row;
            gap: 30px;
            align-items: center;
        }

        body.dark-mode .ticket-card {
            background: #1a2634;
            border-color: #3A414D;
        }

        .ticket-left {
            flex: 2;
            border-right: 2px dashed #E9ECEF;
            padding-right: 30px;
        }

        body.dark-mode .ticket-left {
            border-right-color: #3A414D;
        }

        .ticket-right {
            flex: 1;
            text-align: center;
        }

        .ticket-header h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 4px;
        }

        .ticket-header p {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 20px;
        }

        body.dark-mode .ticket-header p {
            color: #AAAAAA;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 12px 16px;
            margin: 20px 0;
        }

        .info-label {
            color: #6c757d;
            font-weight: 500;
            font-size: 15px;
        }

        body.dark-mode .info-label {
            color: #AAAAAA;
        }

        .info-value {
            color: #212529;
            font-weight: 700;
            font-size: 15px;
        }

        body.dark-mode .info-value {
            color: #FFFFFF;
        }

        .qr-section canvas {
            width: 180px;
            height: 180px;
            border-radius: 16px;
            background: white;
            padding: 8px;
            margin-bottom: 10px;
        }

        .qr-section p {
            font-size: 13px;
            color: #6c757d;
        }

        body.dark-mode .qr-section p {
            color: #AAAAAA;
        }

        .ticket-footer {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        /* ===== STATUS MESSAGE ===== */
        .status-message {
            text-align: center;
            padding: 60px 20px;
            background: #FFFFFF;
            border-radius: 32px;
            border: 1px solid #E9ECEF;
            max-width: 600px;
            margin: 40px auto;
        }

        body.dark-mode .status-message {
            background: #1a2634;
            border-color: #3A414D;
        }

        .status-message i {
            font-size: 5rem;
            color: #dc3545;
            margin-bottom: 20px;
        }

        .status-message h3 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #212529;
        }

        body.dark-mode .status-message h3 {
            color: #FFFFFF;
        }

        .status-message p {
            font-size: 18px;
            color: #6c757d;
            margin-bottom: 30px;
        }

        body.dark-mode .status-message p {
            color: #AAAAAA;
        }

        /* ===== BUTTONS ===== */
        .btn-primary {
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            color: #FFFFFF;
            border: none;
            border-radius: 40px;
            padding: 10px 24px;
            box-shadow: 0 4px 14px rgba(255, 165, 0, 0.3);
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 165, 0, 0.5);
        }

        .btn-outline-secondary {
            border: 1px solid #E9ECEF;
            color: #212529;
            background: transparent;
            border-radius: 40px;
            padding: 10px 24px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        body.dark-mode .btn-outline-secondary {
            border-color: #3A414D;
            color: #FFFFFF;
        }

        .btn-outline-secondary:hover {
            background: var(--primary);
            color: #FFFFFF;
            border-color: var(--primary);
        }

        /* ===== FOOTER ===== */
        .footer {
            background: #FFFFFF;
            border-top: 1px solid #E9ECEF;
            padding: 20px 0;
            margin-top: 40px;
            color: #6c757d;
        }

        body.dark-mode .footer {
            background: #1a2634;
            border-top-color: #3A414D;
            color: #AAAAAA;
        }

        /* ===== DROPDOWNS ===== */
        .dropdown-menu {
            background: #FFFFFF;
            border: 1px solid #E9ECEF;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-height: 400px;
            overflow-y: auto;
        }

        body.dark-mode .dropdown-menu {
            background: #1a2634;
            border-color: #3A414D;
        }

        .dropdown-item {
            color: #212529;
            padding: 10px 20px;
        }

        body.dark-mode .dropdown-item {
            color: #FFFFFF;
        }

        .dropdown-item:hover {
            background: rgba(255, 165, 0, 0.1);
        }

        .dropdown-header {
            color: #6c757d;
        }

        body.dark-mode .dropdown-header {
            color: #AAAAAA;
        }

        /* ===== PRINT STYLES ===== */
        @media print {
            body,
            body.dark-mode {
                background: white;
                color: black;
            }

            .sidebar,
            .top-navbar,
            .ticket-footer,
            footer {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 0;
            }

            .ticket-card {
                background: white;
                border: 2px solid #333;
                box-shadow: none;
                page-break-inside: avoid;
                break-inside: avoid;
                margin: 0 auto;
                border-radius: 16px;
            }

            .ticket-left {
                border-right: 2px dashed #aaa;
            }

            .info-label {
                color: #555;
            }

            .info-value {
                color: #000;
            }

            .ticket-header h1 {
                color: #FFA500;
            }

            .qr-section canvas {
                border: 1px solid #ccc;
            }

            @page {
                size: landscape;
                margin: 0.5in;
            }
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .sidebar {
                left: -100%;
            }

               .top-navbar {
                flex-direction: column;
                align-items: stretch;
            }

            .nav-icons {
                justify-content: flex-end;
            }

            .sidebar.active {
                left: 0;
            }

            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }

            .ticket-card {
                flex-direction: column;
                padding: 20px;
            }

            .ticket-left {
                border-right: none;
                border-bottom: 2px dashed #E9ECEF;
                padding-right: 0;
                padding-bottom: 20px;
            }

            body.dark-mode .ticket-left {
                border-bottom-color: #3A414D;
            }

            .ticket-footer {
                flex-direction: column;
                width: 100%;
            }

            .ticket-footer a,
            .ticket-footer button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    <style id="admin-sidebar-unify">
        .sidebar {
            transition: width 0.28s ease, transform 0.28s ease;
            will-change: width, transform;
        }

        .main-content {
            transition: margin-left 0.28s ease, width 0.28s ease;
        }

        .sidebar .logo span,
        .sidebar .nav-link span {
            transition: opacity 0.22s ease, max-width 0.22s ease;
            max-width: 180px;
            overflow: hidden;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width) !important;
        }

        .sidebar.collapsed .logo span,
        .sidebar.collapsed .nav-link span {
            opacity: 0;
            max-width: 0;
        }

        #sidebarToggle i {
            transition: transform 0.25s ease;
        }

        body.sidebar-collapsed #sidebarToggle i {
            transform: rotate(180deg);
        }

        .search-bar {
            display: none !important;
        }

        .top-navbar {
            justify-content: flex-end;
            gap: 12px;
        }
    </style>
</head>

<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo-area">
            <div class="logo">
                <i class="bi bi-camera-reels me-2"></i>
                <span><?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></span>
            </div>
            <button class="toggle-btn" id="sidebarToggle"><i class="bi bi-chevron-left"></i></button>
        </div>

        <div class="nav">
            <a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
            <a href="movies.php" class="nav-link"><i class="bi bi-film"></i><span>Movies</span></a>

            <!-- Theatres submenu -->
            <div class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#theatresSubmenu" role="button" aria-expanded="false">
                    <i class="bi bi-building"></i><span>Theatres</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <div class="collapse" id="theatresSubmenu">
                    <a href="theatres.php" class="nav-link submenu-link"><i class="bi bi-list-ul"></i><span>All Theatres</span></a>
                    <a href="add_theatre.php" class="nav-link submenu-link"><i class="bi bi-plus-circle"></i><span>Add Theatre</span></a>
                </div>
            </div>

            <a href="bookings.php" class="nav-link"><i class="bi bi-ticket"></i><span>Bookings</span></a>

            <!-- Users submenu -->
            <div class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#usersSubmenu" role="button" aria-expanded="false">
                    <i class="bi bi-people"></i><span>Users</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <div class="collapse" id="usersSubmenu">
                    <a href="users.php" class="nav-link submenu-link"><i class="bi bi-list-ul"></i><span>All Users</span></a>
                    <a href="add_user.php" class="nav-link submenu-link"><i class="bi bi-plus-circle"></i><span>Add User</span></a>
                </div>
            </div>

            <a href="analytics.php" class="nav-link"><i class="bi bi-graph-up"></i><span>Analytics</span></a>
            <a href="messages.php" class="nav-link"><i class="bi bi-chat-dots"></i><span>Messages</span></a>
            <a href="votes.php" class="nav-link"><i class="bi bi-bar-chart"></i><span>Voting</span></a>

            <!-- Settings submenu -->
            <div class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#settingsSubmenu" role="button" aria-expanded="false">
                    <i class="bi bi-gear"></i><span>Settings</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <div class="collapse" id="settingsSubmenu">
                    <a href="settings.php" class="nav-link submenu-link"><i class="bi bi-sliders2"></i><span>General</span></a>
                    <a href="email_settings.php" class="nav-link submenu-link"><i class="bi bi-envelope"></i><span>Email</span></a>
                </div>
            </div>
        </div>

        <div class="bottom-section">
            <a href="../logout.php" class="nav-link"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-navbar">
            <div class="d-flex align-items-center">
                <i class="bi bi-list menu-toggle me-3" id="menuToggle"></i>
            </div>
            <div class="nav-icons">
                <div class="dropdown d-inline-block">
                    <div class="icon position-relative" id="notificationDropdown" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <span class="badge" id="notificationBadge" style="display: none;">0</span>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end" style="width: 300px;">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="notificationList"></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center small" href="#" id="markAllRead">Mark all as read</a></li>
                    </ul>
                </div>
                <div class="theme-toggle" id="themeToggle"><i class="bi bi-moon"></i></div>
                <i class="bi bi-person-circle avatar-icon"></i>
            </div>
        </div>

        <div class="page-header">
            <h2>Ticket #<?= $booking_id ?></h2>
        </div>

        <?php if ($isConfirmed): ?>
            <div class="ticket-container">
                <div class="ticket-card">
                    <div class="ticket-left">
                        <div class="ticket-header">
                            <h1> <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></h1>
                            <p>Admin Ticket - E-Ticket - Scan for verification</p>
                        </div>
                        <div class="info-grid" id="ticketInfo"></div>
                    </div>
                    <div class="ticket-right">
                        <div class="qr-section">
                            <div id="qrcode"></div>
                            <p>Scan this QR code at the entrance</p>
                        </div>
                    </div>
                </div>
                <div class="ticket-footer">
                    <a href="bookings.php" class="btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Bookings</a>
                    <button class="btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print Ticket</button>
                </div>
            </div>
        <?php else: ?>
            <div class="status-message">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <h3>Ticket Unavailable</h3>
                <p>This booking is <strong><?= strtoupper($status) ?></strong>. Tickets are only available for confirmed bookings.</p>
                <a href="bookings.php" class="btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Bookings</a>
            </div>
        <?php endif; ?>

        <footer class="footer text-center">
            <div class="container">
                <p class="small"><?= htmlspecialchars($settings['footer_text'] ?? ' '.date('Y').' Popcorn Hub. All rights reserved.') ?></p>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="admin_toggle.js"></script>
    <script>
        // Notifications
        function updateNotifications() {
            fetch('get_notifications.php')
                .then(res => res.json())
                .then(data => {
                    const badge = document.getElementById('notificationBadge');
                    const list = document.getElementById('notificationList');
                    if (badge && list) {
                        if (data.notifications?.length) {
                            badge.textContent = data.notifications.length;
                            badge.style.display = 'flex';
                            list.innerHTML = '';
                            data.notifications.forEach(notif => {
                                const item = document.createElement('li');
                                item.innerHTML = `<a class="dropdown-item" href="${notif.link || '#'}">
                                    ${notif.message}<br>
                                    <small class="text-muted">${new Date(notif.created_at).toLocaleString()}</small>
                                </a>`;
                                list.appendChild(item);
                            });
                        } else {
                            badge.style.display = 'none';
                            list.innerHTML = '<li><span class="dropdown-item-text text-muted">No new notifications</span></li>';
                        }
                    }
                });
        }

        document.getElementById('markAllRead')?.addEventListener('click', (e) => {
            e.preventDefault();
            fetch('mark_notifications_read.php', { method: 'POST' })
                .then(res => res.json())
                .then(data => { if (data.success) updateNotifications(); });
        });

        updateNotifications();
        setInterval(updateNotifications, 30000);

        <?php if ($isConfirmed): ?>
            // Ticket data
            const movie = <?= json_encode($movie) ?>;
            const name = <?= json_encode($holderName) ?>;
            const seats = <?= json_encode($seats) ?>;
            const total = <?= json_encode('$' . $total) ?>;
            const adults = <?= json_encode($adults) ?>;
            const children = <?= json_encode($children) ?>;
            const date = <?= json_encode($date) ?>;
            const time = <?= json_encode($time) ?>;
            const theatre = <?= json_encode($theatre) ?>;
            const cinema = <?= json_encode($cinema) ?>;

            // Fill ticket info
            document.getElementById('ticketInfo').innerHTML = `
                <span class="info-label">Movie</span><span class="info-value">${movie}</span>
                <span class="info-label">Name</span><span class="info-value">${name}</span>
                <span class="info-label">Seats</span><span class="info-value">${seats}</span>
                <span class="info-label">Date</span><span class="info-value">${date} ${time}</span>
                <span class="info-label">Theatre</span><span class="info-value">${theatre}</span>
                <span class="info-label">Location</span><span class="info-value">${cinema}</span>
                <span class="info-label">Tickets</span><span class="info-value">Adults: ${adults}, Children: ${children}</span>
                <span class="info-label">Total</span><span class="info-value">${total}</span>
            `;

            // Generate QR code
            const qrData = `
Booking Confirmation
Movie: ${movie}
Name: ${name}
Seats: ${seats}
Date: ${date} ${time}
Theatre: ${theatre}
Location: ${cinema}
Tickets: Adults ${adults}, Children ${children}
Total: ${total}
            `.trim();

            const qr = qrcode(0, 'M');
            qr.addData(qrData);
            qr.make();
            document.getElementById('qrcode').innerHTML = qr.createImgTag(5, 10);
        <?php endif; ?>
    </script>
</body>
</html>
