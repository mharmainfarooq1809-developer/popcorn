<?php
session_start();
require_once 'db_connect.php';
// load settings early so they're available below
require_once 'settings_init.php';
$public_pages = ['login.php', 'register.php', 'maintenance.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (($settings['maintenance_mode'] ?? '0') === '1' && !in_array($current_page, $public_pages, true)) {
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
        header("Location: /eproject2/maintenance.php");
        exit;
    }
}
$theatres_result = $conn->query("SELECT * FROM theatres ORDER BY id ASC");
$theatres = [];
while ($row = $theatres_result->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['facilities'] = json_decode($row['facilities'] ?? '[]', true);
    $theatres[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?> · Theatres</title>
    <?php if (!empty($settings['theme_color'])): ?>
        <style>
            :root { --primary: <?= htmlspecialchars($settings['theme_color']) ?>; }
            header a, .btn-primary { color: var(--primary); }
        </style>
    <?php endif; ?>
    <!-- Google Fonts: Heebo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Font Awesome -->
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

        /* Parallax star background */
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
            --dark-orange: #cc7f00;
            --light-gold: #ffe68f;
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
            -webkit-backdrop-filter: blur(16px);
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
        /* ensure any logo image scales nicely */
        .logo img {
            max-height: 40px;
            width: auto;
            display: block;
        }
        @media (max-width: 768px) {
            .logo img {
                max-height: 30px;
            }
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

        /* ================= HERO SECTION (REDESIGNED) ================= */
        .hero {
            min-height: 50vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 120px 0 80px;
            background: radial-gradient(circle at 30% 50%, var(--deep-navy), var(--dark-navy));
            border-bottom: 1px solid var(--gray-1);
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 165, 0, 0.15) 0%, transparent 70%);
            animation: rotate 25s linear infinite;
            z-index: 0;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .hero .container {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 52px;
            font-weight: 800;
            color: var(--white);
            text-shadow: 0 2px 15px rgba(0, 0, 0, 0.6);
            margin-bottom: 15px;
            letter-spacing: -0.5px;
        }

        .hero-subtitle {
            font-size: 18px;
            color: var(--light-gray);
            max-width: 600px;
            margin: 0 auto 40px;
        }

        /* Hero search bar � sleek and integrated */
        .hero-search {
            max-width: 700px;
            margin: 0 auto;
            background: rgba(15, 28, 43, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 60px;
            padding: 5px 5px 5px 25px;
            display: flex;
            align-items: center;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            transition: box-shadow 0.3s;
        }

        .hero-search:focus-within {
            box-shadow: 0 15px 35px rgba(255, 165, 0, 0.3);
            border-color: var(--popcorn-orange);
        }

        .hero-search input {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--white);
            font-size: 16px;
            padding: 16px 0;
            outline: none;
        }

        .hero-search input::placeholder {
            color: var(--light-gray);
            font-weight: 300;
        }

        .hero-search button {
            background: linear-gradient(145deg, var(--popcorn-orange), var(--dark-orange));
            border: none;
            border-radius: 60px;
            padding: 12px 35px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            white-space: nowrap;
            cursor: pointer;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .hero-search button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 165, 0, 0.6);
        }

        /* ================= FILTER SIDEBAR ================= */
        .filter-box {
            background: rgba(15, 28, 43, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--gray-1);
            border-radius: 24px;
            padding: 25px;
            color: var(--white);
        }

        .filter-box label {
            color: var(--light-gray);
            font-weight: 500;
            margin-bottom: 6px;
        }

        .filter-box .form-select,
        .filter-box .form-check-input {
            background: var(--dark-gray);
            border: 1px solid var(--gray-1);
            color: var(--white);
        }

        .filter-box .form-select:focus,
        .filter-box .form-check-input:focus {
            border-color: var(--popcorn-orange);
            box-shadow: 0 0 0 4px rgba(255, 165, 0, 0.2);
        }

        .filter-box .form-check-input:checked {
            background-color: var(--popcorn-orange);
            border-color: var(--popcorn-orange);
        }

        .filter-box .form-range {
            width: 100%;
        }

        .filter-box .form-range::-webkit-slider-thumb {
            background: var(--popcorn-orange);
        }

        .filter-box .price-value {
            color: var(--popcorn-gold);
            font-weight: 600;
            margin-top: 5px;
            display: inline-block;
        }

        /* ================= THEATRE CARDS ================= */
        .theatre-card {
            background: rgba(15, 28, 43, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--gray-1);
            border-radius: 20px;
            overflow: hidden;
            transition: transform 0.4s, border-color 0.3s, box-shadow 0.4s;
            color: var(--white);
            height: 100%;
        }

        .theatre-card:hover {
            transform: translateY(-8px);
            border-color: var(--popcorn-orange);
            box-shadow: 0 25px 40px rgba(255, 165, 0, 0.4);
        }

        .theatre-card img {
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid var(--gray-1);
        }

        .theatre-card .card-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .theatre-card .card-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 8px;
        }

        .theatre-card .text-muted {
            color: var(--light-gray) !important;
        }

        .theatre-card .btn-primary {
            background: linear-gradient(145deg, var(--popcorn-orange), var(--dark-orange));
            border: none;
            border-radius: 40px;
            padding: 10px;
            font-weight: 600;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .theatre-card .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 165, 0, 0.5);
        }

        /* ================= MODAL ================= */
        .modal-content {
            background: rgba(15, 28, 43, 0.95);
            backdrop-filter: blur(16px);
            border: 1px solid var(--gray-1);
            border-radius: 32px;
            color: var(--white);
        }

        .modal-body img {
            border-radius: 16px;
            border: 1px solid var(--gray-1);
        }

        .modal-body .btn-primary {
            background: linear-gradient(145deg, var(--popcorn-orange), var(--dark-orange));
            border: none;
            border-radius: 40px;
            padding: 12px 28px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .modal-body .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 165, 0, 0.5);
        }

        /* ================= TESTIMONIALS ================= */
        .testimonials-section {
            margin: 80px 0;
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .testimonial-card {
            background: rgba(15, 28, 43, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--gray-1);
            border-radius: 20px;
            padding: 25px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
            border-color: var(--popcorn-orange);
            box-shadow: 0 15px 30px rgba(255, 165, 0, 0.3);
        }

        .testimonial-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--popcorn-orange);
            box-shadow: 0 0 15px rgba(255, 165, 0, 0.3);
            margin-bottom: 15px;
        }

        .testimonial-content h4 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--white);
        }

        .testimonial-content p {
            font-size: 15px;
            color: var(--light-gray);
            margin: 0;
            line-height: 1.6;
            font-style: italic;
        }

        .testimonial-rating {
            margin-top: 12px;
            color: #ffc107;
        }

        /* ================= FOOTER ================= */
        footer {
            background: var(--deep-navy);
            border-top: 1px solid var(--gray-1);
            padding: 60px 0 30px;
            margin-top: 80px;
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

        .newsletter input {
            width: 100%;
            padding: 12px 16px;
            border-radius: 40px;
            border: 1px solid var(--gray-1);
            background: var(--dark-gray);
            color: var(--white);
            margin-bottom: 12px;
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

        /* ================= REVEAL ANIMATION ================= */
        .reveal {
            opacity: 0;
            transform: translateY(60px);
            transition: opacity 1s ease, transform 1s ease;
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* ================= PAGE FADE TRANSITION ================= */
        body.fade-out {
            opacity: 0;
            transition: opacity 0.6s ease;
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

            .hero-title {
                font-size: 36px;
            }

            .hero-subtitle {
                font-size: 16px;
                margin-bottom: 30px;
            }

            .hero-search {
                flex-direction: column;
                background: transparent;
                backdrop-filter: none;
                padding: 0;
                gap: 15px;
            }

            .hero-search input {
                width: 100%;
                background: rgba(15, 28, 43, 0.7);
                backdrop-filter: blur(12px);
                border: 1px solid rgba(255,255,255,0.1);
                border-radius: 60px;
                padding: 15px 25px;
            }

            .hero-search button {
                width: 100%;
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <!-- Floating Navbar with Burger Menu -->
    <header id="navbar">
        <a href="theatre.php" class="logo">
            <?php if (!empty($settings['site_logo'])): ?>
                <img class="logo-img" src="<?= htmlspecialchars($settings['site_logo']) ?>" alt="<?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?>">
            <?php else: ?>
                <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?>
            <?php endif; ?>
        </a>
        <div class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></div>
        <nav class="nav-links" id="navLinks">
            <a href="first_page.php">Home</a>
            <a href="showtimes.php">Showtimes</a>
            <a href="theatre.php" class="active">Theatres</a>
            <a href="booking.php">Booking</a>
            <a href="about.php">About</a>
            <a href="user_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main>
        <!-- Hero Section with integrated search -->
        <section class="hero d-flex align-items-center">
            <div class="container">
                <h1 class="hero-title reveal">Find Your Perfect Theatre</h1>
                <p class="hero-subtitle reveal">Explore premium cinemas, check facilities, and book your favourite movies in the best theatres near you.</p>
                <div class="hero-search reveal">
                    <input type="text" id="searchTheatre" placeholder="Search by theatre name or city...">
                    <button id="searchBtn"><i class="bi bi-search me-2"></i>Search</button>
                </div>
            </div>
        </section>

        <div class="container">
            <!-- Main Content with Filters and Cards -->
            <section class="py-4">
                <div class="row">
                    <!-- Filter Sidebar -->
                    <aside class="col-lg-3 mb-4 reveal">
                        <div class="filter-box">
                            <h5 class="mb-3" style="color: var(--white);">Filters</h5>
                            <label>City</label>
                            <select class="form-select mb-3" id="cityFilter">
                                <option value="all">All Cities</option>
                                <option value="Karachi">Karachi</option>
                                <option value="Islamabad">Islamabad</option>
                                <option value="Lahore">Lahore</option>
                            </select>
                            <label>Rating</label>
                            <select class="form-select mb-3" id="ratingFilter">
                                <option value="0">All Ratings</option>
                                <option value="4">4+</option>
                                <option value="3">3+</option>
                            </select>
                            <label>Facilities</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="imax" value="IMAX">
                                <label class="form-check-label" for="imax">IMAX</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="3d" value="3D">
                                <label class="form-check-label" for="3d">3D</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="dolby" value="Dolby Atmos">
                                <label class="form-check-label" for="dolby">Dolby Atmos</label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="vip" value="VIP Lounge">
                                <label class="form-check-label" for="vip">VIP Lounge</label>
                            </div>
                            <label>Price Range (max $)</label>
                            <input type="range" class="form-range" id="priceRange" min="10" max="30" step="1"
                                value="30">
                            <span class="price-value" id="priceValue">$30</span>
                        </div>
                    </aside>

                    <!-- Theatre Cards -->
                    <div class="col-lg-9">
                        <div class="row g-4" id="theatreContainer"></div>
                    </div>
                </div>
            </section>

            <!-- Testimonials Section -->
            <section class="testimonials-section">
                <div class="container">
                    <h2 class="section-title reveal" style="padding-left: 0; text-align: center;">What Our Visitors Say</h2>
                    <div class="testimonials-grid">
                        <!-- Testimonial 1 -->
                        <div class="testimonial-card reveal">
                            <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Amelia Clarke"
                                class="testimonial-avatar">
                            <div class="testimonial-content">
                                <h4>Amelia Clarke</h4>
                                <p>The IMAX experience was out of this world! Crystal clear picture and immersive sound.</p>
                                <div class="testimonial-rating">
                                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                        class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                        class="bi bi-star-fill"></i>
                                </div>
                            </div>
                        </div>
                        <!-- Testimonial 2 -->
                        <div class="testimonial-card reveal">
                            <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="James Wilson"
                                class="testimonial-avatar">
                            <div class="testimonial-content">
                                <h4>James Wilson</h4>
                                <p>Best sound system I've ever heard. The VIP lounge is pure luxury.</p>
                                <div class="testimonial-rating">
                                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                        class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                        class="bi bi-star-fill"></i>
                                </div>
                            </div>
                        </div>
                        <!-- Testimonial 3 -->
                        <div class="testimonial-card reveal">
                            <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Sophia Martinez"
                                class="testimonial-avatar">
                            <div class="testimonial-content">
                                <h4>Sophia Martinez</h4>
                                <p>Luxury seating and amazing service. Perfect for date nights.</p>
                                <div class="testimonial-rating">
                                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                        class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                        class="bi bi-star-half"></i>
                                </div>
                            </div>
                        </div>
                        <!-- Testimonial 4 -->
                        <div class="testimonial-card reveal">
                            <img src="https://randomuser.me/api/portraits/men/75.jpg" alt="Oliver Brown"
                                class="testimonial-avatar">
                            <div class="testimonial-content">
                                <h4>Oliver Brown</h4>
                                <p>Perfect for family movie nights. Clean, comfortable, and great value.</p>
                                <div class="testimonial-rating">
                                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                        class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                        class="bi bi-star-fill"></i>
                                </div>
                            </div>
                        </div>
                        <!-- Testimonial 5 -->
                        <div class="testimonial-card reveal">
                            <img src="https://randomuser.me/api/portraits/men/22.jpg" alt="Ethan Harris"
                                class="testimonial-avatar">
                            <div class="testimonial-content">
                                <h4>Ethan Harris</h4>
                                <p>Crystal clear 3D – felt like I was in the movie. Highly recommended!</p>
                                <div class="testimonial-rating">
                                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                        class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                        class="bi bi-star-fill"></i>
                                </div>
                            </div>
                        </div>
                        <!-- Testimonial 6 -->
                        <div class="testimonial-card reveal">
                            <img src="https://randomuser.me/api/portraits/women/53.jpg" alt="Charlotte King"
                                class="testimonial-avatar">
                            <div class="testimonial-content">
                                <h4>Charlotte King</h4>
                                <p>The staff was incredibly friendly and helpful. A wonderful experience.</p>
                                <div class="testimonial-rating">
                                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                        class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                        class="bi bi-star-fill"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="theatreModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content p-4" id="modalContent"></div>
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
        // ======= Native Smooth Scroll & Navbar =======
        document.documentElement.style.scrollBehavior = 'smooth';

        const navbar = document.getElementById('navbar');
        function updateNavbarScrolled() {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        }
        updateNavbarScrolled();
        window.addEventListener('scroll', updateNavbarScrolled, { passive: true });

        // ======= Mobile Menu Toggle =======
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

        // ======= Reveal Elements =======
        const reveals = document.querySelectorAll('.reveal');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) entry.target.classList.add('active');
            });
        }, { threshold: 0.2 });

        reveals.forEach(el => observer.observe(el));

        // ======= Smooth Page Transitions =======
        document.querySelectorAll("a").forEach(link => {
            link.addEventListener("click", function (e) {
                if (this.href && this.href.indexOf("#") === -1 && !this.hasAttribute('target') && !this.classList.contains('btn-primary')) {
                    e.preventDefault();
                    document.body.classList.add("fade-out");
                    setTimeout(() => { window.location = this.href; }, 600);
                }
            });
        });

        // ================== DYNAMIC THEATRE DATA ==================
        const theatres = <?php echo json_encode($theatres); ?>;
        console.log('Theatres loaded from PHP:', theatres);

        // Get DOM elements
        const container = document.getElementById('theatreContainer');
        const searchInput = document.getElementById('searchTheatre');
        const searchBtn = document.getElementById('searchBtn');
        const cityFilter = document.getElementById('cityFilter');
        const ratingFilter = document.getElementById('ratingFilter');
        const imaxCheck = document.getElementById('imax');
        const check3d = document.getElementById('3d');
        const dolbyCheck = document.getElementById('dolby');
        const vipCheck = document.getElementById('vip');
        const priceRange = document.getElementById('priceRange');
        const priceValue = document.getElementById('priceValue');
        const modalElement = document.getElementById('theatreModal');

        if (!modalElement) {
            console.error('Modal element with id "theatreModal" not found!');
        }

        // Update price display
        priceRange.addEventListener('input', () => {
            priceValue.textContent = `$${priceRange.value}`;
        });

        // Filter function
        function filterTheatres() {
            const searchTerm = searchInput.value.trim().toLowerCase();
            const selectedCity = cityFilter.value;
            const minRating = parseFloat(ratingFilter.value) || 0;
            const maxPrice = parseInt(priceRange.value, 10);
            const selectedFacilities = [];
            if (imaxCheck.checked) selectedFacilities.push('IMAX');
            if (check3d.checked) selectedFacilities.push('3D');
            if (dolbyCheck.checked) selectedFacilities.push('Dolby Atmos');
            if (vipCheck.checked) selectedFacilities.push('VIP Lounge');

            return theatres.filter(theatre => {
                // Search by name or city
                if (searchTerm) {
                    const nameMatch = theatre.name.toLowerCase().includes(searchTerm);
                    const cityMatch = theatre.city.toLowerCase().includes(searchTerm);
                    if (!nameMatch && !cityMatch) return false;
                }
                if (selectedCity !== 'all' && theatre.city !== selectedCity) return false;
                if (minRating > 0 && theatre.rating < minRating) return false;
                if (theatre.price > maxPrice) return false;
                for (let f of selectedFacilities) {
                    if (!theatre.facilities.includes(f)) return false;
                }
                return true;
            });
        }

        // Render filtered theatres
        function renderFiltered() {
            const filtered = filterTheatres();
            container.innerHTML = '';
            if (filtered.length === 0) {
                container.innerHTML = '<div class="col-12 text-center py-5"><p class="text-light">No theatres match your criteria.</p></div>';
                return;
            }
            filtered.forEach(t => {
                const imageUrl = t.image_url || 'https://via.placeholder.com/300x200?text=No+Image';
                const col = document.createElement('div');
                col.className = 'col-md-6 col-lg-3 reveal';
                col.innerHTML = `
                    <div class="card theatre-card shadow-sm">
                        <img src="${imageUrl}" alt="${t.name}">
                        <div class="card-body">
                            <h5 class="card-title">${t.name}</h5>
                            <p class="text-muted">${t.location}, ${t.city}</p>
                            <p><i class="fas fa-star text-warning"></i> ${t.rating} | $${t.price}</p>
                            <button class="btn btn-primary mt-auto view-details-btn" data-id="${t.id}">View Details</button>
                        </div>
                    </div>
                `;
                container.appendChild(col);
                observer.observe(col);
            });

            // Attach click event to all "View Details" buttons
            document.querySelectorAll('.view-details-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = parseInt(this.getAttribute('data-id'));
                    console.log('View Details clicked for id:', id);
                    showDetails(id);
                });
            });
        }

        renderFiltered();

        searchBtn.addEventListener('click', renderFiltered);
        searchInput.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') renderFiltered();
        });
        [cityFilter, ratingFilter, imaxCheck, check3d, dolbyCheck, vipCheck, priceRange].forEach(el => {
            if (el) el.addEventListener('input', renderFiltered);
        });

        // Show details modal
        function showDetails(id) {
            console.log('showDetails called with id:', id);
            const numericId = Number(id);
            const theatre = theatres.find(x => x.id == numericId);
            if (!theatre) {
                console.error('Theatre not found for id:', id);
                alert('Theatre details not found.');
                return;
            }
            const facilitiesList = theatre.facilities && theatre.facilities.length ? theatre.facilities.join(', ') : 'Standard';
            const imageUrl = theatre.image_url || 'https://via.placeholder.com/600x400?text=No+Image';
            const modalContent = document.getElementById('modalContent');
            if (!modalContent) {
                console.error('Modal content element not found!');
                return;
            }
            modalContent.innerHTML = `
                <h4>${theatre.name}</h4>
                <img src="${imageUrl}" class="img-fluid rounded mb-3" alt="${theatre.name}">
                <p><i class="fas fa-map-pin" style="color: var(--popcorn-orange);"></i> ${theatre.location}, ${theatre.city}</p>
                <p><strong>Facilities:</strong> ${facilitiesList}</p>
                <p><strong>Show Times:</strong> 12PM | 3PM | 6PM | 9PM</p>
                <p><strong>Rating:</strong> <i class="fas fa-star text-warning"></i> ${theatre.rating} | <strong>Price:</strong> $${theatre.price}</p>
                <div class="text-center mt-3">
                    <a href="booking.php" class="btn btn-primary">Book Now</a>
                </div>
            `;
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    </script>
</body>
</html>