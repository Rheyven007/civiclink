<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['citizen','sector_rep']);
$uid = current_user_id();
$page_title = 'Citizen Dashboard';
$current_page = 'citizen/dashboard.php';

function count_q($conn, $sql, $uid) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['c'];
}
$proposals_c = count_q($conn, "SELECT COUNT(*) c FROM proposals WHERE user_id=?", $uid);
$requests_c = count_q($conn, "SELECT COUNT(*) c FROM service_requests WHERE user_id=?", $uid);
$complaints_c = count_q($conn, "SELECT COUNT(*) c FROM complaints WHERE user_id=?", $uid);
$pending_c = count_q($conn, "SELECT COUNT(*) c FROM service_requests WHERE user_id=? AND status IN ('submitted','in_progress')", $uid);
$resolved_personal=count_q($conn, "SELECT COUNT(*) c FROM service_requests WHERE user_id=? AND status IN ('resolved','closed')", $uid)+count_q($conn, "SELECT COUNT(*) c FROM complaints WHERE user_id=? AND status IN ('resolved','dismissed')", $uid);
$active_personal=$pending_c+count_q($conn, "SELECT COUNT(*) c FROM complaints WHERE user_id=? AND status IN ('filed','investigating','mediation')", $uid);
$personal_total=(int)$proposals_c+(int)$requests_c+(int)$complaints_c; $completion_rate=$personal_total?round($resolved_personal/$personal_total*100):0;
$resolved_prop=count_q($conn, "SELECT COUNT(*) c FROM proposals WHERE user_id=? AND status IN ('approved','implemented')", $uid); $resolved_req=count_q($conn, "SELECT COUNT(*) c FROM service_requests WHERE user_id=? AND status IN ('resolved','closed')", $uid); $resolved_comp=count_q($conn, "SELECT COUNT(*) c FROM complaints WHERE user_id=? AND status IN ('resolved','dismissed')", $uid);

// Open consultations count
$open_consult = 0;
$cres = $conn->query("SELECT COUNT(*) c FROM consultations WHERE status='open'");
if ($cres) $open_consult = (int)$cres->fetch_assoc()['c'];

require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($pending_c > 0 || $open_consult > 0): ?>
<div class="attention">
  <div>
    <strong><i class="fa-solid fa-circle-exclamation"></i> Needs your attention</strong>
    <div class="muted">
      <?php if ($pending_c > 0): ?><?= (int)$pending_c ?> open service request<?= $pending_c > 1 ? 's' : '' ?><?php endif; ?>
      <?php if ($pending_c > 0 && $open_consult > 0): ?> · <?php endif; ?>
      <?php if ($open_consult > 0): ?><?= (int)$open_consult ?> public consultation<?= $open_consult > 1 ? 's' : '' ?> open<?php endif; ?>
    </div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <?php if ($pending_c > 0): ?><a href="/citizen/requests.php" class="btn btn-sm">View requests</a><?php endif; ?>
    <?php if ($open_consult > 0): ?><a href="/citizen/consultations.php" class="btn btn-outline btn-sm">Join consultation</a><?php endif; ?>
  </div>
</div>
<?php endif; ?>


<div class="analytics-insights">
 <div class="insight"><div class="insight-type">Descriptive</div><strong><?= number_format($personal_total) ?> submissions</strong><p>Your activity includes <?= $proposals_c ?> proposals, <?= $requests_c ?> service requests, and <?= $complaints_c ?> complaints.</p></div>
 <div class="insight"><div class="insight-type">Diagnostic</div><strong><?= number_format($active_personal) ?> active items</strong><p>These items are still moving through review or resolution. Checking their status regularly helps identify delays.</p></div>
 <div class="insight"><div class="insight-type">Predictive</div><strong><?= $active_personal ? 'Follow-up may be useful' : 'No active follow-up needed' ?></strong><p><?= $active_personal ? 'If an item remains unchanged for a long period, consider checking its details or contacting the appropriate LGU channel.' : 'Your submitted items currently have no tracked active workload.' ?></p></div>
</div>

