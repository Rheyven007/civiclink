<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['citizen','sector_rep']);
$uid = current_user_id();
$page_title = 'Community Proposals';
$current_page = 'citizen/proposals.php';

// Handle voting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote'])) {
    $pid = (int)$_POST['proposal_id'];
    $vtype = $_POST['vote'] === 'up' ? 'up' : 'down';
    $stmt = $conn->prepare("INSERT INTO proposal_votes (proposal_id, user_id, vote_type) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE vote_type = VALUES(vote_type)");
    $stmt->bind_param('iis', $pid, $uid, $vtype);
    $stmt->execute();
    $conn->query("UPDATE proposals SET votes_up = (SELECT COUNT(*) FROM proposal_votes WHERE proposal_id=$pid AND vote_type='up'),
                  votes_down = (SELECT COUNT(*) FROM proposal_votes WHERE proposal_id=$pid AND vote_type='down') WHERE proposal_id=$pid");
    notify_vote_update($conn, $pid);
    $redirect = '/citizen/proposals.php';
    if (!empty($_GET['status'])) $redirect .= '?status=' . urlencode($_GET['status']);
    if (!empty($_GET['filter'])) $redirect .= (strpos($redirect,'?')!==false?'&':'?') . 'filter=' . urlencode($_GET['filter']);
    header('Location: ' . $redirect);
    exit;
}

$filter = $_GET['filter'] ?? 'all';
$status = $_GET['status'] ?? 'all';

$valid_statuses = ['pending','under_review','approved','rejected','implemented'];
if ($status !== 'all' && !in_array($status, $valid_statuses, true)) $status = 'all';

// Status counts (scoped by filter)
$count_base = "SELECT status, COUNT(*) c FROM proposals";
$count_where = [];
if ($filter === 'mine') $count_where[] = "user_id = $uid";
$count_sql = $count_base . ($count_where ? ' WHERE ' . implode(' AND ', $count_where) : '') . " GROUP BY status";
$status_counts = ['all' => 0];
foreach ($valid_statuses as $s) $status_counts[$s] = 0;
$cres = $conn->query($count_sql);
if ($cres) while ($r = $cres->fetch_assoc()) {
    $status_counts[$r['status']] = (int)$r['c'];
    $status_counts['all'] += (int)$r['c'];
}

$search=trim($_GET['q']??''); $page=max(1,(int)($_GET['p']??1)); $per_page=12;
$where = []; if ($filter==='mine') $where[] = "p.user_id=$uid"; if($status!=='all') $where[] = "p.status='".$conn->real_escape_string($status)."'"; if($search){$q=$conn->real_escape_string($search);$where[] = "(p.title LIKE '%$q%' OR p.description LIKE '%$q%' OR p.category LIKE '%$q%' OR u.full_name LIKE '%$q%')";}
$whereSql=$where?' WHERE '.implode(' AND ',$where):''; $total=(int)$conn->query("SELECT COUNT(*) c FROM proposals p JOIN users u ON p.user_id=u.user_id".$whereSql)->fetch_assoc()['c']; $total_pages=max(1,(int)ceil($total/$per_page)); if($page>$total_pages)$page=$total_pages; $offset=($page-1)*$per_page;
$proposals=$conn->query("SELECT p.*, u.full_name FROM proposals p JOIN users u ON p.user_id=u.user_id".$whereSql." ORDER BY p.created_at DESC LIMIT $per_page OFFSET $offset");

$status_labels = [
    'all' => 'All',
    'pending' => 'Pending',
    'under_review' => 'Under Review',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'implemented' => 'Implemented',
];

require_once __DIR__ . '/../includes/header.php';
?>
<div class="flex-between mb">
  <div class="tabs" style="border:none;margin:0;border-bottom:none">
    <a href="?filter=all<?= $status !== 'all' ? '&status='.urlencode($status) : '' ?>" class="<?= $filter==='all'?'active':'' ?>"><i class="fa-solid fa-list"></i> All Proposals</a>
    <a href="?filter=mine<?= $status !== 'all' ? '&status='.urlencode($status) : '' ?>" class="<?= $filter==='mine'?'active':'' ?>"><i class="fa-solid fa-user"></i> My Proposals</a>
  </div>
  <a href="/citizen/proposal_new.php" class="btn open-form-modal"><i class="fa-solid fa-plus"></i> New Proposal</a>
</div>

<div class="tabs citizen-status-tabs">
  <?php foreach ($status_labels as $key => $label):
    $href = '?status=' . urlencode($key);
    if ($filter === 'mine') $href .= '&filter=mine';
    $count = $status_counts[$key] ?? 0;
  ?>
    <a href="<?= $href ?>" class="<?= $status === $key ? 'active' : '' ?>">
      <?= e($label) ?>
      <span class="tab-count"><?= (int)$count ?></span>
    </a>
  <?php endforeach; ?>
</div>

<div class="ajax-list-container ajax-table-container" data-ajax-endpoint="1">
<div class="data-toolbar"><div class="data-search"><i class="fa-solid fa-magnifying-glass"></i><input data-ajax-search type="search" value="<?= e($search) ?>" placeholder="Search proposals..."></div></div>
<div class="grid grid-2" data-ajax-content>
<?php if ($proposals->num_rows === 0): ?>
  <div class="empty" style="grid-column:1/-1">
    <i class="fa-regular fa-lightbulb"></i>
    <p>No proposals<?= $status !== 'all' ? ' with status “' . e($status_labels[$status]) . '”' : '' ?> yet.<?= $filter === 'mine' ? '' : ' Share an idea that could improve your community.' ?></p>
  </div>
<?php else: while ($p = $proposals->fetch_assoc()): ?>
  <div class="card">
    <div class="flex-between">
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <code class="ref-code"><?= e(ref_number('proposal', $p['proposal_id'], $p['created_at'])) ?></code>
        <div class="pill"><i class="fa-solid fa-tag"></i> <?= e(ucfirst($p['category'])) ?></div>
      </div>
      <?= status_badge($p['status']) ?>
    </div>
    <h2 style="margin-top:8px"><?= e($p['title']) ?></h2>
    <p class="small muted mb"><?= e(mb_strimwidth($p['description'],0,140,'...')) ?></p>
    <div class="small muted mb"><i class="fa-solid fa-user"></i> By <?= e($p['full_name']) ?> &middot; <?= time_ago($p['created_at']) ?></div>
    <form method="post" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <input type="hidden" name="proposal_id" value="<?= $p['proposal_id'] ?>">
      <button type="submit" name="vote" value="up" class="btn btn-sm btn-outline vote-btn"><i class="fa-solid fa-thumbs-up"></i> <?= $p['votes_up'] ?></button>
      <button type="submit" name="vote" value="down" class="btn btn-sm btn-outline vote-btn"><i class="fa-solid fa-thumbs-down"></i> <?= $p['votes_down'] ?></button>
      <a href="/citizen/proposal_view.php?id=<?= $p['proposal_id'] ?>" class="btn btn-sm btn-muted" style="margin-left:auto"><i class="fa-solid fa-eye"></i> View Details</a>
    </form>
  </div>
<?php endwhile; endif; ?>
</div>
<?php render_pagination($page,$total_pages,$total,$per_page,['filter'=>$filter,'status'=>$status,'q'=>$search]); ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>