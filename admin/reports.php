<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$page_title = 'Analytics & Reports';
$current_page = 'admin/reports.php';

$proposal_status = $conn->query("SELECT status, COUNT(*) c FROM proposals GROUP BY status");
$request_status = $conn->query("SELECT status, COUNT(*) c FROM service_requests GROUP BY status");
$complaint_status = $conn->query("SELECT status, COUNT(*) c FROM complaints GROUP BY status");
$avg_rating = $conn->query("SELECT ROUND(AVG(rating),1) avg_r, COUNT(*) total FROM feedback")->fetch_assoc();
$sector_page = max(1, (int)($_GET['sector_p'] ?? 1));
$monthly_page = max(1, (int)($_GET['monthly_p'] ?? 1));
$table_per_page = 12;
$sector_total = (int)$conn->query("SELECT COUNT(*) c FROM sectors")->fetch_assoc()['c'];
$sector_pages = max(1, (int)ceil($sector_total / $table_per_page));
if ($sector_page > $sector_pages) $sector_page = $sector_pages;
$sector_offset = ($sector_page - 1) * $table_per_page;
$monthly_total = (int)$conn->query("SELECT COUNT(*) c FROM (SELECT DATE_FORMAT(created_at,'%Y-%m') ym FROM proposals GROUP BY ym) m")->fetch_assoc()['c'];
$monthly_pages = max(1, (int)ceil($monthly_total / $table_per_page));
if ($monthly_page > $monthly_pages) $monthly_page = $monthly_pages;
$monthly_offset = ($monthly_page - 1) * $table_per_page;
$sector_dist = $conn->query("SELECT s.sector_name, COUNT(cp.profile_id) c FROM sectors s LEFT JOIN citizen_profiles cp ON cp.sector_id=s.sector_id GROUP BY s.sector_id ORDER BY c DESC LIMIT $table_per_page OFFSET $sector_offset");
$monthly = $conn->query("SELECT DATE_FORMAT(created_at,'%Y-%m') ym, COUNT(*) c FROM proposals GROUP BY ym ORDER BY ym DESC LIMIT $table_per_page OFFSET $monthly_offset");

$prop_total = max(1, (int)$conn->query("SELECT COUNT(*) c FROM proposals")->fetch_assoc()['c']);
$req_total = max(1, (int)$conn->query("SELECT COUNT(*) c FROM service_requests")->fetch_assoc()['c']);
$comp_total = max(1, (int)$conn->query("SELECT COUNT(*) c FROM complaints")->fetch_assoc()['c']);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="kpi-grid">
  <div class="kpi">
    <div class="kpi-icon"><i class="fa-solid fa-star"></i></div>
    <div class="kpi-label">Avg Rating</div>
    <div class="kpi-value"><?= e($avg_rating['avg_r'] ?? '—') ?></div>
    <div class="kpi-sub"><?= (int)($avg_rating['total'] ?? 0) ?> reviews</div>
  </div>
  <div class="kpi">
    <div class="kpi-icon"><i class="fa-solid fa-diagram-project"></i></div>
    <div class="kpi-label">Sectors</div>
    <div class="kpi-value"><?= (int)$sector_total ?></div>
    <div class="kpi-sub">equity tracking</div>
  </div>
  <div class="kpi">
    <div class="kpi-icon"><i class="fa-solid fa-lightbulb"></i></div>
    <div class="kpi-label">Proposals</div>
    <div class="kpi-value"><?= $prop_total ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-icon"><i class="fa-solid fa-clipboard-list"></i></div>
    <div class="kpi-label">Requests</div>
    <div class="kpi-value"><?= $req_total ?></div>
  </div>
</div>

<div class="card chart-compare">
  <div class="flex-between">
    <div>
      <h2><i class="fa-solid fa-chart-column"></i> Case Type Comparison</h2>
      <p class="small muted">Compare total volume with completed outcomes across the three main civic case types.</p>
    </div>
  </div>
  <canvas id="adminCaseComparison" aria-label="Case type comparison chart"></canvas>
</div>

