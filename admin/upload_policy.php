<?php
/**
 * OFBL Policy Governance — Upload Official Policy Directive
 * Ordnance Factory Badmal, Munitions India Limited (MIL)
 */

ob_start();
session_start();

require_once '../db.php';
require_once '../includes/helpers.php';
require_once '../includes/auth.php';

require_admin();

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    verify_csrf();

    $title = trim($_POST['title'] ?? '');
    $policy_number = trim($_POST['policy_number'] ?? '');
    $policy_date = $_POST['policy_date'] ?? date('Y-m-d');
    $section_id = (int)($_POST['section_id'] ?? 0);
    $version = trim($_POST['version'] ?? '1.0');
    $pdf_hint = trim($_POST['pdf_hint'] ?? '');

    if (empty($title) || empty($section_id) || empty($_FILES['pdf_file']['name'])) {
        $error_msg = 'Please complete all required fields and choose a PDF file.';
    } else {
        // Auto-generate Policy Code if not provided
        if (empty($policy_number)) {
            $policy_number = 'POL-OFBL-' . date('Y') . '-' . str_pad((string)rand(10, 999), 3, '0', STR_PAD_LEFT);
        }

        $file_name = $_FILES['pdf_file']['name'];
        $file_tmp = $_FILES['pdf_file']['tmp_name'];
        $file_size = $_FILES['pdf_file']['size'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if ($ext !== 'pdf') {
            $error_msg = 'Security Protocol: Only official PDF documents (.pdf) are permitted.';
        } elseif ($file_size > 20 * 1024 * 1024) {
            $error_msg = 'File size limit exceeded (maximum 20MB allowed).';
        } else {
            // Sanitize filename
            $safe_filename = preg_replace('/[^A-Za-z0-9_.-]/', '_', $file_name);
            $target_filename = time() . '_' . $safe_filename;
            $destination = __DIR__ . '/../uploads/pdfs/' . $target_filename;

            if (move_uploaded_file($file_tmp, $destination)) {
                $file_size_kb = (int)round($file_size / 1024);
                $uploader_id = $_SESSION['user_id'];
                $status = 'active';

                $stmt = $conn->prepare("INSERT INTO policies (policy_number, title, section_id, policy_date, pdf_file, pdf_hint, file_size_kb, version, status, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssisssissi", $policy_number, $title, $section_id, $policy_date, $target_filename, $pdf_hint, $file_size_kb, $version, $status, $uploader_id);

                if ($stmt->execute()) {
                    log_audit($conn, $uploader_id, $_SESSION['name'], 'POLICY_UPLOAD', "Uploaded directive: {$policy_number} - {$title}");
                    set_toast('success', 'Policy directive published successfully to the organizational vault!');
                    header("Location: manage_policies.php");
                    exit();
                } else {
                    $error_msg = 'Database insertion error: ' . $conn->error;
                }
                $stmt->close();
            } else {
                $error_msg = 'Failed to store uploaded PDF file. Please verify uploads directory write permissions.';
            }
        }
    }
}

// Fetch sections for select dropdown
$sections = [];
$res = $conn->query("SELECT id, section_name, section_code FROM sections ORDER BY section_name ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) $sections[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Policy Directive | OFBL Admin</title>
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
            <li><a href="upload_policy.php" class="active"><i class="bi bi-cloud-arrow-up-fill"></i> Upload Directive</a></li>
            <li><a href="sections.php"><i class="bi bi-building"></i> Sections &amp; Depts</a></li>
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
                <h2>Upload Official Policy Directive</h2>
                <p>Publish a classified or general policy PDF into the secure OFBL digital repository</p>
            </div>
            <div class="topbar-right">
                <a href="manage_policies.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Policies</a>
            </div>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger" style="background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #f87171; padding: 14px 20px; border-radius: 12px; margin-bottom: 20px;">
                <i class="bi bi-exclamation-octagon-fill"></i> <?php echo e($error_msg); ?>
            </div>
        <?php endif; ?>

        <div class="form-box">
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>

                <div class="input-group">
                    <label>Directive Title *</label>
                    <input type="text" name="title" placeholder="e.g. Standard Operating Procedure for Munitions Storage" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="input-group">
                        <label>Policy Reference Number</label>
                        <input type="text" name="policy_number" placeholder="e.g. POL-PROD-2026-008 (Leave blank for auto-gen)">
                    </div>
                    <div class="input-group">
                        <label>Effective Release Date *</label>
                        <input type="date" name="policy_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                    <div class="input-group">
                        <label>Target Department / Section *</label>
                        <select name="section_id" required>
                            <option value="">-- Select Target Department --</option>
                            <?php foreach ($sections as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo e($s['section_name'] . ' (' . $s['section_code'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Revision Version</label>
                        <input type="text" name="version" value="1.0" placeholder="e.g. 1.0, 2.1">
                    </div>
                </div>

                <div class="input-group">
                    <label>Executive Summary &amp; Hints</label>
                    <textarea name="pdf_hint" rows="3" placeholder="Brief summary outlining the mandatory scope, compliance targets, and applicable personnel..."></textarea>
                </div>

                <div class="input-group">
                    <label>Select Official PDF File *</label>
                    <input type="file" name="pdf_file" accept=".pdf" required style="background: var(--surface); padding: 10px;">
                    <small style="color: var(--text-muted); font-size: 12px;">Authorized format: PDF only. Maximum file size: 20MB.</small>
                </div>

                <button type="submit" name="upload" class="btn btn-primary" style="padding: 12px 24px; font-size: 14px;">
                    <i class="bi bi-cloud-arrow-up-fill"></i> Upload &amp; Authorize Directive
                </button>
            </form>
        </div>
    </div>
</body>
</html>
