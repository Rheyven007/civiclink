<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$id = (int)($_GET['id'] ?? 0);
$page_title = 'Proposal Details';
$current_page = 'citizen/proposals.php';

$stmt = $conn->prepare("SELECT p.*, u.full_name FROM proposals p JOIN users u ON p.user_id=u.user_id WHERE p.proposal_id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
if (!$p) { header('Location: /citizen/proposals.php'); exit; }

$stmt = $conn->prepare("SELECT d.*, u.full_name officer FROM decision_logs d JOIN users u ON d.decided_by=u.user_id WHERE reference_type='proposal' AND reference_id=? ORDER BY d.created_at DESC");
$stmt->bind_param('i', $id);
$stmt->execute();
$logs = $stmt->get_result();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width:700px">
  <div class="flex-between">
    <div class="pill"><?= e(ucfirst($p['category'])) ?></div>
    <?= status_badge($p['status']) ?>
  </div>
  <h2 style="margin-top:8px;font-size:20px"><?= e($p['title']) ?></h2>
  <div class="small muted mb">Proposed by <?= e($p['full_name']) ?> &middot; <?= time_ago($p['created_at']) ?></div>

  <h2>Description</h2>
  <p class="mb"><?= nl2br(e($p['description'])) ?></p>
  <?php if ($p['justification']): ?><h2>Justification</h2><p class="mb"><?= nl2br(e($p['justification'])) ?></p><?php endif; ?>
  <?php if ($p['expected_benefits']): ?><h2>Expected Benefits</h2><p class="mb"><?= nl2br(e($p['expected_benefits'])) ?></p><?php endif; ?>

  <div class="flex-between mt" style="border-top:1px solid var(--line);padding-top:14px">
    <div>👍 <?= $p['votes_up'] ?> support &middot; 👎 <?= $p['votes_down'] ?> oppose</div>
    <a href="/citizen/proposals.php" class="btn btn-muted">Back to Proposals</a>
  </div>
</div>

<div class="card" style="max-width:700px">
  <h2>Transparency: Decision Log</h2>
  <?php if ($logs->num_rows === 0): ?>
    <div class="empty">No decisions recorded yet. This proposal is awaiting LGU review.</div>
  <?php else: while ($l = $logs->fetch_assoc()): ?>
    <div style="padding:10px 0;border-bottom:1px solid var(--line)">
      <div class="flex-between">
        <strong><?= status_badge($l['decision']) ?></strong>
        <span class="small muted"><?= time_ago($l['created_at']) ?></span>
      </div>
      <p class="small mt" style="margin-top:6px"><?= nl2br(e($l['justification'])) ?></p>
      <div class="small muted">Decided by <?= e($l['officer']) ?></div>
    </div>
  <?php endwhile; endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
