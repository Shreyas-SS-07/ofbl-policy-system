<?php
/**
 * OFBL Policy Governance — Personnel & Clearance Directory
 * Ordnance Factory Badmal, Munitions India Limited (MIL)
 */

ob_start();
session_start();

require_once '../db.php';
require_once '../includes/helpers.php';
require_once '../includes/auth.php';

require_admin();

// Approve User
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $stmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    log_audit($conn, $_SESSION['user_id'], $_SESSION['name'], 'USER_APPROVE', "Approved clearance for user ID {$id}");
    set_toast('success', 'User clearance approved. Personnel now has portal access.');
    header("Location: users.php");
    exit();
}

// Delete User
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id === (int)$_SESSION['user_id']) {
        set_toast('danger', 'Security Protocol: You cannot delete your own active administrator account.');
    } else {
        $del = $conn->prepare("DELETE FROM users WHERE id = ?");
        $del->bind_param("i", $id);
        $del->execute();
        $del->close();

        log_audit($conn, $_SESSION['user_id'], $_SESSION['name'], 'USER_DELETE', "Deleted user account ID {$id}");
        set_toast('success', 'User account permanently removed.');
    }
    header("Location: users.php");
    exit();
}

// Elevate to Admin / Demote to User
if (isset($_GET['toggle_role'])) {
    $id = (int)$_GET['toggle_role'];
    $check = $conn->query("SELECT role, name FROM users WHERE id = {$id}")->fetch_assoc();
    if ($check) {
        $new_role = $check['role'] === 'admin' ? 'user' : 'admin';
        $up = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $up->bind_param("si", $new_role, $id);
        $up->execute();
        $up->close();

        log_audit($conn, $_SESSION['user_id'], $_SESSION['name'], 'ROLE_CHANGE', "Changed role for {$check['name']} to {$new_role}");
        set_toast('success', "Role for {$check['name']} updated to " . strtoupper($new_role));
    }
    header("Location: users.php");
    exit();
}

// Fetch all users with department join
$status_filter = trim($_GET['status'] ?? '');
$sql = "SELECT users.*, sections.section_name, sections.section_code
        FROM users
        LEFT JOIN sections ON users.department_id = sections.id";
if (!empty($status_filter)) {
    $sql .= " WHERE users.status = '" . $conn->real_escape_string($status_filter) . "'";
}
$sql .= " ORDER BY users.id DESC";
$users = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personnel Clearance Directory | OFBL Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo-icon"><i class="bi bi-shield-shaded"></i></div>
            <div class="sidebar-title">
                <h2>OFBL ADMIN</h2>
                <p>Munitions India Limited</p>
            </div>
        </div>
        <ul class="sidebar-nav">
            <li class="nav-label">Core Modules</li>
            <li><a href="dashboard.php"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a></li>
            <li><a href="manage_policies.php"><i class="bi bi-file-earmark-text-fill"></i> Manage Policies</a></li>
            <li><a href="upload_policy.php"><i class="bi bi-cloud-arrow-up-fill"></i> Upload Directive</a></li>
            <li><a href="sections.php"><i class="bi bi-building"></i> Sections &amp; Depts</a></li>
            <li class="nav-label">Governance &amp; Users</li>
            <li><a href="users.php" class="active"><i class="bi bi-people-fill"></i> User Directory</a></li>
            <li><a href="comments.php"><i class="bi bi-chat-left-dots-fill"></i> Discussion Log</a></li>
            <li class="nav-label">Session</li>
            <li><a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Sign Out</a></li>
        </ul>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h2>Personnel &amp; Clearance Directory</h2>
                <p>Manage employee access, role permissions, and registration authorizations</p>
            </div>
            <div class="topbar-right">
                <div style="display: flex; gap: 8px;">
                    <a href="users.php" class="btn btn-sm btn-secondary">All</a>
                    <a href="users.php?status=pending" class="btn btn-sm btn-secondary" style="color: #f59e0b;"><i class="bi bi-hourglass-split"></i> Pending</a>
                    <a href="users.php?status=approved" class="btn btn-sm btn-secondary" style="color: #10b981;"><i class="bi bi-check-circle"></i> Approved</a>
                </div>
                <button class="btn-theme" onclick="document.body.classList.toggle('dark-mode')">🌓 Theme</button>
            </div>
        </div>

        <?php echo render_toast(); ?>

        <div class="table-box">
            <table>
                <thead>
                    <tr>
                        <th>Personnel Name</th>
                        <th>Official Email</th>
                        <th>Department</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>IP Origin</th>
                        <th>Joined Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users && $users->num_rows > 0): ?>
                        <?php while ($u = $users->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo e($u['name']); ?></strong></td>
                                <td><code><?php echo e($u['email']); ?></code></td>
                                <td><?php echo e($u['section_name'] ?? 'General Staff'); ?></td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge-pill badge-blue"><i class="bi bi-shield-fill-check"></i> ADMIN</span>
                                    <?php else: ?>
                                        <span class="badge-pill badge-green">USER</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status <?php echo $u['status']; ?>">
                                        <?php echo strtoupper($u['status']); ?>
                                    </span>
                                </td>
                                <td><small style="color: var(--text-muted); font-family: monospace;"><?php echo e($u['ip_address'] ?? '127.0.0.1'); ?></small></td>
                                <td><small><?php echo date('d M Y', strtotime($u['created_at'])); ?></small></td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <?php if ($u['status'] === 'pending'): ?>
                                            <a href="users.php?approve=<?php echo $u['id']; ?>" class="btn btn-sm btn-success" title="Grant Portal Clearance">
                                                <i class="bi bi-check-circle-fill"></i> Approve
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                            <a href="users.php?toggle_role=<?php echo $u['id']; ?>" class="btn btn-sm btn-secondary" title="Toggle Admin/User Role" onclick="return confirm('Change authorization role for <?php echo addslashes($u['name']); ?>?');">
                                                <i class="bi bi-shield-lock"></i> Role
                                            </a>
                                            <a href="users.php?delete=<?php echo $u['id']; ?>" onclick="return confirm('Revoke and delete account for <?php echo addslashes($u['name']); ?>?');" class="btn btn-sm btn-danger" title="Delete User">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                        <?php else: ?>
                                            <span style="font-size: 11px; color: var(--text-muted); font-style: italic;">Current Admin</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
