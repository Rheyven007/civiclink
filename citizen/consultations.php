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

$search=trim($_GET['q']??''); $page=max(1,(int)($_GET['p']??1)); $per_page=12;
$where=''; if($search){$q=$conn->real_escape_string($search);$where=" WHERE c.title LIKE '%$q%' OR c.description LIKE '%$q%' OR c.status LIKE '%$q%' OR u.full_name LIKE '%$q%'";}
$total=(int)$conn->query("SELECT COUNT(*) c FROM consultations c JOIN users u ON c.created_by=u.user_id".$where)->fetch_assoc()['c']; $total_pages=max(1,(int)ceil($total/$per_page)); if($page>$total_pages)$page=$total_pages; $offset=($page-1)*$per_page;
$consultations = $conn->query("SELECT c.*, u.full_name creator,
    (SELECT COUNT(*) FROM consultation_responses r WHERE r.consultation_id=c.consultation_id) total_responses,
    (SELECT COUNT(*) FROM consultation_responses r WHERE r.consultation_id=c.consultation_id AND r.choice='support') support_c,
    (SELECT COUNT(*) FROM consultation_responses r WHERE r.consultation_id=c.consultation_id AND r.choice='oppose') oppose_c,
    (SELECT choice FROM consultation_responses r WHERE r.consultation_id=c.consultation_id AND r.user_id=$uid) my_choice
    FROM consultations c JOIN users u ON c.created_by=u.user_id".$where." ORDER BY c.status='open' DESC, c.start_date DESC LIMIT $per_page OFFSET $offset");

require_once __DIR__ . '/../includes/header.php';
?>
<p class="muted mb"><i class="fa-solid fa-people-arrows"></i> Participate in public discussions and voting for community planning.</p>
<div class="ajax-list-container ajax-table-container" data-ajax-endpoint="1"><div class="data-toolbar"><div class="data-search"><i class="fa-solid fa-magnifying-glass"></i><input data-ajax-search type="search" value="<?= e($search) ?>" placeholder="Search consultations..."></div></div><div class="grid grid-2" data-ajax-content>
<?php if ($consultations->num_rows === 0): ?>
  <div class="empty"><i class="fa-regular fa-comments"></i>No consultations available right now.</div>
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
    <div class="small mb"><span class="badge badge-ok"><i class="fa-solid fa-chart-simple"></i> <?= $support_pct ?>% support</span> &middot; <?= $c['total_responses'] ?> responses</div>
    <div class="bar mb"><div class="bar-fill" style="width:<?= $support_pct ?>%"></div></div>
    <?php if ($c['status'] === 'open'): ?>
    <button type="button" class="btn btn-sm" data-open-inline-modal="consultModal<?= (int)$c['consultation_id'] ?>"><i class="fa-solid fa-square-poll-vertical"></i> <?= $c['my_choice'] ? 'Update Response' : 'Respond' ?></button>
    <div class="app-modal" id="consultModal<?= (int)$c['consultation_id'] ?>" aria-hidden="true"><div class="app-modal-dialog" role="dialog" aria-modal="true"><div class="app-modal-head"><h2><?= $c['my_choice'] ? 'Update Response' : 'Respond to Consultation' ?></h2><button type="button" class="app-modal-close" data-close-inline-modal><i class="fa-solid fa-xmark"></i></button></div><div class="app-modal-body"><form method="post">
      <input type="hidden" name="consultation_id" value="<?= $c['consultation_id'] ?>">
      <div class="field"><label>Your position</label><select name="choice"><option value="support" <?= $c['my_choice']==='support'?'selected':'' ?>>Support</option><option value="oppose" <?= $c['my_choice']==='oppose'?'selected':'' ?>>Oppose</option><option value="neutral" <?= $c['my_choice']==='neutral'?'selected':'' ?>>Neutral</option></select></div>
      <div class="field"><label>Comment</label><textarea name="response_text" placeholder="Optional comment..."></textarea></div>
      <button type="submit" class="btn btn-sm"><i class="fa-solid fa-square-poll-vertical"></i> <?= $c['my_choice'] ? 'Update Response' : 'Submit Response' ?></button>
    </form></div></div></div>
    <?php else: ?>
      <div class="small muted"><i class="fa-solid fa-lock"></i> This consultation is closed.</div>
    <?php endif; ?>
  </div>
<?php endwhile; endif; ?>
</div><?php render_pagination($page,$total_pages,$total,$per_page,['q'=>$search]); ?></div>
<script>(function(){document.querySelectorAll('[data-open-inline-modal]').forEach(function(b){b.addEventListener('click',function(){var m=document.getElementById(b.dataset.openInlineModal);if(m){m.classList.add('open');m.setAttribute('aria-hidden','false');}});});document.querySelectorAll('[data-close-inline-modal]').forEach(function(b){b.addEventListener('click',function(){b.closest('.app-modal').classList.remove('open');});});document.querySelectorAll('.app-modal').forEach(function(m){m.addEventListener('click',function(e){if(e.target===m)m.classList.remove('open');});});})();</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
