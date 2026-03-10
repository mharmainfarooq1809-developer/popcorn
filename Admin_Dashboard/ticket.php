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

$booking_id = intval($_GET['booking_id'] ?? 0);
if (!$booking_id) {
    header("Location: bookings.php");
    exit;
}

// Fetch booking details with correct joins
$stmt = $conn->prepare("
    SELECT 
        b.*, 
        u.name AS user_name,
        u.email AS user_email,
        m.title AS movie,
        s.show_date,
        s.show_time,
        s.theatre
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    WHERE b.id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    die("Booking not found.");
}

$status = $booking['status']; // e.g., 'confirmed', 'pending', 'cancelled'
$isConfirmed = ($status === 'confirmed');

// Prepare data for display (still needed for info)
$movie = htmlspecialchars($booking['movie']);
$holderName = htmlspecialchars($booking['holder_name'] ?? $booking['user_name']);
$seats = htmlspecialchars($booking['seats']);
$adults = $booking['adults'];
$children = $booking['children'];
$total = number_format($booking['total_price'], 2);
$date = htmlspecialchars($booking['show_date']);
$time = htmlspecialchars($booking['show_time']);
$theatre = htmlspecialchars($booking['theatre'] ?? 'Unknown Theatre');
$cinema = htmlspecialchars($booking['theatre'] ?? 'Bashundhara Shopping Mall, Panthapath');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket · <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
    <?php if (!empty($settings['theme_color'])): ?>
        <style>
            :root { --primary: <?= htmlspecialchars($settings['theme_color']) ?>; }
            .btn-primary { background: linear-gradient(145deg, var(--primary), var(--primary-dark)); }
        </style>
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?php if ($isConfirmed): ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
    <?php endif; ?>
    <style>
        /* ========== FULL ADMIN TICKET CSS ========== */
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

        /* ===== SIDEBAR ===== */
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

        /* Submenu (optional) */
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

        .nav-icons {
            display: flex;
            align-items: center;
            gap: 20px;
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

        /* ===== TICKET CARD ===== */
        .ticket-container {
            margin: 20px 0 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .ticket-card {
            background: var(--light-card);
            border-radius: 32px;
            padding: 30px 40px;
            max-width: 1000px;
            width: 100%;
            border: 1px solid var(--border-light);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: row;
            gap: 30px;
            align-items: center;
        }

        .dark-mode .ticket-card {
            background: var(--dark-card);
            border-color: var(--border-dark);
        }

        .ticket-left {
            flex: 2;
            border-right: 2px dashed var(--border-light);
            padding-right: 30px;
        }

        .dark-mode .ticket-left {
            border-right-color: var(--border-dark);
        }

        .ticket-right {
            flex: 1;
            text-align: center;
        }

        .ticket-header h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 4px;
        }

        .ticket-header p {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .dark-mode .ticket-header p {
            color: #adb5bd;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 12px 16px;
            margin: 20px 0;
        }

        .info-label {
            color: #6c757d;
            font-weight: 500;
            font-size: 15px;
        }

        .dark-mode .info-label {
            color: #adb5bd;
        }

        .info-value {
            color: var(--light-text);
            font-weight: 700;
            font-size: 15px;
        }

        .dark-mode .info-value {
            color: var(--dark-text);
        }

        .qr-section canvas {
            width: 180px;
            height: 180px;
            border-radius: 16px;
            background: white;
            padding: 8px;
            margin-bottom: 10px;
        }

        .qr-section p {
            font-size: 13px;
            color: #6c757d;
        }

        .ticket-footer {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }

        .btn-primary {
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            color: #fff;
            border: none;
            border-radius: 40px;
            padding: 10px 24px;
            box-shadow: 0 4px 14px rgba(255, 165, 0, 0.3);
            transition: var(--transition);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 165, 0, 0.5);
        }

        .btn-outline-secondary {
            border-radius: 40px;
            padding: 10px 24px;
            border: 1px solid var(--border-light);
            color: var(--light-text);
            background: transparent;
            transition: var(--transition);
        }

        .dark-mode .btn-outline-secondary {
            border-color: var(--border-dark);
            color: var(--dark-text);
        }

        .btn-outline-secondary:hover {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        /* ===== STATUS MESSAGE ===== */
        .status-message {
            text-align: center;
            padding: 60px 20px;
            background: var(--light-card);
            border-radius: 32px;
            border: 1px solid var(--border-light);
            max-width: 600px;
            margin: 40px auto;
        }

        .dark-mode .status-message {
            background: var(--dark-card);
            border-color: var(--border-dark);
        }

        .status-message i {
            font-size: 5rem;
            color: #dc3545;
            margin-bottom: 20px;
        }

        .status-message h3 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .status-message p {
            font-size: 18px;
            color: #6c757d;
            margin-bottom: 30px;
        }

        .dark-mode .status-message p {
            color: #adb5bd;
        }

        /* ===== PRINT STYLES ===== */
        @media print {
            body,
            body.dark-mode {
                background: white;
                color: black;
            }

            .sidebar,
            .top-navbar,
            .ticket-footer,
            footer {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 0;
            }

            .ticket-card {
                background: white;
                border: 2px solid #333;
                box-shadow: none;
                page-break-inside: avoid;
                break-inside: avoid;
                margin: 0 auto;
                border-radius: 16px;
            }

            .ticket-left {
                border-right: 2px dashed #aaa;
            }

            .info-label {
                color: #555;
            }

            .info-value {
                color: #000;
            }

            .ticket-header h1 {
                color: #FFA500;
            }

            .qr-section canvas {
                border: 1px solid #ccc;
            }

            @page {
                size: landscape;
                margin: 0.5in;
            }
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .top-navbar {
                flex-direction: column;
                align-items: stretch;
            }

            .nav-icons {
                justify-content: flex-end;
            }

            .ticket-card {
                flex-direction: column;
                padding: 14px;
            }

            .ticket-left {
                border-right: none;
                border-bottom: 2px dashed var(--border-light);
                padding-right: 0;
                padding-bottom: 20px;
            }

            .dark-mode .ticket-left {
                border-bottom-color: var(--border-dark);
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
    <?php include 'sidebar.php'; ?>

    <!-- Overlay for mobile sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="main-content">
        <div class="top-navbar">
            <div class="d-flex align-items-center">
                <i class="bi bi-list menu-toggle-mobile me-3" id="mobileMenuToggle"></i>
                <h2 class="mb-0">Ticket #<?= $booking_id ?></h2>
            </div>
            <div class="nav-icons">
                <div class="theme-toggle" id="themeToggle"><i class="bi bi-moon"></i></div>
                <i class="bi bi-person-circle avatar-icon"></i>
            </div>
        </div>

        <?php if ($isConfirmed): ?>
            <div class="ticket-container">
                <div class="ticket-card">
                    <div class="ticket-left">
                        <div class="ticket-header">
                            <h1>🎟️ <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></h1>
                            <p>Admin Ticket · E‑Ticket · Scan for verification</p>
                        </div>
                        <div class="info-grid" id="ticketInfo">
                            <!-- Filled by JavaScript below -->
                        </div>
                    </div>
                    <div class="ticket-right">
                        <div class="qr-section">
                            <div id="qrcode"></div>
                            <p>Scan this QR code at the entrance</p>
                        </div>
                    </div>
                </div>
                <div class="ticket-footer">
                    <a href="bookings.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Bookings</a>
                    <button class="btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print Ticket</button>
                </div>
            </div>
        <?php else: ?>
            <div class="status-message">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <h3>Ticket Unavailable</h3>
                <p>This booking is <strong><?= strtoupper($status) ?></strong>. Tickets are only available for confirmed bookings.</p>
                <a href="bookings.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Bookings</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ===== SIDEBAR TOGGLE =====
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                const sidebar = document.getElementById('sidebar');
                if (sidebar) {
                    sidebar.classList.toggle('collapsed');
                    document.body.classList.toggle('sidebar-collapsed');
                }
            });
        }

        // Mobile menu toggle
        const mobileToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (mobileToggle && sidebar && overlay) {
            mobileToggle.addEventListener('click', function() {
                sidebar.classList.add('active');
                overlay.classList.add('active');
            });
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        }

        // ===== DARK MODE =====
        document.getElementById('themeToggle').addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-moon');
                icon.classList.toggle('bi-sun');
            }
        });

        <?php if ($isConfirmed): ?>
            // ===== TICKET DATA FROM PHP =====
            const movie = <?= json_encode($movie) ?>;
            const name = <?= json_encode($holderName) ?>;
            const seats = <?= json_encode($seats) ?>;
            const total = <?= json_encode('$' . $total) ?>;
            const adults = <?= json_encode($adults) ?>;
            const children = <?= json_encode($children) ?>;
            const date = <?= json_encode($date) ?>;
            const time = <?= json_encode($time) ?>;
            const theatre = <?= json_encode($theatre) ?>;
            const cinema = <?= json_encode($cinema) ?>;

            // Fill ticket info
            document.getElementById('ticketInfo').innerHTML = `
                <span class="info-label">Movie</span><span class="info-value">${movie}</span>
                <span class="info-label">Name</span><span class="info-value">${name}</span>
                <span class="info-label">Seats</span><span class="info-value">${seats}</span>
                <span class="info-label">Date</span><span class="info-value">${date} ${time}</span>
                <span class="info-label">Theatre</span><span class="info-value">${theatre}</span>
                <span class="info-label">Location</span><span class="info-value">${cinema}</span>
                <span class="info-label">Tickets</span><span class="info-value">Adults: ${adults}, Children: ${children}</span>
                <span class="info-label">Total</span><span class="info-value">${total}</span>
            `;

            // Generate QR code with booking summary
            const qrData = `
Booking Confirmation
Movie: ${movie}
Name: ${name}
Seats: ${seats}
Date: ${date} ${time}
Theatre: ${theatre}
Location: ${cinema}
Tickets: Adults ${adults}, Children ${children}
Total: ${total}
            `.trim();

            const qr = qrcode(0, 'M');
            qr.addData(qrData);
            qr.make();
            document.getElementById('qrcode').innerHTML = qr.createImgTag(5, 10);
        <?php endif; ?>
    </script>
</body>
</html>


