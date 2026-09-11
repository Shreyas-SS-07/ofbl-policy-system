<?php
/**
 * OFBL Policy Governance — Discussion & Feedback Moderation
 * Ordnance Factory Badmal, Munitions India Limited (MIL)
 */

ob_start();
session_start();

require_once '../db.php';
require_once '../includes/helpers.php';
require_once '../includes/auth.php';

require_admin();

// Delete Comment
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $del = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $del->bind_param("i", $id);
    $del->execute();
    $del->close();

    log_audit($conn, $_SESSION['user_id'], $_SESSION['name'], 'COMMENT_DELETE', "Moderator removed comment ID {$id}");
    set_toast('success', 'Discussion comment removed from stream.');
    header("Location: comments.php");
    exit();
}

$query = $conn->query("
    SELECT comments.*, users.name, users.email, policies.title as policy_title, policies.policy_number
    FROM comments
    LEFT JOIN users ON comments.user_id = users.id
    LEFT JOIN policies ON comments.policy_id = policies.id
    ORDER BY comments.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discussion Moderation | OFBL Admin</title>
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
            <li><a href="users.php"><i class="bi bi-people-fill"></i> User Directory</a></li>
            <li><a href="comments.php" class="active"><i class="bi bi-chat-left-dots-fill"></i> Discussion Log</a></li>
            <li class="nav-label">Session</li>
            <li><a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Sign Out</a></li>
        </ul>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h2>Policy Feedback &amp; Discussion Logs</h2>
                <p>Monitor queries and compliance feedback submitted across departmental directives</p>
            </div>
            <div class="topbar-right">
                <button class="btn-theme" onclick="document.body.classList.toggle('dark-mode')">🌓 Theme</button>
            </div>
        </div>

        <?php echo render_toast(); ?>

        <div class="table-box">
            <table>
                <thead>
                    <tr>
                        <th>Personnel</th>
                        <th>Associated Directive</th>
                        <th>Comment &amp; Inquiry Body</th>
                        <th>Submitted At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($query && $query->num_rows > 0): ?>
                        <?php while ($c = $query->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($c['name'] ?? 'Authorized Personnel'); ?></strong>
                                    <div style="font-size: 11.5px; color: var(--text-muted);"><?php echo e($c['email'] ?? ''); ?></div>
                                </td>
                                <td>
                                    <span class="badge-pill badge-blue"><?php echo e($c['policy_number'] ?? 'POLICY'); ?></span>
                                    <div style="font-size: 12px; margin-top: 4px; font-weight: 600;"><?php echo e($c['policy_title'] ?? 'General Directive'); ?></div>
                                </td>
                                <td style="max-width: 380px; line-height: 1.5; font-size: 13.5px;">
                                    <?php echo nl2br(e($c['comment'])); ?>
                                </td>
                                <td><small><?php echo date('d M Y, h:i A', strtotime($c['created_at'])); ?></small></td>
                                <td>
                                    <a href="comments.php?delete=<?php echo $c['id']; ?>" onclick="return confirm('Delete this comment?');" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash-fill"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding: 40px; color: var(--text-muted);">No user discussions logged yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
