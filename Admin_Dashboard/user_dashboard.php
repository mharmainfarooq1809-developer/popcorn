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

// Get user ID from URL
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if (!$user_id) {
    header("Location: users.php");
    exit;
}

// Fetch user details
$stmt = $conn->prepare("SELECT name, email, points, role, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found.");
}

// Get points history
$stmt = $conn->prepare("SELECT * FROM points_history WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$history = $stmt->get_result();

// Get recent bookings
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
        COALESCE(s.theatre, 'N/A') AS theatre
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard · <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
    <?php if (!empty($settings['theme_color'])): ?>
        <style>
            :root { --primary: <?= htmlspecialchars($settings['theme_color']) ?>; }
            .btn-primary { background: linear-gradient(145deg, var(--primary), var(--primary-dark)); }
        </style>
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* ========== FULL ADMIN CSS ========== */
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
            color: #F2F2F2;
        }

        :root {
            --primary: #FFA500;
            --primary-dark: #cc7f00;
            --primary-gold: #FFD966;
            --light-bg: #F8F9FA;
            --dark-bg: #0B1623;
            --light-card: #FFFFFF;
            --dark-card: #0F1C2B;
            --light-text: #212529;
            --dark-text: #F2F2F2;
            --border-light: #E9ECEF;
            --border-dark: #3A414D;
            --sidebar-width: 260px;
            --sidebar-collapsed: 80px;
            --transition: all 0.3s ease;
        }

        /* Overlay for mobile sidebar */
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

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            max-width: var(--sidebar-width);
            background: var(--light-card);
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.05);
            transition: transform var(--transition), width var(--transition);
            z-index: 1000;
            overflow-y: auto;
            border-right: 1px solid var(--border-light);
            transform: translateX(-100%);
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .dark-mode .sidebar {
            background: var(--dark-card);
            border-right-color: var(--border-dark);
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed);
        }

        .sidebar .logo-area {
            padding: 24px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-light);
        }

        .dark-mode .sidebar .logo-area {
            border-bottom-color: var(--border-dark);
        }

        .sidebar .logo {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-gold);
            white-space: nowrap;
            overflow: hidden;
        }

        .sidebar.collapsed .logo span {
            display: none;
        }

        .sidebar .toggle-btn {
            background: none;
            border: none;
            color: var(--light-text);
            cursor: pointer;
            font-size: 20px;
            transition: color 0.2s;
        }

        .dark-mode .sidebar .toggle-btn {
            color: var(--dark-text);
        }

        .sidebar .toggle-btn:hover {
            color: var(--primary);
        }

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

        .dark-mode .sidebar .nav-link {
            color: var(--dark-text);
        }

        .sidebar .nav-link i {
            font-size: 17px; min-width: 24px;
            text-align: center;
        }

        .sidebar.collapsed .nav-link span {
            display: none;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 165, 0, 0.1);
            color: var(--primary);
        }

        .sidebar .nav-link.active {
            background: var(--primary);
            color: #fff;
        }

        .dark-mode .sidebar .nav-link.active {
            background: var(--primary-dark);
            color: #fff;
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

        .dark-mode .sidebar .bottom-section {
            border-top-color: var(--border-dark);
        }

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
                margin-left: var(--sidebar-collapsed);
                width: calc(100% - var(--sidebar-collapsed));
            }
        }

        @media (max-width: 991px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }

        .top-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .menu-toggle-mobile {
            font-size: 24px;
            cursor: pointer;
            display: inline-block;
        }

        @media (min-width: 992px) {
            .menu-toggle-mobile {
                display: none;
            }
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
            font-family: 'Heebo', sans-serif;
        }

        .dark-mode .search-bar input {
            background: var(--dark-card);
            border-color: var(--border-dark);
            color: var(--dark-text);
        }

        .search-bar input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 165, 0, 0.2);
        }

        .search-bar i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            pointer-events: none;
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

        .dark-mode .nav-icons .icon {
            color: var(--dark-text);
        }

        .nav-icons .icon:hover {
            color: var(--primary);
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
            font-weight: 600;
        }

        .dropdown-menu {
            background: var(--light-card);
            border: 1px solid var(--border-light);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
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

        .dark-mode .dropdown-item {
            color: var(--dark-text);
        }

        .dropdown-item:hover {
            background: rgba(255, 165, 0, 0.1);
        }

        .avatar-icon {
            font-size: 2.2rem;
            color: var(--primary);
            cursor: pointer;
            transition: color 0.2s;
        }

        .avatar-icon:hover {
            color: var(--primary-dark);
        }

        .card {
            border: none;
            border-radius: 20px;
            padding: 14px;
            background: var(--light-card);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
            margin-bottom: 20px;
        }

        .dark-mode .card {
            background: var(--dark-card);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .dark-mode .card-title {
            color: #adb5bd;
        }

        .card-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--light-text);
            margin-bottom: 5px;
        }

        .dark-mode .card-value {
            color: var(--dark-text);
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }

        .badge-success {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }

        .badge-warning {
            background: rgba(255, 193, 7, 0.15);
            color: #ffc107;
        }

        .badge-danger {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }

        .badge-info {
            background: rgba(23, 162, 184, 0.15);
            color: #17a2b8;
        }

        .badge-primary {
            background: rgba(255, 165, 0, 0.15);
            color: var(--primary);
        }

        .btn {
            display: inline-block;
            padding: 10px 24px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            text-align: center;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            color: #fff;
            box-shadow: 0 4px 14px rgba(255, 165, 0, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 165, 0, 0.5);
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

        .btn-outline-secondary {
            border-radius: 40px;
            padding: 10px 24px;
            border: 1px solid var(--border-light);
            color: var(--light-text);
            background: transparent;
            transition: var(--transition);
        }

        .dark-mode .btn-outline-secondary {
            border-color: var(--border-dark);
            color: var(--dark-text);
        }

        .btn-outline-secondary:hover {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        .table-responsive {
            border-radius: 20px;
            background: var(--light-card);
            padding: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
        }

        .dark-mode .table-responsive {
            background: var(--dark-card);
        }

        .table {
            width: 100%;
            margin-bottom: 0;
            color: var(--light-text);
            border-collapse: separate;
            border-spacing: 0;
        }

        .dark-mode .table {
            color: var(--dark-text);
        }

        .table th {
            border-bottom: 2px solid var(--border-light);
            font-weight: 600;
            color: #6c757d;
            padding: 12px 8px;
            text-align: left;
        }

        .dark-mode .table th {
            border-bottom-color: var(--border-dark);
            color: #adb5bd;
        }

        .table td {
            border-bottom: 1px solid var(--border-light);
            padding: 12px 8px;
            vertical-align: middle;
        }

        .dark-mode .table td {
            border-bottom-color: var(--border-dark);
        }

        .table tbody tr:hover {
            background: rgba(0, 0, 0, 0.02);
        }

        .dark-mode .table tbody tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

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

        /* Progress bar */
        .progress-bar-container {
            width: 100%;
            height: 8px;
            background: var(--gray-1);
            border-radius: 4px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--popcorn-orange), var(--popcorn-gold));
            border-radius: 4px;
        }

        .points-value {
            font-size: 24px;
            font-weight: 800;
            color: var(--popcorn-gold);
        }

        /* Status badges for bookings */
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

            .search-bar {
                width: 100%;
            }

            .nav-icons {
                justify-content: flex-end;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <!-- Overlay for mobile sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="main-content">
        <div class="top-navbar">
            <div class="d-flex align-items-center flex-grow-1">
                <i class="bi bi-list menu-toggle-mobile me-3" id="mobileMenuToggle"></i>
                <div class="search-bar">
                    <input type="text" id="searchUsers" placeholder="Search...">
                    <i class="bi bi-search"></i>
                </div>
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
                        <li id="notificationList" style="max-height: 300px; overflow-y: auto;">
                            <!-- Notifications will be loaded here -->
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center small" href="#" id="markAllRead">Mark all as read</a></li>
                    </ul>
                </div>

                <div class="theme-toggle" id="themeToggle"><i class="bi bi-moon"></i></div>
                <i class="bi bi-person-circle avatar-icon"></i>
            </div>
        </div>

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>User Dashboard: <?= htmlspecialchars($user['name']) ?></h2>
            <a href="users.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Users</a>
        </div>

        <!-- User Info Card -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <h4 class="mb-3">User Information</h4>
                    <table class="table">
                        <tr><th>Name</th><td><?= htmlspecialchars($user['name']) ?></td></tr>
                        <tr><th>Email</th><td><?= htmlspecialchars($user['email']) ?></td></tr>
                        <tr><th>Role</th><td><?= ucfirst($user['role']) ?></td></tr>
                        <tr><th>Joined</th><td><?= date('Y-m-d', strtotime($user['created_at'])) ?></td></tr>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <h4 class="mb-3">Loyalty Points</h4>
                    <div class="points-value"><?= $user['points'] ?></div>
                    <?php if ($user['points'] >= 10): ?>
                        <div class="mt-2 text-success"><i class="bi bi-gift"></i> Eligible for 10% discount</div>
                    <?php else: ?>
                        <div class="mt-2 text-muted"><?= (10 - ($user['points'] % 10)) ?> more points until next discount</div>
                        <div class="progress-bar-container mt-2">
                            <div class="progress-fill" style="width: <?= ($user['points'] % 10) * 10 ?>%;"></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Points History -->
        <div class="card">
            <h4 class="mb-3">Points History</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Change</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($history->num_rows > 0): ?>
                            <?php while ($row = $history->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
                                    <td class="<?= $row['points_change'] > 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $row['points_change'] > 0 ? '+' : '' ?><?= $row['points_change'] ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['reason']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center">No points history.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="card">
            <h4 class="mb-3">Recent Bookings</h4>
            <div class="table-responsive">
                <table class="table">
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
                            <th>Ticket</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($bookings->num_rows > 0): ?>
                            <?php while ($row = $bookings->fetch_assoc()): 
                                $statusClass = '';
                                if ($row['status'] == 'confirmed') $statusClass = 'status-confirmed';
                                elseif ($row['status'] == 'pending') $statusClass = 'status-pending';
                                elseif ($row['status'] == 'cancelled') $statusClass = 'status-cancelled';
                            ?>
                                <tr>
                                    <td><?= date('Y-m-d', strtotime($row['booking_date'])) ?></td>
                                    <td><?= htmlspecialchars($row['movie_title']) ?></td>
                                    <td><?= htmlspecialchars($row['theatre']) ?></td>
                                    <td><?= $row['show_time'] != 'N/A' ? date('g:i A', strtotime($row['show_time'])) : 'N/A' ?></td>
                                    <td><?= htmlspecialchars($row['seats']) ?></td>
                                    <td>$<?= number_format($row['total_price'], 2) ?></td>
                                    <td><?= $row['points_earned'] ?></td>
                                    <td><span class="status-badge <?= $statusClass ?>"><?= ucfirst($row['status']) ?></span></td>
                                    <td>
                                        <a href="../ticket.php?booking_id=<?= $row['booking_id'] ?>" class="btn-sm btn-outline-primary" target="_blank">View</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center">No bookings yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Votes -->
        <div class="card">
            <h4 class="mb-3">Movies Voted For</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Movie</th>
                            <th>Genre</th>
                            <th>Year</th>
                            <th>Voted On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($votedMovies->num_rows > 0): ?>
                            <?php while ($vote = $votedMovies->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($vote['title']) ?></td>
                                    <td><?= htmlspecialchars($vote['genre'] ?? 'N/A') ?></td>
                                    <td><?= $vote['year'] ?? 'N/A' ?></td>
                                    <td><?= date('Y-m-d', strtotime($vote['voted_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center">No votes yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
        // ========== SIDEBAR & UI ==========
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function () {
                document.getElementById('sidebar').classList.toggle('collapsed');
                document.body.classList.toggle('sidebar-collapsed');
            });
        }

        const mobileToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (mobileToggle && sidebar && overlay) {
            mobileToggle.addEventListener('click', function () {
                sidebar.classList.add('active');
                overlay.classList.add('active');
            });
            overlay.addEventListener('click', function () {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        }

        document.getElementById('themeToggle').addEventListener('click', function () {
            document.body.classList.toggle('dark-mode');
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-moon');
                icon.classList.toggle('bi-sun');
            }
        });

        // ========== NOTIFICATIONS ==========
        function updateNotifications() {
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('notificationBadge');
                    const list = document.getElementById('notificationList');
                    if (badge && list) {
                        if (data.notifications && data.notifications.length > 0) {
                            badge.textContent = data.notifications.length;
                            badge.style.display = 'flex';
                            list.innerHTML = '';
                            data.notifications.forEach(notif => {
                                const item = document.createElement('li');
                                const link = notif.link ? notif.link : '#';
                                item.innerHTML = `<a class="dropdown-item" href="${link}">${notif.message}<br><small class="text-muted">${new Date(notif.created_at).toLocaleString()}</small></a>`;
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
                .then(data => {
                    if (data.success) updateNotifications();
                });
        });

        updateNotifications();
        setInterval(updateNotifications, 30000);
    </script>
</body>
</html>