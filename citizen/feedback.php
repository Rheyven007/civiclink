<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['citizen','sector_rep']);
$uid = current_user_id();
$page_title = 'Feedback & Ratings';
$current_page = 'citizen/feedback.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)($_POST['rating'] ?? 0);
    $comments = trim($_POST['comments'] ?? '');
    $ref_type = $_POST['reference_type'] ?? 'general';
    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating between 1 and 5 stars.';
    } else {
        $stmt = $conn->prepare("INSERT INTO feedback (user_id, reference_type, rating, comments) VALUES (?,?,?,?)");
        $stmt->bind_param('isis', $uid, $ref_type, $rating, $comments);
        $stmt->execute();
        header('Location: /citizen/feedback.php?sent=1');
        exit;
    }
}

$search=trim($_GET['q']??''); $page=max(1,(int)($_GET['p']??1)); $per_page=12; $where="user_id=$uid"; if($search){$q=$conn->real_escape_string($search);$where.=" AND (comments LIKE '%$q%' OR reference_type LIKE '%$q%')";} $total=(int)$conn->query("SELECT COUNT(*) c FROM feedback WHERE $where")->fetch_assoc()['c']; $total_pages=max(1,(int)ceil($total/$per_page)); if($page>$total_pages)$page=$total_pages; $offset=($page-1)*$per_page; $mine=$conn->query("SELECT * FROM feedback WHERE $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");

require_once __DIR__ . '/../includes/header.php';
?>
<?php if (isset($_GET['sent'])): ?><div class="alert alert-ok"><i class="fa-solid fa-circle-check"></i> Thank you! Your feedback has been recorded.</div><?php endif; ?>
<div class="modal-form-trigger-row"><p class="muted"><i class="fa-solid fa-star"></i> Share your experience with LGU services.</p><button type="button" class="btn btn-sm" data-open-inline-modal="feedbackModal"><i class="fa-solid fa-plus"></i> Add Feedback</button></div><div class="app-modal" id="feedbackModal" aria-hidden="true"><div class="app-modal-dialog" role="dialog" aria-modal="true"><div class="app-modal-head"><h2>Rate LGU Services</h2><button type="button" class="app-modal-close" data-close-inline-modal><i class="fa-solid fa-xmark"></i></button></div><div class="app-modal-body"><form method="post">
      <div class="field">
        <label>What are you rating?</label>
        <select name="reference_type">
          <option value="general">General Service Experience</option>
          <option value="service_request">A Service Request</option>
          <option value="complaint">Complaint Handling</option>
        </select>
      </div>
      <div class="field">
        <label>Rating</label>
        <select name="rating" required>
          <option value="">Select rating</option>
          <option value="5">★★★★★ Excellent</option>
          <option value="4">★★★★ Good</option>
          <option value="3">★★★ Average</option>
          <option value="2">★★ Poor</option>
          <option value="1">★ Very Poor</option>
        </select>
      </div>
      <div class="field">
        <label>Comments</label>
        <textarea name="comments" placeholder="Tell us more..."></textarea>
      </div>
      <button type="submit" class="btn"><i class="fa-solid fa-paper-plane"></i> Submit Feedback</button>
    </form></div></div></div>
<div class="card ajax-list-container ajax-table-container" data-ajax-endpoint="1">
    <div class="data-toolbar"><h2 style="margin:0"><i class="fa-solid fa-comment-dots"></i> Your Recent Feedback</h2><div class="data-search"><i class="fa-solid fa-magnifying-glass"></i><input data-ajax-search type="search" value="<?= e($search) ?>" placeholder="Search feedback..."></div></div>
    <div data-ajax-content>
    <?php if ($mine->num_rows === 0): ?>
      <div class="empty"><i class="fa-regular fa-star"></i>You haven't submitted feedback yet.</div>
    <?php else: while ($f = $mine->fetch_assoc()): ?>
      <div style="padding:9px 0;border-bottom:1px solid var(--line)">
        <div class="flex-between">
          <strong><?= str_repeat('★', $f['rating']) . str_repeat('☆', 5-$f['rating']) ?></strong>
          <span class="small muted"><?= time_ago($f['created_at']) ?></span>
        </div>
        <?php if ($f['comments']): ?><p class="small mt"><?= e($f['comments']) ?></p><?php endif; ?>
      </div>
    <?php endwhile; endif; ?>
    </div>
    <?php render_pagination($page,$total_pages,$total,$per_page,['q'=>$search]); ?>
  </div>
<script>(function(){document.querySelectorAll('[data-open-inline-modal]').forEach(function(b){b.onclick=function(){document.getElementById(b.dataset.openInlineModal).classList.add('open');};});document.querySelectorAll('[data-close-inline-modal]').forEach(function(b){b.onclick=function(){b.closest('.app-modal').classList.remove('open');};});document.querySelectorAll('.app-modal').forEach(function(m){m.onclick=function(e){if(e.target===m)m.classList.remove('open');};});})();</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
