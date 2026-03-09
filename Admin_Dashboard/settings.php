<?php
session_start();
require_once '../db_connect.php';

require_once '../settings_init.php'; // load global settings

// Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$settings = get_settings($conn);
$message = '';
$error = '';

// Handle main settings form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_POST['maintenance_mode'] = isset($_POST['maintenance_mode']) ? '1' : '0';

    // Logo removal check
    if (isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1') {
        if (!empty($settings['site_logo'])) {
            $logo_path = '../' . $settings['site_logo'];
            if (file_exists($logo_path))
                unlink($logo_path);
        }
        $_POST['site_logo'] = '';
    }

    // Logo upload
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK && (!isset($_POST['remove_logo']) || $_POST['remove_logo'] !== '1')) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0755, true);
        $file_ext = strtolower(pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        if (in_array($file_ext, $allowed)) {
            $new_filename = 'logo_' . time() . '.' . $file_ext;
            $destination = $upload_dir . $new_filename;
            if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $destination)) {
                $_POST['site_logo'] = 'uploads/' . $new_filename; // store relative path
            } else {
                $error = 'Logo upload failed.';
            }
        } else {
            $error = 'Invalid file type. Allowed: jpg, jpeg, png, gif, svg, webp';
        }
    }

    // Save all posted fields
    if (empty($error)) {
        foreach ($_POST as $key => $value) {
            // Do NOT skip site_logo – we want to save the new path
            $value = trim($value);
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->bind_param("ss", $key, $value);
            $stmt->execute();
            $stmt->close();
        }
        $message = 'Settings saved successfully.';
        $settings = get_settings($conn, true);
    }
}

