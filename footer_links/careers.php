<?php $page_title = "Careers"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | Popcorn Hub</title>
    <!-- (same head links and CSS as abouts.php – omitted here for brevity, but in a real answer include the full CSS block) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Copy the full CSS block from page_template.php – exactly the same */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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
            background: rgba(10, 10, 10, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 18px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
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

        .logo i {
            margin-right: 8px;
            color: #FFA500;
        }

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
            box-shadow: 0 4px 14px rgba(255, 165, 0, 0.3);
        }

        .back-btn i {
            transition: transform 0.2s;
        }

        .back-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 165, 0, 0.5);
        }

        .back-btn:hover i {
            transform: translateX(-4px);
        }

        .page-container {
            max-width: 900px;
            margin: 60px auto;
            padding: 0 30px;
            flex: 1;
        }

        .content-card {
            background: rgba(20, 20, 20, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 50px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 165, 0, 0.1) inset;
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

        .section {
            margin-top: 40px;
        }

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
            content: "●";
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
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 15px 25px;
            }

            .logo {
                font-size: 20px;
            }

            .back-btn {
                padding: 8px 16px;
                font-size: 14px;
            }

            .content-card {
                padding: 30px 20px;
            }

            h1 {
                font-size: 32px;
            }

            .section h2 {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .page-header {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }

            .content-card {
                padding: 25px 15px;
            }
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
            <h1>Careers</h1>
            <p>Join the Popcorn Hub family! We're always looking for passionate individuals to help us create the best
                movie experience.</p>

            <div class="section">
                <h2>Current Openings</h2>
                <ul>
                    <li><strong>Cinema Manager</strong> – Lead our team and ensure smooth operations.</li>
                    <li><strong>Customer Service Representative</strong> – Be the face of Popcorn Hub.</li>
                    <li><strong>Projectionist</strong> – Maintain our cutting‑edge projection equipment.</li>
                    <li><strong>Concession Staff</strong> – Serve our famous popcorn with a smile.</li>
                </ul>
            </div>

            <div class="section">
                <h2>Why Work With Us?</h2>
                <ul>
                    <li>Competitive pay and benefits</li>
                    <li>Free movies for you and your family</li>
                    <li>Flexible schedules</li>
                    <li>Opportunities for growth</li>
                </ul>
            </div>

            <p>To apply, send your resume to <strong>careers@popcornhub.com</strong> with the position in the subject
                line.</p>
        </div>
    </div>

    <div class="footer">
        &copy; <?php echo date('Y'); ?> Popcorn Hub. All rights reserved.
    </div>
</body>

</html>