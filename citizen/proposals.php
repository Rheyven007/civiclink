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
    header('Location: /citizen/proposals.php');
    exit;
}

$filter = $_GET['filter'] ?? 'all';
$sql = "SELECT p.*, u.full_name FROM proposals p JOIN users u ON p.user_id = u.user_id";
if ($filter === 'mine') $sql .= " WHERE p.user_id = $uid";
$sql .= " ORDER BY p.created_at DESC";
$proposals = $conn->query($sql);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="flex-between mb">
  <div class="tabs" style="border:none;margin:0">
    <a href="?filter=all" class="<?= $filter==='all'?'active':'' ?>"><i class="fa-solid fa-list"></i> All Proposals</a>
    <a href="?filter=mine" class="<?= $filter==='mine'?'active':'' ?>"><i class="fa-solid fa-user"></i> My Proposals</a>
  </div>
  <a href="/citizen/proposal_new.php" class="btn"><i class="fa-solid fa-plus"></i> New Proposal</a>
</div>

<div class="grid grid-2">
<?php if ($proposals->num_rows === 0): ?>
  <div class="empty"><i class="fa-regular fa-lightbulb"></i>No proposals yet.</div>
<?php else: while ($p = $proposals->fetch_assoc()): ?>
  <div class="card">
    <div class="flex-between">
      <div class="pill"><i class="fa-solid fa-tag"></i> <?= e(ucfirst($p['category'])) ?></div>
      <?= status_badge($p['status']) ?>
    </div>
    <h2 style="margin-top:8px"><?= e($p['title']) ?></h2>
    <p class="small muted mb"><?= e(mb_strimwidth($p['description'],0,140,'...')) ?></p>
    <div class="small muted mb"><i class="fa-solid fa-user"></i> By <?= e($p['full_name']) ?> &middot; <?= time_ago($p['created_at']) ?></div>
    <form method="post" style="display:flex;gap:8px;align-items:center">
      <input type="hidden" name="proposal_id" value="<?= $p['proposal_id'] ?>">
      <button type="submit" name="vote" value="up" class="btn btn-sm btn-outline vote-btn"><i class="fa-solid fa-thumbs-up"></i> <?= $p['votes_up'] ?></button>
      <button type="submit" name="vote" value="down" class="btn btn-sm btn-outline vote-btn"><i class="fa-solid fa-thumbs-down"></i> <?= $p['votes_down'] ?></button>
      <a href="/citizen/proposal_view.php?id=<?= $p['proposal_id'] ?>" class="btn btn-sm btn-muted" style="margin-left:auto"><i class="fa-solid fa-eye"></i> View Details</a>
    </form>
  </div>
<?php endwhile; endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
