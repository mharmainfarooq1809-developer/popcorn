<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$admin_name = $_SESSION['user_name'] ?? 'Admin';

// Fetch all theatres for the dropdown (used in showtimes modal)
$theatres_result = $conn->query("SELECT id, name, city FROM theatres ORDER BY name");
$theatres = [];
while ($row = $theatres_result->fetch_assoc()) {
    $theatres[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movies · Popcorn Hub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* ========== GLOBAL & VARIABLES ========== */
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

        .btn-outline-warning {
            border: 1px solid #ffc107;
            color: #ffc107;
            background: transparent;
        }

        .btn-outline-warning:hover {
            background: #ffc107;
            color: #000;
        }

        .btn-outline-info {
            border: 1px solid #17a2b8;
            color: #17a2b8;
            background: transparent;
        }

        .btn-outline-info:hover {
            background: #17a2b8;
            color: #fff;
        }

        .btn-sm {
            padding: 6px 16px;
            font-size: 13px;
        }

        /* Movie card specific */
        .movie-card {
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            cursor: pointer;
            overflow: hidden;
        }

        .movie-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(255, 165, 0, 0.2);
            border-color: var(--primary) !important;
        }

        .movie-card .poster-container {
            height: 220px;
            overflow: hidden;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .dark-mode .movie-card .poster-container {
            background: #1a1a1a;
        }

        .movie-card .poster-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .movie-card:hover .poster-container img {
            transform: scale(1.05);
        }

        .movie-card .poster-container .poster-fallback {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            color: #999;
            font-size: 14px;
            text-align: center;
            padding: 20px;
        }

        .movie-card .poster-container .poster-fallback i {
            font-size: 48px;
            margin-bottom: 10px;
            color: #ccc;
        }

        .premium-badge {
            background: linear-gradient(145deg, #FFD700, #B8860B);
            color: #000;
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 30px;
            font-weight: 600;
            display: inline-block;
            margin-left: 5px;
        }

        /* Featured star */
        .featured-star {
            color: #ffc107;
            font-size: 1.2rem;
            margin-left: 5px;
        }

        /* Data URL preview */
        .data-url-preview {
            max-width: 100%;
            max-height: 150px;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: none;
            object-fit: contain;
            background: #f8f9fa;
        }

        .data-url-preview.show {
            display: block;
        }

        .data-url-info {
            font-size: 12px;
            margin-top: 5px;
            padding: 5px;
            background: #e9ecef;
            border-radius: 4px;
            word-break: break-all;
        }

        /* Showtime table */
        .showtime-table th {
            background: rgba(0,0,0,0.02);
            border-bottom: 2px solid var(--border-light);
        }
        .dark-mode .showtime-table th {
            background: rgba(255,255,255,0.02);
            border-bottom-color: var(--border-dark);
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-active {
            background: rgba(40,167,69,0.15);
            color: #28a745;
        }
        .status-ended {
            background: rgba(108,117,125,0.15);
            color: #6c757d;
        }
        .status-cancelled {
            background: rgba(220,53,69,0.15);
            color: #dc3545;
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
        <div class="top-navbar">
            <div class="d-flex align-items-center flex-grow-1">
                <i class="bi bi-list menu-toggle-mobile me-3" id="mobileMenuToggle"></i>
                <div class="search-bar">
                    <input type="text" id="searchMovies" placeholder="Search movies...">
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
                <img src="https://picsum.photos/40" class="avatar" alt="User">
            </div>
        </div>

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Movies</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#movieModal"
                onclick="clearModal()"><i class="bi bi-plus-circle"></i> Add Movie</button>
        </div>

        <!-- Movies Grid -->
        <div class="row g-4" id="moviesGrid">
            <!-- Movies will be loaded here via AJAX -->
        </div>
    </div>

    <!-- Movie Modal (Add/Edit) -->
    <div class="modal fade" id="movieModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Movie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="movieForm">
                        <input type="hidden" name="id" id="movieId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" class="form-control" name="title" id="title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category" id="category" required>
                                    <option value="">Select Category</option>
                                    <option value="Action">Action</option>
                                    <option value="Animation">Animation</option>
                                    <option value="Comedy">Comedy</option>
                                    <option value="Drama">Drama</option>
                                    <option value="Horror">Horror</option>
                                    <option value="Adventure">Adventure</option>
                                    <option value="Family">Family</option>
                                    <option value="Premium">Premium</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Genre</label>
                                <input type="text" class="form-control" name="genre" id="genre"
                                    placeholder="e.g., Action, Adventure" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Language</label>
                                <input type="text" class="form-control" name="language" id="language"
                                    placeholder="e.g., English" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image URL or Data URL</label>
                            <textarea class="form-control" name="image_url" id="image_url" rows="5"
                                placeholder="Enter image URL or data:image URL (can be very long)"><?php echo isset($movie['image_url']) ? htmlspecialchars($movie['image_url']) : ''; ?></textarea>
                            <small class="text-muted">
                                Supports: HTTPS, HTTP, data:image (base64 - can be very long), relative paths
                            </small>
                            <div class="data-url-info" id="dataUrlInfo"></div>
                            <img id="imagePreview" class="data-url-preview" src="" alt="Preview">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Trailer URL (YouTube embed link)</label>
                            <input type="url" class="form-control" name="trailer_url" id="trailer_url"
                                placeholder="https://www.youtube.com/embed/...">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="is_premium" id="is_premium" value="1">
                            <label class="form-check-label" for="is_premium">Premium Movie</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveMovie()">Save Movie</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Showtime Modal -->
    <div class="modal fade" id="showtimeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Manage Showtimes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="currentMovieId">
                    <div class="mb-4">
                        <h6>Add New Showtime</h6>
                        <div class="row g-2">
                            <div class="col-md-3">
                                <select class="form-select" id="newTheatre" required>
                                    <option value="">Select Theatre</option>
                                    <?php foreach ($theatres as $t): ?>
                                        <option value="<?php echo htmlspecialchars($t['name']) ?>">
                                            <?php echo htmlspecialchars($t['name']) ?> (<?php echo htmlspecialchars($t['city']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control" id="newDate" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" class="form-control" id="newTime" required>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-primary w-100" onclick="addShowtime()">Add</button>
                            </div>
                        </div>
                    </div>
                    <div id="showtimesList">
                        <table class="table table-hover showtime-table">
                            <thead>
                                <tr>
                                    <th>Theatre</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="showtimesBody">
                                <tr>
                                    <td colspan="5" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer text-center">
        <div class="container">
            <p class="small">© <?php echo date('Y') ?> Popcorn Hub. All rights reserved.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
   <script>
    // ================= GLOBAL VARIABLES =================
    let movies = [];
    let currentShowtimeMovieId = null;
    let showtimes = [];

    // ================= SAFE EVENT LISTENER ATTACHMENT =================
    function safeAddListener(id, event, handler) {
        const el = document.getElementById(id);
        if (el) el.addEventListener(event, handler);
    }

    // ================= CHECK IF STRING IS A DATA URL =================
    function isDataUrl(url) {
        return url && url.startsWith('data:');
    }

    // ================= PREVIEW DATA URL =================
    function previewDataUrl() {
        const url = document.getElementById('image_url').value;
        const preview = document.getElementById('imagePreview');
        const info = document.getElementById('dataUrlInfo');
        
        if (url) {
            if (isDataUrl(url)) {
                preview.src = url;
                preview.classList.add('show');
                info.innerHTML = `Data URL length: ${url.length} characters<br>Type: ${url.split(';')[0] || 'Unknown'}`;
                info.style.color = '#28a745';
            } else {
                preview.src = url;
                preview.classList.add('show');
                info.innerHTML = `Regular URL: ${url.substring(0, 100)}${url.length > 100 ? '...' : ''}`;
                info.style.color = '#0066cc';
            }
        } else {
            preview.classList.remove('show');
            preview.src = '';
            info.innerHTML = '';
        }
    }

    // ================= HANDLE IMAGE ERRORS =================
    function handleImageError(img, movieTitle) {
        console.log('Image failed to load for:', movieTitle);
        
        // Don't show alerts for data URLs - they might just be slow to load
        if (img.src && isDataUrl(img.src)) {
            console.log('Data URL detected - length:', img.src.length);
            return;
        }
        
        // Replace the img with a fallback display
        const container = img.parentElement;
        if (container) {
            // Create fallback element
            const fallback = document.createElement('div');
            fallback.className = 'poster-fallback';
            fallback.innerHTML = `
                <i class="bi bi-film"></i>
                <span>${movieTitle || 'No Poster'}</span>
                <small style="font-size: 10px; color: #999; margin-top: 5px;">Image failed to load</small>
            `;
            // Hide the broken image
            img.style.display = 'none';
            // Add fallback
            container.appendChild(fallback);
        }
    }

    // ================= SIDEBAR TOGGLE =================
    safeAddListener('sidebarToggle', 'click', function () {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.classList.toggle('collapsed');
            document.body.classList.toggle('sidebar-collapsed');
        }
    });

    // ================= MOBILE MENU TOGGLE =================
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

    // ================= DARK MODE TOGGLE =================
    safeAddListener('themeToggle', 'click', function () {
        document.body.classList.toggle('dark-mode');
        const icon = this.querySelector('i');
        if (icon) {
            icon.classList.toggle('bi-moon');
            icon.classList.toggle('bi-sun');
        }
    });

    // ================= FETCH MOVIES FROM DATABASE =================
    function loadMovies() {
        fetch('get_movies_admin.php')
            .then(response => response.json())
            .then(data => {
                movies = data;
                renderMovies(data);
            })
            .catch(error => {
                console.error('Error loading movies:', error);
                document.getElementById('moviesGrid').innerHTML = '<div class="col-12 text-center py-5"><p class="text-danger">Failed to load movies.</p></div>';
            });
    }

    // ================= RENDER MOVIES IN GRID =================
    function renderMovies(moviesArray) {
        const grid = document.getElementById('moviesGrid');
        if (!grid) return;
        grid.innerHTML = '';
        
        moviesArray.forEach(movie => {
            const isPremium = Number(movie.is_premium) === 1;
            const isFeatured = Number(movie.is_featured) === 1;
            const starIcon = isFeatured ? 'bi-star-fill' : 'bi-star';
            const starClass = isFeatured ? 'btn-warning' : 'btn-outline-warning';
            
            // Use the image URL directly - no fixing needed
            const imageUrl = movie.image_url || 'https://via.placeholder.com/400x600?text=No+Poster';
            
            // Check if it's a data URL for logging
            if (isDataUrl(imageUrl)) {
                console.log(`Movie "${movie.title}" uses data URL (length: ${imageUrl.length} chars)`);
            }
            
            const col = document.createElement('div');
            col.className = 'col-md-6 col-lg-4 col-xl-3';
            col.innerHTML = `
                <div class="card movie-card p-0">
                    <div class="poster-container">
                        <img 
                            src="${imageUrl.replace(/"/g, '&quot;')}"
                            class="card-img-top"
                            alt="${movie.title.replace(/"/g, '&quot;')}"
                            onerror="handleImageError(this, '${movie.title.replace(/'/g, "\\'").replace(/"/g, '&quot;')}')"
                            loading="lazy"
                        >
                    </div>
                    <div class="card-body">
                        <h5 class="card-title mb-2">${movie.title} ${isPremium ? '<span class="premium-badge">Premium</span>' : ''}</h5>
                        <p class="mb-1"><span class="badge badge-primary">${movie.category}</span> <span class="badge badge-success">${movie.language}</span></p>
                        <p class="text-muted small">${movie.genre}</p>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <button class="btn btn-sm ${starClass}" onclick="toggleFeatured(${movie.id}, this)" title="Featured"><i class="bi ${starIcon}"></i></button>
                            <button class="btn btn-sm btn-outline-primary" onclick="editMovie(${movie.id})"><i class="bi bi-pencil"></i> Edit</button>
                            <button class="btn btn-sm btn-outline-info" onclick="manageShowtimes(${movie.id})"><i class="bi bi-calendar-week"></i> Showtimes</button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteMovie(${movie.id})"><i class="bi bi-trash"></i> Delete</button>
                        </div>
                    </div>
                </div>
            `;
            grid.appendChild(col);
        });
    }

    // ================= TOGGLE FEATURED =================
    function toggleFeatured(id, btn) {
        fetch('toggle_featured.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the button appearance
                const icon = btn.querySelector('i');
                if (data.is_featured) {
                    btn.classList.remove('btn-outline-warning');
                    btn.classList.add('btn-warning');
                    icon.classList.remove('bi-star');
                    icon.classList.add('bi-star-fill');
                } else {
                    btn.classList.remove('btn-warning');
                    btn.classList.add('btn-outline-warning');
                    icon.classList.remove('bi-star-fill');
                    icon.classList.add('bi-star');
                }
            } else {
                Swal.fire('Error', data.error || 'Could not toggle featured.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Network error.', 'error');
        });
    }

    // ================= SHOWTIME MANAGEMENT =================
    function manageShowtimes(movieId) {
        currentShowtimeMovieId = movieId;
        document.getElementById('currentMovieId').value = movieId;
        loadShowtimes(movieId);
        new bootstrap.Modal(document.getElementById('showtimeModal')).show();
    }

    function loadShowtimes(movieId) {
        fetch('get_showtimes.php?movie_id=' + movieId)
            .then(response => response.json())
            .then(data => {
                showtimes = data;
                renderShowtimes(data);
            })
            .catch(error => {
                console.error('Error loading showtimes:', error);
                document.getElementById('showtimesBody').innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load showtimes.</td></tr>';
            });
    }

    function renderShowtimes(showtimesArray) {
        const tbody = document.getElementById('showtimesBody');
        if (!tbody) return;
        if (!showtimesArray.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No showtimes added yet.</td></tr>';
            return;
        }
        tbody.innerHTML = '';
        showtimesArray.forEach(st => {
            const statusClass = st.status === 'active' ? 'status-active' : (st.status === 'ended' ? 'status-ended' : 'status-cancelled');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${st.theatre}</td>
                <td>${st.show_date}</td>
                <td>${st.show_time}</td>
                <td><span class="status-badge ${statusClass}">${st.status}</span></td>
                <td>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteShowtime(${st.id})"><i class="bi bi-trash"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    function addShowtime() {
        const theatre = document.getElementById('newTheatre').value;
        const date = document.getElementById('newDate').value;
        const time = document.getElementById('newTime').value;
        if (!theatre || !date || !time) {
            Swal.fire({ icon: 'warning', title: 'Missing fields', text: 'Please fill all fields.' });
            return;
        }
        fetch('save_showtime.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                movie_id: currentShowtimeMovieId,
                theatre: theatre,
                show_date: date,
                show_time: time
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('newTheatre').value = '';
                    document.getElementById('newDate').value = '';
                    document.getElementById('newTime').value = '';
                    loadShowtimes(currentShowtimeMovieId);
                    Swal.fire({ icon: 'success', title: 'Added', text: 'Showtime added!', timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'Failed to add showtime.' });
                }
            })
            .catch(error => {
                console.error('Add showtime error:', error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Network error.' });
            });
    }

    function deleteShowtime(showtimeId) {
        Swal.fire({
            title: 'Delete showtime?',
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('delete_showtime.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + showtimeId
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadShowtimes(currentShowtimeMovieId);
                            Swal.fire('Deleted!', '', 'success');
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'Failed to delete.' });
                        }
                    })
                    .catch(error => {
                        console.error('Delete error:', error);
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Network error.' });
                    });
            }
        });
    }

    // ================= MOVIE CRUD =================
    function clearModal() {
        document.getElementById('modalTitle').innerText = 'Add Movie';
        document.getElementById('movieId').value = '';
        document.getElementById('title').value = '';
        document.getElementById('category').value = '';
        document.getElementById('genre').value = '';
        document.getElementById('language').value = '';
        document.getElementById('image_url').value = '';
        document.getElementById('trailer_url').value = '';
        document.getElementById('is_premium').checked = false;
        document.getElementById('imagePreview').classList.remove('show');
        document.getElementById('dataUrlInfo').innerHTML = '';
    }

    function editMovie(id) {
        const movie = movies.find(m => m.id == id);
        if (!movie) return;
        document.getElementById('modalTitle').innerText = 'Edit Movie';
        document.getElementById('movieId').value = movie.id;
        document.getElementById('title').value = movie.title;
        document.getElementById('category').value = movie.category;
        document.getElementById('genre').value = movie.genre;
        document.getElementById('language').value = movie.language;
        document.getElementById('image_url').value = movie.image_url;
        document.getElementById('trailer_url').value = movie.trailer_url || '';
        document.getElementById('is_premium').checked = movie.is_premium == 1;
        
        // Preview if it's a data URL
        if (movie.image_url) {
            previewDataUrl();
        }
        
        new bootstrap.Modal(document.getElementById('movieModal')).show();
    }

    async function saveMovie() {
        const form = document.getElementById('movieForm');
        const formData = new FormData(form);

        fetch('save_movie.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('movieModal')).hide();
                    loadMovies();
                    Swal.fire({ icon: 'success', title: 'Success', text: 'Movie saved!', timer: 2000, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'Failed to save movie.' });
                }
            })
            .catch(error => {
                console.error('Save error:', error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Network error. Please try again.' });
            });
    }

    function deleteMovie(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('delete_movie.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + id
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Deleted!', 'Movie has been deleted.', 'success');
                            loadMovies();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'Failed to delete movie.' });
                        }
                    })
                    .catch(error => {
                        console.error('Delete error:', error);
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Network error. Please try again.' });
                    });
            }
        });
    }

    // ================= SEARCH =================
    const searchInput = document.getElementById('searchMovies');
    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            const term = e.target.value.toLowerCase();
            const filtered = movies.filter(m =>
                m.title.toLowerCase().includes(term) ||
                m.category.toLowerCase().includes(term) ||
                m.genre.toLowerCase().includes(term)
            );
            renderMovies(filtered);
        });
    }

    // ================= NOTIFICATIONS =================
    function updateNotifications() {
        fetch('get_notifications.php')
            .then(res => res.json())
            .then(data => {
                const badge = document.getElementById('notificationBadge');
                const list = document.getElementById('notificationList');
                if (!badge || !list) return;
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

    // Add preview on input change with debounce to handle long pastes
    let previewTimeout;
    document.getElementById('image_url').addEventListener('input', function() {
        clearTimeout(previewTimeout);
        previewTimeout = setTimeout(previewDataUrl, 500);
    });

    const markAllRead = document.getElementById('markAllRead');
    if (markAllRead) {
        markAllRead.addEventListener('click', (e) => {
            e.preventDefault();
            fetch('mark_notifications_read.php', { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                    if (data.success) updateNotifications();
                });
        });
    }

    // ================= INITIAL LOAD =================
    loadMovies();
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
</script>
</body>
</html>