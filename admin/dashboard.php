<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$page_title = 'Administrator Dashboard';
$current_page = 'admin/dashboard.php';

$total_users = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
$total_proposals = $conn->query("SELECT COUNT(*) c FROM proposals")->fetch_assoc()['c'];
$total_requests = $conn->query("SELECT COUNT(*) c FROM service_requests")->fetch_assoc()['c'];
$total_complaints = $conn->query("SELECT COUNT(*) c FROM complaints")->fetch_assoc()['c'];
$active_users = $conn->query("SELECT COUNT(*) c FROM users WHERE status='active'")->fetch_assoc()['c'];
$pending_props = $conn->query("SELECT COUNT(*) c FROM proposals WHERE status IN ('pending','under_review')")->fetch_assoc()['c'];
$total_cases=(int)$total_proposals+(int)$total_requests+(int)$total_complaints;
$open_cases=(int)$pending_props+(int)$conn->query("SELECT COUNT(*) c FROM service_requests WHERE status IN ('submitted','in_progress')")->fetch_assoc()['c']+(int)$conn->query("SELECT COUNT(*) c FROM complaints WHERE status IN ('filed','investigating','mediation')")->fetch_assoc()['c'];
$resolved_cases=(int)$conn->query("SELECT COUNT(*) c FROM proposals WHERE status IN ('approved','implemented')")->fetch_assoc()['c']+(int)$conn->query("SELECT COUNT(*) c FROM service_requests WHERE status IN ('resolved','closed')")->fetch_assoc()['c']+(int)$conn->query("SELECT COUNT(*) c FROM complaints WHERE status IN ('resolved','dismissed')")->fetch_assoc()['c'];
$resolution_rate=$total_cases?round($resolved_cases/$total_cases*100):0;
$recent30=(int)$conn->query("SELECT COUNT(*) c FROM audit_logs WHERE created_at>=DATE_SUB(NOW(),INTERVAL 30 DAY)")->fetch_assoc()['c'];

$roles_breakdown = $conn->query("SELECT role, COUNT(*) c FROM users GROUP BY role");
$recent_audit = $conn->query("SELECT a.*, u.full_name FROM audit_logs a LEFT JOIN users u ON a.user_id=u.user_id ORDER BY a.created_at DESC LIMIT 8");

require_once __DIR__ . '/../includes/header.php';
?>
<div class="grid grid-4">
  <div class="stat">
    <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
    <div><div class="num"><?= (int)$total_users ?></div><div class="lbl">Total Users</div><div class="sub"><?= (int)$active_users ?> active</div></div>
  </div>
  <div class="stat">
    <div class="stat-icon"><i class="fa-solid fa-lightbulb"></i></div>
    <div><div class="num"><?= (int)$total_proposals ?></div><div class="lbl">Proposals</div><div class="sub"><?= (int)$pending_props ?> pending review</div></div>
  </div>
  <div class="stat">
    <div class="stat-icon"><i class="fa-solid fa-clipboard-list"></i></div>
    <div><div class="num"><?= (int)$total_requests ?></div><div class="lbl">Service Requests</div></div>
  </div>
  <div class="stat">
    <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <div><div class="num"><?= (int)$total_complaints ?></div><div class="lbl">Complaints</div></div>
  </div>
</div>


<div class="analytics-insights">
  <div class="insight"><div class="insight-type">Descriptive</div><strong><?= number_format($total_cases) ?> total cases</strong><p>Community activity currently includes <?= number_format($total_proposals) ?> proposals, <?= number_format($total_requests) ?> service requests, and <?= number_format($total_complaints) ?> complaints.</p></div>
  <div class="insight"><div class="insight-type">Diagnostic</div><strong><?= number_format($open_cases) ?> cases need attention</strong><p><?= $open_cases ?> cases are still in active or review states. A high open count relative to completed cases suggests workload concentration.</p></div>
  <div class="insight"><div class="insight-type">Predictive</div><strong><?= $open_cases > max(1,$resolved_cases) ? 'Backlog pressure likely' : 'Backlog currently manageable' ?></strong><p>Based on the current open-to-completed ratio, <?= $open_cases > max(1,$resolved_cases) ? 'unresolved workload may grow unless processing capacity increases.' : 'the system is completing cases at a pace that currently keeps backlog pressure contained.' ?></p></div>
</div>

<div class="grid grid-2">
  <div class="card">
    <h2><i class="fa-solid fa-users-gear"></i> User Roles</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Role</th><th>Count</th></tr></thead>
        <tbody>
        <?php while ($r = $roles_breakdown->fetch_assoc()): ?>
          <tr><td><?= e(ucwords(str_replace('_',' ',$r['role']))) ?></td><td><strong><?= (int)$r['c'] ?></strong></td></tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <h2><i class="fa-solid fa-clock-rotate-left"></i> Recent System Activity</h2>
    <?php if ($recent_audit->num_rows === 0): ?>
      <div class="empty"><i class="fa-regular fa-folder-open"></i><p>No activity recorded yet.</p></div>
    <?php else: while ($a = $recent_audit->fetch_assoc()): ?>
      <div class="activity-row">
        <div>
          <div class="title"><?= e($a['full_name'] ?? 'System') ?> — <?= e($a['action']) ?></div>
          <div class="meta" title="<?= e(format_datetime($a['created_at'])) ?>"><?= time_ago($a['created_at']) ?> · <?= e(format_datetime($a['created_at'])) ?></div>
        </div>
      </div>
    <?php endwhile; endif; ?>
    <a href="/admin/audit_logs.php" class="btn btn-sm btn-outline mt"><i class="fa-solid fa-arrow-right"></i> View all logs</a>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
