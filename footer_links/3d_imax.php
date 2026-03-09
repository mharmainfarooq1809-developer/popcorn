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
        @media (max-width: 768px) {
            .page-header { padding: 15px 25px; }
            .logo { font-size: 20px; }
            .back-btn { padding: 8px 16px; font-size: 14px; }
            .content-card { padding: 30px 20px; }
            h1 { font-size: 32px; }
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
            <h1>🎥 3D & IMAX Experiences</h1>
            <p>Immerse yourself in stunning visuals and crystal‑clear sound. See it in IMAX or 3D.</p>

            <div class="movie-grid">
                <div class="movie-card">
                    <img src="https://images.unsplash.com/photo-1535016120720-40c646be5580?w=300&h=400&fit=crop" alt="Dune IMAX">
                    <div class="card-body">
                        <div class="card-title">Dune: Part Two</div>
                        <div class="card-meta">IMAX · 3D</div>
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
                    <img src="https://images.unsplash.com/photo-1616530940355-3491f4566b12?w=300&h=400&fit=crop" alt="Godzilla IMAX">
                    <div class="card-body">
                        <div class="card-title">Godzilla x Kong</div>
                        <div class="card-meta">IMAX · 3D</div>
                    </div>
                </div>
                <div class="movie-card">
                    <img src="https://images.unsplash.com/photo-1635805737707-575885ab0820?w=300&h=400&fit=crop" alt="Madame Web">
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