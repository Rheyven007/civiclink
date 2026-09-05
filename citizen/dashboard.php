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

require_once __DIR__ . '/../includes/header.php';
?>
<div class="grid grid-4">
  <div class="stat"><div class="stat-icon"><i class="fa-solid fa-lightbulb"></i></div><div><div class="num"><?= $proposals_c ?></div><div class="lbl">My Proposals</div></div></div>
  <div class="stat"><div class="stat-icon"><i class="fa-solid fa-clipboard-list"></i></div><div><div class="num"><?= $requests_c ?></div><div class="lbl">Service Requests</div></div></div>
  <div class="stat"><div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div><div><div class="num"><?= $complaints_c ?></div><div class="lbl">Complaints Filed</div></div></div>
  <div class="stat"><div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div><div><div class="num"><?= $pending_c ?></div><div class="lbl">Pending Items</div></div></div>
</div>

<div class="grid grid-2 mt">
  <div class="card">
    <h2><i class="fa-solid fa-bolt"></i> Quick Actions</h2>
    <div style="display:flex;flex-direction:column;gap:10px">
      <a href="/citizen/proposal_new.php" class="btn"><i class="fa-solid fa-plus"></i> Propose a Community Project</a>
      <a href="/citizen/request_new.php" class="btn btn-outline"><i class="fa-solid fa-plus"></i> Request a Public Service</a>
      <a href="/citizen/complaint_new.php" class="btn btn-outline"><i class="fa-solid fa-plus"></i> File a Complaint / Dispute</a>
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
      <div class="empty"><i class="fa-regular fa-folder-open"></i>No activity yet. Start by submitting a proposal or request.</div>
    <?php else: while ($row = $res->fetch_assoc()): ?>
      <div class="flex-between" style="padding:9px 0;border-bottom:1px solid var(--line)">
        <div>
          <div style="font-weight:600;font-size:13.5px"><?= e($row['title']) ?></div>
          <div class="small muted"><?= e($row['kind']) ?> &middot; <?= time_ago($row['created_at']) ?></div>
        </div>
        <?= status_badge($row['status']) ?>
      </div>
    <?php endwhile; endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
