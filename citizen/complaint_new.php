<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['citizen','sector_rep']);
$uid = current_user_id();
$page_title = 'Report a Concern';
$current_page = 'citizen/complaints.php';
$error = '';
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $category = $_POST['category'] ?? 'other';
    $evidence_path = null;

    if (strlen($subject) < 4) {
        $error = 'Subject must contain at least 4 characters.';
    } elseif (strlen($details) < 10) {
        $error = 'Please provide details containing at least 10 characters.';
    } else {
        if (!empty($_FILES['evidence']['name'])) {
            $allowed = ['jpg','jpeg','png','pdf'];
            $ext = strtolower(pathinfo($_FILES['evidence']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed) && $_FILES['evidence']['size'] < 5*1024*1024) {
                $fname = 'evidence_' . $uid . '_' . time() . '.' . $ext;
                $dest = __DIR__ . '/../uploads/' . $fname;
                if (move_uploaded_file($_FILES['evidence']['tmp_name'], $dest)) {
                    $evidence_path = 'uploads/' . $fname;
                }
            } else {
                $error = 'Evidence file must be JPG, PNG, or PDF under 5MB.';
            }
        }

        if (!$error) {
            $stmt = $conn->prepare("INSERT INTO complaints (user_id, subject, details, evidence_path, category) VALUES (?,?,?,?,?)");
            $stmt->bind_param('issss', $uid, $subject, $details, $evidence_path, $category);
            $stmt->execute();
            $cid = $stmt->insert_id;
            $stmt->close();
            $ref = ref_number('complaint', $cid);
            log_audit($conn, $uid, 'Complaint Filed', "Complaint $ref: $subject");
            notify_admins_and_lgu($conn, "New complaint filed: \"$subject\" ($ref)", "/lgu/cases.php?type=complaint");
            $success = ['ref' => $ref, 'id' => $cid, 'title' => $subject];
        }
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
  <a href="/citizen/dashboard.php">Dashboard</a>
  <span class="sep">/</span>
  <a href="/citizen/complaints.php">Complaints</a>
  <span class="sep">/</span>
  <span class="current">Report a Concern</span>
</nav>

<?php if ($success): ?>
<div class="card card-narrow">
  <div class="empty" style="padding:28px 16px">
    <i class="fa-solid fa-circle-check" style="color:var(--ok);font-size:40px"></i>
    <h2 style="margin-top:12px;color:var(--primary-dark)">Complaint filed successfully!</h2>
    <p class="muted" style="margin:10px 0 16px">Your concern has been recorded. The LGU has been notified and will investigate.</p>
    <div class="success-box">
      <div><strong>Reference</strong><br><code class="ref-code"><?= e($success['ref']) ?></code></div>
      <div class="mt"><strong>Status</strong><br><?= status_badge('filed') ?></div>
      <div class="mt small muted">Use this reference number when following up.</div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-top:20px">
      <a href="/citizen/complaints.php" class="btn">View My Complaints</a>
      <a href="/citizen/dashboard.php" class="btn btn-muted">Return to Dashboard</a>
    </div>
  </div>
</div>
<?php else: ?>
<div class="card card-form">
  <h2><i class="fa-solid fa-triangle-exclamation"></i> Report a Concern</h2>
  <p class="small muted mb">File a complaint or dispute about a public service, facility, personnel, or other civic matter. Provide as much detail as you can so the LGU can investigate fairly.</p>
  <?php if ($error): ?><div class="alert alert-bad" role="alert"><i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data" id="complaintForm">
    <div class="field">
      <label for="subject">Subject *</label>
      <input type="text" id="subject" name="subject" required minlength="4" maxlength="200"
             value="<?= e($_POST['subject'] ?? '') ?>"
             placeholder="e.g. Uncollected garbage on Rizal Avenue">
    </div>
    <div class="field">
      <label for="category">Category</label>
      <select id="category" name="category">
        <?php
        $cats = ['service'=>'Service','personnel'=>'Personnel','facility'=>'Facility','dispute'=>'Dispute','other'=>'Other'];
        $sel = $_POST['category'] ?? 'other';
        foreach ($cats as $v => $l): ?>
          <option value="<?= $v ?>" <?= $sel === $v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="details">Details *</label>
      <textarea id="details" name="details" required minlength="10" rows="5"
                placeholder="Describe what happened, when, and where..."><?= e($_POST['details'] ?? '') ?></textarea>
    </div>
    <div class="field">
      <label for="evidence">Evidence (optional)</label>
      <input type="file" id="evidence" name="evidence" accept=".jpg,.jpeg,.png,.pdf">
      <div class="help">Supported: JPG, PNG, or PDF — maximum 5MB.</div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn" id="submitBtn"><i class="fa-solid fa-paper-plane"></i> Submit Complaint</button>
      <a href="/citizen/complaints.php" class="btn btn-muted" onclick="return confirmLeave();"><i class="fa-solid fa-xmark"></i> Cancel</a>
    </div>
  </form>
</div>
<script>
(function(){
  var form = document.getElementById('complaintForm');
  var btn = document.getElementById('submitBtn');
  var dirty = false;
  if (form) {
    form.addEventListener('input', function(){ dirty = true; });
    form.addEventListener('change', function(){ dirty = true; });
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
