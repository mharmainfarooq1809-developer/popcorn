<?php
require_once 'db_connect.php';
session_start();
// DEBUG - Create a log file
$log = __DIR__ . '/access_log.txt';
$data = date('Y-m-d H:i:s') . " - Page: first_page.php\n";
$data .= "User Role: " . ($_SESSION['user_role'] ?? 'NOT SET') . "\n";
$data .= "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
$data .= "Session: " . print_r($_SESSION, true) . "\n";
$data .= "----------------------------\n\n";
file_put_contents($log, $data, FILE_APPEND);

if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header("Location: Admin_Dashboard/dashboard.php");
    exit;
}
// Helper function to convert hex to rgba (for navbar transparency)
function hex2rgba($hex, $alpha = 1)
{
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
        $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
        $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return "rgba($r,$g,$b,$alpha)";
}

// load global settings (only once)
require_once 'settings_init.php';
$public_pages = ['login.php', 'register.php', 'maintenance.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (($settings['maintenance_mode'] ?? '0') === '1' && !in_array($current_page, $public_pages, true)) {
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
        header("Location: /eproject2/maintenance.php");
        exit;
    }
}

// Fetch all distinct categories that have at least one movie
$categories_result = $conn->query("SELECT DISTINCT category FROM movies WHERE category IS NOT NULL AND category != '' ORDER BY category");
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row['category'];
}
// Fetch upcoming movies (release_date in the future)
$upcoming_query = "SELECT id, title, genre, release_date, image_url AS poster 
                   FROM movies 
                   WHERE release_date > CURDATE() 
                   ORDER BY release_date ASC 
                   LIMIT 4";
$upcoming_result = $conn->query($upcoming_query);
$upcoming_movies = $upcoming_result->fetch_all(MYSQLI_ASSOC);

