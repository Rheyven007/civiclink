<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$uid = current_user_id();
$page_title = 'Sector Management';
$current_page = 'admin/sectors.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['add_sector'])) {
    $name = trim($_POST['sector_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if (strlen($name) >= 2) {
      $stmt = $conn->prepare("INSERT INTO sectors (sector_name, description) VALUES (?,?)");
      $stmt->bind_param('ss', $name, $desc);
      $stmt->execute();
      log_audit($conn, $uid, 'Sector Added', $name);
    } else {
      $error = 'Sector name is required.';
    }
  } elseif (isset($_POST['delete_id'])) {
    $sid = (int)$_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM sectors WHERE sector_id=?");
    $stmt->bind_param('i', $sid);
    $stmt->execute();
    log_audit($conn, $uid, 'Sector Deleted', "Sector #$sid");
  }
  if (!$error) {
    header('Location: /admin/sectors.php');
    exit;
  }
}

$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$per_page = 12;
$where = '';
if ($search) {
  $q = $conn->real_escape_string($search);
  $where = " WHERE s.sector_name LIKE '%$q%' OR s.description LIKE '%$q%'";
}
$total = (int)$conn->query("SELECT COUNT(*) c FROM sectors s" . $where)->fetch_assoc()['c'];
$total_pages = max(1, (int)ceil($total / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;
$sectors = $conn->query("SELECT s.*, (SELECT COUNT(*) FROM citizen_profiles cp WHERE cp.sector_id=s.sector_id) members FROM sectors s" . $where . " ORDER BY s.sector_name LIMIT $per_page OFFSET $offset");

require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($error): ?><div class="alert alert-bad"><?= e($error) ?></div><?php endif; ?>
<div class="modal-form-trigger-row">
  <p class="muted"><i class="fa-solid fa-diagram-project"></i> Manage community sectors.</p><button type="button" class="btn btn-sm" data-open-inline-modal="createSectorModal"><i class="fa-solid fa-plus"></i> Add Sector</button>
</div>
<div class="app-modal" id="createSectorModal" aria-hidden="true">
  <div class="app-modal-dialog" role="dialog" aria-modal="true">
    <div class="app-modal-head">
      <h2>Add New Sector</h2><button type="button" class="app-modal-close" data-close-inline-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="app-modal-body">
      <form method="post">
        <div class="field"><label>Sector Name</label><input type="text" name="sector_name" required></div>
        <div class="field"><label>Description</label><input type="text" name="description"></div><button type="submit" name="add_sector" value="1" class="btn btn-sm"><i class="fa-solid fa-plus"></i> Add Sector</button>
      </form>
    </div>
  </div>
</div>
<div class="card ajax-table-container" data-ajax-endpoint="1">
  <div class="data-toolbar">
    <h2 style="margin:0"><i class="fa-solid fa-diagram-project"></i> Existing Sectors</h2>
    <div class="data-search"><i class="fa-solid fa-magnifying-glass"></i><input data-ajax-search type="search" value="<?= e($search) ?>" placeholder="Search sectors..."></div>
  </div>
  <div class="table-wrap">
    <table>
      <tr>
        <th>Sector</th>
        <th>Members</th>
        <th></th>
      </tr>
      <?php if ($sectors->num_rows === 0): ?><tr>
          <td colspan="3">
            <div class="empty">No sectors found.</div>
          </td>
        </tr><?php else: while ($s = $sectors->fetch_assoc()): ?>
          <tr>
            <td><?= e($s['sector_name']) ?><div class="small muted"><?= e($s['description']) ?></div>
            </td>
            <td><?= $s['members'] ?></td>
            <td>
              <form method="post" data-confirm="Delete this sector? This cannot be undone." data-confirm-icon="warning">
                <input type="hidden" name="delete_id" value="<?= $s['sector_id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
              </form>
            </td>
          </tr>
      <?php endwhile;
            endif; ?>
    </table>
  </div>
  <?php render_pagination($page, $total_pages, $total, $per_page, ['q' => $search]); ?>
</div>
</div>
<script>
  (function() {
    document.querySelectorAll('[data-open-inline-modal]').forEach(function(b) {
      b.onclick = function() {
        var m = document.getElementById(b.dataset.openInlineModal);
        m.classList.add('open');
        m.setAttribute('aria-hidden', 'false');
      };
    });
    document.querySelectorAll('[data-close-inline-modal]').forEach(function(b) {
      b.onclick = function() {
        b.closest('.app-modal').classList.remove('open');
      };
    });
    document.querySelectorAll('.app-modal').forEach(function(m) {
      m.onclick = function(e) {
        if (e.target === m) m.classList.remove('open');
      };
    });
  })();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>