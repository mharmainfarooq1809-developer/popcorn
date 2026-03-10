<?php
session_start();
require_once 'db_connect.php';
require_once 'settings_init.php';

$public_pages = ['login.php', 'register.php', 'maintenance.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (($settings['maintenance_mode'] ?? '0') === '1' && !in_array($current_page, $public_pages, true)) {
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
        header("Location: /eproject2/maintenance.php");
        exit;
    }
}

// Handle registration
if (isset($_POST['register'])) {
    $name = trim($_POST['reg_name']);
    $email = trim($_POST['reg_email']);
    $password = $_POST['reg_password'];
    $confirm = $_POST['reg_confirm'];

    $errors = [];

    // Validation
    if (empty($name) || !preg_match("/^[a-zA-Z\s]{2,50}$/", $name)) {
        $errors[] = "Name must be 2-50 letters and spaces only.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    // Check if email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email already registered.";
        }
        $stmt->close();
    }

    // Insert if no errors
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $role = 'user'; // default role
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $hashed, $role);
        if ($stmt->execute()) {
            $reg_success = "Registration successful! You can now log in.";
            // Log registration notification
            $conn->query("INSERT INTO notifications (type, message, link) VALUES ('user', 'New user registered: " . $conn->real_escape_string($name) . "', 'Admin_Dashboard/users.php')");
        } else {
            $errors[] = "Registration failed: " . $conn->error;
        }
        $stmt->close();
    }
}

if (isset($_POST['login'])) {
    $email = trim($_POST['login_email']);
    $password = $_POST['login_password'];

    $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        // User found
        if (password_verify($password, $row['password'])) {
            $normalizedRole = strtolower(trim((string)$row['role']));
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_role'] = $normalizedRole;

            // Redirect based on role
            if ($normalizedRole === 'admin') {
                header("Location: Admin_Dashboard/dashboard.php");
                exit;
            } else {
                header("Location: first_page.php");
                exit;
            }
        } else {
            $login_error = "Invalid email or password (password mismatch)";
        }
    } else {
        $login_error = "Invalid email or password (email not found)";
    }
    $stmt->close();
}

