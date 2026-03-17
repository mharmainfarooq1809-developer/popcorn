// admin_toggle.js - Unified toggle system for all admin pages

document.addEventListener('DOMContentLoaded', function() {
    initSidebarToggle();
    initThemeToggle();
    initNotificationSystem();
    initCollapsibleSections();
    initMobileMenu();
});

// ========== SIDEBAR TOGGLE (Unified across all pages) ==========
function initSidebarToggle() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const menuToggle = document.getElementById('menuToggle');
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebarToggleBtn = document.getElementById('sidebarToggle');

    function toggleSidebar() {
        if (!sidebar) return;

        if (window.innerWidth >= 992) {
            // Desktop: toggle collapsed class (mini sidebar)
            sidebar.classList.toggle('collapsed');
            document.body.classList.toggle('sidebar-collapsed');

            // Store preference in localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        } else {
            // Mobile: slide sidebar in/out
            sidebar.classList.toggle('active');
            if (overlay) overlay.classList.toggle('active');
        }
    }

    // Event listeners for toggle buttons
    if (menuToggle) {
        menuToggle.addEventListener('click', toggleSidebar);
    }

    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', toggleSidebar);
    }

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.innerWidth >= 992) {
                sidebar.classList.toggle('collapsed');
                document.body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            }
        });
    }

    // Close sidebar when clicking overlay on mobile
    if (overlay) {
        overlay.addEventListener('click', function() {
            if (sidebar) sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }

    // Close sidebar on link click (mobile)
    if (sidebar) {
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('active');
                    if (overlay) overlay.classList.remove('active');
                }
            });
        });
    }

    // Load saved sidebar state from localStorage
    const savedState = localStorage.getItem('sidebarCollapsed');
    if (savedState === 'true' && window.innerWidth >= 992 && sidebar) {
        sidebar.classList.add('collapsed');
        document.body.classList.add('sidebar-collapsed');
    }

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992 && sidebar) {
            sidebar.style.transform = '';
            const savedState = localStorage.getItem('sidebarCollapsed');
            if (savedState === 'true') {
                sidebar.classList.add('collapsed');
                document.body.classList.add('sidebar-collapsed');
            } else {
                sidebar.classList.remove('collapsed');
                document.body.classList.remove('sidebar-collapsed');
            }
        } else if (window.innerWidth < 992 && sidebar) {
            sidebar.classList.remove('active', 'collapsed');
            document.body.classList.remove('sidebar-collapsed');
            if (overlay) overlay.classList.remove('active');
        }
    });
}

// ========== THEME TOGGLE (Dark/Light mode) ==========
function initThemeToggle() {
    const themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) return;

    const themeIcon = themeToggle.querySelector('i');

    function setTheme(isDark) {
        if (isDark) {
            document.body.classList.add('dark-mode');
            if (themeIcon) themeIcon.className = 'bi bi-sun';
            localStorage.setItem('admin-theme', 'dark');
        } else {
            document.body.classList.remove('dark-mode');
            if (themeIcon) themeIcon.className = 'bi bi-moon';
            localStorage.setItem('admin-theme', 'light');
        }

        // Dispatch event for other components to listen
        document.dispatchEvent(new CustomEvent('themeChanged', { detail: { isDark: isDark } }));
    }

    // Load saved theme
    const savedTheme = localStorage.getItem('admin-theme');
    if (savedTheme === 'dark') {
        setTheme(true);
    } else if (savedTheme === 'light') {
        setTheme(false);
    } else {
        // Default: check if dark mode class already exists
        if (document.body.classList.contains('dark-mode')) {
            setTheme(true);
        }
    }

    themeToggle.addEventListener('click', () => {
        const isDark = document.body.classList.contains('dark-mode');
        setTheme(!isDark);
    });
}

// ========== COLLAPSIBLE SECTIONS (Toggle effect for cards) ==========
function initCollapsibleSections() {
    // Find all toggle headers
    const headers = document.querySelectorAll('.card-header-with-toggle');

    headers.forEach(function(header) {
        // Add click event to each header
        header.addEventListener('click', function() {
            toggleSection(this);
        });

        // Check if section should be expanded by default
        const contentId = header.getAttribute('data-target');
        const defaultExpanded = header.getAttribute('data-default-expanded') === 'true';

        if (defaultExpanded) {
            header.classList.add('active');
            let content = null;
            if (contentId) {
                content = document.getElementById(contentId);
            } else {
                content = header.nextElementSibling;
            }
            if (content && content.classList.contains('toggle-content')) {
                content.classList.add('show');
            }
        }
    });
}

// Global toggle function that can be called from any page
function toggleSection(header) {
    if (!header) return;

    header.classList.toggle('active');

    // Try to find content by data-target attribute first
    const contentId = header.getAttribute('data-target');
    let content = null;
    if (contentId) {
        content = document.getElementById(contentId);
    }

    // If not found, try next sibling
    if (!content) {
        content = header.nextElementSibling;
    }

    if (content && content.classList.contains('toggle-content')) {
        content.classList.toggle('show');

        // Dispatch event for tracking
        document.dispatchEvent(new CustomEvent('sectionToggled', {
            detail: {
                header: header,
                isOpen: content.classList.contains('show')
            }
        }));
    }
}

