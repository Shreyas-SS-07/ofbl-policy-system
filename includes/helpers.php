<?php
/**
 * Security & Utility Helper Functions
 * OFBL Policy Management System
 */

// Universal XSS Sanitizer
function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// Generate Anti-CSRF Token
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Render hidden CSRF form input
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(get_csrf_token()) . '">';
}

// Validate CSRF Token
function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            die("Security Verification Failed: Invalid CSRF Token. Please refresh the page and try again.");
        }
    }
}

// Record Audit Log Entry
function log_audit($conn, $userId, $userName, $action, $details = '') {
    if (!$conn) return;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, user_name, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issss", $userId, $userName, $action, $details, $ip);
        $stmt->execute();
        $stmt->close();
    }
}

// Flash Notifications
function set_toast($type, $message) {
    $_SESSION['toast'] = ['type' => $type, 'message' => $message];
}

function render_toast() {
    if (isset($_SESSION['toast'])) {
        $t = $_SESSION['toast'];
        unset($_SESSION['toast']);
        $icon = $t['type'] === 'success' ? 'bi-check-circle-fill' : ($t['type'] === 'danger' ? 'bi-exclamation-octagon-fill' : 'bi-info-circle-fill');
        $bg = $t['type'] === 'success' ? '#10b981' : ($t['type'] === 'danger' ? '#ef4444' : '#2563eb');
        return "<div id='ofblToast' class='toast-notice' style='background: {$bg}; color: #fff; padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 14px rgba(0,0,0,0.15);'>
            <div style='display: flex; align-items: center; gap: 10px;'><i class='bi {$icon}'></i> <span>" . e($t['message']) . "</span></div>
            <button type='button' onclick='this.parentElement.remove()' style='background:none; border:none; color:#fff; font-size:18px; cursor:pointer;'>&times;</button>
        </div>";
    }
    return '';
}
?>