// Determine initial active panel based on PHP state (for UI)
$initialPanel = 'login'; // default
if (!empty($errors) || isset($reg_success)) {
    $initialPanel = 'register';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?> · access</title>
    <!-- Modern fonts: Inter + Clash Display for headings -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">
    <link href="https://api.fontshare.com/v2/css?f[]=clash-display@200,300,400,500,600,700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ---------- DESIGN SYSTEM ---------- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --space-black: #0A0F1E;
            --navy-deep: #0B1623;
            --navy-medium: #142433;
            --popcorn-gold: #FFD966;
            --popcorn-orange: #FFA500;
            --popcorn-dark: #cc7f00;
            --popcorn-glow: #FFE68F;
            --white-pure: #FFFFFF;
            --gray-soft: #94A3B8;
            --error-red: #EF4444;
            --success-green: #10B981;
            --glass-bg: rgba(10, 20, 35, 0.6);
            --glass-border: rgba(255, 255, 255, 0.05);
            --card-shadow: 0 50px 80px -20px rgba(0, 0, 0, 0.8), 0 0 0 1px rgba(255, 165, 0, 0.3);
            --transition-slow: 0.8s cubic-bezier(0.25, 0.1, 0.15, 1.2);
        }

        body {
            font-family: "Inter", sans-serif;
            background: var(--space-black);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            color: var(--white-pure);
            line-height: 1.5;
        }

        /* ---------- ANIMATED BACKGROUND ---------- */
        .cosmic-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        .gradient-orb {
            position: absolute;
            width: 80vmax;
            height: 80vmax;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, rgba(255, 165, 0, 0.25), transparent 70%);
            filter: blur(80px);
            animation: orbFloat 30s infinite alternate ease-in-out;
        }

        .orb-1 {
            top: -20%;
            right: -20%;
            background: radial-gradient(circle, rgba(255, 165, 0, 0.3), transparent);
        }

        .orb-2 {
            bottom: -20%;
            left: -20%;
            background: radial-gradient(circle, rgba(204, 127, 0, 0.3), transparent);
            animation-duration: 40s;
        }

        .orb-3 {
            top: 40%;
            left: 30%;
            width: 40vmax;
            height: 40vmax;
            background: radial-gradient(circle, rgba(255, 217, 102, 0.2), transparent);
            filter: blur(100px);
        }

        @keyframes orbFloat {
            0% {
                transform: translate(0, 0) scale(1);
            }

            100% {
                transform: translate(-5%, 10%) scale(1.3);
            }
        }

        /* Subtle noise texture */
        .noise {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.05'/%3E%3C/svg%3E");
            opacity: 0.2;
            pointer-events: none;
            z-index: 1;
        }

        /* Particles */
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 2;
        }

        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(255, 215, 0, 0.5);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--popcorn-gold);
            animation: particleDrift 15s infinite linear;
        }

        @keyframes particleDrift {
            0% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            90% {
                opacity: 1;
            }

            100% {
                transform: translateY(-100vh) translateX(20px);
                opacity: 0;
            }
        }

        /* ---------- MAIN CARD ---------- */
        .auth-wrapper {
            position: relative;
            z-index: 20;
            width: 100%;
            max-width: 1100px;
            padding: 20px;
        }

        .auth-card {
            position: relative;
            background: var(--glass-bg);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-radius: 40px;
            border: 1px solid var(--glass-border);
            box-shadow: var(--card-shadow), 0 0 40px rgba(255, 165, 0, 0.3);
            overflow: hidden;
        }

        /* Floating border glow */
        .auth-card::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 40px;
            padding: 2px;
            background: linear-gradient(145deg, transparent 20%, var(--popcorn-orange), transparent 80%);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
            opacity: 0.5;
            animation: borderRotate 8s infinite linear;
        }

        @keyframes borderRotate {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Two-column layout for forms */
        .forms-row {
            display: flex;
            width: 100%;
            min-height: 650px;
        }

        .form-col {
            flex: 1;
            padding: 50px 45px;
            transition: all 0.3s;
        }

        /* ---------- FORM STYLES (premium minimal) ---------- */
        .form-title {
            font-family: "Clash Display", sans-serif;
            font-size: 36px;
            font-weight: 600;
            letter-spacing: -0.02em;
            margin-bottom: 40px;
            background: linear-gradient(135deg, #fff, var(--popcorn-gold));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            position: relative;
            display: inline-block;
        }

        .form-title::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 0;
            width: 70px;
            height: 4px;
            background: linear-gradient(90deg, var(--popcorn-orange), transparent);
            border-radius: 2px;
        }

        .input-group {
            margin-bottom: 28px;
        }

        .input-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--gray-soft);
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--popcorn-gold);
            font-size: 18px;
            transition: all 0.3s;
            z-index: 2;
        }

        .input-wrapper input {
            width: 100%;
            padding: 18px 20px 18px 54px;
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 165, 0, 0.2);
            border-radius: 28px;
            font-size: 16px;
            color: white;
            transition: all 0.4s cubic-bezier(0.2, 0.9, 0.3, 1.2);
            backdrop-filter: blur(4px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--popcorn-orange);
            box-shadow: 0 0 30px var(--popcorn-orange), 0 4px 20px rgba(255, 165, 0, 0.5);
            background: rgba(20, 30, 50, 0.8);
            transform: scale(1.01);
        }

        .input-wrapper input:focus+i {
            color: white;
            text-shadow: 0 0 20px var(--popcorn-orange);
        }

        .btn {
            width: 100%;
            padding: 18px 24px;
            border-radius: 36px;
            font-weight: 700;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            border: none;
            cursor: pointer;
            background: linear-gradient(135deg, var(--popcorn-orange), var(--popcorn-dark));
            color: white;
            position: relative;
            overflow: hidden;
            transition: all 0.4s;
            box-shadow: 0 15px 25px -5px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.1);
            transform: translateZ(0);
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.7s;
        }

        .btn:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 25px 35px -5px rgba(255, 165, 0, 0.7), 0 0 40px var(--popcorn-orange);
        }

        .btn:hover::before {
            left: 100%;
        }

        .error-msg,
        .success-msg {
            padding: 16px 22px;
            border-radius: 22px;
            margin-bottom: 30px;
            font-size: 14px;
            font-weight: 500;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: slideDown 0.4s ease;
        }

        .error-msg {
            background: rgba(239, 68, 68, 0.15);
            border-left: 6px solid var(--error-red);
            color: #fecaca;
        }

        .success-msg {
            background: rgba(16, 185, 129, 0.15);
            border-left: 6px solid var(--success-green);
            color: #a7f3d0;
        }

        @keyframes slideDown {
            0% {
                opacity: 0;
                transform: translateY(-20px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ---------- SLIDING OVERLAY PANEL ---------- */
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 50%;
            height: 100%;
            background: rgba(20, 30, 45, 0.7);
            backdrop-filter: blur(20px) saturate(200%);
            -webkit-backdrop-filter: blur(20px) saturate(200%);
            border-radius: 40px;
            border: 1px solid rgba(255, 165, 0, 0.4);
            box-shadow: 0 0 50px rgba(255, 165, 0, 0.3), inset 0 0 30px rgba(255, 217, 102, 0.3);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 40px;
            transition: left 0.8s cubic-bezier(0.34, 1.3, 0.3, 1);
            will-change: left;
            z-index: 30;
            overflow: hidden;
        }

        /* Overlay content */
        .overlay-content {
            max-width: 320px;
        }

        .overlay-title {
            font-family: "Clash Display", sans-serif;
            font-size: 44px;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 20px;
            background: linear-gradient(145deg, #fff, var(--popcorn-gold));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 0 30px rgba(255, 217, 102, 0.5);
        }

        .overlay-desc {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .overlay-btn {
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.15);
            color: white;
            padding: 16px 48px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 20px -10px black;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            position: relative;
            overflow: hidden;
        }

        .overlay-btn:hover {
            border-color: var(--popcorn-orange);
            background: rgba(255, 165, 0, 0.2);
            box-shadow: 0 0 40px var(--popcorn-orange);
            transform: scale(1.05);
        }

        /* Overlay right state */
        .auth-card.overlay-right .overlay {
            left: 50%;
        }

        /* Switch content based on overlay position */
        .overlay-content[data-state="left"] {
            display: block;
        }

        .overlay-content[data-state="right"] {
            display: none;
        }

        .auth-card.overlay-right .overlay-content[data-state="left"] {
            display: none;
        }

        .auth-card.overlay-right .overlay-content[data-state="right"] {
            display: block;
        }

        /* Diagonal light streak */
        .overlay::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.1) 0%, transparent 60%);
            opacity: 0.3;
            pointer-events: none;
        }

        /* ---------- RESPONSIVE ---------- */
        @media (max-width: 900px) {
            .forms-row {
                min-height: 600px;
            }

            .form-col {
                padding: 40px 30px;
            }

            .overlay-title {
                font-size: 36px;
            }
        }

        @media (max-width: 700px) {
            .auth-wrapper {
                padding: 10px;
            }

            .forms-row {
                flex-direction: column;
            }

            .form-col {
                width: 100%;
            }

            .overlay {
                width: 100%;
                height: 50%;
                left: 0 !important;
                top: 0;
                border-radius: 40px 40px 0 0;
            }

            .auth-card.overlay-right .overlay {
                top: 50%;
                left: 0 !important;
                border-radius: 0 0 40px 40px;
            }
        }

        /* ---------- UTILS ---------- */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
        }
    </style>
