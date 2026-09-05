<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$page_title = 'Audit Logs';
$current_page = 'admin/audit_logs.php';

$page = max(1, (int)($_GET['p'] ?? 1));
$per_page = 30;
$offset = ($page - 1) * $per_page;

$total = $conn->query("SELECT COUNT(*) c FROM audit_logs")->fetch_assoc()['c'];
$logs = $conn->query("SELECT a.*, u.full_name, u.role FROM audit_logs a LEFT JOIN users u ON a.user_id=u.user_id ORDER BY a.created_at DESC LIMIT $per_page OFFSET $offset");

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <h2><i class="fa-solid fa-file-shield"></i> System Audit Logs (<?= $total ?> total)</h2>
  <table>
    <tr><th>User</th><th>Role</th><th>Action</th><th>Details</th><th>IP</th><th>Timestamp</th></tr>
    <?php if ($logs->num_rows === 0): ?>
      <tr><td colspan="6" class="empty"><i class="fa-regular fa-folder-open"></i>No logs found.</td></tr>
    <?php else: while ($l = $logs->fetch_assoc()): ?>
      <tr>
        <td><?= e($l['full_name'] ?? 'System') ?></td>
        <td><span class="pill"><?= e($l['role'] ? ucwords(str_replace('_',' ',$l['role'])) : '—') ?></span></td>
        <td><?= e($l['action']) ?></td>
        <td class="small muted"><?= e($l['details']) ?></td>
        <td class="small muted"><?= e($l['ip_address']) ?></td>
        <td class="small muted"><?= date('M j, Y g:i A', strtotime($l['created_at'])) ?></td>
      </tr>
    <?php endwhile; endif; ?>
  </table>
  <div class="flex-between mt">
    <?php if ($page > 1): ?><a href="?p=<?= $page-1 ?>" class="btn btn-sm btn-outline"><i class="fa-solid fa-arrow-left"></i> Previous</a><?php else: ?><span></span><?php endif; ?>
    <span class="small muted">Page <?= $page ?> of <?= max(1, ceil($total/$per_page)) ?></span>
    <?php if ($offset + $per_page < $total): ?><a href="?p=<?= $page+1 ?>" class="btn btn-sm btn-outline">Next <i class="fa-solid fa-arrow-right"></i></a><?php else: ?><span></span><?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
