<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$uid = current_user_id();
$page_title = 'My Profile';
$current_page = 'citizen/profile.php';
$error = ''; $success = '';

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
$stmt->bind_param('i', $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$profile = null;
if ($user['role'] === 'citizen' || $user['role'] === 'sector_rep') {
    $stmt = $conn->prepare("SELECT cp.*, s.sector_name FROM citizen_profiles cp LEFT JOIN sectors s ON cp.sector_id=s.sector_id WHERE cp.user_id=?");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $contact = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    if (strlen($full_name) < 2) {
        $error = 'Please enter a valid name.';
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, contact_number=?, address=? WHERE user_id=?");
        $stmt->bind_param('sssi', $full_name, $contact, $address, $uid);
        $stmt->execute();
        $_SESSION['full_name'] = $full_name;

        if (!empty($_POST['new_password'])) {
            if (strlen($_POST['new_password']) >= 6) {
                $hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET password_hash=? WHERE user_id=?");
                $stmt->bind_param('si', $hash, $uid);
                $stmt->execute();
            } else {
                $error = 'New password must be at least 6 characters.';
            }
        }
        if (!$error) {
            log_audit($conn, $uid, 'Profile Updated', '');
            $success = 'Profile updated successfully.';
            $user['full_name'] = $full_name; $user['contact_number'] = $contact; $user['address'] = $address;
        }
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card card-narrow">
  <h2><i class="fa-solid fa-user"></i> Profile Information</h2>
  <?php if ($error): ?><div class="alert alert-bad"><?= e($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-ok"><?= e($success) ?></div><?php endif; ?>
  <form method="post">
    <div class="field">
      <label>Full Name</label>
      <input type="text" name="full_name" value="<?= e($user['full_name']) ?>">
    </div>
    <div class="field">
      <label>Email (cannot be changed)</label>
      <input type="email" value="<?= e($user['email']) ?>" disabled>
    </div>
    <div class="form-row">
      <div class="field">
        <label>Contact Number</label>
        <input type="text" name="contact_number" value="<?= e($user['contact_number']) ?>">
      </div>
      <div class="field">
        <label>Role</label>
        <input type="text" value="<?= e(ucwords(str_replace('_',' ',$user['role']))) ?>" disabled>
      </div>
    </div>
    <div class="field">
      <label>Address</label>
      <input type="text" name="address" value="<?= e($user['address']) ?>">
    </div>
    <?php if ($profile): ?>
    <div class="field">
      <label>Sector Affiliation</label>
      <input type="text" value="<?= e($profile['sector_name'] ?? 'None specified') ?>" disabled>
      <div class="hint">Contact an administrator to update your sector.</div>
    </div>
    <?php endif; ?>
    <div class="field">
      <label>New Password (leave blank to keep current)</label>
      <input type="password" name="new_password">
    </div>
    <button type="submit" class="btn"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
