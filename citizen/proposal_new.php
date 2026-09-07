<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['citizen','sector_rep']);
$uid = current_user_id();
$page_title = 'Submit a Community Proposal';
$current_page = 'citizen/proposals.php';
$error = '';
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $justification = trim($_POST['justification'] ?? '');
    $benefits = trim($_POST['expected_benefits'] ?? '');
    $category = $_POST['category'] ?? 'other';

    if (strlen($title) < 4) {
        $error = 'Project title must contain at least 4 characters.';
    } elseif (strlen($description) < 10) {
        $error = 'Please provide a description containing at least 10 characters.';
    } else {
        $stmt = $conn->prepare("INSERT INTO proposals (user_id, title, description, justification, expected_benefits, category) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('isssss', $uid, $title, $description, $justification, $benefits, $category);
        $stmt->execute();
        $pid = $stmt->insert_id;
        $stmt->close();
        $ref = ref_number('proposal', $pid);
        log_audit($conn, $uid, 'Proposal Submitted', "Proposal $ref: $title");
        notify_admins_and_lgu($conn, "New community proposal submitted: \"$title\" ($ref)", "/lgu/cases.php?type=proposal");
        $success = [
            'ref' => $ref,
            'id' => $pid,
            'title' => $title,
        ];
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
  <a href="/citizen/dashboard.php">Dashboard</a>
  <span class="sep">/</span>
  <a href="/citizen/proposals.php">Proposals</a>
  <span class="sep">/</span>
  <span class="current">New Proposal</span>
</nav>

<?php if ($success): ?>
<div class="card card-narrow">
  <div class="empty" style="padding:28px 16px">
    <i class="fa-solid fa-circle-check" style="color:var(--ok);font-size:40px"></i>
    <h2 style="margin-top:12px;color:var(--primary-dark)">Proposal submitted successfully!</h2>
    <p class="muted" style="margin:10px 0 16px">Your idea has been received. The LGU has been notified and will review it shortly.</p>
    <div class="success-box">
      <div><strong>Reference</strong><br><code class="ref-code"><?= e($success['ref']) ?></code></div>
      <div class="mt"><strong>Status</strong><br><?= status_badge('pending') ?></div>
      <div class="mt small muted">Use this reference number when following up with your LGU.</div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-top:20px">
      <a href="/citizen/proposal_view.php?id=<?= (int)$success['id'] ?>" class="btn"><i class="fa-solid fa-eye"></i> View Submission</a>
      <a href="/citizen/proposals.php" class="btn btn-outline">My Proposals</a>
      <a href="/citizen/dashboard.php" class="btn btn-muted">Return to Dashboard</a>
    </div>
  </div>
</div>
<?php else: ?>
<div class="card card-form">
  <h2><i class="fa-solid fa-lightbulb"></i> Propose a Community Project</h2>
  <p class="small muted mb">Share an idea that could improve your community. LGU officers will review it and publish a justified decision.</p>
  <?php if ($error): ?><div class="alert alert-bad" role="alert"><i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?></div><?php endif; ?>
  <form method="post" id="proposalForm">
    <div class="field">
      <label for="title">Project Title *</label>
      <input type="text" id="title" name="title" required minlength="4" maxlength="200"
             value="<?= e($_POST['title'] ?? '') ?>"
             placeholder="e.g. Community Park Improvement on Mabini Street"
             aria-describedby="title-help">
      <div class="help" id="title-help">At least 4 characters. Be specific so officers can identify the project.</div>
    </div>
    <div class="field">
      <label for="category">Category *</label>
      <select id="category" name="category" required>
        <?php
        $cats = ['housing'=>'Housing','transport'=>'Transport','safety'=>'Safety','sanitation'=>'Sanitation','infrastructure'=>'Infrastructure','health'=>'Health','education'=>'Education','other'=>'Other'];
        $sel = $_POST['category'] ?? 'other';
        foreach ($cats as $val => $label): ?>
          <option value="<?= $val ?>" <?= $sel === $val ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="description">Description *</label>
      <textarea id="description" name="description" required minlength="10" rows="5"
                placeholder="Describe the project location, what needs to be done, and the current situation..."
                aria-describedby="desc-help"><?= e($_POST['description'] ?? '') ?></textarea>
      <div class="help" id="desc-help">Minimum 10 characters. Include location and scope.</div>
    </div>
    <div class="field">
      <label for="justification">Justification</label>
      <textarea id="justification" name="justification" rows="3"
                placeholder="Why is this project needed now?"><?= e($_POST['justification'] ?? '') ?></textarea>
      <div class="help">Optional. Helps officers prioritise the proposal.</div>
    </div>
    <div class="field">
      <label for="expected_benefits">Expected Benefits</label>
      <textarea id="expected_benefits" name="expected_benefits" rows="3"
                placeholder="Who will benefit from this project and how?"
                aria-describedby="benefits-help"><?= e($_POST['expected_benefits'] ?? '') ?></textarea>
      <div class="help" id="benefits-help">Example: Residents of Barangay San Jose will have a safe play area for children.</div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn" id="submitBtn"><i class="fa-solid fa-paper-plane"></i> Submit Proposal</button>
      <a href="/citizen/proposals.php" class="btn btn-muted" onclick="return confirmLeave();"><i class="fa-solid fa-xmark"></i> Cancel</a>
    </div>
  </form>
</div>
<script>
(function(){
  var form = document.getElementById('proposalForm');
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
