<?php
session_start();
require_once '../db_connect.php';
require_once '../settings_init.php'; // load global settings

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$admin_name = $_SESSION['user_name'] ?? 'Admin';

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$booking_id) {
    header("Location: bookings.php");
    exit;
}

// Fetch booking details with related info
$stmt = $conn->prepare("
    SELECT 
        b.*,
        u.name AS user_name,
        u.email AS user_email,
        s.show_date,
        s.show_time,
        s.theatre,
        m.title AS movie_title,
        m.image_url AS movie_image
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    LEFT JOIN showtimes s ON b.showtime_id = s.id
    LEFT JOIN movies m ON s.movie_id = m.id
    WHERE b.id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    $_SESSION['error'] = "Booking not found.";
    header("Location: bookings.php");
    exit;
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $holder_name = trim($_POST['holder_name'] ?? '');
    $adults = intval($_POST['adults'] ?? 0);
    $children = intval($_POST['children'] ?? 0);
    $total_price = floatval($_POST['total_price'] ?? 0);
    $discount_applied = isset($_POST['discount_applied']) ? 1 : 0;
    $status = $_POST['status'] ?? 'confirmed';
    $seats = trim($_POST['seats'] ?? '');

    if (empty($holder_name) || ($adults + $children) == 0 || empty($seats)) {
        $error = "Holder name, at least one ticket, and seats are required.";
    } else {
        $update = $conn->prepare("
            UPDATE bookings 
            SET holder_name = ?, adults = ?, children = ?, total_price = ?, discount_applied = ?, status = ?, seats = ?
            WHERE id = ?
        ");
        $update->bind_param("siiidisi", $holder_name, $adults, $children, $total_price, $discount_applied, $status, $seats, $booking_id);
        if ($update->execute()) {
            $message = "Booking updated successfully.";
            // Refresh booking data
            $stmt = $conn->prepare("
                SELECT 
                    b.*,
                    u.name AS user_name,
                    u.email AS user_email,
                    s.show_date,
                    s.show_time,
                    s.theatre,
                    m.title AS movie_title,
                    m.image_url AS movie_image
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                LEFT JOIN showtimes s ON b.showtime_id = s.id
                LEFT JOIN movies m ON s.movie_id = m.id
                WHERE b.id = ?
            ");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $booking = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
        $update->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Booking · <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
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

        /* ===== IMPROVED RELATED INFO SECTION ===== */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-card {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 16px;
            transition: all 0.3s ease;
            border: 1px solid #E9ECEF;
        }

        body.dark-mode .info-card {
            background: #2a3644;
            border-color: #3A414D;
        }

        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            border-color: var(--primary);
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 165, 0, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            color: var(--primary);
            font-size: 1.2rem;
        }

        .info-card .label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        body.dark-mode .info-card .label {
            color: #AAAAAA;
        }

        .info-card .value {
            font-size: 16px;
            font-weight: 600;
            color: #212529;
            line-height: 1.4;
        }

        body.dark-mode .info-card .value {
            color: #FFFFFF;
        }

        .info-card .value small {
            font-size: 13px;
            font-weight: 400;
            color: #6c757d;
            display: block;
            margin-top: 2px;
        }

        body.dark-mode .info-card .value small {
            color: #AAAAAA;
        }

        /* User info special card */
        .user-info-card {
            grid-column: span 2;
            background: linear-gradient(135deg, rgba(255,165,0,0.05) 0%, rgba(255,165,0,0.02) 100%);
            border: 1px solid rgba(255,165,0,0.2);
        }

        body.dark-mode .user-info-card {
            background: linear-gradient(135deg, rgba(255,165,0,0.15) 0%, rgba(255,165,0,0.05) 100%);
            border-color: rgba(255,165,0,0.3);
        }

        .user-info-card .info-icon {
            background: rgba(255, 165, 0, 0.2);
        }

        /* Ticket button */
        .ticket-btn {
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            color: #FFFFFF;
            border: none;
            border-radius: 16px;
            padding: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            margin-top: 10px;
        }

        .ticket-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255,165,0,0.4);
            color: #FFFFFF;
        }

        .ticket-btn i {
            font-size: 1.2rem;
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

        .btn-outline-primary {
            border: 1px solid var(--primary);
            color: var(--primary);
            background: transparent;
            border-radius: 40px;
            padding: 8px 20px;
            transition: all 0.2s;
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
            color: #1a2634;
        }

        .btn-outline-secondary {
            border: 1px solid #E9ECEF;
            color: #212529;
            background: transparent;
            border-radius: 40px;
            padding: 8px 20px;
            transition: all 0.2s;
        }

        body.dark-mode .btn-outline-secondary {
            border-color: #3A414D;
            color: #FFFFFF;
        }

        .btn-outline-secondary:hover {
            background: var(--primary);
            color: #FFFFFF;
            border-color: var(--primary);
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
            margin-bottom: 20px;
            cursor: pointer;
            padding: 5px 0;
            border-bottom: 1px solid transparent;
        }

        .card-header-with-toggle:hover {
            border-bottom-color: var(--primary);
        }

        .card-header-with-toggle h4 {
            margin-bottom: 0;
            font-size: 18px;
            font-weight: 600;
            color: #212529;
        }

        body.dark-mode .card-header-with-toggle h4 {
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

            .card {
                padding: 15px;
            }

            .row.g-4 {
                flex-direction: column;
            }

            .col-lg-6 {
                width: 100%;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .user-info-card {
                grid-column: span 1;
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

            <a href="bookings.php" class="nav-link active"><i class="bi bi-ticket"></i><span>Bookings</span></a>

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
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div class="d-flex align-items-center">
                <i class="bi bi-list menu-toggle me-3" id="menuToggle"></i>
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

        <!-- Page Header -->
        <div class="page-header">
            <h2>Edit Booking #<?= $booking_id ?></h2>
            <a href="bookings.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Bookings</a>
        </div>

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

        <!-- Main Content Row -->
        <div class="row g-4">
            <!-- Left column: Edit Form -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header-with-toggle" data-target="editFormSection" data-default-expanded="true">
                        <h4>Booking Details</h4>
                        <i class="bi bi-chevron-right"></i>
                    </div>
                    <div class="toggle-content show" id="editFormSection">
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label">Ticket Holder Name</label>
                                <input type="text" class="form-control" name="holder_name"
                                    value="<?= htmlspecialchars($booking['holder_name'] ?? '') ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Adults</label>
                                    <input type="number" class="form-control" name="adults" min="0"
                                        value="<?= $booking['adults'] ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Children</label>
                                    <input type="number" class="form-control" name="children" min="0"
                                        value="<?= $booking['children'] ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Seats (comma separated)</label>
                                <input type="text" class="form-control" name="seats"
                                    value="<?= htmlspecialchars($booking['seats']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Total Price ($)</label>
                                <input type="number" step="0.01" class="form-control" name="total_price"
                                    value="<?= number_format($booking['total_price'], 2) ?>" required>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="discount_applied"
                                    id="discount_applied" value="1" <?= $booking['discount_applied'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="discount_applied">Discount Applied (10%)</label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="confirmed" <?= $booking['status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="pending" <?= $booking['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="cancelled" <?= $booking['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Update Booking</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right column: IMPROVED Related Info -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header-with-toggle" data-target="infoSection" data-default-expanded="true">
                        <h4>Related Information</h4>
                        <i class="bi bi-chevron-right"></i>
                    </div>
                    <div class="toggle-content show" id="infoSection">
                        <div class="info-grid">
                            <!-- User Info - Special card spanning 2 columns -->
                            <div class="info-card user-info-card">
                                <div class="info-icon">
                                    <i class="bi bi-person-circle"></i>
                                </div>
                                <div class="label">Customer</div>
                                <div class="value">
                                    <?= htmlspecialchars($booking['user_name']) ?>
                                    <small><?= htmlspecialchars($booking['user_email']) ?></small>
                                </div>
                            </div>

                            <!-- Movie Info -->
                            <div class="info-card">
                                <div class="info-icon">
                                    <i class="bi bi-film"></i>
                                </div>
                                <div class="label">Movie</div>
                                <div class="value"><?= htmlspecialchars($booking['movie_title'] ?? 'N/A') ?></div>
                            </div>

                            <!-- Showtime Info -->
                            <div class="info-card">
                                <div class="info-icon">
                                    <i class="bi bi-clock"></i>
                                </div>
                                <div class="label">Showtime</div>
                                <div class="value">
                                    <?= htmlspecialchars($booking['show_date'] ?? 'N/A') ?><br>
                                    <small><?= htmlspecialchars($booking['show_time'] ?? 'N/A') ?></small>
                                </div>
                            </div>

                            <!-- Theatre Info -->
                            <div class="info-card">
                                <div class="info-icon">
                                    <i class="bi bi-building"></i>
                                </div>
                                <div class="label">Theatre</div>
                                <div class="value"><?= htmlspecialchars($booking['theatre'] ?? 'N/A') ?></div>
                            </div>

                            <!-- Booking Date -->
                            <div class="info-card">
                                <div class="info-icon">
                                    <i class="bi bi-calendar-check"></i>
                                </div>
                                <div class="label">Booking Date</div>
                                <div class="value"><?= date('M j, Y', strtotime($booking['booking_date'])) ?></div>
                            </div>

                            <!-- Points Earned -->
                            <div class="info-card">
                                <div class="info-icon">
                                    <i class="bi bi-stars"></i>
                                </div>
                                <div class="label">Points Earned</div>
                                <div class="value"><?= $booking['points_earned'] ?></div>
                            </div>
                        </div>

                        <!-- View Ticket Button -->
                        <a href="ticket.php?booking_id=<?= $booking_id ?>" class="ticket-btn" target="_blank">
                            <i class="bi bi-ticket-perforated"></i> View Ticket
                        </a>
                    </div>
                </div>
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
    </script>
</body>
</html>