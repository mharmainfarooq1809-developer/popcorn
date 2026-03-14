<?php
session_start();
require_once '../db_connect.php';
require_once '../settings_init.php';

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
        <style>:root { --primary: <?= htmlspecialchars($settings['theme_color']) ?>; }</style>
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
/* ========== UNIFIED ADMIN STYLES ========== */
:root {
    --primary: #FFA500;
    --primary-dark: #cc7f00;
    --primary-gold: #FFD966;
    --light-card: #FFFFFF;
    --dark-card: #1a2634; /* Slightly lighter dark card for better contrast */
    --light-text: #212529;
    --dark-text: #FFFFFF;
    --border-light: #E9ECEF;
    --border-dark: #3A414D;
    --sidebar-width: 250px;
    --sidebar-collapsed-width: 80px;
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Heebo', sans-serif;
    background-color: #F8F9FA;
    color: #212529;
    transition: var(--transition);
    overflow-x: hidden;
    line-height: 1.6;
}

body.dark-mode {
    background-color: #0B1623;
    color: #FFFFFF;
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

.card h4 {
    color: #212529;
    font-weight: 600;
    margin-bottom: 15px;
}

body.dark-mode .card h4 {
    color: #FFFFFF;
}

/* ===== TEXT UTILITIES ===== */
.text-muted {
    color: #6c757d !important;
}

body.dark-mode .text-muted {
    color: #AAAAAA !important;
}

.text-success {
    color: #28a745 !important;
}

body.dark-mode .text-success {
    color: #7acf7a !important;
}

.text-danger {
    color: #dc3545 !important;
}

body.dark-mode .text-danger {
    color: #ff8a92 !important;
}

/* ===== SIDEBAR ===== */
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

.sidebar-overlay.active {
    display: block;
}

.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: var(--sidebar-width);
    background: #FFFFFF;
    box-shadow: 2px 0 20px rgba(0,0,0,0.05);
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
}

.sidebar .nav-link:hover {
    background: rgba(255,165,0,0.1);
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
    justify-content: space-between;
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

.theme-toggle {
    cursor: pointer;
    font-size: 22px;
    color: #212529;
}

body.dark-mode .theme-toggle {
    color: #FFFFFF;
}

/* ===== CARDS ===== */
.card {
    border: none;
    border-radius: 20px;
    padding: 20px;
    background: #FFFFFF;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    margin-bottom: 20px;
    width: 100%;
    overflow: hidden;
}

body.dark-mode .card {
    background: #1a2634;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

/* ===== TABLES - FIXED VISIBILITY ===== */
.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 20px;
    margin-top: 10px;
}

/* LIGHT MODE TABLES */
.table {
    width: 100%;
    min-width: 600px;
    margin-bottom: 0;
    border-collapse: collapse;
    background: #FFFFFF;
    border-radius: 20px;
    overflow: hidden;
}

.table th {
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    color: #212529 !important; /* Dark text */
    padding: 12px;
    white-space: nowrap;
}

.table td {
    border-bottom: 1px solid #dee2e6;
    padding: 12px;
    vertical-align: middle;
    color: #212529 !important; /* Dark text */
    background: #FFFFFF;
}

/* DARK MODE TABLES */
body.dark-mode .table {
    background: #1a2634;
}

body.dark-mode .table th {
    background: #2a3644;
    border-bottom: 2px solid #3A414D;
    color: #FFFFFF !important; /* White text */
}

body.dark-mode .table td {
    border-bottom: 1px solid #3A414D;
    color: #FFFFFF !important; /* White text */
    background: #1a2634;
}

/* Hover effects */
.table tbody tr:hover td {
    background: rgba(0,0,0,0.02);
}

body.dark-mode .table tbody tr:hover td {
    background: #2a3644;
}

/* User info table (no min-width) */
.card .table {
    min-width: auto;
    background: transparent;
}

.card .table th {
    width: 30%;
    background: transparent;
    border-bottom: 1px solid #dee2e6;
    color: #495057 !important;
}

.card .table td {
    width: 70%;
    background: transparent;
    color: #212529 !important;
}

body.dark-mode .card .table th {
    color: #CCCCCC !important;
    border-bottom-color: #3A414D;
}

body.dark-mode .card .table td {
    color: #FFFFFF !important;
    background: transparent;
}

/* Status badges */
.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 30px;
    font-weight: 600;
    font-size: 12px;
    white-space: nowrap;
}

/* Light mode status badges */
.status-confirmed {
    background: rgba(40, 167, 69, 0.15);
    color: #28a745 !important;
}

.status-pending {
    background: rgba(255, 193, 7, 0.15);
    color: #b17f00 !important;
}

.status-cancelled {
    background: rgba(220, 53, 69, 0.15);
    color: #dc3545 !important;
}

/* Dark mode status badges */
body.dark-mode .status-confirmed {
    background: rgba(40, 167, 69, 0.25);
    color: #8fd98f !important;
}

body.dark-mode .status-pending {
    background: rgba(255, 193, 7, 0.25);
    color: #ffe08c !important;
}

body.dark-mode .status-cancelled {
    background: rgba(220, 53, 69, 0.25);
    color: #ffa0a8 !important;
}

/* Points history colors */
.table td.text-success {
    color: #28a745 !important;
    font-weight: 600;
}

.table td.text-danger {
    color: #dc3545 !important;
    font-weight: 600;
}

body.dark-mode .table td.text-success {
    color: #8fd98f !important;
}

