<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$uid = current_user_id();
$page_title = 'Notifications';
$current_page = 'citizen/notifications.php';

$conn->query("UPDATE notifications SET is_read=1 WHERE user_id=$uid");

$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50");
$stmt->bind_param('i', $uid);
$stmt->execute();
$notifs = $stmt->get_result();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <h2>Your Notifications</h2>
  <?php if ($notifs->num_rows === 0): ?>
    <div class="empty">No notifications yet.</div>
  <?php else: while ($n = $notifs->fetch_assoc()): ?>
    <div style="padding:10px 0;border-bottom:1px solid var(--line)">
      <div class="flex-between">
        <span><?= e($n['message']) ?></span>
        <span class="small muted"><?= time_ago($n['created_at']) ?></span>
      </div>
      <?php if ($n['link']): ?><a href="<?= e($n['link']) ?>" class="small">View →</a><?php endif; ?>
    </div>
  <?php endwhile; endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