</head>

<body>
    <!-- Cosmic background layers (now gold/orange) -->
    <div class="cosmic-bg">
        <div class="gradient-orb orb-1"></div>
        <div class="gradient-orb orb-2"></div>
        <div class="gradient-orb orb-3"></div>
        <div class="noise"></div>
        <div class="particles" id="particles"></div>
    </div>

    <div class="auth-wrapper" id="authWrapper">
        <!-- Main card (no tilt effect) -->
        <div class="auth-card" id="authCard">
            <!-- Forms row (exact fields preserved) -->
            <div class="forms-row">
                <!-- LOGIN COLUMN (left) -->
                <div class="form-col">
                    <h2 class="form-title">Sign In</h2>
                    <?php if (isset($login_error)): ?>
                        <div class="error-msg"><?= htmlspecialchars($login_error) ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="input-group">
                            <label>Email address</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="login_email" placeholder="name@domain.com" required>
                            </div>
                        </div>
                        <div class="input-group">
                            <label>Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="login_password" placeholder="••••••••" required>
                            </div>
                        </div>
                        <button type="submit" name="login" class="btn">Log in</button>
                    </form>
                </div>

                <!-- REGISTER COLUMN (right) -->
                <div class="form-col">
                    <h2 class="form-title">Create account</h2>
                    <?php if (!empty($errors)): ?>
                        <div class="error-msg">
                            <?php foreach ($errors as $err): ?>
                                <?= htmlspecialchars($err) ?><br>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif (isset($reg_success)): ?>
                        <div class="success-msg"><?= htmlspecialchars($reg_success) ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="input-group">
                            <label>Full name</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user"></i>
                                <input type="text" name="reg_name" placeholder="John Doe" required
                                    pattern="[A-Za-z\s]{2,50}" title="2-50 letters and spaces only">
                            </div>
                        </div>
                        <div class="input-group">
                            <label>Email address</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="reg_email" placeholder="name@domain.com" required>
                            </div>
                        </div>
                        <div class="input-group">
                            <label>Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="reg_password" placeholder="••••••••" required
                                    minlength="6">
                            </div>
                        </div>
                        <div class="input-group">
                            <label>Confirm password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="reg_confirm" placeholder="••••••••" required minlength="6">
                            </div>
                        </div>
                        <button type="submit" name="register" class="btn">Register</button>
                    </form>
                </div>
            </div>

            <!-- OVERLAY PANEL with dual content (buttons now correctly mapped) -->
            <div class="overlay">
                <!-- Left content: visible when overlay on left (register form visible) -->
                <div class="overlay-content" data-state="left">
                    <h3 class="overlay-title">New here</h3>
                    <p class="overlay-desc">Create an account and unlock a universe of possibilities.</p>
                    <button class="overlay-btn" id="switchToLogin">Sign in</button>
                </div>
                <!-- Right content: visible when overlay on right (login form visible) -->
                <div class="overlay-content" data-state="right">
                    <h3 class="overlay-title">Welcome back</h3>
                    <p class="overlay-desc">Enter your credentials to continue your journey among the stars.</p>
                    <button class="overlay-btn" id="switchToRegister">Join now</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // ===== OVERLAY CONTROL =====
            const authCard = document.getElementById('authCard');
            const switchToRegister = document.getElementById('switchToRegister');
            const switchToLogin = document.getElementById('switchToLogin');

            // Set initial state from PHP
            const initialPanel = '<?php echo $initialPanel; ?>'; // 'login' or 'register'
            if (initialPanel === 'register') {
                authCard.classList.remove('overlay-right'); // overlay left → register visible
                console.log('Initial state: register (overlay left)');
            } else {
                authCard.classList.add('overlay-right'); // overlay right → login visible
                console.log('Initial state: login (overlay right)');
            }

            // Toggle functions
            switchToRegister.addEventListener('click', function () {
                authCard.classList.remove('overlay-right'); // moves overlay left, showing register
                console.log('Switched to register (overlay left)');
            });

            switchToLogin.addEventListener('click', function () {
                authCard.classList.add('overlay-right'); // moves overlay right, showing login
                console.log('Switched to login (overlay right)');
            });

            // ===== PARTICLE FIELD GENERATION =====
            const particlesDiv = document.getElementById('particles');
            for (let i = 0; i < 60; i++) {
                const p = document.createElement('div');
                p.className = 'particle';
                const size = Math.random() * 3 + 1;
                p.style.width = size + 'px';
                p.style.height = size + 'px';
                p.style.left = Math.random() * 100 + '%';
                p.style.top = Math.random() * 100 + '%';
                p.style.animationDelay = Math.random() * 8 + 's';
                p.style.animationDuration = (Math.random() * 15 + 15) + 's';
                particlesDiv.appendChild(p);
            }
        });
    </script>
</body>

</html>
