<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('lgu_officer');
$uid = current_user_id();
$type = $_GET['type'] ?? 'proposal';
if (!in_array($type, ['proposal','service_request','complaint'])) $type = 'proposal';
$page_title = 'Case Management';
$current_page = "lgu/cases.php?type=$type";

$labels = ['proposal' => 'Community Proposals', 'service_request' => 'Service Requests', 'complaint' => 'Complaints & Disputes'];
$tables = ['proposal' => 'proposals', 'service_request' => 'service_requests', 'complaint' => 'complaints'];
$id_cols = ['proposal' => 'proposal_id', 'service_request' => 'request_id', 'complaint' => 'complaint_id'];
$title_cols = ['proposal' => 'title', 'service_request' => 'service_type', 'complaint' => 'subject'];
$table = $tables[$type];
$id_col = $id_cols[$type];
$title_col = $title_cols[$type];

// Handle status update / decision submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ref_id = (int)$_POST['ref_id'];
    $new_status = $_POST['new_status'];
    $justification = trim($_POST['justification'] ?? '');

    $valid_statuses = [
        'proposal' => ['pending','under_review','approved','rejected','implemented'],
        'service_request' => ['submitted','in_progress','resolved','closed','cancelled'],
        'complaint' => ['filed','investigating','mediation','resolved','dismissed']
    ];
    if (in_array($new_status, $valid_statuses[$type]) && strlen($justification) >= 5) {
        $stmt = $conn->prepare("UPDATE $table SET status=? WHERE $id_col=?");
        $stmt->bind_param('si', $new_status, $ref_id);
        $stmt->execute();

        $decision_map = ['approved'=>'approved','rejected'=>'rejected','resolved'=>'closed','dismissed'=>'rejected','implemented'=>'approved','closed'=>'closed'];
        $decision = $decision_map[$new_status] ?? 'escalated';
        $stmt = $conn->prepare("INSERT INTO decision_logs (reference_type, reference_id, decided_by, decision, justification) VALUES (?,?,?,?,?)");
        $stmt->bind_param('siiss', $type, $ref_id, $uid, $decision, $justification);
        $stmt->execute();

        // notify the submitter
        $owner_col = 'user_id';
        $stmt = $conn->prepare("SELECT $owner_col uid, $title_col t FROM $table WHERE $id_col=?");
        $stmt->bind_param('i', $ref_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            $msg = "Your " . str_replace('_',' ',$type) . " \"{$row['t']}\" status changed to " . ucwords(str_replace('_',' ',$new_status));
            notify_user($conn, $row['uid'], $msg);
        }
        log_audit($conn, $uid, 'Decision Recorded', "$type #$ref_id -> $new_status");
        header("Location: /lgu/cases.php?type=$type&updated=1");
        exit;
    }
}

$status_filter = $_GET['status'] ?? '';
$sql = "SELECT t.*, u.full_name FROM $table t JOIN users u ON t.user_id = u.user_id";
if ($status_filter) $sql .= " WHERE t.status = '" . $conn->real_escape_string($status_filter) . "'";
$sql .= " ORDER BY t.created_at DESC LIMIT 40";
$cases = $conn->query($sql);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="tabs">
  <a href="?type=proposal" class="<?= $type==='proposal'?'active':'' ?>">Proposals</a>
  <a href="?type=service_request" class="<?= $type==='service_request'?'active':'' ?>">Service Requests</a>
  <a href="?type=complaint" class="<?= $type==='complaint'?'active':'' ?>">Complaints</a>
</div>
<?php if (isset($_GET['updated'])): ?><div class="alert alert-ok">Decision recorded and logged for transparency.</div><?php endif; ?>

<div class="card">
  <h2><?= $labels[$type] ?></h2>
  <table>
    <tr><th>Title</th><th>Submitted By</th><th>Status</th><th>Date</th><th>Action</th></tr>
    <?php if ($cases->num_rows === 0): ?>
      <tr><td colspan="5" class="empty">No cases found.</td></tr>
    <?php else: while ($c = $cases->fetch_assoc()): ?>
      <tr>
        <td><?= e($c[$title_col]) ?></td>
        <td><?= e($c['full_name']) ?></td>
        <td><?= status_badge($c['status']) ?></td>
        <td class="small muted"><?= time_ago($c['created_at']) ?></td>
        <td><a href="#case-<?= $c[$id_col] ?>" class="small">Decide ↓</a></td>
      </tr>
    <?php endwhile; endif; ?>
  </table>
</div>

<?php
$cases->data_seek(0);
$valid_statuses = [
    'proposal' => ['pending','under_review','approved','rejected','implemented'],
    'service_request' => ['submitted','in_progress','resolved','closed','cancelled'],
    'complaint' => ['filed','investigating','mediation','resolved','dismissed']
];
while ($c = $cases->fetch_assoc()):
?>
<div class="card" id="case-<?= $c[$id_col] ?>">
  <div class="flex-between">
    <h2><?= e($c[$title_col]) ?></h2>
    <?= status_badge($c['status']) ?>
  </div>
  <p class="small mb"><?= nl2br(e($c['description'] ?? $c['details'] ?? '')) ?></p>
  <div class="small muted mb">Submitted by <?= e($c['full_name']) ?> &middot; <?= time_ago($c['created_at']) ?></div>
  <form method="post">
    <input type="hidden" name="ref_id" value="<?= $c[$id_col] ?>">
    <div class="form-row">
      <div class="field">
        <label>Update Status</label>
        <select name="new_status" required>
          <?php foreach ($valid_statuses[$type] as $s): ?>
            <option value="<?= $s ?>" <?= $c['status']===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field">
      <label>Justification (required for transparency log) *</label>
      <textarea name="justification" required placeholder="Explain the reasoning for this decision..."></textarea>
    </div>
    <button type="submit" class="btn btn-sm">Record Decision</button>
  </form>
</div>
<?php endwhile; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
