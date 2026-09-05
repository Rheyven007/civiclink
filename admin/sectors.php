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
        } else { $error = 'Sector name is required.'; }
    } elseif (isset($_POST['delete_id'])) {
        $sid = (int)$_POST['delete_id'];
        $stmt = $conn->prepare("DELETE FROM sectors WHERE sector_id=?");
        $stmt->bind_param('i', $sid);
        $stmt->execute();
        log_audit($conn, $uid, 'Sector Deleted', "Sector #$sid");
    }
    if (!$error) { header('Location: /admin/sectors.php'); exit; }
}

$sectors = $conn->query("SELECT s.*, (SELECT COUNT(*) FROM citizen_profiles cp WHERE cp.sector_id=s.sector_id) members FROM sectors s ORDER BY s.sector_name");

require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($error): ?><div class="alert alert-bad"><?= e($error) ?></div><?php endif; ?>
<div class="grid grid-2">
  <div class="card">
    <h2><i class="fa-solid fa-plus"></i> Add New Sector</h2>
    <form method="post">
      <div class="field"><label>Sector Name</label><input type="text" name="sector_name" required></div>
      <div class="field"><label>Description</label><input type="text" name="description"></div>
      <button type="submit" name="add_sector" value="1" class="btn btn-sm"><i class="fa-solid fa-plus"></i> Add Sector</button>
    </form>
  </div>
  <div class="card">
    <h2><i class="fa-solid fa-diagram-project"></i> Existing Sectors</h2>
    <table>
      <tr><th>Sector</th><th>Members</th><th></th></tr>
      <?php while ($s = $sectors->fetch_assoc()): ?>
        <tr>
          <td><?= e($s['sector_name']) ?><div class="small muted"><?= e($s['description']) ?></div></td>
          <td><?= $s['members'] ?></td>
          <td>
            <form method="post" onsubmit="return confirm('Delete this sector?')">
              <input type="hidden" name="delete_id" value="<?= $s['sector_id'] ?>">
              <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
