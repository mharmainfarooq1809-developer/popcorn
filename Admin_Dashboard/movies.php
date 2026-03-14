<?php
session_start();
require_once '../db_connect.php';
require_once '../settings_init.php'; // load global settings

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
    <title>Movies · <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
    <?php if (!empty($settings['theme_color'])): ?>
        <style>
            :root { --primary: <?= htmlspecialchars($settings['theme_color']) ?>; }
        </style>
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* ========== UNIFIED ADMIN STYLES ========== */
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

        /* ===== CARDS ===== */
        .card {
            border: none;
            border-radius: 20px;
            padding: 0;
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

        /* Movie card specific */
        .movie-card {
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            cursor: pointer;
            overflow: hidden;
            background: #FFFFFF;
        }

        body.dark-mode .movie-card {
            background: #0F1C2B;
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

        body.dark-mode .movie-card .poster-container {
            background: #1a2634;
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
            color: #6c757d;
            font-size: 14px;
            text-align: center;
            padding: 20px;
        }

        body.dark-mode .movie-card .poster-container .poster-fallback {
            color: #AAAAAA;
        }

        .movie-card .poster-container .poster-fallback i {
            font-size: 48px;
            margin-bottom: 10px;
            color: #adb5bd;
        }

        body.dark-mode .movie-card .poster-container .poster-fallback i {
            color: #6c757d;
        }

        .movie-card .card-body {
            padding: 15px;
        }

        .movie-card .card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #212529;
        }

        body.dark-mode .movie-card .card-title {
            color: #FFFFFF;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-primary {
            background: rgba(255, 165, 0, 0.15);
            color: #FFA500;
        }

        body.dark-mode .badge-primary {
            background: rgba(255, 165, 0, 0.25);
            color: #FFD966;
        }

        .badge-success {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }

        body.dark-mode .badge-success {
            background: rgba(40, 167, 69, 0.25);
            color: #7acf7a;
        }

        .premium-badge {
            background: linear-gradient(145deg, #FFD700, #B8860B);
            color: #000;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 30px;
            font-weight: 600;
            display: inline-block;
            margin-left: 5px;
        }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 40px;
            font-weight: 500;
            font-size: 12px;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            color: #FFFFFF;
            border: none;
            padding: 8px 16px;
            box-shadow: 0 4px 14px rgba(255, 165, 0, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 165, 0, 0.5);
        }

        .btn-outline-primary {
            border: 1px solid var(--primary);
            color: var(--primary);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: var(--primary);
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

        .btn-outline-warning {
            border: 1px solid #ffc107;
            color: #b17f00;
            background: transparent;
        }

        .btn-outline-warning:hover {
            background: #ffc107;
            color: #212529;
        }

        body.dark-mode .btn-outline-warning {
            border-color: #ffdb7c;
            color: #ffdb7c;
        }

        body.dark-mode .btn-outline-warning:hover {
            background: #ffdb7c;
            color: #0F1C2B;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
            border: none;
        }

        .btn-warning:hover {
            background: #ffdb7c;
        }

        .btn-sm {
            padding: 4px 10px;
            font-size: 11px;
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

        .modal-title {
            color: #212529;
            font-weight: 600;
        }

        body.dark-mode .modal-title {
            color: #FFFFFF;
        }

        .modal-footer {
            border-top-color: #E9ECEF;
            padding: 20px;
        }

        body.dark-mode .modal-footer {
            border-top-color: #3A414D;
        }

        /* ===== FORMS ===== */
        .form-control,
        .form-select {
            background: #FFFFFF;
            border: 1px solid #E9ECEF;
            color: #212529;
            border-radius: 10px;
            padding: 8px 12px;
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
            min-height: 80px;
            resize: vertical;
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

        /* ===== SHOWTIME TABLE - FIXED VISIBILITY ===== */
        .showtime-table {
            width: 100%;
            border-collapse: collapse;
            background: transparent;
        }

        /* Table headers - LIGHT MODE */
        .showtime-table thead {
            background: #f8f9fa;
        }

        .showtime-table th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #212529 !important;
            padding: 12px;
            font-size: 14px;
        }

        /* Table cells - LIGHT MODE */
        .showtime-table td {
            border-bottom: 1px solid #dee2e6;
            padding: 12px;
            color: #212529 !important;
            background: #ffffff;
            font-size: 14px;
        }

        /* DARK MODE OVERRIDES */
        body.dark-mode .showtime-table thead {
            background: #1a2634;
        }

        body.dark-mode .showtime-table th {
            background: #1a2634;
            border-bottom-color: #3A414D;
            color: #FFFFFF !important;
        }

        body.dark-mode .showtime-table td {
            border-bottom-color: #3A414D;
            color: #FFFFFF !important;
            background: #0F1C2B;
        }

        /* Hover effect */
        .showtime-table tbody tr:hover td {
            background: rgba(255, 165, 0, 0.05);
        }

        body.dark-mode .showtime-table tbody tr:hover td {
            background: rgba(255, 165, 0, 0.1);
        }

        /* Status badges - fix visibility */
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            text-align: center;
        }

        /* Light mode status badges */
        .status-active {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745 !important;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .status-ended {
            background: rgba(108, 117, 125, 0.15);
            color: #495057 !important;
            border: 1px solid rgba(108, 117, 125, 0.3);
        }

        .status-cancelled {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545 !important;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        /* Dark mode status badges - brighter colors */
        body.dark-mode .status-active {
            background: rgba(40, 167, 69, 0.25);
            color: #7acf7a !important;
            border-color: rgba(40, 167, 69, 0.4);
        }

        body.dark-mode .status-ended {
            background: rgba(108, 117, 125, 0.25);
            color: #c0c0c0 !important;
            border-color: rgba(108, 117, 125, 0.4);
        }

        body.dark-mode .status-cancelled {
            background: rgba(220, 53, 69, 0.25);
            color: #ff8a92 !important;
            border-color: rgba(220, 53, 69, 0.4);
        }

        /* Buttons in table */
        .showtime-table .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
        }

        /* Loading state */
        .showtime-table .text-center {
            color: #6c757d !important;
            background: transparent;
        }

        body.dark-mode .showtime-table .text-center {
            color: #AAAAAA !important;
        }

        /* ===== DATA URL PREVIEW ===== */
        .data-url-preview {
            max-width: 100%;
            max-height: 150px;
            margin-top: 10px;
            border: 1px solid #E9ECEF;
            border-radius: 10px;
            display: none;
            object-fit: contain;
            background: #f8f9fa;
        }

        body.dark-mode .data-url-preview {
            border-color: #3A414D;
            background: #1a2634;
        }

        .data-url-preview.show {
            display: block;
        }

        .data-url-info {
            font-size: 12px;
            margin-top: 5px;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 8px;
            word-break: break-all;
            color: #212529;
        }

        body.dark-mode .data-url-info {
            background: #1a2634;
            color: #FFFFFF;
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

        /* ===== HEADERS WITH TOGGLE ===== */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .page-header h2 {
            margin-bottom: 0;
            font-size: 24px;
            font-weight: 600;
            color: #212529;
        }

        body.dark-mode .page-header h2 {
            color: #FFFFFF;
        }

        .card-header-with-toggle {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            padding: 5px 0;
            border-bottom: 1px solid transparent;
            margin-bottom: 20px;
        }

        .card-header-with-toggle:hover {
            border-bottom-color: var(--primary);
        }

        .card-header-with-toggle h5 {
            margin-bottom: 0;
            font-size: 18px;
            font-weight: 600;
            color: #212529;
        }

        body.dark-mode .card-header-with-toggle h5 {
            color: #FFFFFF;
        }

        .card-header-with-toggle i {
            font-size: 20px;
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
        @media (max-width: 992px) {
            .sidebar {
                left: -100%;
            }

            .top-navbar {
                flex-direction: column;
                align-items: stretch;
            }

            .nav-icons {
                justify-content: flex-end;
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

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .page-header .btn {
                width: 100%;
            }

            .movie-card .poster-container {
                height: 180px;
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

         .avatar-icon {
            font-size: 2.2rem;
            color: var(--primary);
            cursor: pointer;
        }

        .avatar-icon:hover {
            color: var(--primary-dark);
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
            <a href="movies.php" class="nav-link active"><i class="bi bi-film"></i><span>Movies</span></a>

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

        <!-- Page Header -->
        <div class="page-header">
            <h2>Movies</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#movieModal" onclick="clearModal()">
                <i class="bi bi-plus-circle"></i> Add Movie
            </button>
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
                                placeholder="Enter image URL or data:image URL (can be very long)"></textarea>
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
                        <table class="table showtime-table">
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
            <p class="small"><?= htmlspecialchars($settings['footer_text'] ?? '© '.date('Y').' Popcorn Hub. All rights reserved.') ?></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="admin_toggle.js"></script>
    <script>
        // ================= GLOBAL VARIABLES =================
        let movies = [];
        let currentShowtimeMovieId = null;
        let showtimes = [];

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
                preview.src = url;
                preview.classList.add('show');
                
                if (isDataUrl(url)) {
                    info.innerHTML = `📸 Data URL · Length: ${url.length} characters · Type: ${url.split(';')[0] || 'Unknown'}`;
                } else if (url.startsWith('http')) {
                    info.innerHTML = `🌐 External URL · ${url.substring(0, 100)}${url.length > 100 ? '...' : ''}`;
                } else {
                    info.innerHTML = `📁 Local path · ${url}`;
                }
                
                preview.onerror = function() {
                    info.innerHTML = '⚠️ Image failed to load. Please check the URL.';
                    info.style.color = '#dc3545';
                };
                
                preview.onload = function() {
                    info.style.color = '';
                    if (!info.innerHTML.includes('⚠️')) {
                        info.innerHTML += ` · ${this.naturalWidth} x ${this.naturalHeight}px`;
                    }
                };
            } else {
                preview.classList.remove('show');
                preview.src = '';
                info.innerHTML = '';
            }
        }

        // ================= HANDLE IMAGE ERRORS =================
        function handleImageError(img, movieTitle) {
            console.log('Image failed to load for:', movieTitle);
            
            // Don't show alerts for data URLs
            if (img.src && isDataUrl(img.src)) {
                return;
            }
            
            // Replace with fallback
            const container = img.parentElement;
            if (container) {
                const fallback = document.createElement('div');
                fallback.className = 'poster-fallback';
                fallback.innerHTML = `
                    <i class="bi bi-film"></i>
                    <span>${movieTitle || 'No Poster'}</span>
                    <small style="font-size: 10px; margin-top: 5px;">Image failed to load</small>
                `;
                img.style.display = 'none';
                container.appendChild(fallback);
            }
        }

        // ================= FETCH MOVIES =================
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

        // ================= RENDER MOVIES =================
        function renderMovies(moviesArray) {
            const grid = document.getElementById('moviesGrid');
            if (!grid) return;
            grid.innerHTML = '';
            
            moviesArray.forEach(movie => {
                const isPremium = Number(movie.is_premium) === 1;
                const isFeatured = Number(movie.is_featured) === 1;
                const starIcon = isFeatured ? 'bi-star-fill' : 'bi-star';
                const starClass = isFeatured ? 'btn-warning' : 'btn-outline-warning';
                
                const imageUrl = movie.image_url || 'https://via.placeholder.com/400x600?text=No+Poster';
                
                if (isDataUrl(imageUrl)) {
                    console.log(`Movie "${movie.title}" uses data URL (length: ${imageUrl.length} chars)`);
                }
                
                const col = document.createElement('div');
                col.className = 'col-md-6 col-lg-4 col-xl-3';
                col.innerHTML = `
                    <div class="card movie-card">
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
                            <h5 class="card-title">${movie.title} ${isPremium ? '<span class="premium-badge">Premium</span>' : ''}</h5>
                            <div class="mb-2">
                                <span class="badge badge-primary me-1">${movie.category}</span>
                                <span class="badge badge-success">${movie.language}</span>
                            </div>
                            <p class="text-muted small mb-3">${movie.genre}</p>
                            <div class="d-flex flex-wrap gap-1">
                                <button class="btn btn-sm ${starClass}" onclick="toggleFeatured(${movie.id}, this)" title="Featured"><i class="bi ${starIcon}"></i></button>
                                <button class="btn btn-sm btn-outline-primary" onclick="editMovie(${movie.id})"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-info" onclick="manageShowtimes(${movie.id})"><i class="bi bi-calendar-week"></i></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteMovie(${movie.id})"><i class="bi bi-trash"></i></button>
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
            console.log('manageShowtimes called with movieId:', movieId);
            currentShowtimeMovieId = movieId;
            document.getElementById('currentMovieId').value = movieId;
            
            // Reset the modal content
            document.getElementById('showtimesBody').innerHTML = '<tr><td colspan="5" class="text-center"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading showtimes...</td></tr>';
            
            // Clear form fields
            document.getElementById('newTheatre').value = '';
            document.getElementById('newDate').value = '';
            document.getElementById('newTime').value = '';
            
            // Set default date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('newDate').value = today;
            
            // Load showtimes
            loadShowtimes(movieId);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('showtimeModal'));
            modal.show();
        }

        function loadShowtimes(movieId) {
            console.log('Loading showtimes for movie ID:', movieId);
            
            fetch('get_showtimes.php?movie_id=' + movieId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP error ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Showtimes received:', data);
                    showtimes = data;
                    renderShowtimes(data);
                })
                .catch(error => {
                    console.error('Error loading showtimes:', error);
                    document.getElementById('showtimesBody').innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load showtimes. Please try again.</td></tr>';
                });
        }

        function renderShowtimes(showtimesArray) {
            const tbody = document.getElementById('showtimesBody');
            if (!tbody) return;
            
            if (!showtimesArray || showtimesArray.length === 0) {
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
            console.log('addShowtime called');
            console.log('currentShowtimeMovieId:', currentShowtimeMovieId);
            
            const theatre = document.getElementById('newTheatre').value;
            const date = document.getElementById('newDate').value;
            const time = document.getElementById('newTime').value;
            
            console.log('Theatre:', theatre);
            console.log('Date:', date);
            console.log('Time:', time);
            
            if (!theatre || !date || !time) {
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'Missing fields', 
                    text: 'Please fill all fields.' 
                });
                return;
            }
            
            if (!currentShowtimeMovieId) {
                Swal.fire({ 
                    icon: 'error', 
                    title: 'Error', 
                    text: 'No movie selected.' 
                });
                return;
            }
            
            // Show loading
            Swal.fire({
                title: 'Adding...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new URLSearchParams();
            formData.append('movie_id', currentShowtimeMovieId);
            formData.append('theatre', theatre);
            formData.append('show_date', date);
            formData.append('show_time', time);

            console.log('Sending data:', Object.fromEntries(formData));

            fetch('save_showtime.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString(),
                credentials: 'same-origin'
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    if (response.status === 403) {
                        throw new Error('Unauthorized - Please refresh the page and try again');
                    }
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    // Clear form
                    document.getElementById('newTheatre').value = '';
                    document.getElementById('newDate').value = '';
                    document.getElementById('newTime').value = '';
                    
                    // Reload showtimes
                    loadShowtimes(currentShowtimeMovieId);
                    
                    Swal.fire({ 
                        icon: 'success', 
                        title: 'Added!', 
                        text: 'Showtime added successfully',
                        timer: 1500, 
                        showConfirmButton: false 
                    });
                } else {
                    Swal.fire({ 
                        icon: 'error', 
                        title: 'Error', 
                        text: data.error || 'Failed to add showtime.' 
                    });
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                Swal.fire({ 
                    icon: 'error', 
                    title: 'Error', 
                    text: error.message 
                });
            });
        }

        function deleteShowtime(showtimeId) {
            console.log('deleteShowtime called for ID:', showtimeId);
            
            Swal.fire({
                title: 'Delete showtime?',
                text: "This action cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    const formData = new URLSearchParams();
                    formData.append('id', showtimeId);

                    fetch('delete_showtime.php', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: formData.toString(),
                        credentials: 'same-origin'
                    })
                    .then(response => {
                        if (!response.ok) {
                            if (response.status === 403) {
                                throw new Error('Unauthorized - Please refresh the page and try again');
                            }
                            throw new Error('HTTP error ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            loadShowtimes(currentShowtimeMovieId);
                            Swal.fire('Deleted!', '', 'success');
                        } else {
                            Swal.fire({ 
                                icon: 'error', 
                                title: 'Error', 
                                text: data.error || 'Failed to delete.' 
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Delete error:', error);
                        Swal.fire({ 
                            icon: 'error', 
                            title: 'Error', 
                            text: error.message 
                        });
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
            
            if (movie.image_url) {
                setTimeout(previewDataUrl, 100);
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

        // ================= NOTIFICATIONS =================
        function updateNotifications() {
            fetch('get_notifications.php')
                .then(res => res.json())
                .then(data => {
                    const badge = document.getElementById('notificationBadge');
                    const list = document.getElementById('notificationList');
                    if (!badge || !list) return;
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
                });
        }

        document.getElementById('markAllRead')?.addEventListener('click', (e) => {
            e.preventDefault();
            fetch('mark_notifications_read.php', { method: 'POST' })
                .then(res => res.json())
                .then(data => { if (data.success) updateNotifications(); });
        });

        // ================= INITIALIZATION =================
        document.addEventListener('DOMContentLoaded', function() {
            loadMovies();
            updateNotifications();
            setInterval(updateNotifications, 30000);
            
            // Image preview
            const imageInput = document.getElementById('image_url');
            if (imageInput) {
                imageInput.addEventListener('input', function() {
                    clearTimeout(window.previewTimeout);
                    window.previewTimeout = setTimeout(previewDataUrl, 500);
                });
            }
        });
    </script>
</body>
</html>