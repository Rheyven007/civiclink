<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('sector_rep');
$page_title = 'Sector Issues';
$current_page = 'sector/issues.php';
$sector_filter = (int)($_GET['sector_id'] ?? 0);
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$per_page = 12;
$sectors = $conn->query("SELECT * FROM sectors ORDER BY sector_name");
$conditions = [];
if ($sector_filter) $conditions[] = "cp.sector_id=$sector_filter";
if ($search) {
  $q = $conn->real_escape_string($search);
  $conditions[] = "(c.subject LIKE '%$q%' OR u.full_name LIKE '%$q%' OR s.sector_name LIKE '%$q%' OR c.status LIKE '%$q%')";
}
$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$countSql = "SELECT COUNT(*) c FROM complaints c JOIN users u ON c.user_id=u.user_id LEFT JOIN citizen_profiles cp ON cp.user_id=u.user_id LEFT JOIN sectors s ON s.sector_id=cp.sector_id" . $where;
$total = (int)$conn->query($countSql)->fetch_assoc()['c'];
$total_pages = max(1, (int)ceil($total / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;
$sql = "SELECT c.complaint_id ref_id,'Complaint' kind,c.subject title,c.status,c.created_at,u.full_name,s.sector_name FROM complaints c JOIN users u ON c.user_id=u.user_id LEFT JOIN citizen_profiles cp ON cp.user_id=u.user_id LEFT JOIN sectors s ON s.sector_id=cp.sector_id" . $where . " ORDER BY c.created_at DESC LIMIT $per_page OFFSET $offset";
$issues = $conn->query($sql);
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card mb">
  <form method="get" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap">
    <div class="field" style="max-width:280px;margin-bottom:0"><label><i class="fa-solid fa-filter"></i> Filter by Sector</label><select name="sector_id" onchange="this.form.submit()">
        <option value="0">All Sectors</option><?php while ($s = $sectors->fetch_assoc()): ?><option value="<?= $s['sector_id'] ?>" <?= $sector_filter == $s['sector_id'] ? 'selected' : '' ?>><?= e($s['sector_name']) ?></option><?php endwhile; ?>
      </select></div>
  </form>
</div>
<div class="card ajax-table-container" data-ajax-endpoint="1">
  <div class="data-toolbar">
    <h2 style="margin:0"><i class="fa-solid fa-triangle-exclamation"></i> Reported Issues (Complaints) <span class="small muted" data-result-count>(<?= number_format($total) ?>)</span></h2>
    <div class="data-search"><i class="fa-solid fa-magnifying-glass"></i><input data-ajax-search type="search" value="<?= e($search) ?>" placeholder="Search subject, citizen, sector..."></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Subject</th>
          <th>Filed By</th>
          <th>Sector</th>
          <th>Status</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($issues->num_rows === 0): ?><tr>
            <td colspan="5" class="empty"><i class="fa-regular fa-folder-open"></i>No issues found for this filter.</td>
          </tr><?php else: while ($i = $issues->fetch_assoc()): ?><tr>
              <td><?= e($i['title']) ?></td>
              <td><?= e($i['full_name']) ?></td>
              <td><span class="pill"><?= e($i['sector_name'] ?: 'Unspecified') ?></span></td>
              <td><?= status_badge($i['status']) ?></td>
              <td class="small muted"><?= time_ago($i['created_at']) ?></td>
            </tr><?php endwhile;
              endif; ?>
      </tbody>
    </table>
  </div>
  <?php render_pagination($page, $total_pages, $total, $per_page, ['sector_id' => $sector_filter, 'q' => $search]); ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>