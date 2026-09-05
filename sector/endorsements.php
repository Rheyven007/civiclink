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
    log_audit($conn, $uid, 'Sector Endorsement', "Endorsed proposal #$pid");
    header('Location: /sector/endorsements.php');
    exit;
}

$proposals = $conn->query("SELECT p.*, u.full_name,
    (SELECT vote_type FROM proposal_votes v WHERE v.proposal_id=p.proposal_id AND v.user_id=$uid) my_vote
    FROM proposals p JOIN users u ON p.user_id=u.user_id WHERE p.status IN ('pending','under_review') ORDER BY p.created_at DESC");

require_once __DIR__ . '/../includes/header.php';
?>
<p class="muted mb">Endorse community proposals on behalf of the sector you represent to strengthen their standing for review.</p>
<div class="grid grid-2">
<?php if ($proposals->num_rows === 0): ?>
  <div class="empty">No pending proposals to endorse.</div>
<?php else: while ($p = $proposals->fetch_assoc()): ?>
  <div class="card">
    <div class="flex-between">
      <div class="pill"><?= e(ucfirst($p['category'])) ?></div>
      <?= status_badge($p['status']) ?>
    </div>
    <h2 style="margin-top:8px"><?= e($p['title']) ?></h2>
    <p class="small muted mb"><?= e(mb_strimwidth($p['description'],0,140,'...')) ?></p>
    <div class="small muted mb">By <?= e($p['full_name']) ?> &middot; 👍 <?= $p['votes_up'] ?></div>
    <form method="post">
      <input type="hidden" name="proposal_id" value="<?= $p['proposal_id'] ?>">
      <?php if ($p['my_vote'] === 'up'): ?>
        <button class="btn btn-sm btn-muted" disabled>✓ Endorsed</button>
      <?php else: ?>
        <button type="submit" class="btn btn-sm">Endorse Proposal</button>
      <?php endif; ?>
    </form>
  </div>
<?php endwhile; endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
