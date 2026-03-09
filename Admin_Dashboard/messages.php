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
    <title>Messages · <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
    <?php if (!empty($settings['theme_color'])): ?>
        <style>
            :root {
                --primary:
                    <?= htmlspecialchars($settings['theme_color']) ?>
                ;
            }

            .btn-primary {
                background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            }
        </style>
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* ========== GLOBAL & VARIABLES ========== */
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
            width: var(--sidebar-collapsed-width);
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
            box-shadow: 0 0 0 4px rgba(255, 165, 0, 0.2);
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

        /* ===== CARDS ===== */
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

        /* ===== LIST GROUP (message list) ===== */
        .list-group {
            border-radius: 20px;
            overflow: hidden;
        }

        .list-group-item {
            background: var(--light-card);
            border: none;
            border-bottom: 1px solid var(--border-light);
            padding: 15px 20px;
            color: var(--light-text);
            cursor: pointer;
        }

        .dark-mode .list-group-item {
            background: var(--dark-card);
            border-bottom-color: var(--border-dark);
            color: var(--dark-text);
        }

        .list-group-item:hover {
            background: rgba(255, 165, 0, 0.05);
        }

        .list-group-item.active {
            background: var(--primary);
            color: #fff;
        }

        .list-group-item-unread {
            font-weight: 700;
            border-left: 4px solid var(--primary-gold);
        }

        /* ===== REPLY BUBBLES ===== */
        .reply-bubble {
            background: #e9ecef;
            border-radius: 18px 18px 18px 0;
            padding: 10px 15px;
            margin: 5px 0 5px 30px;
            max-width: 80%;
        }

        .dark-mode .reply-bubble {
            background: #2d3a4a;
            color: #f2f2f2;
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
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 165, 0, 0.5);
        }

        .btn-outline-danger {
            border: 1px solid #dc3545;
            color: #dc3545;
            background: transparent;
            border-radius: 40px;
            padding: 6px 16px;
        }

        .btn-outline-danger:hover {
            background: #dc3545;
            color: #fff;
        }

        .btn-sm {
            padding: 6px 16px;
            font-size: 13px;
        }

        /* ===== FORMS ===== */
        .form-label {
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .dark-mode .form-label {
            color: #adb5bd;
        }

        .form-control {
            background: var(--light-card);
            border: 1px solid var(--border-light);
            color: var(--light-text);
            border-radius: 10px;
            padding: 10px 15px;
            transition: var(--transition);
        }

        .dark-mode .form-control {
            background: var(--dark-card);
            border-color: var(--border-dark);
            color: var(--dark-text);
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 165, 0, 0.2);
            outline: none;
        }

        /* ===== BADGES ===== */
        .badge.bg-primary {
            background: var(--primary) !important;
        }

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
    <!-- Sidebar Overlay (mobile) – if your sidebar.php includes it, you can remove this -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>


    <!-- Sidebar (with title attributes for tooltips when collapsed) -->
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
        <div class="container-fluid">
            <div class="top-navbar">
                <div class="d-flex align-items-center flex-grow-1">
                    <!-- Unified hamburger button -->
                    <i class="bi bi-list menu-toggle me-3" id="menuToggle"></i>
                    <div class="search-bar">
                        <input type="text" id="searchMessages" placeholder="Search messages...">
                        <i class="bi bi-search"></i>
                    </div>
                </div>
                <div class="nav-icons">
                    <!-- Notification Bell Dropdown -->
                    <div class="dropdown d-inline-block">
                        <div class="icon position-relative" id="notificationDropdown" data-bs-toggle="dropdown"
                            aria-expanded="false" role="button">
                            <i class="bi bi-bell"></i>
                            <span class="badge" id="notificationBadge" style="display: none;">0</span>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown"
                            style="width: 300px;">
                            <li>
                                <h6 class="dropdown-header">Notifications</h6>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li id="notificationList" style="max-height: 300px; overflow-y: auto;"></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-center small" href="#" id="markAllRead">Mark all as
                                    read</a></li>
                        </ul>
                    </div>

                    <div class="theme-toggle" id="themeToggle"><i class="bi bi-moon"></i></div>
                    <i class="bi bi-person-circle avatar-icon"></i>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>User Feedback <span class="badge bg-primary" id="unreadBadge" style="display: none;">0</span></h2>
            </div>

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
                                            <small
                                                class="text-muted"><?= date('M j, H:i', strtotime($msg['submitted_at'])) ?></small>
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
                        <div id="messageContent" style="min-height: 150px;">
                            <!-- Original message appears here -->
                        </div>
                        <hr>
                        <!-- Replies will be loaded here -->
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

    <!-- Footer (optional – you can add it if you want) -->
    <footer class="footer text-center">
        <div class="container">
            <p class="small">
                <?= htmlspecialchars($settings['footer_text'] ?? '© ' . date('Y') . ' Popcorn Hub. All rights reserved.') ?>
            </p>
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

        // ========== SIDEBAR TOGGLE (Unified) ==========
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const menuToggle = document.getElementById('menuToggle');
        const sidebarToggleBtn = document.getElementById('sidebarToggle'); // chevron inside sidebar

        function toggleSidebar() {
            if (window.innerWidth >= 992) {
                // Desktop: toggle collapsed class (mini sidebar)
                sidebar.classList.toggle('collapsed');
                document.body.classList.toggle('sidebar-collapsed');
            } else {
                // Mobile: slide sidebar in/out
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            }
        }

        if (menuToggle) {
            menuToggle.addEventListener('click', toggleSidebar);
        }

        if (sidebarToggleBtn) {
            sidebarToggleBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (window.innerWidth >= 992) {
                    sidebar.classList.toggle('collapsed');
                    document.body.classList.toggle('sidebar-collapsed');
                }
            });
        }

        if (overlay) {
            overlay.addEventListener('click', function () {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        }

        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function () {
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

        // ========== MESSAGES FUNCTIONALITY (unchanged) ==========
        let currentMessageId = null;
        let adminId = <?= json_encode($admin_id) ?>; // for reply tracking

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
                                    <small class="text-muted">Admin · ${new Date(reply.created_at).toLocaleString()}</small>
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

        document.querySelectorAll('#messageList .list-group-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                document.querySelectorAll('#messageList .list-group-item').forEach(li => li.classList.remove('active'));
                item.classList.add('active');

                currentMessageId = item.dataset.id;
                document.getElementById('replyFeedbackId').value = currentMessageId;

                const name = item.dataset.name;
                const email = item.dataset.email;
                const message = item.dataset.message;
                const date = item.dataset.date;
                const status = item.dataset.status;

                document.getElementById('contactName').innerText = name;
                document.getElementById('contactEmail').innerText = email;
                document.getElementById('messageContent').innerHTML = `
                    <p><strong>Sent:</strong> ${new Date(date).toLocaleString()}</p>
                    <p>${message.replace(/\n/g, '<br>')}</p>
                `;

                loadReplies(currentMessageId);

                if (status === 'unread') {
                    markMessageAsRead(currentMessageId, item);
                }
            });
        });

        const firstItem = document.querySelector('#messageList .list-group-item');
        if (firstItem) firstItem.click();

        function deleteMessage() {
            if (!currentMessageId || !confirm('Delete this message and all replies?')) return;
            fetch('delete_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + currentMessageId
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert('Delete failed.');
                });
        }

        document.getElementById('replyForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const feedbackId = document.getElementById('replyFeedbackId').value;
            if (!feedbackId) {
                alert('Please select a message first.');
                return;
            }

            const subject = document.getElementById('replySubject').value;
            const message = document.getElementById('replyMessage').value.trim();
            if (!message) {
                alert('Reply message cannot be empty.');
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
                    console.log('Raw server response:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Server returned non-JSON. Response: ' + text.substring(0, 200));
                    }
                })
                .then(data => {
                    if (data.success) {
                        statusDiv.innerHTML = '<span class="text-success">✓ Reply sent successfully!</span>';
                        document.getElementById('replyMessage').value = '';
                        loadReplies(feedbackId);
                    } else {
                        statusDiv.innerHTML = '<span class="text-danger">Error: ' + data.error + '</span>';
                    }
                })
                .catch(err => {
                    console.error('Fetch error:', err);
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

        updateUnreadCount();
        setInterval(() => {
            updateUnreadCount();
        }, 30000);

        // Search filter
        document.getElementById('searchMessages').addEventListener('input', function () {
            const filter = this.value.toLowerCase();
            const items = document.querySelectorAll('#messageList .list-group-item');
            items.forEach(item => {
                const text = item.innerText.toLowerCase();
                item.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    </script>
</body>

</html>