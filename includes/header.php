<?php
require_once __DIR__ . '/functions.php';
require_login();
$role = current_role();
$notif_count = unread_notif_count($conn, current_user_id());

function nav_link($href, $label, $current) {
    $active = ($current === $href) ? 'active' : '';
    echo "<a href=\"/$href\" class=\"$active\"><span class=\"label\">$label</span></a>";
}
$page = $current_page ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? e($page_title) . ' - ' : '' ?>CivicBridge</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="app">
  <aside class="sidebar">
    <div class="brand">Civic<span>Bridge</span></div>
    <nav>
      <?php if ($role === 'citizen'): ?>
        <?php nav_link('citizen/dashboard.php','Dashboard',$page); ?>
        <?php nav_link('citizen/proposals.php','Proposals',$page); ?>
        <?php nav_link('citizen/requests.php','Service Requests',$page); ?>
        <?php nav_link('citizen/complaints.php','Complaints',$page); ?>
        <?php nav_link('citizen/consultations.php','Consultations',$page); ?>
        <?php nav_link('citizen/feedback.php','Feedback',$page); ?>
        <?php nav_link('citizen/notifications.php','Notifications',$page); ?>
        <?php nav_link('citizen/profile.php','My Profile',$page); ?>
      <?php elseif ($role === 'sector_rep'): ?>
        <?php nav_link('sector/dashboard.php','Dashboard',$page); ?>
        <?php nav_link('sector/issues.php','Sector Issues',$page); ?>
        <?php nav_link('sector/endorsements.php','Endorsements',$page); ?>
        <?php nav_link('citizen/consultations.php','Consultations',$page); ?>
        <?php nav_link('citizen/notifications.php','Notifications',$page); ?>
        <?php nav_link('citizen/profile.php','My Profile',$page); ?>
      <?php elseif ($role === 'lgu_officer'): ?>
        <?php nav_link('lgu/dashboard.php','Dashboard',$page); ?>
        <?php nav_link('lgu/cases.php?type=proposal','Proposals',$page); ?>
        <?php nav_link('lgu/cases.php?type=service_request','Service Requests',$page); ?>
        <?php nav_link('lgu/cases.php?type=complaint','Complaints',$page); ?>
        <?php nav_link('lgu/sla.php','SLA & Performance',$page); ?>
        <?php nav_link('citizen/notifications.php','Notifications',$page); ?>
        <?php nav_link('citizen/profile.php','My Profile',$page); ?>
      <?php elseif ($role === 'admin'): ?>
        <?php nav_link('admin/dashboard.php','Dashboard',$page); ?>
        <?php nav_link('admin/users.php','User Management',$page); ?>
        <?php nav_link('admin/sectors.php','Sectors',$page); ?>
        <?php nav_link('admin/moderation.php','Moderation',$page); ?>
        <?php nav_link('admin/reports.php','Analytics & Reports',$page); ?>
        <?php nav_link('admin/audit_logs.php','Audit Logs',$page); ?>
        <?php nav_link('citizen/profile.php','My Profile',$page); ?>
      <?php endif; ?>
    </nav>
    <div class="user-box">
      <strong><?= e($_SESSION['full_name']) ?></strong>
      <?= e(ucwords(str_replace('_',' ',$role))) ?><br>
      <a href="/auth/logout.php" style="color:#f87171">Log out</a>
    </div>
  </aside>
  <div class="main">
    <div class="topbar">
      <h1><?= e($page_title ?? 'CivicBridge') ?></h1>
      <div class="actions">
        <a href="/citizen/notifications.php" class="muted">🔔 Notifications
          <?php if ($notif_count > 0): ?><span class="notif-dot"><?= $notif_count ?></span><?php endif; ?>
        </a>
      </div>
    </div>
    <div class="content">
