<?php
/**
 * OFBL Policy Governance — Personnel Portal & Document Vault
 * Ordnance Factory Badmal, Munitions India Limited (MIL)
 * Ministry of Defence, Government of India
 * Lead Developer: Shreyas Sankalp Sahu (KIIT CSE)
 */

ob_start();
session_start();

require_once '../db.php';
require_once '../includes/helpers.php';
require_once '../includes/auth.php';

require_login();

$user_id = (int)$_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'Personnel';

// Handle Comment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    verify_csrf();

    $policy_id = (int)($_POST['policy_id'] ?? 0);
    $comment_text = trim($_POST['comment'] ?? '');

    if ($policy_id > 0 && !empty($comment_text)) {
        $stmt = $conn->prepare("INSERT INTO comments (policy_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $policy_id, $user_id, $comment_text);
        if ($stmt->execute()) {
            log_audit($conn, $user_id, $user_name, 'COMMENT_SUBMIT', "Commented on Policy ID {$policy_id}");
            set_toast('success', 'Your query/comment has been posted to the policy discussion thread.');
        }
        $stmt->close();
    }
    header("Location: dashboard.php");
    exit();
}

// Search and Department Filtering
$search = trim($_GET['search'] ?? '');
$sec_filter = (int)($_GET['section'] ?? 0);

$query_str = "SELECT policies.*, sections.section_name, sections.section_code
              FROM policies
              LEFT JOIN sections ON policies.section_id = sections.id
              WHERE policies.status = 'active'";

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

if ($sec_filter > 0) {
    $query_str .= " AND policies.section_id = ?";
    $params[] = $sec_filter;
    $types .= "i";
}

$query_str .= " ORDER BY policies.id DESC";

$stmt = $conn->prepare($query_str);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$policies = $stmt->get_result();

