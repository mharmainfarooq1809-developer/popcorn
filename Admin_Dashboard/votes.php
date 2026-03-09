<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$admin_name = $_SESSION['user_name'] ?? 'Admin';

// Get all votes with user and movie details
$votes = $conn->query("
    SELECT 
        v.id AS vote_id,
        v.voted_at,
        u.id AS user_id,
        u.name AS user_name,
        u.email AS user_email,
        m.id AS movie_id,
        m.title AS movie_title,
        m.category,
        m.year
    FROM user_votes v
    JOIN users u ON v.user_id = u.id
    JOIN movies m ON v.movie_id = m.id
    ORDER BY v.voted_at DESC
");

// Get vote counts per movie (for chart)
$movie_counts = $conn->query("
    SELECT 
        m.title,
        COUNT(v.id) AS vote_count
    FROM user_votes v
    JOIN movies m ON v.movie_id = m.id
    GROUP BY m.id
    ORDER BY vote_count DESC
    LIMIT 10
");

$movie_labels = [];
$movie_data = [];
while ($row = $movie_counts->fetch_assoc()) {
    $movie_labels[] = $row['title'];
    $movie_data[] = $row['vote_count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votes · Popcorn Hub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        /* ========== GLOBAL & VARIABLES ========== */
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Heebo', sans-serif;
            background-color:#F8F9FA;
            color:#212529;
            transition:.3s;
            overflow-x:hidden;
            line-height:1.6;
        }
        body.dark-mode {
            background-color:#0B1623;
            color:#F2F2F2;
        }
        :root {
            --primary:#FFA500;
            --primary-dark:#cc7f00;
            --primary-gold:#FFD966;
            --light-card:#FFFFFF;
            --dark-card:#0F1C2B;
            --border-light:#E9ECEF;
            --border-dark:#3A414D;
            --sidebar-width:260px;
            --sidebar-collapsed:80px;
            --transition:.3s;
        }

        .sidebar {
            position:fixed;
            top:0;
            left:0;
            height:100vh;
            width:var(--sidebar-width);
            background:var(--light-card);
            box-shadow:2px 0 20px rgba(0,0,0,.05);
            transition:var(--transition);
            z-index:1000;
            overflow-y:auto;
            border-right:1px solid var(--border-light);
        }
        .dark-mode .sidebar {
            background:var(--dark-card);
            border-right-color:var(--border-dark);
        }
        .sidebar.collapsed { width:var(--sidebar-collapsed); }
        .sidebar .logo-area {
            padding:24px 20px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            border-bottom:1px solid var(--border-light);
        }
        .dark-mode .sidebar .logo-area { border-bottom-color:var(--border-dark); }
        .sidebar .logo {
            font-size:22px;
            font-weight:700;
            color:var(--primary-gold);
            white-space:nowrap;
            overflow:hidden;
        }
        .sidebar.collapsed .logo span { display:none; }
        .sidebar .toggle-btn {
            background:none;
            border:none;
            color:var(--light-text);
            cursor:pointer;
            font-size:20px;
        }
        .dark-mode .sidebar .toggle-btn { color:var(--dark-text); }
        .sidebar .toggle-btn:hover { color:var(--primary); }
        .sidebar .nav { padding: 12px 0 96px; }
        .sidebar .nav-link {
            display:flex;
            align-items:center;
            padding: 9px 16px;
            color:var(--light-text);
            text-decoration:none;
            border-radius:0 30px 30px 0;
            margin-right:10px;
            transition:var(--transition);
            white-space:nowrap;
        }
        .dark-mode .sidebar .nav-link { color:var(--dark-text); }
        .sidebar .nav-link i { font-size: 17px; min-width: 24px; text-align:center; }
        .sidebar.collapsed .nav-link span { display:none; }
        .sidebar .nav-link:hover { background:rgba(255,165,0,0.1); color:var(--primary); }
        .sidebar .nav-link.active { background:var(--primary); color:#fff; }
        .dark-mode .sidebar .nav-link.active { background:var(--primary-dark); color:#fff; }
        .sidebar .bottom-section {
            position:absolute;
            bottom:0;
            left:0;
            width:100%;
            padding: 14px;
            border-top:1px solid var(--border-light);
            background:inherit;
        }
        .dark-mode .sidebar .bottom-section { border-top-color:var(--border-dark); }

        .main-content {
            margin-left:var(--sidebar-width);
            padding:20px 30px;
            transition:var(--transition);
            min-height:100vh;
        }
        .sidebar.collapsed+.main-content { margin-left:var(--sidebar-collapsed); }

        .top-navbar {
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:15px 0;
            margin-bottom:30px;
            flex-wrap:wrap;
            gap:15px;
        }
        .search-bar {
            position:relative;
            width:300px;
        }
        .search-bar input {
            width:100%;
            padding:12px 40px 12px 20px;
            border-radius:40px;
            border:1px solid var(--border-light);
            background:var(--light-card);
            color:var(--light-text);
            transition:var(--transition);
        }
        .dark-mode .search-bar input {
            background:var(--dark-card);
            border-color:var(--border-dark);
            color:var(--dark-text);
        }
        .search-bar input:focus {
            outline:none;
            border-color:var(--primary);
            box-shadow:0 0 0 4px rgba(255,165,0,0.2);
        }
        .search-bar i {
            position:absolute;
            right:15px;
            top:50%;
            transform:translateY(-50%);
            color:#999;
        }
        .nav-icons {
            display:flex;
            align-items:center;
            gap:20px;
        }
        .nav-icons .icon {
            position:relative;
            font-size:22px;
            color:var(--light-text);
            cursor:pointer;
        }
        .dark-mode .nav-icons .icon { color:var(--dark-text); }
        .nav-icons .icon:hover { color:var(--primary); }
        .nav-icons .badge {
            position:absolute;
            top:-5px;
            right:-5px;
            background:var(--primary);
            color:#fff;
            border-radius:50%;
            width:18px;
            height:18px;
            font-size:11px;
            display:flex;
            align-items:center;
            justify-content:center;
        }
        .avatar {
            width:40px;
            height:40px;
            border-radius:50%;
            object-fit:cover;
            cursor:pointer;
            border:2px solid transparent;
        }
        .avatar:hover { border-color:var(--primary); }

        .card {
            border:none;
            border-radius:20px;
            padding: 14px;
            background:var(--light-card);
            box-shadow:0 10px 30px rgba(0,0,0,.05);
            margin-bottom:20px;
        }
        .dark-mode .card { background:var(--dark-card); }

        .badge {
            display:inline-block;
            padding:5px 12px;
            border-radius:30px;
            font-size:12px;
            font-weight:600;
        }
        .badge-primary { background:rgba(255,165,0,0.15); color:var(--primary); }

        .btn {
            display:inline-block;
            padding:10px 24px;
            border-radius:40px;
            font-weight:600;
            font-size:14px;
            text-decoration:none;
            text-align:center;
            transition:var(--transition);
            border:none;
            cursor:pointer;
        }
        .btn-primary {
            background:linear-gradient(145deg, var(--primary), var(--primary-dark));
            color:#fff;
            box-shadow:0 4px 14px rgba(255,165,0,0.3);
        }
        .btn-primary:hover {
            transform:translateY(-3px);
            box-shadow:0 8px 25px rgba(255,165,0,0.5);
        }
        .btn-outline-danger {
            border:1px solid #dc3545;
            color:#dc3545;
            background:transparent;
        }
        .btn-outline-danger:hover {
            background:#dc3545;
            color:#fff;
        }
        .btn-sm { padding:6px 16px; font-size:13px; }

        .table-responsive {
            border-radius:20px;
            background:var(--light-card);
            padding:0;
            box-shadow:0 10px 30px rgba(0,0,0,.05);
            overflow:hidden;
            border:1px solid var(--border-light);
        }
        .dark-mode .table-responsive {
            background:var(--dark-card);
            border-color:var(--border-dark);
        }
        .table {
            width:100%;
            margin-bottom:0;
            color:var(--light-text);
            border-collapse:separate;
            border-spacing:0;
        }
        .dark-mode .table { color:var(--dark-text); }
        .table th {
            background:rgba(0,0,0,0.02);
            border-bottom:2px solid var(--border-light);
            font-weight:600;
            color:#6c757d;
            padding:15px 12px;
            text-align:left;
            white-space:nowrap;
        }
        .dark-mode .table th {
            background:rgba(255,255,255,0.02);
            border-bottom-color:var(--border-dark);
            color:#adb5bd;
        }
        .table td {
            border-bottom:1px solid var(--border-light);
            padding:12px;
            vertical-align:middle;
        }
        .dark-mode .table td { border-bottom-color:var(--border-dark); }
        .table tbody tr:hover { background:rgba(0,0,0,0.02); }
        .dark-mode .table tbody tr:hover { background:rgba(255,255,255,0.02); }

        @media (max-width:992px) {
            .sidebar { left:-100%; }
            .sidebar.active { left:0; }
            .main-content { margin-left:0!important; }
            .search-bar { width:250px; }
        }
        @media (max-width:768px) {
            .top-navbar { flex-direction:column; align-items:stretch; }
            .search-bar { width:100%; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="top-navbar">
            <div class="search-bar">
                <input type="text" id="searchVotes" placeholder="Search votes...">
                <i class="bi bi-search"></i>
            </div>
            <div class="nav-icons">
                <div class="dropdown d-inline-block">
                    <div class="icon position-relative" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" role="button">
                        <i class="bi bi-bell"></i>
                        <span class="badge" id="notificationBadge" style="display:none;">0</span>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end" style="width:300px;">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="notificationList">Loading...</li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center small" href="#" id="markAllRead">Mark all as read</a></li>
                    </ul>
                </div>
                <div class="theme-toggle" id="themeToggle"><i class="bi bi-moon"></i></div>
                <img src="https://via.placeholder.com/40" class="avatar" alt="User">
            </div>
        </div>

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>User Votes</h2>
            <button class="btn btn-primary" onclick="exportToCSV()"><i class="bi bi-download"></i> Export CSV</button>
        </div>

        <!-- Chart Row -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <h5 class="mb-3">Top Voted Movies</h5>
                    <div style="height:250px;">
                    <canvas id="votesChart" ></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <h5 class="mb-3">Quick Stats</h5>
                    <div class="row">
                        <div class="col-6">
                            <div class="border p-3 rounded text-center">
                                <h3><?= $votes->num_rows ?></h3>
                                <small>Total Votes</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border p-3 rounded text-center">
                                <h3><?= $conn->query("SELECT COUNT(DISTINCT user_id) FROM user_votes")->fetch_row()[0] ?></h3>
                                <small>Voters</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Votes Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover" id="votesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Movie</th>
                            <th>Category</th>
                            <th>Year</th>
                            <th>Voted At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($votes->num_rows > 0): ?>
                            <?php while ($v = $votes->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $v['vote_id'] ?></td>
                                    <td><?= htmlspecialchars($v['user_name']) ?></td>
                                    <td><?= htmlspecialchars($v['user_email']) ?></td>
                                    <td><?= htmlspecialchars($v['movie_title']) ?></td>
                                    <td><?= htmlspecialchars($v['category']) ?></td>
                                    <td><?= $v['year'] ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($v['voted_at'])) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteVote(<?= $v['vote_id'] ?>)"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center">No votes yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        });

        // Dark mode toggle
        document.getElementById('themeToggle').addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            const icon = this.querySelector('i');
            icon.classList.toggle('bi-moon');
            icon.classList.toggle('bi-sun');
        });

        // Search filter
        document.getElementById('searchVotes').addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#votesTable tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });

        // Chart
        new Chart(document.getElementById('votesChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($movie_labels) ?>,
                datasets: [{
                    label: 'Votes',
                    data: <?= json_encode($movie_data) ?>,
                    backgroundColor: '#FFA500'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });

        // Delete vote
        function deleteVote(voteId) {
            if (!confirm('Delete this vote?')) return;
            fetch('delete_vote.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + voteId
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) location.reload();
                else alert('Error: ' + data.error);
            })
            .catch(err => alert('Network error'));
        }

        // Export to CSV
        function exportToCSV() {
            let csv = "ID,User,Email,Movie,Category,Year,Voted At\n";
            document.querySelectorAll('#votesTable tbody tr').forEach(row => {
                if (row.style.display !== 'none') {
                    const cells = row.querySelectorAll('td');
                    csv += cells[0].innerText + ',' +
                           cells[1].innerText + ',' +
                           cells[2].innerText + ',' +
                           cells[3].innerText + ',' +
                           cells[4].innerText + ',' +
                           cells[5].innerText + ',' +
                           cells[6].innerText + '\n';
                }
            });
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'votes.csv';
            a.click();
        }

        // Notifications (optional)
        function updateNotifications() {
            fetch('get_notifications.php')
                .then(res => res.json())
                .then(data => {
                    const badge = document.getElementById('notificationBadge');
                    const list = document.getElementById('notificationList');
                    if (data.notifications?.length) {
                        badge.textContent = data.notifications.length;
                        badge.style.display = 'flex';
                        list.innerHTML = '';
                        data.notifications.forEach(n => {
                            const item = document.createElement('li');
                            item.innerHTML = `<a class="dropdown-item" href="${n.link}">${n.message}<br><small>${new Date(n.created_at).toLocaleString()}</small></a>`;
                            list.appendChild(item);
                        });
                    } else {
                        badge.style.display = 'none';
                        list.innerHTML = '<li><span class="dropdown-item-text text-muted">No new notifications</span></li>';
                    }
                });
        }
        updateNotifications();
        setInterval(updateNotifications, 30000);
    </script>
</body>
</html>