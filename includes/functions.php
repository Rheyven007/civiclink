<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: /auth/login.php');
        exit;
    }
}

function require_role($roles) {
    require_login();
    if (!in_array($_SESSION['role'], (array)$roles)) {
        header('Location: /index.php');
        exit;
    }
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function current_role() {
    return $_SESSION['role'] ?? null;
}

function redirect_by_role() {
    switch ($_SESSION['role'] ?? '') {
        case 'admin': header('Location: /admin/dashboard.php'); break;
        case 'lgu_officer': header('Location: /lgu/dashboard.php'); break;
        case 'sector_rep': header('Location: /sector/dashboard.php'); break;
        default: header('Location: /citizen/dashboard.php'); break;
    }
    exit;
}

function log_audit($conn, $user_id, $action, $details = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isss', $user_id, $action, $details, $ip);
    $stmt->execute();
    $stmt->close();
}

function notify_user($conn, $user_id, $message, $link = null) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $user_id, $message, $link);
    $stmt->execute();
    $stmt->close();
}

function notify_admins_and_lgu($conn, $message, $link = null) {
    $res = $conn->query("SELECT user_id FROM users WHERE role IN ('admin','lgu_officer')");
    while ($row = $res->fetch_assoc()) {
        notify_user($conn, $row['user_id'], $message, $link);
    }
}

function unread_notif_count($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $c = $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
    return $c;
}

function status_badge($status) {
    $map = [
        'pending' => 'badge-warn', 'under_review' => 'badge-info', 'approved' => 'badge-ok',
        'rejected' => 'badge-bad', 'implemented' => 'badge-ok', 'submitted' => 'badge-info',
        'in_progress' => 'badge-warn', 'resolved' => 'badge-ok', 'closed' => 'badge-muted',
        'cancelled' => 'badge-bad', 'filed' => 'badge-info', 'investigating' => 'badge-warn',
        'mediation' => 'badge-warn', 'dismissed' => 'badge-bad', 'active' => 'badge-ok',
        'suspended' => 'badge-bad', 'open' => 'badge-ok'
    ];
    $icon_map = [
        'pending' => 'fa-hourglass-half', 'under_review' => 'fa-magnifying-glass', 'approved' => 'fa-circle-check',
        'rejected' => 'fa-circle-xmark', 'implemented' => 'fa-flag-checkered', 'submitted' => 'fa-paper-plane',
        'in_progress' => 'fa-spinner', 'resolved' => 'fa-circle-check', 'closed' => 'fa-box-archive',
        'cancelled' => 'fa-ban', 'filed' => 'fa-file-circle-exclamation', 'investigating' => 'fa-magnifying-glass',
        'mediation' => 'fa-handshake', 'dismissed' => 'fa-circle-xmark', 'active' => 'fa-circle-check',
        'suspended' => 'fa-ban', 'open' => 'fa-lock-open'
    ];
    $cls = $map[$status] ?? 'badge-muted';
    $icon = $icon_map[$status] ?? 'fa-circle';
    $label = ucwords(str_replace('_', ' ', $status));
    return "<span class=\"badge $cls\"><i class=\"fa-solid $icon\"></i>$label</span>";
}

function notify_vote_update($conn, $proposal_id) {
    // Notify the proposal owner whenever the vote tally changes.
    $stmt = $conn->prepare("SELECT p.user_id, p.title, p.votes_up, p.votes_down FROM proposals p WHERE p.proposal_id=?");
    $stmt->bind_param('i', $proposal_id);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$p) return;
    $msg = "Vote update on your proposal \"{$p['title']}\": {$p['votes_up']} support / {$p['votes_down']} oppose.";
    notify_user($conn, $p['user_id'], $msg, "/citizen/proposal_view.php?id=$proposal_id");
}

function notify_decision_logged($conn, $type, $ref_id, $owner_id, $title, $new_status, $link = null) {
    $type_label = ucwords(str_replace('_', ' ', $type));
    $status_label = ucwords(str_replace('_', ' ', $new_status));
    $msg = "Decision log updated: your $type_label \"$title\" is now $status_label.";
    notify_user($conn, $owner_id, $msg, $link);
}

function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    if ($diff < 2592000) return floor($diff/86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}
