<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$uid = current_user_id();
$page_title = 'User Management';
$current_page = 'admin/users.php';
$error = ''; $success = '';

// Handle role/status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['target_id'])) {
    $target_id = (int)$_POST['target_id'];
    $action = $_POST['action'];
    if ($action === 'update_role') {
        $role = $_POST['role'];
        if (in_array($role, ['citizen','sector_rep','lgu_officer','admin'])) {
            $stmt = $conn->prepare("UPDATE users SET role=? WHERE user_id=?");
            $stmt->bind_param('si', $role, $target_id);
            $stmt->execute();
            log_audit($conn, $uid, 'Role Changed', "User #$target_id -> $role");
        }
    } elseif ($action === 'toggle_status') {
        $status = $_POST['status'] === 'active' ? 'suspended' : 'active';
        $stmt = $conn->prepare("UPDATE users SET status=? WHERE user_id=?");
        $stmt->bind_param('si', $status, $target_id);
        $stmt->execute();
        log_audit($conn, $uid, 'Status Changed', "User #$target_id -> $status");
    } elseif ($action === 'create_officer') {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $department = trim($_POST['department']);
        if (strlen($full_name) >= 2 && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($password) >= 6) {
            $exists = $conn->query("SELECT user_id FROM users WHERE email='" . $conn->real_escape_string($email) . "'")->num_rows;
            if (!$exists) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role, department) VALUES (?,?,?,'lgu_officer',?)");
                $stmt->bind_param('ssss', $full_name, $email, $hash, $department);
                $stmt->execute();
                log_audit($conn, $uid, 'LGU Officer Created', $email);
                $success = 'LGU officer account created.';
            } else { $error = 'Email already in use.'; }
        } else { $error = 'Please complete all fields correctly.'; }
    }
    if (!$error) { header('Location: /admin/users.php'); exit; }
}

$search = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM users";
if ($search) $sql .= " WHERE full_name LIKE '%" . $conn->real_escape_string($search) . "%' OR email LIKE '%" . $conn->real_escape_string($search) . "%'";
$sql .= " ORDER BY created_at DESC";
$users = $conn->query($sql);

require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($error): ?><div class="alert alert-bad"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-ok"><?= e($success) ?></div><?php endif; ?>

<div class="grid grid-2">
  <div class="card">
    <h2>Create LGU Officer Account</h2>
    <form method="post">
      <input type="hidden" name="action" value="create_officer">
      <input type="hidden" name="target_id" value="0">
      <div class="field"><label>Full Name</label><input type="text" name="full_name" required></div>
      <div class="field"><label>Email</label><input type="email" name="email" required></div>
      <div class="field"><label>Department</label><input type="text" name="department" placeholder="e.g., Public Works"></div>
      <div class="field"><label>Temporary Password</label><input type="password" name="password" required></div>
      <button type="submit" class="btn btn-sm">Create Account</button>
    </form>
  </div>
  <div class="card">
    <h2>Search Users</h2>
    <form method="get">
      <div class="field"><label>Name or Email</label><input type="text" name="q" value="<?= e($search) ?>" placeholder="Search..."></div>
      <button type="submit" class="btn btn-sm btn-outline">Search</button>
      <a href="/admin/users.php" class="btn btn-sm btn-muted">Clear</a>
    </form>
  </div>
</div>

<div class="card">
  <h2>All Users (<?= $users->num_rows ?>)</h2>
  <table>
    <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
    <?php while ($u = $users->fetch_assoc()): ?>
      <tr>
        <td><?= e($u['full_name']) ?></td>
        <td class="small"><?= e($u['email']) ?></td>
        <td>
          <form method="post" style="display:inline-flex;gap:6px">
            <input type="hidden" name="action" value="update_role">
            <input type="hidden" name="target_id" value="<?= $u['user_id'] ?>">
            <select name="role" onchange="this.form.submit()" <?= $u['user_id']==$uid?'disabled':'' ?> style="padding:4px 6px;font-size:12.5px">
              <?php foreach (['citizen','sector_rep','lgu_officer','admin'] as $r): ?>
                <option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= ucwords(str_replace('_',' ',$r)) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </td>
        <td><?= status_badge($u['status']) ?></td>
        <td class="small muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
        <td>
          <?php if ($u['user_id'] != $uid): ?>
          <form method="post" style="display:inline">
            <input type="hidden" name="action" value="toggle_status">
            <input type="hidden" name="target_id" value="<?= $u['user_id'] ?>">
            <input type="hidden" name="status" value="<?= $u['status'] ?>">
            <button type="submit" class="btn btn-sm <?= $u['status']==='active'?'btn-danger':'btn-ok' ?>">
              <?= $u['status']==='active' ? 'Suspend' : 'Activate' ?>
            </button>
          </form>
          <?php else: ?>
            <span class="small muted">You</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
