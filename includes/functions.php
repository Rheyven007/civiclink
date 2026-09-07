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
    // Guard against a stale session pointing at a user_id that no longer exists —
    // e.g. right after re-importing the database. Without this, inserts that
    // record created_by/user_id (like consultations) would silently fail on the
    // foreign key and never get saved.
    global $conn;
    $stmt = $conn->prepare("SELECT 1 FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    if (!$exists) {
        session_unset();
        session_destroy();
        header('Location: /auth/login.php?reason=stale_session');
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

// Broadcasts a message to every citizen and sector representative — used for
// public consultation activities (launch/close) per the system's notification scope.
function notify_all_citizens($conn, $message, $link = null) {
    $res = $conn->query("SELECT user_id FROM users WHERE role IN ('citizen','sector_rep') AND status='active'");
    while ($row = $res->fetch_assoc()) {
        notify_user($conn, $row['user_id'], $message, $link);
    }
}

// Notifies only the citizens/sector reps who already responded to a consultation
// (used when a consultation closes, so only participants are pinged).
function notify_consultation_participants($conn, $consultation_id, $message, $link = null) {
    $stmt = $conn->prepare("SELECT user_id FROM consultation_responses WHERE consultation_id = ?");
    $stmt->bind_param('i', $consultation_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        notify_user($conn, $row['user_id'], $message, $link);
    }
    $stmt->close();
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

function notify_endorsement($conn, $proposal_id) {
    // Notify the proposal owner specifically that a sector representative endorsed it —
    // distinct from a plain vote update, per the system's notification scope.
    $stmt = $conn->prepare("SELECT p.user_id, p.title FROM proposals p WHERE p.proposal_id=?");
    $stmt->bind_param('i', $proposal_id);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$p) return;
    $msg = "Your proposal \"{$p['title']}\" was endorsed by a sector representative.";
    notify_user($conn, $p['user_id'], $msg, "/citizen/proposal_view.php?id=$proposal_id");
}

function notify_decision_logged($conn, $type, $ref_id, $owner_id, $title, $new_status, $link = null) {
    $type_label = ucwords(str_replace('_', ' ', $type));
    $status_label = ucwords(str_replace('_', ' ', $new_status));
    $msg = "Decision log updated: your $type_label \"$title\" is now $status_label.";
    notify_user($conn, $owner_id, $msg, $link);
}



/** Render consistent Previous / Page / Next pagination. */
function render_pagination($page, $total_pages, $total, $per_page, $params = []) {
    if ($total <= 0) return;
    $page = max(1, min((int)$page, (int)$total_pages));
    $build = function($p) use ($params) {
        $q = $params;
        $q['p'] = $p;
        return '?' . http_build_query($q);
    };
    $from = (($page - 1) * $per_page) + 1;
    $to = min($page * $per_page, $total);
    echo '<div class="pagination" data-pagination>'; 
    echo '<div class="meta">Showing ' . number_format($from) . '–' . number_format($to) . ' of ' . number_format($total) . '</div>';
    echo '<div class="pages">';
    if ($page > 1) echo '<a class="pager-link" href="' . e($build($page-1)) . '" data-page-link="1">Previous</a>';
    else echo '<span class="pager-link pager-disabled">Previous</span>';
    echo '<span class="current" aria-current="page">Page ' . $page . ' of ' . $total_pages . '</span>';
    if ($page < $total_pages) echo '<a class="pager-link" href="' . e($build($page+1)) . '" data-page-link="1">Next</a>';
    else echo '<span class="pager-link pager-disabled">Next</span>';
    echo '</div></div>';
}

function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    if ($diff < 2592000) return floor($diff/86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}

/** Full human-readable timestamp, e.g. Sep 5, 2026 6:45 PM */
function format_datetime($datetime) {
    if (!$datetime) return '—';
    return date('M j, Y g:i A', strtotime($datetime));
}

/** Confirm attribute helper for forms */
function confirm_attr($message = 'Are you sure you want to continue?') {
    return 'onsubmit="return confirm(' . json_encode($message) . ');"';
}

/**
 * Human-friendly reference number for submissions.
 * Examples: PROP-2026-00124, REQ-2026-00318, CMP-2026-00082
 */
function ref_number($type, $id, $created_at = null) {
    $year = $created_at ? date('Y', strtotime($created_at)) : date('Y');
    $prefixes = [
        'proposal' => 'PROP',
        'service_request' => 'REQ',
        'request' => 'REQ',
        'complaint' => 'CMP',
        'consultation' => 'CON',
    ];
    $prefix = $prefixes[$type] ?? strtoupper(substr($type, 0, 3));
    return sprintf('%s-%s-%05d', $prefix, $year, (int)$id);
}

/** Short plain-language status explanation for tooltips / help text */
function status_help($status) {
    $map = [
        'pending' => 'Your submission is waiting to be reviewed by the LGU.',
        'under_review' => 'An LGU officer is currently reviewing this submission.',
        'approved' => 'The LGU has approved this proposal.',
        'rejected' => 'The LGU has declined this submission. See the decision log for the reason.',
        'implemented' => 'This project has been implemented by the LGU.',
        'submitted' => 'Your service request has been received and is awaiting action.',
        'in_progress' => 'The LGU is actively working on this request.',
        'resolved' => 'This request has been resolved.',
        'closed' => 'This case is closed.',
        'cancelled' => 'This submission was cancelled.',
        'filed' => 'Your complaint has been filed and is awaiting investigation.',
        'investigating' => 'The LGU is investigating this complaint.',
        'mediation' => 'This complaint is in mediation.',
        'dismissed' => 'This complaint was dismissed. See the decision log for details.',
        'open' => 'This consultation is open for participation.',
        'active' => 'This account is active.',
        'suspended' => 'This account has been suspended.',
    ];
    return $map[$status] ?? '';
}
