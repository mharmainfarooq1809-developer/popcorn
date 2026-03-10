<?php
session_start();
require_once 'db_connect.php';
require_once 'settings_init.php';
$public_pages = ['login.php', 'register.php', 'maintenance.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (($settings['maintenance_mode'] ?? '0') === '1' && !in_array($current_page, $public_pages, true)) {
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
        header("Location: /eproject2/maintenance.php");
        exit;
    }
}

if (!isset($_GET['booking_id'])) {
    header("Location: login.php");
    exit;
}

$booking_id = intval($_GET['booking_id']);

// Fetch booking details with correct joins, including status
$stmt = $conn->prepare("
    SELECT 
        b.*, 
        u.name AS user_name,
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

if (!$booking) {
    $error = "Booking not found.";
} else {
    $status = $booking['status'];
    $isConfirmed = ($status === 'confirmed');
    if (!$isConfirmed) {
        $error = "This booking is <strong>" . strtoupper($status) . "</strong>. Tickets are only available for confirmed bookings.";
    }
}

// Prepare data for display (only used if confirmed)
// Sanitize each variable to remove any non-printable characters that could break JSON
$movie          = $isConfirmed ? preg_replace('/[^\x20-\x7E]/', '', $booking['movie']) : '';
$holderName     = $isConfirmed ? preg_replace('/[^\x20-\x7E]/', '', $booking['user_name']) : '';
$seats          = $isConfirmed ? preg_replace('/[^\x20-\x7E]/', '', $booking['seats']) : '';
$adults         = $isConfirmed ? $booking['adults'] : 0;
$children       = $isConfirmed ? $booking['children'] : 0;
$total          = $isConfirmed ? number_format($booking['total_price'], 2) : 0;
$date           = $isConfirmed ? $booking['show_date'] : '';
$time           = $isConfirmed ? $booking['show_time'] : '';
$theatre        = $isConfirmed ? preg_replace('/[^\x20-\x7E]/', '', $booking['theatre'] ?? 'Unknown Theatre') : '';
$cinema         = $isConfirmed ? preg_replace('/[^\x20-\x7E]/', '', $booking['theatre'] ?? 'Bashundhara Shopping Mall, Panthapath') : '';
$site_name      = htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub');
$footer_text    = htmlspecialchars($settings['footer_text'] ?? '&copy; '.date('Y').' '.($settings['site_name'] ?? 'Popcorn Hub').'. Your ticket is ready.');
$theme_color    = !empty($settings['theme_color']) ? htmlspecialchars($settings['theme_color']) : '';
$site_logo      = !empty($settings['site_logo']) ? htmlspecialchars($settings['site_logo']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Ticket · <?= $site_name ?></title>
    <?php if ($theme_color): ?>
        <style>
            :root { --primary: <?= $theme_color ?>; }
            .btn, .highlight { color: var(--primary); }
        </style>
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Use a reliable QR library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <style>
        /* ================= BASE & PARALLAX BACKGROUND ================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Heebo', sans-serif;
            background-color: #0B1623;
            color: #F2F2F2;
            line-height: 1.6;
            overflow-x: hidden;
            position: relative;
        }

        /* Parallax background */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?q=80&w=2070&auto=format&fit=crop') center/cover no-repeat;
            filter: brightness(0.5) blur(4px);
            z-index: -2;
        }

        body::after {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(11, 22, 35, 0.75);
            z-index: -1;
        }

        :root {
            --dark-navy: #0B1623;
            --deep-navy: #0F1C2B;
            --white: #F2F2F2;
            --popcorn-gold: #FFD966;
            --popcorn-orange: #FFA500;
            --popcorn-dark: #cc7f00;
            --dark-gray: #1F2732;
            --gray-1: #3A414D;
            --gray-2: #555C68;
            --gray-3: #7A808A;
            --light-gray: #A7ADB6;
            --very-light-gray: #C9CED6;
            --error-bg: rgba(220, 53, 69, 0.1);
            --error-border: #dc3545;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 30px;
            width: 100%;
        }

        /* ================= FLOATING NAVBAR ================= */
        header {
            position: fixed;
            width: 100%;
            top: 0;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            background: rgba(15, 28, 43, 0.3);
            padding: 18px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            transition: all 0.4s ease;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        header.scrolled {
            padding: 12px 40px;
            background: rgba(0,0,0,0.1);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .logo {
            font-size: 28px;
            font-weight: 800;
            color: var(--popcorn-gold);
            letter-spacing: 2px;
            text-transform: uppercase;
            text-decoration: none;
            text-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }

        .logo:hover { color: var(--popcorn-orange); }

        .logo img { max-height: 60px; width: auto; display: block; }

        /* Mobile menu toggle */
        .menu-toggle {
            display: none;
            font-size: 24px;
            color: var(--white);
            cursor: pointer;
        }

        .nav-links {
            display: flex;
            gap: 32px;
            flex-wrap: wrap;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            font-weight: 500;
            color: var(--white);
            transition: all 0.3s ease;
            position: relative;
            padding: 4px 0;
            font-size: 16px;
            letter-spacing: 0.3px;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0%;
            height: 2px;
            background: var(--popcorn-gold);
            transition: width 0.3s;
        }

        .nav-links a:hover { color: var(--popcorn-orange); }
        .nav-links a:hover::after { width: 100%; }

        .btn {
            display: inline-block;
            padding: 10px 28px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(0,0,0,0.3);
        }

        .btn-primary {
            background: linear-gradient(145deg, var(--popcorn-orange), var(--popcorn-dark));
            color: var(--white);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255,165,0,0.5);
        }

        .btn-outline {
            background: transparent;
            color: var(--popcorn-gold);
            border: 2px solid var(--popcorn-orange);
        }

        .btn-outline:hover {
            background: var(--popcorn-orange);
            color: white;
            border-color: var(--popcorn-orange);
            transform: translateY(-3px);
        }

        /* ===== ERROR / STATUS MESSAGE ===== */
        .status-message {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid #dc3545;
            border-radius: 32px;
            padding: 60px 40px;
            text-align: center;
            max-width: 600px;
            margin: 120px auto;
        }
        .status-message i {
            font-size: 5rem;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .status-message h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
        }
        .status-message p {
            font-size: 18px;
            color: var(--light-gray);
            margin-bottom: 30px;
        }

        /* ================= HORIZONTAL TICKET CARD ================= */
        .ticket-container {
            margin: 120px 0 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .ticket-card {
            background: rgba(15, 28, 43, 0.8);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 30px 40px;
            max-width: 1000px;
            width: 100%;
            border: 1px solid var(--gray-1);
            box-shadow: 0 20px 40px rgba(0,0,0,0.6);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: row;
            gap: 30px;
            align-items: center;
        }

        .ticket-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,165,0,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
            z-index: 0;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .ticket-left, .ticket-right {
            position: relative;
            z-index: 2;
        }

        .ticket-left {
            flex: 2;
            border-right: 2px dashed var(--gray-1);
            padding-right: 30px;
        }

        .ticket-right {
            flex: 1;
            text-align: center;
        }

        .ticket-header h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--popcorn-gold);
            margin-bottom: 4px;
        }

        .ticket-header p {
            color: var(--light-gray);
            font-size: 14px;
            margin-bottom: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 12px 16px;
            margin: 20px 0;
        }

        .info-label {
            color: var(--light-gray);
            font-weight: 500;
            font-size: 15px;
        }

        .info-value {
            color: var(--white);
            font-weight: 700;
            font-size: 15px;
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
            color: var(--light-gray);
        }

        .ticket-footer {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }

        footer {
            background: linear-gradient(180deg, var(--deep-navy) 0%, #0a121f 100%);
            border-top: 1px solid var(--gray-1);
            padding: 30px 0;
            text-align: center;
            color: var(--gray-2);
        }

        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s, transform 0.8s;
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        body.fade-out {
            opacity: 0;
            transition: opacity 0.6s ease;
        }

        /* ================= PRINT STYLES ================= */
        @media print {
            body {
                background: white;
                color: black;
            }
            body::before, body::after {
                display: none;
            }
            header, footer, .ticket-footer {
                display: none !important;
            }
            .ticket-card {
                background: white;
                backdrop-filter: none;
                border: 2px solid #333;
                box-shadow: none;
                padding: 20px;
                max-width: 100%;
                page-break-inside: avoid;
                break-inside: avoid;
                margin: 0 auto;
                border-radius: 16px;
                overflow: visible;
            }
            .ticket-card::before {
                display: none;
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
            .ticket-left {
                border-right: 2px dashed #aaa;
            }
            .qr-section {
                page-break-inside: avoid;
                text-align: center;
            }
            .qr-section canvas {
                width: 200px !important;
                height: 200px !important;
                max-width: none;
                background: white;
                border: 1px solid #ccc;
                margin: 0 auto;
            }
            @page {
                size: landscape;
                margin: 0.5in;
            }
        }

        /* ================= RESPONSIVE ================= */
        @media (max-width: 768px) {
            header { padding: 12px 20px; }
            header.scrolled { padding: 8px 20px; }

            .menu-toggle {
                display: block;
            }

            .nav-links {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: rgba(15, 28, 43, 0.95);
                backdrop-filter: blur(12px);
                flex-direction: column;
                align-items: center;
                gap: 15px;
                padding: 20px;
                border-bottom: 1px solid var(--gray-1);
                transform: translateY(-100%);
                opacity: 0;
                visibility: hidden;
                transition: all 0.4s;
                pointer-events: none;
            }

            .nav-links.active {
                transform: translateY(0);
                opacity: 1;
                visibility: visible;
                pointer-events: all;
            }

            .ticket-card {
                flex-direction: column;
                padding: 20px;
            }
            .ticket-left {
                border-right: none;
                border-bottom: 2px dashed var(--gray-1);
                padding-right: 0;
                padding-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Floating Navbar with Burger Menu -->
    <header id="navbar">
        <a href="first_page.php" class="logo">
            <?php if ($site_logo): ?>
                <img src="<?= $site_logo ?>" alt="<?= $site_name ?>">
            <?php else: ?>
                <?= $site_name ?>
            <?php endif; ?>
        </a>
        <div class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></div>
        <nav class="nav-links" id="navLinks">
            <a href="first_page.php">Home</a>
            <a href="showtimes.php">Showtimes</a>
            <a href="theatre.php">Theatres</a>
            <a href="booking.php">Booking</a>
            <a href="about.php">About</a>
            <a href="user_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <?php if (isset($error)): ?>
            <div class="status-message">
                <i class="fas fa-exclamation-triangle"></i>
                <h2>Ticket Unavailable</h2>
                <p><?= $error ?></p>
                <a href="user_dashboard.php" class="btn btn-outline">Go to Dashboard</a>
            </div>
        <?php else: ?>
            <div class="ticket-container reveal" id="ticketContainer">
                <div class="ticket-card">
                    <div class="ticket-left">
                        <div class="ticket-header">
                            <h1><?= $site_name ?></h1>
                            <p>Digital Ticket · E-Ticket · Scan for verification</p>
                        </div>
                        <div class="info-grid" id="ticketInfo">
                            <!-- Filled by JavaScript -->
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
                    <a href="user_dashboard.php" class="btn btn-outline">Back to Dashboard</a>
                    <button class="btn btn-primary" onclick="window.print()">Print Ticket</button>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p><?= $footer_text ?></p>
        </div>
    </footer>

    <script src="https://unpkg.com/@studio-freight/lenis@1.0.27/bundled/lenis.min.js"></script>
    <script>
        // Lenis smooth scroll
        const lenis = new Lenis({ duration: 1.6, smooth: true, smoothTouch: true });
        function raf(time) { lenis.raf(time); requestAnimationFrame(raf); }
        requestAnimationFrame(raf);

        // Navbar effect
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) navbar.classList.add('scrolled');
            else navbar.classList.remove('scrolled');
        });

        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const navLinks = document.getElementById('navLinks');
        if (menuToggle && navLinks) {
            menuToggle.addEventListener('click', () => {
                navLinks.classList.toggle('active');
                menuToggle.innerHTML = navLinks.classList.contains('active') ? '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
            });
            // Close menu when a link is clicked
            navLinks.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    navLinks.classList.remove('active');
                    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                });
            });
        }

        // Reveal animation
        const reveal = document.querySelector('.reveal');
        if (reveal) setTimeout(() => reveal.classList.add('active'), 200);

        <?php if ($isConfirmed): ?>
            // Booking data from PHP – safely encoded with json_encode
            const movie    = <?= json_encode($movie, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const name     = <?= json_encode($holderName, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const seats    = <?= json_encode($seats, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const total    = <?= json_encode('$' . $total, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const adults   = <?= json_encode($adults, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const children = <?= json_encode($children, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const date     = <?= json_encode($date, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const time     = <?= json_encode($time, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const theatre  = <?= json_encode($theatre, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const cinema   = <?= json_encode($cinema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

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

            // Generate QR code after DOM is fully loaded
            document.addEventListener('DOMContentLoaded', function() {
                const qrContainer = document.getElementById('qrcode');
                if (!qrContainer) return;

                // Check if qrcode library is loaded
                if (typeof qrcode === 'undefined') {
                    console.error('QR library not loaded');
                    qrContainer.innerHTML = '<p style="color:red;">QR library failed to load</p>';
                    return;
                }

                try {
                    // Build QR data – use a simple string without backticks to avoid issues
                    const qrData = 
                        'Booking Confirmation\n' +
                        'Movie: ' + movie + '\n' +
                        'Name: ' + name + '\n' +
                        'Seats: ' + seats + '\n' +
                        'Date: ' + date + ' ' + time + '\n' +
                        'Theatre: ' + theatre + '\n' +
                        'Location: ' + cinema + '\n' +
                        'Tickets: Adults ' + adults + ', Children ' + children + '\n' +
                        'Total: ' + total;

                    const qr = qrcode(0, 'M');  // version 0 (auto), error correction M
                    qr.addData(qrData);
                    qr.make();
                    // Create an image tag and insert it
                    qrContainer.innerHTML = qr.createImgTag(5, 10);  // cell size 5, margin 10
                } catch (e) {
                    console.error('QR generation failed:', e);
                    qrContainer.innerHTML = '<p style="color:red;">QR code error</p>';
                }
            });
        <?php endif; ?>

        // Page transitions
        document.querySelectorAll("a").forEach(link => {
            link.addEventListener("click", function (e) {
                if (this.href && this.href.indexOf("#") === -1 && !this.hasAttribute('target')) {
                    e.preventDefault();
                    document.body.classList.add("fade-out");
                    setTimeout(() => { window.location = this.href; }, 600);
                }
            });
        });
    </script>
</body>
</html>