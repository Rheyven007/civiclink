<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['citizen','sector_rep']);
$uid = current_user_id();
$page_title = 'Service Requests';
$current_page = 'citizen/requests.php';

$status = $_GET['status'] ?? 'all';
$valid_statuses = ['submitted','in_progress','resolved','closed','cancelled'];
if ($status !== 'all' && !in_array($status, $valid_statuses, true)) $status = 'all';

// Counts
$status_counts = ['all' => 0];
foreach ($valid_statuses as $s) $status_counts[$s] = 0;
$cres = $conn->prepare("SELECT status, COUNT(*) c FROM service_requests WHERE user_id=? GROUP BY status");
$cres->bind_param('i', $uid);
$cres->execute();
$cr = $cres->get_result();
while ($r = $cr->fetch_assoc()) {
    $status_counts[$r['status']] = (int)$r['c'];
    $status_counts['all'] += (int)$r['c'];
}
$cres->close();

$search=trim($_GET['q']??''); $page=max(1,(int)($_GET['p']??1)); $per_page=12;
$where=["r.user_id=$uid"];
if($status!=='all') $where[]="r.status='".$conn->real_escape_string($status)."'";
if($search){$q=$conn->real_escape_string($search);$where[]="(r.service_type LIKE '%$q%' OR r.department LIKE '%$q%' OR r.priority LIKE '%$q%' OR r.status LIKE '%$q%')";}
$whereSql=' WHERE '.implode(' AND ',$where);
$total=(int)$conn->query("SELECT COUNT(*) c FROM service_requests r".$whereSql)->fetch_assoc()['c']; $total_pages=max(1,(int)ceil($total/$per_page)); if($page>$total_pages)$page=$total_pages; $offset=($page-1)*$per_page;
$requests=$conn->query("SELECT r.* FROM service_requests r".$whereSql." ORDER BY r.created_at DESC LIMIT $per_page OFFSET $offset");

$status_labels = [
    'all' => 'All',
    'submitted' => 'Submitted',
    'in_progress' => 'In Progress',
    'resolved' => 'Resolved',
    'closed' => 'Closed',
    'cancelled' => 'Cancelled',
];

require_once __DIR__ . '/../includes/header.php';
?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
  <a href="/citizen/dashboard.php">Dashboard</a>
  <span class="sep">/</span>
  <span class="current">Service Requests</span>
</nav>

<div class="flex-between mb">
  <p class="muted"><i class="fa-solid fa-clipboard-list"></i> Track your non-emergency public service requests.</p>
  <a href="/citizen/request_new.php" class="btn open-form-modal"><i class="fa-solid fa-plus"></i> Request a Public Service</a>
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
<div class="data-toolbar"><div class="data-search"><i class="fa-solid fa-magnifying-glass"></i><input data-ajax-search type="search" value="<?= e($search) ?>" placeholder="Search requests..."></div></div>
<div class="table-wrap"><table>
<tr><th>Reference</th><th>Service Type</th><th>Department</th><th>Priority</th><th>Status</th><th>Submitted</th></tr>
<?php if ($requests->num_rows === 0): ?>
  <tr><td colspan="6">
    <div class="empty">
      <i class="fa-regular fa-folder-open"></i>
      <p>No service requests<?= $status !== 'all' ? ' with status “' . e($status_labels[$status]) . '”' : ' yet' ?>.<?= $status === 'all' ? ' Request a non-emergency public service from your LGU.' : '' ?></p>
    </div>
  </td></tr>
<?php else: while ($r = $requests->fetch_assoc()): ?>
  <tr>
    <td><code class="ref-code"><?= e(ref_number('request', $r['request_id'], $r['created_at'])) ?></code></td>
    <td><?= e($r['service_type']) ?></td>
    <td><?= e($r['department'] ?: '—') ?></td>
    <td><?= e(ucfirst($r['priority'])) ?></td>
    <td><?= status_badge($r['status']) ?></td>
    <td class="small muted" title="<?= e(format_datetime($r['created_at'])) ?>"><?= time_ago($r['created_at']) ?></td>
  </tr>
<?php endwhile; endif; ?>
</table></div>
<?php render_pagination($page,$total_pages,$total,$per_page,['status'=>$status,'q'=>$search]); ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>