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

// Status stepper mapping
$status_order = ['pending','under_review','approved','implemented'];
$reject_statuses = ['rejected','cancelled'];
$current = $p['status'];
$is_rejected = in_array($current, $reject_statuses, true);

require_once __DIR__ . '/../includes/header.php';
?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
  <a href="/citizen/dashboard.php">Dashboard</a>
  <span class="sep">/</span>
  <a href="/citizen/proposals.php">Proposals</a>
  <span class="sep">/</span>
  <span class="current"><?= e(mb_strimwidth($p['title'], 0, 40, '…')) ?></span>
</nav>

<div class="card card-wide">
  <div class="flex-between">
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <code class="ref-code"><?= e(ref_number('proposal', $p['proposal_id'], $p['created_at'])) ?></code>
      <div class="pill"><i class="fa-solid fa-tag"></i> <?= e(ucfirst($p['category'])) ?></div>
    </div>
    <?= status_badge($p['status']) ?>
  </div>
  <h2 style="margin-top:10px;font-size:20px;margin-bottom:6px"><?= e($p['title']) ?></h2>
  <div class="small muted mb">
    <i class="fa-solid fa-user"></i> Proposed by <?= e($p['full_name']) ?>
    &middot; Submitted <?= e(format_datetime($p['created_at'])) ?>
    <?php if (!empty($p['updated_at']) && $p['updated_at'] !== $p['created_at']): ?>
      &middot; Last updated <?= e(format_datetime($p['updated_at'])) ?>
    <?php endif; ?>
  </div>
  <?php $help = status_help($p['status']); if ($help): ?>
  <p class="small muted mb" style="background:var(--muted-bg);padding:8px 12px;border-radius:8px"><i class="fa-solid fa-circle-info"></i> <?= e($help) ?></p>
  <?php endif; ?>

  <?php if (!$is_rejected): ?>
  <div class="stepper" aria-label="Proposal progress">
    <?php
    $reached = false;
    $found_current = false;
    foreach ($status_order as $s):
      $cls = '';
      if ($s === $current) { $cls = 'current'; $found_current = true; }
      elseif (!$found_current) { $cls = 'done'; }
    ?>
      <div class="step <?= $cls ?>"><?= ucwords(str_replace('_',' ',$s)) ?></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <h2><i class="fa-solid fa-align-left"></i> Description</h2>
  <p class="mb"><?= nl2br(e($p['description'])) ?></p>
  <?php if ($p['justification']): ?><h2><i class="fa-solid fa-scale-balanced"></i> Justification</h2><p class="mb"><?= nl2br(e($p['justification'])) ?></p><?php endif; ?>
  <?php if ($p['expected_benefits']): ?><h2><i class="fa-solid fa-seedling"></i> Expected Benefits</h2><p class="mb"><?= nl2br(e($p['expected_benefits'])) ?></p><?php endif; ?>

  <div class="flex-between mt" style="border-top:1px solid var(--line);padding-top:14px">
    <div class="small" style="display:flex;gap:8px;flex-wrap:wrap">
      <span class="badge badge-ok"><i class="fa-solid fa-thumbs-up"></i> <?= (int)$p['votes_up'] ?> support</span>
      <span class="badge badge-bad"><i class="fa-solid fa-thumbs-down"></i> <?= (int)$p['votes_down'] ?> oppose</span>
    </div>
    <a href="/citizen/proposals.php" class="btn btn-muted btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
  </div>
</div>

<div class="card card-wide">
  <h2><i class="fa-solid fa-scroll"></i> Transparency: Decision Log</h2>
  <p class="small muted mb">Every LGU decision includes a written justification. You'll be notified automatically when status changes.</p>
  <?php if ($logs->num_rows === 0): ?>
    <div class="empty">
      <i class="fa-regular fa-clock"></i>
      <p>No decisions recorded yet. This proposal is awaiting LGU review.</p>
    </div>
  <?php else: ?>
    <div class="timeline">
    <?php while ($l = $logs->fetch_assoc()):
      $d = strtolower($l['decision'] ?? '');
      $tlcls = '';
      if (in_array($d, ['rejected','dismissed','cancelled'], true)) $tlcls = 'bad';
      elseif (in_array($d, ['pending','under_review','investigating'], true)) $tlcls = 'info';
      elseif (in_array($d, ['approved','implemented','resolved'], true)) $tlcls = '';
      else $tlcls = 'warn';
    ?>
      <div class="timeline-item <?= $tlcls ?>">
        <div class="tl-head">
          <?= status_badge($l['decision']) ?>
          <span class="small muted"><?= time_ago($l['created_at']) ?></span>
        </div>
        <div class="tl-body"><?= nl2br(e($l['justification'])) ?></div>
        <div class="tl-meta"><i class="fa-solid fa-user-tie"></i> Decided by <?= e($l['officer']) ?></div>
      </div>
    <?php endwhile; ?>
    </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
