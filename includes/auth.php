<?php
/**
 * Centralized Session & Access Control Guard
 * Matches Session Security & Auto Logout specifications (Report Ch 4.3)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session Inactivity Timeout: 30 Minutes (1800 Seconds)
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
        session_unset();
        session_destroy();
        header("Location: ../index.php?msg=timeout");
        exit();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../index.php?msg=login_required");
        exit();
    }
}

function require_admin() {
    require_login();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../user/dashboard.php?msg=access_denied");
        exit();
    }
}
?>