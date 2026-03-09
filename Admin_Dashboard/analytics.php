<?php
session_start();
require_once '../db_connect.php';
require_once '../settings_init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$admin_name = $_SESSION['user_name'] ?? 'Admin';

// ========== KPI DATA ==========
$revenue_total = $conn->query("SELECT SUM(total_price) as total FROM bookings WHERE booking_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['total'] ?? 0;
$bookings_total = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE booking_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['count'] ?? 0;
$avg_ticket = $conn->query("SELECT AVG(total_price) as avg FROM bookings WHERE booking_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['avg'] ?? 0;
$conversion_rate = 4.2;

// ========== DAILY BOOKINGS (LAST 7 DAYS) ==========
$daily_labels = [];
$daily_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $daily_labels[] = date('D', strtotime($date));
    $result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE DATE(booking_date) = '$date'");
    $daily_data[] = $result->fetch_assoc()['count'] ?? 0;
}

// ========== REVENUE BY MOVIE (TOP 5) ==========
$movie_revenue_labels = [];
$movie_revenue_data = [];
$movie_query = $conn->query("
    SELECT m.title, SUM(b.total_price) as total
    FROM bookings b
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    WHERE b.booking_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY m.id
    ORDER BY total DESC
    LIMIT 5
");
while ($row = $movie_query->fetch_assoc()) {
    $movie_revenue_labels[] = $row['title'];
    $movie_revenue_data[] = $row['total'];
}

// ========== USER GROWTH (LAST 4 WEEKS) ==========
$user_growth_labels = [];
$user_growth_data = [];
for ($i = 3; $i >= 0; $i--) {
    $start = date('Y-m-d', strtotime("-$i week Monday"));
    $end = date('Y-m-d', strtotime("-$i week Sunday"));
    $user_growth_labels[] = "Week " . (4 - $i);
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at BETWEEN '$start' AND '$end 23:59:59'");
    $user_growth_data[] = $result->fetch_assoc()['count'] ?? 0;
}

// ========== BOOKING STATUS DISTRIBUTION ==========
$status_labels = [];
$status_data = [];
$status_query = $conn->query("SELECT status, COUNT(*) as count FROM bookings GROUP BY status");
while ($row = $status_query->fetch_assoc()) {
    $status_labels[] = ucfirst($row['status']);
    $status_data[] = $row['count'];
}
if (empty($status_labels)) {
    $status_labels = ['Confirmed', 'Pending', 'Cancelled'];
    $status_data = [65, 25, 10];
}
$status_colors = ['#28a745', '#ffc107', '#dc3545', '#17a2b8', '#FFA500'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics · <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
    <?php if (!empty($settings['theme_color'])): ?>
        <style>
            :root { --primary: <?= htmlspecialchars($settings['theme_color']) ?>; }
            .btn-primary { background: linear-gradient(145deg, var(--primary), var(--primary-dark)); }
        </style>
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        /* ========== GLOBAL & VARIABLES ========== */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
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
            color: #F2F2F2;
        }
        :root {
            --primary: #FFA500;
            --primary-dark: #cc7f00;
            --primary-gold: #FFD966;
            --light-card: #FFFFFF;
            --dark-card: #0F1C2B;
            --light-text: #212529;
            --dark-text: #F2F2F2;
            --border-light: #E9ECEF;
            --border-dark: #3A414D;
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 80px;
            --transition: all 0.3s ease;
        }

        /* ========== SIDEBAR OVERLAY ========== */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        .sidebar-overlay.active { display: block; }

        /* ========== SIDEBAR ========== */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--light-card);
            box-shadow: 2px 0 20px rgba(0,0,0,0.05);
            transition: transform var(--transition), width var(--transition);
            z-index: 1000;
            overflow-y: auto;
            border-right: 1px solid var(--border-light);
            transform: translateX(-100%);
        }
        .sidebar.active { transform: translateX(0); }
        .dark-mode .sidebar {
            background: var(--dark-card);
            border-right-color: var(--border-dark);
        }
        .sidebar.collapsed { width: var(--sidebar-collapsed-width); }

        .sidebar .logo-area {
            padding: 24px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-light);
        }
        .dark-mode .sidebar .logo-area { border-bottom-color: var(--border-dark); }
        .sidebar .logo {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-gold);
            white-space: nowrap;
            overflow: hidden;
        }
        .sidebar.collapsed .logo span { display: none; }
        .sidebar .toggle-btn {
            background: none;
            border: none;
            color: var(--light-text);
            cursor: pointer;
            font-size: 20px;
            transition: color 0.2s;
        }
        .dark-mode .sidebar .toggle-btn { color: var(--dark-text); }
        .sidebar .toggle-btn:hover { color: var(--primary); }

        .sidebar .nav { padding: 12px 0 96px; }
        .sidebar .nav-link {
            display: flex;
            align-items: center;
            padding: 9px 16px;
            color: var(--light-text);
            text-decoration: none;
            border-radius: 0 30px 30px 0;
            margin-right: 10px;
            transition: var(--transition);
            white-space: nowrap;
        }
        .dark-mode .sidebar .nav-link { color: var(--dark-text); }
        .sidebar .nav-link i { font-size: 17px; min-width: 24px; text-align: center; }
        .sidebar .nav-link span {
            transition: opacity 0.2s, width 0.2s;
            opacity: 1;
            width: auto;
            overflow: hidden;
            white-space: nowrap;
        }
        .sidebar.collapsed .nav-link span {
            opacity: 0;
            width: 0;
        }
        .sidebar .nav-link:hover { background: rgba(255,165,0,0.1); color: var(--primary); }
        .sidebar .nav-link.active { background: var(--primary); color: #fff; }
        .dark-mode .sidebar .nav-link.active { background: var(--primary-dark); }

        /* Submenu */
        .nav-item { width: 100%; }
        .nav-link[data-bs-toggle="collapse"] {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .nav-link[data-bs-toggle="collapse"] i.bi-chevron-down { transition: transform 0.3s; }
        .nav-link[data-bs-toggle="collapse"][aria-expanded="true"] i.bi-chevron-down { transform: rotate(180deg); }
        .submenu-link { padding-left: 42px !important; font-size: 13px; }
        .submenu-link i { font-size: 14px; min-width: 20px; }

        .sidebar .bottom-section {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 14px;
            border-top: 1px solid var(--border-light);
            background: inherit;
        }
        .dark-mode .sidebar .bottom-section { border-top-color: var(--border-dark); }

        /* ========== MAIN CONTENT ========== */
        .main-content {
            margin-left: 0;
            padding: 20px 30px;
            transition: margin-left var(--transition), width var(--transition);
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

        @media (max-width: 991px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }

        /* ========== TOP NAVBAR ========== */
        .top-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .menu-toggle {
            font-size: 24px;
            cursor: pointer;
            display: inline-block;
        }
        .search-bar {
            position: relative;
            width: 300px;
            max-width: 100%;
        }
        .search-bar input {
            width: 100%;
            padding: 12px 40px 12px 20px;
            border-radius: 40px;
            border: 1px solid var(--border-light);
            background: var(--light-card);
            color: var(--light-text);
            transition: var(--transition);
        }
        .dark-mode .search-bar input {
            background: var(--dark-card);
            border-color: var(--border-dark);
            color: var(--dark-text);
        }
        .search-bar input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255,165,0,0.2);
        }
        .search-bar i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        .nav-icons {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .nav-icons .icon {
            position: relative;
            font-size: 22px;
            color: var(--light-text);
            cursor: pointer;
            transition: color 0.2s;
        }
        .dark-mode .nav-icons .icon { color: var(--dark-text); }
        .nav-icons .icon:hover { color: var(--primary); }
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
            transition: color 0.2s;
        }
        .avatar-icon:hover { color: var(--primary-dark); }

        /* ========== CARDS ========== */
        .card {
            border: none;
            border-radius: 20px;
            padding: 14px;
            background: var(--light-card);
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .dark-mode .card {
            background: var(--dark-card);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 10px;
        }
        .dark-mode .card-title { color: #adb5bd; }
        .card-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--light-text);
            margin-bottom: 5px;
        }
        .dark-mode .card-value { color: var(--dark-text); }

        .badge-success { background: rgba(40,167,69,0.15); color: #28a745; }
        .badge-warning { background: rgba(255,193,7,0.15); color: #ffc107; }
        .badge-danger { background: rgba(220,53,69,0.15); color: #dc3545; }
        .badge-info { background: rgba(23,162,184,0.15); color: #17a2b8; }
        .badge-primary { background: rgba(255,165,0,0.15); color: var(--primary); }

        .btn-primary {
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            color: #fff;
            border: none;
            border-radius: 40px;
            padding: 10px 24px;
            box-shadow: 0 4px 14px rgba(255,165,0,0.3);
            transition: all 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255,165,0,0.5);
        }

        /* ========== TABLES ========== */
        .table-responsive {
            overflow-x: auto;
            min-width: 100%;
            border-radius: 20px;
            background: var(--light-card);
            padding: 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid var(--border-light);
        }
        .dark-mode .table-responsive {
            background: var(--dark-card);
            border-color: var(--border-dark);
        }
        .table {
            width: 100%;
            margin-bottom: 0;
            color: var(--light-text);
            background: transparent;
        }
        .dark-mode .table { color: var(--dark-text); }
        .table th {
            background: rgba(0,0,0,0.02);
            border-bottom: 2px solid var(--border-light);
            font-weight: 600;
            color: #6c757d;
            padding: 15px 12px;
            white-space: nowrap;
        }
        .dark-mode .table th {
            background: rgba(255,255,255,0.02);
            border-bottom-color: var(--border-dark);
            color: #adb5bd;
        }
        .table td {
            border-bottom: 1px solid var(--border-light);
            padding: 12px;
            vertical-align: middle;
        }
        .dark-mode .table td { border-bottom-color: var(--border-dark); }
        .table tbody tr:last-child td { border-bottom: none; }
        .table tbody tr:hover { background: rgba(0,0,0,0.02); }
        .dark-mode .table tbody tr:hover { background: rgba(255,255,255,0.02); }

        /* ========== CHARTS ========== */
        .card canvas {
            max-width: 100% !important;
            height: auto !important;
            max-height: 250px;
            width: 100% !important;
        }

        @media (min-width: 1400px) {
            .card canvas { max-height: 320px; }
            .table th, .table td { padding: 16px 18px; }
        }

        /* ========== FOOTER ========== */
        .footer {
            background: var(--light-card);
            border-top: 1px solid var(--border-light);
            padding: 30px 0;
            margin-top: 60px;
            color: #6c757d;
        }
        .dark-mode .footer {
            background: var(--dark-card);
            border-top-color: var(--border-dark);
            color: #adb5bd;
        }

        /* ========== DROPDOWNS ========== */
        .dropdown-menu {
            background: var(--light-card);
            border: 1px solid var(--border-light);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-height: 400px;
            overflow-y: auto;
        }
        .dark-mode .dropdown-menu {
            background: var(--dark-card);
            border-color: var(--border-dark);
        }
        .dropdown-item {
            color: var(--light-text);
            white-space: normal;
            word-wrap: break-word;
        }
        .dark-mode .dropdown-item { color: var(--dark-text); }
        .dropdown-item:hover { background: rgba(255,165,0,0.1); }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 992px) {
            .sidebar {
                left: -100%;
            }
            .sidebar.active {
                left: 0;
            }
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
            .search-bar {
                width: 250px;
            }
        }
        @media (max-width: 768px) {
            .top-navbar {
                flex-direction: column;
                align-items: stretch;
            }
            .search-bar { width: 100%; }
            .nav-icons { justify-content: flex-end; }
            .table th, .table td { padding: 10px 8px; font-size: 14px; }
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay (mobile) -->
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
        <!-- Dashboard -->
        <a href="dashboard.php" class="nav-link" title="Dashboard">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>

        <!-- Movies -->
        <a href="movies.php" class="nav-link" title="Movies">
            <i class="bi bi-film"></i>
            <span>Movies</span>
        </a>

        <!-- Theatres (with submenu) -->
        <div class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#theatresSubmenu" role="button" aria-expanded="false" aria-controls="theatresSubmenu" title="Theatres">
                <i class="bi bi-building"></i>
                <span>Theatres</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <div class="collapse" id="theatresSubmenu">
                <a href="theatres.php" class="nav-link submenu-link" title="All Theatres">
                    <i class="bi bi-list-ul"></i>
                    <span>All Theatres</span>
                </a>
                <a href="add_theatre.php" class="nav-link submenu-link" title="Add Theatre">
                    <i class="bi bi-plus-circle"></i>
                    <span>Add Theatre</span>
                </a>
            </div>
        </div>

        <!-- Bookings (direct link) -->
        <a href="bookings.php" class="nav-link" title="Bookings">
            <i class="bi bi-ticket"></i>
            <span>Bookings</span>
        </a>

        <!-- Users (with submenu) -->
            <div class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#usersSubmenu" role="button"
                    aria-expanded="false" aria-controls="usersSubmenu" title="Users">
                    <i class="bi bi-people"></i>
                    <span>Users</span>
                    <i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <div class="collapse" id="usersSubmenu">
                    <a href="users.php" class="nav-link submenu-link" title="All Users">
                        <i class="bi bi-list-ul"></i>
                        <span>All Users</span>
                    </a>
                    <a href="add_user.php" class="nav-link submenu-link" title="Add User">
                        <i class="bi bi-plus-circle"></i>
                        <span>Add User</span>
                    </a>
                </div>
            </div>

        <!-- Analytics -->
        <a href="analytics.php" class="nav-link" title="Analytics">
            <i class="bi bi-graph-up"></i>
            <span>Analytics</span>
        </a>

        <!-- Messages -->
        <a href="messages.php" class="nav-link" title="Messages">
            <i class="bi bi-chat-dots"></i>
            <span>Messages</span>
        </a>

        <!-- Settings (with submenu) -->
        <div class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#settingsSubmenu" role="button" aria-expanded="false" aria-controls="settingsSubmenu" title="Settings">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <div class="collapse" id="settingsSubmenu">
                <a href="settings.php" class="nav-link submenu-link" title="General Settings">
                    <i class="bi bi-sliders2"></i>
                    <span>General</span>
                </a>
                <a href="email_settings.php" class="nav-link submenu-link" title="Email Settings">
                    <i class="bi bi-envelope"></i>
                    <span>Email</span>
                </a>
            </div>
        </div>
    </div>

    <div class="bottom-section">
        <a href="../logout.php" class="nav-link" title="Logout">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="top-navbar">
                <div class="d-flex align-items-center flex-grow-1">
                    <i class="bi bi-list menu-toggle me-3" id="menuToggle"></i>
                    <div class="search-bar">
                        <input type="text" placeholder="Search...">
                        <i class="bi bi-search"></i>
                    </div>
                </div>
                <div class="nav-icons">
                    <div class="dropdown d-inline-block">
                        <div class="icon position-relative" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" role="button">
                            <i class="bi bi-bell"></i>
                            <span class="badge" id="notificationBadge" style="display: none;">0</span>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown" style="width: 300px;">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li id="notificationList" style="max-height: 300px; overflow-y: auto;"></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center small" href="#" id="markAllRead">Mark all as read</a></li>
                        </ul>
                    </div>
                    <div class="theme-toggle" id="themeToggle"><i class="bi bi-moon"></i></div>
                    <i class="bi bi-person-circle avatar-icon"></i>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h2>Analytics Overview</h2>
                <select class="form-select w-auto" id="periodSelect">
                    <option value="7">Last 7 days</option>
                    <option value="30" selected>Last 30 days</option>
                    <option value="90">Last 90 days</option>
                </select>
            </div>

            <!-- KPI Cards -->
            <div class="row g-4 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="card">
                        <div class="card-title">Total Revenue</div>
                        <div class="card-value">$<?= number_format($revenue_total, 2) ?></div>
                        <small class="text-success">+12.5%</small>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card">
                        <div class="card-title">Bookings</div>
                        <div class="card-value"><?= number_format($bookings_total) ?></div>
                        <small class="text-success">+8.2%</small>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card">
                        <div class="card-title">Avg. Ticket</div>
                        <div class="card-value">$<?= number_format($avg_ticket, 2) ?></div>
                        <small class="text-warning">+3.1%</small>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card">
                        <div class="card-title">Conversion</div>
                        <div class="card-value"><?= $conversion_rate ?>%</div>
                        <small class="text-success">+1.2%</small>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card">
                        <h5 class="mb-3">Daily Bookings (Last 7 Days)</h5>
                        <div style="height:250px;"><canvas id="dailyBookingsChart"></canvas></div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <h5 class="mb-3">Revenue by Movie (Top 5)</h5>
                        <div style="height:250px;"><canvas id="revenueByMovieChart"></canvas></div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card">
                        <h5 class="mb-3">User Growth (Last 4 Weeks)</h5>
                        <div style="height:250px;"><canvas id="userGrowthChart"></canvas></div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <h5 class="mb-3">Booking Status Distribution</h5>
                        <div style="height:250px;"><canvas id="statusPieChart"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer text-center">
        <div class="container">
            <p class="small"><?= htmlspecialchars($settings['footer_text'] ?? '© '.date('Y').' Popcorn Hub. All rights reserved.') ?></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ========== NOTIFICATIONS ==========
        function updateNotifications() {
            fetch('get_notifications.php')
                .then(res => res.json())
                .then(data => {
                    const badge = document.getElementById('notificationBadge');
                    const list = document.getElementById('notificationList');
                    if (data.notifications && data.notifications.length > 0) {
                        badge.textContent = data.notifications.length;
                        badge.style.display = 'flex';
                        list.innerHTML = '';
                        data.notifications.forEach(notif => {
                            const item = document.createElement('li');
                            item.innerHTML = `<a class="dropdown-item" href="${notif.link}">${notif.message}<br><small class="text-muted">${new Date(notif.created_at).toLocaleString()}</small></a>`;
                            list.appendChild(item);
                        });
                    } else {
                        badge.style.display = 'none';
                        list.innerHTML = '<li><span class="dropdown-item-text text-muted">No new notifications</span></li>';
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

        // ========== SIDEBAR TOGGLE ==========
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const menuToggle = document.getElementById('menuToggle');
        const sidebarToggleBtn = document.getElementById('sidebarToggle');

        function toggleSidebar() {
            if (window.innerWidth >= 992) {
                sidebar.classList.toggle('collapsed');
                document.body.classList.toggle('sidebar-collapsed');
            } else {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            }
        }
        if (menuToggle) menuToggle.addEventListener('click', toggleSidebar);
        if (sidebarToggleBtn) {
            sidebarToggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (window.innerWidth >= 992) {
                    sidebar.classList.toggle('collapsed');
                    document.body.classList.toggle('sidebar-collapsed');
                }
            });
        }
        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        }
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                }
            });
        });
        (function () {
            const currentFile = window.location.pathname.split('/').pop();

            if (['theatres.php', 'add_theatre.php', 'edit_theatre.php'].includes(currentFile)) {
                const submenu = document.getElementById('theatresSubmenu');
                if (submenu) submenu.classList.add('show');
            }
            if (['users.php', 'add_user.php', 'edit_user.php', 'update_user.php'].includes(currentFile)) {
                const submenu = document.getElementById('usersSubmenu');
                if (submenu) submenu.classList.add('show');
            }
            if (['settings.php', 'email_settings.php'].includes(currentFile)) {
                const submenu = document.getElementById('settingsSubmenu');
                if (submenu) submenu.classList.add('show');
            }

            function clearActiveStates() {
                document.querySelectorAll('.sidebar .nav-link').forEach(link => link.classList.remove('active'));
            }

            function markActive(link) {
                if (!link) return;
                link.classList.add('active');
                if (link.classList.contains('submenu-link')) {
                    const collapseEl = link.closest('.collapse');
                    if (collapseEl) {
                        const parentToggle = document.querySelector('.sidebar .nav-link[data-bs-toggle="collapse"][href="#' + collapseEl.id + '"]');
                        if (parentToggle) parentToggle.classList.add('active');
                    }
                }
            }

            function updateActiveStates() {
                clearActiveStates();
                const activeByHref = document.querySelector('.sidebar .nav-link[href="' + currentFile + '"]');
                if (activeByHref) markActive(activeByHref);
            }

            document.querySelectorAll('.sidebar .nav-link[data-bs-toggle="collapse"]').forEach(toggle => {
                toggle.addEventListener('click', function () {
                    clearActiveStates();
                    this.classList.add('active');
                });
            });

            document.querySelectorAll('.sidebar .submenu-link').forEach(link => {
                link.addEventListener('click', function () {
                    clearActiveStates();
                    markActive(this);
                });
            });

            updateActiveStates();
        })();

        // ========== CHARTS ==========
        new Chart(document.getElementById('dailyBookingsChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($daily_labels) ?>,
                datasets: [{ label: 'Bookings', data: <?= json_encode($daily_data) ?>, backgroundColor: '#FFA500' }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
        new Chart(document.getElementById('revenueByMovieChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($movie_revenue_labels) ?>,
                datasets: [{ label: 'Revenue ($)', data: <?= json_encode($movie_revenue_data) ?>, backgroundColor: '#FFD966' }]
            },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false }
        });
        new Chart(document.getElementById('userGrowthChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($user_growth_labels) ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?= json_encode($user_growth_data) ?>,
                    borderColor: '#FFA500',
                    backgroundColor: 'rgba(255,165,0,0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
        new Chart(document.getElementById('statusPieChart'), {
            type: 'pie',
            data: {
                labels: <?= json_encode($status_labels) ?>,
                datasets: [{
                    data: <?= json_encode($status_data) ?>,
                    backgroundColor: <?= json_encode(array_slice($status_colors, 0, count($status_labels))) ?>
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });

        document.getElementById('periodSelect').addEventListener('change', function () {
            alert('Period changed to ' + this.value + ' days. (Implement AJAX to refresh data)');
        });
    </script>
</body>
</html>