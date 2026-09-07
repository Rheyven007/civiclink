<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$uid = current_user_id();
$page_title = 'User Management';
$current_page = 'admin/users.php';
$error = '';
$success = '';

// Handle role/status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['target_id'])) {
  $target_id = (int)$_POST['target_id'];
  $action = $_POST['action'];
  if ($action === 'update_role') {
    $role = $_POST['role'];
    if (in_array($role, ['citizen', 'sector_rep', 'lgu_officer', 'admin'])) {
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
      } else {
        $error = 'Email already in use.';
      }
    } else {
      $error = 'Please complete all fields correctly.';
    }
  }
  if (!$error) {
    header('Location: /admin/users.php');
    exit;
  }
}

$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$per_page = 12;
$where = '';
if ($search) {
  $q = $conn->real_escape_string($search);
  $where = " WHERE full_name LIKE '%$q%' OR email LIKE '%$q%' OR role LIKE '%$q%' ";
}
$total = (int)$conn->query("SELECT COUNT(*) c FROM users" . $where)->fetch_assoc()['c'];
$total_pages = max(1, (int)ceil($total / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;
$users = $conn->query("SELECT * FROM users" . $where . " ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");

require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($error): ?><div class="alert alert-bad"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-ok"><?= e($success) ?></div><?php endif; ?>

<div class="modal-form-trigger-row">
  <p class="muted"><i class="fa-solid fa-users"></i> Manage registered CivicLink accounts.</p><button type="button" class="btn btn-sm" data-open-inline-modal="createOfficerModal"><i class="fa-solid fa-plus"></i> Add LGU Officer</button>
</div>
<div class="app-modal" id="createOfficerModal" aria-hidden="true">
  <div class="app-modal-dialog" role="dialog" aria-modal="true">
    <div class="app-modal-head">
      <h2>Create LGU Officer Account</h2><button type="button" class="app-modal-close" data-close-inline-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="app-modal-body">
      <form method="post" data-confirm="Create this LGU officer account?">
        <input type="hidden" name="action" value="create_officer"><input type="hidden" name="target_id" value="0">
        <div class="field"><label>Full Name</label><input type="text" name="full_name" required></div>
        <div class="field"><label>Email</label><input type="email" name="email" required></div>
        <div class="field"><label>Department</label><input type="text" name="department" placeholder="e.g., Public Works"></div>
        <div class="field"><label>Temporary Password</label><input type="password" name="password" required></div><button type="submit" class="btn btn-sm"><i class="fa-solid fa-user-plus"></i> Create Account</button>
      </form>
    </div>
  </div>
</div>

<div class="card ajax-table-container" data-ajax-endpoint="1">
  <div class="data-toolbar">
    <h2 style="margin:0"><i class="fa-solid fa-users"></i> All Users <span class="small muted" data-result-count>(<?= number_format($total) ?>)</span></h2>
    <div class="data-search"><i class="fa-solid fa-magnifying-glass"></i><input data-ajax-search type="search" value="<?= e($search) ?>" placeholder="Search name, email, or role..."></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Status</th>
          <th>Joined</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($users->num_rows === 0): ?><tr>
            <td colspan="6">
              <div class="empty">No users found.</div>
            </td>
          </tr><?php else: while ($u = $users->fetch_assoc()): ?>
            <tr>
              <td><?= e($u['full_name']) ?></td>
              <td class="small"><?= e($u['email']) ?></td>
              <td>
                <form method="post" style="display:inline-flex;gap:6px">
                  <input type="hidden" name="action" value="update_role">
                  <input type="hidden" name="target_id" value="<?= $u['user_id'] ?>">
                  <select name="role" data-confirm-change="Change this user's role?" data-current="<?= e($u['role']) ?>" <?= $u['user_id'] == $uid ? 'disabled' : '' ?> style="padding:4px 6px;font-size:12.5px">
                    <?php foreach (['citizen', 'sector_rep', 'lgu_officer', 'admin'] as $r): ?>
                      <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $r)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
              <td><?= status_badge($u['status']) ?></td>
              <td class="small muted" title="<?= e(format_datetime($u['created_at'])) ?>"><?= e(format_datetime($u['created_at'])) ?></td>
              <td>
                <?php if ($u['user_id'] != $uid): ?>
                  <form method="post" style="display:inline" data-confirm="<?= $u['status'] === 'active' ? 'Suspend this user account?' : 'Activate this user account?' ?>" data-confirm-icon="warning">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="target_id" value="<?= $u['user_id'] ?>">
                    <input type="hidden" name="status" value="<?= $u['status'] ?>">
                    <button type="submit" class="btn btn-sm <?= $u['status'] === 'active' ? 'btn-danger' : 'btn-ok' ?>">
                      <i class="fa-solid <?= $u['status'] === 'active' ? 'fa-ban' : 'fa-circle-check' ?>"></i> <?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                    </button>
                  </form>
                <?php else: ?>
                  <span class="small muted">You</span>
                <?php endif; ?>
              </td>
            </tr>
        <?php endwhile;
              endif; ?>
      </tbody>
    </table>
  </div>
  <?php render_pagination($page, $total_pages, $total, $per_page, ['q' => $search]); ?>
</div>
<script>
  (function() {
    document.querySelectorAll('[data-open-inline-modal]').forEach(function(b) {
      b.addEventListener('click', function() {
        var m = document.getElementById(b.dataset.openInlineModal);
        m.classList.add('open');
        m.setAttribute('aria-hidden', 'false');
      });
    });
    document.querySelectorAll('[data-close-inline-modal]').forEach(function(b) {
      b.addEventListener('click', function() {
        var m = b.closest('.app-modal');
        m.classList.remove('open');
        m.setAttribute('aria-hidden', 'true');
      });
    });
    document.querySelectorAll('.app-modal').forEach(function(m) {
      m.addEventListener('click', function(e) {
        if (e.target === m) {
          m.classList.remove('open');
          m.setAttribute('aria-hidden', 'true');
        }
      });
    });
  })();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>