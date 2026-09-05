<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$page_title = 'Administrator Dashboard';
$current_page = 'admin/dashboard.php';

$total_users = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
$total_proposals = $conn->query("SELECT COUNT(*) c FROM proposals")->fetch_assoc()['c'];
$total_requests = $conn->query("SELECT COUNT(*) c FROM service_requests")->fetch_assoc()['c'];
$total_complaints = $conn->query("SELECT COUNT(*) c FROM complaints")->fetch_assoc()['c'];

$roles_breakdown = $conn->query("SELECT role, COUNT(*) c FROM users GROUP BY role");
$recent_audit = $conn->query("SELECT a.*, u.full_name FROM audit_logs a LEFT JOIN users u ON a.user_id=u.user_id ORDER BY a.created_at DESC LIMIT 8");

require_once __DIR__ . '/../includes/header.php';
?>
<div class="grid grid-4 mb">
  <div class="stat"><div class="num"><?= $total_users ?></div><div class="lbl">Total Users</div></div>
  <div class="stat"><div class="num"><?= $total_proposals ?></div><div class="lbl">Proposals</div></div>
  <div class="stat"><div class="num"><?= $total_requests ?></div><div class="lbl">Service Requests</div></div>
  <div class="stat"><div class="num"><?= $total_complaints ?></div><div class="lbl">Complaints</div></div>
</div>
<div class="grid grid-2">
  <div class="card">
    <h2>User Roles</h2>
    <table>
      <tr><th>Role</th><th>Count</th></tr>
      <?php while ($r = $roles_breakdown->fetch_assoc()): ?>
        <tr><td><?= e(ucwords(str_replace('_',' ',$r['role']))) ?></td><td><?= $r['c'] ?></td></tr>
      <?php endwhile; ?>
    </table>
  </div>
  <div class="card">
    <h2>Recent System Activity</h2>
    <?php if ($recent_audit->num_rows === 0): ?>
      <div class="empty">No activity recorded yet.</div>
    <?php else: while ($a = $recent_audit->fetch_assoc()): ?>
      <div style="padding:8px 0;border-bottom:1px solid var(--line)" class="small">
        <strong><?= e($a['full_name'] ?? 'System') ?></strong> — <?= e($a['action']) ?>
        <div class="muted"><?= time_ago($a['created_at']) ?></div>
      </div>
    <?php endwhile; endif; ?>
    <a href="/admin/audit_logs.php" class="small">View all logs →</a>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