// Check if there are any premium movies (for conditional display)
$premium_count = $conn->query("SELECT COUNT(*) as cnt FROM movies WHERE is_premium = 1")->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?> � Premium Movie Experience</title>
    <?php if (!empty($settings['theme_color'])): ?>
        <style>
            :root {
                --primary:
                    <?php echo htmlspecialchars($settings['theme_color']) ?>
                ;
                --primary-transparent:
                    <?php echo hex2rgba($settings['theme_color'], 0.3); ?>
                ;
                --primary-solid:
                    <?php echo htmlspecialchars($settings['theme_color']) ?>
                ;
            }

            .btn,
            .btn-primary {
                background-color: var(--primary);
                border-color: var(--primary);
            }
        </style>
    <?php endif; ?>
    <!-- Google Fonts: Heebo + Inter for clean sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;700;800&family=Inter:wght@400;600;800&display=swap"
        rel="stylesheet">
    <!-- Bootstrap 5 CSS (for modal) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 (free) -->
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
            background: url('images.jfif') center/cover no-repeat;
            background-size: cover;
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
            background: rgba(11, 22, 35, 0.75);
            z-index: -1;
        }

        :root {
            --dark-navy: #0B1623;
            --deep-navy: #0F1C2B;
            --white: #F2F2F2;
            --dark-purple: #5B1E8C;
            --primary-purple: #6F2DA8;
            --medium-purple: #8E63B3;
            --light-lavender: #B9A3CC;
            --dark-gray: #1F2732;
            --gray-1: #3A414D;
            --gray-2: #555C68;
            --gray-3: #7A808A;
            --light-gray: #A7ADB6;
            --very-light-gray: #C9CED6;
            --popcorn-gold: #FFD966;
            --popcorn-orange: #FFA500;
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
            /* default transparent dark */
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
            /* default darker transparent */
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        /* When logo exists, use theme color */
        header.has-logo {
            background: var(--primary-transparent, rgba(15, 28, 43, 0.3));
        }

        header.has-logo.scrolled {
            background: var(--primary-solid, rgba(0, 0, 0, 0.1));
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

        .nav-links .btn-small {
            padding: 6px 18px;
            font-size: 14px;
            border-radius: 30px;
            margin-left: 8px;
        }

        /* ================= PREMIUM HERO SLIDER ================= */
        .hero-slider {
            position: relative;
            width: 100%;
            height: 90vh;
            margin-top: 80px;
            overflow: hidden;
            border-radius: 0 0 30px 30px;
        }

        .hero-slider .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1.5s ease-in-out;
            background-size: cover;
            background-position: center;
        }

        .hero-slider .slide.active {
            opacity: 1;
        }

        .hero-slider .slide::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, rgba(0, 0, 0, 0.7) 0%, rgba(0, 0, 0, 0.3) 100%);
            z-index: 1;
        }

        .slide-content {
            position: absolute;
            top: 50%;
            left: 10%;
            transform: translateY(-50%);
            z-index: 2;
            color: white;
            max-width: 600px;
            text-shadow: 0 5px 20px rgba(0, 0, 0, 0.5);
        }

        .slide-content h2 {
            font-size: 56px;
            font-weight: 800;
            margin-bottom: 15px;
            animation: fadeUp 1s ease;
        }

        .slide-content p {
            font-size: 18px;
            margin-bottom: 25px;
            animation: fadeUp 1s 0.2s both;
        }

        .slide-content .btn {
            animation: fadeUp 1s 0.4s both;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-slider .indicators {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2;
            display: flex;
            gap: 10px;
        }

        .hero-slider .indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s;
        }

        .hero-slider .indicator.active {
            background: var(--popcorn-gold);
            transform: scale(1.3);
        }

        /* ================= BUTTONS ================= */
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
            background: linear-gradient(145deg, var(--popcorn-orange), #cc7f00);
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

        /* ================= EXCLUSIVE HERO SECTION (secondary) ================= */
        .hero-exclusive {
            background: radial-gradient(circle at 30% 50%, var(--deep-navy), var(--dark-navy));
            border-radius: 24px;
            padding: 50px;
            margin: 60px 0;
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            align-items: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
            border: 1px solid var(--gray-1);
            position: relative;
            overflow: hidden;
        }

        .hero-exclusive::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 165, 0, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
            z-index: 0;
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .hero-exclusive .hero-content {
            position: relative;
            z-index: 2;
            flex: 1 1 350px;
        }

        .hero-exclusive .hero-poster {
            flex: 0 0 260px;
            height: 340px;
            background: linear-gradient(145deg, var(--gray-1), var(--dark-gray));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-2);
            border: 2px solid var(--popcorn-orange);
            box-shadow: 0 20px 30px -10px black;
            transition: transform 0.4s;
            font-size: 20px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .hero-exclusive .hero-poster:hover {
            transform: scale(1.02) rotate(1deg);
            border-color: var(--popcorn-gold);
        }

        /* ================= CATEGORY FILTERS ================= */
        .category-filters {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin: 30px 0 20px;
        }

        .category-btn {
            padding: 8px 22px;
            border-radius: 40px;
            background: transparent;
            border: 1px solid var(--gray-1);
            color: var(--light-gray);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 15px;
        }

        .category-btn.active {
            background: var(--popcorn-orange);
            color: white;
            border-color: var(--popcorn-orange);
        }

        .category-btn:hover {
            background: var(--dark-gray);
            color: var(--white);
        }

        /* ================= SECTION HEADER ================= */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 50px 0 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .section-header h2 {
            font-size: 32px;
            font-weight: 700;
            color: var(--white);
            position: relative;
            padding-left: 18px;
        }

        .section-header h2::before {
            content: '';
            position: absolute;
            left: 0;
            top: 8px;
            bottom: 8px;
            width: 5px;
            background: var(--popcorn-orange);
            border-radius: 4px;
        }

        .view-all {
            color: var(--popcorn-gold);
            font-weight: 500;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: gap 0.3s;
        }

        .view-all i {
            font-size: 12px;
        }

        .view-all:hover {
            gap: 12px;
            color: var(--popcorn-orange);
        }

        /* ================= HORIZONTAL SCROLL CARDS (for categories) ================= */
        .horizontal-scroll {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding: 10px 0 20px;
            scrollbar-width: thin;
            scrollbar-color: var(--popcorn-orange) var(--gray-1);
            -webkit-overflow-scrolling: touch;
        }

        .horizontal-scroll::-webkit-scrollbar {
            height: 8px;
        }

        .horizontal-scroll::-webkit-scrollbar-track {
            background: var(--gray-1);
            border-radius: 10px;
        }

        .horizontal-scroll::-webkit-scrollbar-thumb {
            background: var(--popcorn-orange);
            border-radius: 10px;
        }

        /* Movie card style (compact for horizontal scroll) */
        .movie-card-horizontal {
            min-width: 200px;
            width: 200px;
            background: rgba(15, 28, 43, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--gray-1);
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.3s, border-color 0.3s;
            display: flex;
            flex-direction: column;
        }

        .movie-card-horizontal:hover {
            transform: translateY(-5px);
            border-color: var(--popcorn-orange);
            box-shadow: 0 15px 30px rgba(255, 165, 0, 0.3);
        }

        .movie-card-horizontal .card-img {
            height: 130px;
            background-size: cover;
            background-position: center;
        }

        .movie-card-horizontal .card-body {
            padding: 12px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .movie-card-horizontal .card-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
            color: var(--white);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .movie-card-horizontal .card-meta {
            font-size: 12px;
            color: var(--light-gray);
            margin-bottom: 8px;
        }

        .movie-card-horizontal .card-buttons {
            display: flex;
            gap: 6px;
            margin-top: auto;
        }

        .movie-card-horizontal .btn {
            padding: 5px 0;
            font-size: 11px;
            border-radius: 30px;
            flex: 1;
        }

        /* Premium card (for premium section) */
        .premium-card {
            min-width: 280px;
            width: 280px;
            background: rgba(15, 28, 43, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--gray-1);
            border-radius: 20px;
            overflow: hidden;
            transition: transform 0.4s, border-color 0.3s;
        }

        .premium-card:hover {
            transform: translateY(-8px);
            border-color: var(--popcorn-orange);
            box-shadow: 0 25px 40px rgba(255, 165, 0, 0.4);
        }

        .premium-card .card-img {
            height: 160px;
            background-size: cover;
            background-position: center;
        }

        .premium-card .card-content {
            padding: 15px;
        }

        .premium-card .card-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--white);
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .premium-badge {
            background: linear-gradient(145deg, #FFD700, #B8860B);
            color: #000;
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 30px;
            font-weight: 600;
        }

        .premium-card .card-meta {
            font-size: 13px;
            color: var(--light-gray);
            margin-bottom: 8px;
        }

        /* View More card */
        .view-more-card {
            min-width: 200px;
            width: 200px;
            background: rgba(31, 39, 50, 0.8);
            backdrop-filter: blur(10px);
            border: 2px dashed var(--popcorn-orange);
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .view-more-card:hover {
            background: rgba(255, 165, 0, 0.2);
            border-color: var(--popcorn-orange);
            transform: scale(1.02);
        }

        .view-more-card i {
            font-size: 32px;
            color: var(--popcorn-orange);
            margin-bottom: 10px;
        }

        .view-more-card span {
            font-weight: 600;
            color: var(--white);
        }

        .view-more-card a {
            text-decoration: none;
            color: inherit;
            display: block;
            width: 100%;
            height: 100%;
        }

        /* ================= COMING SOON ================= */
        .coming-soon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin: 40px 0;
        }

        .coming-card {
            background: rgba(15, 28, 43, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--gray-1);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }

        .coming-card:hover {
            border-color: var(--popcorn-orange);
            transform: scale(1.03);
        }

        .coming-date {
            background: var(--popcorn-orange);
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #000;
        }

        .coming-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .coming-genre {
            color: var(--light-gray);
            font-size: 14px;
            margin-bottom: 20px;
        }

        .btn-notify {
            background: transparent;
            border: 1px solid var(--popcorn-orange);
            color: var(--popcorn-gold);
            padding: 8px 0;
            width: 100%;
            border-radius: 40px;
            font-weight: 500;
            transition: 0.3s;
            cursor: pointer;
        }

        .btn-notify:hover {
            background: var(--popcorn-orange);
            color: white;
            border-color: var(--popcorn-orange);
        }

        /* ================= STREAMING & VOTE ================= */
        .content-row {
            display: flex;
            gap: 30px;
            margin: 60px 0;
            flex-wrap: wrap;
        }

        .main-column {
            flex: 2;
            min-width: 300px;
        }

        .sidebar-column {
            flex: 1;
            min-width: 250px;
        }

        .streaming-grid {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 25px;
        }

        .streaming-card {
            display: flex;
            align-items: center;
            gap: 18px;
            background: var(--deep-navy);
            padding: 16px 20px;
            border-radius: 16px;
            border: 1px solid var(--gray-1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .streaming-card:hover {
            transform: translateX(8px);
            border-color: var(--popcorn-orange);
            box-shadow: 0 8px 20px rgba(255, 165, 0, 0.3);
        }

        .streaming-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: var(--popcorn-orange);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .streaming-card:hover::before {
            opacity: 1;
        }

        .streaming-card-poster {
            width: 60px;
            height: 80px;
            background: linear-gradient(145deg, var(--dark-gray), var(--gray-1));
            border-radius: 10px;
            flex-shrink: 0;
            border: 1px solid var(--gray-1);
            transition: transform 0.3s;
        }

        .streaming-card:hover .streaming-card-poster {
            transform: scale(1.05);
            border-color: var(--popcorn-orange);
        }

        .streaming-card-info {
            flex: 1;
        }

        .streaming-card-info h4 {
            font-size: 18px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .year-badge {
            font-size: 12px;
            font-weight: 500;
            background: var(--popcorn-orange);
            color: #000;
            padding: 2px 8px;
            border-radius: 30px;
        }

        .streaming-meta {
            font-size: 14px;
            color: var(--light-gray);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .streaming-meta i {
            color: var(--popcorn-orange);
            font-size: 12px;
        }

        .streaming-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .tag {
            background: var(--dark-gray);
            padding: 2px 10px;
            border-radius: 30px;
            font-size: 11px;
            color: var(--gray-3);
            border: 1px solid var(--gray-1);
        }

        .streaming-card-action .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: transparent;
            border: 2px solid var(--popcorn-orange);
            color: var(--popcorn-gold);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .streaming-card-action .btn-icon:hover {
            background: var(--popcorn-orange);
            color: white;
            border-color: var(--popcorn-orange);
            transform: scale(1.1);
        }

        .featured-banner {
            background: linear-gradient(145deg, var(--deep-navy), var(--dark-navy));
            border-radius: 20px;
            padding: 24px;
            border: 1px solid var(--gray-1);
            border-left: 6px solid var(--popcorn-orange);
            margin-top: 20px;
        }

        .featured-banner .quote-icon {
            font-size: 24px;
            color: var(--popcorn-orange);
            opacity: 0.5;
            margin-bottom: 10px;
        }

        .featured-banner p {
            font-size: 16px;
            font-weight: 500;
            color: var(--light-gray);
            margin-bottom: 20px;
            font-style: italic;
        }

        .banner-actions {
            display: flex;
            gap: 15px;
        }

        .vote-card {
            background: var(--deep-navy);
            border-radius: 24px;
            padding: 24px;
            border: 1px solid var(--gray-1);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5);
        }

        .vote-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray-1);
        }

        .vote-subtitle {
            font-size: 14px;
            color: var(--light-gray);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .vote-update {
            font-size: 12px;
            background: var(--dark-gray);
            padding: 4px 10px;
            border-radius: 30px;
            color: var(--gray-3);
        }

        .vote-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .vote-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            background: var(--dark-gray);
            border-radius: 12px;
            transition: all 0.3s;
            border: 1px solid transparent;
        }

        .vote-item:hover {
            border-color: var(--popcorn-orange);
            transform: translateX(4px);
            background: rgba(255, 165, 0, 0.1);
        }

        .vote-rank {
            width: 30px;
            height: 30px;
            background: var(--deep-navy);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: var(--popcorn-orange);
            font-size: 14px;
            border: 1px solid var(--gray-1);
        }

        .vote-movie-info {
            flex: 1;
        }

        .vote-movie-title {
            font-weight: 700;
            color: var(--white);
            font-size: 16px;
            margin-bottom: 2px;
        }

        .vote-movie-meta {
            font-size: 11px;
            color: var(--gray-3);
        }

        .vote-action .vote-btn {
            width: 100%;
            padding: 6px 12px;
            border-radius: 30px;
            background: transparent;
            border: 1px solid var(--gray-1);
            color: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }

        .vote-action .vote-btn:hover {
            background: var(--popcorn-orange);
            color: white;
            border-color: var(--popcorn-orange);
        }

        .vote-action .vote-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: var(--gray-1);
            border-color: var(--gray-1);
            color: var(--light-gray);
        }

        .vote-footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid var(--gray-1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .vote-total {
            font-size: 13px;
            color: var(--light-gray);
        }

        /* ================= JOIN BANNER ================= */
        .join-banner {
            background: linear-gradient(135deg, rgba(255, 165, 0, 0.2), rgba(204, 127, 0, 0.2));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
            padding: 60px 40px;
            margin: 60px 0;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
            position: relative;
            overflow: hidden;
        }

        .join-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 165, 0, 0.2) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
            z-index: 0;
        }

        .join-content {
            position: relative;
            z-index: 2;
        }

        .join-banner h2 {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 16px;
            color: var(--white);
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .join-banner p {
            font-size: 18px;
            color: var(--light-gray);
            max-width: 600px;
            margin: 0 auto 30px;
        }

        .benefits {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }

        .benefit-item {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(31, 39, 50, 0.7);
            padding: 12px 24px;
            border-radius: 50px;
            border: 1px solid var(--gray-1);
        }

        .benefit-item i {
            color: var(--popcorn-orange);
            font-size: 24px;
        }

        .benefit-item span {
            font-weight: 600;
            color: var(--white);
        }

        .join-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
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

        /* ================= SCROLL REVEAL ANIMATION ================= */
        .reveal {
            opacity: 0;
            transform: translateY(60px);
            transition: all 1s ease;
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

        /* ================= THEMED LOADER WITH PARTICLES ================= */
        .popcorn-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: #000;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            transition: opacity 0.8s ease;
            pointer-events: none;
        }

        .loader-content {
            text-align: center;
            z-index: 10;
        }

        .loader-logo {
            font-family: 'Inter', 'Heebo', sans-serif;
            font-size: 4rem;
            font-weight: 800;
            color: white;
            letter-spacing: 4px;
            margin-bottom: 20px;
            animation: glowPulse 2s infinite alternate;
        }

        .loader-logo span {
            background: linear-gradient(135deg, var(--popcorn-gold), var(--popcorn-orange));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        @keyframes glowPulse {
            0% {
                text-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
            }

            100% {
                text-shadow: 0 0 30px rgba(255, 165, 0, 0.8);
            }
        }

        .progress-bar {
            width: 240px;
            height: 3px;
            background: #222;
            border-radius: 4px;
            margin: 0 auto;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            width: 0%;
            background: var(--popcorn-orange);
            border-radius: 4px;
            animation: loadProgress 2.5s ease-out forwards;
        }

        @keyframes loadProgress {
            0% {
                width: 0%;
            }

            100% {
                width: 100%;
            }
        }

        /* Glass sweep effect - left to right */
        .glass-sweep {
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg,
                    transparent 0%,
                    rgba(255, 255, 255, 0.1) 30%,
                    rgba(255, 215, 0, 0.15) 50%,
                    rgba(255, 255, 255, 0.1) 70%,
                    transparent 100%);
            transform: skewX(-15deg);
            animation: sweep 3s ease-in-out infinite;
            pointer-events: none;
            z-index: 8;
        }

        @keyframes sweep {
            0% {
                left: -100%;
            }

            100% {
                left: 200%;
            }
        }

        /* Particles layer */
        .particles {
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle at 10% 20%, rgba(255, 215, 0, 0.6) 1px, transparent 1px),
                radial-gradient(circle at 30% 50%, rgba(255, 165, 0, 0.5) 2px, transparent 2px),
                radial-gradient(circle at 70% 30%, rgba(255, 200, 100, 0.5) 1px, transparent 1px),
                radial-gradient(circle at 90% 70%, rgba(255, 140, 0, 0.6) 2px, transparent 2px),
                radial-gradient(circle at 50% 80%, rgba(255, 180, 50, 0.5) 1px, transparent 1px);
            background-size: 200px 200px;
            animation: particleFloat 12s infinite linear;
            pointer-events: none;
            z-index: 5;
        }

        @keyframes particleFloat {
            0% {
                background-position: 0 0, 30px 40px, 60px 80px, 100px 120px, 150px 160px;
                opacity: 0.4;
            }

            100% {
                background-position: 100px 200px, 130px 240px, 160px 280px, 200px 320px, 250px 360px;
                opacity: 0.8;
            }
        }

        .loader-vignette {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            box-shadow: inset 0 0 150px rgba(0, 0, 0, 0.7);
            pointer-events: none;
            z-index: 6;
        }

        /* ================= MOBILE MENU STYLES (ADDED) ================= */
        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }

            .nav-links {
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: rgba(15, 28, 43, 0.95);
                backdrop-filter: blur(12px);
                flex-direction: column;
                align-items: center;
                padding: 20px 0;
                gap: 15px;
                transform: translateY(-150%);
                transition: transform 0.3s ease;
                z-index: 999;
            }

            .nav-links.active {
                transform: translateY(0);
            }

            .nav-links a {
                font-size: 18px;
            }
        }

        @media (max-width: 768px) {
            .loader-logo {
                font-size: 3rem;
                letter-spacing: 2px;
            }

            .progress-bar {
                width: 200px;
            }
        }

        @media (max-width: 480px) {
            .loader-logo {
                font-size: 2.2rem;
            }
        }

        /* logo image sizing */
        .logo img {
            max-height: 60px;
            width: auto;
            display: block;
        }

        @media (max-width: 768px) {
            .logo img {
                max-height: 45px;
            }
        }
    </style>
