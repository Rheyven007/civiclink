<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['citizen','sector_rep']);
$uid = current_user_id();
$page_title = 'Public Consultations';
$current_page = 'citizen/consultations.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['consultation_id'])) {
    $cid = (int)$_POST['consultation_id'];
    $choice = in_array($_POST['choice'] ?? '', ['support','oppose','neutral']) ? $_POST['choice'] : 'neutral';
    $text = trim($_POST['response_text'] ?? '');
    $stmt = $conn->prepare("INSERT INTO consultation_responses (consultation_id, user_id, response_text, choice) VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE response_text=VALUES(response_text), choice=VALUES(choice)");
    $stmt->bind_param('iiss', $cid, $uid, $text, $choice);
    $stmt->execute();
    header('Location: /citizen/consultations.php');
    exit;
}

$consultations = $conn->query("SELECT c.*, u.full_name creator,
    (SELECT COUNT(*) FROM consultation_responses r WHERE r.consultation_id=c.consultation_id) total_responses,
    (SELECT COUNT(*) FROM consultation_responses r WHERE r.consultation_id=c.consultation_id AND r.choice='support') support_c,
    (SELECT COUNT(*) FROM consultation_responses r WHERE r.consultation_id=c.consultation_id AND r.choice='oppose') oppose_c,
    (SELECT choice FROM consultation_responses r WHERE r.consultation_id=c.consultation_id AND r.user_id=$uid) my_choice
    FROM consultations c JOIN users u ON c.created_by=u.user_id ORDER BY c.status='open' DESC, c.start_date DESC");

require_once __DIR__ . '/../includes/header.php';
?>
<p class="muted mb">Participate in public discussions and voting for community planning.</p>
<div class="grid grid-2">
<?php if ($consultations->num_rows === 0): ?>
  <div class="empty">No consultations available right now.</div>
<?php else: while ($c = $consultations->fetch_assoc()):
    $total = max(1, $c['total_responses']);
    $support_pct = round(($c['support_c']/$total)*100);
?>
  <div class="card">
    <div class="flex-between">
      <?= status_badge($c['status']) ?>
      <span class="small muted"><?= date('M j', strtotime($c['start_date'])) ?> – <?= date('M j, Y', strtotime($c['end_date'])) ?></span>
    </div>
    <h2 style="margin-top:8px"><?= e($c['title']) ?></h2>
    <p class="small muted mb"><?= nl2br(e($c['description'])) ?></p>
    <div class="small mb">Support: <?= $support_pct ?>% (<?= $c['total_responses'] ?> responses)</div>
    <div class="bar mb"><div class="bar-fill" style="width:<?= $support_pct ?>%"></div></div>
    <?php if ($c['status'] === 'open'): ?>
    <form method="post">
      <input type="hidden" name="consultation_id" value="<?= $c['consultation_id'] ?>">
      <div class="field">
        <label>Your position</label>
        <select name="choice">
          <option value="support" <?= $c['my_choice']==='support'?'selected':'' ?>>Support</option>
          <option value="oppose" <?= $c['my_choice']==='oppose'?'selected':'' ?>>Oppose</option>
          <option value="neutral" <?= $c['my_choice']==='neutral'?'selected':'' ?>>Neutral</option>
        </select>
      </div>
      <div class="field">
        <textarea name="response_text" placeholder="Optional comment..."></textarea>
      </div>
      <button type="submit" class="btn btn-sm"><?= $c['my_choice'] ? 'Update Response' : 'Submit Response' ?></button>
    </form>
    <?php else: ?>
      <div class="small muted">This consultation is closed.</div>
    <?php endif; ?>
  </div>
<?php endwhile; endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
