<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$page_title = 'Analytics & Reports';
$current_page = 'admin/reports.php';

$proposal_status = $conn->query("SELECT status, COUNT(*) c FROM proposals GROUP BY status");
$request_status = $conn->query("SELECT status, COUNT(*) c FROM service_requests GROUP BY status");
$complaint_status = $conn->query("SELECT status, COUNT(*) c FROM complaints GROUP BY status");
$sector_dist = $conn->query("SELECT s.sector_name, COUNT(cp.profile_id) c FROM sectors s LEFT JOIN citizen_profiles cp ON cp.sector_id=s.sector_id GROUP BY s.sector_id ORDER BY c DESC");
$avg_rating = $conn->query("SELECT ROUND(AVG(rating),1) avg_r, COUNT(*) total FROM feedback")->fetch_assoc();
$monthly = $conn->query("SELECT DATE_FORMAT(created_at,'%Y-%m') ym, COUNT(*) c FROM proposals GROUP BY ym ORDER BY ym DESC LIMIT 6");

require_once __DIR__ . '/../includes/header.php';
?>
<div class="grid grid-2 mb">
  <div class="stat"><div class="num"><?= $avg_rating['avg_r'] ?? '—' ?> / 5</div><div class="lbl">Average Service Rating (<?= $avg_rating['total'] ?> reviews)</div></div>
  <div class="stat"><div class="num"><?= $sector_dist->num_rows ?></div><div class="lbl">Sectors Tracked</div></div>
</div>
<div class="grid grid-3">
  <div class="card">
    <h2>Proposals by Status</h2>
    <?php while ($r = $proposal_status->fetch_assoc()): ?>
      <div class="flex-between small" style="padding:4px 0"><span><?= status_badge($r['status']) ?></span><strong><?= $r['c'] ?></strong></div>
    <?php endwhile; ?>
  </div>
  <div class="card">
    <h2>Requests by Status</h2>
    <?php while ($r = $request_status->fetch_assoc()): ?>
      <div class="flex-between small" style="padding:4px 0"><span><?= status_badge($r['status']) ?></span><strong><?= $r['c'] ?></strong></div>
    <?php endwhile; ?>
  </div>
  <div class="card">
    <h2>Complaints by Status</h2>
    <?php while ($r = $complaint_status->fetch_assoc()): ?>
      <div class="flex-between small" style="padding:4px 0"><span><?= status_badge($r['status']) ?></span><strong><?= $r['c'] ?></strong></div>
    <?php endwhile; ?>
  </div>
</div>
<div class="card">
  <h2>Sector Distribution (Equity Tracking)</h2>
  <table>
    <tr><th>Sector</th><th>Registered Members</th></tr>
    <?php $sector_dist->data_seek(0); while ($s = $sector_dist->fetch_assoc()): ?>
      <tr><td><?= e($s['sector_name']) ?></td><td><?= $s['c'] ?></td></tr>
    <?php endwhile; ?>
  </table>
</div>
<div class="card">
  <h2>Proposal Submissions (Last 6 Months)</h2>
  <table>
    <tr><th>Month</th><th>Submissions</th></tr>
    <?php if ($monthly->num_rows === 0): ?><tr><td colspan="2" class="empty">No data yet.</td></tr><?php endif; ?>
    <?php while ($m = $monthly->fetch_assoc()): ?>
      <tr><td><?= date('F Y', strtotime($m['ym'].'-01')) ?></td><td><?= $m['c'] ?></td></tr>
    <?php endwhile; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
