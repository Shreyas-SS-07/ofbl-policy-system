<?php
/**
 * OFBL Policy Portal — Password Recovery
 * Ordnance Factory Badmal, Munitions India Limited
 */

ob_start();
session_start();

require_once 'db.php';
require_once 'includes/helpers.php';

$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($new_password)) {
        $error_msg = 'Please enter your email and new password.';
    } elseif (strlen($new_password) < 6) {
        $error_msg = 'Password must be at least 6 characters in length.';
    } elseif ($new_password !== $confirm_password) {
        $error_msg = 'Passwords do not match. Please verify and retry.';
    } else {
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $user = $res->fetch_assoc();
            $stmt->close();

            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $up_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $up_stmt->bind_param("si", $hash, $user['id']);

            if ($up_stmt->execute()) {
                log_audit($conn, $user['id'], $user['name'], 'PASSWORD_RESET', 'Self-service password update');
                $success_msg = 'Password updated successfully! You may now sign in with your new credentials.';
            } else {
                $error_msg = 'Error updating password: ' . $conn->error;
            }
            $up_stmt->close();
        } else {
            $error_msg = 'No authorized user account found for that email address.';
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | OFBL Policy Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #0F172A;
            --accent: #2563EB;
            --accent-glow: rgba(37,99,235,0.3);
            --bg: #0b1120;
            --card-bg: rgba(30, 41, 59, 0.75);
            --border: rgba(255,255,255,0.1);
            --text: #F8FAFC;
            --muted: #94A3B8;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body {
            min-height: 100vh;
            background: radial-gradient(circle at 50% 0%, #1e293b 0%, #0b1120 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--text);
        }
        .card {
            width: 100%;
            max-width: 480px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.6);
        }
        .card-header { text-align: center; margin-bottom: 24px; }
        .card-header h2 { font-size: 24px; font-weight: 800; margin-bottom: 6px; }
        .card-header p { color: var(--muted); font-size: 13.5px; }
        .alert-box {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-danger { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #f87171; }
        .alert-success { background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); color: #34d399; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: #cbd5e1; }
        .form-group input {
            width: 100%;
            padding: 12px 14px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: white;
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
        }
        .form-group input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none;
            color: white;
            font-size: 15px;
            font-weight: 700;
            border-radius: 12px;
            cursor: pointer;
            margin-top: 10px;
            box-shadow: 0 4px 14px var(--accent-glow);
            transition: all 0.2s;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(37,99,235,0.4); }
        .card-footer { margin-top: 20px; text-align: center; font-size: 13px; }
        .card-footer a { color: #60a5fa; text-decoration: none; font-weight: 600; }
        .card-footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h2>Password Recovery</h2>
            <p>Update your portal access key</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert-box alert-danger"><i class="bi bi-exclamation-octagon-fill"></i><div><?php echo e($error_msg); ?></div></div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert-box alert-success"><i class="bi bi-check-circle-fill"></i><div><?php echo e($success_msg); ?></div></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label>Registered Email</label>
                <input type="email" name="email" placeholder="Enter your registered email" required>
            </div>

            <div class="form-group">
                <label>New Password (Min. 6 chars)</label>
                <input type="password" name="new_password" placeholder="Enter new password" required>
            </div>

            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm new password" required>
            </div>

            <button type="submit" name="reset" class="btn-submit">
                <i class="bi bi-shield-lock-fill"></i> Reset Password
            </button>
        </form>

        <div class="card-footer">
            <a href="index.php"><i class="bi bi-arrow-left"></i> Return to Sign In</a>
        </div>
    </div>
</body>
</html>
