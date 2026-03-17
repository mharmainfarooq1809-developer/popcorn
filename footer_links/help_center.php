<?php $page_title = "Help Center"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | Popcorn Hub</title>
  <style>
        /* Copy the full CSS block from page_template.php " exactly the same */
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
            max-width: 900px;
            margin: 60px auto;
            padding: 0 30px;
            flex: 1;
        }
        .content-card {
            background: rgba(20,20,20,0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 24px;
            padding: 50px;
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
            line-height: 1.2;
        }
        p {
            color: #bbb;
            font-size: 17px;
            margin-bottom: 20px;
        }
        .section { margin-top: 40px; }
        .section h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #FFD966;
            border-left: 6px solid #FFA500;
            padding-left: 20px;
        }
        ul {
            list-style: none;
            padding: 0;
        }
        ul li {
            margin-bottom: 14px;
            padding-left: 28px;
            position: relative;
            color: #ccc;
            font-size: 16px;
        }
        ul li::before {
            content: "-";
            color: #FFA500;
            font-size: 20px;
            position: absolute;
            left: 0;
            top: -2px;
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
            .section h2 { font-size: 24px; }
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
            .content-card { padding: 25px 15px; }
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
<h1>Help Center</h1>
            <p>Find answers to common questions and get the support you need.</p>

            <div class="section">
                <h2>Frequently Asked Questions</h2>
                <ul>
                    <li><strong>How do I book a ticket?</strong> " Visit our showtimes page, select a movie, and follow the checkout process.</li>
                    <li><strong>Can I cancel or change my booking?</strong> " Yes, up to 2 hours before the showtime via your account dashboard.</li>
                    <li><strong>Do you offer group discounts?</strong> " Absolutely! Contact us for groups of 10 or more.</li>
                    <li><strong>What safety measures are in place?</strong> " Enhanced cleaning, contactless payment, and optional mask areas.</li>
                </ul>
            </div>

            <div class="section">
                <h2>Still Need Help</h2>
                <p>Email our support team at <strong>help@popcornhub.com</strong> or call (555) 123'4567.</p>
            </div>
        </div>
    </div>
    <div class="footer">...</div>
</body>
</html>

