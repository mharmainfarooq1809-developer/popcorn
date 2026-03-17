<?php
session_start();
require_once '../db_connect.php'; // adjust path to your database connection
require_once '../settings_init.php'; // load global settings

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetch all feedback (latest first)
$messages = $conn->query("SELECT id, name, email, message, status, submitted_at FROM feedback ORDER BY submitted_at DESC");
$admin_name = $_SESSION['user_name'] ?? 'Admin';
$admin_id = $_SESSION['user_id']; // for storing replies
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
    <?php if (!empty($settings['theme_color'])): ?>
        <style>
            :root {
            }
        </style>
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* ========== UNIFIED ADMIN STYLES ========== */
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

        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: #212529;
        }

        body.dark-mode .page-title {
            color: #FFFFFF;
        }

        /* Section headers inside cards */
        .card h4 {
            color: #212529;
            font-weight: 600;
        }

        body.dark-mode .card h4 {
            color: #FFFFFF;
        }

        .card h5 {
            color: #212529;
            font-weight: 600;
        }

        body.dark-mode .card h5 {
            color: #FFFFFF;
        }

        .card h6 {
            color: #212529;
            font-weight: 600;
        }

        body.dark-mode .card h6 {
            color: #FFFFFF;
        }

        /* Form labels */
        .form-label {
            font-weight: 600;
            color: #212529;
            margin-bottom: 5px;
        }

        body.dark-mode .form-label {
            color: #FFFFFF;
        }

        /* Text muted */
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

        /* ===== CARDS & FORMS ===== */
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

        /* Card without padding for message list */
        .card.p-0 {
            padding: 0;
            overflow: hidden;
        }

        .form-control,
        .form-select {
            background: #FFFFFF;
            border: 1px solid #E9ECEF;
            color: #212529;
            border-radius: 10px;
            padding: 10px 15px;
            transition: var(--transition);
        }

        body.dark-mode .form-control,
        body.dark-mode .form-select {
            background: #0F1C2B;
            border-color: #3A414D;
            color: #FFFFFF;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 165, 0, 0.2);
            outline: none;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        /* ===== LIST GROUP (for messages) ===== */
        .list-group {
            border-radius: 20px;
            overflow: hidden;
        }

        .list-group-item {
            background: #FFFFFF;
            border: none;
            border-bottom: 1px solid #E9ECEF;
            color: #212529;
            padding: 15px 20px;
            transition: all 0.2s ease;
        }

        body.dark-mode .list-group-item {
            background: #0F1C2B;
            border-bottom-color: #3A414D;
            color: #FFFFFF;
        }

        .list-group-item:last-child {
            border-bottom: none;
        }

        .list-group-item:hover {
            background: rgba(255, 165, 0, 0.05);
        }

        body.dark-mode .list-group-item:hover {
            background: rgba(255, 165, 0, 0.1);
        }

        .list-group-item.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        body.dark-mode .list-group-item.active {
            background: var(--primary-dark);
            color: #fff;
        }

        .list-group-item-unread {
            background: rgba(255, 165, 0, 0.05);
            font-weight: 500;
            border-left: 3px solid var(--primary);
        }

        body.dark-mode .list-group-item-unread {
            background: rgba(255, 165, 0, 0.1);
        }

        .list-group-item h6 {
            margin-bottom: 5px;
            color: #212529;
        }

        body.dark-mode .list-group-item h6 {
            color: #FFFFFF;
        }

        .list-group-item.active h6,
        .list-group-item.active small,
        .list-group-item.active p {
            color: #fff !important;
        }

        /* ===== MESSAGE DETAIL SECTION ===== */
        #messageDetail {
            min-height: 500px;
        }

        #messageContent {
            background: rgba(0, 0, 0, 0.02);
            padding: 15px;
            border-radius: 10px;
            margin: 10px 0;
            color: #212529;
        }

        body.dark-mode #messageContent {
            background: rgba(255, 255, 255, 0.05);
            color: #FFFFFF;
        }

        #messageContent p {
            margin-bottom: 0;
            line-height: 1.6;
            color: #212529;
        }

        body.dark-mode #messageContent p {
            color: #FFFFFF;
        }

        /* ===== REPLIES STYLING ===== */
        #repliesContainer {
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            background: rgba(0, 0, 0, 0.02);
            border-radius: 10px;
        }

        body.dark-mode #repliesContainer {
            background: rgba(255, 255, 255, 0.05);
        }

        .reply-bubble {
            background: #FFFFFF;
            border: 1px solid #E9ECEF;
            border-radius: 15px;
            padding: 12px 15px;
            margin-bottom: 10px;
        }

        body.dark-mode .reply-bubble {
            background: #0F1C2B;
            border-color: #3A414D;
        }

        .reply-bubble small {
            display: block;
            margin-bottom: 5px;
            color: #6c757d;
        }

        body.dark-mode .reply-bubble small {
            color: #AAAAAA;
        }

        .reply-bubble p {
            margin-bottom: 0;
            color: #212529;
        }

        body.dark-mode .reply-bubble p {
            color: #FFFFFF;
        }

        /* ===== BADGES ===== */
        .badge.bg-primary {
            background: var(--primary) !important;
            color: #fff;
            padding: 5px 10px;
            border-radius: 30px;
            font-weight: 500;
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
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
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

        .btn-outline-danger {
            border: 1px solid #dc3545;
            color: #dc3545;
            background: transparent;
            border-radius: 40px;
            padding: 6px 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-outline-danger:hover {
            background: #dc3545;
            color: #fff;
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
            padding: 6px 16px;
            font-size: 13px;
        }

        /* ===== HEADINGS WITH TOGGLE EFFECT ===== */
        .card-header-with-toggle {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 5px 0;
            border-bottom: 1px solid transparent;
        }

        .card-header-with-toggle:hover {
            border-bottom-color: var(--primary);
        }

        .card-header-with-toggle h2 {
            margin-bottom: 0;
            font-size: 24px;
            font-weight: 600;
            color: #212529;
            transition: color 0.3s ease;
        }

        .card-header-with-toggle:hover h2 {
            color: #FFA500;
        }

        .card-header-with-toggle i {
            font-size: 24px;
            color: #6c757d;
            transition: all 0.3s ease;
        }

        .card-header-with-toggle:hover i {
            color: #FFA500;
            transform: translateX(5px);
        }

        .card-header-with-toggle.active i {
            transform: rotate(90deg);
            color: #FFA500;
        }

        /* Dark mode styles */
        body.dark-mode .card-header-with-toggle h2 {
            color: #FFFFFF;
        }

        body.dark-mode .card-header-with-toggle:hover h2 {
            color: #FFD966;
        }

        body.dark-mode .card-header-with-toggle i {
            color: #AAAAAA;
        }

        body.dark-mode .card-header-with-toggle:hover i {
            color: #FFD966;
        }

        body.dark-mode .card-header-with-toggle.active i {
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

        /* ===== ALERTS ===== */
        .alert-success {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
            border: none;
            border-radius: 10px;
            padding: 9px 16px;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
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
            color: #AAAAAA;
        }

        /* ===== RESPONSIVE ===== */
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
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }

            .top-navbar {
                flex-direction: column;
                align-items: stretch;
            }

            .nav-icons {
                justify-content: flex-end;
            }

            .card-header-with-toggle h2 {
                font-size: 20px;
            }

            .row.g-4 {
                flex-direction: column;
            }

            .col-lg-4, .col-lg-8 {
                width: 100%;
            }

            #messageDetail {
                margin-top: 20px;
            }

            .btn-outline-danger {
                margin-top: 5px;
            }
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
            <a href="messages.php" class="nav-link active"><i class="bi bi-chat-dots"></i><span>Messages</span></a>
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

        <!-- Page Header with Toggle Effect -->
        <div class="card-header-with-toggle" data-target="messagesSection" data-default-expanded="true">
            <h2>User Feedback <span class="badge bg-primary" id="unreadBadge" style="display: none;">0</span></h2>
            <i class="bi bi-chevron-right"></i>
        </div>

        <!-- Messages Section -->
        <div class="toggle-content show" id="messagesSection">
            <div class="row g-4">
                <!-- Left column: message list -->
                <div class="col-lg-4">
                    <div class="card p-0">
                        <div class="list-group" id="messageList">
                            <?php if ($messages->num_rows > 0): ?>
                                <?php while ($msg = $messages->fetch_assoc()):
                                    $unreadClass = ($msg['status'] ?? 'unread') == 'unread' ? 'list-group-item-unread' : '';
                                    ?>
                                    <a href="#" class="list-group-item list-group-item-action <?= $unreadClass ?>"
                                        data-id="<?= $msg['id'] ?>" data-name="<?= htmlspecialchars($msg['name']) ?>"
                                        data-email="<?= htmlspecialchars($msg['email']) ?>"
                                        data-message="<?= htmlspecialchars($msg['message']) ?>"
                                        data-date="<?= $msg['submitted_at'] ?>" data-status="<?= $msg['status'] ?? 'unread' ?>">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1"><?= htmlspecialchars($msg['name']) ?></h6>
                                            <small class="text-muted"><?= date('M j, H:i', strtotime($msg['submitted_at'])) ?></small>
                                        </div>
                                        <p class="mb-1 small"><?= htmlspecialchars(substr($msg['message'], 0, 50)) ?>...</p>
                                    </a>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="list-group-item">No messages yet.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right column: message detail -->
                <div class="col-lg-8">
                    <div class="card" id="messageDetail">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-1" id="contactName">Select a message</h5>
                                <small class="text-muted" id="contactEmail"></small>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-outline-danger" id="deleteBtn"
                                    onclick="deleteMessage()"><i class="bi bi-trash"></i> Delete</button>
                            </div>
                        </div>
                        <hr>
                        <div id="messageContent" style="min-height: 150px;"></div>
                        <hr>
                        <div id="repliesContainer" class="mb-3"></div>
                        <!-- Reply Form -->
                        <div class="mt-3">
                            <h6>Send a Reply</h6>
                            <form id="replyForm">
                                <input type="hidden" id="replyFeedbackId" name="feedback_id" value="">
                                <div class="mb-2">
                                    <input type="text" class="form-control" id="replySubject" name="reply_subject"
                                        placeholder="Subject (optional)">
                                </div>
                                <div class="mb-2">
                                    <textarea class="form-control" id="replyMessage" name="reply_message" rows="3"
                                        placeholder="Type your reply..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary" id="sendReplyBtn">Send Reply</button>
                                <div id="replyStatus" class="mt-2 small"></div>
                            </form>
                        </div>
                    </div>
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
        // ========== NOTIFICATIONS ==========
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
                                const link = notif.link ? notif.link : '#';
                                item.innerHTML = `<a class="dropdown-item" href="${link}">
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

        // ========== MESSAGES FUNCTIONALITY ==========
        let currentMessageId = null;
        let adminId = <?= json_encode($admin_id) ?>;

        function markMessageAsRead(messageId, element) {
            fetch('mark_message_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + messageId
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        updateUnreadCount();
                        if (element) {
                            element.classList.remove('list-group-item-unread');
                            element.dataset.status = 'read';
                        }
                    }
                })
                .catch(err => console.error('Error marking as read:', err));
        }

        function loadReplies(feedbackId) {
            fetch('get_replies.php?feedback_id=' + feedbackId)
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('repliesContainer');
                    if (data.replies && data.replies.length) {
                        let html = '<h6>Conversation</h6>';
                        data.replies.forEach(reply => {
                            html += `
                                <div class="reply-bubble">
                                    <small class="text-muted">Admin - ${new Date(reply.created_at).toLocaleString()}</small>
                                    <p class="mb-0">${reply.reply_text.replace(/\n/g, '<br>')}</p>
                                </div>
                            `;
                        });
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = '';
                    }
                });
        }

        // Load first message automatically
        document.addEventListener('DOMContentLoaded', function() {
            const firstItem = document.querySelector('#messageList .list-group-item');
            if (firstItem) {
                firstItem.click();
            }
        });

        // Message click handler
        document.querySelectorAll('#messageList .list-group-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();

                // Remove active class from all items
                document.querySelectorAll('#messageList .list-group-item').forEach(li => {
                    li.classList.remove('active');
                });

                // Add active class to clicked item
                this.classList.add('active');

                // Get message data
                const id = this.dataset.id;
                const name = this.dataset.name;
                const email = this.dataset.email;
                const message = this.dataset.message;
                const date = this.dataset.date;
                const status = this.dataset.status;

                // Set current message ID
                currentMessageId = id;
                document.getElementById('replyFeedbackId').value = id;

                // Update contact info
                document.getElementById('contactName').textContent = name;
                document.getElementById('contactEmail').textContent = email;

                // Format and display message
                const messageDate = new Date(date).toLocaleString();
                const formattedMessage = message.replace(/\n/g, '<br>');
                document.getElementById('messageContent').innerHTML = `
                    <p><strong>Sent:</strong> ${messageDate}</p>
                    <p>${formattedMessage}</p>
                `;

                // Load replies
                loadReplies(id);

                // Mark as read if unread
                if (status === 'unread') {
                    markMessageAsRead(id, this);
                }
            });
        });

        function deleteMessage() {
            if (!currentMessageId) {
                alert('Please select a message first.');
                return;
            }

            if (!confirm('Are you sure you want to delete this message and all replies?')) {
                return;
            }

            fetch('delete_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + currentMessageId
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Delete failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(err => {
                alert('Network error: ' + err);
            });
        }

        document.getElementById('replyForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const feedbackId = document.getElementById('replyFeedbackId').value;
            if (!feedbackId) {
                alert('Please select a message first.');
                return;
            }

            const subject = document.getElementById('replySubject').value;
            const message = document.getElementById('replyMessage').value.trim();

            if (!message) {
                alert('Please enter a reply message.');
                return;
            }

            const btn = document.getElementById('sendReplyBtn');
            const statusDiv = document.getElementById('replyStatus');

            btn.disabled = true;
            statusDiv.innerHTML = 'Sending...';

            const formData = new FormData();
            formData.append('feedback_id', feedbackId);
            formData.append('reply_subject', subject);
            formData.append('reply_message', message);
            formData.append('admin_id', adminId);

            fetch('reply_feedback.php', {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Server returned: ' + text.substring(0, 100));
                }
            })
            .then(data => {
                if (data.success) {
                    statusDiv.innerHTML = '<span class="text-success">" Reply sent successfully!</span>';
                    document.getElementById('replyMessage').value = '';
                    document.getElementById('replySubject').value = '';
                    loadReplies(feedbackId);

                    // Clear success message after 3 seconds
                    setTimeout(() => {
                        statusDiv.innerHTML = '';
                    }, 3000);
                } else {
                    statusDiv.innerHTML = '<span class="text-danger">Error: ' + (data.error || 'Unknown error') + '</span>';
                }
            })
            .catch(err => {
                statusDiv.innerHTML = '<span class="text-danger">Error: ' + err.message + '</span>';
            })
            .finally(() => {
                btn.disabled = false;
            });
        });

        function updateUnreadCount() {
            fetch('get_unread_count.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('unreadBadge');
                    if (data.count > 0) {
                        badge.textContent = data.count;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                })
                .catch(err => console.error('Error fetching unread count:', err));
        }

        // Initial load of unread count
        updateUnreadCount();

        // Update unread count every 30 seconds
        setInterval(() => {
            updateUnreadCount();
        }, 30000);
    </script>
</body>
</html>
