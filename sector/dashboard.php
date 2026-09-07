<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('sector_rep');
$page_title = 'Sector Representative Dashboard';
$current_page = 'sector/dashboard.php';

$sector_page=max(1,(int)($_GET['p']??1)); $per_page=12; $sector_total=(int)$conn->query("SELECT COUNT(*) c FROM sectors")->fetch_assoc()['c']; $sector_pages=max(1,(int)ceil($sector_total/$per_page)); if($sector_page>$sector_pages)$sector_page=$sector_pages; $sector_offset=($sector_page-1)*$per_page;
$sectors_stats = $conn->query("SELECT s.sector_name, COUNT(cp.profile_id) members FROM sectors s LEFT JOIN citizen_profiles cp ON cp.sector_id = s.sector_id GROUP BY s.sector_id ORDER BY members DESC LIMIT $per_page OFFSET $sector_offset");

$open_issues = $conn->query("SELECT COUNT(*) c FROM complaints WHERE status IN ('filed','investigating','mediation')")->fetch_assoc()['c'];
$open_proposals = $conn->query("SELECT COUNT(*) c FROM proposals WHERE status IN ('pending','under_review')")->fetch_assoc()['c'];
$top_sector=$conn->query("SELECT s.sector_name, COUNT(cp.profile_id) members FROM sectors s LEFT JOIN citizen_profiles cp ON cp.sector_id=s.sector_id GROUP BY s.sector_id ORDER BY members DESC LIMIT 1")->fetch_assoc();
$open_total=(int)$open_issues+(int)$open_proposals;
$recent_issue_growth=(int)$conn->query("SELECT COUNT(*) c FROM complaints WHERE created_at>=DATE_SUB(NOW(),INTERVAL 30 DAY) AND status IN ('filed','investigating','mediation')")->fetch_assoc()['c'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="analytics-insights">
 <div class="insight"><div class="insight-type">Descriptive</div><strong><?= number_format($open_total) ?> open community items</strong><p>The sector view currently shows <?= $open_issues ?> open complaints and <?= $open_proposals ?> proposals awaiting review.</p></div>
 <div class="insight"><div class="insight-type">Diagnostic</div><strong><?= e($top_sector['sector_name'] ?? 'No sector data') ?></strong><p>This is currently the largest sector membership group, which can help prioritize representation and outreach.</p></div>
 <div class="insight"><div class="insight-type">Predictive</div><strong><?= $recent_issue_growth > 0 ? 'Active issue pressure detected' : 'No recent open-issue increase' ?></strong><p><?= $recent_issue_growth > 0 ? "$recent_issue_growth open complaints were submitted in the last 30 days; continued submissions may increase sector workload." : 'No new open complaints were recorded in the last 30 days.' ?></p></div>
</div>

<div class="card chart-compare">
  <h2><i class="fa-solid fa-chart-column"></i> Community Workload Comparison</h2>
  <p class="small muted">Open complaints versus proposals awaiting review.</p>
  <canvas id="sectorComparison" aria-label="Sector community workload comparison chart"></canvas>
</div>
<div class="grid grid-3 mb">
  <div class="stat"><div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div><div><div class="num"><?= $open_issues ?></div><div class="lbl">Open Complaints (All)</div></div></div>
  <div class="stat"><div class="stat-icon"><i class="fa-solid fa-lightbulb"></i></div><div><div class="num"><?= $open_proposals ?></div><div class="lbl">Pending Proposals</div></div></div>
  <div class="stat"><div class="stat-icon"><i class="fa-solid fa-diagram-project"></i></div><div><div class="num"><?= $sector_total ?></div><div class="lbl">Registered Sectors</div></div></div>
</div>
<div class="card">
  <h2><i class="fa-solid fa-users"></i> Sector Membership Overview</h2>
  <div class="table-wrap"><table>
    <tr><th>Sector</th><th>Members</th></tr>
    <?php if($sector_total===0): ?><tr><td colspan="2"><div class="empty">No sector data found.</div></td></tr><?php else: $sectors_stats->data_seek(0); while ($s = $sectors_stats->fetch_assoc()): ?>
      <tr><td><?= e($s['sector_name']) ?></td><td><?= $s['members'] ?></td></tr>
    <?php endwhile; endif; ?>
  </table></div></div>
</div>
<div class="card">
  <h2><i class="fa-solid fa-bolt"></i> Quick Links</h2>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="/sector/issues.php" class="btn"><i class="fa-solid fa-triangle-exclamation"></i> View Sector Issues</a>
    <a href="/sector/endorsements.php" class="btn btn-outline"><i class="fa-solid fa-thumbs-up"></i> Endorse a Proposal</a>
    <a href="/citizen/consultations.php" class="btn btn-outline"><i class="fa-solid fa-people-arrows"></i> Public Consultations</a>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script><script>(function(){var e=document.getElementById('sectorComparison');if(!e||typeof Chart==='undefined')return;new Chart(e,{type:'bar',data:{labels:['Open complaints','Pending proposals'],datasets:[{label:'Cases',data:[<?= (int)$open_issues ?>,<?= (int)$open_proposals ?>]}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});})();</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
