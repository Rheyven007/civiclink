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

$stmt = $conn->prepare("SELECT * FROM feedback WHERE user_id=? ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param('i', $uid);
$stmt->execute();
$mine = $stmt->get_result();

require_once __DIR__ . '/../includes/header.php';
?>
<?php if (isset($_GET['sent'])): ?><div class="alert alert-ok"><i class="fa-solid fa-circle-check"></i> Thank you! Your feedback has been recorded.</div><?php endif; ?>
<div class="grid grid-2">
  <div class="card">
    <h2><i class="fa-solid fa-star"></i> Rate LGU Services</h2>
    <?php if ($error): ?><div class="alert alert-bad"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
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
    </form>
  </div>
  <div class="card">
    <h2><i class="fa-solid fa-comment-dots"></i> Your Recent Feedback</h2>
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
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
