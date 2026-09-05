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

function notif_icon($message) {
    if (stripos($message, 'vote') !== false) return ['fa-thumbs-up', 'badge-accent'];
    if (stripos($message, 'decision log') !== false || stripos($message, 'status') !== false) return ['fa-scroll', 'badge-ok'];
    return ['fa-bell', 'badge-info'];
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <h2><i class="fa-solid fa-bell"></i> Your Notifications</h2>
  <p class="small muted mb">You'll always be notified here when a proposal you follow gets a vote update or when a new decision is logged for transparency.</p>
  <?php if ($notifs->num_rows === 0): ?>
    <div class="empty"><i class="fa-regular fa-bell-slash"></i>No notifications yet.</div>
  <?php else: while ($n = $notifs->fetch_assoc()):
    [$icon, $cls] = notif_icon($n['message']);
  ?>
    <div class="flex-between" style="padding:12px 0;border-bottom:1px solid var(--line);align-items:flex-start;gap:12px">
      <div style="display:flex;gap:12px;align-items:flex-start">
        <span class="badge <?= $cls ?>" style="padding:8px;border-radius:9px"><i class="fa-solid <?= $icon ?>"></i></span>
        <div>
          <span><?= e($n['message']) ?></span>
          <?php if ($n['link']): ?><br><a href="<?= e($n['link']) ?>" class="small"><i class="fa-solid fa-arrow-right"></i> View details</a><?php endif; ?>
        </div>
      </div>
      <span class="small muted" style="white-space:nowrap"><?= time_ago($n['created_at']) ?></span>
    </div>
  <?php endwhile; endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