<div class="grid grid-3">
  <div class="card">
    <h2><i class="fa-solid fa-lightbulb"></i> Proposals by Status</h2>
    <?php
    $proposal_status->data_seek(0);
    while ($r = $proposal_status->fetch_assoc()):
      $pct = round(($r['c'] / $prop_total) * 100);
    ?>
      <div class="progress-row">
        <span class="lbl"><?= status_badge($r['status']) ?></span>
        <div class="bar">
          <div class="bar-fill" style="width:<?= $pct ?>%"></div>
        </div>
        <span class="val"><?= (int)$r['c'] ?></span>
      </div>
    <?php endwhile; ?>
  </div>
  <div class="card">
    <h2><i class="fa-solid fa-clipboard-list"></i> Requests by Status</h2>
    <?php while ($r = $request_status->fetch_assoc()):
      $pct = round(($r['c'] / $req_total) * 100);
    ?>
      <div class="progress-row">
        <span class="lbl"><?= status_badge($r['status']) ?></span>
        <div class="bar">
          <div class="bar-fill" style="width:<?= $pct ?>%"></div>
        </div>
        <span class="val"><?= (int)$r['c'] ?></span>
      </div>
    <?php endwhile; ?>
  </div>
  <div class="card">
    <h2><i class="fa-solid fa-triangle-exclamation"></i> Complaints by Status</h2>
    <?php while ($r = $complaint_status->fetch_assoc()):
      $pct = round(($r['c'] / $comp_total) * 100);
    ?>
      <div class="progress-row">
        <span class="lbl"><?= status_badge($r['status']) ?></span>
        <div class="bar">
          <div class="bar-fill" style="width:<?= $pct ?>%"></div>
        </div>
        <span class="val"><?= (int)$r['c'] ?></span>
      </div>
    <?php endwhile; ?>
  </div>
</div>

<div class="grid grid-2">
  <div class="card">
    <h2><i class="fa-solid fa-diagram-project"></i> Sector Distribution</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Sector</th>
            <th>Members</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($sector_total === 0): ?><tr>
              <td colspan="2">
                <div class="empty">No sector data found.</div>
              </td>
            </tr><?php else: $sector_dist->data_seek(0);
                  while ($s = $sector_dist->fetch_assoc()): ?>
              <tr>
                <td><?= e($s['sector_name']) ?></td>
                <td><strong><?= (int)$s['c'] ?></strong></td>
              </tr>
          <?php endwhile;
                endif; ?>
        </tbody>
      </table>
    </div>
    <?php render_pagination($sector_page, $sector_pages, $sector_total, $table_per_page, ['monthly_p' => $monthly_page]); ?>
  </div>
  <div class="card">
    <h2><i class="fa-solid fa-chart-line"></i> Proposal Submissions by Month</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Month</th>
            <th>Count</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($monthly_total === 0): ?>
            <tr>
              <td colspan="2">
                <div class="empty"><i class="fa-regular fa-chart-bar"></i>No data found.</div>
              </td>
            </tr>
          <?php endif; ?>
          <?php while ($m = $monthly->fetch_assoc()): ?>
            <tr>
              <td><?= date('F Y', strtotime($m['ym'] . '-01')) ?></td>
              <td><strong><?= (int)$m['c'] ?></strong></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php render_pagination($monthly_page, $monthly_pages, $monthly_total, $table_per_page, ['sector_p' => $sector_page]); ?>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
  (function() {
    var el = document.getElementById('adminCaseComparison');
    if (!el || typeof Chart === 'undefined') return;
    new Chart(el, {
      type: 'bar',
      data: {
        labels: ['Proposals', 'Service Requests', 'Complaints'],
        datasets: [{
            label: 'Total',
            data: [<?= (int)$prop_total ?>, <?= (int)$req_total ?>, <?= (int)$comp_total ?>]
          },
          {
            label: 'Completed',
            data: [<?= (int)$conn->query("SELECT COUNT(*) c FROM proposals WHERE status IN ('approved','implemented')")->fetch_assoc()['c'] ?>, <?= (int)$conn->query("SELECT COUNT(*) c FROM service_requests WHERE status IN ('resolved','closed')")->fetch_assoc()['c'] ?>, <?= (int)$conn->query("SELECT COUNT(*) c FROM complaints WHERE status IN ('resolved','dismissed')")->fetch_assoc()['c'] ?>]
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top'
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0
            }
          }
        }
      }
    });
  })();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>