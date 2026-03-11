<?php
session_start();
require_once '../db_connect.php';
require_once '../settings_init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
  header('Location: ../login.php');
  exit;
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Feedback · <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box
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
      --transition: all .3s ease;
    }

    body {
      font-family: Heebo, sans-serif;
      background: #f8f9fa;
      color: #212529;
      transition: var(--transition);
      overflow-x: hidden;
      line-height: 1.6
    }

    body.dark-mode {
      background: #0B1623;
      color: #F2F2F2
    }

    .sidebar-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 999
    }

    .sidebar-overlay.active {
      display: block
    }

    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      width: var(--sidebar-width);
      background: var(--light-card);
      box-shadow: 2px 0 20px rgba(0, 0, 0, .05);
      transition: transform var(--transition), width var(--transition);
      z-index: 1000;
      overflow-y: auto;
      border-right: 1px solid var(--border-light);
      transform: translateX(-100%)
    }

    .sidebar.active {
      transform: translateX(0)
    }

    .dark-mode .sidebar {
      background: var(--dark-card);
      border-right-color: var(--border-dark)
    }

    .sidebar.collapsed {
      width: var(--sidebar-collapsed-width)
    }

    .sidebar .logo-area {
      padding: 24px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--border-light)
    }

    .dark-mode .sidebar .logo-area {
      border-bottom-color: var(--border-dark)
    }

    .sidebar .logo {
      font-size: 22px;
      font-weight: 700;
      color: var(--primary-gold);
      white-space: nowrap;
      overflow: hidden
    }

    .sidebar.collapsed .logo span {
      display: none
    }

    .sidebar .toggle-btn {
      background: none;
      border: none;
      color: var(--light-text);
      cursor: pointer;
      font-size: 20px;
      transition: color .2s
    }

    .dark-mode .sidebar .toggle-btn {
      color: var(--dark-text)
    }

    .sidebar .toggle-btn:hover {
      color: var(--primary)
    }

    .sidebar .nav {
      padding: 12px 0 96px;
      display: block
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
      white-space: nowrap
    }

    .dark-mode .sidebar .nav-link {
      color: var(--dark-text)
    }

    .sidebar .nav-link i {
      font-size: 17px;
      min-width: 24px;
      text-align: center
    }

    .sidebar.collapsed .nav-link span {
      display: none
    }

    .sidebar .nav-link:hover {
      background: rgba(255, 165, 0, 0.1);
      color: var(--primary)
    }

    .sidebar .nav-link.active {
      background: var(--primary);
      color: #fff
    }

    .dark-mode .sidebar .nav-link.active {
      background: var(--primary-dark)
    }

    .sidebar .bottom-section {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      padding: 14px;
      border-top: 1px solid var(--border-light);
      background: inherit
    }

    .dark-mode .sidebar .bottom-section {
      border-top-color: var(--border-dark)
    }

    .main-content {
      margin-left: 0;
      padding: 20px 24px;
      transition: margin-left var(--transition), width var(--transition);
      min-height: 100vh;
      width: 100%
    }

    @media(min-width:992px) {
      .sidebar {
        transform: translateX(0)
      }

      .main-content {
        margin-left: var(--sidebar-width);
        width: calc(100% - var(--sidebar-width))
      }

      body.sidebar-collapsed .main-content {
        margin-left: var(--sidebar-collapsed-width);
        width: calc(100% - var(--sidebar-collapsed-width))
      }
    }

    .top-navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 0;
      margin-bottom: 20px;
      flex-wrap: wrap;
      gap: 15px
    }

    .menu-toggle-mobile {
      font-size: 24px;
      cursor: pointer;
      display: inline-block
    }

    @media(min-width:992px) {
      .menu-toggle-mobile {
        display: none
      }
    }

    .nav-icons {
      display: flex;
      align-items: center;
      gap: 20px
    }

    .nav-icons .icon {
      position: relative;
      font-size: 22px;
      color: var(--light-text);
      cursor: pointer;
      transition: color .2s
    }

    .dark-mode .nav-icons .icon {
      color: var(--dark-text)
    }

    .nav-icons .icon:hover {
      color: var(--primary)
    }

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
      justify-content: center
    }

    .theme-toggle {
      cursor: pointer;
      font-size: 20px
    }

    .avatar-icon {
      font-size: 2.2rem;
      color: var(--primary);
      cursor: pointer;
      transition: color .2s
    }

    .avatar-icon:hover {
      color: var(--primary-dark)
    }

    .card {
      border-radius: 16px;
      border: none;
      background: var(--light-card);
      box-shadow: 0 10px 30px rgba(0, 0, 0, .05)
    }

    .dark-mode .card {
      background: var(--dark-card)
    }

    .dark-mode .text-muted {
      color: #adb5bd !important
    }

    .footer {
      background: var(--light-card);
      border-top: 1px solid var(--border-light);
      padding: 30px 0;
      margin-top: 40px;
      color: #6c757d
    }

    .dark-mode .footer {
      background: var(--dark-card);
      border-top-color: var(--border-dark);
      color: #adb5bd
    }

    .dropdown-menu {
      background: var(--light-card);
      border: 1px solid var(--border-light);
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      max-height: 400px;
      overflow-y: auto
    }

    .dark-mode .dropdown-menu {
      background: var(--dark-card);
      border-color: var(--border-dark)
    }

    .dropdown-item {
      color: var(--light-text)
    }

    .dark-mode .dropdown-item,
    .dark-mode .dropdown-header,
    .dark-mode .dropdown-item-text {
      color: var(--dark-text)
    }

    @media(max-width:768px) {
      .top-navbar {
        flex-direction: column;
        align-items: stretch
      }
    }
  </style>
  <style id="admin-sidebar-unify">
    .sidebar {
      transition: width .28s ease, transform .28s ease;
      will-change: width, transform
    }

    .main-content {
      transition: margin-left .28s ease, width .28s ease
    }

    .sidebar .logo span,
    .sidebar .nav-link span {
      transition: opacity .22s ease, max-width .22s ease, margin .22s ease;
      max-width: 180px;
      overflow: hidden
    }

    .sidebar.collapsed {
      width: var(--sidebar-collapsed, var(--sidebar-collapsed-width, 80px)) !important;
      min-width: var(--sidebar-collapsed, var(--sidebar-collapsed-width, 80px)) !important;
      max-width: var(--sidebar-collapsed, var(--sidebar-collapsed-width, 80px)) !important
    }

    .sidebar.collapsed .logo span,
    .sidebar.collapsed .nav-link span {
      opacity: 0;
      max-width: 0;
      margin: 0
    }

    #sidebarToggle i {
      transition: transform .25s ease
    }

    body.sidebar-collapsed #sidebarToggle i {
      transform: rotate(180deg)
    }

    .search-bar {
      display: none !important
    }

    .top-navbar {
      justify-content: flex-end;
      gap: 12px
    }

    @media(max-width:991.98px) {
      .main-content {
        margin-left: 0 !important;
        width: 100% !important
      }

      .top-navbar {
        flex-wrap: wrap
      }
    }
  </style>
