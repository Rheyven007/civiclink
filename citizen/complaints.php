<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['citizen','sector_rep']);
$uid = current_user_id();
$page_title = 'Complaints & Disputes';
$current_page = 'citizen/complaints.php';

$stmt = $conn->prepare("SELECT * FROM complaints WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param('i', $uid);
$stmt->execute();
$complaints = $stmt->get_result();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="flex-between mb">
  <p class="muted"><i class="fa-solid fa-triangle-exclamation"></i> File and track complaints or disputes with evidence.</p>
  <a href="/citizen/complaint_new.php" class="btn"><i class="fa-solid fa-plus"></i> File Complaint</a>
</div>
<div class="card">
<table>
<tr><th>Subject</th><th>Category</th><th>Status</th><th>Filed</th></tr>
<?php if ($complaints->num_rows === 0): ?>
  <tr><td colspan="4" class="empty"><i class="fa-regular fa-folder-open"></i>No complaints filed yet.</td></tr>
<?php else: while ($c = $complaints->fetch_assoc()): ?>
  <tr>
    <td><?= e($c['subject']) ?></td>
    <td><?= e(ucfirst($c['category'])) ?></td>
    <td><?= status_badge($c['status']) ?></td>
    <td class="small muted"><?= time_ago($c['created_at']) ?></td>
  </tr>
  <?php if ($c['resolution_notes']): ?>
  <tr><td colspan="4" class="small muted" style="padding-top:0;padding-bottom:14px">Resolution: <?= e($c['resolution_notes']) ?></td></tr>
  <?php endif; ?>
<?php endwhile; endif; ?>
</table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
