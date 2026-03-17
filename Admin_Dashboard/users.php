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
    <title>Users - <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
    <?php if (!empty($settings['theme_color'])): ?>
        <style>
            :root {
                ;
            }
        </style>
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* ========== UNIFIED ADMIN STYLES ========== */
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

        /* ===== HEADINGS - FIXED DARK MODE ===== */
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
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
            margin: 0;
            color: #212529;
        }

        body.dark-mode .page-title {
            color: #FFFFFF;
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
            transition: color 0.2s;
        }

        body.dark-mode .sidebar .toggle-btn {
            color: #FFFFFF;
        }

        .sidebar .toggle-btn:hover {
            color: #FFA500;
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
            color: #FFA500;
        }

        .sidebar .nav-link.active {
            background: #FFA500;
            color: #FFFFFF;
        }

        body.dark-mode .sidebar .nav-link.active {
            background: #cc7f00;
            color: #FFFFFF;
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

        .submenu {
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
        }

        .submenu .nav-link {
            padding-left: 42px !important;
            font-size: 13px;
        }

        .submenu .nav-link i {
            font-size: 14px;
            min-width: 20px;
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

        .menu-toggle,
        .menu-toggle-mobile {
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
            transition: color 0.2s;
        }

        body.dark-mode .nav-icons .icon {
            color: #FFFFFF;
        }

        .nav-icons .icon:hover {
            color: #FFA500;
        }

        .nav-icons .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #FFA500;
            color: #FFFFFF;
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
            color: #FFA500;
            cursor: pointer;
            transition: color 0.2s;
        }

        .avatar-icon:hover {
            color: #cc7f00;
        }

        .theme-toggle {
            cursor: pointer;
            font-size: 22px;
            color: #212529;
            transition: color 0.2s;
        }

        body.dark-mode .theme-toggle {
            color: #FFFFFF;
        }

        .theme-toggle:hover {
            color: #FFA500;
        }

        /* Search Box */
        .search-box {
            position: relative;
            width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border-radius: 40px;
            border: 1px solid #E9ECEF;
            background: #FFFFFF;
            color: #212529;
        }

        body.dark-mode .search-box input {
            background: #0F1C2B;
            border-color: #3A414D;
            color: #FFFFFF;
        }

        .search-box input:focus {
            outline: none;
            border-color: #FFA500;
            box-shadow: 0 0 0 3px rgba(255, 165, 0, 0.2);
        }

        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        body.dark-mode .search-box i {
            color: #adb5bd;
        }

        /* ===== CARDS ===== */
        .card {
            border: none;
            border-radius: 20px;
            padding: 20px;
            background: #FFFFFF;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
            margin-bottom: 20px;
        }

        body.dark-mode .card {
            background: #0F1C2B;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        /* ===== BADGES ===== */
        .badge-success {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
            padding: 5px 10px;
            border-radius: 30px;
            font-weight: 500;
        }

        .badge-warning {
            background: rgba(255, 193, 7, 0.15);
            color: #b17f00;
            padding: 5px 10px;
            border-radius: 30px;
            font-weight: 500;
        }

        .badge-danger {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
            padding: 5px 10px;
            border-radius: 30px;
            font-weight: 500;
        }

        .badge-info {
            background: rgba(23, 162, 184, 0.15);
            color: #17a2b8;
            padding: 5px 10px;
            border-radius: 30px;
            font-weight: 500;
        }

        .badge-primary {
            background: rgba(255, 165, 0, 0.15);
            color: #FFA500;
            padding: 5px 10px;
            border-radius: 30px;
            font-weight: 500;
        }

        /* Dark mode badges */
        body.dark-mode .badge-success {
            background: rgba(40, 167, 69, 0.25);
            color: #7acf7a;
        }

        body.dark-mode .badge-warning {
            background: rgba(255, 193, 7, 0.25);
            color: #ffdb7c;
        }

        body.dark-mode .badge-danger {
            background: rgba(220, 53, 69, 0.25);
            color: #ff8a92;
        }

        body.dark-mode .badge-info {
            background: rgba(23, 162, 184, 0.25);
            color: #6ed4ff;
        }

        body.dark-mode .badge-primary {
            background: rgba(255, 165, 0, 0.25);
            color: #FFD966;
        }

        /* ===== BUTTONS ===== */
        .btn-primary {
            background: linear-gradient(145deg, #FFA500, #cc7f00);
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
            color: #FFFFFF;
        }

        .btn-outline-primary {
            border: 1px solid #FFA500;
            color: #FFA500;
            background: transparent;
            border-radius: 40px;
            padding: 8px 20px;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
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
            color: #0F1C2B;
        }

        .btn-outline-danger {
            border: 1px solid #dc3545;
            color: #dc3545;
            background: transparent;
            border-radius: 40px;
            padding: 8px 20px;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-outline-danger:hover {
            background: #dc3545;
            color: #FFFFFF;
        }

        body.dark-mode .btn-outline-danger {
            border-color: #ff8a92;
            color: #ff8a92;
        }

        body.dark-mode .btn-outline-danger:hover {
            background: #ff8a92;
            color: #0F1C2B;
        }

        .btn-outline-info {
            border: 1px solid #17a2b8;
            color: #17a2b8;
            background: transparent;
            border-radius: 40px;
            padding: 8px 20px;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-outline-info:hover {
            background: #17a2b8;
            color: #FFFFFF;
        }

        body.dark-mode .btn-outline-info {
            border-color: #6ed4ff;
            color: #6ed4ff;
        }

        body.dark-mode .btn-outline-info:hover {
            background: #6ed4ff;
            color: #0F1C2B;
        }

        .btn-outline-secondary {
            border-radius: 40px;
            padding: 8px 20px;
            border: 1px solid #E9ECEF;
            color: #212529;
            background: transparent;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        body.dark-mode .btn-outline-secondary {
            border-color: #3A414D;
            color: #FFFFFF;
        }

        .btn-outline-secondary:hover {
            background: #FFA500;
            color: #FFFFFF;
            border-color: #FFA500;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        /* ===== TABLES - FIXED DARK MODE ===== */
        .table-responsive {
            overflow-x: auto;
            border-radius: 20px;
            background: #FFFFFF;
            border: 1px solid #E9ECEF;
            margin-top: 10px;
        }

        body.dark-mode .table-responsive {
            background: #0F1C2B;
            border-color: #3A414D;
        }

        .table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }

        /* LIGHT MODE */
        .table {
            color: #212529 !important;
            background: transparent;
        }

        .table th {
            background: #f8f9fa;
            border-bottom: 2px solid #E9ECEF;
            font-weight: 600;
            color: #495057 !important;
            padding: 15px 12px;
            white-space: nowrap;
        }

        .table td {
            border-bottom: 1px solid #E9ECEF;
            padding: 12px;
            vertical-align: middle;
            color: #212529 !important;
            background: transparent;
        }

        /* DARK MODE */
        body.dark-mode .table {
            color: #FFFFFF !important;
        }

        body.dark-mode .table th {
            background: #1a2634;
            border-bottom-color: #3A414D;
            color: #FFFFFF !important;
        }

        body.dark-mode .table td {
            border-bottom-color: #3A414D;
            color: #FFFFFF !important;
            background: transparent;
        }

        .table tbody tr:hover td {
            background: rgba(0, 0, 0, 0.02);
        }

        body.dark-mode .table tbody tr:hover td {
            background: #1a2634;
        }

        /* Avatar in table */
        .table td img.rounded-circle {
            border: 2px solid transparent;
            transition: border-color 0.2s;
        }

        .table td img.rounded-circle:hover {
            border-color: var(--primary);
        }

        body.dark-mode .table td i.bi-person-circle {
            color: #FFD966 !important;
        }

        /* ===== MODALS ===== */
        .modal-content {
            background: #FFFFFF;
            border: none;
            border-radius: 20px;
        }

        body.dark-mode .modal-content {
            background: #0F1C2B;
        }

        .modal-header {
            border-bottom-color: #E9ECEF;
            padding: 20px;
        }

        body.dark-mode .modal-header {
            border-bottom-color: #3A414D;
        }

        .modal-footer {
            border-top-color: #E9ECEF;
            padding: 20px;
        }

        body.dark-mode .modal-footer {
            border-top-color: #3A414D;
        }

        .modal-title {
            font-weight: 600;
            color: #212529;
        }

        body.dark-mode .modal-title {
            color: #FFFFFF;
        }

        .modal-body {
            color: #212529;
        }

        body.dark-mode .modal-body {
            color: #FFFFFF;
        }

        .form-label {
            font-weight: 500;
            color: #212529;
            margin-bottom: 8px;
        }

        body.dark-mode .form-label {
            color: #FFFFFF;
        }

        .form-control,
        .form-select {
            background: #FFFFFF;
            border: 1px solid #E9ECEF;
            border-radius: 10px;
            padding: 8px 12px;
            color: #212529;
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
            border-color: #FFA500;
            box-shadow: 0 0 0 4px rgba(255, 165, 0, 0.2);
            outline: none;
        }

        /* ===== PAGINATION ===== */
        .pagination {
            gap: 5px;
        }

        .page-link {
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #212529;
            background: #FFFFFF;
            transition: var(--transition);
        }

        body.dark-mode .page-link {
            background: #0F1C2B;
            color: #FFFFFF;
        }

        .page-link:hover {
            background: #FFA500;
            color: #FFFFFF;
        }

        .page-item.active .page-link {
            background: #FFA500;
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
            transition: max-height 0.5s ease, opacity 0.3s ease, margin 0.3s ease;
        }

        .toggle-content.show {
            max-height: 1000px;
            opacity: 1;
            margin-top: 20px;
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

        /* ===== TEXT UTILITIES ===== */
        .text-muted {
            color: #6c757d !important;
        }

        body.dark-mode .text-muted {
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

            .search-box {
                width: 100%;
            }

            .nav-icons {
                justify-content: flex-end;
            }

            .table th,
            .table td {
                padding: 10px 8px;
                font-size: 14px;
            }

            .btn-sm {
                padding: 4px 8px;
                font-size: 11px;
            }

            .d-flex.justify-content-between {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 10px;
            }

            .btn-primary {
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

            <!-- Bookings -->
            <a href="bookings.php" class="nav-link" title="Bookings">
                <i class="bi bi-ticket"></i>
                <span>Bookings</span>
            </a>

            <!-- Users (with submenu) - expanded -->
            <div class="nav-item">
                <a class="nav-link active" data-bs-toggle="collapse" href="#usersSubmenu" role="button"
                    aria-expanded="true" aria-controls="usersSubmenu" title="Users">
                    <i class="bi bi-people"></i>
                    <span>Users</span>
                    <i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <div class="collapse show" id="usersSubmenu">
                    <a href="users.php" class="nav-link submenu-link active" title="All Users">
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

    <div class="main-content">
        <div class="top-navbar">
            <div class="d-flex align-items-center">
                <i class="bi bi-list menu-toggle me-3" id="menuToggle"></i>
                <div class="search-box">
                    <input type="text" class="form-control" id="searchUsers" placeholder="Search users...">
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
                        <li><a class="dropdown-item text-center small" href="#" id="markAllRead">Mark all as read</a>
                        </li>
                    </ul>
                </div>

                <div class="theme-toggle" id="themeToggle"><i class="bi bi-moon"></i></div>
                <i class="bi bi-person-circle avatar-icon"></i>
            </div>
        </div>

        <!-- Page Header with Toggle Effect -->
        <div class="card-header-with-toggle" data-target="usersTableSection" data-default-expanded="true">
            <h2>User Management</h2>
            <i class="bi bi-chevron-right"></i>
        </div>

        <!-- Users Table Section -->
        <div class="toggle-content show" id="usersTableSection">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div></div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-plus-circle"></i> Add User
                </button>
            </div>

            <!-- Users Table Card -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table" id="usersTable">
                        <thead>
                            <tr>
                                <th>#</th>
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
                                                <img src="<?= htmlspecialchars($row['profile_image']) ?>" alt="Avatar"
                                                    class="rounded-circle" width="35" height="35" style="object-fit: cover;">
                                            <?php else: ?>
                                                <i class="bi bi-person-circle"
                                                    style="font-size: 1.8rem; color: var(--primary);"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['name']) ?></td>
                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                        <td>
                                            <?php if ($row['role'] == 'admin'): ?>
                                                <span class="badge badge-primary">Admin</span>
                                            <?php else: ?>
                                                <span class="badge badge-info">User</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $row['points'] ?></td>
                                        <td><?= date('Y-m-d', strtotime($row['created_at'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary edit-user" data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                                data-email="<?= htmlspecialchars($row['email']) ?>"
                                                data-role="<?= $row['role'] ?>" data-points="<?= $row['points'] ?>"
                                                data-profile="<?= htmlspecialchars($row['profile_image']) ?>" title="Edit User">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger delete-user"
                                                data-id="<?= $row['id'] ?>" data-name="<?= htmlspecialchars($row['name']) ?>"
                                                title="Delete User">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <a href="user_dashboard.php?user_id=<?= $row['id'] ?>"
                                                class="btn btn-sm btn-outline-info" title="View Dashboard">
                                                <i class="bi bi-bar-chart"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No users found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination (static) -->
                <nav class="mt-3">
                    <ul class="pagination justify-content-center">
                        <li class="page-item disabled"><a class="page-link" href="#"><i
                                    class="bi bi-chevron-left"></i></a></li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item"><a class="page-link" href="#">4</a></li>
                        <li class="page-item"><a class="page-link" href="#"><i class="bi bi-chevron-right"></i></a></li>
                    </ul>
                </nav>
            </div>
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
                            <input type="url" class="form-control" name="profile_image" id="edit_profile_image"
                                placeholder="https://...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Points</label>
                            <input type="number" class="form-control" name="points" id="edit_points">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" name="password"
                                placeholder="Enter new password">
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

        // ========== SEARCH FUNCTIONALITY ==========
        const searchUsersInput = document.getElementById('searchUsers');
        if (searchUsersInput) {
            searchUsersInput.addEventListener('input', function () {
                const filter = this.value.toLowerCase();
                const rows = document.querySelectorAll('#usersTableBody tr');
                rows.forEach(row => {
                    const name = row.querySelector('td:nth-child(3)')?.innerText.toLowerCase() || '';
                    const email = row.querySelector('td:nth-child(4)')?.innerText.toLowerCase() || '';
                    row.style.display = (name.includes(filter) || email.includes(filter)) ? '' : 'none';
                });
            });
        }

        // ========== EDIT USER ==========
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

        // ========== UPDATE USER ==========
        document.getElementById('updateUserBtn').addEventListener('click', function () {
            const form = document.getElementById('editUserForm');
            const formData = new FormData(form);

            Swal.fire({
                title: 'Updating...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('update_user.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: 'User updated successfully.',
                            timer: 1500
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.error || 'Update failed.', 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Error', 'Network error.', 'error');
                });
        });

        // ========== DELETE USER ==========
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
                    confirmButtonText: 'Yes, delete',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Deleting...',
                            text: 'Please wait',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        fetch('delete_user.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'id=' + id
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: 'User has been deleted.',
                                        timer: 1500
                                    }).then(() => {
                                        location.reload();
                                    });
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

        // ========== ADD USER ==========
        document.getElementById('saveUserBtn').addEventListener('click', function () {
            const form = document.getElementById('addUserForm');
            const formData = new FormData(form);

            // Validate form
            if (!formData.get('name') || !formData.get('email') || !formData.get('password')) {
                Swal.fire('Error', 'Please fill in all required fields.', 'error');
                return;
            }

            Swal.fire({
                title: 'Saving...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('add_user.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'User added successfully.',
                            timer: 1500
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.error || 'Add failed.', 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Error', 'Network error.', 'error');
                });
        });

        // ========== TOOLTIP INITIALIZATION ==========
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>

</html>
