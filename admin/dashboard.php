<?php
/**
 * OFBL Policy Governance — Administrator Executive Dashboard
 * Ordnance Factory Badmal, Munitions India Limited (MIL)
 * Ministry of Defence, Government of India
 * Lead Developer: Shreyas Sankalp Sahu (KIIT CSE)
 */

ob_start();
session_start();

require_once '../db.php';
require_once '../includes/helpers.php';
require_once '../includes/auth.php';

require_admin();

// 1. Metric KPI Totals
$user_count = 0;
$policy_count = 0;
$section_count = 0;
$comment_count = 0;
$pending_users = 0;
$today_comments = 0;

if ($res = $conn->query("SELECT COUNT(*) as c FROM users")) {
    $user_count = (int)$res->fetch_assoc()['c'];
}
if ($res = $conn->query("SELECT COUNT(*) as c FROM policies")) {
    $policy_count = (int)$res->fetch_assoc()['c'];
}
if ($res = $conn->query("SELECT COUNT(*) as c FROM sections")) {
    $section_count = (int)$res->fetch_assoc()['c'];
}
if ($res = $conn->query("SELECT COUNT(*) as c FROM comments")) {
    $comment_count = (int)$res->fetch_assoc()['c'];
}
if ($res = $conn->query("SELECT COUNT(*) as c FROM users WHERE status='pending'")) {
    $pending_users = (int)$res->fetch_assoc()['c'];
}
if ($res = $conn->query("SELECT COUNT(*) as c FROM comments WHERE DATE(created_at)=CURDATE()")) {
    $today_comments = (int)$res->fetch_assoc()['c'];
}

