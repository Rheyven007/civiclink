<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['citizen','sector_rep']);
$uid = current_user_id();
$page_title = 'New Service Request';
$current_page = 'citizen/requests.php';
$error = '';

$departments = ['Public Works','Health Services','Social Welfare','Sanitation','Transportation','Public Safety','Housing','Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_type = trim($_POST['service_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $department = $_POST['department'] ?? 'Other';
    $priority = $_POST['priority'] ?? 'normal';

    if (strlen($service_type) < 3 || strlen($description) < 10) {
        $error = 'Please describe the service you need in more detail.';
    } else {
        $stmt = $conn->prepare("INSERT INTO service_requests (user_id, service_type, description, department, priority) VALUES (?,?,?,?,?)");
        $stmt->bind_param('issss', $uid, $service_type, $description, $department, $priority);
        $stmt->execute();
        $rid = $stmt->insert_id;
        log_audit($conn, $uid, 'Service Request Submitted', "Request #$rid: $service_type");
        notify_admins_and_lgu($conn, "New service request: \"$service_type\"", "/lgu/cases.php?type=service_request");
        header('Location: /citizen/requests.php');
        exit;
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width:600px">
  <h2><i class="fa-solid fa-clipboard-list"></i> Request a Public Service</h2>
  <?php if ($error): ?><div class="alert alert-bad"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <div class="field">
      <label>Service Type *</label>
      <input type="text" name="service_type" required placeholder="e.g., Streetlight repair, Waste collection" value="<?= e($_POST['service_type'] ?? '') ?>">
    </div>
    <div class="form-row">
      <div class="field">
        <label>Department</label>
        <select name="department">
          <?php foreach ($departments as $d): ?><option><?= e($d) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Priority</label>
        <select name="priority">
          <option value="low">Low</option>
          <option value="normal" selected>Normal</option>
          <option value="high">High</option>
          <option value="urgent">Urgent (non-emergency)</option>
        </select>
      </div>
    </div>
    <div class="field">
      <label>Description *</label>
      <textarea name="description" required placeholder="Describe your request..."><?= e($_POST['description'] ?? '') ?></textarea>
    </div>
    <button type="submit" class="btn"><i class="fa-solid fa-paper-plane"></i> Submit Request</button>
    <a href="/citizen/requests.php" class="btn btn-muted"><i class="fa-solid fa-xmark"></i> Cancel</a>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
