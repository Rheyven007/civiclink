<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('lgu_officer');
$page_title = 'SLA & Performance';
$current_page = 'lgu/sla.php';

$avg_resolution = $conn->query("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) h FROM service_requests WHERE status='resolved'")->fetch_assoc()['h'];
$total = $conn->query("SELECT COUNT(*) c FROM service_requests")->fetch_assoc()['c'];
$resolved = $conn->query("SELECT COUNT(*) c FROM service_requests WHERE status='resolved'")->fetch_assoc()['c'];
$overdue = $conn->query("SELECT COUNT(*) c FROM service_requests WHERE status IN ('submitted','in_progress') AND created_at < DATE_SUB(NOW(), INTERVAL 72 HOUR)")->fetch_assoc()['c'];
$resolution_rate = $total > 0 ? round(($resolved / $total) * 100) : 0;
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$per_page = 12;
$where = '';
if ($search) {
  $q = $conn->real_escape_string($search);
  $where = " WHERE department LIKE '%$q%'";
}
$total_dept = (int)$conn->query("SELECT COUNT(*) c FROM (SELECT department FROM service_requests GROUP BY department) d")->fetch_assoc()['c'];
if ($search) $total_dept = (int)$conn->query("SELECT COUNT(*) c FROM (SELECT department FROM service_requests WHERE department LIKE '%$q%' GROUP BY department) d")->fetch_assoc()['c'];
$total_pages = max(1, (int)ceil($total_dept / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;
$by_dept = $conn->query("SELECT department, COUNT(*) total,SUM(CASE WHEN status='resolved' THEN 1 ELSE 0 END) resolved FROM service_requests" . $where . " GROUP BY department ORDER BY total DESC LIMIT $per_page OFFSET $offset");
$overdue_pct = $total ? round($overdue / $total * 100) : 0;

require_once __DIR__ . '/../includes/header.php';
?>
<div class="grid grid-4 mb">
  <div class="stat">
    <div class="num"><?= $avg_resolution ? round($avg_resolution) . 'h' : '—' ?></div>
    <div class="lbl">Avg. Resolution Time</div>
  </div>
  <div class="stat">
    <div class="num"><?= $resolution_rate ?>%</div>
    <div class="lbl">Resolution Rate</div>
  </div>
  <div class="stat">
    <div class="num"><?= $overdue ?></div>
    <div class="lbl">Overdue (&gt;72h)</div>
  </div>
  <div class="stat">
    <div class="num"><?= $total ?></div>
    <div class="lbl">Total Requests</div>
  </div>
</div>
<div class="analytics-insights">
  <div class="insight">
    <div class="insight-type">Descriptive</div><strong><?= $resolution_rate ?>% resolution rate</strong>
    <p><?= $resolved ?> of <?= $total ?> service requests are currently marked resolved.</p>
  </div>
  <div class="insight">
    <div class="insight-type">Diagnostic</div><strong><?= $overdue ?> overdue requests</strong>
    <p><?= $overdue_pct ?>% of all requests are older than 72 hours while still open, indicating where follow-up is most urgent.</p>
  </div>
  <div class="insight">
    <div class="insight-type">Predictive</div><strong><?= $overdue > 0 ? 'Delay risk is present' : 'No overdue backlog detected' ?></strong>
    <p><?= $overdue > 0 ? 'If overdue cases continue accumulating, average resolution time is likely to increase.' : 'Current aging data shows no open requests beyond the 72-hour threshold.' ?></p>
  </div>
</div>
<div class="card chart-compare">
  <h2><i class="fa-solid fa-chart-column"></i> Department Performance Comparison</h2>
  <p class="small muted">Compare total service requests with resolved requests by department.</p><canvas id="slaComparison" aria-label="Department performance comparison chart"></canvas>
</div>
<div class="card ajax-table-container" data-ajax-endpoint="1">
  <div class="data-toolbar">
    <h2 style="margin:0"><i class="fa-solid fa-chart-line"></i> Performance by Department</h2>
    <div class="data-search"><i class="fa-solid fa-magnifying-glass"></i><input data-ajax-search type="search" value="<?= e($search) ?>" placeholder="Search department..."></div>
  </div>
  <div class="table-wrap">
    <table>
      <tr>
        <th>Department</th>
        <th>Total</th>
        <th>Resolved</th>
        <th>Rate</th>
      </tr>
      <?php if ($by_dept->num_rows === 0): ?><tr>
          <td colspan="4">
            <div class="empty">No service performance data found.</div>
          </td>
        </tr><?php else: while ($d = $by_dept->fetch_assoc()):
                $rate = $d['total'] > 0 ? round(($d['resolved'] / $d['total']) * 100) : 0; ?>
          <tr>
            <td><?= e($d['department'] ?: 'Unassigned') ?></td>
            <td><?= $d['total'] ?></td>
            <td><?= $d['resolved'] ?></td>
            <td>
              <div class="bar" style="width:100px;display:inline-block;vertical-align:middle">
                <div class="bar-fill" style="width:<?= $rate ?>%"></div>
              </div>
              <span class="small muted"><?= $rate ?>%</span>
            </td>
          </tr>
      <?php endwhile;
            endif; ?>
    </table>
  </div>
  <?php render_pagination($page, $total_pages, $total_dept, $per_page, ['q' => $search]); ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
  (function() {
    var e = document.getElementById('slaComparison');
    if (!e || typeof Chart === 'undefined') return;
    new Chart(e, {
      type: 'bar',
      data: {
        labels: [<?php $by_dept->data_seek(0);
                  $chartLabels = [];
                  $chartTotals = [];
                  $chartResolved = [];
                  while ($d = $by_dept->fetch_assoc()) {
                    $chartLabels[] = json_encode($d['department'] ?: 'Unassigned');
                    $chartTotals[] = (int)$d['total'];
                    $chartResolved[] = (int)$d['resolved'];
                  }
                  echo implode(',', $chartLabels); ?>],
        datasets: [{
          label: 'Total',
          data: [<?= implode(',', $chartTotals) ?>]
        }, {
          label: 'Resolved',
          data: [<?= implode(',', $chartResolved) ?>]
        }]
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