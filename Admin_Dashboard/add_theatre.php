<?php
session_start();
require_once '../db_connect.php';
require_once '../settings_init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $rating = (float)($_POST['rating'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $image_url = trim($_POST['image_url'] ?? '');
    $facilities = $_POST['facilities'] ?? [];

    if ($name === '' || $city === '' || $location === '') {
        $error = 'Name, city and location are required.';
    } else {
        $facilities_json = json_encode(array_values($facilities));
        $stmt = $conn->prepare('INSERT INTO theatres (name, city, location, rating, price, facilities, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)');
        if ($stmt) {
            $stmt->bind_param('sssddss', $name, $city, $location, $rating, $price, $facilities_json, $image_url);
            if ($stmt->execute()) {
                header('Location: theatres.php?added=1');
                exit;
            }
            $error = 'Failed to add theatre.';
            $stmt->close();
        } else {
            $error = 'Database error.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Add Theatre � <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
   <style>
        /* ========== UNIFIED ADMIN CSS (same as dashboard) ========== */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Heebo', sans-serif;
            background-color: #F8F9FA;
            color: #212529;
            transition: all 0.3s ease;
            overflow-x: hidden;
            line-height: 1.6;
        }
        body.dark-mode {
            background-color: #0B1623;
            color: #F2F2F2;
        }
        :root { 
            --primary: #FFA500;
            --primary-dark: #cc7f00;
            --primary-gold: #FFD966;
            --light-card: #FFFFFF;
            --dark-card: #0F1C2B;
            --light-text: #212529;
            --dark-text: #F2F2F2;
            --border-light: #E9ECEF;
            --border-dark: #3A414D;
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 80px;
            --transition: all 0.3s ease;
        }

        /* ===== SIDEBAR OVERLAY ===== */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        .sidebar-overlay.active { display: block; }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--light-card);
            box-shadow: 2px 0 20px rgba(0,0,0,0.05);
            transition: transform var(--transition), width var(--transition);
            z-index: 1000;
            overflow-y: auto;
            border-right: 1px solid var(--border-light);
            transform: translateX(-100%);
        }
        .sidebar.active { transform: translateX(0); }
        .dark-mode .sidebar {
            background: var(--dark-card);
            border-right-color: var(--border-dark);
        }
        .sidebar.collapsed { width: var(--sidebar-collapsed-width); }

        .sidebar .logo-area {
            padding: 24px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-light);
        }
        .dark-mode .sidebar .logo-area { border-bottom-color: var(--border-dark); }
        .sidebar .logo {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-gold);
            white-space: nowrap;
            overflow: hidden;
        }
        .sidebar.collapsed .logo span { display: none; }
        .sidebar .toggle-btn {
            background: none;
            border: none;
            color: var(--light-text);
            cursor: pointer;
            font-size: 20px;
            transition: color 0.2s;
        }
        .dark-mode .sidebar .toggle-btn { color: var(--dark-text); }
        .sidebar .toggle-btn:hover { color: var(--primary); }

        .sidebar .nav {
            padding: 12px 0 96px;
            display: block;
        }
        .sidebar .nav-link {
            display: flex;
            align-items: center;
            padding: 9px 16px;
            color: var(--light-text);
            text-decoration: none;
            border-radius: 0 30px 30px 0;
            margin-right: 10px;
            transition: var(--transition);
            white-space: nowrap;
        }
        .dark-mode .sidebar .nav-link { color: var(--dark-text); }
        .sidebar .nav-link i { font-size: 17px; min-width: 24px; text-align: center; }
        .sidebar .nav-link span {
            transition: opacity 0.2s, width 0.2s;
            opacity: 1;
            width: auto;
            overflow: hidden;
            white-space: nowrap;
        }
        .sidebar.collapsed .nav-link span {
            opacity: 0;
            width: 0;
        }
        .sidebar .nav-link:hover { background: rgba(255,165,0,0.1); color: var(--primary); }
        .sidebar .nav-link.active { background: var(--primary); color: #fff; }
        .dark-mode .sidebar .nav-link.active { background: var(--primary-dark); }

        /* Submenu */
        .nav-item { width: 100%; }
        .nav-link[data-bs-toggle="collapse"] {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .nav-link[data-bs-toggle="collapse"] i.bi-chevron-down { transition: transform 0.3s; }
        .nav-link[data-bs-toggle="collapse"][aria-expanded="true"] i.bi-chevron-down { transform: rotate(180deg); }
        .submenu-link { padding-left: 42px !important; font-size: 13px; }
        .submenu-link i { font-size: 14px; min-width: 20px; }

        .sidebar .bottom-section {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 14px;
            border-top: 1px solid var(--border-light);
            background: inherit;
        }
        .dark-mode .sidebar .bottom-section { border-top-color: var(--border-dark); }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: 0;
            padding: 20px 30px;
            transition: margin-left var(--transition), width var(--transition);
            min-height: 100vh;
            width: 100%;
            overflow-x: hidden;
        }

        @media (min-width: 992px) {
            .sidebar { transform: translateX(0); }
            .main-content {
                margin-left: var(--sidebar-width);
                width: calc(100% - var(--sidebar-width));
            }
            body.sidebar-collapsed .main-content {
                margin-left: var(--sidebar-collapsed-width);
                width: calc(100% - var(--sidebar-collapsed-width));
            }
        }

        @media (max-width: 991px) {
            .main-content { margin-left: 0; width: 100%; }
        }

        /* ===== TOP NAVBAR ===== */
        .top-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .menu-toggle {
            font-size: 24px;
            cursor: pointer;
            display: inline-block;
        }
        .search-bar {
            position: relative;
            width: 300px;
            max-width: 100%;
        }
        .search-bar input {
            width: 100%;
            padding: 12px 40px 12px 20px;
            border-radius: 40px;
            border: 1px solid var(--border-light);
            background: var(--light-card);
            color: var(--light-text);
            transition: var(--transition);
        }
        .dark-mode .search-bar input {
            background: var(--dark-card);
            border-color: var(--border-dark);
            color: var(--dark-text);
        }
        .search-bar input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255,165,0,0.2);
        }
        .search-bar i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        .nav-icons {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .nav-icons .icon {
            position: relative;
            font-size: 22px;
            color: var(--light-text);
            cursor: pointer;
            transition: color 0.2s;
        }
        .dark-mode .nav-icons .icon { color: var(--dark-text); }
        .nav-icons .icon:hover { color: var(--primary); }
        .nav-icons .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--primary);
            color: #fff;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .avatar-icon {
            font-size: 2.2rem;
            color: var(--primary);
            cursor: pointer;
            transition: color 0.2s;
        }
        .avatar-icon:hover { color: var(--primary-dark); }

        /* ===== CARDS ===== */
        .card {
            border: none;
            border-radius: 20px;
            padding: 14px;
            background: var(--light-card);
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: var(--transition);
            margin-bottom: 20px;
        }
        .dark-mode .card {
            background: var(--dark-card);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .badge-success { background: rgba(40,167,69,0.15); color: #28a745; }
        .badge-warning { background: rgba(255,193,7,0.15); color: #ffc107; }
        .badge-danger { background: rgba(220,53,69,0.15); color: #dc3545; }
        .badge-info { background: rgba(23,162,184,0.15); color: #17a2b8; }
        .badge-primary { background: rgba(255,165,0,0.15); color: var(--primary); }

        .btn-primary {
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            color: #fff;
            border: none;
            border-radius: 40px;
            padding: 10px 24px;
            box-shadow: 0 4px 14px rgba(255,165,0,0.3);
            transition: all 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255,165,0,0.5);
        }
        .btn-outline-primary {
            border: 1px solid var(--primary);
            color: var(--primary);
            background: transparent;
        }
        .btn-outline-primary:hover {
            background: var(--primary);
            color: #fff;
        }
        .btn-outline-danger {
            border: 1px solid #dc3545;
            color: #dc3545;
            background: transparent;
        }
        .btn-outline-danger:hover {
            background: #dc3545;
            color: #fff;
        }
        .btn-sm {
            padding: 6px 16px;
            font-size: 13px;
        }

        /* ===== TABLES ===== */
        .table-responsive {
            overflow-x: auto;
            min-width: 100%;
            border-radius: 20px;
            background: var(--light-card);
            padding: 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid var(--border-light);
        }
        .dark-mode .table-responsive {
            background: var(--dark-card);
            border-color: var(--border-dark);
        }
        .table {
            width: 100%;
            margin-bottom: 0;
            color: var(--light-text);
            background: transparent;
        }
        .dark-mode .table { color: var(--dark-text); }
        .table th {
            background: rgba(0,0,0,0.02);
            border-bottom: 2px solid var(--border-light);
            font-weight: 600;
            color: #6c757d;
            padding: 15px 12px;
            white-space: nowrap;
        }
        .dark-mode .table th {
            background: rgba(255,255,255,0.02);
            border-bottom-color: var(--border-dark);
            color: #adb5bd;
        }
        .table td {
            border-bottom: 1px solid var(--border-light);
            padding: 12px;
            vertical-align: middle;
        }
        .dark-mode .table td { border-bottom-color: var(--border-dark); }
        .table tbody tr:last-child td { border-bottom: none; }
        .table tbody tr:hover { background: rgba(0,0,0,0.02); }
        .dark-mode .table tbody tr:hover { background: rgba(255,255,255,0.02); }

        /* ===== FOOTER ===== */
        .footer {
            background: var(--light-card);
            border-top: 1px solid var(--border-light);
            padding: 30px 0;
            margin-top: 60px;
            color: #6c757d;
        }
        .dark-mode .footer {
            background: var(--dark-card);
            border-top-color: var(--border-dark);
            color: #adb5bd;
        }

        /* ===== DROPDOWNS ===== */
        .dropdown-menu {
            background: var(--light-card);
            border: 1px solid var(--border-light);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-height: 400px;
            overflow-y: auto;
        }
        .dark-mode .dropdown-menu {
            background: var(--dark-card);
            border-color: var(--border-dark);
        }
        .dropdown-item {
            color: var(--light-text);
            white-space: normal;
            word-wrap: break-word;
        }
        .dark-mode .dropdown-item { color: var(--dark-text); }
        .dropdown-item:hover { background: rgba(255,165,0,0.1); }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .sidebar { left: -100%; }
            .sidebar.active { left: 0; }
            .main-content { margin-left: 0 !important; width: 100% !important; }
            .search-bar { width: 250px; }
        }
        @media (max-width: 768px) {
            .top-navbar { flex-direction: column; align-items: stretch; }
            .search-bar { width: 100%; }
            .nav-icons { justify-content: flex-end; }
            .table th, .table td { padding: 10px 8px; font-size: 14px; }
        }
    </style>
    <style id="admin-sidebar-unify">
        /* Unified admin sidebar animation + responsive behavior */
        .sidebar {
            transition: width 0.28s ease, transform 0.28s ease;
            will-change: width, transform;
        }
        .main-content {
            transition: margin-left 0.28s ease, width 0.28s ease;
        }
        .sidebar .logo span,
        .sidebar .nav-link span {
            transition: opacity 0.22s ease, max-width 0.22s ease, margin 0.22s ease;
            max-width: 180px;
            overflow: hidden;
        }
        .sidebar.collapsed {
            width: var(--sidebar-collapsed, var(--sidebar-collapsed-width, 80px)) !important;
            min-width: var(--sidebar-collapsed, var(--sidebar-collapsed-width, 80px)) !important;
            max-width: var(--sidebar-collapsed, var(--sidebar-collapsed-width, 80px)) !important;
        }
        .sidebar.collapsed .logo span,
        .sidebar.collapsed .nav-link span {
            opacity: 0;
            max-width: 0;
            margin: 0;
        }
        #sidebarToggle i {
            transition: transform 0.25s ease;
        }
        body.sidebar-collapsed #sidebarToggle i {
            transform: rotate(180deg);
        }

        /* Remove admin search bars everywhere */
        .search-bar {
            display: none !important;
        }
        .top-navbar {
            justify-content: flex-end;
            gap: 12px;
        }

        /* Extra safety for small screens */
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
            .top-navbar {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
    <div class="top-navbar">
        <div class="d-flex align-items-center"><i class="bi bi-list menu-toggle-mobile me-3" id="mobileMenuToggle"></i><h4 class="m-0">Add Theatre</h4></div>
        <div class="theme-toggle" id="themeToggle"><i class="bi bi-moon"></i></div>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card p-4">
        <form method="post">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Name</label><input class="form-control" name="name" required></div>
                <div class="col-md-4"><label class="form-label">City</label><input class="form-control" name="city" required></div>
                <div class="col-md-4"><label class="form-label">Location</label><input class="form-control" name="location" required></div>
                <div class="col-md-3"><label class="form-label">Rating</label><input type="number" step="0.1" min="0" max="5" class="form-control" name="rating" value="4.0"></div>
                <div class="col-md-3"><label class="form-label">Price</label><input type="number" step="0.01" min="0" class="form-control" name="price" value="500"></div>
                <div class="col-md-6"><label class="form-label">Image URL</label><input class="form-control" name="image_url"></div>
                <div class="col-12">
                    <label class="form-label d-block">Facilities</label>
                    <?php foreach (['3D','IMAX','Dolby Atmos','VIP Seats','Food Court','Parking'] as $fac): ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="facilities[]" value="<?= $fac ?>" id="fac_<?= md5($fac) ?>">
                            <label class="form-check-label" for="fac_<?= md5($fac) ?>"><?= $fac ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Save Theatre</button>
                <a class="btn btn-outline-secondary" href="theatres.php">Back</a>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const mobileToggle = document.getElementById('mobileMenuToggle');
const overlay = document.getElementById('sidebarOverlay');
if (sidebar && sidebarToggle) {
  sidebarToggle.addEventListener('click', () => { sidebar.classList.toggle('collapsed'); document.body.classList.toggle('sidebar-collapsed'); });
}
if (mobileToggle && sidebar && overlay) {
  mobileToggle.addEventListener('click', () => { sidebar.classList.add('active'); overlay.classList.add('active'); });
  overlay.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
}
const themeToggle = document.getElementById('themeToggle');
if (themeToggle) {
  themeToggle.addEventListener('click', function(){ document.body.classList.toggle('dark-mode'); const i=this.querySelector('i'); if(i){i.classList.toggle('bi-moon'); i.classList.toggle('bi-sun');} });
}
</script>
</body>
</html>
