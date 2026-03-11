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
                $_POST['site_logo'] = 'uploads/' . $new_filename;
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
            padding: 25px;
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

        .form-check-input {
            background-color: #FFFFFF;
            border-color: #E9ECEF;
        }

        body.dark-mode .form-check-input {
            background-color: #0F1C2B;
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

        /* Alternative style - with select dropdown */
        .card-header-with-select {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .card-header-with-select h5 {
            margin-bottom: 0;
            font-size: 18px;
            font-weight: 600;
            color: #212529;
        }

        body.dark-mode .card-header-with-select h5 {
            color: #FFFFFF;
        }

        .card-header-with-select select {
            min-width: 150px;
            background: #FFFFFF;
            border: 1px solid #E9ECEF;
            border-radius: 40px;
            padding: 8px 16px;
            color: #212529;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        body.dark-mode .card-header-with-select select {
            background: #0F1C2B;
            border-color: #3A414D;
            color: #FFFFFF;
        }

        body.dark-mode .card-header-with-select select option {
            background: #0F1C2B;
            color: #FFFFFF;
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

        /* ===== HERO SLIDE ROWS ===== */
        .hero-slide-row {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 12px;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        body.dark-mode .hero-slide-row {
            background: #1a2634;
        }

        .hero-slide-row:hover {
            background: #e9ecef;
            border-color: var(--primary);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: scale(1.01);
        }

        body.dark-mode .hero-slide-row:hover {
            background: #2a3644;
        }

        .hero-slide-row .bi-grip-vertical {
            font-size: 1.5rem;
            color: #adb5bd;
            cursor: move;
        }

        body.dark-mode .hero-slide-row .bi-grip-vertical {
            color: #6c757d;
        }

        .hero-slide-row .bi-grip-vertical:hover {
            color: var(--primary);
        }

        /* Inputs inside hero rows */
        .hero-slide-row .form-control {
            background: #FFFFFF;
            color: #212529;
        }

        body.dark-mode .hero-slide-row .form-control {
            background: #0F1C2B;
            color: #FFFFFF;
            border-color: #3A414D;
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

        /* ===== REMOVE CHECKBOX STYLING ===== */
        .remove-checkbox {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #212529;
            white-space: nowrap;
        }

        body.dark-mode .remove-checkbox {
            color: #FFFFFF;
        }

        .remove-checkbox input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        /* ===== CURRENT LOGO DISPLAY ===== */
        .current-logo {
            margin-top: 10px;
        }

        .current-logo img {
            max-height: 60px;
            border-radius: 8px;
            border: 1px solid #E9ECEF;
        }

        body.dark-mode .current-logo img {
            border-color: #3A414D;
        }

        .current-logo span.text-muted {
            color: #6c757d !important;
        }

        body.dark-mode .current-logo span.text-muted {
            color: #AAAAAA !important;
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

            .card-header-with-toggle,
            .card-header-with-select {
                flex-direction: column;
                align-items: flex-start;
            }

            .remove-checkbox {
                margin-top: 10px;
            }

            .btn-primary.w-100 {
                width: 100%;
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
            <a href="messages.php" class="nav-link"><i class="bi bi-chat-dots"></i><span>Messages</span></a>
            <a href="votes.php" class="nav-link"><i class="bi bi-bar-chart"></i><span>Voting</span></a>

            <!-- Settings submenu - expanded and active -->
            <div class="nav-item">
                <a class="nav-link active" data-bs-toggle="collapse" href="#settingsSubmenu" role="button" aria-expanded="true">
                    <i class="bi bi-gear"></i><span>Settings</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <div class="collapse show" id="settingsSubmenu">
                    <a href="settings.php" class="nav-link submenu-link active"><i class="bi bi-sliders2"></i><span>General</span></a>
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

        <!-- Page Header with Toggle -->
        <div class="card-header-with-toggle" data-target="settingsSection" data-default-expanded="true">
            <h2>Website Settings</h2>
            <i class="bi bi-chevron-right"></i>
        </div>

        <!-- Settings Section -->
        <div class="toggle-content show" id="settingsSection">
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

            <!-- MAIN SETTINGS FORM -->
            <div class="card">
                <form method="post" enctype="multipart/form-data" id="settingsForm">
                    <!-- MAINTENANCE MODE -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="maintenance_mode"
                                id="maintenance_mode" value="1" <?php echo ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                        </div>
                        <small class="text-muted d-block">When enabled, all frontend pages will display a maintenance message.</small>
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
                                    <img src="../<?= htmlspecialchars($settings['site_logo']) ?>" alt="Current Logo">
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

                    <hr class="my-4">
                    <button type="submit" class="btn btn-primary w-100">Save All Settings</button>
                </form>
            </div>

            <!-- HERO SLIDER MANAGEMENT -->
            <div class="card mt-4">
                <div class="card-header-with-toggle" data-target="heroSlidesSection" data-default-expanded="true">
                    <h4>Hero Slider Slides</h4>
                    <i class="bi bi-chevron-right"></i>
                </div>
                <div class="toggle-content show" id="heroSlidesSection">
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

    // FIXED DELETE FUNCTION
    $(document).on('click', '.delete-slide', function () {
        const id = $(this).data('id');
        const row = $(this).closest('.hero-slide-row');
        
        if (!confirm('Delete this slide?')) return;
        
        // Show loading state
        const originalText = $(this).text();
        $(this).text('Deleting...').prop('disabled', true);
        
        fetch('delete_hero_slide.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({ id: id })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Fade out and remove the row
                row.fadeOut(300, function() { 
                    $(this).remove(); 
                    
                    // Show message if no slides left
                    if ($('#hero-slides-container .hero-slide-row').length === 0) {
                        $('#hero-slides-container').html('<p class="text-muted">No slides yet. Add one below.</p>');
                    }
                });
            } else {
                alert('Error deleting slide: ' + (data.error || 'Unknown error'));
                // Reset button
                $(this).text(originalText).prop('disabled', false);
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            alert('Network error while deleting: ' + error.message);
            // Reset button
            $(this).text(originalText).prop('disabled', false);
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