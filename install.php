<?php
/**
 * 1-Click Database Setup & Migrator
 * OFBL Policy Management System
 */

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$port = (int)(getenv('DB_PORT') ?: 3306);
$database = 'ofb_policy_db';

$status_msg = '';
$status_type = '';

if (isset($_POST['install_db'])) {
    $conn = @mysqli_connect($host, $user, $password, '', $port);
    if (!$conn) {
        $status_msg = "Could not connect to MySQL server on {$host}: " . mysqli_connect_error();
        $status_type = "error";
    } else {
        $sql_file = __DIR__ . '/database.sql';
        if (!file_exists($sql_file)) {
            $status_msg = "database.sql file not found.";
            $status_type = "error";
        } else {
            $sql = file_get_contents($sql_file);
            mysqli_multi_query($conn, $sql);
            do {
                if ($result = mysqli_store_result($conn)) {
                    mysqli_free_result($result);
                }
            } while (mysqli_more_results($conn) && mysqli_next_result($conn));
            
            $status_msg = "Database 'ofb_policy_db' created and tables seeded successfully! Default Admin: admin@ofbl.gov.in (Password: Admin@OFBL2026!)";
            $status_type = "success";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OFBL System Setup | Ordnance Factory Badmal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #0F172A;
            --accent: #2563EB;
            --accent-glow: rgba(37,99,235,0.25);
            --bg: #0b1120;
            --card-bg: rgba(30, 41, 59, 0.7);
            --border: rgba(255,255,255,0.1);
            --text: #F8FAFC;
            --muted: #94A3B8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body {
            background: radial-gradient(circle at 50% 0%, #1e293b 0%, #0b1120 100%);
            min-height: 100vh;
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .install-card {
            width: 100%;
            max-width: 580px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            backdrop-filter: blur(16px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(37,99,235,0.15);
            color: #60a5fa;
            border: 1px solid rgba(96,165,250,0.3);
            font-size: 12px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 9999px;
            margin-bottom: 20px;
        }
        h1 { font-size: 26px; font-weight: 800; margin-bottom: 8px; }
        p { color: var(--muted); font-size: 14px; line-height: 1.6; margin-bottom: 24px; }
        .spec-box {
            background: rgba(15,23,42,0.6);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 24px;
            font-size: 13px;
        }
        .spec-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .spec-row:last-child { border-bottom: none; }
        .spec-row span { color: var(--muted); }
        .spec-row strong { color: #e2e8f0; }
        .btn {
            width: 100%;
            padding: 14px 20px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none;
            color: white;
            font-weight: 700;
            font-size: 15px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 14px var(--accent-glow);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(37,99,235,0.4); }
        .btn-secondary {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #cbd5e1;
            margin-top: 12px;
            box-shadow: none;
        }
        .btn-secondary:hover { background: rgba(255,255,255,0.1); transform: none; }
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); color: #34d399; }
        .alert-error { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #f87171; }
    </style>
</head>
<body>
    <div class="install-card">
        <div class="badge"><i class="bi bi-shield-lock-fill"></i> DEFENCE ENTERPRISE SETUP</div>
        <h1>OFBL Policy Portal Database Setup</h1>
        <p>This automated migration script configures the MySQL database, creates relational tables with foreign keys, and seeds official Ordnance Factory Badmal policies and users.</p>

        <?php if ($status_msg): ?>
            <div class="alert alert-<?php echo $status_type; ?>">
                <i class="bi bi-<?php echo $status_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>-fill"></i>
                <div><?php echo htmlspecialchars($status_msg); ?></div>
            </div>
        <?php endif; ?>

        <div class="spec-box">
            <div class="spec-row"><span>Host Target</span><strong><?php echo htmlspecialchars($host . ':' . $port); ?></strong></div>
            <div class="spec-row"><span>Database Name</span><strong><?php echo htmlspecialchars($database); ?></strong></div>
            <div class="spec-row"><span>Default Administrator</span><strong>admin@ofbl.gov.in</strong></div>
            <div class="spec-row"><span>Admin Password</span><strong>Admin@OFBL2026!</strong></div>
        </div>

        <form method="POST">
            <button type="submit" name="install_db" class="btn">
                <i class="bi bi-database-fill-gear"></i> Run 1-Click Database Setup
            </button>
        </form>

        <a href="index.php" class="btn btn-secondary">
            <i class="bi bi-box-arrow-in-right"></i> Launch Policy Portal
        </a>
    </div>
</body>
</html>