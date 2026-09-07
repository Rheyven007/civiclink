<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['citizen','sector_rep']);
$uid = current_user_id();
$page_title = 'Complaints & Concerns';
$current_page = 'citizen/complaints.php';

$status = $_GET['status'] ?? 'all';
$valid_statuses = ['filed','investigating','mediation','resolved','dismissed'];
if ($status !== 'all' && !in_array($status, $valid_statuses, true)) $status = 'all';

// Counts
$status_counts = ['all' => 0];
foreach ($valid_statuses as $s) $status_counts[$s] = 0;
$cres = $conn->prepare("SELECT status, COUNT(*) c FROM complaints WHERE user_id=? GROUP BY status");
$cres->bind_param('i', $uid);
$cres->execute();
$cr = $cres->get_result();
while ($r = $cr->fetch_assoc()) {
    $status_counts[$r['status']] = (int)$r['c'];
    $status_counts['all'] += (int)$r['c'];
}
$cres->close();

$search=trim($_GET['q']??''); $page=max(1,(int)($_GET['p']??1)); $per_page=12;
$where=["c.user_id=$uid"];
if($status!=='all') $where[]="c.status='".$conn->real_escape_string($status)."'";
if($search){$q=$conn->real_escape_string($search);$where[]="(c.subject LIKE '%$q%' OR c.category LIKE '%$q%' OR c.status LIKE '%$q%' OR c.resolution_notes LIKE '%$q%')";}
$whereSql=' WHERE '.implode(' AND ',$where);
$total=(int)$conn->query("SELECT COUNT(*) c FROM complaints c".$whereSql)->fetch_assoc()['c']; $total_pages=max(1,(int)ceil($total/$per_page)); if($page>$total_pages)$page=$total_pages; $offset=($page-1)*$per_page;
$complaints=$conn->query("SELECT c.* FROM complaints c".$whereSql." ORDER BY c.created_at DESC LIMIT $per_page OFFSET $offset");

$status_labels = [
    'all' => 'All',
    'filed' => 'Filed',
    'investigating' => 'Investigating',
    'mediation' => 'Mediation',
    'resolved' => 'Resolved',
    'dismissed' => 'Dismissed',
];

require_once __DIR__ . '/../includes/header.php';
?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
  <a href="/citizen/dashboard.php">Dashboard</a>
  <span class="sep">/</span>
  <span class="current">Complaints</span>
</nav>

<div class="flex-between mb">
  <p class="muted"><i class="fa-solid fa-triangle-exclamation"></i> Report and track concerns or disputes with the LGU.</p>
  <a href="/citizen/complaint_new.php" class="btn open-form-modal"><i class="fa-solid fa-plus"></i> Report a Concern</a>
</div>

<div class="tabs citizen-status-tabs">
  <?php foreach ($status_labels as $key => $label):
    $count = $status_counts[$key] ?? 0;
  ?>
    <a href="?status=<?= urlencode($key) ?>" class="<?= $status === $key ? 'active' : '' ?>">
      <?= e($label) ?>
      <span class="tab-count"><?= (int)$count ?></span>
    </a>
  <?php endforeach; ?>
</div>

<div class="card ajax-table-container" data-ajax-endpoint="1">
<div class="data-toolbar"><div class="data-search"><i class="fa-solid fa-magnifying-glass"></i><input data-ajax-search type="search" value="<?= e($search) ?>" placeholder="Search complaints..."></div></div>
<div class="table-wrap"><table>
<tr><th>Reference</th><th>Subject</th><th>Category</th><th>Status</th><th>Filed</th></tr>
<?php if ($complaints->num_rows === 0): ?>
  <tr><td colspan="5">
    <div class="empty">
      <i class="fa-regular fa-folder-open"></i>
      <p>No complaints<?= $status !== 'all' ? ' with status “' . e($status_labels[$status]) . '”' : ' filed yet' ?>.<?= $status === 'all' ? ' Report a concern about a service, facility, or personnel issue.' : '' ?></p>
    </div>
  </td></tr>
<?php else: while ($c = $complaints->fetch_assoc()): ?>
  <tr>
    <td><code class="ref-code"><?= e(ref_number('complaint', $c['complaint_id'], $c['created_at'])) ?></code></td>
    <td><?= e($c['subject']) ?></td>
    <td><?= e(ucfirst($c['category'])) ?></td>
    <td><?= status_badge($c['status']) ?></td>
    <td class="small muted" title="<?= e(format_datetime($c['created_at'])) ?>"><?= time_ago($c['created_at']) ?></td>
  </tr>
  <?php if (!empty($c['resolution_notes'])): ?>
  <tr><td colspan="5" class="small muted" style="padding-top:0;padding-bottom:14px">Resolution: <?= e($c['resolution_notes']) ?></td></tr>
  <?php endif; ?>
<?php endwhile; endif; ?>
</table></div>
<?php render_pagination($page,$total_pages,$total,$per_page,['status'=>$status,'q'=>$search]); ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>