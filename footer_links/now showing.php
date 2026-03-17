<?php $page_title = "Now Showing"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | Popcorn Hub</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
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
        /* ===== HEADER (glassmorphism) ===== */
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
        /* ===== MAIN CONTENT ===== */
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
        /* ===== MOVIE GRID ===== */
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
        /* ===== FOOTER ===== */
        .footer {
            text-align: center;
            padding: 30px;
            color: #666;
            font-size: 14px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        /* ===== RESPONSIVE ===== */
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
<h1> Now Showing</h1>
            <p>Catch the latest blockbusters on the big screen. Book your tickets now!</p>

            <div class="movie-grid">
                <!-- Replace with dynamic data if needed -->
                <div class="movie-card">
                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRq8P5uwVwfPAX9FnmiLtcFECf8l28lS9FN-g&s" alt="Dune">
                    <div class="card-body">
                        <div class="card-title">Dune: Part Two</div>
                        <div class="card-meta"><span>2h 46m</span> <span>Sci-Fi</span></div>
                    </div>
                </div>
                <div class="movie-card">
                    <img src="https://images.unsplash.com/photo-1626814026160-2237a95fc5a0?w=300&h=400&fit=crop" alt="Avatar">
                    <div class="card-body">
                        <div class="card-title">Avatar 3</div>
                        <div class="card-meta"><span>3h 12m</span> <span>Adventure</span></div>
                    </div>
                </div>
                <div class="movie-card">
                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS32LsZ1qjbH_rvaXqni37DMAh5JvFBQWY3Cg&s" alt="Kung Fu Panda">
                    <div class="card-body">
                        <div class="card-title">Kung Fu Panda 4</div>
                        <div class="card-meta"><span>1h 34m</span> <span>Animation</span></div>
                    </div>
                </div>
                <div class="movie-card">
                    <img src="data:image/jpeg;base64,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" alt="Godzilla">
                    <div class="card-body">
                        <div class="card-title">Godzilla x Kong</div>
                        <div class="card-meta"><span>1h 55m</span> <span>Action</span></div>
                    </div>
                </div>
                <div class="movie-card">
                    <img src="https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=400&fit=crop" alt="Madame Web">
                    <div class="card-body">
                        <div class="card-title">Madame Web</div>
                        <div class="card-meta"><span>1h 56m</span> <span>Superhero</span></div>
                    </div>
                </div>
                <div class="movie-card">
                    <img src="data:image/jpeg;base64,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" alt="The Fall Guy">
                    <div class="card-body">
                        <div class="card-title">The Fall Guy</div>
                        <div class="card-meta"><span>2h 5m</span> <span>Action</span></div>
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