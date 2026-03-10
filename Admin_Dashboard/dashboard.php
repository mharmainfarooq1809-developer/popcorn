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
    <title>Dashboard · <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
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
        /* ========== UNIFIED ADMIN CSS (same for all pages) ========== */
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

        /* ===== SIDEBAR OVERLAY ===== */
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

        /* ===== SIDEBAR ===== */
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

        .sidebar .nav {
            padding: 12px 0 96px;
            display: block;
        }
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

        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: 0;
            padding: 20px 30px;
            transition: margin-left var(--transition), width var(--transition);
            min-height: 100vh;
            width: 100%;
            overflow-x: hidden;
        }

        @media (min-width: 992px) {
            .sidebar { transform: translateX(0); }
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
            .main-content { margin-left: 0; width: 100%; }
        }

        /* ===== TOP NAVBAR ===== */
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

        /* ===== CARDS ===== */
        .card {
            border: none;
            border-radius: 20px;
            padding: 14px;
            background: var(--light-card);
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: var(--transition);
            margin-bottom: 20px;
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
        .btn-outline-primary {
            border: 1px solid var(--primary);
            color: var(--primary);
            background: transparent;
        }
        .btn-outline-primary:hover {
            background: var(--primary);
            color: #fff;
        }
        .btn-outline-danger {
            border: 1px solid #dc3545;
            color: #dc3545;
            background: transparent;
        }
        .btn-outline-danger:hover {
            background: #dc3545;
            color: #fff;
        }
        .btn-sm {
            padding: 6px 16px;
            font-size: 13px;
        }

        /* ===== TABLES ===== */
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

        /* ===== FOOTER ===== */
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

        /* ===== DROPDOWNS ===== */
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

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .sidebar { left: -100%; }
            .sidebar.active { left: 0; }
            .main-content { margin-left: 0 !important; width: 100% !important; }
            .search-bar { width: 250px; }
        }
        @media (max-width: 768px) {
            .top-navbar { flex-direction: column; align-items: stretch; }
            .search-bar { width: 100%; }
            .nav-icons { justify-content: flex-end; }
            .table th, .table td { padding: 10px 8px; font-size: 14px; }
        }

        /* Movie grid (specific to movies.php) */
        .movie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .movie-card {
            background: var(--light-card);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            transition: var(--transition);
        }
        .dark-mode .movie-card {
            background: var(--dark-card);
        }
        .movie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(255,165,0,0.2);
        }
        .movie-card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }
        .movie-card .card-body {
            padding: 12px;
        }
        .movie-card .card-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--light-text);
        }
        .dark-mode .movie-card .card-title {
            color: var(--dark-text);
        }
        .movie-card .card-meta {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 8px;
        }
        .movie-card .card-buttons {
            display: flex;
            gap: 8px;
        }
    </style>
    <style id="admin-sidebar-unify">
        /* Unified admin sidebar animation + responsive behavior */
        .sidebar {
            transition: width 0.28s ease, transform 0.28s ease;
            will-change: width, transform;
        }
        .main-content {
            transition: margin-left 0.28s ease, width 0.28s ease;
        }
        .sidebar .logo span,
        .sidebar .nav-link span {
            transition: opacity 0.22s ease, max-width 0.22s ease, margin 0.22s ease;
            max-width: 180px;
            overflow: hidden;
        }
        .sidebar.collapsed {
            width: var(--sidebar-collapsed, var(--sidebar-collapsed-width, 80px)) !important;
            min-width: var(--sidebar-collapsed, var(--sidebar-collapsed-width, 80px)) !important;
            max-width: var(--sidebar-collapsed, var(--sidebar-collapsed-width, 80px)) !important;
        }
        .sidebar.collapsed .logo span,
        .sidebar.collapsed .nav-link span {
            opacity: 0;
            max-width: 0;
            margin: 0;
        }
        #sidebarToggle i {
            transition: transform 0.25s ease;
        }
        body.sidebar-collapsed #sidebarToggle i {
            transform: rotate(180deg);
        }

        /* Remove admin search bars everywhere */
        .search-bar {
            display: none !important;
        }
        .top-navbar {
            justify-content: flex-end;
            gap: 12px;
        }

        /* Extra safety for small screens */
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
            .top-navbar {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="top-navbar">
                <div class="d-flex align-items-center flex-grow-1">
                    <i class="bi bi-list menu-toggle me-3" id="menuToggle"></i>
                    
                </div>
                <div class="nav-icons">
                    <!-- Notification Bell Dropdown -->
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
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <h5 class="mb-0">Revenue Overview (Last 7 Days)</h5>
                            <select class="form-select w-auto" style="min-width:150px;" id="revenueRange">
                                <option>Last 7 days</option>
                                <option>Last 30 days</option>
                                <option>Last 90 days</option>
                            </select>
                        </div>
                        <div style="height:250px;">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <h5 class="mb-3">Booking Sources</h5>
                        <div style="height:250px;">
                            <canvas id="sourceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings Table -->
            <div class="card">
                <h5 class="mb-3">Recent Bookings</h5>
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
                                        <td data-label="Booking ID">#BK-<?= str_pad($display_booking_id++, 4, '0', STR_PAD_LEFT) ?></td>
                                        <td data-label="Customer"><?= htmlspecialchars($row['customer']) ?></td>
                                        <td data-label="Movie"><?= htmlspecialchars($row['movie']) ?></td>
                                        <td data-label="Date"><?= date('Y-m-d', strtotime($row['show_date'])) ?></td>
                                        <td data-label="Amount">$<?= number_format($row['total_price'], 2) ?></td>
                                        <td data-label="Status">
                                            <?php
                                            $class = 'badge-info';
                                            if ($row['status'] == 'confirmed') $class = 'badge-success';
                                            elseif ($row['status'] == 'pending') $class = 'badge-warning';
                                            elseif ($row['status'] == 'cancelled') $class = 'badge-danger';
                                            ?>
                                            <span class="badge <?= $class ?>"><?= ucfirst($row['status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center">No recent bookings found.</td></tr>
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
                .then(data => {
                    if (data.success) updateNotifications();
                });
        });

        updateNotifications();
        setInterval(updateNotifications, 30000);

        // ========== SIDEBAR TOGGLE ==========
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const menuToggle = document.getElementById('menuToggle');
        const sidebarToggleBtn = document.getElementById('sidebarToggle'); // chevron inside sidebar

        function toggleSidebar() {
            if (window.innerWidth >= 992) {
                sidebar.classList.toggle('collapsed');
                document.body.classList.toggle('sidebar-collapsed');
            } else {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            }
        }

        if (menuToggle) {
            menuToggle.addEventListener('click', toggleSidebar);
        }

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

        // ========== DARK MODE ==========
        document.getElementById('themeToggle').addEventListener('click', function () {
            document.body.classList.toggle('dark-mode');
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-moon');
                icon.classList.toggle('bi-sun');
            }
        });

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




