<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('sector_rep');
$page_title = 'Sector Representative Dashboard';
$current_page = 'sector/dashboard.php';

$sectors_stats = $conn->query("SELECT s.sector_name, COUNT(cp.profile_id) members
    FROM sectors s LEFT JOIN citizen_profiles cp ON cp.sector_id = s.sector_id
    GROUP BY s.sector_id ORDER BY members DESC");

$open_issues = $conn->query("SELECT COUNT(*) c FROM complaints WHERE status IN ('filed','investigating','mediation')")->fetch_assoc()['c'];
$open_proposals = $conn->query("SELECT COUNT(*) c FROM proposals WHERE status IN ('pending','under_review')")->fetch_assoc()['c'];

require_once __DIR__ . '/../includes/header.php';
?>
<div class="grid grid-3 mb">
  <div class="stat"><div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div><div><div class="num"><?= $open_issues ?></div><div class="lbl">Open Complaints (All)</div></div></div>
  <div class="stat"><div class="stat-icon"><i class="fa-solid fa-lightbulb"></i></div><div><div class="num"><?= $open_proposals ?></div><div class="lbl">Pending Proposals</div></div></div>
  <div class="stat"><div class="stat-icon"><i class="fa-solid fa-diagram-project"></i></div><div><div class="num"><?= $sectors_stats->num_rows ?></div><div class="lbl">Registered Sectors</div></div></div>
</div>
<div class="card">
  <h2><i class="fa-solid fa-users"></i> Sector Membership Overview</h2>
  <table>
    <tr><th>Sector</th><th>Members</th></tr>
    <?php $sectors_stats->data_seek(0); while ($s = $sectors_stats->fetch_assoc()): ?>
      <tr><td><?= e($s['sector_name']) ?></td><td><?= $s['members'] ?></td></tr>
    <?php endwhile; ?>
  </table>
</div>
<div class="card">
  <h2><i class="fa-solid fa-bolt"></i> Quick Links</h2>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="/sector/issues.php" class="btn"><i class="fa-solid fa-triangle-exclamation"></i> View Sector Issues</a>
    <a href="/sector/endorsements.php" class="btn btn-outline"><i class="fa-solid fa-thumbs-up"></i> Endorse a Proposal</a>
    <a href="/citizen/consultations.php" class="btn btn-outline"><i class="fa-solid fa-people-arrows"></i> Public Consultations</a>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
