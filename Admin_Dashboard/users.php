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

// Fetch all users
$users = $conn->query("SELECT id, name, email, role, profile_image, points, created_at FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users · <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
    <?php if (!empty($settings['theme_color'])): ?>
        <style>
            :root { --primary: <?= htmlspecialchars($settings['theme_color']) ?>; }
            .btn-primary { background: linear-gradient(145deg, var(--primary), var(--primary-dark)); }
        </style>
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* ================= FULL PREMIUM ADMIN CSS ================= */
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

        .dark-mode .sidebar .nav-link {
            color: var(--dark-text);
        }

        .sidebar .nav-link i,
        .sidebar .nav-link svg {
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

        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }

        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 40px;
            background: var(--light-card);
            color: var(--light-text);
            text-decoration: none;
            border: 1px solid var(--border-light);
            transition: var(--transition);
        }

        .dark-mode .page-link {
            background: var(--dark-card);
            border-color: var(--border-dark);
            color: var(--dark-text);
        }

        .page-link:hover {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        .page-item.active .page-link {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
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

        <a href="votes.php" class="nav-link" title="Voting">
            <i class="bi bi-bar-chart"></i>
            <span>Voting</span>
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

    <!-- Overlay for mobile sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="main-content">
        <div class="top-navbar">
            <div class="d-flex align-items-center flex-grow-1">
                <i class="bi bi-list menu-toggle-mobile me-3" id="mobileMenuToggle"></i>
                
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
            <h2>User Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-plus-circle"></i> Add User</button>
        </div>

        <!-- Users Table Card -->
        <div class="card">
            <div class="table-responsive">
                <table class="table" id="usersTable">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Avatar</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Points</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <?php if ($users->num_rows > 0): ?>
                            <?php $display_user_id = 1; ?>
                            <?php while ($row = $users->fetch_assoc()): ?>
                                <tr data-id="<?= $row['id'] ?>">
                                    <td><strong>#<?= $display_user_id++ ?></strong></td>
                                    <td>
                                        <?php if (!empty($row['profile_image'])): ?>
                                            <img src="<?= htmlspecialchars($row['profile_image']) ?>" alt="Avatar" class="rounded-circle" width="35" height="35" style="object-fit: cover;">
                                        <?php else: ?>
                                            <i class="bi bi-person-circle" style="font-size: 1.8rem; color: var(--primary);"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= ucfirst($row['role']) ?></td>
                                    <td><?= $row['points'] ?></td>
                                    <td><?= date('Y-m-d', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary edit-user" 
                                            data-id="<?= $row['id'] ?>"
                                            data-name="<?= htmlspecialchars($row['name']) ?>"
                                            data-email="<?= htmlspecialchars($row['email']) ?>"
                                            data-role="<?= $row['role'] ?>"
                                            data-points="<?= $row['points'] ?>"
                                            data-profile="<?= htmlspecialchars($row['profile_image']) ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-user" 
                                            data-id="<?= $row['id'] ?>"
                                            data-name="<?= htmlspecialchars($row['name']) ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center">No users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination (static) -->
            <nav class="mt-3">
                <ul class="pagination">
                    <li class="page-item disabled"><a class="page-link" href="#"><i class="bi bi-chevron-left"></i></a></li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">4</a></li>
                    <li class="page-item"><a class="page-link" href="#"><i class="bi bi-chevron-right"></i></a></li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Profile Image URL</label>
                            <input type="url" class="form-control" name="profile_image" placeholder="https://...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Points</label>
                            <input type="number" class="form-control" name="points" value="0">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveUserBtn">Save User</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="edit_role">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Profile Image URL</label>
                            <input type="url" class="form-control" name="profile_image" id="edit_profile_image" placeholder="https://...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Points</label>
                            <input type="number" class="form-control" name="points" id="edit_points">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" name="password" placeholder="Enter new password">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="updateUserBtn">Update User</button>
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

        (function () {
            const currentFile = window.location.pathname.split('/').pop();

            if (['theatres.php', 'add_theatre.php'].includes(currentFile)) {
                const submenu = document.getElementById('theatresSubmenu');
                if (submenu) submenu.classList.add('show');
            }
            if (['users.php', 'add_user.php', 'edit_user.php', 'update_user.php', 'user_dashboard.php'].includes(currentFile)) {
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

        // ========== USER CRUD ==========
        // Search functionality
        const searchUsersInput = document.getElementById('searchUsers');
        if (searchUsersInput) {
            searchUsersInput.addEventListener('input', function () {
                const filter = this.value.toLowerCase();
                const rows = document.querySelectorAll('#usersTableBody tr');
                rows.forEach(row => {
                    const name = row.querySelector('td:nth-child(2)')?.innerText.toLowerCase() || '';
                    const email = row.querySelector('td:nth-child(3)')?.innerText.toLowerCase() || '';
                    row.style.display = (name.includes(filter) || email.includes(filter)) ? '' : 'none';
                });
            });
        }

        // Edit user - populate modal
        document.querySelectorAll('.edit-user').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('edit_id').value = this.dataset.id;
                document.getElementById('edit_name').value = this.dataset.name;
                document.getElementById('edit_email').value = this.dataset.email;
                document.getElementById('edit_role').value = this.dataset.role;
                document.getElementById('edit_points').value = this.dataset.points;
                document.getElementById('edit_profile_image').value = this.dataset.profile || '';
                new bootstrap.Modal(document.getElementById('editUserModal')).show();
            });
        });

        // Update user
        document.getElementById('updateUserBtn').addEventListener('click', function () {
            const form = document.getElementById('editUserForm');
            const formData = new FormData(form);
            fetch('update_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Updated!', 'User updated successfully.', 'success');
                    location.reload();
                } else {
                    Swal.fire('Error', data.error || 'Update failed.', 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Network error.', 'error');
            });
        });

        // Delete user
        document.querySelectorAll('.delete-user').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                const name = this.dataset.name;
                Swal.fire({
                    title: 'Delete User?',
                    text: `Are you sure you want to delete ${name}? This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Yes, delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('delete_user.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'id=' + id
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Deleted!', 'User has been deleted.', 'success');
                                location.reload();
                            } else {
                                Swal.fire('Error', data.error || 'Delete failed.', 'error');
                            }
                        })
                        .catch(error => {
                            Swal.fire('Error', 'Network error.', 'error');
                        });
                    }
                });
            });
        });

        // Add user
        document.getElementById('saveUserBtn').addEventListener('click', function () {
            const form = document.getElementById('addUserForm');
            const formData = new FormData(form);
            fetch('add_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success!', 'User added successfully.', 'success');
                    location.reload();
                } else {
                    Swal.fire('Error', data.error || 'Add failed.', 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Network error.', 'error');
            });
        });
    </script>
</body>
</html>








