<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('sector_rep');
$uid = current_user_id();
$page_title = 'Endorsements';
$current_page = 'sector/endorsements.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proposal_id'])) {
  $pid = (int)$_POST['proposal_id'];
  $stmt = $conn->prepare("INSERT INTO proposal_votes (proposal_id, user_id, vote_type) VALUES (?, ?, 'up')
        ON DUPLICATE KEY UPDATE vote_type='up'");
  $stmt->bind_param('ii', $pid, $uid);
  $stmt->execute();
  $conn->query("UPDATE proposals SET votes_up = (SELECT COUNT(*) FROM proposal_votes WHERE proposal_id=$pid AND vote_type='up') WHERE proposal_id=$pid");
  notify_endorsement($conn, $pid);
  log_audit($conn, $uid, 'Sector Endorsement', "Endorsed proposal #$pid");
  header('Location: /sector/endorsements.php');
  exit;
}

$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$per_page = 12;
$where = "p.status IN ('pending','under_review')";
if ($search) {
  $q = $conn->real_escape_string($search);
  $where .= " AND (p.title LIKE '%$q%' OR p.description LIKE '%$q%' OR p.category LIKE '%$q%' OR u.full_name LIKE '%$q%')";
}
$total = (int)$conn->query("SELECT COUNT(*) c FROM proposals p JOIN users u ON p.user_id=u.user_id WHERE $where")->fetch_assoc()['c'];
$total_pages = max(1, (int)ceil($total / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;
$proposals = $conn->query("SELECT p.*, u.full_name,(SELECT vote_type FROM proposal_votes v WHERE v.proposal_id=p.proposal_id AND v.user_id=$uid) my_vote FROM proposals p JOIN users u ON p.user_id=u.user_id WHERE $where ORDER BY p.created_at DESC LIMIT $per_page OFFSET $offset");

require_once __DIR__ . '/../includes/header.php';
?>
<p class="muted mb">Endorse community proposals on behalf of the sector you represent to strengthen their standing for review.</p>
<div class="ajax-list-container ajax-table-container" data-ajax-endpoint="1">
  <div class="data-toolbar">
    <div class="data-search"><i class="fa-solid fa-magnifying-glass"></i><input data-ajax-search type="search" value="<?= e($search) ?>" placeholder="Search proposals..."></div>
  </div>
  <div class="grid grid-2" data-ajax-content>
    <?php if ($proposals->num_rows === 0): ?>
      <div class="empty">No pending proposals to endorse.</div>
      <?php else: while ($p = $proposals->fetch_assoc()): ?>
        <div class="card">
          <div class="flex-between">
            <div class="pill"><i class="fa-solid fa-tag"></i> <?= e(ucfirst($p['category'])) ?></div>
            <?= status_badge($p['status']) ?>
          </div>
          <h2 style="margin-top:8px"><?= e($p['title']) ?></h2>
          <p class="small muted mb"><?= e(mb_strimwidth($p['description'], 0, 140, '...')) ?></p>
          <div class="small muted mb"><i class="fa-solid fa-user"></i> By <?= e($p['full_name']) ?> &middot; <span class="badge badge-ok"><i class="fa-solid fa-thumbs-up"></i> <?= $p['votes_up'] ?></span></div>
          <form method="post" data-confirm="Confirm this endorsement action?">
            <input type="hidden" name="proposal_id" value="<?= $p['proposal_id'] ?>">
            <?php if ($p['my_vote'] === 'up'): ?>
              <button class="btn btn-sm btn-muted" disabled><i class="fa-solid fa-check"></i> Endorsed</button>
            <?php else: ?>
              <button type="submit" class="btn btn-sm"><i class="fa-solid fa-thumbs-up"></i> Endorse Proposal</button>
            <?php endif; ?>
          </form>
        </div>
    <?php endwhile;
    endif; ?>
  </div><?php render_pagination($page, $total_pages, $total, $per_page, ['q' => $search]); ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>