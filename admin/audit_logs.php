<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$page_title = 'Audit Logs';
$current_page = 'admin/audit_logs.php';
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$per_page = 12;
$where = '';
if ($search) {
  $q = $conn->real_escape_string($search);
  $where = " WHERE a.action LIKE '%$q%' OR a.details LIKE '%$q%' OR u.full_name LIKE '%$q%' OR u.role LIKE '%$q%' OR a.ip_address LIKE '%$q%'";
}
$total = (int)$conn->query("SELECT COUNT(*) c FROM audit_logs a LEFT JOIN users u ON a.user_id=u.user_id" . $where)->fetch_assoc()['c'];
$total_pages = max(1, (int)ceil($total / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;
$logs = $conn->query("SELECT a.*,u.full_name,u.role FROM audit_logs a LEFT JOIN users u ON a.user_id=u.user_id" . $where . " ORDER BY a.created_at DESC LIMIT $per_page OFFSET $offset");
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card ajax-table-container" data-ajax-endpoint="1" style="margin-bottom:0">
  <div class="data-toolbar">
    <h2 style="margin:0"><i class="fa-solid fa-file-shield"></i> System Audit Logs <span class="small muted" data-result-count>(<?= number_format($total) ?> total)</span></h2>
    <div class="data-search"><i class="fa-solid fa-magnifying-glass"></i><input data-ajax-search type="search" value="<?= e($search) ?>" placeholder="Search user, action, details..."></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>User</th>
          <th>Role</th>
          <th>Action</th>
          <th>Details</th>
          <th>IP</th>
          <th>Timestamp</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($logs->num_rows === 0): ?><tr>
            <td colspan="6">
              <div class="empty"><i class="fa-regular fa-folder-open"></i>No logs found.</div>
            </td>
          </tr>
          <?php else: while ($l = $logs->fetch_assoc()): ?>
            <tr>
              <td><?= e($l['full_name'] ?? 'System') ?></td>
              <td><span class="pill"><?= e($l['role'] ? ucwords(str_replace('_', ' ', $l['role'])) : '—') ?></span></td>
              <td><?= e($l['action']) ?></td>
              <td class="small muted"><?= e($l['details']) ?></td>
              <td class="small muted"><?= e($l['ip_address']) ?></td>
              <td class="small" title="<?= e(format_datetime($l['created_at'])) ?>"><strong><?= e(format_datetime($l['created_at'])) ?></strong>
                <div class="muted"><?= time_ago($l['created_at']) ?></div>
              </td>
            </tr>
        <?php endwhile;
        endif; ?>
      </tbody>
    </table>
  </div>
  <?php render_pagination($page, $total_pages, $total, $per_page, ['q' => $search]); ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>