// ========== NOTIFICATION SYSTEM ==========
function initNotificationSystem() {
    updateNotifications();
    setInterval(updateNotifications, 30000);

    const markAllReadBtn = document.getElementById('markAllRead');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            markAllNotificationsRead();
        });
    }
}

function updateNotifications() {
    fetch('get_notifications.php')
        .then(res => res.json())
        .then(data => {
            const badge = document.getElementById('notificationBadge');
            const list = document.getElementById('notificationList');

            if (!badge || !list) return;

            if (data.notifications && data.notifications.length > 0) {
                badge.textContent = data.notifications.length;
                badge.style.display = 'flex';
                list.innerHTML = '';

                data.notifications.forEach(notif => {
                    const item = document.createElement('li');
                    const link = notif.link ? notif.link : '#';
                    item.innerHTML = `<a class="dropdown-item" href="${link}">
                        ${notif.message}<br>
                        <small class="text-muted">${new Date(notif.created_at).toLocaleString()}</small>
                    </a>`;
                    list.appendChild(item);
                });
            } else {
                badge.style.display = 'none';
                list.innerHTML = '<li><span class="dropdown-item-text text-muted">No new notifications</span></li>';
            }
        })
        .catch(error => console.error('Error fetching notifications:', error));
}

function markAllNotificationsRead() {
    fetch('mark_notifications_read.php', { method: 'POST' })
        .then(res => res.json())
        .then(data => {
            if (data.success) updateNotifications();
        })
        .catch(error => console.error('Error marking notifications as read:', error));
}

// ========== MOBILE MENU HANDLING ==========
function initMobileMenu() {
    // Auto-close dropdowns on mobile after click
    if (window.innerWidth < 768) {
        document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(item => {
            item.addEventListener('click', function() {
                const dropdown = this.closest('.dropdown');
                if (dropdown) {
                    const toggle = dropdown.querySelector('[data-bs-toggle="dropdown"]');
                    if (toggle) {
                        const bsDropdown = bootstrap.Dropdown.getInstance(toggle);
                        if (bsDropdown) {
                            bsDropdown.hide();
                        }
                    }
                }
            });
        });
    }
}

// ========== ACTIVE MENU HIGHLIGHTING ==========
(function initActiveMenu() {
    const currentFile = window.location.pathname.split('/').pop();

    // Files that should trigger submenu expansion
    const theatrePages = ['theatres.php', 'add_theatre.php', 'edit_theatre.php'];
    const userPages = ['users.php', 'add_user.php', 'edit_user.php', 'user_dashboard.php'];
    const settingsPages = ['settings.php', 'email_settings.php'];

    // Expand submenus based on current page
    if (theatrePages.includes(currentFile)) {
        const submenu = document.getElementById('theatresSubmenu');
        if (submenu) submenu.classList.add('show');
    }
    if (userPages.includes(currentFile)) {
        const submenu = document.getElementById('usersSubmenu');
        if (submenu) submenu.classList.add('show');
    }
    if (settingsPages.includes(currentFile)) {
        const submenu = document.getElementById('settingsSubmenu');
        if (submenu) submenu.classList.add('show');
    }

    // Mark active links
    function clearActiveStates() {
        document.querySelectorAll('.sidebar .nav-link').forEach(link => link.classList.remove('active'));
    }

    function markActive(link) {
        if (!link) return;
        link.classList.add('active');

        // If it's a submenu link, also highlight parent
        if (link.classList.contains('submenu-link')) {
            const collapseEl = link.closest('.collapse');
            if (collapseEl) {
                const parentToggle = document.querySelector('.sidebar .nav-link[data-bs-toggle="collapse"][href="#' + collapseEl.id + '"]');
                if (parentToggle) parentToggle.classList.add('active');
            }
        }
    }

    // Find and mark active link
    clearActiveStates();

    // First try exact match
    let activeLink = document.querySelector('.sidebar .nav-link[href="' + currentFile + '"]');

    // If not found, try with leading ./
    if (!activeLink && currentFile) {
        activeLink = document.querySelector('.sidebar .nav-link[href="./' + currentFile + '"]');
    }

    // If still not found, try with parent path
    if (!activeLink && currentFile) {
        activeLink = document.querySelector('.sidebar .nav-link[href*="' + currentFile + '"]');
    }

    if (activeLink) markActive(activeLink);

    // Special case for add_theatre.php - also highlight Theatres parent
    if (currentFile === 'add_theatre.php' || currentFile === 'edit_theatre.php') {
        const theatresParent = document.querySelector('.sidebar .nav-link[href="#theatresSubmenu"]');
        if (theatresParent) theatresParent.classList.add('active');
    }

    // Special case for add_user.php and user_dashboard.php - also highlight Users parent
    if (currentFile === 'add_user.php' || currentFile === 'edit_user.php' || currentFile === 'user_dashboard.php') {
        const usersParent = document.querySelector('.sidebar .nav-link[href="#usersSubmenu"]');
        if (usersParent) usersParent.classList.add('active');
    }

    // Special case for email_settings.php - also highlight Settings parent
    if (currentFile === 'email_settings.php') {
        const settingsParent = document.querySelector('.sidebar .nav-link[href="#settingsSubmenu"]');
        if (settingsParent) settingsParent.classList.add('active');
    }
})();