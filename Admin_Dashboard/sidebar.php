<?php
// sidebar.php - shared sidebar for all admin pages
?>
<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="logo-area">
        <div class="logo">
            <i class="bi bi-camera-reels me-2"></i>
            <span><?= htmlspecialchars($settings['site_name'] ?? 'Popcorn Hub') ?></span>
        </div>
        <button class="toggle-btn" id="sidebarToggle"><i class="bi bi-chevron-left"></i></button>
    </div>

    <div class="nav">
        <a href="dashboard.php" class="nav-link" title="Dashboard">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>

        <a href="movies.php" class="nav-link" title="Movies">
            <i class="bi bi-film"></i>
            <span>Movies</span>
        </a>

        <div class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#theatresSubmenu" role="button" aria-expanded="false" aria-controls="theatresSubmenu" title="Theatres">
                <i class="bi bi-building"></i>
                <span>Theatres</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <div class="collapse" id="theatresSubmenu">
                <a href="theatres.php" class="nav-link submenu-link" title="All Theatres">
                    <i class="bi bi-list-ul"></i>
                    <span>All Theatres</span>
                </a>
                <a href="add_theatre.php" class="nav-link submenu-link" title="Add Theatre">
                    <i class="bi bi-plus-circle"></i>
                    <span>Add Theatre</span>
                </a>

            </div>
        </div>

        <a href="bookings.php" class="nav-link" title="Bookings">
            <i class="bi bi-ticket"></i>
            <span>Bookings</span>
        </a>

        <div class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#usersSubmenu" role="button" aria-expanded="false" aria-controls="usersSubmenu" title="Users">
                <i class="bi bi-people"></i>
                <span>Users</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <div class="collapse" id="usersSubmenu">
                <a href="users.php" class="nav-link submenu-link" title="All Users">
                    <i class="bi bi-list-ul"></i>
                    <span>All Users</span>
                </a>
                <a href="add_user.php" class="nav-link submenu-link" title="Add User">
                    <i class="bi bi-plus-circle"></i>
                    <span>Add User</span>
                </a>
            </div>
        </div>

        <a href="analytics.php" class="nav-link" title="Analytics">
            <i class="bi bi-graph-up"></i>
            <span>Analytics</span>
        </a>

        <a href="messages.php" class="nav-link" title="Messages">
            <i class="bi bi-chat-dots"></i>
            <span>Messages</span>
        </a>

        <a href="votes.php" class="nav-link" title="Voting">
            <i class="bi bi-bar-chart"></i>
            <span>Voting</span>
        </a>

        <div class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#settingsSubmenu" role="button" aria-expanded="false" aria-controls="settingsSubmenu" title="Settings">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <div class="collapse" id="settingsSubmenu">
                <a href="settings.php" class="nav-link submenu-link" title="General Settings">
                    <i class="bi bi-sliders2"></i>
                    <span>General</span>
                </a>
                <a href="email_settings.php" class="nav-link submenu-link" title="Email Settings">
                    <i class="bi bi-envelope"></i>
                    <span>Email</span>
                </a>
            </div>
        </div>
    </div>

    <div class="bottom-section">
        <a href="../logout.php" class="nav-link" title="Logout">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<!-- Unified Toggle CSS -->
<link rel="stylesheet" href="admin_toggle.css">