body.dark-mode .table td.text-danger {
    color: #ffa0a8 !important;
}

/* Empty state */
.table td.text-center {
    color: #6c757d !important;
    padding: 30px !important;
    background: transparent;
}

body.dark-mode .table td.text-center {
    color: #AAAAAA !important;
}

/* ===== BUTTONS ===== */
.btn-primary {
    background: linear-gradient(145deg, #FFA500, #cc7f00);
    color: #FFFFFF;
    border: none;
    border-radius: 40px;
    padding: 8px 20px;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255,165,0,0.5);
}

.btn-outline-primary {
    border: 1px solid #FFA500;
    color: #FFA500;
    background: transparent;
    border-radius: 40px;
    padding: 4px 12px;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    text-decoration: none;
}

.btn-outline-primary:hover {
    background: #FFA500;
    color: #FFFFFF;
}

body.dark-mode .btn-outline-primary {
    border-color: #FFD966;
    color: #FFD966;
}

body.dark-mode .btn-outline-primary:hover {
    background: #FFD966;
    color: #1a2634;
}

.btn-outline-secondary {
    border-radius: 40px;
    padding: 8px 20px;
    border: 1px solid #dee2e6;
    color: #212529;
    background: transparent;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
}

.btn-outline-secondary:hover {
    background: #FFA500;
    color: #FFFFFF;
    border-color: #FFA500;
}

body.dark-mode .btn-outline-secondary {
    border-color: #3A414D;
    color: #FFFFFF;
}

/* ===== POINTS ===== */
.points-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: #FFD966;
    line-height: 1.2;
}

/* ===== PROGRESS BAR ===== */
.progress-bar-container {
    width: 100%;
    height: 8px;
    background: #E9ECEF;
    border-radius: 4px;
    overflow: hidden;
}

body.dark-mode .progress-bar-container {
    background: #2A3A4A;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #FFA500, #FFD966);
    border-radius: 4px;
}

/* ===== DROPDOWNS ===== */
.dropdown-menu {
    background: #FFFFFF;
    border: 1px solid #dee2e6;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
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
    background: rgba(255,165,0,0.1);
}

/* ===== FOOTER ===== */
.footer {
    background: #FFFFFF;
    border-top: 1px solid #dee2e6;
    padding: 20px 0;
    margin-top: 40px;
    color: #6c757d;
}

body.dark-mode .footer {
    background: #1a2634;
    border-top-color: #3A414D;
    color: #AAAAAA;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .main-content {
        padding: 15px;
    }
    
    .card {
        padding: 15px;
    }
    
    .points-value {
        font-size: 2rem;
    }
    
    .table th,
    .table td {
        padding: 8px;
        font-size: 13px;
    }
}
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="main-content">
        <div class="top-navbar">
            <div class="d-flex align-items-center">
                <i class="bi bi-list menu-toggle-mobile me-3" id="mobileMenuToggle"></i>
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
                        <li id="notificationList" style="max-height: 300px; overflow-y: auto;"></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center small" href="#" id="markAllRead">Mark all as read</a></li>
                    </ul>
                </div>
                <div class="theme-toggle" id="themeToggle"><i class="bi bi-moon"></i></div>
                <i class="bi bi-person-circle avatar-icon"></i>
            </div>
        </div>

        <!-- Header with Back Button -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <h2 class="mb-0">User Dashboard: <?= htmlspecialchars($user['name']) ?></h2>
            <a href="users.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Users
            </a>
        </div>

        <!-- User Info Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <h4>User Information</h4>
                    <table class="table">
                        <tr><th>Name</th><td><?= htmlspecialchars($user['name']) ?></td></tr>
                        <tr><th>Email</th><td><?= htmlspecialchars($user['email']) ?></td></tr>
                        <tr><th>Role</th><td><?= ucfirst($user['role']) ?></td></tr>
                        <tr><th>Joined</th><td><?= date('Y-m-d', strtotime($user['created_at'])) ?></td></tr>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <h4>Loyalty Points</h4>
                    <div class="points-value"><?= $user['points'] ?></div>
                    <?php if ($user['points'] >= 10): ?>
                        <div class="mt-2 text-success">
                            <i class="bi bi-gift-fill"></i> Eligible for 10% discount
                        </div>
                    <?php else: ?>
                        <div class="mt-2 text-muted">
                            <?= (10 - ($user['points'] % 10)) ?> more points until next discount
                        </div>
                        <div class="progress-bar-container mt-2">
                            <div class="progress-fill" style="width: <?= ($user['points'] % 10) * 10 ?>%;"></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Points History -->
        <div class="card">
            <h4>Points History</h4>
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
                                    <td class="<?= $row['points_change'] > 0 ? 'text-success' : 'text-danger' ?> fw-bold">
                                        <?= $row['points_change'] > 0 ? '+' : '' ?><?= $row['points_change'] ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['reason']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center py-4">No points history</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="card">
            <h4>Recent Bookings</h4>
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
                                        <a href="../ticket.php?booking_id=<?= $row['booking_id'] ?>" class="btn-sm btn-outline-primary" target="_blank">
                                            <i class="bi bi-ticket"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center py-4">No bookings yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>


    <footer class="footer text-center">
        <div class="container">
            <p class="small mb-0"><?= htmlspecialchars($settings['footer_text'] ?? '© '.date('Y').' Popcorn Hub. All rights reserved.') ?></p>
        </div>
    </footer>

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
    </script>
</body>
</html>