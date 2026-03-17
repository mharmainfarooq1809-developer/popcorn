<?php
session_start();
require_once '../db_connect.php';
require_once '../settings_init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// ========== FETCH REAL STATISTICS ==========
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_movies = $conn->query("SELECT COUNT(*) as count FROM movies")->fetch_assoc()['count'];
$total_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];

$revenue_result = $conn->query("SELECT SUM(total_price) as total FROM bookings");
$total_revenue = $revenue_result->fetch_assoc()['total'] ?? 0;

$recent_bookings = $conn->query("
    SELECT
        b.id AS booking_id,
        u.name AS customer,
        m.title AS movie,
        s.show_date,
        b.total_price,
        b.status
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    ORDER BY b.booking_date DESC
    LIMIT 5
");

$revenue_data = [];
$labels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('D', strtotime($date));
    $day_result = $conn->query("SELECT SUM(total_price) as daily FROM bookings WHERE DATE(booking_date) = '$date'");
    $revenue_data[] = $day_result->fetch_assoc()['daily'] ?? 0;
}

$sources = ['Website', 'Mobile App', 'Walk-in'];
$source_counts = [60, 30, 10];
$admin_name = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
    <?php if (!empty($settings['theme_color'])): ?>
        <style>
        </style>
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        /* ========== UNIFIED ADMIN CSS (same for all pages) ========== */
        *,
        *::before,
        *::after {
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
            --dark-card: #0F1C2B;
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

        .text-muted {
            color: #6c757d !important;
        }

        body.dark-mode .text-muted {
            color: #adb5bd !important;
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
            background: #0F1C2B;
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

        /* ===== CARDS ===== */
        .card {
            border: none;
            border-radius: 20px;
            padding: 20px;
            background: #FFFFFF;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            width: 100%;
            overflow: hidden;
        }

        body.dark-mode .card {
            background: #0F1C2B;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 10px;
        }

        body.dark-mode .card-title {
            color: #adb5bd;
        }

        .card-value {
            font-size: 32px;
            font-weight: 700;
            color: #212529;
            margin-bottom: 5px;
        }

        body.dark-mode .card-value {
            color: #FFFFFF;
        }

        /* ===== BADGES ===== */
        .badge-success {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
            padding: 5px 10px;
            border-radius: 30px;
            font-weight: 500;
        }

        .badge-warning {
            background: rgba(255, 193, 7, 0.15);
            color: #b17f00;
            padding: 5px 10px;
            border-radius: 30px;
            font-weight: 500;
        }

        .badge-danger {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
            padding: 5px 10px;
            border-radius: 30px;
            font-weight: 500;
        }

        .badge-info {
            background: rgba(23, 162, 184, 0.15);
            color: #17a2b8;
            padding: 5px 10px;
            border-radius: 30px;
            font-weight: 500;
        }

        .badge-primary {
            background: rgba(255, 165, 0, 0.15);
            color: var(--primary);
            padding: 5px 10px;
            border-radius: 30px;
            font-weight: 500;
        }

        body.dark-mode .badge-success {
            background: rgba(40, 167, 69, 0.25);
            color: #7acf7a;
        }

        body.dark-mode .badge-warning {
            background: rgba(255, 193, 7, 0.25);
            color: #ffdb7c;
        }

        body.dark-mode .badge-danger {
            background: rgba(220, 53, 69, 0.25);
            color: #ff8a92;
        }

        body.dark-mode .badge-info {
            background: rgba(23, 162, 184, 0.25);
            color: #6ed4ff;
        }

        body.dark-mode .badge-primary {
            background: rgba(255, 165, 0, 0.25);
            color: #FFD966;
        }

        /* ===== BUTTONS ===== */
        .btn-primary {
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            color: #fff;
            border: none;
            border-radius: 40px;
            padding: 10px 24px;
            box-shadow: 0 4px 14px rgba(255, 165, 0, 0.3);
            transition: all 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 165, 0, 0.5);
        }

        .btn-outline-primary {
            border: 1px solid var(--primary);
            color: var(--primary);
            background: transparent;
            border-radius: 40px;
            padding: 8px 20px;
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            color: #fff;
        }

        body.dark-mode .btn-outline-primary {
            border-color: #FFD966;
            color: #FFD966;
        }

        body.dark-mode .btn-outline-primary:hover {
            background: #FFD966;
            color: #0F1C2B;
        }

        .btn-sm {
            padding: 6px 16px;
            font-size: 13px;
        }

        /* ===== TABLES - FIXED VISIBILITY ===== */
        .table-responsive {
            overflow-x: auto;
            border-radius: 20px;
            background: #FFFFFF;
            border: 1px solid #E9ECEF;
            margin-top: 10px;
        }

        body.dark-mode .table-responsive {
            background: #0F1C2B;
            border-color: #3A414D;
        }

        .table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }

        /* LIGHT MODE TABLE */
        .table {
            color: #212529 !important;
            background: transparent;
        }

        .table th {
            background: #f8f9fa;
            border-bottom: 2px solid #E9ECEF;
            font-weight: 600;
            color: #495057 !important;
            padding: 15px 12px;
            white-space: nowrap;
        }

        .table td {
            border-bottom: 1px solid #E9ECEF;
            padding: 12px;
            vertical-align: middle;
            color: #212529 !important;
            background: transparent;
        }

        /* DARK MODE TABLE */
        body.dark-mode .table {
            color: #FFFFFF !important;
        }

        body.dark-mode .table th {
            background: #1a2634;
            border-bottom-color: #3A414D;
            color: #FFFFFF !important;
        }

        body.dark-mode .table td {
            border-bottom-color: #3A414D;
            color: #FFFFFF !important;
            background: transparent;
        }

        .table tbody tr:hover td {
            background: rgba(0,0,0,0.02);
        }

        body.dark-mode .table tbody tr:hover td {
            background: #1a2634;
        }

        /* Empty state */
        .table td.text-center {
            color: #6c757d !important;
            padding: 30px !important;
        }

        body.dark-mode .table td.text-center {
            color: #adb5bd !important;
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
            background: #0F1C2B;
            border-top-color: #3A414D;
            color: #adb5bd;
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
            background: #0F1C2B;
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
            color: #adb5bd;
        }

        /* ===== HEADER WITH TOGGLE ===== */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .page-header h2 {
            margin-bottom: 0;
            font-size: 24px;
            font-weight: 600;
            color: #212529;
        }

        body.dark-mode .page-header h2 {
            color: #FFFFFF;
        }

        .card-header-with-toggle {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            padding: 5px 0;
            border-bottom: 1px solid transparent;
        }

        .card-header-with-toggle:hover {
            border-bottom-color: var(--primary);
        }

        .card-header-with-toggle h5 {
            margin-bottom: 0;
            font-size: 18px;
            font-weight: 600;
            color: #212529;
        }

        body.dark-mode .card-header-with-toggle h5 {
            color: #FFFFFF;
        }

        .card-header-with-toggle i {
            font-size: 20px;
            color: #6c757d;
            transition: all 0.3s ease;
        }

        .card-header-with-toggle:hover i {
            color: var(--primary);
            transform: translateX(5px);
        }

        .card-header-with-toggle.active i {
            transform: rotate(90deg);
            color: var(--primary);
        }

        body.dark-mode .card-header-with-toggle i {
            color: #adb5bd;
        }

        body.dark-mode .card-header-with-toggle:hover i {
            color: #FFD966;
        }

        .toggle-content {
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            transition: max-height 0.5s ease, opacity 0.3s ease;
        }

        .toggle-content.show {
            max-height: 2000px;
            opacity: 1;
            margin-top: 20px;
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

            .card {
                padding: 15px;
            }

            .card-value {
                font-size: 24px;
            }

            .table th,
            .table td {
                padding: 10px 8px;
                font-size: 14px;
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
        </div>

        <div class="nav">
            <a href="dashboard.php" class="nav-link active"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-navbar">
            <div class="d-flex align-items-center">
                <i class="bi bi-list menu-toggle me-3" id="menuToggle"></i>
            </div>
            <div class="nav-icons">
                <!-- Notification Bell Dropdown -->
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
            <h2>Welcome back, <?= htmlspecialchars($admin_name) ?>!</h2>
            <p class="text-muted mb-0"><?= date('M j, Y') ?></p>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-title">Total Revenue</div>
                    <div class="card-value">$<?= number_format($total_revenue, 2) ?></div>
                    <small class="text-success">+12.5%</small>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-title">Total Users</div>
                    <div class="card-value"><?= number_format($total_users) ?></div>
                    <small class="text-success">+8.2%</small>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-title">Total Bookings</div>
                    <div class="card-value"><?= number_format($total_bookings) ?></div>
                    <small class="text-warning">+3.1%</small>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-title">Movies</div>
                    <div class="card-value"><?= number_format($total_movies) ?></div>
                    <small class="text-success">+5 new</small>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header-with-toggle" data-target="revenueSection" data-default-expanded="true">
                        <h5>Revenue Overview (Last 7 Days)</h5>
                        <i class="bi bi-chevron-right"></i>
                    </div>
                    <div class="toggle-content show" id="revenueSection">
                        <div style="height:250px;">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header-with-toggle" data-target="sourceSection" data-default-expanded="true">
                        <h5>Booking Sources</h5>
                        <i class="bi bi-chevron-right"></i>
                    </div>
                    <div class="toggle-content show" id="sourceSection">
                        <div style="height:250px;">
                            <canvas id="sourceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings Table -->
        <div class="card">
            <div class="card-header-with-toggle" data-target="bookingsSection" data-default-expanded="true">
                <h5>Recent Bookings</h5>
                <i class="bi bi-chevron-right"></i>
            </div>
            <div class="toggle-content show" id="bookingsSection">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Movie</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_bookings && $recent_bookings->num_rows > 0): ?>
                                <?php $display_booking_id = 1; ?>
                                <?php while ($row = $recent_bookings->fetch_assoc()): ?>
                                    <tr>
                                        <td>#BK-<?= str_pad($display_booking_id++, 4, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= htmlspecialchars($row['customer']) ?></td>
                                        <td><?= htmlspecialchars($row['movie']) ?></td>
                                        <td><?= date('Y-m-d', strtotime($row['show_date'])) ?></td>
                                        <td>$<?= number_format($row['total_price'], 2) ?></td>
                                        <td>
                                            <?php
                                            $class = 'badge-info';
                                            if ($row['status'] == 'confirmed')
                                                $class = 'badge-success';
                                            elseif ($row['status'] == 'pending')
                                                $class = 'badge-warning';
                                            elseif ($row['status'] == 'cancelled')
                                                $class = 'badge-danger';
                                            ?>
                                            <span class="badge <?= $class ?>"><?= ucfirst($row['status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No recent bookings found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer text-center">
        <div class="container">
            <p class="small">
                <?= htmlspecialchars($settings['footer_text'] ?? ' ' . date('Y') . ' Popcorn Hub. All rights reserved.') ?>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="admin_toggle.js"></script>
    <script>
        // ========== CHARTS ==========
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?= json_encode($revenue_data) ?>,
                    borderColor: '#FFA500',
                    backgroundColor: 'rgba(255,165,0,0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });

        new Chart(document.getElementById('sourceChart'), {
            type: 'pie',
            data: {
                labels: <?= json_encode($sources) ?>,
                datasets: [{
                    data: <?= json_encode($source_counts) ?>,
                    backgroundColor: ['#FFA500', '#FFD966', '#cc7f00', '#FFB347', '#FF8C42']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    </script>
</body>

</html>
