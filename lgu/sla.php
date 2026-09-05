<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('lgu_officer');
$page_title = 'SLA & Performance';
$current_page = 'lgu/sla.php';

$avg_resolution = $conn->query("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) h FROM service_requests WHERE status='resolved'")->fetch_assoc()['h'];
$total = $conn->query("SELECT COUNT(*) c FROM service_requests")->fetch_assoc()['c'];
$resolved = $conn->query("SELECT COUNT(*) c FROM service_requests WHERE status='resolved'")->fetch_assoc()['c'];
$overdue = $conn->query("SELECT COUNT(*) c FROM service_requests WHERE status IN ('submitted','in_progress') AND created_at < DATE_SUB(NOW(), INTERVAL 72 HOUR)")->fetch_assoc()['c'];
$resolution_rate = $total > 0 ? round(($resolved / $total) * 100) : 0;

$by_dept = $conn->query("SELECT department, COUNT(*) total,
    SUM(CASE WHEN status='resolved' THEN 1 ELSE 0 END) resolved
    FROM service_requests GROUP BY department ORDER BY total DESC");

require_once __DIR__ . '/../includes/header.php';
?>
<div class="grid grid-4 mb">
  <div class="stat"><div class="num"><?= $avg_resolution ? round($avg_resolution) . 'h' : '—' ?></div><div class="lbl">Avg. Resolution Time</div></div>
  <div class="stat"><div class="num"><?= $resolution_rate ?>%</div><div class="lbl">Resolution Rate</div></div>
  <div class="stat"><div class="num"><?= $overdue ?></div><div class="lbl">Overdue (&gt;72h)</div></div>
  <div class="stat"><div class="num"><?= $total ?></div><div class="lbl">Total Requests</div></div>
</div>
<div class="card">
  <h2>Performance by Department</h2>
  <table>
    <tr><th>Department</th><th>Total</th><th>Resolved</th><th>Rate</th></tr>
    <?php while ($d = $by_dept->fetch_assoc()):
        $rate = $d['total'] > 0 ? round(($d['resolved']/$d['total'])*100) : 0; ?>
      <tr>
        <td><?= e($d['department'] ?: 'Unassigned') ?></td>
        <td><?= $d['total'] ?></td>
        <td><?= $d['resolved'] ?></td>
        <td>
          <div class="bar" style="width:100px;display:inline-block;vertical-align:middle"><div class="bar-fill" style="width:<?= $rate ?>%"></div></div>
          <span class="small muted"><?= $rate ?>%</span>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
