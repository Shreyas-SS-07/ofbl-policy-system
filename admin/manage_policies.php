<?php
/**
 * OFBL Policy Governance — Policy Catalog & Document Management
 * Ordnance Factory Badmal, Munitions India Limited (MIL)
 */

ob_start();
session_start();

require_once '../db.php';
require_once '../includes/helpers.php';
require_once '../includes/auth.php';

require_admin();

// Delete Policy Action
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $stmt = $conn->prepare("SELECT pdf_file, title FROM policies WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            $file_path = __DIR__ . '/../uploads/pdfs/' . $row['pdf_file'];
            if (file_exists($file_path) && is_file($file_path)) {
                @unlink($file_path);
            }
            $stmt->close();

            $del = $conn->prepare("DELETE FROM policies WHERE id = ?");
            $del->bind_param("i", $id);
            $del->execute();
            $del->close();

            log_audit($conn, $_SESSION['user_id'], $_SESSION['name'], 'POLICY_DELETE', 'Deleted policy: ' . $row['title']);
            set_toast('success', 'Policy directive deleted successfully from repository.');
        } else {
            $stmt->close();
        }
    }
    header("Location: manage_policies.php");
    exit();
}

// Fetch all policies with section joins
$search = trim($_GET['q'] ?? '');
$section_filter = (int)($_GET['section'] ?? 0);

$query_str = "SELECT policies.*, sections.section_name, sections.section_code, users.name as uploader_name
              FROM policies
              LEFT JOIN sections ON policies.section_id = sections.id
              LEFT JOIN users ON policies.uploaded_by = users.id
              WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $query_str .= " AND (policies.title LIKE ? OR policies.policy_number LIKE ? OR policies.pdf_hint LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

if ($section_filter > 0) {
    $query_str .= " AND policies.section_id = ?";
    $params[] = $section_filter;
    $types .= "i";
}

$query_str .= " ORDER BY policies.id DESC";

$stmt = $conn->prepare($query_str);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$policies = $stmt->get_result();

// Get sections for filter
$sec_res = $conn->query("SELECT id, section_name FROM sections ORDER BY section_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Policies | OFBL Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

    <!-- SIDEBAR -->
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
            <li><a href="manage_policies.php" class="active"><i class="bi bi-file-earmark-text-fill"></i> Manage Policies</a></li>
            <li><a href="upload_policy.php"><i class="bi bi-cloud-arrow-up-fill"></i> Upload Directive</a></li>
            <li><a href="sections.php"><i class="bi bi-building"></i> Sections &amp; Depts</a></li>
            <li class="nav-label">Governance &amp; Users</li>
            <li><a href="users.php"><i class="bi bi-people-fill"></i> User Directory</a></li>
            <li><a href="comments.php"><i class="bi bi-chat-left-dots-fill"></i> Discussion Log</a></li>
            <li class="nav-label">Session</li>
            <li><a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Sign Out</a></li>
        </ul>

        <div class="sidebar-footer">
            <span>Auth: <strong>Shreyas S.</strong></span>
            <span style="font-size: 10px; color: #10b981;">● Online</span>
        </div>
    </div>

    <!-- MAIN -->
    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h2>Policy Document Repository</h2>
                <p>Browse, update, inspect, or archive official factory directives</p>
            </div>
            <div class="topbar-right">
                <a href="upload_policy.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add New Policy</a>
                <button class="btn-theme" onclick="document.body.classList.toggle('dark-mode')">🌓 Theme</button>
            </div>
        </div>

        <?php echo render_toast(); ?>

        <!-- FILTER BAR -->
        <div class="panel" style="margin-bottom: 24px;">
            <form method="GET" style="display: flex; gap: 14px; flex-wrap: wrap;">
                <input type="text" name="q" placeholder="Search title, policy code, or keyword..." value="<?php echo e($search); ?>" style="flex: 2; min-width: 240px;">
                <select name="section" style="flex: 1; min-width: 180px;">
                    <option value="0">-- All Departments --</option>
                    <?php if ($sec_res): while ($s = $sec_res->fetch_assoc()): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo $section_filter == $s['id'] ? 'selected' : ''; ?>>
                            <?php echo e($s['section_name']); ?>
                        </option>
                    <?php endwhile; endif; ?>
                </select>
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                <?php if (!empty($search) || $section_filter > 0): ?>
                    <a href="manage_policies.php" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- POLICIES TABLE -->
        <div class="table-box">
            <table>
                <thead>
                    <tr>
                        <th>Policy Code</th>
                        <th>Directive Title</th>
                        <th>Department</th>
                        <th>Effective Date</th>
                        <th>Version</th>
                        <th>Document</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($policies && $policies->num_rows > 0): ?>
                        <?php while ($p = $policies->fetch_assoc()): ?>
                            <tr>
                                <td><span class="badge-pill badge-blue"><strong><?php echo e($p['policy_number']); ?></strong></span></td>
                                <td>
                                    <strong><?php echo e($p['title']); ?></strong>
                                    <?php if (!empty($p['pdf_hint'])): ?>
                                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;"><?php echo e(substr($p['pdf_hint'], 0, 75)) . '...'; ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($p['section_name'] ?? 'General'); ?></td>
                                <td><?php echo e($p['policy_date']); ?></td>
                                <td><span class="badge-pill badge-green">v<?php echo e($p['version'] ?? '1.0'); ?></span></td>
                                <td>
                                    <a href="../uploads/pdfs/<?php echo urlencode($p['pdf_file']); ?>" target="_blank" class="btn btn-sm btn-secondary">
                                        <i class="bi bi-file-earmark-pdf-fill" style="color: #ef4444;"></i> View PDF
                                    </a>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <a href="edit_policy.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-secondary" title="Edit Policy">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </a>
                                        <a href="manage_policies.php?delete=<?php echo $p['id']; ?>" onclick="return confirm('Confirm permanent deletion of policy: <?php echo addslashes($p['title']); ?>?');" class="btn btn-sm btn-danger" title="Delete Policy">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; padding: 40px; color: var(--text-muted);">No policies found matching your search criteria.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
