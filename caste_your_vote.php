<?php
require_once 'db_connect.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=vote.php");
    exit;
}

require_once 'settings_init.php';

// Handle vote submission via AJAX (keeping same as before)
// Get filter parameters
$genre_filter = isset($_GET['genre']) ? $_GET['genre'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'votes_desc';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get all unique genres for filter
$genres_query = "SELECT DISTINCT genre FROM movies WHERE genre IS NOT NULL AND genre != '' ORDER BY genre";
$genres_result = $conn->query($genres_query);
$genres = [];
while ($row = $genres_result->fetch_assoc()) {
    $genres[] = $row['genre'];
}

// Get total votes count
$total_votes_query = "SELECT SUM(votes) as total FROM movies";
$total_votes_result = $conn->query($total_votes_query);
$total_votes = $total_votes_result->fetch_assoc()['total'] ?? 0;

// Get user's voted movies
$user_votes = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT movie_id FROM votes WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $user_votes[] = $row['movie_id'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cast Your Vote - <?php echo htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;700;800&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        /* Copy all the base styles from first_page.php */
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

        /* Navbar styles (copy from first_page.php) */
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

        /* Page Header */
        .page-header {
            margin-top: 120px;
            margin-bottom: 40px;
            text-align: center;
        }

        .page-header h1 {
            font-size: 48px;
            font-weight: 800;
            color: var(--white);
            margin-bottom: 15px;
        }

        .page-header h1 span {
            color: var(--popcorn-orange);
        }

        .page-header p {
            font-size: 18px;
            color: var(--light-gray);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(15, 28, 43, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--gray-1);
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            transition: transform 0.3s, border-color 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--popcorn-orange);
        }

        .stat-icon {
            font-size: 32px;
            color: var(--popcorn-orange);
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: var(--white);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--light-gray);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Filter Section */
        .filter-section {
            background: rgba(15, 28, 43, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--gray-1);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .filter-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            flex: 2;
            min-width: 250px;
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-2);
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            background: var(--dark-gray);
            border: 1px solid var(--gray-1);
            border-radius: 40px;
            color: var(--white);
            font-size: 16px;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--popcorn-orange);
        }

        .filter-select {
            flex: 1;
            min-width: 150px;
        }

        .filter-select select {
            width: 100%;
            padding: 12px 15px;
            background: var(--dark-gray);
            border: 1px solid var(--gray-1);
            border-radius: 40px;
            color: var(--white);
            font-size: 16px;
            cursor: pointer;
        }

        .filter-select select:focus {
            outline: none;
            border-color: var(--popcorn-orange);
        }

        .reset-filter {
            padding: 12px 25px;
            background: transparent;
            border: 1px solid var(--gray-1);
            border-radius: 40px;
            color: var(--light-gray);
            cursor: pointer;
            transition: all 0.3s;
        }

        .reset-filter:hover {
            border-color: var(--popcorn-orange);
            color: var(--popcorn-orange);
        }

        /* Movies Grid */
        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .movie-card {
            background: rgba(15, 28, 43, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--gray-1);
            border-radius: 20px;
            overflow: hidden;
            transition: transform 0.3s, border-color 0.3s;
            position: relative;
        }

        .movie-card:hover {
            transform: translateY(-8px);
            border-color: var(--popcorn-orange);
            box-shadow: 0 25px 40px rgba(255, 165, 0, 0.2);
        }

        .movie-poster {
            height: 180px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .movie-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, transparent 50%, rgba(0, 0, 0, 0.8));
        }

        .premium-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(145deg, #FFD700, #B8860B);
            color: #000;
            font-size: 12px;
            padding: 4px 12px;
            border-radius: 30px;
            font-weight: 600;
            z-index: 2;
        }

        .movie-content {
            padding: 20px;
        }

        .movie-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .movie-genre {
            font-size: 14px;
            color: var(--light-gray);
            margin-bottom: 12px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .genre-tag {
            background: var(--dark-gray);
            padding: 2px 10px;
            border-radius: 30px;
            font-size: 12px;
            color: var(--gray-3);
        }

        .movie-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray-1);
        }

        .movie-year {
            color: var(--gray-2);
            font-size: 14px;
        }

        .vote-count {
            background: var(--dark-gray);
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 14px;
            color: var(--popcorn-gold);
        }

        .vote-count i {
            margin-right: 5px;
            color: var(--popcorn-orange);
        }

        .vote-button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 40px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .vote-button:not(:disabled) {
            background: linear-gradient(145deg, var(--popcorn-orange), #cc7f00);
            color: var(--white);
        }

        .vote-button:not(:disabled):hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 165, 0, 0.5);
        }

        .vote-button:disabled {
            background: var(--dark-gray);
            color: var(--gray-2);
            cursor: not-allowed;
        }

        .vote-button.voted {
            background: var(--dark-gray);
            color: var(--popcorn-gold);
            border: 1px solid var(--popcorn-orange);
        }

        /* Leaderboard Section */
        .leaderboard-section {
            background: rgba(15, 28, 43, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--gray-1);
            border-radius: 20px;
            padding: 25px;
            margin-top: 40px;
        }

        .leaderboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .leaderboard-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--white);
        }

        .leaderboard-header h2 i {
            color: var(--popcorn-orange);
            margin-right: 10px;
        }

        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
        }

        .leaderboard-table th {
            text-align: left;
            padding: 15px 10px;
            color: var(--light-gray);
            font-weight: 500;
            font-size: 14px;
            border-bottom: 1px solid var(--gray-1);
        }

        .leaderboard-table td {
            padding: 15px 10px;
            border-bottom: 1px solid var(--gray-1);
        }

        .leaderboard-table tr:last-child td {
            border-bottom: none;
        }

        .leaderboard-table tr:hover td {
            background: rgba(255, 165, 0, 0.05);
        }

        .rank {
            font-weight: 800;
            font-size: 18px;
            color: var(--popcorn-orange);
        }

        .rank-1 {
            color: #FFD700;
        }

        .rank-2 {
            color: #C0C0C0;
        }

        .rank-3 {
            color: #CD7F32;
        }

        .movie-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .movie-thumb {
            width: 50px;
            height: 70px;
            background-size: cover;
            background-position: center;
            border-radius: 8px;
            border: 1px solid var(--gray-1);
        }

        .movie-details h4 {
            font-size: 16px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 4px;
        }

        .movie-details p {
            font-size: 12px;
            color: var(--light-gray);
        }

        .progress-bar-container {
            width: 100%;
            max-width: 200px;
            height: 8px;
            background: var(--dark-gray);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--popcorn-gold), var(--popcorn-orange));
            border-radius: 4px;
            transition: width 0.3s;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 40px;
        }

        .page-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--dark-gray);
            border: 1px solid var(--gray-1);
            color: var(--light-gray);
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .page-btn:hover {
            border-color: var(--popcorn-orange);
            color: var(--popcorn-orange);
        }

        .page-btn.active {
            background: var(--popcorn-orange);
            color: white;
            border-color: var(--popcorn-orange);
        }

        /* Loading Spinner */
        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 300px;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 3px solid var(--gray-1);
            border-top-color: var(--popcorn-orange);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* No Results */
        .no-results {
            text-align: center;
            padding: 60px 20px;
            background: rgba(15, 28, 43, 0.5);
            border-radius: 20px;
            border: 1px solid var(--gray-1);
        }

        .no-results i {
            font-size: 48px;
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
        }

        /* Toast Notification */
        .toast-notification {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: rgba(15, 28, 43, 0.95);
            backdrop-filter: blur(10px);
            border-left: 4px solid var(--popcorn-orange);
            border-radius: 12px;
            padding: 15px 25px;
            color: var(--white);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            transform: translateX(400px);
            transition: transform 0.3s;
            z-index: 9999;
        }

        .toast-notification.show {
            transform: translateX(0);
        }

        .toast-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .toast-content i {
            font-size: 20px;
            color: var(--popcorn-orange);
        }

        /* Mobile Responsive */
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

            .page-header h1 {
                font-size: 36px;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .filter-row {
                flex-direction: column;
            }

            .search-box,
            .filter-select,
            .reset-filter {
                width: 100%;
            }
        }

        /* Copy footer styles from first_page.php */
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

        .copyright {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid var(--gray-1);
            color: var(--gray-2);
            font-size: 14px;
        }
    </style>
    <link rel="stylesheet" href="public_theme.php">
