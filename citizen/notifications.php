<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$uid = current_user_id();
$page_title = 'Notifications';
$current_page = 'citizen/notifications.php';

$conn->query("UPDATE notifications SET is_read=1 WHERE user_id=$uid");

$search=trim($_GET['q']??''); $page=max(1,(int)($_GET['p']??1)); $per_page=12; $where="user_id=$uid"; if($search){$q=$conn->real_escape_string($search);$where.=" AND message LIKE '%$q%'";} $total=(int)$conn->query("SELECT COUNT(*) c FROM notifications WHERE $where")->fetch_assoc()['c']; $total_pages=max(1,(int)ceil($total/$per_page)); if($page>$total_pages)$page=$total_pages; $offset=($page-1)*$per_page;
$notifs=$conn->query("SELECT * FROM notifications WHERE $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");

function notif_icon($message) {
    if (stripos($message, 'vote') !== false) return ['fa-thumbs-up', 'badge-accent'];
    if (stripos($message, 'decision log') !== false || stripos($message, 'status') !== false) return ['fa-scroll', 'badge-ok'];
    return ['fa-bell', 'badge-info'];
}
function notif_day_label($datetime) {
    $day = date('Y-m-d', strtotime($datetime));
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    if ($day === $today) return 'Today';
    if ($day === $yesterday) return 'Yesterday';
    return date('F j, Y', strtotime($datetime));
}

require_once __DIR__ . '/../includes/header.php';
$current_day = null;
?>
<div class="flex-between mb">
  <div>
    <h2 style="margin:0;font-size:18px;color:var(--primary-dark)"><i class="fa-solid fa-bell"></i> Your Notifications</h2>
    <p class="small muted" style="margin-top:4px">You'll be notified when a proposal you follow gets a vote update or when a new decision is logged.</p>
  </div>
  <span class="small muted"><?= (int)$notifs->num_rows ?> recent</span>
</div>

<?php if ($notifs->num_rows === 0): ?>
  <div class="card">
    <div class="empty"><i class="fa-regular fa-bell-slash"></i>No notifications yet.</div>
  </div>
<?php else: ?>
  <div class="ajax-list-container ajax-table-container" data-ajax-endpoint="1">
  <div class="data-toolbar"><div class="data-search"><i class="fa-solid fa-magnifying-glass"></i><input data-ajax-search type="search" value="<?= e($search) ?>" placeholder="Search notifications..."></div></div>
  <div class="notif-list-cards" data-ajax-content>
  <?php while ($n = $notifs->fetch_assoc()):
    [$icon, $cls] = notif_icon($n['message']);
    $day_label = notif_day_label($n['created_at']);
    $is_new_day = ($day_label !== $current_day);
    $current_day = $day_label;
    $unread = empty($n['is_read']);
  ?>
    <?php if ($is_new_day): ?>
      <div class="notif-day-label"><?= e($day_label) ?></div>
    <?php endif; ?>
    <div class="notif-card<?= $unread ? ' unread' : '' ?>">
      <span class="badge <?= $cls ?> notif-card-icon"><i class="fa-solid <?= $icon ?>"></i></span>
      <div class="notif-card-body">
        <div class="notif-card-msg"><?= e($n['message']) ?></div>
        <div class="notif-card-meta">
          <span title="<?= e(format_datetime($n['created_at'])) ?>"><?= time_ago($n['created_at']) ?></span>
          <?php if ($n['link']): ?>
            <span class="sep">&middot;</span>
            <a href="<?= e($n['link']) ?>"><i class="fa-solid fa-arrow-right"></i> View details</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endwhile; ?>
  </div>
  <?php render_pagination($page,$total_pages,$total,$per_page,['q'=>$search]); ?>
  </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
