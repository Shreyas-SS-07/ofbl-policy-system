<?php
/**
 * OFBL Policy Governance — Security & Password Update
 * Ordnance Factory Badmal, Munitions India Limited (MIL)
 */

ob_start();
session_start();

require_once '../db.php';
require_once '../includes/helpers.php';
require_once '../includes/auth.php';

require_login();

$error_msg = '';
$success_msg = '';
$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    verify_csrf();

    $old_pass = $_POST['old_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $conf_pass = $_POST['confirm_password'] ?? '';

    if (empty($old_pass) || empty($new_pass)) {
        $error_msg = 'Please complete all required password fields.';
    } elseif (strlen($new_pass) < 6) {
        $error_msg = 'New password must be at least 6 characters in length.';
    } elseif ($new_pass !== $conf_pass) {
        $error_msg = 'New password and confirmation password do not match.';
    } else {
        $stmt = $conn->prepare("SELECT password, name FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($old_pass, $user['password'])) {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $up = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $up->bind_param("si", $new_hash, $user_id);
            $up->execute();
            $up->close();

            log_audit($conn, $user_id, $user['name'], 'PASSWORD_CHANGE', 'User updated account password');
            $success_msg = 'Password changed successfully!';
        } else {
            $error_msg = 'Verification failed: Current password is incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | OFBL Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo-icon"><i class="bi bi-shield-check"></i></div>
            <div class="sidebar-title">
                <h2>OFBL PORTAL</h2>
                <p>Munitions India Limited</p>
            </div>
        </div>
        <ul class="sidebar-nav">
            <li class="nav-label">Main Navigation</li>
            <li><a href="dashboard.php"><i class="bi bi-folder2-open"></i> Policy Vault</a></li>
            <li><a href="profile.php"><i class="bi bi-person-badge"></i> My Personnel Profile</a></li>
            <li><a href="change_password.php" class="active"><i class="bi bi-key-fill"></i> Security Settings</a></li>
            <li><a href="about.php"><i class="bi bi-info-circle-fill"></i> About OFBL &amp; Project</a></li>
            <li class="nav-label">Session</li>
            <li><a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Sign Out</a></li>
        </ul>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h2>Update Security Credentials</h2>
                <p>Maintain cyber hygiene by regularly updating your intranet password</p>
            </div>
            <div class="topbar-right">
                <button class="btn-theme" onclick="document.body.classList.toggle('dark-mode')">🌓 Theme</button>
            </div>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger" style="background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #f87171; padding: 14px 20px; border-radius: 12px; margin-bottom: 20px;">
                <i class="bi bi-exclamation-octagon-fill"></i> <?php echo e($error_msg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success" style="background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); color: #34d399; padding: 14px 20px; border-radius: 12px; margin-bottom: 20px;">
                <i class="bi bi-check-circle-fill"></i> <?php echo e($success_msg); ?>
            </div>
        <?php endif; ?>

        <div class="form-box" style="max-width: 500px;">
            <form method="POST">
                <?php echo csrf_field(); ?>

                <div class="input-group">
                    <label>Current Password *</label>
                    <input type="password" name="old_password" placeholder="Enter existing password" required>
                </div>

                <div class="input-group">
                    <label>New Password (Min. 6 characters) *</label>
                    <input type="password" name="new_password" placeholder="Create new password" required>
                </div>

                <div class="input-group">
                    <label>Confirm New Password *</label>
                    <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                </div>

                <button type="submit" name="change_password" class="btn btn-primary" style="padding: 12px 24px;">
                    <i class="bi bi-shield-lock-fill"></i> Save New Password
                </button>
            </form>
        </div>
    </div>
</body>
</html>
