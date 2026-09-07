<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$uid = current_user_id();
$page_title = 'Content Moderation';
$current_page = 'admin/moderation.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_consultation']) || ($_POST['action'] ?? '') === 'create_consultation') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $start = $_POST['start_date'] ?? '';
        $end = $_POST['end_date'] ?? '';
        if (strlen($title) >= 3 && strlen($title) <= 200 && $description !== '' && $start && $end) {
            if ($end < $start) {
                $error = 'End date cannot be earlier than the start date.';
            }
            try {
                if ($error) {
                    throw new RuntimeException($error);
                }
                $stmt = $conn->prepare("INSERT INTO consultations (created_by, title, description, start_date, end_date) VALUES (?,?,?,?,?)");
                $stmt->bind_param('issss', $uid, $title, $description, $start, $end);
                $stmt->execute();
                $new_cid = $stmt->insert_id;
                log_audit($conn, $uid, 'Consultation Created', $title);
                notify_all_citizens($conn, "New public consultation: \"$title\". Share your input before " . date('M j, Y', strtotime($end)) . ".", "/citizen/consultations.php");
            } catch (mysqli_sql_exception $e) {
                // Surface the real DB error (e.g. a stale session user_id that no
                // longer exists after a fresh import, which would violate the
                // created_by foreign key) instead of silently doing nothing.
                $error = 'Could not save the consultation: ' . $e->getMessage()
                    . '. If you recently re-imported the database, please log out and log back in.';
            }
        } else {
            $error = 'Please complete all fields.';
        }
    } elseif (isset($_POST['close_id'])) {
        $cid = (int)$_POST['close_id'];
        try {
            $c = $conn->query("SELECT title FROM consultations WHERE consultation_id=$cid")->fetch_assoc();
            $conn->query("UPDATE consultations SET status='closed' WHERE consultation_id=$cid");
            log_audit($conn, $uid, 'Consultation Closed', "#$cid");
            if ($c) {
                notify_consultation_participants($conn, $cid, "The consultation \"{$c['title']}\" is now closed. Thank you for participating.", "/citizen/consultations.php");
            }
        } catch (mysqli_sql_exception $e) {
            $error = 'Could not close the consultation: ' . $e->getMessage();
        }
    }
    if (!$error) {
        header('Location: /admin/moderation.php');
        exit;
    }
}

$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$per_page = 12;
$where = '';
if ($search) {
    $q = $conn->real_escape_string($search);
    $where = " WHERE c.title LIKE '%$q%' OR c.description LIKE '%$q%' OR c.status LIKE '%$q%'";
}
$total = (int)$conn->query("SELECT COUNT(*) c FROM consultations c" . $where)->fetch_assoc()['c'];
$total_pages = max(1, (int)ceil($total / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;
$consultations = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM consultation_responses r WHERE r.consultation_id=c.consultation_id) responses FROM consultations c" . $where . " ORDER BY c.created_at DESC LIMIT $per_page OFFSET $offset");

require_once __DIR__ . '/../includes/header.php';
?>
<?php if ($error): ?><div class="alert alert-bad"><?= e($error) ?></div><?php endif; ?>
<div class="modal-form-trigger-row">
    <p class="muted"><i class="fa-solid fa-people-arrows"></i> Manage public consultation activities.</p><button type="button" class="btn btn-sm" data-open-inline-modal="createConsultationModal"><i class="fa-solid fa-plus"></i> Add Consultation</button>
</div>
<div class="app-modal" id="createConsultationModal" aria-hidden="true">
    <div class="app-modal-dialog" role="dialog" aria-modal="true">
        <div class="app-modal-head">
            <h2>Launch Public Consultation</h2><button type="button" class="app-modal-close" data-close-inline-modal><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="app-modal-body">
            <form method="post" data-confirm="Confirm this moderation action?"><input type="hidden" name="action" value="create_consultation">
                <div class="field"><label>Title</label><input type="text" name="title" required></div>
                <div class="field"><label>Description</label><textarea name="description" required></textarea></div>
                <div class="form-row">
                    <div class="field"><label>Start Date</label><input type="date" name="start_date" required></div>
                    <div class="field"><label>End Date</label><input type="date" name="end_date" required></div>
                </div><button type="submit" name="create_consultation" value="1" class="btn btn-sm"><i class="fa-solid fa-bullhorn"></i> Launch Consultation</button>
            </form>
        </div>
    </div>
</div>
<div class="card ajax-table-container" data-ajax-endpoint="1">
    <div class="data-toolbar">
        <h2 style="margin:0"><i class="fa-solid fa-people-arrows"></i> Manage Consultations <span class="small muted" data-result-count>(<?= number_format($total) ?>)</span></h2>
        <div class="data-search"><i class="fa-solid fa-magnifying-glass"></i><input data-ajax-search type="search" value="<?= e($search) ?>" placeholder="Search consultations..."></div>
    </div>
    <div class="table-wrap">
        <table>
            <tr>
                <th>Title</th>
                <th>Status</th>
                <th>Responses</th>
                <th></th>
            </tr>
            <?php if ($consultations->num_rows === 0): ?><tr>
                    <td colspan="4">
                        <div class="empty">No consultations found.</div>
                    </td>
                </tr><?php else: while ($c = $consultations->fetch_assoc()): ?>
                    <tr>
                        <td><?= e($c['title']) ?></td>
                        <td><?= status_badge($c['status']) ?></td>
                        <td><?= $c['responses'] ?></td>
                        <td>
                            <?php if ($c['status'] === 'open'): ?>
                                <form method="post" data-confirm="Confirm this moderation action?"><input type="hidden" name="close_id" value="<?= $c['consultation_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-lock"></i> Close</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
            <?php endwhile;
                    endif; ?>
        </table>
    </div><?php render_pagination($page, $total_pages, $total, $per_page, ['q' => $search]); ?>
</div>
</div>
</div>
<script>
    (function() {
        document.querySelectorAll('[data-open-inline-modal]').forEach(function(b) {
            b.onclick = function() {
                document.getElementById(b.dataset.openInlineModal).classList.add('open');
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