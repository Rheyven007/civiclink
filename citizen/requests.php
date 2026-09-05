<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['citizen','sector_rep']);
$uid = current_user_id();
$page_title = 'Service Requests';
$current_page = 'citizen/requests.php';

$stmt = $conn->prepare("SELECT * FROM service_requests WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param('i', $uid);
$stmt->execute();
$requests = $stmt->get_result();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="flex-between mb">
  <p class="muted"><i class="fa-solid fa-clipboard-list"></i> Track your non-emergency public service requests.</p>
  <a href="/citizen/request_new.php" class="btn"><i class="fa-solid fa-plus"></i> New Request</a>
</div>
<div class="card">
<table>
<tr><th>Service Type</th><th>Department</th><th>Priority</th><th>Status</th><th>Submitted</th></tr>
<?php if ($requests->num_rows === 0): ?>
  <tr><td colspan="5" class="empty"><i class="fa-regular fa-folder-open"></i>No service requests yet.</td></tr>
<?php else: while ($r = $requests->fetch_assoc()): ?>
  <tr>
    <td><?= e($r['service_type']) ?></td>
    <td><?= e($r['department'] ?: '—') ?></td>
    <td><?= e(ucfirst($r['priority'])) ?></td>
    <td><?= status_badge($r['status']) ?></td>
    <td class="small muted"><?= time_ago($r['created_at']) ?></td>
  </tr>
<?php endwhile; endif; ?>
</table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
