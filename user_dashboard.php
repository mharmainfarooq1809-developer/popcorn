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

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user points and name
$stmt = $conn->prepare("SELECT name, email, points FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get points history
$stmt = $conn->prepare("SELECT * FROM points_history WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$history = $stmt->get_result();

// Get recent bookings with full details – using LEFT JOIN to include bookings even if showtime/movie missing
$stmt = $conn->prepare("
    SELECT 
        b.id AS booking_id,
        b.seats,
        b.adults,
        b.children,
        b.total_price,
        b.points_earned,
        b.status,
        b.booking_date,
        COALESCE(m.title, 'Movie unavailable') AS movie_title,
        COALESCE(s.show_date, 'N/A') AS show_date,
        COALESCE(s.show_time, 'N/A') AS show_time,
        COALESCE(s.theatre, 'N/A') AS theatre
    FROM bookings b
    LEFT JOIN showtimes s ON b.showtime_id = s.id
    LEFT JOIN movies m ON s.movie_id = m.id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC
    LIMIT 10
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();

// Get movies the user has voted for – with error handling in case table doesn't exist
try {
    $votes = $conn->prepare("
        SELECT m.title, m.genre, m.year, v.created_at AS voted_at
        FROM movie_votes v
        JOIN movies m ON v.movie_id = m.id
        WHERE v.user_id = ?
        ORDER BY v.created_at DESC
    ");
    $votes->bind_param("i", $user_id);
    $votes->execute();
    $votedMovies = $votes->get_result();
} catch (mysqli_sql_exception $e) {
    // Table likely doesn't exist – treat as empty result
    $votedMovies = $conn->query("SELECT 1 WHERE 1=0"); // empty result set
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard · <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
    <?php if (!empty($settings['theme_color'])): ?>
        <style>
            :root { --primary: <?= htmlspecialchars($settings['theme_color']) ?>; }
            a, .btn-primary { color: var(--primary); }
            .btn-sm { background: var(--primary); }
        </style>
    <?php endif; ?>
    <!-- Google Fonts: Heebo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Font Awesome (for menu icon) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1444703686981-a3abbc4d4fe3') center/cover no-repeat;
            z-index: -2;
            transform: translateZ(0);
            filter: brightness(0.5) blur(4px);
        }

        body::after {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(11, 22, 35, 0.85);
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
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 120px 30px 60px;
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
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        header.scrolled {
            padding: 12px 40px;
            background: rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .logo {
            font-size: 28px;
            font-weight: 800;
            color: var(--popcorn-gold);
            letter-spacing: 2px;
            text-transform: uppercase;
            text-decoration: none;
            transition: color 0.3s;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        .logo:hover {
            color: var(--popcorn-orange);
        }

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

        .nav-links a:hover {
            color: var(--popcorn-orange);
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .nav-links a.active {
            color: var(--popcorn-orange);
        }

        .nav-links a.active::after {
            width: 100%;
        }

        /* ================= CARDS ================= */
        .dashboard-card {
            background: rgba(15, 28, 43, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--gray-1);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s, border-color 0.3s;
        }

        .dashboard-card:hover {
            border-color: var(--popcorn-orange);
            transform: translateY(-2px);
        }

        .card-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--white);
            position: relative;
            padding-left: 15px;
        }

        .card-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 6px;
            bottom: 6px;
            width: 5px;
            background: var(--popcorn-orange);
            border-radius: 4px;
        }

        .points-badge {
            font-size: 48px;
            font-weight: 800;
            color: var(--popcorn-gold);
            display: inline-block;
            margin-left: 15px;
        }

        .progress-bar {
            width: 100%;
            height: 12px;
            background: var(--gray-1);
            border-radius: 20px;
            margin: 15px 0 5px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--popcorn-orange), var(--popcorn-gold));
            border-radius: 20px;
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: var(--white);
        }

        th {
            text-align: left;
            padding: 15px 10px;
            border-bottom: 2px solid var(--gray-1);
            color: var(--light-gray);
            font-weight: 600;
        }

        td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--gray-1);
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .text-success {
            color: #4CAF50;
        }

        .text-danger {
            color: #ff4d4d;
        }

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-confirmed {
            background: rgba(40, 167, 69, 0.2);
            color: #4CAF50;
        }
        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        .status-cancelled {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
        }

        .btn-sm {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            background: var(--popcorn-orange);
            color: #000;
            transition: 0.3s;
        }
        .btn-sm:hover {
            background: var(--popcorn-gold);
            transform: translateY(-2px);
        }

        /* ================= VOTE SECTION ================= */
        .vote-item {
            display: flex;
            align-items: center;
            gap: 15px;
            background: var(--dark-gray);
            padding: 10px 15px;
            border-radius: 12px;
            margin-bottom: 10px;
        }

        .vote-item .movie-title {
            font-weight: 600;
            flex: 1;
        }

        .vote-item .vote-date {
            color: var(--light-gray);
            font-size: 14px;
        }

        /* ================= FOOTER (FULL WIDTH) ================= */
        footer {
            background: var(--deep-navy);
            border-top: 1px solid var(--gray-1);
            padding: 60px 0 30px;
            margin-top: 80px;
            width: 100%;
        }

        .footer-content {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 30px;
            text-align: center;
            color: var(--gray-2);
        }

        .footer-content p {
            font-size: 14px;
        }

        /* ================= RESPONSIVE ================= */
        @media (max-width: 768px) {
            header {
                padding: 12px 20px;
            }

            header.scrolled {
                padding: 8px 20px;
            }

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

            .container {
                padding: 100px 20px 40px;
            }
        }

        /* logo image sizing */
        .logo img { max-height: 60px; width: auto; display: block; }
        @media (max-width: 768px) { .logo img { max-height: 45px; } }
    </style>
</head>

<body>
    <!-- Floating Navbar with Burger Menu -->
    <header id="navbar">
        <a href="first_page.php" class="logo">
            <?php if (!empty($settings['site_logo'])): ?>
                <img src="<?= htmlspecialchars($settings['site_logo']) ?>" alt="<?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?>">
            <?php else: ?>
                <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?>
            <?php endif; ?>
        </a>
        <div class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></div>
        <nav class="nav-links" id="navLinks">
            <a href="first_page.php">Home</a>
            <a href="showtimes.php">Showtimes</a>
            <a href="theatre.php">Theatres</a>
            <a href="booking.php">Booking</a>
            <a href="about.php">About</a>
            <a href="user_dashboard.php" class="active">Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <!-- Welcome Header -->
        <div style="margin-bottom: 30px;">
            <h1 style="font-size: 42px; font-weight: 800;">Welcome back, <?= htmlspecialchars($user['name']) ?>!</h1>
            <p style="color: var(--light-gray);"><?= htmlspecialchars($user['email']) ?> · Your loyalty dashboard</p>
        </div>

        <!-- Points Card -->
        <div class="dashboard-card">
            <h2 class="card-title">Your Points</h2>
            <div style="display: flex; align-items: baseline; gap: 15px; flex-wrap: wrap;">
                <span class="points-badge"><?= $user['points'] ?></span>
                <?php if ($user['points'] >= 10): ?>
                    <span
                        style="background: rgba(255,165,0,0.2); border-left: 4px solid var(--popcorn-orange); padding: 10px 16px; border-radius: 8px; color: var(--popcorn-gold);">
                        <i class="bi bi-gift"></i> You have 10 points! 10% off your next booking.
                    </span>
                <?php else: ?>
                    <span style="color: var(--light-gray);"><?= (10 - ($user['points'] % 10)) ?> more points until next
                        discount.</span>
                <?php endif; ?>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= ($user['points'] % 10) * 10 ?>%;"></div>
            </div>
            <p style="color: var(--light-gray); font-size: 14px; margin-top: 10px;">Every ticket earns 1 point. 10
                points = 10% discount.</p>
        </div>

        <!-- Your Votes Section -->
        <div class="dashboard-card">
            <h2 class="card-title">Your Votes</h2>
            <?php if ($votedMovies->num_rows > 0): ?>
                <div class="vote-list">
                    <?php while ($vote = $votedMovies->fetch_assoc()): ?>
                        <div class="vote-item">
                            <span class="movie-title"><?= htmlspecialchars($vote['title']) ?></span>
                            <span class="movie-meta"><?= $vote['genre'] ?> • <?= $vote['year'] ?></span>
                            <span class="vote-date"><?= date('M j, Y', strtotime($vote['voted_at'])) ?></span>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="color: var(--light-gray);">You haven't voted for any movies yet. <a href="first_page.php#voteCard"
                        style="color: var(--popcorn-gold);">Cast your vote now!</a></p>
            <?php endif; ?>
        </div>

        <!-- Points History -->
        <div class="dashboard-card">
            <h2 class="card-title">Points History</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Change</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $history->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
                                <td class="<?= $row['points_change'] > 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= $row['points_change'] > 0 ? '+' : '' ?><?= $row['points_change'] ?>
                                </td>
                                <td><?= htmlspecialchars($row['reason']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if ($history->num_rows == 0): ?>
                            <tr>
                                <td colspan="3" style="text-align:center; color: var(--light-gray);">No points history yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="dashboard-card">
            <h2 class="card-title">Recent Bookings</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Movie</th>
                            <th>Theatre</th>
                            <th>Time</th>
                            <th>Seats</th>
                            <th>Total</th>
                            <th>Points</th>
                            <th>Status</th>
                            <th>Ticket</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($bookings->num_rows > 0): ?>
                            <?php while ($row = $bookings->fetch_assoc()): 
                                $statusClass = '';
                                if ($row['status'] == 'confirmed') $statusClass = 'status-confirmed';
                                elseif ($row['status'] == 'pending') $statusClass = 'status-pending';
                                elseif ($row['status'] == 'cancelled') $statusClass = 'status-cancelled';
                            ?>
                                <tr>
                                    <td><?= date('Y-m-d', strtotime($row['booking_date'])) ?></td>
                                    <td><?= htmlspecialchars($row['movie_title']) ?></td>
                                    <td><?= htmlspecialchars($row['theatre']) ?></td>
                                    <td><?= $row['show_time'] != 'N/A' ? date('g:i A', strtotime($row['show_time'])) : 'N/A' ?></td>
                                    <td><?= htmlspecialchars($row['seats']) ?></td>
                                    <td>$<?= number_format($row['total_price'], 2) ?></td>
                                    <td><?= $row['points_earned'] ?></td>
                                    <td><span class="status-badge <?= $statusClass ?>"><?= ucfirst($row['status']) ?></span></td>
                                    <td>
                                        <a href="ticket.php?booking_id=<?= $row['booking_id'] ?>" class="btn-sm"><i class="bi bi-ticket-perforated"></i> View</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align:center; color: var(--light-gray);">No bookings yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Full-width Footer -->
    <footer>
        <div class="footer-content">
            <p><?= htmlspecialchars($settings['footer_text'] ?? '&copy; '.date('Y').' '.($settings['site_name'] ?? 'Popcorn Hub').'. Your loyalty dashboard.') ?></p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.27/bundled/lenis.min.js"></script>
    <script>
        // Smooth scroll and navbar effect
        const lenis = new Lenis({ duration: 1.6, smooth: true, smoothTouch: true });
        function raf(time) { lenis.raf(time); requestAnimationFrame(raf); }
        requestAnimationFrame(raf);

        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
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

        // Smooth page transitions
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
    <style>
        /* Page transition effect */
        body.fade-out { opacity: 0; transition: opacity 0.6s ease; }
    </style>
</body>
</html>