</head>

<body>
    <!-- Themed loader with particles -->
    <div id="popcornLoader" class="popcorn-loader">
        <div class="particles"></div>
        <div class="glass-sweep"></div>
        <div class="loader-content">
            <div class="loader-logo">POPCORN <span>HUB</span></div>
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
        </div>
        <div class="loader-vignette"></div>
    </div>

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
            <a href="first_page.php" <?= basename($_SERVER['PHP_SELF']) == 'first_page.php' ? 'class="active"' : '' ?>>Home</a>
            <a href="showtimes.php" <?= basename($_SERVER['PHP_SELF']) == 'showtimes.php' ? 'class="active"' : '' ?>>Showtimes</a>
            <a href="theatre.php" <?= basename($_SERVER['PHP_SELF']) == 'theatre.php' ? 'class="active"' : '' ?>>Theatres</a>
            <a href="booking.php" <?= basename($_SERVER['PHP_SELF']) == 'booking.php' ? 'class="active"' : '' ?>>Booking</a>
            <a href="about.php" <?= basename($_SERVER['PHP_SELF']) == 'about.php' ? 'class="active"' : '' ?>>About</a>

            <?php if (isset($_SESSION['user_id'])):
                $user_role = $_SESSION['user_role'] ?? 'user';
                ?>
                <?php if ($user_role === 'admin'): ?>
                    <!-- Admin sees both Dashboard and Admin Panel -->
                    <a href="user_dashboard.php" <?= basename($_SERVER['PHP_SELF']) == 'user_dashboard.php' ? 'class="active"' : '' ?>>My Dashboard</a>
                    <a href="Admin_Dashboard/dashboard.php" class="admin-link">
                        <i class="fas fa-crown"></i> Admin Panel
                    </a>
                <?php else: ?>
                    <!-- Regular users only see their dashboard -->
                    <a href="user_dashboard.php" <?= basename($_SERVER['PHP_SELF']) == 'user_dashboard.php' ? 'class="active"' : '' ?>>Dashboard</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </header>
    <!-- PREMIUM HERO SLIDER (dynamic from hero_slides table) -->
    <?php
    $slides = $conn->query("SELECT * FROM hero_slides WHERE active = 1 ORDER BY slide_order ASC");
    $slide_count = $slides->num_rows;
    ?>
    <div class="hero-slider">
        <?php if ($slide_count > 0):
            $first = true;
            while ($slide = $slides->fetch_assoc()): ?>
                <div class="slide <?php echo $first ? 'active' : '' ?>"
                    style="background-image: url('<?php echo htmlspecialchars($slide['image_url']) ?>');">
                    <div class="slide-content">
                        <h2><?php echo htmlspecialchars($slide['title']) ?></h2>
                        <?php if (!empty($slide['description'])): ?>
                            <p><?php echo htmlspecialchars($slide['description']) ?></p>
                        <?php endif; ?>

                        <div class="hero-buttons" style="display: flex; gap: 10px; margin-top: 20px;">
                            <a href="booking.php" class="btn btn-primary">
                                <i class="fas fa-ticket-alt"></i> Book Now
                            </a>
                            <?php if (!empty($slide['trailer_url'])): ?>
                                <a href="#" class="btn btn-outline"
                                    onclick="playTrailer('<?php echo htmlspecialchars(addslashes($slide['title'])) ?>', '<?php echo htmlspecialchars(addslashes($slide['trailer_url'])) ?>')">
                                    <i class="fas fa-play"></i> Watch Trailer
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php
                $first = false;
            endwhile;
        else: ?>
            <!-- Fallback slide -->
            <div class="slide active" style="background-image: url('default.jpg');">
                <div class="slide-content">
                    <h2>Welcome to Popcorn Hub</h2>
                    <p>Experience the best movies in town.</p>
                    <a href="showtimes.php" class="btn btn-primary">Browse Movies</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="indicators">
            <?php for ($i = 0; $i < $slide_count; $i++): ?>
                <span class="indicator <?php echo $i == 0 ? 'active' : '' ?>" data-index="<?php echo $i ?>"></span>
            <?php endfor; ?>
        </div>
    </div>

    <main class="container">
        <!-- Featured Movies Carousel -->
        <?php
        $featured_result = $conn->query("SELECT * FROM movies WHERE is_featured = 1 ORDER BY created_at DESC LIMIT 5");
        if ($featured_result->num_rows > 0): ?>
            <div id="featuredCarousel" class="carousel slide hero-exclusive reveal" data-bs-ride="carousel">
                <div class="carousel-indicators">
                    <?php $i = 0;
                    while ($featured = $featured_result->fetch_assoc()): ?>
                        <button type="button" data-bs-target="#featuredCarousel" data-bs-slide-to="<?php echo $i ?>"
                            class="<?php echo $i == 0 ? 'active' : '' ?>"
                            aria-current="<?php echo $i == 0 ? 'true' : 'false' ?>"
                            aria-label="Slide <?php echo $i + 1 ?>"></button>
                        <?php $i++; endwhile; ?>
                    <?php $featured_result->data_seek(0); // reset pointer ?>
                </div>
                <div class="carousel-inner">
                    <?php $first = true;
                    while ($featured = $featured_result->fetch_assoc()): ?>
                        <div class="carousel-item <?php echo $first ? 'active' : '' ?>">
                            <div class="hero-content">
                                <span class="hero-label">? Exclusive Featured</span>
                                <h1 class="hero-title"><?php echo htmlspecialchars($featured['title']) ?></h1>
                                <div class="hero-meta">
                                    <span><?php echo htmlspecialchars($featured['category']) ?></span>
                                    <span><?php echo htmlspecialchars($featured['language']) ?></span>
                                </div>
                                <p class="hero-description"><?php echo htmlspecialchars($featured['genre']) ?></p>
                                <p class="hero-director"><strong>Director:</strong> TBD | <strong>Cast:</strong> TBD</p>
                                <div class="hero-buttons">
                                    <a href="booking.php?movie_id=<?php echo $featured['id'] ?>" class="btn btn-primary">Get
                                        Ticket</a>
                                    <a href="#" class="btn btn-outline"
                                        onclick="playTrailer('<?php echo htmlspecialchars(addslashes($featured['title'])) ?>', '<?php echo htmlspecialchars(addslashes($featured['trailer_url'])) ?>')"><i
                                            class="fas fa-play"></i> Watch Trailer</a>
                                </div>
                            </div>
                            <div class="hero-poster"
                                style="background-image: url('<?php echo htmlspecialchars($featured['image_url']) ?>'); background-size: cover; background-position: center;">
                                <!-- Poster image as background instead of text -->
                            </div>
                        </div>
                        <?php $first = false; endwhile; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#featuredCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#featuredCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        <?php else: ?>
            <!-- Fallback if no featured movies -->
            <section class="hero-exclusive reveal">
                <div class="hero-content">
                    <span class="hero-label">? Exclusive Featured</span>
                    <h1 class="hero-title">No featured movies</h1>
                    <p class="hero-description">Admin hasn't selected any featured movies yet.</p>
                </div>
                <div class="hero-poster">FEATURED</div>
            </section>
        <?php endif; ?>

        <!-- Category Filters (now interactive) -->
        <div class="category-filters reveal">
            <button class="category-btn active" data-category="all">All</button>
            <?php foreach ($categories as $cat): ?>
                <button class="category-btn"
                    data-category="<?php echo htmlspecialchars($cat) ?>"><?php echo htmlspecialchars($cat) ?></button>
            <?php endforeach; ?>
        </div>

        <!-- Premium Movies Horizontal Scroll (conditional) -->
        <?php if ($premium_count > 0): ?>
            <div class="section-header reveal" data-category-section="premium">
                <h2>Premium Movies</h2>
                <a href="#" class="view-all">View all <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="horizontal-scroll reveal" data-category-section="premium">
                <?php
                $premium = $conn->query("SELECT * FROM movies WHERE is_premium = 1 ORDER BY created_at DESC LIMIT 10");
                while ($movie = $premium->fetch_assoc()):
                    ?>
                    <div class="premium-card">
                        <div class="card-img"
                            style="background-image: url('<?php echo htmlspecialchars($movie['image_url']) ?>');">
                        </div>
                        <div class="card-content">
                            <div class="card-title"><?php echo htmlspecialchars($movie['title']) ?> <span
                                    class="premium-badge">Premium</span></div>
                            <div class="card-meta"><?php echo htmlspecialchars($movie['category']) ?></div>
                            <div class="card-buttons">
                                <a href="#" class="btn btn-outline btn-small"
                                    onclick="playTrailer('<?php echo htmlspecialchars(addslashes($movie['title'])) ?>', '<?php echo htmlspecialchars(addslashes($movie['trailer_url'])) ?>')">Trailer</a>
                                <a href="booking.php?movie_id=<?php echo $movie['id'] ?>" class="btn btn-primary btn-small">Book
                                    Now</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <!-- Dynamic Category Sections (Horizontal Scroll with View More) -->
        <?php foreach ($categories as $category_name):
            $stmt = $conn->prepare("SELECT * FROM movies WHERE category = ? ORDER BY created_at DESC LIMIT 8");
            $stmt->bind_param("s", $category_name);
            $stmt->execute();
            $cat_movies = $stmt->get_result();
            if ($cat_movies->num_rows == 0)
                continue;
            ?>
            <div class="section-header reveal" data-category-section="<?php echo htmlspecialchars($category_name) ?>">
                <h2><?php echo htmlspecialchars($category_name) ?></h2>
                <a href="showtimes.php?category=<?php echo urlencode($category_name) ?>" class="view-all">View all <i
                        class="fas fa-arrow-right"></i></a>
            </div>
            <div class="horizontal-scroll reveal" data-category-section="<?php echo htmlspecialchars($category_name) ?>">
                <?php while ($movie = $cat_movies->fetch_assoc()): ?>
                    <div class="movie-card-horizontal">
                        <div class="card-img"
                            style="background-image: url('<?php echo htmlspecialchars($movie['image_url']) ?>');">
                        </div>
                        <div class="card-body">
                            <h3 class="card-title"><?php echo htmlspecialchars($movie['title']) ?></h3>
                            <p class="card-meta"><?php echo htmlspecialchars($movie['category']) ?></p>
                            <div class="card-buttons">
                                <a href="#" class="btn btn-outline btn-small"
                                    onclick="playTrailer('<?php echo htmlspecialchars(addslashes($movie['title'])) ?>', '<?php echo htmlspecialchars(addslashes($movie['trailer_url'])) ?>')">Trailer</a>
                                <a href="booking.php?movie_id=<?php echo $movie['id'] ?>"
                                    class="btn btn-primary btn-small">Book</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
                <!-- View More Card -->
                <a href="showtimes.php?category=<?php echo urlencode($category_name) ?>" style="text-decoration: none;">
                    <div class="view-more-card">
                        <i class="fas fa-plus-circle"></i>
                        <span>View All</span>
                        <small style="color: var(--light-gray);"><?php echo $cat_movies->num_rows ?>+ movies</small>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>

        <!-- Streaming & Voting Section (redesigned) -->
        <div class="content-row" style="margin: 60px 0;">
            <!-- Left column: Dynamic Voting (moved here) -->
            <div class="main-column reveal">
                <div class="section-header" style="margin-bottom: 25px;">
                    <h2>Vote For Movie <span style="color: var(--popcorn-orange); font-size: 14px;"> Audience
                            Choice</span></h2>
                </div>
                <div class="vote-card" id="voteCard">
                    <div class="vote-header">
                        <span class="vote-subtitle">This Week's Top Picks</span>
                        <span class="vote-update">Updates every 15 min</span>
                    </div>
                    <div class="vote-list" id="voteList">
                        <div class="text-center py-4">Loading...</div>
                    </div>
                    <div class="vote-footer">
                        <span class="vote-total" id="voteTotal">0 votes</span>
                    </div>
                </div>
            </div>

            <!-- Right column: Promotional Image -->
            <div class="sidebar-column reveal">
                <div class="card"
                    style="background: rgba(15,28,43,0.7); backdrop-filter: blur(10px); border: 1px solid var(--gray-1); border-radius: 24px; overflow: hidden;">
                    <img src="ad.png" alt="Promotion" style="width:100%; height:auto; display:block;">
                    <div style="padding: 20px; text-align: center;">
                        <a href="booking.php" class="btn btn-primary btn-small">Book Now</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Join Banner -->
        <div class="join-banner reveal">
            <div class="join-content">
                <h2>Join Popcorn Hub Premium</h2>
                <p>Unlock exclusive offers, early access, and discounts for kids!</p>
                <div class="benefits">
                    <div class="benefit-item"><i class="fas fa-ticket-alt"></i><span>Early Access</span></div>
                    <div class="benefit-item"><i class="fas fa-gift"></i><span>Exclusive Offers</span></div>
                    <div class="benefit-item"><i class="fas fa-child"></i><span>50% Off for Kids</span></div>
                </div>
                <div class="join-buttons"><a href="register.html" class="btn btn-primary">Create Account</a><a
                        href="login.html" class="btn btn-outline">Sign In</a></div>
            </div>
        </div>
    </main>

    <!-- Modal for trailers -->
    <div class="modal fade" id="trailerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Trailer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe id="trailerIframe" src="" allowfullscreen></iframe>
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
                    <div class="newsletter">
                        <input type="email" placeholder="Your email address">
                        <button class="btn btn-primary btn-small">Subscribe</button>
                    </div>
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
    <!-- Bootstrap JS for modal -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ================= THEMED LOADER WITH PARTICLES =================
        (function () {
            const loader = document.getElementById('popcornLoader');
            // After animation completes (progress bar fills in 2.5s + buffer), fade out
            setTimeout(() => {
                if (loader) {
                    loader.style.opacity = '0';
                    setTimeout(() => {
                        if (loader && loader.parentNode) {
                            loader.remove();
                        }
                    }, 800); // fade-out duration
                }
            }, 2800); // slightly longer than progress animation to ensure it finishes
        })();

        // ================= SCROLL REVEAL & NAVBAR =================
        const reveals = document.querySelectorAll('.reveal');
        const navbar = document.getElementById('navbar');
        if (navbar) {
            window.addEventListener('scroll', function () {
                if (navbar) {
                    if (window.scrollY > 50) {
                        navbar.classList.add('scrolled');
                    } else {
                        navbar.classList.remove('scrolled');
                    }
                }
            });
        }

        // Reveal elements on scroll
        if (reveals.length) {
            const observer = new IntersectionObserver(function (entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('active');
                    }
                });
            }, { threshold: 0.2 });
            reveals.forEach(el => observer.observe(el));
        }

        // ================= MOBILE MENU TOGGLE =================
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

        // ================= SMOOTH PAGE TRANSITIONS =================
        document.querySelectorAll("a").forEach(link => {
            link.addEventListener("click", function (e) {
                if (this.href && this.href.indexOf("#") === -1 && !this.hasAttribute('target') && !this.href.includes('#')) {
                    e.preventDefault();
                    document.body.classList.add("fade-out");
                    setTimeout(() => { window.location = this.href; }, 600);
                }
            });
        });

        // ================= HERO SLIDER =================
        const slides = document.querySelectorAll('.hero-slider .slide');
        const indicators = document.querySelectorAll('.hero-slider .indicator');
        if (slides.length && indicators.length) {
            let currentSlide = 0;
            const slideInterval = setInterval(function () {
                if (slides[currentSlide]) slides[currentSlide].classList.remove('active');
                if (indicators[currentSlide]) indicators[currentSlide].classList.remove('active');
                currentSlide = (currentSlide + 1) % slides.length;
                if (slides[currentSlide]) slides[currentSlide].classList.add('active');
                if (indicators[currentSlide]) indicators[currentSlide].classList.add('active');
            }, 5000);

            indicators.forEach(function (indicator, idx) {
                indicator.addEventListener('click', function () {
                    clearInterval(slideInterval);
                    slides.forEach(s => { if (s) s.classList.remove('active'); });
                    indicators.forEach(i => { if (i) i.classList.remove('active'); });
                    if (slides[idx]) slides[idx].classList.add('active');
                    if (indicators[idx]) indicators[idx].classList.add('active');
                    currentSlide = idx;
                    setInterval(function () {
                        if (slides[currentSlide]) slides[currentSlide].classList.remove('active');
                        if (indicators[currentSlide]) indicators[currentSlide].classList.remove('active');
                        currentSlide = (currentSlide + 1) % slides.length;
                        if (slides[currentSlide]) slides[currentSlide].classList.add('active');
                        if (indicators[currentSlide]) indicators[currentSlide].classList.add('active');
                    }, 5000);
                });
            });
        }

        // ================= DYNAMIC VOTING =================
        const voteList = document.getElementById('voteList');
        const voteTotal = document.getElementById('voteTotal');

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function loadMovies() {
            if (!voteList || !voteTotal) return;
            fetch('get_movies.php')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        voteList.innerHTML = `<div class="text-center py-4" style="color: #ff6b6b;">${escapeHtml(data.error)}</div>`;
                        return;
                    }
                    voteList.innerHTML = '';
                    let total = 0;
                    data.forEach((movie, index) => {
                        total += movie.votes;
                        const item = document.createElement('div');
                        item.className = 'vote-item';
                        item.innerHTML = `
                            <div class="vote-rank">${index + 1}</div>
                            <div class="vote-movie-info">
                                <div class="vote-movie-title">${escapeHtml(movie.title)}</div>
                                <div class="vote-movie-meta">${escapeHtml(movie.genre)} � ${movie.year}</div>
                            </div>
                            <div class="vote-action">
                                <button class="vote-btn" data-movie-id="${movie.id}" ${movie.userVoted ? 'disabled' : ''}>
                                    <i class="fas fa-thumbs-up"></i> ${movie.votes}
                                </button>
                            </div>
                        `;
                        voteList.appendChild(item);
                    });
                    voteTotal.textContent = total + ' votes';
                })
                .catch(error => {
                    console.error('Error loading movies:', error);
                    if (voteList) voteList.innerHTML = '<div class="text-center py-4">Failed to load movies. Check console.</div>';
                });
        }

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.vote-btn');
            if (!btn || btn.disabled) return;

            const movieId = btn.dataset.movieId;
            btn.disabled = true;

            fetch('vote.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'movie_id=' + encodeURIComponent(movieId)
            })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { throw new Error(err.error || `HTTP error ${response.status}`); });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        btn.innerHTML = `<i class="fas fa-thumbs-up"></i> ${data.votes}`;
                        if (voteTotal) {
                            const currentTotal = parseInt(voteTotal.textContent) || 0;
                            voteTotal.textContent = (currentTotal + 1) + ' votes';
                        }
                    } else {
                        btn.disabled = false;
                        alert(data.error || 'Vote failed');
                    }
                })
                .catch(error => {
                    console.error('Vote error:', error);
                    btn.disabled = false;
                    alert(error.message || 'Network error. Please try again.');
                });
        });

        // ================= CATEGORY FILTER =================
        const filterButtons = document.querySelectorAll('.category-btn');
        const sections = document.querySelectorAll('[data-category-section]');

        filterButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const category = btn.dataset.category;
                // Update active class
                filterButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                // Show/hide sections
                sections.forEach(section => {
                    if (category === 'all') {
                        section.style.display = '';
                    } else {
                        const sectionCat = section.dataset.categorySection;
                        section.style.display = sectionCat === category ? '' : 'none';
                    }
                });
            });
        });

        // ================= TRAILER FUNCTION =================
        function playTrailer(movieTitle, trailerUrl) {
            const iframe = document.getElementById('trailerIframe');
            let src = '';

            if (trailerUrl && trailerUrl.trim() !== '') {
                // Convert YouTube watch URL to embed
                if (trailerUrl.includes('youtube.com/watch?v=')) {
                    try {
                        const url = new URL(trailerUrl);
                        const videoId = url.searchParams.get('v');
                        src = 'https://www.youtube.com/embed/' + videoId;
                    } catch (e) {
                        src = trailerUrl;
                    }
                }
                // Convert youtu.be short URL
                else if (trailerUrl.includes('youtu.be/')) {
                    const videoId = trailerUrl.split('youtu.be/')[1].split('?')[0];
                    src = 'https://www.youtube.com/embed/' + videoId;
                }
                // Convert Vimeo URL
                else if (trailerUrl.includes('vimeo.com/')) {
                    const vimeoId = trailerUrl.split('vimeo.com/')[1].split('?')[0];
                    src = 'https://player.vimeo.com/video/' + vimeoId;
                }
                // For any other URL, try to use it directly (must be embeddable)
                else {
                    src = trailerUrl;
                }
            } else {
                // Fallback: search YouTube
                const searchQuery = encodeURIComponent(movieTitle + ' official trailer');
                src = 'https://www.youtube.com/embed?listType=search&list=' + searchQuery;
            }

            iframe.src = src;
            const modal = new bootstrap.Modal(document.getElementById('trailerModal'));
            modal.show();
        }

        const trailerModal = document.getElementById('trailerModal');
        trailerModal.addEventListener('hidden.bs.modal', function () {
            const iframe = document.getElementById('trailerIframe');
            iframe.src = '';
        });

        // Initial load and refresh every 15 minutes
        if (voteList) loadMovies();
        setInterval(function () {
            if (voteList) loadMovies();
        }, 900000); // 15 minutes
    </script>
</body>

</html>