</head>

<body>
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="top-navbar">
      <div class="d-flex align-items-center"><i class="bi bi-list menu-toggle-mobile me-3" id="mobileMenuToggle"></i>
        <h4 class="m-0">Feedback</h4>
      </div>
      <div class="nav-icons">
        <div class="dropdown d-inline-block">
          <div class="icon position-relative" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false"
            role="button">
            <i class="bi bi-bell"></i>
            <span class="badge" id="notificationBadge" style="display: none;">0</span>
          </div>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown" style="width: 300px;">
            <li>
              <h6 class="dropdown-header">Notifications</h6>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li id="notificationList">Loading...</li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item text-center small" href="#" id="markAllRead">Mark all as read</a></li>
          </ul>
        </div>
        <div class="theme-toggle" id="themeToggle"><i class="bi bi-moon"></i></div>
        <i class="bi bi-person-circle avatar-icon"></i>
      </div>
    </div>
    <div class="card p-4">
      <h5 class="mb-2">Feedback Module Restored</h5>
      <p class="text-muted mb-3">This page has been restored safely. Use Messages to view and respond to incoming
        feedback.</p>
      <a href="messages.php" class="btn btn-primary"><i class="bi bi-chat-dots me-1"></i>Open Messages</a>
    </div>
    <footer class="footer text-center">
      <div class="container">
        <p class="small">
          <?= htmlspecialchars($settings['footer_text'] ?? '© ' . date('Y') . ' Popcorn Hub. All rights reserved.') ?></p>
      </div>
    </footer>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileToggle = document.getElementById('mobileMenuToggle');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar && sidebarToggle) { sidebarToggle.addEventListener('click', () => { sidebar.classList.toggle('collapsed'); document.body.classList.toggle('sidebar-collapsed'); }); }
    if (mobileToggle && sidebar && overlay) { mobileToggle.addEventListener('click', () => { sidebar.classList.add('active'); overlay.classList.add('active'); }); overlay.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); }); }
    function updateNotifications() { fetch('get_notifications.php').then(res => res.json()).then(data => { const badge = document.getElementById('notificationBadge'); const list = document.getElementById('notificationList'); if (!badge || !list) return; if (data.notifications && data.notifications.length > 0) { badge.textContent = data.notifications.length; badge.style.display = 'flex'; list.innerHTML = ''; data.notifications.forEach(notif => { const item = document.createElement('li'); item.innerHTML = `<a class="dropdown-item" href="${notif.link}">${notif.message}<br><small>${new Date(notif.created_at).toLocaleString()}</small></a>`; list.appendChild(item); }); } else { badge.style.display = 'none'; list.innerHTML = '<li><span class="dropdown-item-text text-muted">No new notifications</span></li>'; } }); }
    updateNotifications(); setInterval(updateNotifications, 30000);
    const markAllRead = document.getElementById('markAllRead');
    if (markAllRead) { markAllRead.addEventListener('click', function (e) { e.preventDefault(); fetch('mark_notifications_read.php', { method: 'POST' }).then(updateNotifications); }); }
  </script>
</body>

</html>