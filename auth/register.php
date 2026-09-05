<?php
require_once __DIR__ . '/../includes/functions.php';
if (is_logged_in()) redirect_by_role();

$error = '';
$sectors = $conn->query("SELECT sector_id, sector_name FROM sectors ORDER BY sector_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $contact = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $sector_id = !empty($_POST['sector_id']) ? (int)$_POST['sector_id'] : null;
    $barangay = trim($_POST['barangay'] ?? '');

    if (strlen($full_name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        $error = 'Please complete all required fields. Password must be at least 6 characters.';
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'An account with this email already exists.';
        }
        $stmt->close();

        if (!$error) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role, contact_number, address) VALUES (?, ?, ?, 'citizen', ?, ?)");
            $stmt->bind_param('sssss', $full_name, $email, $hash, $contact, $address);
            $stmt->execute();
            $user_id = $stmt->insert_id;
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO citizen_profiles (user_id, sector_id, barangay) VALUES (?, ?, ?)");
            $stmt->bind_param('iis', $user_id, $sector_id, $barangay);
            $stmt->execute();
            $stmt->close();

            log_audit($conn, $user_id, 'Register', 'New citizen account created');

            $_SESSION['user_id'] = $user_id;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['role'] = 'citizen';
            redirect_by_role();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - CivicBridge</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-box" style="max-width:460px">
    <h1>Create your account</h1>
    <p class="sub">Join CivicBridge to participate in local governance</p>
    <?php if ($error): ?><div class="alert alert-bad"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <div class="field">
        <label>Full name *</label>
        <input type="text" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Email address *</label>
        <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Password *</label>
        <input type="password" name="password" required>
        <div class="hint">At least 6 characters</div>
      </div>
      <div class="form-row">
        <div class="field">
          <label>Contact number</label>
          <input type="text" name="contact_number" value="<?= e($_POST['contact_number'] ?? '') ?>">
        </div>
        <div class="field">
          <label>Barangay</label>
          <input type="text" name="barangay" value="<?= e($_POST['barangay'] ?? '') ?>">
        </div>
      </div>
      <div class="field">
        <label>Address</label>
        <input type="text" name="address" value="<?= e($_POST['address'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Sector affiliation (optional)</label>
        <select name="sector_id">
          <option value="">Prefer not to say</option>
          <?php while ($s = $sectors->fetch_assoc()): ?>
            <option value="<?= $s['sector_id'] ?>"><?= e($s['sector_name']) ?></option>
          <?php endwhile; ?>
        </select>
        <div class="hint">Used only for equitable prioritization and policy analysis — never for discrimination.</div>
      </div>
      <button type="submit" class="btn btn-block">Create Account</button>
    </form>
    <div class="switch">Already have an account? <a href="/auth/login.php">Log in</a></div>
  </div>
</div>
</body>
</html>
