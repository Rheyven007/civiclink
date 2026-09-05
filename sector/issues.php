<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('sector_rep');
$page_title = 'Sector Issues';
$current_page = 'sector/issues.php';

$sector_filter = (int)($_GET['sector_id'] ?? 0);
$sectors = $conn->query("SELECT * FROM sectors ORDER BY sector_name");

$sql = "SELECT c.complaint_id ref_id, 'Complaint' kind, c.subject title, c.status, c.created_at, u.full_name, s.sector_name
        FROM complaints c JOIN users u ON c.user_id=u.user_id
        LEFT JOIN citizen_profiles cp ON cp.user_id=u.user_id
        LEFT JOIN sectors s ON s.sector_id=cp.sector_id";
if ($sector_filter) $sql .= " WHERE cp.sector_id = $sector_filter";
$sql .= " ORDER BY c.created_at DESC LIMIT 30";
$issues = $conn->query($sql);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card mb">
  <form method="get" style="display:flex;gap:10px;align-items:end">
    <div class="field" style="max-width:280px;margin-bottom:0">
      <label>Filter by Sector</label>
      <select name="sector_id" onchange="this.form.submit()">
        <option value="0">All Sectors</option>
        <?php while ($s = $sectors->fetch_assoc()): ?>
          <option value="<?= $s['sector_id'] ?>" <?= $sector_filter==$s['sector_id']?'selected':'' ?>><?= e($s['sector_name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
  </form>
</div>
<div class="card">
  <h2>Reported Issues (Complaints)</h2>
  <table>
    <tr><th>Subject</th><th>Filed By</th><th>Sector</th><th>Status</th><th>Date</th></tr>
    <?php if ($issues->num_rows === 0): ?>
      <tr><td colspan="5" class="empty">No issues found for this filter.</td></tr>
    <?php else: while ($i = $issues->fetch_assoc()): ?>
      <tr>
        <td><?= e($i['title']) ?></td>
        <td><?= e($i['full_name']) ?></td>
        <td><span class="pill"><?= e($i['sector_name'] ?: 'Unspecified') ?></span></td>
        <td><?= status_badge($i['status']) ?></td>
        <td class="small muted"><?= time_ago($i['created_at']) ?></td>
      </tr>
    <?php endwhile; endif; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
