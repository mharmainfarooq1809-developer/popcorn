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

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$booking_id) {
    header("Location: bookings.php");
    exit;
}

// Fetch booking details with joins
$query = "
    SELECT 
        b.id AS booking_id,
        b.user_id,
        b.showtime_id,
        b.seats,
        b.adults,
        b.children,
        b.total_price,
        b.discount_applied,
        b.status,
        b.booking_date,
        u.name AS customer_name,
        u.email AS customer_email,
        u.points AS customer_points,
        s.show_date,
        s.show_time,
        s.theatre,
        m.id AS movie_id,
        m.title AS movie_title,
        m.genre,
        m.language,
        m.year,
        m.image_url
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    WHERE b.id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    header("Location: bookings.php");
    exit;
}

// Status badge function
function statusBadge($status) {
    switch ($status) {
        case 'confirmed':
            return '<span class="badge badge-success">Confirmed</span>';
        case 'pending':
            return '<span class="badge badge-warning">Pending</span>';
        case 'cancelled':
            return '<span class="badge badge-danger">Cancelled</span>';
        default:
            return '<span class="badge badge-info">' . htmlspecialchars($status) . '</span>';
    }
}

// Prepare email link (opens messages.php with user ID)
$messageLink = "messages.php?user_id=" . $booking['user_id'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking #BK-<?= str_pad($booking['booking_id'], 4, '0', STR_PAD_LEFT) ?> · <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
    <?php if (!empty($settings['theme_color'])): ?>
        <style>
            :root { --primary: <?= htmlspecialchars($settings['theme_color']) ?>; }
            .btn-primary { background: linear-gradient(145deg, var(--primary), var(--primary-dark)); }
        </style>
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* ================= FULL PREMIUM ADMIN CSS ================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box
        }

        body {
            font-family: 'Heebo', sans-serif;
            background-color: #F8F9FA;
            color: #212529;
            transition: all 0.3s ease;
            overflow-x: hidden;
            line-height: 1.6
        }

        body.dark-mode {
            background-color: #0B1623;
            color: #F2F2F2
        }

        :root {
            --primary: #FFA500;
            --primary-dark: #cc7f00;
            --primary-gold: #FFD966;
            --secondary: #FFB347;
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
            --transition: all 0.3s ease
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--light-card);
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
            z-index: 1000;
            overflow-y: auto;
            border-right: 1px solid var(--border-light)
        }

        .dark-mode .sidebar {
            background: var(--dark-card);
            border-right-color: var(--border-dark)
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed)
        }

        .sidebar .logo-area {
            padding: 24px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-light)
        }

        .dark-mode .sidebar .logo-area {
            border-bottom-color: var(--border-dark)
        }

        .sidebar .logo {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-gold);
            white-space: nowrap;
            overflow: hidden
        }

        .sidebar.collapsed .logo span {
            display: none
        }

        .sidebar .toggle-btn {
            background: none;
            border: none;
            color: var(--light-text);
            cursor: pointer;
            font-size: 20px;
            transition: color 0.2s
        }

        .dark-mode .sidebar .toggle-btn {
            color: var(--dark-text)
        }

        .sidebar .toggle-btn:hover {
            color: var(--primary)
        }

        .sidebar .nav {
            padding: 20px 0
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
            white-space: nowrap
        }

        .dark-mode .sidebar .nav-link {
            color: var(--dark-text)
        }

        .sidebar .nav-link i,
        .sidebar .nav-link svg {
            font-size: 17px; min-width: 24px;
            text-align: center
        }

        .sidebar.collapsed .nav-link span {
            display: none
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 165, 0, 0.1);
            color: var(--primary)
        }

        .sidebar .nav-link.active {
            background: var(--primary);
            color: #fff
        }

        .dark-mode .sidebar .nav-link.active {
            background: var(--primary-dark);
            color: #fff
        }

        .sidebar .bottom-section {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 14px;
            border-top: 1px solid var(--border-light);
            background: inherit
        }

        .dark-mode .sidebar .bottom-section {
            border-top-color: var(--border-dark)
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px 30px;
            transition: var(--transition);
            min-height: 100vh
        }

        .sidebar.collapsed+.main-content {
            margin-left: var(--sidebar-collapsed)
        }

        .top-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px
        }

        .search-bar {
            position: relative;
            width: 300px
        }

        .search-bar input {
            width: 100%;
            padding: 12px 40px 12px 20px;
            border-radius: 40px;
            border: 1px solid var(--border-light);
            background: var(--light-card);
            color: var(--light-text);
            transition: var(--transition);
            font-family: 'Heebo', sans-serif
        }

        .dark-mode .search-bar input {
            background: var(--dark-card);
            border-color: var(--border-dark);
            color: var(--dark-text)
        }

        .search-bar input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 165, 0, 0.2)
        }

        .search-bar i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            pointer-events: none
        }

        .nav-icons {
            display: flex;
            align-items: center;
            gap: 20px
        }

        .nav-icons .icon {
            position: relative;
            font-size: 22px;
            color: var(--light-text);
            cursor: pointer;
            transition: color 0.2s
        }

        .dark-mode .nav-icons .icon {
            color: var(--dark-text)
        }

        .nav-icons .icon:hover {
            color: var(--primary)
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
            font-weight: 600
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
            margin-bottom: 20px
        }

        .dark-mode .card {
            background: var(--dark-card);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2)
        }

        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--light-text);
            margin-bottom: 15px;
            position: relative;
            padding-left: 12px
        }

        .card-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 6px;
            bottom: 6px;
            width: 4px;
            background: var(--primary);
            border-radius: 4px
        }

        .dark-mode .card-title {
            color: var(--dark-text)
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            text-align: center
        }

        .badge-success {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745
        }

        .badge-warning {
            background: rgba(255, 193, 7, 0.15);
            color: #ffc107
        }

        .badge-danger {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545
        }

        .badge-info {
            background: rgba(23, 162, 184, 0.15);
            color: #17a2b8
        }

        .badge-primary {
            background: rgba(255, 165, 0, 0.15);
            color: var(--primary)
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
            cursor: pointer
        }

        .btn-primary {
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            color: #fff;
            box-shadow: 0 4px 14px rgba(255, 165, 0, 0.3)
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 165, 0, 0.5)
        }

        .btn-outline-primary {
            border: 1px solid var(--primary);
            color: var(--primary);
            background: transparent
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            color: #fff
        }

        .btn-outline-secondary {
            border: 1px solid #6c757d;
            color: #6c757d;
            background: transparent
        }

        .btn-outline-secondary:hover {
            background: #6c757d;
            color: #fff
        }

        .btn-outline-danger {
            border: 1px solid #dc3545;
            color: #dc3545;
            background: transparent
        }

        .btn-outline-danger:hover {
            background: #dc3545;
            color: #fff
        }

        .btn-sm {
            padding: 6px 16px;
            font-size: 13px
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px
        }

        .info-item {
            background: rgba(0,0,0,0.02);
            padding: 12px 15px;
            border-radius: 12px;
            border-left: 3px solid var(--primary)
        }

        .dark-mode .info-item {
            background: rgba(255,255,255,0.02)
        }

        .info-label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px
        }

        .dark-mode .info-label {
            color: #adb5bd
        }

        .info-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--light-text)
        }

        .dark-mode .info-value {
            color: var(--dark-text)
        }

        /* ================= PRINT STYLES ================= */
        @media print {
            .sidebar, .top-navbar, .footer, .btn, .btn-group, .dropdown, .nav-icons, .avatar-icon, .theme-toggle, .search-bar, .bottom-section, [onclick] {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            .card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                page-break-inside: avoid;
            }
            body {
                background: white;
                color: black;
            }
            .info-label, .info-value {
                color: black !important;
            }
        }

        .footer {
            background: var(--light-card);
            border-top: 1px solid var(--border-light);
            padding: 30px 0;
            margin-top: 60px;
            color: #6c757d
        }

        .dark-mode .footer {
            background: var(--dark-card);
            border-top-color: var(--border-dark);
            color: #adb5bd
        }

        @media (max-width:992px) {
            .sidebar {
                left: -100%
            }

            .sidebar.active {
                left: 0
            }

            .main-content {
                margin-left: 0 !important
            }

            .search-bar {
                width: 250px
            }
        }

        @media (max-width:768px) {
            .top-navbar {
                flex-direction: column;
                align-items: stretch
            }

            .search-bar {
                width: 100%
            }

            .nav-icons {
                justify-content: flex-end
            }
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
                <a class="nav-link" data-bs-toggle="collapse" href="#theatresSubmenu" role="button"
                    aria-expanded="false" aria-controls="theatresSubmenu" title="Theatres">
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
                <a class="nav-link" data-bs-toggle="collapse" href="#settingsSubmenu" role="button"
                    aria-expanded="false" aria-controls="settingsSubmenu" title="Settings">
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
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div class="search-bar">
                <input type="text" placeholder="Search...">
                <i class="bi bi-search"></i>
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
                        <li id="notificationList">Loading...</li>
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
            <div>
                <h2>Booking #BK-<?= str_pad($booking['booking_id'], 4, '0', STR_PAD_LEFT) ?></h2>
                <p class="text-muted mb-0"><?= date('F j, Y \a\t g:i A', strtotime($booking['booking_date'])) ?></p>
            </div>
            <div>
                <a href="bookings.php" class="btn btn-outline-secondary me-2"><i class="bi bi-arrow-left"></i> Back</a>
                <button class="btn btn-outline-primary me-2" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
                <div class="btn-group">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        Update Status
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="updateStatus('confirmed')">Confirm</a></li>
                        <li><a class="dropdown-item" href="#" onclick="updateStatus('pending')">Mark Pending</a></li>
                        <li><a class="dropdown-item" href="#" onclick="updateStatus('cancelled')">Cancel</a></li>
                    </ul>
                </div>
                <button class="btn btn-outline-danger ms-2" onclick="deleteBooking(<?= $booking['booking_id'] ?>)"><i class="bi bi-trash"></i> Delete</button>
            </div>
        </div>

        <!-- Booking Details Card -->
        <div class="row">
            <div class="col-md-8">
                <!-- Movie & Showtime Card -->
                <div class="card">
                    <h3 class="card-title">Movie & Showtime</h3>
                    <div class="row">
                        <div class="col-md-4">
                            <img src="<?= htmlspecialchars($booking['image_url'] ?? 'https://via.placeholder.com/300x450?text=No+Poster') ?>" 
                                 alt="<?= htmlspecialchars($booking['movie_title']) ?>" 
                                 class="img-fluid rounded" style="max-height: 200px; width: auto;">
                        </div>
                        <div class="col-md-8">
                            <h4><?= htmlspecialchars($booking['movie_title']) ?></h4>
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Year</div>
                                    <div class="info-value"><?= htmlspecialchars($booking['year'] ?? 'N/A') ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Language</div>
                                    <div class="info-value"><?= htmlspecialchars($booking['language'] ?? 'N/A') ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Genre</div>
                                    <div class="info-value"><?= htmlspecialchars($booking['genre'] ?? 'N/A') ?></div>
                                </div>
                            </div>
                            <hr>
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Date</div>
                                    <div class="info-value"><?= htmlspecialchars($booking['show_date']) ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Time</div>
                                    <div class="info-value"><?= date('g:i A', strtotime($booking['show_time'])) ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Theatre</div>
                                    <div class="info-value"><?= htmlspecialchars($booking['theatre']) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seat & Pricing Card -->
                <div class="card mt-3">
                    <h3 class="card-title">Seats & Pricing</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Seats</div>
                            <div class="info-value"><?= htmlspecialchars($booking['seats']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Adults</div>
                            <div class="info-value"><?= $booking['adults'] ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Children</div>
                            <div class="info-value"><?= $booking['children'] ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Total Tickets</div>
                            <div class="info-value"><?= $booking['adults'] + $booking['children'] ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Total Price</div>
                            <div class="info-value">$<?= number_format($booking['total_price'], 2) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Discount Applied</div>
                            <div class="info-value"><?= $booking['discount_applied'] ? 'Yes' : 'No' ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value"><?= statusBadge($booking['status']) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Info Card -->
            <div class="col-md-4">
                <div class="card">
                    <h3 class="card-title">Customer Information</h3>
                    <div class="info-item">
                        <div class="info-label">Name</div>
                        <div class="info-value"><?= htmlspecialchars($booking['customer_name']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?= htmlspecialchars($booking['customer_email']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Loyalty Points</div>
                        <div class="info-value"><?= $booking['customer_points'] ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">User ID</div>
                        <div class="info-value">#<?= $booking['user_id'] ?></div>
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="card mt-3">
                    <h3 class="card-title">Quick Actions</h3>
                    <div class="d-grid gap-2">
                        <a href="<?= htmlspecialchars($messageLink) ?>" class="btn btn-outline-primary">
                            <i class="bi bi-envelope"></i> Message Customer
                        </a>
                        <a href="user_dashboard.php?user_id=<?= $booking['user_id'] ?>" class="btn btn-outline-primary">
                            <i class="bi bi-person"></i> View User
                        </a>
                        <a href="edit_booking.php?id=<?= $booking['booking_id'] ?>" class="btn btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit Booking
                        </a>
                    </div>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function () {
                document.getElementById('sidebar').classList.toggle('collapsed');
            });
        }

        // Dark mode toggle
        document.getElementById('themeToggle').addEventListener('click', function () {
            document.body.classList.toggle('dark-mode');
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-moon');
                icon.classList.toggle('bi-sun');
            }
        });

        // Notifications
        function updateNotifications() {
            fetch('get_notifications.php')
                .then(res => res.json())
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
                                item.innerHTML = `<a class="dropdown-item" href="${notif.link}">${notif.message}<br><small class="text-muted">${new Date(notif.created_at).toLocaleString()}</small></a>`;
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
        updateNotifications();
        setInterval(updateNotifications, 30000);

        // Update booking status
        function updateStatus(newStatus) {
            if (!confirm(`Are you sure you want to mark this booking as ${newStatus}?`)) return;
            fetch('update_booking_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=<?= $booking['booking_id'] ?>&status=' + newStatus
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(err => alert('Request failed: ' + err));
        }

        // Delete booking
        function deleteBooking(id) {
            if (confirm('Are you sure you want to delete this booking? This action cannot be undone.')) {
                fetch('delete_booking.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + id
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'bookings.php';
                    } else {
                        alert('Delete failed: ' + data.error);
                    }
                })
                .catch(err => alert('Error: ' + err));
            }
        }
    </script>
</body>
</html>