<?php
require_once __DIR__ . '/functions.php';
require_login();
$role = current_role();
$notif_count = unread_notif_count($conn, current_user_id());

// Recent notifications for dropdown
$recent_notifs = [];
$uid = current_user_id();
$nstmt = $conn->prepare("SELECT notification_id, message, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 6");
$nstmt->bind_param('i', $uid);
$nstmt->execute();
$nres = $nstmt->get_result();
while ($nr = $nres->fetch_assoc()) $recent_notifs[] = $nr;
$nstmt->close();

function nav_link($href, $label, $icon, $current) {
    $active = ($current === $href) ? 'active' : '';
    echo "<a href=\"/$href\" class=\"$active\"><i class=\"fa-solid $icon\"></i><span class=\"label\">$label</span></a>";
}
// NOTE: kept as a distinct name ($nav_current) so it never collides with a
// page-scope $page variable used for pagination in files like admin/audit_logs.php.
$nav_current = $current_page ?? '';
$role_labels = [
    'citizen' => 'Citizen', 'sector_rep' => 'Sector Representative',
    'lgu_officer' => 'LGU Officer', 'admin' => 'Administrator'
];
function user_initials($name) {
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $p) { $initials .= mb_strtoupper(mb_substr($p, 0, 1)); }
    return $initials ?: 'U';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? e($page_title) . ' - ' : '' ?>CivicLink</title>
