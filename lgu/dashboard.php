<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('lgu_officer');
$page_title = 'LGU Officer Dashboard';
$current_page = 'lgu/dashboard.php';

$pending_proposals = $conn->query("SELECT COUNT(*) c FROM proposals WHERE status IN ('pending','under_review')")->fetch_assoc()['c'];
$pending_requests = $conn->query("SELECT COUNT(*) c FROM service_requests WHERE status IN ('submitted','in_progress')")->fetch_assoc()['c'];
$pending_complaints = $conn->query("SELECT COUNT(*) c FROM complaints WHERE status IN ('filed','investigating','mediation')")->fetch_assoc()['c'];
$resolved_month = $conn->query("SELECT COUNT(*) c FROM service_requests WHERE status='resolved' AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['c'];
$total_cases = (int)$conn->query("SELECT COUNT(*) c FROM proposals")->fetch_assoc()['c'] + (int)$conn->query("SELECT COUNT(*) c FROM service_requests")->fetch_assoc()['c'] + (int)$conn->query("SELECT COUNT(*) c FROM complaints")->fetch_assoc()['c'];
$open_total = (int)$pending_proposals + (int)$pending_requests + (int)$pending_complaints;
$avg_open_days = (float)($conn->query("SELECT COALESCE(AVG(DATEDIFF(NOW(),created_at)),0) d FROM (SELECT created_at FROM proposals WHERE status IN ('pending','under_review') UNION ALL SELECT created_at FROM service_requests WHERE status IN ('submitted','in_progress') UNION ALL SELECT created_at FROM complaints WHERE status IN ('filed','investigating','mediation')) x")->fetch_assoc()['d'] ?? 0);
$resolved30 = (int)$resolved_month + (int)$conn->query("SELECT COUNT(*) c FROM complaints WHERE status IN ('resolved','dismissed') AND updated_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)")->fetch_assoc()['c'];

// Recent cases for quick view
$recent = $conn->query("
  (SELECT proposal_id AS id, title AS label, status, created_at, 'proposal' AS kind FROM proposals ORDER BY created_at DESC LIMIT 4)
  UNION ALL
  (SELECT request_id, service_type, status, created_at, 'service_request' FROM service_requests ORDER BY created_at DESC LIMIT 4)
  UNION ALL
  (SELECT complaint_id, subject, status, created_at, 'complaint' FROM complaints ORDER BY created_at DESC LIMIT 4)
  ORDER BY created_at DESC LIMIT 8
");

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card chart-compare">
  <h2><i class="fa-solid fa-chart-column"></i> Case Workload Comparison</h2>
  <p class="small muted">Open workload compared with completed outcomes by case type.</p><canvas id="lguComparison" aria-label="LGU case workload comparison chart"></canvas>
</div>
<div class="grid grid-4">
  <a href="/lgu/cases.php?type=proposal" class="stat stat-link">
    <div class="stat-icon"><i class="fa-solid fa-lightbulb"></i></div>
    <div>
      <div class="num"><?= (int)$pending_proposals ?></div>
      <div class="lbl">Pending Proposals</div>
    </div>
  </a>
  <a href="/lgu/cases.php?type=service_request" class="stat stat-link">
    <div class="stat-icon"><i class="fa-solid fa-clipboard-list"></i></div>
    <div>
      <div class="num"><?= (int)$pending_requests ?></div>
      <div class="lbl">Open Service Requests</div>
    </div>
  </a>
  <a href="/lgu/cases.php?type=complaint" class="stat stat-link">
    <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <div>
      <div class="num"><?= (int)$pending_complaints ?></div>
      <div class="lbl">Active Complaints</div>
    </div>
  </a>
  <div class="stat">
    <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
    <div>
      <div class="num"><?= (int)$resolved_month ?></div>
      <div class="lbl">Resolved (30 days)</div>
    </div>
  </div>
</div>


<div class="analytics-insights">
  <div class="insight">
    <div class="insight-type">Descriptive</div><strong><?= number_format($open_total) ?> active cases</strong>
    <p>There are <?= number_format($pending_proposals) ?> proposals, <?= number_format($pending_requests) ?> service requests, and <?= number_format($pending_complaints) ?> complaints currently requiring attention.</p>
  </div>
  <div class="insight">
    <div class="insight-type">Diagnostic</div><strong>Average open age: <?= number_format($avg_open_days, 1) ?> days</strong>
    <p>Older open cases should be reviewed first, especially where the same status is accumulating.</p>
  </div>
  <div class="insight">
    <div class="insight-type">Predictive</div><strong><?= $open_total > max(1, $resolved30) ? 'Backlog may increase' : 'Backlog trend is controlled' ?></strong>
    <p>Current workload versus recent closures indicates <?= $open_total > max(1, $resolved30) ? 'a risk of additional backlog if new submissions continue at the same pace.' : 'recent closures are keeping pace with the visible workload.' ?></p>
  </div>
</div>

<?php
$total_open = (int)$pending_proposals + (int)$pending_requests + (int)$pending_complaints;
if ($total_open > 0): ?>
  <div class="attention">
    <div>
      <strong><i class="fa-solid fa-circle-exclamation"></i> Case workload</strong>
      <div class="muted"><?= $total_open ?> open case<?= $total_open > 1 ? 's' : '' ?> awaiting action. Review and record justified decisions.</div>
    </div>
    <a href="/lgu/cases.php?type=proposal" class="btn btn-sm">Manage Cases</a>
  </div>
<?php endif; ?>

<div class="grid grid-3 mt">
  <div class="card">
    <h2><i class="fa-solid fa-lightbulb"></i> Community Proposals</h2>
    <p class="small muted mb">Review and decide on citizen-submitted projects. Every decision requires a written justification.</p>
    <a href="/lgu/cases.php?type=proposal" class="btn btn-block">Manage Proposals</a>
  </div>
  <div class="card">
    <h2><i class="fa-solid fa-clipboard-list"></i> Service Requests</h2>
    <p class="small muted mb">Track and update non-emergency public service requests.</p>
    <a href="/lgu/cases.php?type=service_request" class="btn btn-block">Manage Requests</a>
  </div>
  <div class="card">
    <h2><i class="fa-solid fa-triangle-exclamation"></i> Complaints & Disputes</h2>
    <p class="small muted mb">Investigate and resolve citizen concerns with transparency.</p>
    <a href="/lgu/cases.php?type=complaint" class="btn btn-block">Manage Complaints</a>
  </div>
</div>

<div class="card mt">
  <h2><i class="fa-solid fa-clock-rotate-left"></i> Recent Cases</h2>
  <?php if (!$recent || $recent->num_rows === 0): ?>
    <div class="empty"><i class="fa-regular fa-folder-open"></i>
      <p>No cases yet.</p>
    </div>
    <?php else: while ($row = $recent->fetch_assoc()):
      $kind = $row['kind'];
      $ref = ref_number($kind === 'service_request' ? 'request' : $kind, $row['id'], $row['created_at']);
      $link = '/lgu/cases.php?type=' . urlencode($kind);
    ?>
      <div class="activity-row">
        <div>
          <div class="title"><code class="ref-code"><?= e($ref) ?></code> <?= e($row['label']) ?></div>
          <div class="meta"><?= e(ucwords(str_replace('_', ' ', $kind))) ?> · <?= time_ago($row['created_at']) ?></div>
        </div>
        <?= status_badge($row['status']) ?>
      </div>
  <?php endwhile;
  endif; ?>
  <a href="/lgu/cases.php?type=proposal" class="btn btn-sm btn-outline mt"><i class="fa-solid fa-arrow-right"></i> Open case management</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
  (function() {
    var e = document.getElementById('lguComparison');
    if (!e || typeof Chart === 'undefined') return;
    new Chart(e, {
      type: 'bar',
      data: {
        labels: ['Proposals', 'Service Requests', 'Complaints'],
        datasets: [{
          label: 'Open',
          data: [<?= (int)$conn->query("SELECT COUNT(*) c FROM proposals WHERE status IN ('pending','under_review')")->fetch_assoc()['c'] ?>, <?= (int)$conn->query("SELECT COUNT(*) c FROM service_requests WHERE status IN ('submitted','in_progress')")->fetch_assoc()['c'] ?>, <?= (int)$conn->query("SELECT COUNT(*) c FROM complaints WHERE status IN ('filed','investigating','mediation')")->fetch_assoc()['c'] ?>]
        }, {
          label: 'Completed',
          data: [<?= (int)$conn->query("SELECT COUNT(*) c FROM proposals WHERE status IN ('approved','implemented')")->fetch_assoc()['c'] ?>, <?= (int)$conn->query("SELECT COUNT(*) c FROM service_requests WHERE status IN ('resolved','closed')")->fetch_assoc()['c'] ?>, <?= (int)$conn->query("SELECT COUNT(*) c FROM complaints WHERE status IN ('resolved','dismissed')")->fetch_assoc()['c'] ?>]
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