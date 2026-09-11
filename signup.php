<?php
/**
 * OFBL Policy Portal — Employee Registration
 * Ordnance Factory Badmal, Munitions India Limited
 */

ob_start();
session_start();

require_once 'db.php';
require_once 'includes/helpers.php';

$error_msg = '';
$success_msg = '';

// Fetch active sections for department dropdown
$sections = [];
if (isset($conn) && $conn) {
    $res = $conn->query("SELECT id, section_name, section_code FROM sections ORDER BY section_name ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $sections[] = $row;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error_msg = 'Please fill out all mandatory registration fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Please provide a valid official email address.';
    } elseif (strlen($password) < 6) {
        $error_msg = 'Password must be at least 6 characters in length.';
    } elseif ($password !== $confirm_password) {
        $error_msg = 'Entered passwords do not match. Please re-enter.';
    } else {
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $error_msg = 'An account is already associated with this email address.';
            $check_stmt->close();
        } else {
            $check_stmt->close();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $role = 'user';
            $status = 'pending';

            $ins_stmt = $conn->prepare("INSERT INTO users (name, email, password, department_id, role, status, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins_stmt->bind_param("sssisss", $name, $email, $hash, $department_id, $role, $status, $ip);

            if ($ins_stmt->execute()) {
                $new_id = $ins_stmt->insert_id;
                log_audit($conn, $new_id, $name, 'USER_REGISTER', "New user registration submitted from {$ip}");
                $success_msg = 'Registration request submitted successfully! Your account is pending Administrative clearance. You may log in once approved.';
            } else {
                $error_msg = 'Registration failure: ' . $conn->error;
            }
            $ins_stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration | OFBL Policy Portal</title>
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
            flex-direction: column;
            color: var(--text);
        }
        .container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .card {
            width: 100%;
            max-width: 520px;
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
        .form-group input, .form-group select {
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
        .form-group select option { background: #1e293b; color: white; }
        .form-group input:focus, .form-group select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }
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
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Request Personnel Account</h2>
                <p>Ordnance Factory Badmal &bull; Munitions India Limited</p>
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
                    <label>Full Official Name</label>
                    <input type="text" name="name" placeholder="e.g. Ramesh Chandra Sethi" required>
                </div>

                <div class="form-group">
                    <label>Official Email Address</label>
                    <input type="email" name="email" placeholder="e.g. ramesh.sethi@ofbl.gov.in" required>
                </div>

                <div class="form-group">
                    <label>Assigned Department / Section</label>
                    <select name="department_id" required>
                        <option value="">-- Select Official Department --</option>
                        <?php foreach ($sections as $sec): ?>
                            <option value="<?php echo $sec['id']; ?>"><?php echo e($sec['section_name'] . ' (' . $sec['section_code'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Password (Min. 6 chars)</label>
                    <input type="password" name="password" placeholder="Create password" required>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm password" required>
                </div>

                <button type="submit" name="signup" class="btn-submit">
                    <i class="bi bi-person-check-fill"></i> Submit Registration
                </button>
            </form>

            <div class="card-footer">
                <a href="index.php"><i class="bi bi-arrow-left"></i> Return to Sign In</a>
            </div>
        </div>
    </div>
</body>
</html>
