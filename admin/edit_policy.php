<?php
/**
 * OFBL Policy Governance — Edit Policy Directive
 * Ordnance Factory Badmal, Munitions India Limited (MIL)
 */

ob_start();
session_start();

require_once '../db.php';
require_once '../includes/helpers.php';
require_once '../includes/auth.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: manage_policies.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM policies WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    header("Location: manage_policies.php");
    exit();
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_policy'])) {
    verify_csrf();

    $title = trim($_POST['title'] ?? '');
    $policy_number = trim($_POST['policy_number'] ?? '');
    $policy_date = $_POST['policy_date'] ?? date('Y-m-d');
    $section_id = (int)($_POST['section_id'] ?? 0);
    $version = trim($_POST['version'] ?? '1.0');
    $pdf_hint = trim($_POST['pdf_hint'] ?? '');

    if (empty($title) || empty($section_id)) {
        $error_msg = 'Please fill out all required fields.';
    } else {
        if (!empty($_FILES['pdf_file']['name'])) {
            $file_name = $_FILES['pdf_file']['name'];
            $file_tmp = $_FILES['pdf_file']['tmp_name'];
            $file_size = $_FILES['pdf_file']['size'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if ($ext !== 'pdf') {
                $error_msg = 'Only PDF documents (.pdf) are allowed.';
            } else {
                $safe_filename = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $file_name);
                $destination = __DIR__ . '/../uploads/pdfs/' . $safe_filename;

                if (move_uploaded_file($file_tmp, $destination)) {
                    // Delete old PDF file
                    $old_file = __DIR__ . '/../uploads/pdfs/' . $data['pdf_file'];
                    if (file_exists($old_file) && is_file($old_file)) @unlink($old_file);

                    $file_size_kb = (int)round($file_size / 1024);
                    $up = $conn->prepare("UPDATE policies SET policy_number=?, title=?, section_id=?, policy_date=?, pdf_file=?, pdf_hint=?, file_size_kb=?, version=? WHERE id=?");
                    $up->bind_param("ssisssisi", $policy_number, $title, $section_id, $policy_date, $safe_filename, $pdf_hint, $file_size_kb, $version, $id);
                    $up->execute();
                    $up->close();

                    log_audit($conn, $_SESSION['user_id'], $_SESSION['name'], 'POLICY_UPDATE', "Updated policy & PDF: {$title}");
                    set_toast('success', 'Policy and PDF document updated successfully.');
                    header("Location: manage_policies.php");
                    exit();
                }
            }
        } else {
            // Update without changing PDF file
            $up = $conn->prepare("UPDATE policies SET policy_number=?, title=?, section_id=?, policy_date=?, pdf_hint=?, version=? WHERE id=?");
            $up->bind_param("ssisssi", $policy_number, $title, $section_id, $policy_date, $pdf_hint, $version, $id);
            $up->execute();
            $up->close();

            log_audit($conn, $_SESSION['user_id'], $_SESSION['name'], 'POLICY_UPDATE', "Updated policy metadata: {$title}");
            set_toast('success', 'Policy metadata updated successfully.');
            header("Location: manage_policies.php");
            exit();
        }
    }
}

$sections = [];
$res = $conn->query("SELECT id, section_name FROM sections ORDER BY section_name ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) $sections[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Policy Directive | OFBL Admin</title>
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
            <li><a href="manage_policies.php" class="active"><i class="bi bi-file-earmark-text-fill"></i> Manage Policies</a></li>
            <li><a href="upload_policy.php"><i class="bi bi-cloud-arrow-up-fill"></i> Upload Directive</a></li>
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
                <h2>Modify Policy Directive</h2>
                <p>Edit metadata, change assigned department, or replace document revision</p>
            </div>
            <div class="topbar-right">
                <a href="manage_policies.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Policies</a>
            </div>
        </div>

        <div class="form-box">
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>

                <div class="input-group">
                    <label>Policy Title *</label>
                    <input type="text" name="title" value="<?php echo e($data['title']); ?>" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="input-group">
                        <label>Policy Code / ID</label>
                        <input type="text" name="policy_number" value="<?php echo e($data['policy_number']); ?>" required>
                    </div>
                    <div class="input-group">
                        <label>Effective Date *</label>
                        <input type="date" name="policy_date" value="<?php echo e($data['policy_date']); ?>" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                    <div class="input-group">
                        <label>Department / Section *</label>
                        <select name="section_id" required>
                            <?php foreach ($sections as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo $s['id'] == $data['section_id'] ? 'selected' : ''; ?>>
                                    <?php echo e($s['section_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Version Tag</label>
                        <input type="text" name="version" value="<?php echo e($data['version']); ?>" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>Summary &amp; Hints</label>
                    <textarea name="pdf_hint" rows="3"><?php echo e($data['pdf_hint']); ?></textarea>
                </div>

                <div class="input-group">
                    <label>Replace PDF File (Optional)</label>
                    <input type="file" name="pdf_file" accept=".pdf">
                    <small style="color: var(--text-muted); font-size: 12px;">
                        Current file: <strong><?php echo e($data['pdf_file']); ?></strong>. Leave blank to retain existing file.
                    </small>
                </div>

                <button type="submit" name="update_policy" class="btn btn-primary" style="padding: 12px 24px;">
                    <i class="bi bi-check-circle-fill"></i> Save Modifications
                </button>
            </form>
        </div>
    </div>
</body>
</html>
