<?php
/**
 * OFBL Policy Management Portal — Secure Authentication Gateway
 * Ordnance Factory Badmal, Munitions India Limited (MIL)
 * Ministry of Defence, Government of India
 * Lead Developer: Shreyas Sankalp Sahu (KIIT CSE)
 */

ob_start();
session_start();

require_once 'db.php';
require_once 'includes/helpers.php';

// If already logged in, redirect to respective dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit();
    } else {
        header("Location: user/dashboard.php");
        exit();
    }
}

$error_msg = '';
$info_msg = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'timeout') {
        $info_msg = 'Your session timed out after 30 minutes of inactivity. Please re-authenticate.';
    } elseif ($_GET['msg'] === 'login_required') {
        $info_msg = 'Access denied. Please log in to access portal resources.';
    } elseif ($_GET['msg'] === 'logout') {
        $info_msg = 'You have successfully signed out of the OFBL Portal.';
    }
}

// Handle Login Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_msg = 'Please enter both your official email address and password.';
    } elseif (isset($db_connection_error)) {
        $error_msg = 'Database not connected. Please run the <a href="install.php" style="color: #60a5fa; text-decoration: underline;">Database Setup Utility</a>.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    if ($user['status'] !== 'approved') {
                        $error_msg = 'Access Pending: Your account is awaiting clearance from the OFBL System Administrator.';
                    } else {
                        // Prevent Session Fixation
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['name'] = $user['name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['LAST_ACTIVITY'] = time();

                        // Audit Trail
                        log_audit($conn, $user['id'], $user['name'], 'USER_LOGIN', 'Authenticated successfully from ' . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown IP'));

                        if ($user['role'] === 'admin') {
                            header("Location: admin/dashboard.php");
                        } else {
                            header("Location: user/dashboard.php");
                        }
                        exit();
                    }
                } else {
                    $error_msg = 'Authentication failed: Invalid password entered.';
                }
            } else {
                $error_msg = 'No authorized record found matching that email address.';
            }
            $stmt->close();
        } else {
            $error_msg = 'System query error. Please contact the ITC Helpdesk.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OFBL Policy Portal | Ordnance Factory Badmal</title>
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
        /* TOP BANNER */
        .gov-banner {
            background: #020617;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            padding: 10px 30px;
            font-size: 12px;
            color: #94a3b8;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .gov-banner .emblem {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #e2e8f0;
            font-weight: 600;
        }
        /* HEADER */
        .portal-header {
            padding: 18px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(12px);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .brand-logo {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #2563eb, #1e40af);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 20px;
            color: white;
            box-shadow: 0 4px 16px var(--accent-glow);
        }
        .brand-text h1 {
            font-size: 19px;
            font-weight: 800;
            letter-spacing: -0.2px;
        }
        .brand-text p {
            font-size: 12px;
            color: var(--muted);
        }
        .live-demo-pill {
            background: rgba(16,185,129,0.15);
            border: 1px solid rgba(16,185,129,0.3);
            color: #34d399;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .live-demo-pill:hover { background: rgba(16,185,129,0.25); transform: translateY(-1px); }
        /* CONTAINER */
        .container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .login-card {
            width: 100%;
            max-width: 480px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.6);
            animation: fadeIn 0.6s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .card-header {
            text-align: center;
            margin-bottom: 28px;
        }
        .card-header h2 {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 6px;
        }
        .card-header p {
            color: var(--muted);
            font-size: 13.5px;
        }
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
        .alert-info { background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.3); color: #60a5fa; }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #cbd5e1;
        }
        .input-wrapper {
            position: relative;
        }
        .input-wrapper input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            color: white;
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
        }
        .input-wrapper input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }
        .toggle-pw {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--muted);
            font-size: 16px;
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
            transition: all 0.2s;
            box-shadow: 0 4px 14px var(--accent-glow);
            margin-top: 10px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37,99,235,0.4);
        }
        .quick-fill-box {
            background: rgba(15,23,42,0.4);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            padding: 14px;
            margin-top: 24px;
            font-size: 12px;
        }
        .quick-fill-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            color: var(--muted);
            font-weight: 600;
        }
        .quick-fill-btns {
            display: flex;
            gap: 8px;
        }
        .quick-btn {
            flex: 1;
            padding: 6px 10px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            color: #cbd5e1;
            border-radius: 8px;
            font-size: 11.5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .quick-btn:hover { background: rgba(37,99,235,0.2); border-color: #3b82f6; color: white; }
        .card-footer-links {
            margin-top: 24px;
            text-align: center;
            font-size: 13px;
        }
        .card-footer-links a {
            color: #60a5fa;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            margin: 4px 10px;
            transition: color 0.2s;
        }
        .card-footer-links a:hover { text-decoration: underline; color: #93c5fd; }
        .footer {
            padding: 16px 20px;
            text-align: center;
            border-top: 1px solid rgba(255,255,255,0.06);
            font-size: 12px;
            color: var(--muted);
            background: #020617;
        }
        @media (max-width: 600px) {
            .portal-header { padding: 14px 20px; }
            .login-card { padding: 30px 20px; }
        }
    </style>
</head>
<body>

    <!-- GOV BANNER -->
    <div class="gov-banner">
        <div class="emblem">
            <i class="bi bi-shield-check"></i>
            <span>Ordnance Factory Badmal (OFBL) &bull; Munitions India Limited &bull; Ministry of Defence</span>
        </div>
        <div>Classified Governance Portal</div>
    </div>

    <!-- PORTAL HEADER -->
    <div class="portal-header">
        <div class="brand">
            <div class="brand-logo">OFBL</div>
            <div class="brand-text">
                <h1>OFBL Policy Portal</h1>
                <p>Enterprise Policy Governance &amp; Document Management</p>
            </div>
        </div>
        <div>
            <a href="index.html" class="live-demo-pill">
                <i class="bi bi-lightning-charge-fill"></i> Open Live Interactive Demo
            </a>
        </div>
    </div>

    <!-- LOGIN CONTAINER -->
    <div class="container">
        <div class="login-card">
            <div class="card-header">
                <h2>Official Sign In</h2>
                <p>Enter your credentials to access the departmental vault</p>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="alert-box alert-danger">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                    <div><?php echo $error_msg; ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($info_msg)): ?>
                <div class="alert-box alert-info">
                    <i class="bi bi-info-circle-fill"></i>
                    <div><?php echo e($info_msg); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <?php echo csrf_field(); ?>

                <div class="form-group">
                    <label for="email">Official Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" placeholder="e.g. name@ofbl.gov.in" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Account Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <i class="bi bi-eye toggle-pw" id="togglePw" onclick="togglePasswordVisibility()"></i>
                    </div>
                </div>

                <button type="submit" name="login" class="btn-submit">
                    <i class="bi bi-box-arrow-in-right"></i> Secure Sign In
                </button>
            </form>

            <!-- DEMO AUTO-FILL HELPER FOR EVALUATORS & FAANG RECRUITERS -->
            <div class="quick-fill-box">
                <div class="quick-fill-header">
                    <span><i class="bi bi-person-badge-fill"></i> Quick Credential Auto-fill:</span>
                    <a href="install.php" style="color: #60a5fa; text-decoration: none;">Setup DB</a>
                </div>
                <div class="quick-fill-btns">
                    <button type="button" class="quick-btn" onclick="fillDemo('admin@ofbl.gov.in', 'Admin@OFBL2026!')">
                        <i class="bi bi-shield-lock-fill" style="color:#d4af37;"></i> Officer (Admin)
                    </button>
                    <button type="button" class="quick-btn" onclick="fillDemo('rajesh.sharma@ofbl.gov.in', 'User@OFBL2026!')">
                        <i class="bi bi-person-badge-fill" style="color:#38bdf8;"></i> Personnel (Staff)
                    </button>
                </div>
            </div>

            <div class="card-footer-links">
                <a href="forgot_password.php"><i class="bi bi-key"></i> Forgot Password?</a>
                <a href="signup.php"><i class="bi bi-person-plus"></i> Request User Access</a>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        &copy; 2026 Ordnance Factory Badmal, Munitions India Limited (MIL) | Project Architect: <strong>Shreyas Sankalp Sahu</strong> (KIIT University)
    </div>

    <script>
        function togglePasswordVisibility() {
            var pw = document.getElementById('password');
            var icon = document.getElementById('togglePw');
            if (pw.type === 'password') {
                pw.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                pw.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }

        function fillDemo(email, pass) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = pass;
        }
    </script>
</body>
</html>
