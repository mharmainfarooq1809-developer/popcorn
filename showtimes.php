<?php
require_once 'db_connect.php';
session_start();
// load global settings
require_once 'settings_init.php';
$public_pages = ['login.php', 'register.php', 'maintenance.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (($settings['maintenance_mode'] ?? '0') === '1' && !in_array($current_page, $public_pages, true)) {
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
        header("Location: /eproject2/maintenance.php");
        exit;
    }
}

// Fetch all movies from database
$movies = $conn->query("SELECT * FROM movies ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Showtimes - <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
    <?php if (!empty($settings['theme_color'])): ?>
        <style>
            :root {
                --theme-primary: <?= htmlspecialchars($settings['theme_color']) ?>
                ;
            }
        </style>
    <?php endif; ?>
    <!-- Google Fonts: Heebo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Bootstrap 5 (for modal) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* ================= BASE & VARIABLES ================= */
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
            filter: brightness(0.6) blur(4px);
            z-index: -2;
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
            --dark-orange: #cc7f00;
            --light-gold: #ffe68f;
            --dark-gray: #1F2732;
            --gray-1: #3A414D;
            --gray-2: #555C68;
            --gray-3: #7A808A;
            --light-gray: #A7ADB6;
        }

        .container {
            max-width: 1400px;
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

        .logo img {
            max-height: 60px;
            width: auto;
            display: block;
        }

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

        /* ================= SEARCH SECTION ================= */
        .search-section {
            margin: 120px 0 30px;
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            background: rgba(15, 28, 43, 0.7);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            border-radius: 60px;
            border: 1px solid var(--gray-1);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }

        .search-section label {
            font-weight: 700;
            color: var(--light-gray);
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-section label i {
            color: var(--popcorn-orange);
        }

        .search-section .search-wrapper {
            flex: 1;
            position: relative;
            min-width: 280px;
        }

        .search-section input {
            width: 100%;
            padding: 12px 20px 12px 50px;
            border-radius: 40px;
            border: 1px solid var(--gray-1);
            background: var(--dark-gray);
            color: var(--white);
            font-family: 'Heebo', sans-serif;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .search-section input:hover,
        .search-section input:focus {
            border-color: var(--popcorn-orange);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 165, 0, 0.2);
        }

        .search-section input::placeholder {
            color: var(--gray-2);
        }

        .search-section .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-2);
            font-size: 16px;
            pointer-events: none;
        }

        .search-section .clear-search {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-2);
            cursor: pointer;
            font-size: 18px;
            display: none;
            transition: color 0.3s;
        }

        .search-section .clear-search:hover {
            color: var(--popcorn-orange);
        }

        .search-section .search-stats {
            color: var(--light-gray);
            font-size: 14px;
            padding: 0 10px;
        }

        /* ================= CATEGORY FILTERS ================= */
        .category-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 30px 0 20px;
            justify-content: center;
        }

        .category-btn {
            padding: 8px 24px;
            border-radius: 40px;
            background: var(--dark-gray);
            border: 1px solid var(--gray-1);
            color: var(--light-gray);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            letter-spacing: 0.3px;
        }

        .category-btn:hover {
            border-color: var(--popcorn-orange);
            color: var(--white);
            transform: translateY(-2px);
        }

        .category-btn.active {
            background: var(--popcorn-orange);
            border-color: var(--popcorn-orange);
            color: var(--dark-navy);
        }

        /* ================= NO RESULTS MESSAGE ================= */
        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            background: rgba(15, 28, 43, 0.5);
            border-radius: 30px;
            border: 1px solid var(--gray-1);
            backdrop-filter: blur(10px);
        }

        .no-results i {
            font-size: 60px;
            color: var(--gray-2);
            margin-bottom: 20px;
        }

        .no-results h3 {
            font-size: 24px;
            color: var(--white);
            margin-bottom: 10px;
        }

        .no-results p {
            color: var(--light-gray);
            font-size: 16px;
        }

        /* ================= MODERN MOVIE GRID LAYOUT ================= */
        .movies-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin: 40px 0 60px;
        }

        .movie-card {
            position: relative;
            border-radius: 18px;
            overflow: hidden;
            background: var(--deep-navy);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.5);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 430px;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.6s ease forwards;
        }

        .movie-card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .movie-card:nth-child(2) {
            animation-delay: 0.15s;
        }

        .movie-card:nth-child(3) {
            animation-delay: 0.2s;
        }

        .movie-card:nth-child(4) {
            animation-delay: 0.25s;
        }

        .movie-card:nth-child(5) {
            animation-delay: 0.3s;
        }

        .movie-card:nth-child(6) {
            animation-delay: 0.35s;
        }

        .movie-card:nth-child(7) {
            animation-delay: 0.4s;
        }

        .movie-card:nth-child(8) {
            animation-delay: 0.45s;
        }

        .movie-card:nth-child(9) {
            animation-delay: 0.5s;
        }

        .movie-card:nth-child(10) {
            animation-delay: 0.55s;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .movie-card:hover {
            transform: translateY(-12px) scale(1.02);
            border-color: var(--popcorn-orange);
            box-shadow: 0 25px 40px -8px rgba(255, 165, 0, 0.4);
        }

        .movie-poster {
            position: relative;
            overflow: hidden;
            aspect-ratio: 4/3;
            background-color: var(--dark-gray);
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            transition: transform 0.6s ease;
        }

        .movie-card:hover .movie-poster {
            transform: scale(1.08);
        }

        .movie-poster-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to top,
                    rgba(15, 28, 43, 0.98) 0%,
                    rgba(15, 28, 43, 0.6) 40%,
                    transparent 80%);
            opacity: 0;
            transition: opacity 0.4s ease;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 20px;
            z-index: 2;
        }

        .movie-card:hover .movie-poster-overlay {
            opacity: 1;
        }

        .movie-poster-badges {
            position: absolute;
            top: 12px;
            left: 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            z-index: 3;
        }

        .premium-badge {
            background: linear-gradient(145deg, #FFD700, #B8860B);
            color: #000;
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 30px;
            font-weight: 700;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            text-transform: uppercase;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .movie-card-content {
            padding: 18px 16px 20px;
            background: var(--deep-navy);
            flex: 1;
            min-height: 160px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            position: relative;
            z-index: 2;
        }

        .movie-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--white);
            line-height: 1.3;
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .movie-genre {
            font-size: 13px;
            color: var(--popcorn-gold);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .movie-genre i {
            font-size: 12px;
            color: var(--popcorn-orange);
        }

        .movie-language {
            font-size: 13px;
            color: var(--light-gray);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
        }

        .movie-language i {
            font-size: 12px;
            color: var(--popcorn-gold);
        }

        .showtimes-preview {
            display: flex;
            flex-wrap: wrap;
            align-content: flex-start;
            gap: 6px;
            margin: 8px 0 12px;
            min-height: 56px;
            overflow: hidden;
        }

        .showtime-pill {
            background: var(--dark-gray);
            border: 1px solid var(--gray-1);
            border-radius: 30px;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 600;
            color: var(--white);
            transition: all 0.3s ease;
            letter-spacing: 0.3px;
        }

        .showtime-pill.tonight {
            background: var(--popcorn-orange);
            border-color: var(--popcorn-orange);
            color: var(--dark-navy);
            position: relative;
        }

        .movie-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
            opacity: 0.9;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            flex: 1;
            letter-spacing: 0.3px;
        }

        .btn-primary {
            background: linear-gradient(145deg, var(--popcorn-orange), var(--dark-orange));
            color: var(--white);
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 165, 0, 0.5);
        }

        .btn-outline {
            background: transparent;
            color: var(--popcorn-gold);
            border: 2px solid var(--popcorn-orange);
            backdrop-filter: blur(4px);
        }

        .btn-outline:hover {
            background: var(--popcorn-orange);
            color: var(--dark-navy);
            border-color: var(--popcorn-orange);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 165, 0, 0.3);
        }

        .btn i {
            font-size: 12px;
        }

        /* ================= MODAL ================= */
        .modal-content {
            background: rgba(15, 28, 43, 0.95);
            backdrop-filter: blur(16px);
            border: 1px solid var(--gray-1);
            border-radius: 24px;
            color: var(--white);
        }

        .modal-header {
            border-bottom: 1px solid var(--gray-1);
        }

        .modal-body iframe {
            width: 100%;
            height: 400px;
            border-radius: 16px;
            border: none;
        }

        .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        /* ================= FOOTER (FULL WIDTH) ================= */
        footer {
            background: var(--deep-navy);
            border-top: 1px solid var(--gray-1);
            padding: 60px 0 30px;
            margin-top: 80px;
            width: 100%;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-col h4 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--white);
            position: relative;
            padding-bottom: 8px;
        }

        .footer-col h4::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--popcorn-orange);
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col ul li {
            margin-bottom: 12px;
        }

        .footer-col ul li a {
            color: var(--light-gray);
            text-decoration: none;
            transition: color 0.2s, padding-left 0.2s;
        }

        .footer-col ul li a:hover {
            color: var(--popcorn-gold);
            padding-left: 6px;
        }

        .contact-info {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .contact-info h5 {
            color: var(--popcorn-gold);
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            letter-spacing: 0.5px;
        }

        .contact-info p {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
            color: var(--light-gray);
            font-size: 14px;
            line-height: 1.5;
        }

        .contact-info p i {
            color: var(--popcorn-orange);
            font-size: 16px;
            min-width: 20px;
            margin-top: 3px;
        }

        .contact-info p a {
            color: var(--light-gray);
            text-decoration: none;
            transition: color 0.2s;
        }

        .contact-info p a:hover {
            color: var(--popcorn-gold);
        }

        .social-links {
            display: flex;
            gap: 18px;
            margin-top: 20px;
        }

        .social-links a {
            color: var(--light-gray);
            font-size: 22px;
            transition: all 0.3s;
        }

        .social-links a:hover {
            color: var(--popcorn-orange);
            transform: translateY(-3px);
        }

        .newsletter {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .newsletter input {
            width: 100%;
            padding: 12px 16px;
            border-radius: 40px;
            border: 1px solid var(--gray-1);
            background: var(--dark-gray);
            color: var(--white);
        }

        .newsletter button {
            width: 100%;
        }

        .copyright {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid var(--gray-1);
            color: var(--gray-2);
            font-size: 14px;
        }


        /* ================= RESPONSIVE DESIGN ================= */
        @media (max-width: 1200px) {
            .movies-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
            }
        }

        @media (max-width: 992px) {
            .movies-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
        }

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

            .movies-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .search-section {
                flex-direction: column;
                align-items: stretch;
                margin-top: 100px;
            }

            .search-section .search-wrapper {
                min-width: 100%;
            }

            .movie-title {
                font-size: 16px;
            }

            .btn {
                padding: 8px 12px;
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .movies-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .container {
                padding: 0 20px;
            }

            .movie-card {
                max-width: 320px;
                margin: 0 auto;
                width: 100%;
            }
        }
    </style>
    <link rel="stylesheet" href="public_theme.php">
</head>

<body>
    <!-- Floating Navbar with Burger Menu -->
    <header id="navbar">
        <a href="first_page.php" class="logo">
            <?php if (!empty($settings['site_logo'])): ?>
                <img src="<?= htmlspecialchars($settings['site_logo']) ?>"
                    alt="<?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?>">
            <?php else: ?>
                <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?>
            <?php endif; ?>
        </a>
        <div class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></div>
        <nav class="nav-links" id="navLinks">
            <a href="first_page.php">Home</a>
            <a href="showtimes.php" class="active">Showtimes</a>
            <a href="theatre.php">Theatres</a>
            <a href="booking.php">Booking</a>
            <a href="about.php">About</a>
            <a href="user_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <!-- Search Section  -->
        <div class="search-section">
            <label><i class="fas fa-search"></i> Search Movies</label>
            <div class="search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput"
                    placeholder="Type to search by title, genre, category or language...">
                <i class="fas fa-times-circle clear-search" id="clearSearch"></i>
            </div>
            <div class="search-stats" id="searchStats"></div>
        </div>

        <!-- Category filters -->
        <div class="category-filters">
            <button class="category-btn active" data-category="all">All</button>
            <?php
            // Fetch distinct categories from movies table
            $cat_result = $conn->query("SELECT DISTINCT category FROM movies WHERE category IS NOT NULL AND category != '' ORDER BY category");
            while ($cat = $cat_result->fetch_assoc()) {
                echo '<button class="category-btn" data-category="' . htmlspecialchars($cat['category']) . '">' . htmlspecialchars($cat['category']) . '</button>';
            }
            ?>
        </div>

        <!-- Movie grid container -->
        <div class="movies-grid" id="moviesContainer">
            <?php if ($movies->num_rows > 0): ?>
                <?php while ($movie = $movies->fetch_assoc()):
                    $movie_id = $movie['id'];
                    $showtimes = [];

                    // Safely fetch upcoming showtimes
                    try {
                        $stmt = $conn->prepare("SELECT * FROM showtimes WHERE movie_id = ? AND status = 'active' AND (show_date > CURDATE() OR (show_date = CURDATE() AND show_time >= CURTIME())) ORDER BY show_date, show_time LIMIT 3");
                        if ($stmt) {
                            $stmt->bind_param("i", $movie_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            while ($row = $result->fetch_assoc()) {
                                $showtimes[] = $row;
                            }
                        }
                    } catch (Exception $e) {
                        // Table missing - ignore
                    }

                    $currentHour = (int) date('H');
                    $posterUrl = !empty($movie['image_url']) ? $movie['image_url'] : (!empty($movie['poster_url']) ? $movie['poster_url'] : 'https://via.placeholder.com/500x750?text=No+Poster');

                    // Prepare searchable data for JavaScript filtering
                    $searchableData = strtolower(
                        ($movie['title'] ?? '') . ' ' .
                        ($movie['genre'] ?? '') . ' ' .
                        ($movie['category'] ?? '') . ' ' .
                        ($movie['language'] ?? '')
                    );
                    ?>
                    <div class="movie-card" data-category="<?= htmlspecialchars($movie['category'] ?? '') ?>"
                        data-search="<?= htmlspecialchars($searchableData) ?>"
                        data-title="<?= htmlspecialchars($movie['title'] ?? '') ?>">

                        <div class="movie-poster" style="background-image: url(<?= htmlspecialchars($posterUrl) ?>);">
                            <div class="movie-poster-badges">
                                <?php if (!empty($movie['is_premium'])): ?>
                                    <span class="premium-badge">Premium</span>
                                <?php endif; ?>
                                <?php if (!empty($movie['rating'])): ?>
                                    <span class="premium-badge"
                                        style="background: linear-gradient(145deg, #FFA500, #CC7F00);"><?= htmlspecialchars($movie['rating']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="movie-poster-overlay">
                                <div class="movie-actions">
                                    <button class="btn btn-outline"
                                        onclick="openTrailer('<?= htmlspecialchars($movie['trailer_url'] ?? 'https://www.youtube.com/embed/dQw4w9WgXcQ') ?>')">
                                        <i class="fas fa-play"></i> Trailer
                                    </button>
                                    <a href="booking.php?movie_id=<?= $movie['id'] ?>" class="btn btn-primary">
                                        <i class="fas fa-ticket-alt"></i> Book
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="movie-card-content">
                            <h3 class="movie-title"><?= htmlspecialchars($movie['title']) ?></h3>

                            <?php if (!empty($movie['genre'])): ?>
                                <div class="movie-genre">
                                    <i class="fas fa-film"></i> <?= htmlspecialchars($movie['genre']) ?>
                                </div>
                            <?php elseif (!empty($movie['category'])): ?>
                                <div class="movie-genre">
                                    <i class="fas fa-tag"></i> <?= htmlspecialchars($movie['category']) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($movie['language'])): ?>
                                <div class="movie-language">
                                    <i class="fas fa-language"></i> <?= htmlspecialchars($movie['language']) ?>
                                </div>
                            <?php endif; ?>

                            <!-- Showtimes as rounded badges -->
                            <?php if (count($showtimes) > 0): ?>
                                <div class="showtimes-preview">
                                    <?php foreach ($showtimes as $st):
                                        $time = date('g:i A', strtotime($st['show_time']));
                                        $isTonight = ($st['show_date'] == date('Y-m-d') && $currentHour >= 18 && $currentHour < 23);
                                        ?>
                                        <span class="showtime-pill <?= $isTonight ? 'tonight' : '' ?>"><?= $time ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="showtimes-preview">
                                    <span class="showtime-pill" style="opacity: 0.5;">No shows</span>
                                </div>
                            <?php endif; ?>

                            <!-- Mobile/fallback buttons -->
                            <div class="movie-actions d-md-none">
                                <button class="btn btn-outline"
                                    onclick="openTrailer('<?= htmlspecialchars($movie['trailer_url'] ?? 'https://www.youtube.com/embed/dQw4w9WgXcQ') ?>')">
                                    <i class="fas fa-play"></i>
                                </button>
                                <a href="booking.php?movie_id=<?= $movie['id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-ticket-alt"></i> Book
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <p class="text-light-gray">No movies available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Modal for trailers -->
        <div class="modal fade" id="trailerModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Movie Trailer</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <iframe id="trailerIframe" src="" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        </div>
    </main>

     <!-- Footer (full width) -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <!-- Column 1: About & Contact -->
                <div class="footer-col">
                    <h4>Popcorn Hub</h4>
                    <ul>
                        <li><a href="footer_links/abouts.php">About Us</a></li>
                        <li><a href="footer_links/careers.php">Careers</a></li>
                        <li><a href="footer_links/press.php">Press</a></li>
                        <li><a href="footer_links/contact.php">Contact</a></li>
                    </ul>
                    <?php if (!empty($settings['contact_email']) || !empty($settings['contact_phone']) || !empty($settings['address'])): ?>
                        <div class="contact-info">
                            <h5>Get in touch</h5>
                            <?php if (!empty($settings['contact_email'])): ?>
                                <p><i class="fas fa-envelope"></i> <a
                                        href="mailto:<?php echo htmlspecialchars($settings['contact_email']) ?>"><?php echo htmlspecialchars($settings['contact_email']) ?></a>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($settings['contact_phone'])): ?>
                                <p><i class="fas fa-phone-alt"></i> <a
                                        href="tel:<?php echo htmlspecialchars($settings['contact_phone']) ?>"><?php echo htmlspecialchars($settings['contact_phone']) ?></a>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($settings['address'])): ?>
                                <p><i class="fas fa-map-marker-alt"></i>
                                    <?php echo nl2br(htmlspecialchars($settings['address'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Column 2: Movies -->
                <div class="footer-col">
                    <h4>Movies</h4>
                    <ul>
                        <li><a href="footer_links/now%20showing.php">Now Showing</a></li>
                        <li><a href="footer_links/coming_soon.php">Coming Soon</a></li>
                        <li><a href="footer_links/exclusive.php">Exclusive</a></li>
                        <li><a href="footer_links/3d_imax.php">3D/IMAX</a></li>
                    </ul>
                </div>

                <!-- Column 3: Support -->
                <div class="footer-col">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="footer_links/help_center.php">Help Center</a></li>
                        <li><a href="footer_links/terms.php">Terms of Use</a></li>
                        <li><a href="footer_links/privacy.php">Privacy Policy</a></li>
                        <li><a href="footer_links/faq.php">FAQ</a></li>
                    </ul>
                </div>

                <!-- Column 4: Newsletter & Social -->
                <div class="footer-col">
                    <h4>Stay Connected</h4>
                    <div class="social-links">
                        <?php if (!empty($settings['facebook_url'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['facebook_url']) ?>" target="_blank"
                                rel="noopener"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($settings['twitter_url'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['twitter_url']) ?>" target="_blank"
                                rel="noopener"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($settings['instagram_url'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['instagram_url']) ?>" target="_blank"
                                rel="noopener"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <?php echo htmlspecialchars($settings['footer_text'] ?? '&copy; ' . date('Y') . ' ' . ($settings['site_name'] ?? 'Popcorn Hub') . ' Cinemas. All rights reserved.') ?>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
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
            navLinks.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    navLinks.classList.remove('active');
                    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                });
            });
        }

        // ================= SEARCH FUNCTIONALITY =================
        const searchInput = document.getElementById('searchInput');
        const clearSearch = document.getElementById('clearSearch');
        const searchStats = document.getElementById('searchStats');
        const movieCards = document.querySelectorAll('.movie-card');
        const categoryBtns = document.querySelectorAll('.category-btn');

        let activeCategory = 'all';
        let searchTerm = '';

        // Search input handler
        function filterMovies() {
            searchTerm = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            movieCards.forEach(card => {
                const searchData = card.dataset.search || '';
                const category = card.dataset.category || '';

                // Check category match
                const categoryMatch = activeCategory === 'all' || category === activeCategory;

                // Check search term match
                const searchMatch = searchTerm === '' || searchData.includes(searchTerm);

                if (categoryMatch && searchMatch) {
                    card.style.display = 'flex';
                    card.style.animation = 'fadeInUp 0.6s ease forwards';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Update search stats
            if (searchTerm || activeCategory !== 'all') {
                const totalVisible = visibleCount;
                const totalMovies = movieCards.length;
                searchStats.innerHTML = `Found ${totalVisible} movie${totalVisible !== 1 ? 's' : ''}`;

                if (totalVisible === 0) {
                    showNoResults();
                } else {
                    hideNoResults();
                }
            } else {
                searchStats.innerHTML = '';
            }

            // Show/hide clear button
            clearSearch.style.display = searchTerm ? 'block' : 'none';
        }

        // No results message
        function showNoResults() {
            let noResultsDiv = document.querySelector('.no-results');
            if (!noResultsDiv) {
                noResultsDiv = document.createElement('div');
                noResultsDiv.className = 'no-results';
                noResultsDiv.innerHTML = `
                    <i class="fas fa-film"></i>
                    <h3>No Movies Found</h3>
                    <p>Try adjusting your search or category filter</p>
                `;
                document.getElementById('moviesContainer').appendChild(noResultsDiv);
            }
        }

        function hideNoResults() {
            const noResultsDiv = document.querySelector('.no-results');
            if (noResultsDiv) {
                noResultsDiv.remove();
            }
        }

        // Clear search
        clearSearch.addEventListener('click', () => {
            searchInput.value = '';
            filterMovies();
            searchInput.focus();
        });

        // Category filtering
        categoryBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                categoryBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                activeCategory = btn.dataset.category;
                filterMovies();
            });
        });

        // Debounced search input
        let searchTimeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(filterMovies, 300);
        });

        // Trailer modal function
        function openTrailer(url) {
            const iframe = document.getElementById('trailerIframe');

            // Convert YouTube URL to embed format if needed
            if (url.includes('youtube.com/watch?v=')) {
                const videoId = new URL(url).searchParams.get('v');
                url = 'https://www.youtube.com/embed/' + videoId;
            } else if (url.includes('youtu.be/')) {
                const videoId = url.split('youtu.be/')[1].split('?')[0];
                url = 'https://www.youtube.com/embed/' + videoId;
            }

            iframe.src = url;
            const modal = new bootstrap.Modal(document.getElementById('trailerModal'));
            modal.show();
        }

        // Clean up modal iframe when hidden
        const trailerModal = document.getElementById('trailerModal');
        if (trailerModal) {
            trailerModal.addEventListener('hidden.bs.modal', function () {
                const iframe = document.getElementById('trailerIframe');
                iframe.src = '';
            });
        }

        // Smooth page transitions (skip for buttons)
        document.querySelectorAll("a:not(.btn)").forEach(link => {
            link.addEventListener("click", function (e) {
                if (this.href && this.href.indexOf("#") === -1 && !this.hasAttribute('target') && !this.classList.contains('btn')) {
                    e.preventDefault();
                    document.body.classList.add("fade-out");
                    setTimeout(() => { window.location = this.href; }, 600);
                }
            });
        });

        // Initialize
        filterMovies();
    </script>
</body>

</html>