<div class="card chart-compare">
  <h2><i class="fa-solid fa-chart-column"></i> My Submission Outcomes</h2>
  <p class="small muted">Compare submitted items with those already resolved or completed.</p>
  <canvas id="citizenComparison" aria-label="Personal submission outcome comparison chart"></canvas>
</div>

<div class="grid grid-4">
  <a href="/citizen/proposals.php?filter=mine" class="stat stat-link">
    <div class="stat-icon"><i class="fa-solid fa-lightbulb"></i></div>
    <div><div class="num"><?= $proposals_c ?></div><div class="lbl">My Proposals</div></div>
  </a>
  <a href="/citizen/requests.php" class="stat stat-link">
    <div class="stat-icon"><i class="fa-solid fa-clipboard-list"></i></div>
    <div><div class="num"><?= $requests_c ?></div><div class="lbl">Service Requests</div></div>
  </a>
  <a href="/citizen/complaints.php" class="stat stat-link">
    <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <div><div class="num"><?= $complaints_c ?></div><div class="lbl">Complaints Filed</div></div>
  </a>
  <a href="/citizen/requests.php" class="stat stat-link">
    <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
    <div><div class="num"><?= $pending_c ?></div><div class="lbl">Pending Items</div></div>
  </a>
</div>

<div class="grid grid-2 mt">
  <div class="card">
    <h2><i class="fa-solid fa-bolt"></i> Quick Actions</h2>
    <div class="action-stack">
      <a href="/citizen/proposal_new.php" class="btn open-form-modal"><i class="fa-solid fa-plus"></i> Propose a Community Project</a>
      <a href="/citizen/request_new.php" class="btn btn-outline open-form-modal"><i class="fa-solid fa-plus"></i> Request a Public Service</a>
      <a href="/citizen/complaint_new.php" class="btn btn-outline open-form-modal"><i class="fa-solid fa-plus"></i> File a Complaint / Dispute</a>
      <a href="/citizen/consultations.php" class="btn btn-outline"><i class="fa-solid fa-people-arrows"></i> Join a Public Consultation</a>
    </div>
  </div>
  <div class="card">
    <h2><i class="fa-solid fa-clock-rotate-left"></i> Recent Activity</h2>
    <?php
    $stmt = $conn->prepare("SELECT title, status, created_at, 'Proposal' AS kind FROM proposals WHERE user_id=?
        UNION ALL SELECT service_type, status, created_at, 'Service Request' FROM service_requests WHERE user_id=?
        UNION ALL SELECT subject, status, created_at, 'Complaint' FROM complaints WHERE user_id=?
        ORDER BY created_at DESC LIMIT 6");
    $stmt->bind_param('iii', $uid, $uid, $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0): ?>
      <div class="empty">
        <i class="fa-regular fa-folder-open"></i>
        <p>No activity yet. Start by submitting a proposal or service request — your voice shapes local decisions.</p>
        <a href="/citizen/proposal_new.php" class="btn btn-sm"><i class="fa-solid fa-plus"></i> Submit your first proposal</a>
      </div>
    <?php else: while ($row = $res->fetch_assoc()): ?>
      <div class="activity-row">
        <div>
          <div class="title"><?= e($row['title']) ?></div>
          <div class="meta" title="<?= e(format_datetime($row['created_at'])) ?>"><?= e($row['kind']) ?> · <?= time_ago($row['created_at']) ?> · <?= e(format_datetime($row['created_at'])) ?></div>
        </div>
        <?= status_badge($row['status']) ?>
      </div>
    <?php endwhile; endif; ?>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script><script>(function(){var e=document.getElementById('citizenComparison');if(!e||typeof Chart==='undefined')return;new Chart(e,{type:'bar',data:{labels:['Proposals','Service Requests','Complaints'],datasets:[{label:'Submitted',data:[<?= (int)$proposals_c ?>,<?= (int)$requests_c ?>,<?= (int)$complaints_c ?>]},{label:'Resolved',data:[<?= (int)$resolved_prop ?>,<?= (int)$resolved_req ?>,<?= (int)$resolved_comp ?>]}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top'}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});})();</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
