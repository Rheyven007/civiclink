<?php
require_once __DIR__ . '/../includes/functions.php';
if (is_logged_in()) redirect_by_role();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT user_id, full_name, password_hash, role, status FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['status'] !== 'active') {
            $error = 'Your account is not active. Please contact the administrator.';
        } else {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            log_audit($conn, $user['user_id'], 'Login', 'User logged in');
            redirect_by_role();
        }
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log In - CivicLink</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
  <div style="width:100%;max-width:400px">
    <a href="/landing.php" class="back-home"><i class="fa-solid fa-arrow-left"></i> Back to home</a>
    <div class="auth-box">
      <div class="logo"><i class="fa-solid fa-landmark-dome"></i> Civic<span>Link</span></div>
      <h1>Welcome back</h1>
      <p class="sub">Log in to CivicLink — Inclusive Urban Governance Platform</p>
      <?php if ($error): ?><div class="alert alert-bad"><i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?></div><?php endif; ?>
      <form method="post">
        <div class="field">
          <label>Email address</label>
          <input type="email" name="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <div class="field">
          <label>Password</label>
          <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-block"><i class="fa-solid fa-right-to-bracket"></i> Log In</button>
      </form>
      <div class="switch">New to CivicLink? <a href="/auth/register.php">Create an account</a></div>
      <div class="switch small">Admin demo: admin@civicbridge.gov / password</div>
    </div>
  </div>
</div>
</body>
</html>
