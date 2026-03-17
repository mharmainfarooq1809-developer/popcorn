<?php $page_title = "3D / IMAX"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | Popcorn Hub</title>
    <!-- same head and CSS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    /* Paste the full CSS from now_showing.php here */

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0a0a0a;
            color: #fff;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .page-header {
            background: rgba(10,10,10,0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding: 18px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .logo {
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, #FFD966, #FFA500);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .logo i { margin-right: 8px; color: #FFA500; }
        .back-btn {
            text-decoration: none;
            background: linear-gradient(145deg, #FFA500, #cc7f00);
            color: #fff;
            font-weight: 600;
            padding: 10px 22px;
            border-radius: 40px;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 14px rgba(255,165,0,0.3);
        }
        .back-btn i { transition: transform 0.2s; }
        .back-btn:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(255,165,0,0.5); }
        .back-btn:hover i { transform: translateX(-4px); }
        .page-container {
            max-width: 1200px;
            margin: 60px auto;
            padding: 0 30px;
            flex: 1;
        }
        .content-card {
            background: rgba(20,20,20,0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,165,0,0.1) inset;
            animation: fadeInUp 0.6s ease-out;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        h1 {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #fff, #ccc);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        p {
            color: #bbb;
            font-size: 18px;
            margin-bottom: 30px;
        }
        .movie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        .movie-card {
            background: #1a1a1a;
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
        .movie-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 30px rgba(255,165,0,0.3);
        }
        .movie-card img {
            width: 100%;
            height: 260px;
            object-fit: cover;
            display: block;
        }
        .movie-card .card-body {
            padding: 15px;
        }
        .movie-card .card-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #FFD966;
        }
        .movie-card .card-meta {
            font-size: 14px;
            color: #aaa;
            display: flex;
            gap: 10px;
        }
        .footer {
            text-align: center;
            padding: 30px;
            color: #666;
            font-size: 14px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        .hero-image {
            width: 100%;
            border-radius: 18px;
            overflow: hidden;
            margin: 0 0 24px;
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 14px 30px rgba(0,0,0,0.35);
        }
        .hero-image img {
            width: 100%;
            height: 240px;
            object-fit: cover;
            display: block;
        }
@media (max-width: 768px) {
            .page-header { padding: 15px 25px; }
            .logo { font-size: 20px; }
            .back-btn { padding: 8px 16px; font-size: 14px; }
            .content-card { padding: 30px 20px; }
            h1 { font-size: 32px; }
        }
        .hero-image {
            width: 100%;
            border-radius: 18px;
            overflow: hidden;
            margin: 0 0 24px;
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 14px 30px rgba(0,0,0,0.35);
        }
        .hero-image img {
            width: 100%;
            height: 240px;
            object-fit: cover;
            display: block;
        }
@media (max-width: 480px) {
            .page-header { flex-direction: column; gap: 12px; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="logo"><i class="fas fa-film"></i> Popcorn Hub</div>
        <a href="javascript:history.back()" class="back-btn"><i class="fas fa-arrow-left"></i> Go Back</a>
    </div>


    <div class="page-container">
        <div class="content-card">
            <div class="hero-image">
                <img src="https://images.unsplash.com/photo-1626814026160-2237a95fc5a0?q=80&w=2070&auto=format&fit=crop" alt="Popcorn Hub cinema" loading="lazy">
            </div>
<h1> 3D & IMAX Experiences</h1>
            <p>Immerse yourself in stunning visuals and crystal'clear sound. See it in IMAX or 3D.</p>

            <div class="movie-grid">
                <div class="movie-card">
                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRq8P5uwVwfPAX9FnmiLtcFECf8l28lS9FN-g&s" alt="Dune IMAX">
                    <div class="card-body">
                        <div class="card-title">Dune: Part Two</div>
                        <div class="card-meta">IMAX - 3D</div>
                    </div>
                </div>
                <div class="movie-card">
                    <img src="https://images.unsplash.com/photo-1626814026160-2237a95fc5a0?w=300&h=400&fit=crop" alt="Avatar IMAX">
                    <div class="card-body">
                        <div class="card-title">Avatar 3</div>
                        <div class="card-meta">IMAX 3D</div>
                    </div>
                </div>
                <div class="movie-card">
                    <img src="data:image/jpeg;base64,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" alt="Godzilla IMAX">
                    <div class="card-body">
                        <div class="card-title">Godzilla x Kong</div>
                        <div class="card-meta">IMAX - 3D</div>
                    </div>
                </div>
                <div class="movie-card">
                    <img src="https://www.cleveland.com/resizer/v2/https%3A%2F%2Fimage.cleveland.com%2Fhome%2Fcleve-media%2Fwidth2048%2Fimg%2Fent_impact_movies%2Fphoto%2FScreen%20Shot%202018-03-29%20at%2010.16.46%20AM.png?auth=4c48442e315ff022f2755c200b246c780024e34791cf9511c68efc1d940fb073&width=1280&smart=true&quality=90" alt="Madame Web">
                    <div class="card-body">
                        <div class="card-title">Madame Web</div>
                        <div class="card-meta">3D</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        &copy; <?php echo date('Y'); ?> Popcorn Hub. All rights reserved.
    </div>
</body>
</html>