<?php
require_once __DIR__ . '/functions.php';
require_login();
$role = current_role();
$notif_count = unread_notif_count($conn, current_user_id());

function nav_link($href, $label, $icon, $current) {
    $active = ($current === $href) ? 'active' : '';
    echo "<a href=\"/$href\" class=\"$active\"><i class=\"fa-solid $icon\"></i><span class=\"label\">$label</span></a>";
}
$page = $current_page ?? '';
$role_labels = [
    'citizen' => 'Citizen', 'sector_rep' => 'Sector Representative',
    'lgu_officer' => 'LGU Officer', 'admin' => 'Administrator'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? e($page_title) . ' - ' : '' ?>CivicLink</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="app">
  <aside class="sidebar">
    <div class="brand"><i class="fa-solid fa-landmark-dome"></i>Civic<span>Link</span></div>
    <nav>
      <?php if ($role === 'citizen'): ?>
        <?php nav_link('citizen/dashboard.php','Dashboard','fa-gauge-high',$page); ?>
        <?php nav_link('citizen/proposals.php','Proposals','fa-lightbulb',$page); ?>
        <?php nav_link('citizen/requests.php','Service Requests','fa-clipboard-list',$page); ?>
        <?php nav_link('citizen/complaints.php','Complaints','fa-triangle-exclamation',$page); ?>
        <?php nav_link('citizen/consultations.php','Consultations','fa-people-arrows',$page); ?>
        <?php nav_link('citizen/feedback.php','Feedback','fa-comment-dots',$page); ?>
        <?php nav_link('citizen/notifications.php','Notifications','fa-bell',$page); ?>
        <?php nav_link('citizen/profile.php','My Profile','fa-user',$page); ?>
      <?php elseif ($role === 'sector_rep'): ?>
        <?php nav_link('sector/dashboard.php','Dashboard','fa-gauge-high',$page); ?>
        <?php nav_link('sector/issues.php','Sector Issues','fa-triangle-exclamation',$page); ?>
        <?php nav_link('sector/endorsements.php','Endorsements','fa-thumbs-up',$page); ?>
        <?php nav_link('citizen/consultations.php','Consultations','fa-people-arrows',$page); ?>
        <?php nav_link('citizen/notifications.php','Notifications','fa-bell',$page); ?>
        <?php nav_link('citizen/profile.php','My Profile','fa-user',$page); ?>
      <?php elseif ($role === 'lgu_officer'): ?>
        <?php nav_link('lgu/dashboard.php','Dashboard','fa-gauge-high',$page); ?>
        <?php nav_link('lgu/cases.php?type=proposal','Proposals','fa-lightbulb',$page); ?>
        <?php nav_link('lgu/cases.php?type=service_request','Service Requests','fa-clipboard-list',$page); ?>
        <?php nav_link('lgu/cases.php?type=complaint','Complaints','fa-triangle-exclamation',$page); ?>
        <?php nav_link('lgu/sla.php','SLA & Performance','fa-chart-line',$page); ?>
        <?php nav_link('citizen/notifications.php','Notifications','fa-bell',$page); ?>
        <?php nav_link('citizen/profile.php','My Profile','fa-user',$page); ?>
      <?php elseif ($role === 'admin'): ?>
        <?php nav_link('admin/dashboard.php','Dashboard','fa-gauge-high',$page); ?>
        <?php nav_link('admin/users.php','User Management','fa-users-gear',$page); ?>
        <?php nav_link('admin/sectors.php','Sectors','fa-diagram-project',$page); ?>
        <?php nav_link('admin/moderation.php','Moderation','fa-shield-halved',$page); ?>
        <?php nav_link('admin/reports.php','Analytics & Reports','fa-chart-pie',$page); ?>
        <?php nav_link('admin/audit_logs.php','Audit Logs','fa-file-shield',$page); ?>
        <?php nav_link('citizen/profile.php','My Profile','fa-user',$page); ?>
      <?php endif; ?>
    </nav>
    <div class="user-box">
      <strong><?= e($_SESSION['full_name']) ?></strong>
      <span class="role-badge"><?= e($role_labels[$role] ?? ucwords(str_replace('_',' ',$role))) ?></span><br>
      <a href="/auth/logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
    </div>
  </aside>
  <div class="main">
    <div class="topbar">
      <h1><?= e($page_title ?? 'CivicLink') ?></h1>
      <div class="actions">
        <a href="/citizen/notifications.php" class="muted">
          <i class="fa-solid fa-bell"></i> Notifications
          <?php if ($notif_count > 0): ?><span class="notif-dot"><?= $notif_count ?></span><?php endif; ?>
        </a>
      </div>
    </div>
    <div class="content">
