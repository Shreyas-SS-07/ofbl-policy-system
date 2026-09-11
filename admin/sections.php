<?php
/**
 * OFBL Policy Governance — Sections & Factory Departments
 * Ordnance Factory Badmal, Munitions India Limited (MIL)
 */

ob_start();
session_start();

require_once '../db.php';
require_once '../includes/helpers.php';
require_once '../includes/auth.php';

require_admin();

// Add Section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_section'])) {
    verify_csrf();
    $name = trim($_POST['section_name'] ?? '');
    $code = strtoupper(trim($_POST['section_code'] ?? ''));
    $desc = trim($_POST['description'] ?? '');

    if (empty($code)) {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 4));
    }

    if (!empty($name)) {
        $stmt = $conn->prepare("INSERT INTO sections (section_name, section_code, description) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $code, $desc);
        if ($stmt->execute()) {
            log_audit($conn, $_SESSION['user_id'], $_SESSION['name'], 'SECTION_CREATE', "Created section: {$name} ({$code})");
            set_toast('success', 'New factory department added successfully.');
        } else {
            set_toast('danger', 'Error adding section: ' . $conn->error);
        }
        $stmt->close();
    }
    header("Location: sections.php");
    exit();
}

// Delete Section
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Check if policies exist in this section
    $check = $conn->prepare("SELECT COUNT(*) as c FROM policies WHERE section_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $count = (int)$check->get_result()->fetch_assoc()['c'];
    $check->close();

    if ($count > 0) {
        set_toast('danger', "Cannot delete department: {$count} policies are currently assigned to it.");
    } else {
        $del = $conn->prepare("DELETE FROM sections WHERE id = ?");
        $del->bind_param("i", $id);
        $del->execute();
        $del->close();
        log_audit($conn, $_SESSION['user_id'], $_SESSION['name'], 'SECTION_DELETE', "Deleted section ID {$id}");
        set_toast('success', 'Department removed successfully.');
    }
    header("Location: sections.php");
    exit();
}

// Fetch sections with policy count
$query = $conn->query("
    SELECT sections.*, COUNT(policies.id) as policy_count
    FROM sections
    LEFT JOIN policies ON sections.id = policies.section_id
    GROUP BY sections.id
    ORDER BY sections.id ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sections | OFBL Admin</title>
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
            <li><a href="sections.php" class="active"><i class="bi bi-building"></i> Sections &amp; Depts</a></li>
            <li class="nav-label">Governance &amp; Users</li>
            <li><a href="users.php"><i class="bi bi-people-fill"></i> User Directory</a></li>
            <li><a href="comments.php"><i class="bi bi-chat-left-dots-fill"></i> Discussion Log</a></li>
            <li class="nav-label">Session</li>
            <li><a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Sign Out</a></li>
        </ul>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h2>Factory Sections &amp; Divisions</h2>
                <p>Ordnance Factory Badmal functional divisions and departmental classifications</p>
            </div>
            <div class="topbar-right">
                <button class="btn-theme" onclick="document.body.classList.toggle('dark-mode')">🌓 Theme</button>
            </div>
        </div>

        <?php echo render_toast(); ?>

        <!-- ADD SECTION FORM -->
        <div class="panel" style="margin-bottom: 24px;">
            <h3 style="font-size: 16px; margin-bottom: 14px;"><i class="bi bi-plus-circle-fill" style="color: #2563eb;"></i> Register New Factory Department</h3>
            <form method="POST" style="display: grid; grid-template-columns: 2fr 1fr 2fr auto; gap: 14px; align-items: flex-end;">
                <?php echo csrf_field(); ?>
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Department Full Name *</label>
                    <input type="text" name="section_name" placeholder="e.g. Directorate of Ballistics Testing" required style="width:100%;">
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Code *</label>
                    <input type="text" name="section_code" placeholder="e.g. DBT" style="width:100%; text-transform:uppercase;">
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Scope / Description</label>
                    <input type="text" name="description" placeholder="Brief functional remit..." style="width:100%;">
                </div>
                <div>
                    <button type="submit" name="add_section" class="btn btn-primary" style="height: 44px;">
                        <i class="bi bi-check-lg"></i> Create Section
                    </button>
                </div>
            </form>
        </div>

        <!-- SECTIONS LIST -->
        <div class="table-box">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Code</th>
                        <th>Department Name</th>
                        <th>Description</th>
                        <th>Active Policies</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($query && $query->num_rows > 0): ?>
                        <?php while ($row = $query->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td><span class="badge-pill badge-blue"><strong><?php echo e($row['section_code']); ?></strong></span></td>
                                <td><strong><?php echo e($row['section_name']); ?></strong></td>
                                <td style="color: var(--text-muted); font-size: 13px;"><?php echo e($row['description'] ?? 'General Factory Division'); ?></td>
                                <td>
                                    <span class="badge-pill badge-green">
                                        <?php echo (int)$row['policy_count']; ?> Directives
                                    </span>
                                </td>
                                <td>
                                    <a href="sections.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete section <?php echo addslashes($row['section_name']); ?>?');" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash-fill"></i> Delete
                                    </a>
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