</head>

<body>
    <!-- Navbar (same as first_page.php) -->
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
            <a href="showtimes.php">Showtimes</a>
            <a href="theatre.php">Theatres</a>
            <a href="booking.php">Booking</a>
            <a href="vote.php" class="active">Cast Your Vote</a>
            <a href="about.php">About</a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="user_dashboard.php">Dashboard</a>
                <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                    <a href="Admin_Dashboard/dashboard.php"><i class="fas fa-crown"></i> Admin</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Cast Your <span>Vote</span></h1>
            <p>Help us choose the next blockbuster! Vote for your favorite movies and see what others are watching.</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-film"></i></div>
                <div class="stat-value" id="totalMovies">0</div>
                <div class="stat-label">Total Movies</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-thumbs-up"></i></div>
                <div class="stat-value" id="totalVotes"><?php echo number_format($total_votes); ?></div>
                <div class="stat-label">Total Votes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-value" id="totalVoters">0</div>
                <div class="stat-label">Active Voters</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-trophy"></i></div>
                <div class="stat-value" id="topMovie">-</div>
                <div class="stat-label">Top Movie</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-row">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search movies..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-select">
                    <select id="genreFilter">
                        <option value="">All Genres</option>
                        <?php foreach ($genres as $genre): ?>
                            <option value="<?php echo htmlspecialchars($genre); ?>" <?php echo $genre_filter == $genre ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($genre); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-select">
                    <select id="sortFilter">
                        <option value="votes_desc" <?php echo $sort_by == 'votes_desc' ? 'selected' : ''; ?>>Most Voted</option>
                        <option value="votes_asc" <?php echo $sort_by == 'votes_asc' ? 'selected' : ''; ?>>Least Voted</option>
                        <option value="title_asc" <?php echo $sort_by == 'title_asc' ? 'selected' : ''; ?>>Title A-Z</option>
                        <option value="title_desc" <?php echo $sort_by == 'title_desc' ? 'selected' : ''; ?>>Title Z-A</option>
                        <option value="year_desc" <?php echo $sort_by == 'year_desc' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="year_asc" <?php echo $sort_by == 'year_asc' ? 'selected' : ''; ?>>Oldest First</option>
                    </select>
                </div>
                <button class="reset-filter" onclick="resetFilters()">
                    <i class="fas fa-redo-alt"></i> Reset
                </button>
            </div>
        </div>

        <!-- Movies Grid -->
        <div id="moviesGrid" class="movies-grid">
            <!-- Movies will be loaded here via AJAX -->
            <div class="loading-spinner">
                <div class="spinner"></div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="pagination" id="pagination"></div>

        <!-- Leaderboard Section -->
        <div class="leaderboard-section">
            <div class="leaderboard-header">
                <h2><i class="fas fa-crown"></i> Top 10 Movies Leaderboard</h2>
                <span class="vote-update">Updates in real-time</span>
            </div>
            <table class="leaderboard-table" id="leaderboardTable">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Movie</th>
                        <th>Genre</th>
                        <th>Votes</th>
                        <th>Share</th>
                    </tr>
                </thead>
                <tbody id="leaderboardBody">
                    <!-- Leaderboard will be loaded here -->
                    <tr>
                        <td colspan="5" class="text-center py-4">Loading leaderboard...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Toast Notification -->
    <div id="toast" class="toast-notification">
        <div class="toast-content">
            <i class="fas fa-check-circle"></i>
            <span id="toastMessage">Vote recorded successfully!</span>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4><?php echo htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></h4>
                    <ul>
                        <li><a href="footer_links/about.php">About Us</a></li>
                        <li><a href="footer_links/careers.php">Careers</a></li>
                        <li><a href="footer_links/contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Movies</h4>
                    <ul>
                        <li><a href="showtimes.php">Now Showing</a></li>
                        <li><a href="coming_soon.php">Coming Soon</a></li>
                        <li><a href="vote.php">Cast Your Vote</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="footer_links/terms.php">Terms of Use</a></li>
                        <li><a href="footer_links/privacy.php">Privacy Policy</a></li>
                        <li><a href="footer_links/faq.php">FAQ</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Stay Connected</h4>
                    <div class="social-links">
                        <?php if (!empty($settings['facebook_url'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['facebook_url']) ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($settings['twitter_url'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['twitter_url']) ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($settings['instagram_url'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['instagram_url']) ?>" target="_blank"><i class="fab fa-instagram"></i></a>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Store user's voted movies
        const userVotes = <?php echo json_encode($user_votes); ?>;

        // ================= NAVBAR SCROLL EFFECT =================
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // ================= MOBILE MENU TOGGLE =================
        const menuToggle = document.getElementById('menuToggle');
        const navLinks = document.getElementById('navLinks');
        if (menuToggle && navLinks) {
            menuToggle.addEventListener('click', () => {
                navLinks.classList.toggle('active');
                menuToggle.innerHTML = navLinks.classList.contains('active') ?
                    '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
            });

            navLinks.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    navLinks.classList.remove('active');
                    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                });
            });
        }

        // ================= TOAST NOTIFICATION =================
        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            const toastIcon = toast.querySelector('i');

            toastMessage.textContent = message;
            if (isError) {
                toastIcon.className = 'fas fa-exclamation-circle';
                toastIcon.style.color = '#ff4444';
                toast.style.borderLeftColor = '#ff4444';
            } else {
                toastIcon.className = 'fas fa-check-circle';
                toastIcon.style.color = 'var(--popcorn-orange)';
                toast.style.borderLeftColor = 'var(--popcorn-orange)';
            }

            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // ================= LOAD MOVIES =================
        let currentPage = 1;
        let totalPages = 1;

        function loadMovies(page = 1) {
            const search = document.getElementById('searchInput').value;
            const genre = document.getElementById('genreFilter').value;
            const sort = document.getElementById('sortFilter').value;

            currentPage = page;

            const params = new URLSearchParams({
                page: page,
                search: search,
                genre: genre,
                sort: sort
            });

            fetch('api/get_movies.php?' + params.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        showToast(data.error, true);
                        return;
                    }

                    renderMovies(data.movies);
                    renderPagination(data.total_pages, data.current_page);
                    updateStats(data.stats);
                    updateLeaderboard(data.leaderboard);
                })
                .catch(error => {
                    console.error('Error loading movies:', error);
                    showToast('Failed to load movies', true);
                });
        }

        function renderMovies(movies) {
            const grid = document.getElementById('moviesGrid');

            if (movies.length === 0) {
                grid.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-film"></i>
                        <h3>No Movies Found</h3>
                        <p>Try adjusting your filters or search criteria</p>
                    </div>
                `;
                return;
            }

            grid.innerHTML = movies.map(movie => {
                const hasVoted = userVotes.includes(movie.id);
                const releaseYear = movie.release_date ? new Date(movie.release_date).getFullYear() : 'TBA';

                return `
                    <div class="movie-card" data-movie-id="${movie.id}">
                        <div class="movie-poster" style="background-image: url('${movie.image_url || 'default-poster.jpg'}')">
                            <div class="movie-overlay"></div>
                            ${movie.is_premium ? '<span class="premium-badge">Premium</span>' : ''}
                        </div>
                        <div class="movie-content">
                            <h3 class="movie-title">
                                ${escapeHtml(movie.title)}
                            </h3>
                            <div class="movie-genre">
                                ${movie.genre.split(',').map(g =>
                                    `<span class="genre-tag">${g.trim()}</span>`
                                ).join('')}
                            </div>
                            <div class="movie-meta">
                                <span class="movie-year">${releaseYear}</span>
                                <span class="vote-count">
                                    <i class="fas fa-thumbs-up"></i> ${movie.votes}
                                </span>
                            </div>
                            <button class="vote-button ${hasVoted ? 'voted' : ''}"
                                    onclick="voteForMovie(${movie.id})"
                                    ${hasVoted ? 'disabled' : ''}>
                                <i class="fas fa-${hasVoted ? 'check' : 'thumbs-up'}"></i>
                                ${hasVoted ? 'Voted' : 'Vote Now'}
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderPagination(total, current) {
            totalPages = total;
            const pagination = document.getElementById('pagination');

            if (total <= 1) {
                pagination.innerHTML = '';
                return;
            }

            let html = '';

            // Previous button
            html += `<button class="page-btn" onclick="loadMovies(${current - 1})" ${current === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
            </button>`;

            // Page numbers
            for (let i = 1; i <= total; i++) {
                if (i === 1 || i === total || (i >= current - 2 && i <= current + 2)) {
                    html += `<button class="page-btn ${i === current ? 'active' : ''}" onclick="loadMovies(${i})">${i}</button>`;
                } else if (i === current - 3 || i === current + 3) {
                    html += `<button class="page-btn" disabled>...</button>`;
                }
            }

            // Next button
            html += `<button class="page-btn" onclick="loadMovies(${current + 1})" ${current === total ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
            </button>`;

            pagination.innerHTML = html;
        }

        function updateStats(stats) {
            document.getElementById('totalMovies').textContent = stats.total_movies;
            document.getElementById('totalVotes').textContent = stats.total_votes;
            document.getElementById('totalVoters').textContent = stats.total_voters;
            document.getElementById('topMovie').textContent = stats.top_movie || '-';
        }

        function updateLeaderboard(leaderboard) {
            const tbody = document.getElementById('leaderboardBody');

            if (leaderboard.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">No data available</td></tr>';
                return;
            }

            const maxVotes = leaderboard[0]?.votes || 1;

            tbody.innerHTML = leaderboard.map((movie, index) => {
                const rankClass = index === 0 ? 'rank-1' : index === 1 ? 'rank-2' : index === 2 ? 'rank-3' : '';
                const percentage = Math.round((movie.votes / maxVotes) * 100);

                return `
                    <tr>
                        <td><span class="rank ${rankClass}">${index + 1}</span></td>
                        <td>
                            <div class="movie-info">
                                <div class="movie-thumb" style="background-image: url('${movie.image_url || 'default-poster.jpg'}')"></div>
                                <div class="movie-details">
                                    <h4>${escapeHtml(movie.title)}</h4>
                                    <p>${movie.genre || 'N/A'}</p>
                                </div>
                            </div>
                        </td>
                        <td>${movie.genre || 'N/A'}</td>
                        <td><strong>${movie.votes}</strong> votes</td>
                        <td>
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill" style="width: ${percentage}%"></div>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // ================= VOTE FUNCTION =================
        function voteForMovie(movieId) {
            fetch('vote.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'movie_id=' + encodeURIComponent(movieId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    userVotes.push(movieId);
                    showToast('Vote recorded successfully!');
                    loadMovies(currentPage); // Reload current page

                    // Also reload leaderboard
                    fetch('api/get_leaderboard.php')
                        .then(res => res.json())
                        .then(leaderboard => updateLeaderboard(leaderboard));
                } else {
                    showToast(data.error || 'Failed to vote', true);
                }
            })
            .catch(error => {
                console.error('Vote error:', error);
                showToast('Network error. Please try again.', true);
            });
        }

        // ================= FILTER HANDLERS =================
        document.getElementById('searchInput').addEventListener('input', debounce(() => {
            loadMovies(1);
        }, 500));

        document.getElementById('genreFilter').addEventListener('change', () => {
            loadMovies(1);
        });

        document.getElementById('sortFilter').addEventListener('change', () => {
            loadMovies(1);
        });

        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('genreFilter').value = '';
            document.getElementById('sortFilter').value = 'votes_desc';
            loadMovies(1);
        }

        // ================= UTILITY FUNCTIONS =================
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // ================= INITIAL LOAD =================
        document.addEventListener('DOMContentLoaded', () => {
            loadMovies(1);

            // Auto-refresh leaderboard every 30 seconds
            setInterval(() => {
                fetch('api/get_leaderboard.php')
                    .then(res => res.json())
                    .then(leaderboard => updateLeaderboard(leaderboard));
            }, 30000);
        });
    </script>
</body>
</html>