$sections = $conn->query("SELECT id, section_name, section_code FROM sections ORDER BY section_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personnel Policy Portal | OFBL Badmal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .comment-stream {
            background: var(--surface-secondary);
            border-radius: var(--radius-sm);
            padding: 10px;
            margin-top: 8px;
            max-height: 150px;
            overflow-y: auto;
            border-left: 3px solid var(--accent);
        }
        .comment-item {
            margin-bottom: 6px;
            padding-bottom: 6px;
            border-bottom: 1px solid var(--border);
            font-size: 12px;
        }
        .comment-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .comment-author { font-weight: 700; color: var(--text-primary); }
        .comment-date { font-size: 10px; color: var(--text-muted); }
        .pdf-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(8px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .pdf-modal.open { display: flex; }
        .pdf-modal-card {
            width: 100%;
            max-width: 900px;
            height: 85vh;
            background: var(--surface);
            border-radius: var(--radius-md);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--border);
        }
        .pdf-modal-header {
            padding: 14px 20px;
            background: var(--surface-secondary);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
        }
        .pdf-modal-body { flex: 1; width: 100%; height: 100%; }
        .pdf-modal-body iframe { width: 100%; height: 100%; border: none; }
        .chatbot-fab {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 8px 24px var(--accent-glow);
            z-index: 1500;
            transition: var(--transition);
        }
        .chatbot-fab:hover { transform: scale(1.08); }
        .chatbot-drawer {
            position: fixed;
            bottom: 90px;
            right: 24px;
            width: 350px;
            height: 460px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            z-index: 1500;
            display: none;
            flex-direction: column;
            overflow: hidden;
        }
        .chatbot-drawer.open { display: flex; }
        .bot-header {
            padding: 14px 18px;
            background: #0b1329;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 700;
            font-size: 14px;
        }
        .bot-messages {
            flex: 1;
            padding: 14px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: var(--surface-secondary);
        }
        .msg-bubble {
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.4;
            max-width: 85%;
        }
        .msg-bot { background: var(--surface); border: 1px solid var(--border); align-self: flex-start; }
        .msg-user { background: #2563eb; color: white; align-self: flex-end; }
        .bot-chips {
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            background: var(--surface);
            border-top: 1px solid var(--border);
        }
        .chip-btn {
            padding: 8px 12px;
            background: var(--surface-secondary);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 600;
            color: var(--text-primary);
            text-align: left;
            cursor: pointer;
            transition: var(--transition);
        }
        .chip-btn:hover { background: rgba(37,99,235,0.1); border-color: #2563eb; color: #2563eb; }
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
            <li><a href="dashboard.php" class="active"><i class="bi bi-folder2-open"></i> Policy Vault</a></li>
            <li><a href="profile.php"><i class="bi bi-person-badge"></i> My Personnel Profile</a></li>
            <li><a href="change_password.php"><i class="bi bi-key-fill"></i> Security Settings</a></li>
            <li><a href="about.php"><i class="bi bi-info-circle-fill"></i> About OFBL &amp; Project</a></li>
            <li class="nav-label">Session</li>
            <li><a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Sign Out</a></li>
        </ul>

        <div class="sidebar-footer">
            <span>Defence Intranet</span>
            <span style="font-size: 10px; color: #10b981;">● Active</span>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h2>Official Policy Vault &amp; Directives</h2>
                <p>Welcome, <strong><?php echo e($user_name); ?></strong> &bull; Access authorized ordnance factory documents</p>
            </div>
            <div class="topbar-right">
                <div class="user-badge">
                    <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                    <span><?php echo e($user_name); ?></span>
                </div>
                <button class="btn-theme" onclick="document.body.classList.toggle('dark-mode')">🌓 Theme</button>
            </div>
        </div>

        <?php echo render_toast(); ?>

        <div class="panel" style="margin-bottom: 24px;">
            <form method="GET" style="display: flex; gap: 14px; flex-wrap: wrap;">
                <input type="text" name="search" placeholder="Search policy by title, code, or keyword..." value="<?php echo e($search); ?>" style="flex: 2; min-width: 240px;">
                <select name="section" style="flex: 1; min-width: 200px;">
                    <option value="0">-- All Departments --</option>
                    <?php if ($sections): while ($s = $sections->fetch_assoc()): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo $sec_filter == $s['id'] ? 'selected' : ''; ?>>
                            <?php echo e($s['section_name'] . ' (' . $s['section_code'] . ')'); ?>
                        </option>
                    <?php endwhile; endif; ?>
                </select>
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search Directives</button>
                <?php if (!empty($search) || $sec_filter > 0): ?>
                    <a href="dashboard.php" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-box">
            <table>
                <thead>
                    <tr>
                        <th style="width: 140px;">Directive Ref</th>
                        <th>Policy Title &amp; Scope</th>
                        <th>Department</th>
                        <th>Release Date</th>
                        <th style="width: 130px;">Document</th>
                        <th style="width: 320px;">Discussion &amp; Queries</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($policies && $policies->num_rows > 0): ?>
                        <?php while ($p = $policies->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="badge-pill badge-blue"><strong><?php echo e($p['policy_number']); ?></strong></span>
                                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">v<?php echo e($p['version'] ?? '1.0'); ?></div>
                                </td>
                                <td>
                                    <strong><?php echo e($p['title']); ?></strong>
                                    <?php if (!empty($p['pdf_hint'])): ?>
                                        <p style="font-size: 12.5px; color: var(--text-secondary); margin-top: 6px; line-height: 1.4;">
                                            <?php echo e($p['pdf_hint']); ?>
                                        </p>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-pill badge-green"><?php echo e($p['section_code'] ?? 'GEN'); ?></span>
                                    <div style="font-size: 11.5px; margin-top: 4px;"><?php echo e($p['section_name'] ?? 'Factory-wide'); ?></div>
                                </td>
                                <td><small><?php echo date('d M Y', strtotime($p['policy_date'])); ?></small></td>
                                <td>
                                    <button type="button" onclick="openPdfModal('<?php echo urlencode($p['pdf_file']); ?>', '<?php echo addslashes($p['title']); ?>')" class="btn btn-sm btn-primary" style="margin-bottom: 6px; width: 100%; justify-content: center;">
                                        <i class="bi bi-eye-fill"></i> Preview
                                    </button>
                                    <a href="../uploads/pdfs/<?php echo urlencode($p['pdf_file']); ?>" download class="btn btn-sm btn-secondary" style="width: 100%; justify-content: center;">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                </td>
                                <td>
                                    <form method="POST" style="margin-bottom: 8px;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="policy_id" value="<?php echo $p['id']; ?>">
                                        <div style="display: flex; gap: 6px;">
                                            <input type="text" name="comment" placeholder="Ask query or feedback..." required style="font-size: 12px; padding: 6px 10px;">
                                            <button type="submit" name="add_comment" class="btn btn-sm btn-primary"><i class="bi bi-send-fill"></i></button>
                                        </div>
                                    </form>

                                    <?php
                                    $com_res = $conn->query("
                                        SELECT comments.*, users.name
                                        FROM comments
                                        JOIN users ON comments.user_id = users.id
                                        WHERE policy_id = {$p['id']}
                                        ORDER BY comments.id DESC LIMIT 2
                                    ");
                                    if ($com_res && $com_res->num_rows > 0):
                                    ?>
                                        <div class="comment-stream">
                                            <?php while ($c = $com_res->fetch_assoc()): ?>
                                                <div class="comment-item">
                                                    <span class="comment-author"><?php echo e($c['name']); ?></span>
                                                    <span class="comment-date">&bull; <?php echo date('d M, h:i A', strtotime($c['created_at'])); ?></span>
                                                    <p style="margin-top: 2px;"><?php echo e($c['comment']); ?></p>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding: 50px; color: var(--text-muted);">No policies found matching your criteria.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PDF PREVIEW MODAL -->
    <div class="pdf-modal" id="pdfModal">
        <div class="pdf-modal-card">
            <div class="pdf-modal-header">
                <div style="display:flex; align-items:center; gap:10px;">
                    <i class="bi bi-file-earmark-pdf-fill" style="color:#ef4444; font-size:20px;"></i>
                    <strong id="modalPdfTitle">Document Preview</strong>
                </div>
                <button type="button" onclick="closePdfModal()" class="btn btn-sm btn-secondary">&times; Close</button>
            </div>
            <div class="pdf-modal-body">
                <iframe id="pdfFrame" src=""></iframe>
            </div>
        </div>
    </div>

    <!-- CHATBOT -->
    <div class="chatbot-fab" onclick="toggleChatbot()" title="OFBL Assistant">
        <i class="bi bi-robot"></i>
    </div>

    <div class="chatbot-drawer" id="chatbotDrawer">
        <div class="bot-header">
            <span><i class="bi bi-cpu"></i> OFBL Smart Policy Bot</span>
            <button onclick="toggleChatbot()" style="background:none; border:none; color:white; font-size:18px; cursor:pointer;">&times;</button>
        </div>
        <div class="bot-messages" id="botMessages">
            <div class="msg-bubble msg-bot">
                Namaste <?php echo e($user_name); ?>! 👋 I am your OFBL Policy Assistant. Click an inquiry below or search policies.
            </div>
        </div>
        <div class="bot-chips">
            <button type="button" class="chip-btn" onclick="askBot('How do I search for safety protocols?')">🔍 How do I search for safety protocols?</button>
            <button type="button" class="chip-btn" onclick="askBot('How do I download directives?')">📥 How do I download official PDF directives?</button>
            <button type="button" class="chip-btn" onclick="askBot('Who manages policy approvals?')">🛡️ Who manages policy approvals?</button>
            <button type="button" class="chip-btn" onclick="askBot('What is Ordnance Factory Badmal?')">🏭 What is Ordnance Factory Badmal (OFBL)?</button>
        </div>
    </div>

    <script>
        function openPdfModal(pdfFile, title) {
            document.getElementById('modalPdfTitle').innerText = title;
            document.getElementById('pdfFrame').src = '../uploads/pdfs/' + pdfFile;
            document.getElementById('pdfModal').classList.add('open');
        }
        function closePdfModal() {
            document.getElementById('pdfFrame').src = '';
            document.getElementById('pdfModal').classList.remove('open');
        }
        function toggleChatbot() {
            document.getElementById('chatbotDrawer').classList.toggle('open');
        }
        function askBot(query) {
            const box = document.getElementById('botMessages');
            box.innerHTML += `<div class="msg-bubble msg-user">${query}</div>`;
            let reply = '';
            if (query.includes('search')) {
                reply = 'Use the filter bar at the top of the dashboard. You can search by keywords or select specific departments (ITC, Safety, DGQA, Production).';
            } else if (query.includes('download')) {
                reply = 'Click the "Download" button beside any policy in the vault table, or open the "Preview" modal to view and save.';
            } else if (query.includes('approvals')) {
                reply = 'Policy directives are authored by Department Heads and authorized by OFBL Administrative Command under Munitions India Limited directives.';
            } else if (query.includes('Ordnance')) {
                reply = 'Ordnance Factory Badmal (OFBL), located in Balangir, Odisha, is a prime munitions manufacturing unit under Munitions India Limited (MIL), Ministry of Defence, Government of India.';
            } else {
                reply = 'For additional assistance, contact the Information Technology Centre (ITC) at OFBL.';
            }
            setTimeout(() => {
                box.innerHTML += `<div class="msg-bubble msg-bot">${reply}</div>`;
                box.scrollTop = box.scrollHeight;
            }, 300);
        }
    </script>
</body>
</html>