// 2. Department Analytics Chart Data
$labels = [];
$chart_data = [];
$chart_query = $conn->query("
    SELECT sections.section_name, COUNT(policies.id) as total
    FROM sections
    LEFT JOIN policies ON sections.id = policies.section_id
    GROUP BY sections.id
");
if ($chart_query) {
    while ($row = $chart_query->fetch_assoc()) {
        $labels[] = $row['section_name'];
        $chart_data[] = (int)$row['total'];
    }
}

// 3. Recent Audit Logs
$recent_logs = [];
$log_query = $conn->query("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 5");
if ($log_query) {
    while ($log = $log_query->fetch_assoc()) {
        $recent_logs[] = $log;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Dashboard | OFBL Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="../assets/js/chart.js"></script>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo-icon">
                <i class="bi bi-shield-shaded"></i>
            </div>
            <div class="sidebar-title">
                <h2>OFBL ADMIN</h2>
                <p>Munitions India Limited</p>
            </div>
        </div>

        <ul class="sidebar-nav">
            <li class="nav-label">Core Modules</li>
            <li>
                <a href="dashboard.php" class="active">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="manage_policies.php">
                    <i class="bi bi-file-earmark-text-fill"></i> Manage Policies
                </a>
            </li>
            <li>
                <a href="upload_policy.php">
                    <i class="bi bi-cloud-arrow-up-fill"></i> Upload Directive
                </a>
            </li>
            <li>
                <a href="sections.php">
                    <i class="bi bi-building"></i> Sections &amp; Depts
                </a>
            </li>
            <li class="nav-label">Governance &amp; Users</li>
            <li>
                <a href="users.php">
                    <i class="bi bi-people-fill"></i> User Directory
                    <?php if ($pending_users > 0): ?>
                        <span class="badge-pill badge-amber" style="margin-left:auto; font-size:10px;"><?php echo $pending_users; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="comments.php">
                    <i class="bi bi-chat-left-dots-fill"></i> Discussion Log
                </a>
            </li>
            <li class="nav-label">Session</li>
            <li>
                <a href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i> Sign Out
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <span>Auth: <strong>Shreyas S.</strong></span>
            <span style="font-size: 10px; color: #10b981;">● Online</span>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <!-- TOPBAR -->
        <div class="topbar">
            <div class="topbar-left">
                <h2>Admin Executive Control Center</h2>
                <p>Overview of Ordnance Factory Badmal policy governance, document storage, and clearances</p>
            </div>
            <div class="topbar-right">
                <div class="user-badge">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['name'] ?? 'A', 0, 1)); ?>
                    </div>
                    <span><?php echo e($_SESSION['name'] ?? 'Administrator'); ?></span>
                </div>
                <button class="btn-theme" id="themeToggle" onclick="toggleTheme()">
                    <i class="bi bi-moon-fill" id="themeIcon"></i> Dark
                </button>
            </div>
        </div>

        <?php echo render_toast(); ?>

        <!-- METRIC KPI CARDS -->
        <div class="cards">
            <div class="card">
                <div class="card-top">
                    <h3>Active Policies</h3>
                    <div class="card-icon blue"><i class="bi bi-file-earmark-check"></i></div>
                </div>
                <p><?php echo $policy_count; ?></p>
                <div class="card-subtext"><span style="color:#10b981;">↑ Live</span> in digital repository</div>
            </div>

            <div class="card">
                <div class="card-top">
                    <h3>Personnel Users</h3>
                    <div class="card-icon purple"><i class="bi bi-people"></i></div>
                </div>
                <p><?php echo $user_count; ?></p>
                <div class="card-subtext">Registered across factory divisions</div>
            </div>

            <div class="card">
                <div class="card-top">
                    <h3>Departments</h3>
                    <div class="card-icon green"><i class="bi bi-building-check"></i></div>
                </div>
                <p><?php echo $section_count; ?></p>
                <div class="card-subtext">ITC, Production, DGQA, Safety &amp; HRD</div>
            </div>

            <div class="card">
                <div class="card-top">
                    <h3>Queries &amp; Comments</h3>
                    <div class="card-icon amber"><i class="bi bi-chat-square-text"></i></div>
                </div>
                <p><?php echo $comment_count; ?></p>
                <div class="card-subtext"><span style="color:#f59e0b;">+<?php echo $today_comments; ?></span> submitted today</div>
            </div>
        </div>

        <!-- DASHBOARD GRID: ANALYTICS + AUDIT STREAM -->
        <div class="dashboard-grid">
            <!-- CHART PANEL -->
            <div class="panel">
                <div class="panel-header">
                    <h3><i class="bi bi-pie-chart-fill" style="color: #2563eb;"></i> Departmental Policy Distribution</h3>
                    <span>Live Metric Breakdown</span>
                </div>
                <div style="max-width: 420px; margin: 20px auto;">
                    <canvas id="departmentChart"></canvas>
                </div>
            </div>

            <!-- NOTIFICATIONS & QUICK STATUS -->
            <div class="panel">
                <div class="panel-header">
                    <h3><i class="bi bi-bell-fill" style="color: #f59e0b;"></i> Action Priority</h3>
                    <span>Clearance Queue</span>
                </div>

                <div class="notify-item">
                    <div class="notify-left">
                        <div class="notify-icon" style="background: rgba(245,158,11,0.15); color: #d97706;">
                            <i class="bi bi-person-exclamation"></i>
                        </div>
                        <div class="notify-text">
                            <h4>Pending Clearances</h4>
                            <p>Accounts awaiting approval</p>
                        </div>
                    </div>
                    <a href="users.php" class="badge-pill badge-amber" style="text-decoration:none;">
                        <?php echo $pending_users; ?> Action
                    </a>
                </div>

                <div class="notify-item">
                    <div class="notify-left">
                        <div class="notify-icon" style="background: rgba(37,99,235,0.15); color: #2563eb;">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                        <div class="notify-text">
                            <h4>Recent Comments</h4>
                            <p>Discussions in last 24 hrs</p>
                        </div>
                    </div>
                    <span class="badge-pill badge-blue"><?php echo $today_comments; ?> Logged</span>
                </div>

                <div class="notify-item">
                    <div class="notify-left">
                        <div class="notify-icon" style="background: rgba(16,185,129,0.15); color: #059669;">
                            <i class="bi bi-file-earmark-arrow-up"></i>
                        </div>
                        <div class="notify-text">
                            <h4>Policy Vault Size</h4>
                            <p>Archived &amp; active directives</p>
                        </div>
                    </div>
                    <span class="badge-pill badge-green"><?php echo $policy_count; ?> Directives</span>
                </div>

                <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border);">
                    <a href="upload_policy.php" class="btn btn-primary" style="width: 100%; justify-content: center;">
                        <i class="bi bi-cloud-arrow-up"></i> Upload New Directive
                    </a>
                </div>
            </div>
        </div>

        <!-- RECENT AUDIT TRAIL PANEL -->
        <div class="panel">
            <div class="panel-header">
                <h3><i class="bi bi-shield-check" style="color: #10b981;"></i> Security Audit Trail (Defence Compliance)</h3>
                <span>Real-time Activity Stream</span>
            </div>

            <div class="table-box" style="margin-top: 0; padding: 0; box-shadow: none; border: none;">
                <table>
                    <thead>
                        <tr>
                            <th>User / Operator</th>
                            <th>Action Type</th>
                            <th>Operation Details</th>
                            <th>IP Address</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_logs)): ?>
                            <tr><td colspan="5" style="text-align:center; color: var(--text-muted);">No activity recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_logs as $log): ?>
                                <tr>
                                    <td><strong><?php echo e($log['user_name'] ?? 'System Operator'); ?></strong></td>
                                    <td><span class="badge-pill badge-blue"><?php echo e($log['action']); ?></span></td>
                                    <td><?php echo e($log['details']); ?></td>
                                    <td><code><?php echo e($log['ip_address'] ?? '127.0.0.1'); ?></code></td>
                                    <td><?php echo e($log['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Doughnut Chart for Department Analytics
        const ctx = document.getElementById('departmentChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($chart_data); ?>,
                        backgroundColor: [
                            '#2563eb', '#10b981', '#f59e0b', '#8b5cf6', '#06b6d4', '#ec4899', '#f97316'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 12
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 14,
                                font: { family: 'Plus Jakarta Sans', size: 12, weight: 600 }
                            }
                        }
                    },
                    cutout: '68%'
                }
            });
        }

        // Theme Toggle
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('ofbl_theme', isDark ? 'dark' : 'light');
            document.getElementById('themeIcon').className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
            document.getElementById('themeToggle').innerHTML = isDark ? '<i class="bi bi-sun-fill"></i> Light' : '<i class="bi bi-moon-fill"></i> Dark';
        }

        // Persist theme
        if (localStorage.getItem('ofbl_theme') === 'dark') {
            document.body.classList.add('dark-mode');
            document.getElementById('themeToggle').innerHTML = '<i class="bi bi-sun-fill"></i> Light';
        }
    </script>
</body>
</html>
