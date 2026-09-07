<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['citizen','sector_rep']);
$uid = current_user_id();
$page_title = 'Request a Public Service';
$current_page = 'citizen/requests.php';
$error = '';
$success = null;

$departments = ['Public Works','Health Services','Social Welfare','Sanitation','Transportation','Public Safety','Housing','Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_type = trim($_POST['service_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $department = $_POST['department'] ?? 'Other';
    $priority = $_POST['priority'] ?? 'normal';

    if (strlen($service_type) < 3) {
        $error = 'Please enter a service type (at least 3 characters).';
    } elseif (strlen($description) < 10) {
        $error = 'Please describe the service you need in more detail (at least 10 characters).';
    } else {
        $stmt = $conn->prepare("INSERT INTO service_requests (user_id, service_type, description, department, priority) VALUES (?,?,?,?,?)");
        $stmt->bind_param('issss', $uid, $service_type, $description, $department, $priority);
        $stmt->execute();
        $rid = $stmt->insert_id;
        $stmt->close();
        $ref = ref_number('request', $rid);
        log_audit($conn, $uid, 'Service Request Submitted', "Request $ref: $service_type");
        notify_admins_and_lgu($conn, "New service request: \"$service_type\" ($ref)", "/lgu/cases.php?type=service_request");
        $success = ['ref' => $ref, 'id' => $rid, 'title' => $service_type];
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
  <a href="/citizen/dashboard.php">Dashboard</a>
  <span class="sep">/</span>
  <a href="/citizen/requests.php">Service Requests</a>
  <span class="sep">/</span>
  <span class="current">New Request</span>
</nav>

<?php if ($success): ?>
<div class="card card-narrow">
  <div class="empty" style="padding:28px 16px">
    <i class="fa-solid fa-circle-check" style="color:var(--ok);font-size:40px"></i>
    <h2 style="margin-top:12px;color:var(--primary-dark)">Service request submitted successfully!</h2>
    <p class="muted" style="margin:10px 0 16px">Your request has been received. The LGU has been notified.</p>
    <div class="success-box">
      <div><strong>Reference</strong><br><code class="ref-code"><?= e($success['ref']) ?></code></div>
      <div class="mt"><strong>Status</strong><br><?= status_badge('submitted') ?></div>
      <div class="mt small muted">Use this reference number when following up.</div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-top:20px">
      <a href="/citizen/requests.php" class="btn">View My Requests</a>
      <a href="/citizen/dashboard.php" class="btn btn-muted">Return to Dashboard</a>
    </div>
  </div>
</div>
<?php else: ?>
<div class="card card-form">
  <h2><i class="fa-solid fa-clipboard-list"></i> Request a Public Service</h2>
  <p class="small muted mb">Use this form to request a <strong>non-emergency</strong> public service from the LGU (e.g. streetlight repair, waste collection, drainage clearing).</p>
  <?php if ($error): ?><div class="alert alert-bad" role="alert"><i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?></div><?php endif; ?>
  <form method="post" id="requestForm">
    <div class="field">
      <label for="service_type">Service Type *</label>
      <input type="text" id="service_type" name="service_type" required minlength="3"
             placeholder="e.g. Streetlight repair, Waste collection, Drainage clearing"
             value="<?= e($_POST['service_type'] ?? '') ?>">
    </div>
    <div class="form-row">
      <div class="field">
        <label for="department">Department</label>
        <select id="department" name="department">
          <?php
          $selDept = $_POST['department'] ?? 'Other';
          foreach ($departments as $d): ?>
            <option <?= $selDept === $d ? 'selected' : '' ?>><?= e($d) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="priority">Priority <span class="help-inline" title="Priority helps the LGU determine which requests may require earlier attention.">?</span></label>
        <select id="priority" name="priority">
          <?php
          $prios = ['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent (non-emergency)'];
          $selP = $_POST['priority'] ?? 'normal';
          foreach ($prios as $v => $l): ?>
            <option value="<?= $v ?>" <?= $selP === $v ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
        <div class="help">For true emergencies, contact local emergency services — do not use this form.</div>
      </div>
    </div>
    <div class="field">
      <label for="description">Description *</label>
      <textarea id="description" name="description" required minlength="10" rows="4"
                placeholder="Describe the location, the problem, and any details that will help the LGU respond..."><?= e($_POST['description'] ?? '') ?></textarea>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn" id="submitBtn"><i class="fa-solid fa-paper-plane"></i> Submit Request</button>
      <a href="/citizen/requests.php" class="btn btn-muted" onclick="return confirmLeave();"><i class="fa-solid fa-xmark"></i> Cancel</a>
    </div>
  </form>
</div>
<script>
(function(){
  var form = document.getElementById('requestForm');
  var btn = document.getElementById('submitBtn');
  var dirty = false;
  if (form) {
    form.addEventListener('input', function(){ dirty = true; });
    form.addEventListener('submit', function(){
      if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...'; }
    });
  }
  window.confirmLeave = function(){
    if (!dirty) return true;
    return confirm('You have unsaved information. Are you sure you want to leave this page?');
  };
})();
</script>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