<link rel="icon" type="image/png" href="/assets/img/favicon-32.png" sizes="32x32">
<link rel="icon" type="image/png" href="/assets/img/favicon-64.png" sizes="64x64">
<link rel="apple-touch-icon" href="/assets/img/favicon-180.png">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.5/sweetalert2.min.css">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
<div class="app">
  <aside class="sidebar" id="appSidebar">
    <div class="brand">
      <img src="/assets/img/logo.png" alt="CivicLink" class="brand-logo">
      <span class="brand-text">Civic<span>Link</span></span>
    </div>
    <nav>
      <?php if ($role === 'citizen'): ?>
        <?php nav_link('citizen/dashboard.php','Dashboard','fa-gauge-high',$nav_current); ?>
        <?php nav_link('citizen/proposals.php','Proposals','fa-lightbulb',$nav_current); ?>
        <?php nav_link('citizen/requests.php','Service Requests','fa-clipboard-list',$nav_current); ?>
        <?php nav_link('citizen/complaints.php','Report a Concern','fa-triangle-exclamation',$nav_current); ?>
        <?php nav_link('citizen/consultations.php','Consultations','fa-people-arrows',$nav_current); ?>
        <?php nav_link('citizen/feedback.php','Feedback','fa-comment-dots',$nav_current); ?>
        <?php nav_link('citizen/notifications.php','Notifications','fa-bell',$nav_current); ?>
        <?php nav_link('citizen/help.php','Help & Support','fa-circle-question',$nav_current); ?>
        <?php nav_link('citizen/profile.php','My Profile','fa-user',$nav_current); ?>
      <?php elseif ($role === 'sector_rep'): ?>
        <?php nav_link('sector/dashboard.php','Dashboard','fa-gauge-high',$nav_current); ?>
        <?php nav_link('sector/issues.php','Sector Issues','fa-triangle-exclamation',$nav_current); ?>
        <?php nav_link('sector/endorsements.php','Endorsements','fa-thumbs-up',$nav_current); ?>
        <?php nav_link('citizen/consultations.php','Consultations','fa-people-arrows',$nav_current); ?>
        <?php nav_link('citizen/notifications.php','Notifications','fa-bell',$nav_current); ?>
        <?php nav_link('citizen/help.php','Help & Support','fa-circle-question',$nav_current); ?>
        <?php nav_link('citizen/profile.php','My Profile','fa-user',$nav_current); ?>
      <?php elseif ($role === 'lgu_officer'): ?>
        <?php nav_link('lgu/dashboard.php','Dashboard','fa-gauge-high',$nav_current); ?>
        <?php nav_link('lgu/cases.php?type=proposal','Proposals','fa-lightbulb',$nav_current); ?>
        <?php nav_link('lgu/cases.php?type=service_request','Service Requests','fa-clipboard-list',$nav_current); ?>
        <?php nav_link('lgu/cases.php?type=complaint','Complaints','fa-triangle-exclamation',$nav_current); ?>
        <?php nav_link('lgu/sla.php','Service Performance','fa-chart-line',$nav_current); ?>
        <?php nav_link('citizen/notifications.php','Notifications','fa-bell',$nav_current); ?>
        <?php nav_link('citizen/profile.php','My Profile','fa-user',$nav_current); ?>
      <?php elseif ($role === 'admin'): ?>
        <?php nav_link('admin/dashboard.php','Dashboard','fa-gauge-high',$nav_current); ?>
        <?php nav_link('admin/users.php','User Management','fa-users-gear',$nav_current); ?>
        <?php nav_link('admin/sectors.php','Sectors','fa-diagram-project',$nav_current); ?>
        <?php nav_link('admin/moderation.php','Review Submissions','fa-shield-halved',$nav_current); ?>
        <?php nav_link('admin/reports.php','Analytics & Reports','fa-chart-pie',$nav_current); ?>
        <?php nav_link('admin/audit_logs.php','System Activity Log','fa-file-shield',$nav_current); ?>
        <?php nav_link('citizen/profile.php','My Profile','fa-user',$nav_current); ?>
      <?php endif; ?>
    </nav>
  </aside>
  <div class="main">
    <div class="topbar">
      <div class="topbar-inner">
      <button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
      <h1><?= e($page_title ?? 'CivicLink') ?></h1>
      <div class="actions">
        <div class="notif-wrap">
          <button type="button" class="notif-btn" id="notifToggle" aria-label="Notifications" title="Notifications" aria-expanded="false">
            <i class="fa-solid fa-bell"></i>
            <?php if ($notif_count > 0): ?><span class="notif-dot"><?= (int)$notif_count ?></span><?php endif; ?>
          </button>
          <div class="notif-panel" id="notifPanel" role="menu">
            <div class="notif-panel-head">
              <span>Notifications</span>
              <a href="/citizen/notifications.php">View all</a>
            </div>
            <div class="notif-list">
              <?php if (empty($recent_notifs)): ?>
                <div class="notif-empty"><i class="fa-regular fa-bell-slash"></i>You're all caught up.</div>
              <?php else: foreach ($recent_notifs as $n): ?>
                <a class="notif-item <?= empty($n['is_read']) ? 'unread' : '' ?>" href="<?= e($n['link'] ?: '/citizen/notifications.php') ?>">
                  <div class="notif-msg"><?= e($n['message']) ?></div>
                  <div class="notif-time" title="<?= e(format_datetime($n['created_at'])) ?>"><?= time_ago($n['created_at']) ?> · <?= e(format_datetime($n['created_at'])) ?></div>
                </a>
              <?php endforeach; endif; ?>
            </div>
          </div>
        </div>
        <div class="user-wrap">
          <button type="button" class="user-btn" id="userToggle" aria-label="Account menu" aria-expanded="false">
            <span class="user-avatar"><?= e(user_initials($_SESSION['full_name'])) ?></span>
            <span class="user-btn-text">
              <strong><?= e($_SESSION['full_name']) ?></strong>
              <span><?= e($role_labels[$role] ?? ucwords(str_replace('_',' ',$role))) ?></span>
            </span>
            <i class="fa-solid fa-chevron-down user-caret"></i>
          </button>
          <div class="user-panel" id="userPanel" role="menu">
            <div class="user-panel-head">
              <span class="user-avatar user-avatar-lg"><?= e(user_initials($_SESSION['full_name'])) ?></span>
              <div>
                <strong><?= e($_SESSION['full_name']) ?></strong>
                <span class="role-badge"><?= e($role_labels[$role] ?? ucwords(str_replace('_',' ',$role))) ?></span>
              </div>
            </div>
            <a href="/citizen/profile.php" role="menuitem"><i class="fa-solid fa-user"></i> My Profile</a>
            <a href="/auth/logout.php" class="logout" id="logoutLink" role="menuitem"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
          </div>
        </div>
      </div>
      </div>
    </div>
    <div class="content">
      <div class="content-inner">
