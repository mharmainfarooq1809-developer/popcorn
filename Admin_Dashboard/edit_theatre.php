<?php
session_start();
require_once '../db_connect.php';
require_once '../settings_init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$error = $success = '';
$theatre_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$theatre_id) {
    header("Location: theatres.php");
    exit;
}

// Fetch theatre data
$stmt = $conn->prepare("SELECT * FROM theatres WHERE id = ?");
$stmt->bind_param("i", $theatre_id);
$stmt->execute();
$theatre = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$theatre) {
    $_SESSION['error'] = "Theatre not found.";
    header("Location: theatres.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $city = trim($_POST['city']);
    $location = trim($_POST['location']);
    $rating = floatval($_POST['rating']);
    $price = floatval($_POST['price']);
    $facilities = $_POST['facilities'] ?? [];
    $image_url = trim($_POST['image_url']);

    if (empty($name) || empty($city) || empty($location)) {
        $error = "Name, City, and Location are required.";
    } else {
        $facilities_json = json_encode($facilities);
        $update_stmt = $conn->prepare("UPDATE theatres SET name = ?, city = ?, location = ?, rating = ?, price = ?, facilities = ?, image_url = ? WHERE id = ?");
        $update_stmt->bind_param("sssddssi", $name, $city, $location, $rating, $price, $facilities_json, $image_url, $theatre_id);
        if ($update_stmt->execute()) {
            $success = "Theatre updated successfully.";
            $update_stmt->close();

            // Refresh theatre data with a new statement
            $refresh_stmt = $conn->prepare("SELECT * FROM theatres WHERE id = ?");
            $refresh_stmt->bind_param("i", $theatre_id);
            $refresh_stmt->execute();
            $theatre = $refresh_stmt->get_result()->fetch_assoc();
            $refresh_stmt->close();
        } else {
            $error = "Error: " . $conn->error;
            $update_stmt->close();
        }
    }
}

$facility_options = ['IMAX', '3D', 'Dolby Atmos', 'VIP Lounge'];
$current_facilities = json_decode($theatre['facilities'] ?? '[]', true);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Theatre · <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
    <?php if (!empty($settings['theme_color'])): ?>
        <style>
            :root {
                --primary: <?= htmlspecialchars($settings['theme_color']) ?>;
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
            --dark-card: #1a2634;
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

        /* ===== CARDS & FORMS ===== */
        .card {
            border: none;
            border-radius: 20px;
            padding: 25px;
            background: #FFFFFF;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            width: 100%;
            overflow: hidden;
        }

        body.dark-mode .card {
            background: #1a2634;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
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
            background: #1a2634;
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
            min-height: 60px;
            resize: vertical;
        }

        /* Checkbox styling */
        .form-check-input {
            background-color: #FFFFFF;
            border-color: #E9ECEF;
        }

        body.dark-mode .form-check-input {
            background-color: #1a2634;
            border-color: #3A414D;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .form-check-label {
            color: #212529;
        }

        body.dark-mode .form-check-label {
            color: #FFFFFF;
        }

        /* Image preview styles */
        .image-preview-container {
            margin-top: 15px;
            text-align: center;
            display: none;
        }

        .image-preview-container.show {
            display: block;
        }

        .image-preview {
            max-width: 100%;
            max-height: 200px;
            border-radius: 10px;
            border: 2px solid #E9ECEF;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            object-fit: contain;
            background: #f8f9fa;
        }

        body.dark-mode .image-preview {
            border-color: #3A414D;
            background: #2a3644;
        }

        .image-info {
            font-size: 12px;
            margin-top: 8px;
            padding: 5px 15px;
            background: #E9ECEF;
            border-radius: 20px;
            display: inline-block;
            color: #212529;
        }

        body.dark-mode .image-info {
            background: #2a3644;
            color: #FFFFFF;
        }

        /* ===== BUTTONS ===== */
        .btn-primary {
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            color: #FFFFFF;
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

        .btn-secondary {
            background: #E9ECEF;
            color: #212529;
            border: none;
            border-radius: 40px;
            padding: 10px 24px;
            margin-left: 10px;
            transition: all 0.2s;
        }

        body.dark-mode .btn-secondary {
            background: #2a3644;
            color: #FFFFFF;
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: #FFFFFF;
        }

        /* ===== ALERTS ===== */
        .alert-success {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
        }

        body.dark-mode .alert-success {
            background: rgba(40, 167, 69, 0.25);
            color: #7acf7a;
        }

        body.dark-mode .alert-danger {
            background: rgba(220, 53, 69, 0.25);
            color: #ff8a92;
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
            background: #1a2634;
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
            background: rgba(255, 165, 0, 0.1);
        }

        /* ===== HEADINGS WITH TOGGLE EFFECT ===== */
        .card-header-with-toggle {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            cursor: pointer;
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
        }

        body.dark-mode .card-header-with-toggle h2 {
            color: #FFFFFF;
        }

        .card-header-with-toggle i {
            font-size: 24px;
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
            color: #AAAAAA;
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

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }

            .card {
                padding: 20px;
            }

            .btn-secondary {
                margin-left: 0;
                margin-top: 10px;
            }

            .col-auto {
                width: 50%;
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
            <button class="toggle-btn" id="sidebarToggle"><i class="bi bi-chevron-left"></i></button>
        </div>

        <div class="nav">
            <a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
            <a href="movies.php" class="nav-link"><i class="bi bi-film"></i><span>Movies</span></a>

            <!-- Theatres submenu - expanded -->
            <div class="nav-item">
                <a class="nav-link active" data-bs-toggle="collapse" href="#theatresSubmenu" role="button" aria-expanded="true">
                    <i class="bi bi-building"></i><span>Theatres</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <div class="collapse show" id="theatresSubmenu">
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
        <div class="container-fluid">
            <div class="top-navbar">
                <div class="d-flex align-items-center">
                    <i class="bi bi-list menu-toggle me-3" id="menuToggle"></i>
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
                            <li id="notificationList"></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center small" href="#" id="markAllRead">Mark all as read</a></li>
                        </ul>
                    </div>
                    <div class="theme-toggle" id="themeToggle"><i class="bi bi-moon"></i></div>
                    <i class="bi bi-person-circle avatar-icon"></i>
                </div>
            </div>

            <!-- Page Header with Toggle -->
            <div class="card-header-with-toggle" data-target="editTheatreSection" data-default-expanded="true">
                <h2>Edit Theatre: <?= htmlspecialchars($theatre['name']) ?></h2>
                <i class="bi bi-chevron-right"></i>
            </div>

            <!-- Edit Form Section -->
            <div class="toggle-content show" id="editTheatreSection">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="post" class="card">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Theatre Name *</label>
                            <input type="text" name="name" class="form-control"
                                value="<?= htmlspecialchars($theatre['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">City *</label>
                            <input type="text" name="city" class="form-control"
                                value="<?= htmlspecialchars($theatre['city']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address / Location *</label>
                            <input type="text" name="location" class="form-control"
                                value="<?= htmlspecialchars($theatre['location']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Rating (0-5)</label>
                            <input type="number" step="0.1" min="0" max="5" name="rating" class="form-control"
                                value="<?= $theatre['rating'] ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ticket Price ($)</label>
                            <input type="number" step="0.01" min="0" name="price" class="form-control"
                                value="<?= $theatre['price'] ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Image URL</label>
                            <textarea name="image_url" id="image_url" class="form-control" rows="2"
                                placeholder="Enter image URL"><?= htmlspecialchars($theatre['image_url'] ?? '') ?></textarea>
                            <small class="text-muted">Supports: http://, https://, data:image</small>
                        </div>

                        <!-- Image Preview -->
                        <div class="col-12">
                            <div class="image-preview-container" id="imagePreviewContainer">
                                <img id="imagePreview" class="image-preview"
                                    src="<?= htmlspecialchars($theatre['image_url'] ?? '') ?>" alt="Preview">
                                <div class="image-info" id="imageInfo"></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Facilities</label>
                            <div class="row g-2">
                                <?php foreach ($facility_options as $fac): ?>
                                    <div class="col-auto">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="facilities[]"
                                                value="<?= $fac ?>" id="fac_<?= $fac ?>"
                                                <?= in_array($fac, $current_facilities) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="fac_<?= $fac ?>"><?= $fac ?></label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary">Update Theatre</button>
                            <a href="theatres.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer text-center">
        <div class="container">
            <p class="small"><?= htmlspecialchars($settings['footer_text'] ?? '© ' . date('Y') . ' Popcorn Hub. All rights reserved.') ?></p>
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

        // Image preview
        function isDataUrl(url) {
            return url && url.startsWith('data:');
        }

        function previewImage() {
            const urlInput = document.getElementById('image_url');
            const previewContainer = document.getElementById('imagePreviewContainer');
            const previewImg = document.getElementById('imagePreview');
            const imageInfo = document.getElementById('imageInfo');

            const url = urlInput.value.trim();

            if (url) {
                previewContainer.classList.add('show');
                previewImg.src = url;

                if (isDataUrl(url)) {
                    const length = url.length;
                    imageInfo.innerHTML = `📸 Data URL · Length: ${length} characters`;
                } else if (url.startsWith('http')) {
                    imageInfo.innerHTML = `🌐 External URL`;
                } else {
                    imageInfo.innerHTML = `📁 Local path`;
                }

                previewImg.onerror = function() {
                    imageInfo.innerHTML = '⚠️ Image failed to load';
                    imageInfo.style.color = '#dc3545';
                };

                previewImg.onload = function() {
                    imageInfo.style.color = '';
                    if (!imageInfo.innerHTML.includes('⚠️')) {
                        imageInfo.innerHTML += ` · ${this.naturalWidth} x ${this.naturalHeight}px`;
                    }
                };
            } else {
                previewContainer.classList.remove('show');
                previewImg.src = '';
                imageInfo.innerHTML = '';
            }
        }

        let previewTimeout;
        const imageUrlInput = document.getElementById('image_url');
        if (imageUrlInput) {
            imageUrlInput.addEventListener('input', function() {
                clearTimeout(previewTimeout);
                previewTimeout = setTimeout(previewImage, 500);
            });
        }

        window.addEventListener('load', previewImage);
    </script>
</body>
</html>