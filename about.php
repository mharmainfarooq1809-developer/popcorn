<?php
require_once 'db_connect.php';
session_start();
require_once 'settings_init.php'; // load global settings

$public_pages = ['login.php', 'register.php', 'maintenance.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (($settings['maintenance_mode'] ?? '0') === '1' && !in_array($current_page, $public_pages, true)) {
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
        header("Location: /eproject2/maintenance.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us · <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
    <?php if (!empty($settings['theme_color'])): ?>
        <style>
            :root { --primary: <?= htmlspecialchars($settings['theme_color']) ?>; }
            .btn-primary { background: linear-gradient(145deg, var(--primary), var(--primary-dark)); }
        </style>
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;700;800&display=swap" rel="stylesheet">
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
            background: url('images.jfif') center/cover no-repeat;
            z-index: -2;
            transform: translateZ(0);
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
            --popcorn-glow: #FFE68F;
            --dark-gray: #1F2732;
            --gray-1: #3A414D;
            --gray-2: #555C68;
            --gray-3: #7A808A;
            --light-gray: #A7ADB6;
            --very-light-gray: #C9CED6;
            --error: #ff4d4d;
            --success: #4CAF50;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 30px;
            width: 100%;
        }

        /* ================= FLOATING NAVBAR (with mobile burger) ================= */
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

        .btn {
            display: inline-block;
            padding: 10px 28px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.2, 0.9, 0.3, 1);
            border: none;
            cursor: pointer;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
        }

        .btn-primary {
            background: linear-gradient(145deg, var(--popcorn-orange), var(--popcorn-dark));
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
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 165, 0, 0.5);
        }

        .btn-outline {
            background: transparent;
            color: var(--popcorn-gold);
            border: 2px solid var(--popcorn-orange);
            backdrop-filter: blur(4px);
        }

        .btn-outline:hover {
            background-color: var(--popcorn-orange);
            color: white;
            border-color: var(--popcorn-orange);
            transform: translateY(-3px);
        }

        .btn-small {
            padding: 6px 18px;
            font-size: 14px;
            border-radius: 30px;
        }

        /* ================= PAGE HEADER ================= */
        .page-header {
            margin: 120px 0 40px;
            text-align: center;
        }

        .page-header h1 {
            font-size: 56px;
            font-weight: 800;
            color: var(--white);
            margin-bottom: 16px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }

        .page-header p {
            font-size: 20px;
            color: var(--light-gray);
            max-width: 700px;
            margin: 0 auto;
        }

        /* ================= ABOUT SECTIONS ================= */
        .section-title {
            font-size: 36px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 30px;
            position: relative;
            padding-left: 20px;
        }

        .section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 8px;
            bottom: 8px;
            width: 5px;
            background: var(--popcorn-orange);
            border-radius: 4px;
        }

        .mission-vision {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin: 50px 0;
        }

        .card {
            background: rgba(15, 28, 43, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--gray-1);
            border-radius: 24px;
            padding: 40px 30px;
            transition: transform 0.4s, border-color 0.3s;
        }

        .card:hover {
            transform: translateY(-8px);
            border-color: var(--popcorn-orange);
        }

        .card i {
            font-size: 48px;
            color: var(--popcorn-orange);
            margin-bottom: 20px;
        }

        .card h3 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--white);
        }

        .card p {
            color: var(--light-gray);
            font-size: 16px;
        }

        /* Timeline */
        .timeline {
            margin: 60px 0;
            position: relative;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            width: 2px;
            height: 100%;
            background: var(--popcorn-orange);
            transform: translateX(-50%);
        }

        .timeline-item {
            display: flex;
            justify-content: space-between;
            padding: 20px 0;
            position: relative;
        }

        .timeline-item:nth-child(even) {
            flex-direction: row-reverse;
        }

        .timeline-content {
            width: 45%;
            background: rgba(15, 28, 43, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--gray-1);
            border-radius: 20px;
            padding: 25px;
            transition: transform 0.3s;
        }

        .timeline-content:hover {
            transform: scale(1.02);
            border-color: var(--popcorn-orange);
        }

        .timeline-year {
            font-size: 24px;
            font-weight: 800;
            color: var(--popcorn-orange);
            margin-bottom: 10px;
        }

        .timeline-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 10px;
        }

        .timeline-desc {
            color: var(--light-gray);
            font-size: 15px;
        }

        .timeline-dot {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            background: var(--popcorn-orange);
            border: 3px solid var(--popcorn-gold);
            border-radius: 50%;
            z-index: 2;
        }

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin: 50px 0;
        }

        .stat-item {
            background: rgba(15, 28, 43, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--gray-1);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-item:hover {
            transform: translateY(-5px);
            border-color: var(--popcorn-orange);
        }

        .stat-number {
            font-size: 48px;
            font-weight: 800;
            color: var(--popcorn-orange);
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 18px;
            color: var(--light-gray);
        }

        /* Team / Values */
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin: 50px 0;
        }

        .value-card {
            background: rgba(15, 28, 43, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--gray-1);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s;
        }

        .value-card:hover {
            border-color: var(--popcorn-orange);
            transform: translateY(-5px);
        }

        .value-card i {
            font-size: 40px;
            color: var(--popcorn-orange);
            margin-bottom: 15px;
        }

        .value-card h4 {
            font-size: 22px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 12px;
        }

        .value-card p {
            color: var(--light-gray);
            font-size: 15px;
        }

        /* CTA */
        .cta-section {
            background: linear-gradient(135deg, rgba(255, 165, 0, 0.2), rgba(204, 127, 0, 0.2));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
            padding: 60px 40px;
            margin: 80px 0;
            text-align: center;
        }

        .cta-section h2 {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 16px;
            color: var(--white);
        }

        .cta-section p {
            font-size: 18px;
            color: var(--light-gray);
            max-width: 600px;
            margin: 0 auto 30px;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* ================= FEEDBACK SECTION ================= */
        .feedback-section {
            margin: 60px 0;
        }

        .feedback-card {
            background: rgba(15, 28, 43, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--gray-1);
            border-radius: 32px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
        }

        .feedback-card input,
        .feedback-card textarea {
            width: 100%;
            padding: 14px 18px;
            background: var(--deep-navy);
            border: 1px solid var(--gray-1);
            border-radius: 12px;
            color: var(--white);
            font-family: 'Heebo', sans-serif;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .feedback-card input:focus,
        .feedback-card textarea:focus {
            outline: none;
            border-color: var(--popcorn-orange) !important;
            box-shadow: 0 0 0 4px rgba(255, 165, 0, 0.2);
        }

        .feedback-message {
            background: rgba(76, 175, 80, 0.2);
            border-left: 4px solid var(--success);
            padding: 12px 16px;
            border-radius: 8px;
            margin-top: 20px;
            color: var(--success);
            display: none;
        }

        .feedback-error {
            background: rgba(255, 77, 77, 0.2);
            border-left: 4px solid var(--error);
            padding: 12px 16px;
            border-radius: 8px;
            margin-top: 20px;
            color: var(--error);
            display: none;
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
            font-family: 'Heebo', sans-serif;
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

        /* ================= REVEAL ANIMATION ================= */
        .reveal {
            opacity: 0;
            transform: translateY(60px);
            transition: all 1s ease;
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* ================= PAGE FADE ================= */
        body.fade-out {
            opacity: 0;
            transition: opacity 0.6s ease;
        }

        /* ================= TESTIMONIALS SECTION ================= */
        .testimonials-section {
            margin: 80px 0;
        }

        .testimonials-container {
            width: 100%;
            overflow: hidden;
            padding: 20px 0;
            position: relative;
        }

        .testimonials-row {
            margin-bottom: 20px;
            overflow: hidden;
        }

        .testimonials-track {
            display: flex;
            gap: 20px;
            width: max-content;
            animation: scrollLeft 30s linear infinite;
        }

        .testimonials-row.right .testimonials-track {
            animation: scrollRight 30s linear infinite;
        }

        @keyframes scrollLeft {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }

        @keyframes scrollRight {
            0% {
                transform: translateX(-50%);
            }

            100% {
                transform: translateX(0);
            }
        }

        .testimonial-card {
            background: rgba(15, 28, 43, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--gray-1);
            border-radius: 20px;
            padding: 20px;
            min-width: 300px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
            border-color: var(--popcorn-orange);
            box-shadow: 0 15px 30px rgba(255, 165, 0, 0.3);
        }

        .testimonial-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--popcorn-orange);
            box-shadow: 0 0 15px rgba(255, 165, 0, 0.3);
        }

        .testimonial-content h4 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
            color: var(--white);
        }

        .testimonial-content p {
            font-size: 14px;
            color: var(--light-gray);
            margin: 0;
            line-height: 1.4;
        }

        /* ================= LOGO IMAGE SIZING ================= */
        .logo img { max-height: 60px; width: auto; display: block; }
        @media (max-width: 768px) { .logo img { max-height: 45px; } }

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

            .timeline::before {
                left: 30px;
            }

            .timeline-item {
                flex-direction: column !important;
                padding-left: 60px;
            }

            .timeline-content {
                width: 100%;
            }

            .timeline-dot {
                left: 30px;
            }

            .page-header h1 {
                font-size: 36px;
            }

            .page-header p {
                font-size: 16px;
            }
        }
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
            <a href="about.php" class="active">About</a>
            <a href="user_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <!-- Page Header -->
        <div class="page-header reveal">
            <h1>About Popcorn Hub</h1>
            <p>Redefining the movie experience since 2010 – premium comfort, cutting-edge technology, and unforgettable
                memories.</p>
        </div>

        <!-- Mission & Vision Cards -->
        <div class="mission-vision">
            <div class="card reveal">
                <i class="fas fa-rocket"></i>
                <h3>Our Mission</h3>
                <p>To transport audiences to new worlds through unparalleled cinematic experiences, combining
                    state-of-the-art technology with exceptional comfort and service.</p>
            </div>
            <div class="card reveal">
                <i class="fas fa-eye"></i>
                <h3>Our Vision</h3>
                <p>To be the most loved and innovative cinema chain, where every visit feels like a premiere and every
                    guest leaves with a story to tell.</p>
            </div>
        </div>

        <!-- Timeline / History -->
        <h2 class="section-title reveal">Our Journey</h2>
        <div class="timeline">
            <div class="timeline-item reveal">
                <div class="timeline-content">
                    <div class="timeline-year">2010</div>
                    <div class="timeline-title">First Popcorn Hub Cinema</div>
                    <div class="timeline-desc">Opened our flagship theatre in Bashundhara City, Dhaka, with 5 screens
                        and 800 seats.</div>
                </div>
                <div class="timeline-dot"></div>
            </div>
            <div class="timeline-item reveal">
                <div class="timeline-content">
                    <div class="timeline-year">2015</div>
                    <div class="timeline-title">IMAX & 3D Expansion</div>
                    <div class="timeline-desc">Introduced the first IMAX screen in the country and expanded to 3 new
                        locations.</div>
                </div>
                <div class="timeline-dot"></div>
            </div>
            <div class="timeline-item reveal">
                <div class="timeline-content">
                    <div class="timeline-year">2020</div>
                    <div class="timeline-title">Digital Transformation</div>
                    <div class="timeline-desc">Launched our online booking platform and mobile app, serving over 500,000
                        users.</div>
                </div>
                <div class="timeline-dot"></div>
            </div>
            <div class="timeline-item reveal">
                <div class="timeline-content">
                    <div class="timeline-year">2024</div>
                    <div class="timeline-title">Premium VIP Lounges</div>
                    <div class="timeline-desc">Introduced luxury Box classes with recliners, gourmet food, and personal
                        service.</div>
                </div>
                <div class="timeline-dot"></div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-item reveal">
                <div class="stat-number">15+</div>
                <div class="stat-label">Screens</div>
            </div>
            <div class="stat-item reveal">
                <div class="stat-number">4,200</div>
                <div class="stat-label">Seats</div>
            </div>
            <div class="stat-item reveal">
                <div class="stat-number">6</div>
                <div class="stat-label">Locations</div>
            </div>
            <div class="stat-item reveal">
                <div class="stat-number">2M+</div>
                <div class="stat-label">Annual Visitors</div>
            </div>
        </div>

        <!-- Core Values -->
        <h2 class="section-title reveal">Our Values</h2>
        <div class="values-grid">
            <div class="value-card reveal">
                <i class="fas fa-heart"></i>
                <h4>Passion</h4>
                <p>We love movies and it shows in every detail of your visit.</p>
            </div>
            <div class="value-card reveal">
                <i class="fas fa-star"></i>
                <h4>Excellence</h4>
                <p>From picture quality to customer service, we never compromise.</p>
            </div>
            <div class="value-card reveal">
                <i class="fas fa-users"></i>
                <h4>Community</h4>
                <p>Creating shared experiences that bring people together.</p>
            </div>
            <div class="value-card reveal">
                <i class="fas fa-leaf"></i>
                <h4>Sustainability</h4>
                <p>Eco-friendly practices and responsible operations.</p>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="cta-section reveal">
            <h2>Experience the Difference</h2>
            <p>Join us for your next movie adventure. Book your tickets now and enjoy premium comfort.</p>
            <div class="cta-buttons">
                <a href="showtimes.php" class="btn btn-primary">View Showtimes</a>
                <a href="contact.php" class="btn btn-outline">Contact Us</a>
            </div>
        </div>

        <!-- ================= FEEDBACK SECTION ================= -->
        <div class="feedback-section reveal">
            <div class="feedback-card">
                <h2 class="section-title" style="padding-left: 0; margin-bottom: 30px;">We Value Your Feedback</h2>
                <p style="color: var(--light-gray); margin-bottom: 30px; font-size: 16px;">Have a suggestion or want to
                    recommend a movie? Let us know!</p>
                <form id="feedbackForm">
                    <div class="form-row"
                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label>Your Name</label>
                            <input type="text" id="feedbackName" placeholder="John Doe" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" id="feedbackEmail" placeholder="you@example.com" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 25px;">
                        <label>Your Message</label>
                        <textarea id="feedbackMessage" rows="5" placeholder="Tell us what you think..."
                            required></textarea>
                    </div>
                    <div style="text-align: center;">
                        <button type="button" class="btn btn-primary" onclick="submitFeedback()"
                            style="min-width: 200px;">Send Feedback</button>
                    </div>
                </form>
                <div id="feedback-success" class="feedback-message">
                    Thank you for your feedback! We appreciate your input.
                </div>
                <div id="feedback-error" class="feedback-error">
                    There was an error. Please try again.
                </div>
            </div>
        </div>
    </main>

    <!-- ================= TESTIMONIALS SECTION ================= -->
    <div class="testimonials-section">
        <h2 class="section-title reveal" style="padding-left: 0; text-align: center;">What Our Visitors Say</h2>
        <div class="testimonials-container">
            <!-- First row (scrolls left) -->
            <div class="testimonials-row left">
                <div class="testimonials-track">
                    <!-- Cards (duplicated for seamless loop) -->
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="User"
                            class="testimonial-avatar">
                        <div class="testimonial-content">
                            <h4>Amelia Clarke</h4>
                            <p>“The IMAX experience was out of this world!”</p>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="User">
                        <div class="testimonial-content">
                            <h4>James Wilson</h4>
                            <p>“Best sound system I've ever heard.”</p>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="User">
                        <div class="testimonial-content">
                            <h4>Sophia Martinez</h4>
                            <p>“Luxury seating and amazing service.”</p>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/men/75.jpg" alt="User">
                        <div class="testimonial-content">
                            <h4>Oliver Brown</h4>
                            <p>“Perfect for family movie nights.”</p>
                        </div>
                    </div>
                    <!-- Duplicates for loop -->
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="User">
                        <div class="testimonial-content">
                            <h4>Amelia Clarke</h4>
                            <p>“The IMAX experience was out of this world!”</p>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="User">
                        <div class="testimonial-content">
                            <h4>James Wilson</h4>
                            <p>“Best sound system I've ever heard.”</p>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="User">
                        <div class="testimonial-content">
                            <h4>Sophia Martinez</h4>
                            <p>“Luxury seating and amazing service.”</p>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/men/75.jpg" alt="User">
                        <div class="testimonial-content">
                            <h4>Oliver Brown</h4>
                            <p>“Perfect for family movie nights.”</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second row (scrolls right) -->
            <div class="testimonials-row right">
                <div class="testimonials-track">
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/men/22.jpg" alt="User">
                        <div class="testimonial-content">
                            <h4>Ethan Harris</h4>
                            <p>“Crystal clear 3D – felt like I was in the movie.”</p>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/women/53.jpg" alt="User">
                        <div class="testimonial-content">
                            <h4>Charlotte King</h4>
                            <p>“The staff was incredibly friendly and helpful.”</p>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/men/47.jpg" alt="User">
                        <div class="testimonial-content">
                            <h4>Liam Scott</h4>
                            <p>“Great value for money, especially the VIP lounge.”</p>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/women/62.jpg" alt="User">
                        <div class="testimonial-content">
                            <h4>Isabella Lee</h4>
                            <p>“I'll definitely come back for more.”</p>
                        </div>
                    </div>
                    <!-- Duplicates -->
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/men/22.jpg" alt="User">
                        <div class="testimonial-content">
                            <h4>Ethan Harris</h4>
                            <p>“Crystal clear 3D – felt like I was in the movie.”</p>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/women/53.jpg" alt="User">
                        <div class="testimonial-content">
                            <h4>Charlotte King</h4>
                            <p>“The staff was incredibly friendly and helpful.”</p>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/men/47.jpg" alt="User">
                        <div class="testimonial-content">
                            <h4>Liam Scott</h4>
                            <p>“Great value for money, especially the VIP lounge.”</p>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <img src="https://randomuser.me/api/portraits/women/62.jpg" alt="User">
                        <div class="testimonial-content">
                            <h4>Isabella Lee</h4>
                            <p>“I'll definitely come back for more.”</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                                <p><i class="fas fa-envelope"></i> <a href="mailto:<?= htmlspecialchars($settings['contact_email']) ?>"><?= htmlspecialchars($settings['contact_email']) ?></a></p>
                            <?php endif; ?>
                            <?php if (!empty($settings['contact_phone'])): ?>
                                <p><i class="fas fa-phone-alt"></i> <a href="tel:<?= htmlspecialchars($settings['contact_phone']) ?>"><?= htmlspecialchars($settings['contact_phone']) ?></a></p>
                            <?php endif; ?>
                            <?php if (!empty($settings['address'])): ?>
                                <p><i class="fas fa-map-marker-alt"></i> <?= nl2br(htmlspecialchars($settings['address'])) ?></p>
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
                    <div class="newsletter">
                        <input type="email" placeholder="Your email address">
                        <button class="btn btn-primary btn-small">Subscribe</button>
                    </div>
                    <div class="social-links">
                        <?php if (!empty($settings['facebook_url'])): ?>
                            <a href="<?= htmlspecialchars($settings['facebook_url']) ?>" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($settings['twitter_url'])): ?>
                            <a href="<?= htmlspecialchars($settings['twitter_url']) ?>" target="_blank" rel="noopener"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($settings['instagram_url'])): ?>
                            <a href="<?= htmlspecialchars($settings['instagram_url']) ?>" target="_blank" rel="noopener"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <?= htmlspecialchars($settings['footer_text'] ?? '&copy; '.date('Y').' '.($settings['site_name'] ?? 'Popcorn Hub').'. All rights reserved.') ?>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.27/bundled/lenis.min.js"></script>
    <script>
        // Lenis smooth scroll
        const lenis = new Lenis({
            duration: 1.6,
            smooth: true,
            smoothTouch: true
        });

        function raf(time) {
            lenis.raf(time);
            requestAnimationFrame(raf);
        }
        requestAnimationFrame(raf);

        // Scroll reveal and navbar effect
        const reveals = document.querySelectorAll('.reveal');
        const navbar = document.getElementById('navbar');

        window.addEventListener('scroll', () => {
            // Reveal elements
            reveals.forEach(el => {
                const windowHeight = window.innerHeight;
                const elementTop = el.getBoundingClientRect().top;
                if (elementTop < windowHeight - 100) {
                    el.classList.add('active');
                }
            });

            // Navbar background on scroll
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
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

        // Feedback submission (AJAX)
        function submitFeedback() {
            const name = document.getElementById('feedbackName').value.trim();
            const email = document.getElementById('feedbackEmail').value.trim();
            const message = document.getElementById('feedbackMessage').value.trim();
            const successDiv = document.getElementById('feedback-success');
            const errorDiv = document.getElementById('feedback-error');

            // Simple client-side validation
            if (!name || !email || !message) {
                errorDiv.innerText = 'Please fill in all fields.';
                errorDiv.style.display = 'block';
                successDiv.style.display = 'none';
                setTimeout(() => { errorDiv.style.display = 'none'; }, 5000);
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                errorDiv.innerText = 'Please enter a valid email address.';
                errorDiv.style.display = 'block';
                successDiv.style.display = 'none';
                setTimeout(() => { errorDiv.style.display = 'none'; }, 5000);
                return;
            }

            // Send data to server
            fetch('submit_feedback.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, email, message })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        successDiv.style.display = 'block';
                        errorDiv.style.display = 'none';
                        document.getElementById('feedbackName').value = '';
                        document.getElementById('feedbackEmail').value = '';
                        document.getElementById('feedbackMessage').value = '';
                        setTimeout(() => { successDiv.style.display = 'none'; }, 5000);
                    } else {
                        let errorMsg = data.errors ? data.errors.join(', ') : (data.error || 'Unknown error');
                        errorDiv.innerText = errorMsg;
                        errorDiv.style.display = 'block';
                        successDiv.style.display = 'none';
                        setTimeout(() => { errorDiv.style.display = 'none'; }, 5000);
                    }
                })
                .catch(error => {
                    errorDiv.innerText = 'Network error. Please try again.';
                    errorDiv.style.display = 'block';
                    successDiv.style.display = 'none';
                    setTimeout(() => { errorDiv.style.display = 'none'; }, 5000);
                });
        }
    </script>
</body>
</html>
