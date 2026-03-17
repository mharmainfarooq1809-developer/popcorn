<?php
session_start();
// maintenance.php " Under Construction page
require_once 'settings_init.php';
$public_pages = ['login.php', 'register.php', 'maintenance.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (($settings['maintenance_mode'] ?? '0') === '1' && !in_array($current_page, $public_pages, true)) {
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
        header("Location: /eproject2/maintenance.php");
        exit;
    }
}
$settings = get_settings($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Construction - <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: #0a0a0a;
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
        }
        .maintenance-card {
            max-width: 600px;
            background: rgba(20,20,20,0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 24px;
            padding: 50px 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.6);
            animation: fadeInUp 0.6s ease-out;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        h1 {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #FFD966, #FFA500);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
            color: #FFA500;
        }
        p {
            font-size: 18px;
            color: #bbb;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        .small {
            font-size: 14px;
            color: #666;
        }
        .progress-bar {
            width: 100%;
            height: 4px;
            background: #222;
            border-radius: 4px;
            margin: 30px 0 20px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            width: 45%;
            background: linear-gradient(90deg, #FFA500, #FFD966);
            border-radius: 4px;
            animation: progress 2s ease-in-out infinite alternate;
        }
        @keyframes progress {
            0% { width: 20%; margin-left: 0; }
            100% { width: 70%; margin-left: 30%; }
        }
    </style>
    <link rel="stylesheet" href="public_theme.php">
</head>
<body>
    <div class="maintenance-card">
        <div class="icon"></div>
        <h1>Under Construction</h1>
        <p>We're currently performing scheduled maintenance to improve your experience.</p>
        <p>Our team is working hard to bring you something amazing. Please check back soon!</p>
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
        <p class="small">Expected completion: within the next few hours.</p>
        <p class="small">Thank you for your patience.</p>
    </div>
</body>
</html>