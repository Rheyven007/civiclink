<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('lgu_officer');
$page_title = 'LGU Officer Dashboard';
$current_page = 'lgu/dashboard.php';

$pending_proposals = $conn->query("SELECT COUNT(*) c FROM proposals WHERE status IN ('pending','under_review')")->fetch_assoc()['c'];
$pending_requests = $conn->query("SELECT COUNT(*) c FROM service_requests WHERE status IN ('submitted','in_progress')")->fetch_assoc()['c'];
$pending_complaints = $conn->query("SELECT COUNT(*) c FROM complaints WHERE status IN ('filed','investigating','mediation')")->fetch_assoc()['c'];
$resolved_month = $conn->query("SELECT COUNT(*) c FROM service_requests WHERE status='resolved' AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['c'];

require_once __DIR__ . '/../includes/header.php';
?>
<div class="grid grid-4">
  <div class="stat"><div class="num"><?= $pending_proposals ?></div><div class="lbl">Pending Proposals</div></div>
  <div class="stat"><div class="num"><?= $pending_requests ?></div><div class="lbl">Open Service Requests</div></div>
  <div class="stat"><div class="num"><?= $pending_complaints ?></div><div class="lbl">Active Complaints</div></div>
  <div class="stat"><div class="num"><?= $resolved_month ?></div><div class="lbl">Resolved (30 days)</div></div>
</div>
<div class="grid grid-3 mt">
  <div class="card">
    <h2>Community Proposals</h2>
    <p class="small muted mb">Review and decide on citizen-submitted projects.</p>
    <a href="/lgu/cases.php?type=proposal" class="btn btn-block">Manage Proposals</a>
  </div>
  <div class="card">
    <h2>Service Requests</h2>
    <p class="small muted mb">Track and update non-emergency requests.</p>
    <a href="/lgu/cases.php?type=service_request" class="btn btn-block">Manage Requests</a>
  </div>
  <div class="card">
    <h2>Complaints & Disputes</h2>
    <p class="small muted mb">Investigate and resolve citizen complaints.</p>
    <a href="/lgu/cases.php?type=complaint" class="btn btn-block">Manage Complaints</a>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
