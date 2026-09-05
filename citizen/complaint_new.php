<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['citizen','sector_rep']);
$uid = current_user_id();
$page_title = 'File a Complaint';
$current_page = 'citizen/complaints.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $category = $_POST['category'] ?? 'other';
    $evidence_path = null;

    if (strlen($subject) < 4 || strlen($details) < 10) {
        $error = 'Please provide a subject and detailed explanation (min 10 characters).';
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
            log_audit($conn, $uid, 'Complaint Filed', "Complaint #$cid: $subject");
            notify_admins_and_lgu($conn, "New complaint filed: \"$subject\"", "/lgu/cases.php?type=complaint");
            header('Location: /citizen/complaints.php');
            exit;
        }
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width:600px">
  <h2>File a Complaint or Dispute</h2>
  <?php if ($error): ?><div class="alert alert-bad"><?= e($error) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <div class="field">
      <label>Subject *</label>
      <input type="text" name="subject" required value="<?= e($_POST['subject'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Category</label>
      <select name="category">
        <?php foreach (['service','personnel','facility','dispute','other'] as $c): ?>
          <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Details *</label>
      <textarea name="details" required placeholder="Describe what happened..."><?= e($_POST['details'] ?? '') ?></textarea>
    </div>
    <div class="field">
      <label>Evidence (optional)</label>
      <input type="file" name="evidence" accept=".jpg,.jpeg,.png,.pdf">
      <div class="hint">JPG, PNG, or PDF — max 5MB</div>
    </div>
    <button type="submit" class="btn">Submit Complaint</button>
    <a href="/citizen/complaints.php" class="btn btn-muted">Cancel</a>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
