<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$uid = current_user_id();
$page_title = 'Content Moderation';
$current_page = 'admin/moderation.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_consultation'])) {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $start = $_POST['start_date'] ?? '';
        $end = $_POST['end_date'] ?? '';
        if (strlen($title) >= 3 && $start && $end) {
            $stmt = $conn->prepare("INSERT INTO consultations (created_by, title, description, start_date, end_date) VALUES (?,?,?,?,?)");
            $stmt->bind_param('issss', $uid, $title, $description, $start, $end);
            $stmt->execute();
            log_audit($conn, $uid, 'Consultation Created', $title);
        } else { $error = 'Please complete all fields.'; }
    } elseif (isset($_POST['close_id'])) {
        $cid = (int)$_POST['close_id'];
        $conn->query("UPDATE consultations SET status='closed' WHERE consultation_id=$cid");
        log_audit($conn, $uid, 'Consultation Closed', "#$cid");
    }
    if (!$error) { header('Location: /admin/moderation.php'); exit; }
}

$consultations = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM consultation_responses r WHERE r.consultation_id=c.consultation_id) responses FROM consultations c ORDER BY c.created_at DESC");

require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($error): ?><div class="alert alert-bad"><?= e($error) ?></div><?php endif; ?>
<div class="grid grid-2">
  <div class="card">
    <h2><i class="fa-solid fa-bullhorn"></i> Launch Public Consultation</h2>
    <form method="post">
      <div class="field"><label>Title</label><input type="text" name="title" required></div>
      <div class="field"><label>Description</label><textarea name="description" required></textarea></div>
      <div class="form-row">
        <div class="field"><label>Start Date</label><input type="date" name="start_date" required></div>
        <div class="field"><label>End Date</label><input type="date" name="end_date" required></div>
      </div>
      <button type="submit" name="create_consultation" value="1" class="btn btn-sm"><i class="fa-solid fa-bullhorn"></i> Launch Consultation</button>
    </form>
  </div>
  <div class="card">
    <h2><i class="fa-solid fa-people-arrows"></i> Manage Consultations</h2>
    <table>
      <tr><th>Title</th><th>Status</th><th>Responses</th><th></th></tr>
      <?php while ($c = $consultations->fetch_assoc()): ?>
        <tr>
          <td><?= e($c['title']) ?></td>
          <td><?= status_badge($c['status']) ?></td>
          <td><?= $c['responses'] ?></td>
          <td>
            <?php if ($c['status']==='open'): ?>
            <form method="post"><input type="hidden" name="close_id" value="<?= $c['consultation_id'] ?>">
              <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-lock"></i> Close</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
