<?php
/**
 * OFBL Policy Governance — Personnel Profile
 * Ordnance Factory Badmal, Munitions India Limited (MIL)
 */

ob_start();
session_start();

require_once '../db.php';
require_once '../includes/helpers.php';
require_once '../includes/auth.php';

require_login();

$user_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT users.*, sections.section_name, sections.section_code FROM users LEFT JOIN sections ON users.department_id = sections.id WHERE users.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar'])) {
    verify_csrf();

    if (!empty($_FILES['profile_image']['name'])) {
        $file_name = $_FILES['profile_image']['name'];
        $tmp = $_FILES['profile_image']['tmp_name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {
            $new_name = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
            $dest = __DIR__ . '/../uploads/profile/' . $new_name;

            if (move_uploaded_file($tmp, $dest)) {
                $up = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $up->bind_param("si", $new_name, $user_id);
                $up->execute();
                $up->close();

                log_audit($conn, $user_id, $user['name'], 'PROFILE_UPDATE', 'Updated profile photo');
                set_toast('success', 'Profile photo updated successfully.');
                header("Location: profile.php");
                exit();
            }
        } else {
            set_toast('danger', 'Invalid format. Allowed image types: JPG, PNG, WEBP.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personnel Profile | OFBL Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .profile-hero {
            background: linear-gradient(135deg, #0b192c, #1e3e62);
            padding: 36px;
            border-radius: var(--radius-md);
            color: white;
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: 24px;
        }
        .profile-img-wrap {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 4px solid rgba(255,255,255,0.2);
            font-size: 36px;
            color: #2563eb;
            font-weight: 800;
        }
        .profile-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
        }
        .detail-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 16px;
        }
        .detail-box span { font-size: 11.5px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }
        .detail-box h4 { font-size: 15px; font-weight: 700; margin-top: 4px; }
    </style>
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
            <li><a href="profile.php" class="active"><i class="bi bi-person-badge"></i> My Personnel Profile</a></li>
            <li><a href="change_password.php"><i class="bi bi-key-fill"></i> Security Settings</a></li>
            <li><a href="about.php"><i class="bi bi-info-circle-fill"></i> About OFBL &amp; Project</a></li>
            <li class="nav-label">Session</li>
            <li><a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Sign Out</a></li>
        </ul>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h2>Official Personnel Credentials</h2>
                <p>Ordnance Factory Badmal staff identity &amp; authorization status</p>
            </div>
            <div class="topbar-right">
                <button class="btn-theme" onclick="document.body.classList.toggle('dark-mode')">🌓 Theme</button>
            </div>
        </div>

        <?php echo render_toast(); ?>

        <div class="profile-hero">
            <div class="profile-img-wrap">
                <?php if (!empty($user['profile_image']) && file_exists(__DIR__ . '/../uploads/profile/' . $user['profile_image'])): ?>
                    <img src="../uploads/profile/<?php echo e($user['profile_image']); ?>?v=<?php echo time(); ?>" alt="Avatar">
                <?php else: ?>
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                <?php endif; ?>
            </div>
            <div>
                <span class="badge-pill badge-green" style="margin-bottom: 6px; display:inline-block;">
                    <i class="bi bi-patch-check-fill"></i> ACTIVE CLEARANCE
                </span>
                <h2 style="font-size: 24px; font-weight: 800;"><?php echo e($user['name']); ?></h2>
                <p style="color: #cbd5e1; font-size: 13.5px; margin-top: 4px;">
                    <i class="bi bi-envelope"></i> <?php echo e($user['email']); ?> &bull;
                    <i class="bi bi-building"></i> <?php echo e($user['section_name'] ?? 'General Division'); ?>
                </p>
            </div>
        </div>

        <div class="panel" style="margin-bottom: 24px;">
            <h3 style="font-size: 16px; margin-bottom: 16px;">Identity &amp; System Permissions</h3>
            <div class="details-grid">
                <div class="detail-box">
                    <span>Assigned Role</span>
                    <h4><?php echo strtoupper($user['role']); ?></h4>
                </div>
                <div class="detail-box">
                    <span>Department / Bay</span>
                    <h4><?php echo e($user['section_name'] ?? 'Factory-Wide'); ?></h4>
                </div>
                <div class="detail-box">
                    <span>Security Clearance</span>
                    <h4 style="color: #10b981;"><?php echo strtoupper($user['status']); ?></h4>
                </div>
                <div class="detail-box">
                    <span>Terminal Origin</span>
                    <h4 style="font-family: monospace;"><?php echo e($user['ip_address'] ?? '127.0.0.1'); ?></h4>
                </div>
            </div>
        </div>

        <div class="panel">
            <h3 style="font-size: 16px; margin-bottom: 16px;">Update Personnel Photo</h3>
            <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 14px; align-items: center; max-width: 500px;">
                <?php echo csrf_field(); ?>
                <input type="file" name="profile_image" accept="image/*" required style="flex: 1;">
                <button type="submit" name="upload_avatar" class="btn btn-primary">Upload</button>
            </form>
        </div>
    </div>
</body>
</html>
