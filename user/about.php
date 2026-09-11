<?php
/**
 * OFBL Policy Governance — Official Institutional & Project Overview
 * Ordnance Factory Badmal, Munitions India Limited (MIL)
 * Ministry of Defence, Government of India
 * Lead Developer: Shreyas Sankalp Sahu (KIIT CSE)
 */

ob_start();
session_start();

require_once '../includes/helpers.php';
require_once '../includes/auth.php';

require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About OFBL &amp; Governance System | Munitions India Limited</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .about-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 36px;
            line-height: 1.8;
            margin-bottom: 24px;
        }
        .about-card h2 { font-size: 22px; font-weight: 800; margin-bottom: 14px; color: var(--text-primary); }
        .about-card p { font-size: 14px; color: var(--text-secondary); margin-bottom: 16px; }
        .credit-badge {
            background: rgba(37,99,235,0.08);
            border-left: 4px solid #2563eb;
            padding: 16px 20px;
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
            margin-top: 24px;
        }
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
            <li><a href="profile.php"><i class="bi bi-person-badge"></i> My Personnel Profile</a></li>
            <li><a href="change_password.php"><i class="bi bi-key-fill"></i> Security Settings</a></li>
            <li><a href="about.php" class="active"><i class="bi bi-info-circle-fill"></i> About OFBL &amp; Project</a></li>
            <li class="nav-label">Session</li>
            <li><a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Sign Out</a></li>
        </ul>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h2>Institutional Overview &amp; Project Credits</h2>
                <p>Ordnance Factory Badmal &bull; Munitions India Limited (MIL) &bull; Ministry of Defence</p>
            </div>
            <div class="topbar-right">
                <button class="btn-theme" onclick="document.body.classList.toggle('dark-mode')">🌓 Theme</button>
            </div>
        </div>

        <div class="about-card">
            <span class="badge-pill badge-blue" style="margin-bottom: 12px; display:inline-block;">
                DEFENCE PUBLIC SECTOR ENTERPRISE
            </span>
            <h2>Ordnance Factory Badmal (OFBL)</h2>
            <p>
                <strong>Ordnance Factory Badmal (OFBL)</strong> is a prime munitions manufacturing installation under <strong>Munitions India Limited (MIL)</strong>, operating within the Department of Defence Production, <strong>Ministry of Defence, Government of India</strong>. Situated in Balangir, Odisha, OFBL produces critical defense equipment and ammunitions for the Indian Armed Forces.
            </p>
            <p>
                The <strong>OFBL Policy Management System</strong> is a secure digital vault engineered to modernize organizational policy distribution, versioning, access control, and compliance feedback.
            </p>

            <h3 style="font-size: 17px; font-weight: 700; margin: 24px 0 12px;">Architecture &amp; Security Standards</h3>
            <ul style="padding-left: 20px; color: var(--text-secondary); font-size: 13.5px; margin-bottom: 20px;">
                <li><strong>Role-Based Access Control (RBAC)</strong>: Segregates Administrative control from standard employee access.</li>
                <li><strong>Cryptographic Password Protection</strong>: Bcrypt-secured credential hashing and salted storage.</li>
                <li><strong>Defence-Grade Audit Trail</strong>: Automatic real-time logging of authentication, uploads, downloads, and approvals.</li>
                <li><strong>Departmental Isolation</strong>: Policies segregated by ITC, Production, DGQA, Safety, Finance, and HRD.</li>
                <li><strong>Dual Deployment</strong>: Live interactive client-side demo on GitHub Pages and enterprise PHP/MySQL backend.</li>
            </ul>

            <div class="credit-badge">
                <h4 style="font-size: 15px; font-weight: 800; color: #1e3a8a; margin-bottom: 6px;">Official Internship Credentials &amp; Authorship</h4>
                <p style="font-size: 13px; color: #334155; margin-bottom: 4px; line-height: 1.6;">
                    <strong>Lead Developer &amp; Project Architect:</strong> Shreyas Sankalp Sahu (Roll/Reg No: 2405840)<br>
                    <strong>Academic Institution:</strong> B.Tech CSE-Core, 2nd Year / 4th Sem, KIIT Deemed to be University, Bhubaneswar<br>
                    <strong>Supervised By:</strong> Smt. Minati Pradhan (JWM/ITC), Shri Trinatha Behera (SR MANAGER/HRD), Shri M.S Naik (OIC/HRD)<br>
                    <strong>Internship Period:</strong> 04/05/2026 to 06/06/2026 &bull; Ordnance Factory, Badmal (Munitions India Limited)
                </p>
            </div>
        </div>
    </div>
</body>
</html>
