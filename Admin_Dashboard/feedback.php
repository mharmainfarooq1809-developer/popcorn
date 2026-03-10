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
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Feedback · <?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
body{font-family:Heebo,sans-serif;background:#f8f9fa}.main-content{margin-left:0;padding:20px 24px}@media(min-width:992px){.main-content{margin-left:250px}}
.top-navbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}.menu-toggle-mobile{font-size:24px;cursor:pointer}@media(min-width:992px){.menu-toggle-mobile{display:none}}.theme-toggle{cursor:pointer;font-size:20px}
.card{border-radius:16px}
</style>
</head><body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
  <div class="top-navbar">
    <div class="d-flex align-items-center"><i class="bi bi-list menu-toggle-mobile me-3" id="mobileMenuToggle"></i><h4 class="m-0">Feedback</h4></div>
    <div class="theme-toggle" id="themeToggle"><i class="bi bi-moon"></i></div>
  </div>
  <div class="card p-4">
    <h5 class="mb-2">Feedback Module Restored</h5>
    <p class="text-muted mb-3">This page has been restored safely. Use Messages to view and respond to incoming feedback.</p>
    <a href="messages.php" class="btn btn-primary"><i class="bi bi-chat-dots me-1"></i>Open Messages</a>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar=document.getElementById('sidebar'); const sidebarToggle=document.getElementById('sidebarToggle'); const mobileToggle=document.getElementById('mobileMenuToggle'); const overlay=document.getElementById('sidebarOverlay');
if(sidebar&&sidebarToggle){sidebarToggle.addEventListener('click',()=>{sidebar.classList.toggle('collapsed');document.body.classList.toggle('sidebar-collapsed');});}
if(mobileToggle&&sidebar&&overlay){mobileToggle.addEventListener('click',()=>{sidebar.classList.add('active');overlay.classList.add('active');});overlay.addEventListener('click',()=>{sidebar.classList.remove('active');overlay.classList.remove('active');});}
</script>
</body></html>