// Delete custom setting
if (isset($_GET['delete_key'])) {
    $key_to_delete = $_GET['delete_key'];
    $protected = ['site_name', 'site_logo', 'site_keywords', 'footer_text', 'theme_color', 'contact_email', 'contact_phone', 'address', 'facebook_url', 'twitter_url', 'instagram_url', 'maintenance_mode']; // removed SMTP fields
    if (!in_array($key_to_delete, $protected)) {
        $stmt = $conn->prepare("DELETE FROM settings WHERE setting_key = ?");
        $stmt->bind_param("s", $key_to_delete);
        $stmt->execute();
        $stmt->close();
        header("Location: settings.php?deleted=1");
        exit;
    } else {
        $error = "Cannot delete protected setting.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings · <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
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
        /* ========== UNIFIED ADMIN CSS (same as dashboard) ========== */
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

        .sidebar .nav {
            padding: 12px 0 96px;
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

        .sidebar .nav-link i {
            font-size: 17px;
            min-width: 24px;
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

        .submenu-link {
            padding-left: 42px !important;
            font-size: 13px;
        }

        .submenu-link i {
            font-size: 14px;
            min-width: 20px;
        }

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

        /* ===== CARDS & FORMS ===== */
        .card {
            border: none;
            border-radius: 20px;
            padding: 25px;
            background: var(--light-card);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
            margin-bottom: 20px;
        }

        .dark-mode .card {
            background: var(--dark-card);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .form-label {
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .dark-mode .form-label {
            color: #adb5bd;
        }

        .form-control,
        .form-select {
            background: var(--light-card);
            border: 1px solid var(--border-light);
            color: var(--light-text);
            border-radius: 10px;
            padding: 10px 15px;
            transition: var(--transition);
        }

        .dark-mode .form-control,
        .dark-mode .form-select {
            background: var(--dark-card);
            border-color: var(--border-dark);
            color: var(--dark-text);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 165, 0, 0.2);
            outline: none;
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

        .btn-outline-primary {
            border: 1px solid var(--primary);
            color: var(--primary);
            background: transparent;
            border-radius: 40px;
            padding: 8px 20px;
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            color: #fff;
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

        /* ===== HERO SLIDE ROWS ===== */
        .hero-slide-row {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 12px;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .hero-slide-row:hover {
            background: #e9ecef;
            border-color: var(--primary);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: scale(1.01);
        }

        .hero-slide-row .bi-grip-vertical {
            font-size: 1.5rem;
            color: #adb5bd;
            cursor: move;
        }

        .hero-slide-row .bi-grip-vertical:hover {
            color: var(--primary);
        }

        /* ===== CUSTOM FIELDS ===== */
        .custom-field-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }

        .delete-btn {
            color: #dc3545;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .delete-btn:hover {
            color: #b02a37;
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
                <a class="nav-link" data-bs-toggle="collapse" href="#usersSubmenu" role="button" aria-expanded="false"
                    aria-controls="usersSubmenu" title="Users">
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
                    <i class="bi bi-list menu-toggle me-3" id="menuToggle"></i>
                    <div class="search-bar">
                        <input type="text" placeholder="Search...">
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

            <h2 class="mb-4">Website Settings</h2>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Custom setting deleted.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- MAIN SETTINGS FORM -->
            <div class="card">
                <form method="post" enctype="multipart/form-data" id="settingsForm">

                    <!-- MAINTENANCE MODE -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="maintenance_mode"
                                id="maintenance_mode" value="1" <?php echo ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="maintenance_mode">Maintenance Mode (show "Under
                                Construction" page to visitors)</label>
                        </div>
                        <small class="text-muted d-block">When enabled, all frontend pages will display a maintenance
                            message. Admin area remains accessible.</small>
                    </div>
                    <!-- GENERAL SETTINGS -->
                    <h4 class="mb-3">General</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Site Name</label>
                            <input type="text" class="form-control" name="site_name"
                                value="<?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Site Logo</label>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <input type="file" class="form-control" name="site_logo" accept="image/*"
                                    style="width: auto; flex:1;">
                                <label class="remove-checkbox">
                                    <input type="checkbox" name="remove_logo" value="1"> Remove current logo
                                </label>
                            </div>
                            <div class="current-logo mt-2">
                                <?php if (!empty($settings['site_logo'])): ?>
                                    <img src="../<?= htmlspecialchars($settings['site_logo']) ?>" alt="Current Logo"
                                        style="max-height:60px;">
                                <?php else: ?>
                                    <span class="text-muted">No logo uploaded.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- SITE KEYWORDS -->
                    <div class="mb-3">
                        <label class="form-label">Site Keywords (SEO)</label>
                        <input type="text" class="form-control" name="site_keywords"
                            value="<?= htmlspecialchars($settings['site_keywords'] ?? '') ?>"
                            placeholder="cinema, movies, popcorn hub, film">
                        <small class="text-muted">Comma‑separated keywords for search engines.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Footer Text</label>
                        <input type="text" class="form-control" name="footer_text"
                            value="<?= htmlspecialchars($settings['footer_text'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Theme Color (hex)</label>
                        <input type="color" class="form-control form-control-color" name="theme_color"
                            value="<?= htmlspecialchars($settings['theme_color'] ?? '#FFA500') ?>"
                            style="width:100px; height:50px;">
                    </div>

                    <!-- CONTACT INFO -->
                    <h4 class="mt-4 mb-3">Contact Information</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Contact Email</label>
                            <input type="email" class="form-control" name="contact_email"
                                value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Phone</label>
                            <input type="text" class="form-control" name="contact_phone"
                                value="<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address"
                            rows="2"><?= htmlspecialchars($settings['address'] ?? '') ?></textarea>
                    </div>

                    <!-- SOCIAL LINKS -->
                    <h4 class="mt-4 mb-3">Social Media</h4>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Facebook URL</label>
                            <input type="url" class="form-control" name="facebook_url"
                                value="<?= htmlspecialchars($settings['facebook_url'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Twitter URL</label>
                            <input type="url" class="form-control" name="twitter_url"
                                value="<?= htmlspecialchars($settings['twitter_url'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Instagram URL</label>
                            <input type="url" class="form-control" name="instagram_url"
                                value="<?= htmlspecialchars($settings['instagram_url'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- CUSTOM SETTINGS -->
                    <h4 class="mt-4 mb-3">Custom Settings</h4>
                    <div id="custom-fields-container">
                        <?php
                        $predefined = ['site_name', 'site_logo', 'site_keywords', 'footer_text', 'theme_color', 'contact_email', 'contact_phone', 'address', 'facebook_url', 'twitter_url', 'instagram_url', 'maintenance_mode'];
                        foreach ($settings as $key => $value):
                            if (!in_array($key, $predefined) && !empty($key)):
                                ?>
                                <div class="custom-field-row" id="row-<?= htmlspecialchars($key) ?>">
                                    <input type="text" class="form-control" name="<?= htmlspecialchars($key) ?>"
                                        value="<?= htmlspecialchars($value) ?>" placeholder="Key" readonly
                                        style="background:#f0f0f0; width:200px;">
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($value) ?>"
                                        placeholder="Value" onchange="updateCustomField(this, '<?= htmlspecialchars($key) ?>')">
                                    <a href="?delete_key=<?= urlencode($key) ?>" class="delete-btn"
                                        onclick="return confirm('Delete this custom setting?')"><i class="bi bi-trash"></i></a>
                                </div>
                                <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addCustomField()"><i
                                class="bi bi-plus"></i> Add Custom Setting</button>
                    </div>

                    <hr class="my-4">
                    <button type="submit" class="btn btn-primary w-100">Save All Settings</button>
                </form>
            </div>

            <!-- HERO SLIDER MANAGEMENT -->
            <div class="card mt-4">
                <h4 class="mb-3">Hero Slider Slides</h4>
                <div id="hero-slides-container">
                    <?php
                    $slides = $conn->query("SELECT * FROM hero_slides ORDER BY slide_order ASC, id ASC");
                    if ($slides->num_rows > 0):
                        while ($slide = $slides->fetch_assoc()):
                            ?>
                            <div class="hero-slide-row d-flex flex-wrap align-items-center gap-2 mb-2"
                                data-id="<?= $slide['id'] ?>">
                                <i class="bi bi-grip-vertical"></i>
                                <input type="text" class="form-control" style="width:250px;"
                                    value="<?= htmlspecialchars($slide['title']) ?>" placeholder="Title">
                                <input type="text" class="form-control" style="width:300px;"
                                    value="<?= htmlspecialchars($slide['image_url']) ?>" placeholder="Image URL">
                                <button type="button" class="btn btn-sm btn-outline-primary update-slide">Update</button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-slide"
                                    data-id="<?= $slide['id'] ?>">Delete</button>
                            </div>
                            <?php
                        endwhile;
                    else:
                        echo '<p class="text-muted">No slides yet. Add one below.</p>';
                    endif;
                    ?>
                </div>

                <hr>
                <h6>Add New Slide</h6>
                <div class="row g-2">
                    <div class="col-md-5">
                        <input type="text" class="form-control" id="new_slide_title" placeholder="Title">
                    </div>
                    <div class="col-md-5">
                        <input type="text" class="form-control" id="new_slide_image" placeholder="Image URL">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-primary" id="add_slide_btn">Add</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer text-center">
        <div class="container">
            <p class="small">
                <?= htmlspecialchars($settings['footer_text'] ?? '© ' . date('Y') . ' Popcorn Hub. All rights reserved.') ?>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
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

        // ========== CUSTOM FIELDS ==========
        function addCustomField() {
            const container = document.getElementById('custom-fields-container');
            const newRow = document.createElement('div');
            newRow.className = 'custom-field-row';
            newRow.innerHTML = `
                <input type="text" class="form-control" name="new_key_${Date.now()}" placeholder="New key" style="width:200px;">
                <input type="text" class="form-control" name="new_value_${Date.now()}" placeholder="Value">
                <span class="delete-btn" onclick="this.parentElement.remove()"><i class="bi bi-x-circle"></i></span>
            `;
            container.appendChild(newRow);
        }

        function updateCustomField(input, originalKey) {
            // placeholder – you can implement if needed
        }

        // ========== HERO SLIDER MANAGEMENT (jQuery) ==========
        $(document).ready(function () {
            $("#hero-slides-container").sortable({
                handle: '.bi-grip-vertical',
                update: function () {
                    const order = [];
                    $('#hero-slides-container .hero-slide-row').each(function (index) {
                        order.push({ id: $(this).data('id'), order: index });
                    });
                    fetch('reorder_hero_slides.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(order)
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('Order saved');
                            } else {
                                alert('Error saving order: ' + (data.error || 'Unknown'));
                            }
                        })
                        .catch(error => {
                            alert('Network error while saving order: ' + error);
                        });
                }
            });

            $(document).on('click', '.update-slide', function () {
                const row = $(this).closest('.hero-slide-row');
                const id = row.data('id');
                const title = row.find('input').eq(0).val();
                const image = row.find('input').eq(1).val();

                fetch('save_hero_slide.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ id, title, image })
                })
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            alert('Slide updated');
                        } else {
                            alert('Error: ' + (data.error || 'Unknown'));
                        }
                    })
                    .catch(error => {
                        alert('Fetch error: ' + error.message);
                    });
            });

            $(document).on('click', '.delete-slide', function () {
                if (!confirm('Delete this slide?')) return;
                const id = $(this).data('id');
                fetch('delete_hero_slide.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ id })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            $(this).closest('.hero-slide-row').fadeOut(300, function () { $(this).remove(); });
                        } else {
                            alert('Error deleting slide: ' + (data.error || 'Unknown'));
                        }
                    })
                    .catch(error => {
                        alert('Network error while deleting: ' + error);
                    });
            });

            $('#add_slide_btn').click(function () {
                const title = $('#new_slide_title').val();
                const image = $('#new_slide_image').val();
                if (!title || !image) {
                    alert('Title and image are required');
                    return;
                }

                fetch('save_hero_slide.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ title, image })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const newRow = `
                            <div class="hero-slide-row d-flex flex-wrap align-items-center gap-2 mb-2" data-id="${data.id}">
                                <i class="bi bi-grip-vertical"></i>
                                <input type="text" class="form-control" style="width:250px;" value="${title}" placeholder="Title">
                                <input type="text" class="form-control" style="width:300px;" value="${image}" placeholder="Image URL">
                                <button type="button" class="btn btn-sm btn-outline-primary update-slide">Update</button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-slide" data-id="${data.id}">Delete</button>
                            </div>
                        `;
                            $('#hero-slides-container').append(newRow);
                            $('#new_slide_title, #new_slide_image').val('');
                        } else {
                            alert('Error adding slide: ' + (data.error || 'Unknown'));
                        }
                    })
                    .catch(error => {
                        alert('Network error while adding: ' + error);
                    });
            });
        });
    </script>
</body>

</html>
