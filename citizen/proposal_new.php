<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['citizen','sector_rep']);
$uid = current_user_id();
$page_title = 'New Community Proposal';
$current_page = 'citizen/proposals.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $justification = trim($_POST['justification'] ?? '');
    $benefits = trim($_POST['expected_benefits'] ?? '');
    $category = $_POST['category'] ?? 'other';

    if (strlen($title) < 4 || strlen($description) < 10) {
        $error = 'Please provide a valid title and a detailed description (min 10 characters).';
    } else {
        $stmt = $conn->prepare("INSERT INTO proposals (user_id, title, description, justification, expected_benefits, category) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('isssss', $uid, $title, $description, $justification, $benefits, $category);
        $stmt->execute();
        $pid = $stmt->insert_id;
        log_audit($conn, $uid, 'Proposal Submitted', "Proposal #$pid: $title");
        notify_admins_and_lgu($conn, "New community proposal submitted: \"$title\"", "/lgu/cases.php?type=proposal");
        header('Location: /citizen/proposals.php');
        exit;
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width:640px">
  <h2><i class="fa-solid fa-lightbulb"></i> Propose a Community Project</h2>
  <?php if ($error): ?><div class="alert alert-bad"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <div class="field">
      <label>Project Title *</label>
      <input type="text" name="title" required value="<?= e($_POST['title'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Category *</label>
      <select name="category">
        <?php foreach (['housing','transport','safety','sanitation','infrastructure','health','education','other'] as $c): ?>
          <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Description *</label>
      <textarea name="description" required placeholder="Describe the project in detail..."><?= e($_POST['description'] ?? '') ?></textarea>
    </div>
    <div class="field">
      <label>Justification</label>
      <textarea name="justification" placeholder="Why is this project needed?"><?= e($_POST['justification'] ?? '') ?></textarea>
    </div>
    <div class="field">
      <label>Expected Benefits</label>
      <textarea name="expected_benefits" placeholder="Who benefits and how?"><?= e($_POST['expected_benefits'] ?? '') ?></textarea>
    </div>
    <button type="submit" class="btn"><i class="fa-solid fa-paper-plane"></i> Submit Proposal</button>
    <a href="/citizen/proposals.php" class="btn btn-muted"><i class="fa-solid fa-xmark"></i> Cancel</a>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
