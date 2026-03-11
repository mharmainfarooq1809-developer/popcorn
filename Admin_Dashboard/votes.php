<?php
session_start();
require_once '../db_connect.php';
require_once '../settings_init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$admin_name = $_SESSION['user_name'] ?? 'Admin';

// Get all votes with user and movie details
$votes = $conn->query("
    SELECT 
        v.id AS vote_id,
        v.voted_at,
        u.id AS user_id,
        u.name AS user_name,
        u.email AS user_email,
        m.id AS movie_id,
        m.title AS movie_title,
        m.category,
        m.year
    FROM user_votes v
    JOIN users u ON v.user_id = u.id
    JOIN movies m ON v.movie_id = m.id
    ORDER BY v.voted_at DESC
");

// Get vote counts per movie (for chart)
$movie_counts = $conn->query("
    SELECT 
        m.title,
        COUNT(v.id) AS vote_count
    FROM user_votes v
    JOIN movies m ON v.movie_id = m.id
    GROUP BY m.id
    ORDER BY vote_count DESC
    LIMIT 10
");

$movie_labels = [];
$movie_data = [];
while ($row = $movie_counts->fetch_assoc()) {
    $movie_labels[] = $row['title'];
    $movie_data[] = $row['vote_count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votes · <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
    <?php if (!empty($settings['theme_color'])): ?>
        <style>
            :root { --primary: <?= htmlspecialchars($settings['theme_color']) ?>; }
        </style>
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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

        /* Text utilities */
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

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color 0.2s;
        }

        .avatar:hover {
            border-color: var(--primary);
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

        .card h5 {
            color: #212529;
            font-weight: 600;
            margin-bottom: 15px;
        }

        body.dark-mode .card h5 {
            color: #FFFFFF;
        }

        /* ===== STATS CARDS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .stat-card {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            border: 1px solid #E9ECEF;
            transition: all 0.3s ease;
        }

        body.dark-mode .stat-card {
            background: #1a2634;
            border-color: #3A414D;
        }

        .stat-card h3 {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-card small {
            font-size: 13px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        body.dark-mode .stat-card small {
            color: #adb5bd;
        }

        /* ===== BADGES ===== */
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-primary {
            background: rgba(255, 165, 0, 0.15);
            color: #FFA500;
        }

        body.dark-mode .badge-primary {
            background: rgba(255, 165, 0, 0.25);
            color: #FFD966;
        }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            color: #FFFFFF;
            box-shadow: 0 4px 14px rgba(255, 165, 0, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 165, 0, 0.5);
        }

        .btn-outline-danger {
            border: 1px solid #dc3545;
            color: #dc3545;
            background: transparent;
        }

        .btn-outline-danger:hover {
            background: #dc3545;
            color: #FFFFFF;
        }

        body.dark-mode .btn-outline-danger {
            border-color: #ff8a92;
            color: #ff8a92;
        }

        body.dark-mode .btn-outline-danger:hover {
            background: #ff8a92;
            color: #0F1C2B;
        }

        .btn-sm {
            padding: 4px 12px;
            font-size: 12px;
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

        /* ===== CHART CARD ===== */
        .chart-container {
            height: 250px;
            width: 100%;
            position: relative;
        }

        /* ===== HEADERS WITH TOGGLE ===== */
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

        /* Toggle content animation */
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

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .page-header .btn {
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .table td, .table th {
                padding: 10px 8px;
                font-size: 13px;
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
            <a href="votes.php" class="nav-link active"><i class="bi bi-bar-chart"></i><span>Voting</span></a>

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
                <div class="dropdown d-inline-block">
                    <div class="icon position-relative" id="notificationDropdown" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <span class="badge" id="notificationBadge" style="display:none;">0</span>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end" style="width:300px;">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="notificationList"></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center small" href="#" id="markAllRead">Mark all as read</a></li>
                    </ul>
                </div>
                <div class="theme-toggle" id="themeToggle"><i class="bi bi-moon"></i></div>
                <img src="https://via.placeholder.com/40" class="avatar" alt="User">
            </div>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h2>User Votes</h2>
            <button class="btn btn-primary" onclick="exportToCSV()"><i class="bi bi-download"></i> Export CSV</button>
        </div>

        <!-- Charts and Stats Row -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header-with-toggle" data-target="chartSection" data-default-expanded="true">
                        <h5>Top Voted Movies</h5>
                        <i class="bi bi-chevron-right"></i>
                    </div>
                    <div class="toggle-content show" id="chartSection">
                        <div class="chart-container">
                            <canvas id="votesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header-with-toggle" data-target="statsSection" data-default-expanded="true">
                        <h5>Quick Stats</h5>
                        <i class="bi bi-chevron-right"></i>
                    </div>
                    <div class="toggle-content show" id="statsSection">
                        <div class="stats-grid">
                            <div class="stat-card">
                                <h3><?= $votes->num_rows ?></h3>
                                <small>Total Votes</small>
                            </div>
                            <div class="stat-card">
                                <h3><?= $conn->query("SELECT COUNT(DISTINCT user_id) FROM user_votes")->fetch_row()[0] ?></h3>
                                <small>Voters</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Votes Table -->
        <div class="card">
            <div class="card-header-with-toggle" data-target="tableSection" data-default-expanded="true">
                <h5>Vote History</h5>
                <i class="bi bi-chevron-right"></i>
            </div>
            <div class="toggle-content show" id="tableSection">
                <div class="table-responsive">
                    <table class="table" id="votesTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Movie</th>
                                <th>Category</th>
                                <th>Year</th>
                                <th>Voted At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($votes->num_rows > 0): ?>
                                <?php $serial = 1; ?>
                                <?php while ($v = $votes->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $serial++ ?></td>
                                        <td><?= htmlspecialchars($v['user_name']) ?></td>
                                        <td><?= htmlspecialchars($v['user_email']) ?></td>
                                        <td><?= htmlspecialchars($v['movie_title']) ?></td>
                                        <td><span class="badge badge-primary"><?= htmlspecialchars($v['category']) ?></span></td>
                                        <td><?= $v['year'] ?></td>
                                        <td><?= date('Y-m-d H:i', strtotime($v['voted_at'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteVote(<?= $v['vote_id'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center">No votes yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <footer class="footer text-center">
            <div class="container">
                <p class="small"><?= htmlspecialchars($settings['footer_text'] ?? '© '.date('Y').' Popcorn Hub. All rights reserved.') ?></p>
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

        // Chart
        const votesChartEl = document.getElementById('votesChart');
        if (votesChartEl) {
            new Chart(votesChartEl, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($movie_labels) ?>,
                    datasets: [{
                        label: 'Votes',
                        data: <?= json_encode($movie_data) ?>,
                        backgroundColor: '#FFA500',
                        borderRadius: 8,
                        barPercentage: 0.7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        }
                    }
                }
            });
        }

        // Delete vote
        function deleteVote(voteId) {
            if (!confirm('Delete this vote?')) return;
            fetch('delete_vote.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + voteId
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) location.reload();
                else alert('Error: ' + data.error);
            })
            .catch(err => alert('Network error'));
        }

        // Export to CSV
        function exportToCSV() {
            let csv = "ID,User,Email,Movie,Category,Year,Voted At\n";
            document.querySelectorAll('#votesTable tbody tr').forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 7) {
                    csv += cells[0].innerText + ',' +
                           cells[1].innerText + ',' +
                           cells[2].innerText + ',' +
                           cells[3].innerText + ',' +
                           cells[4].innerText.replace(/<[^>]*>/g, '') + ',' +
                           cells[5].innerText + ',' +
                           cells[6].innerText + '\n';
                }
            });
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'votes.csv';
            a.click();
        }
    </script>
</body>
</html>