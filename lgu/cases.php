<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('lgu_officer');
$uid = current_user_id();
$type = $_GET['type'] ?? 'proposal';
if (!in_array($type, ['proposal', 'service_request', 'complaint'])) $type = 'proposal';
$page_title = 'Case Management';
$current_page = "lgu/cases.php?type=$type";

$labels = ['proposal' => 'Community Proposals', 'service_request' => 'Service Requests', 'complaint' => 'Complaints & Disputes'];
$tables = ['proposal' => 'proposals', 'service_request' => 'service_requests', 'complaint' => 'complaints'];
$id_cols = ['proposal' => 'proposal_id', 'service_request' => 'request_id', 'complaint' => 'complaint_id'];
$title_cols = ['proposal' => 'title', 'service_request' => 'service_type', 'complaint' => 'subject'];
$table = $tables[$type];
$id_col = $id_cols[$type];
$title_col = $title_cols[$type];

// Handle status update / decision submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $ref_id = (int)$_POST['ref_id'];
  $new_status = $_POST['new_status'];
  $justification = trim($_POST['justification'] ?? '');

  $valid_statuses = [
    'proposal' => ['pending', 'under_review', 'approved', 'rejected', 'implemented'],
    'service_request' => ['submitted', 'in_progress', 'resolved', 'closed', 'cancelled'],
    'complaint' => ['filed', 'investigating', 'mediation', 'resolved', 'dismissed']
  ];
  if (in_array($new_status, $valid_statuses[$type]) && strlen($justification) >= 5) {
    $stmt = $conn->prepare("UPDATE $table SET status=? WHERE $id_col=?");
    $stmt->bind_param('si', $new_status, $ref_id);
    $stmt->execute();

    $decision_map = ['approved' => 'approved', 'rejected' => 'rejected', 'resolved' => 'closed', 'dismissed' => 'rejected', 'implemented' => 'approved', 'closed' => 'closed'];
    $decision = $decision_map[$new_status] ?? 'escalated';
    $stmt = $conn->prepare("INSERT INTO decision_logs (reference_type, reference_id, decided_by, decision, justification) VALUES (?,?,?,?,?)");
    $stmt->bind_param('siiss', $type, $ref_id, $uid, $decision, $justification);
    $stmt->execute();

    // notify the submitter that a decision has been logged for transparency
    $owner_col = 'user_id';
    $stmt = $conn->prepare("SELECT $owner_col uid, $title_col t FROM $table WHERE $id_col=?");
    $stmt->bind_param('i', $ref_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
      $link = $type === 'proposal' ? "/citizen/proposal_view.php?id=$ref_id" : null;
      notify_decision_logged($conn, $type, $ref_id, $row['uid'], $row['t'], $new_status, $link);
    }
    log_audit($conn, $uid, 'Decision Recorded', "$type #$ref_id -> $new_status");
    header("Location: /lgu/cases.php?type=$type&updated=1");
    exit;
  }
}

$valid_statuses = [
  'proposal' => ['pending', 'under_review', 'approved', 'rejected', 'implemented'],
  'service_request' => ['submitted', 'in_progress', 'resolved', 'closed', 'cancelled'],
  'complaint' => ['filed', 'investigating', 'mediation', 'resolved', 'dismissed']
];
$status_filter = $_GET['status'] ?? 'all';
$page = max(1, (int)($_GET['p'] ?? 1));
$per_page = 12;
if ($status_filter !== 'all' && !in_array($status_filter, $valid_statuses[$type], true)) {
  $status_filter = 'all';
}

// Status counts for the selected case type, matching the citizen-side tabs.
$status_counts = ['all' => 0];
foreach ($valid_statuses[$type] as $s) $status_counts[$s] = 0;
$count_sql = "SELECT status, COUNT(*) c FROM $table GROUP BY status";
$count_res = $conn->query($count_sql);
if ($count_res) {
  while ($r = $count_res->fetch_assoc()) {
    if (isset($status_counts[$r['status']])) {
      $status_counts[$r['status']] = (int)$r['c'];
      $status_counts['all'] += (int)$r['c'];
    }
  }
}

$search = trim($_GET['q'] ?? '');
$where_parts = [];
if ($status_filter !== 'all') $where_parts[] = "t.status='" . $conn->real_escape_string($status_filter) . "'";
if ($search) {
  $q = $conn->real_escape_string($search);
  $where_parts[] = "(t.$title_col LIKE '%$q%' OR u.full_name LIKE '%$q%' OR t.status LIKE '%$q%')";
}
$where_sql = $where_parts ? ' WHERE ' . implode(' AND ', $where_parts) : '';
$total_cases = (int)$conn->query("SELECT COUNT(*) c FROM $table t JOIN users u ON t.user_id=u.user_id" . $where_sql)->fetch_assoc()['c'];
$total_pages = max(1, (int)ceil($total_cases / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;

$sql = "SELECT t.*, u.full_name FROM $table t JOIN users u ON t.user_id = u.user_id" . $where_sql . " ORDER BY t.created_at DESC LIMIT $per_page OFFSET $offset";
$cases = $conn->query($sql);

$status_labels = [
  'proposal' => [
    'all' => 'All',
    'pending' => 'Pending',
    'under_review' => 'Under Review',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'implemented' => 'Implemented'
  ],
  'service_request' => [
    'all' => 'All',
    'submitted' => 'Submitted',
    'in_progress' => 'In Progress',
    'resolved' => 'Resolved',
    'closed' => 'Closed',
    'cancelled' => 'Cancelled'
  ],
  'complaint' => [
    'all' => 'All',
    'filed' => 'Filed',
    'investigating' => 'Investigating',
    'mediation' => 'Mediation',
    'resolved' => 'Resolved',
    'dismissed' => 'Dismissed'
  ]
];

require_once __DIR__ . '/../includes/header.php';
?>
<div class="case-tabs-sticky">
  <div class="tabs case-type-tabs">
    <a href="?type=proposal" class="<?= $type === 'proposal' ? 'active' : '' ?>"><i class="fa-solid fa-lightbulb"></i> Proposals</a>
    <a href="?type=service_request" class="<?= $type === 'service_request' ? 'active' : '' ?>"><i class="fa-solid fa-clipboard-list"></i> Service Requests</a>
    <a href="?type=complaint" class="<?= $type === 'complaint' ? 'active' : '' ?>"><i class="fa-solid fa-triangle-exclamation"></i> Complaints</a>
  </div>

  <div class="tabs case-status-tabs">
    <?php foreach ($status_labels[$type] as $key => $label):
      $count = $status_counts[$key] ?? 0;
    ?>
      <a href="?type=<?= urlencode($type) ?>&status=<?= urlencode($key) ?>" class="<?= $status_filter === $key ? 'active' : '' ?>">
        <?= e($label) ?>
        <span class="tab-count"><?= (int)$count ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</div>
<?php if (isset($_GET['updated'])): ?><div class="alert alert-ok"><i class="fa-solid fa-circle-check"></i> Decision recorded and logged for transparency. The citizen has been notified.</div><?php endif; ?>

<div class="card case-summary-card ajax-table-container" data-ajax-endpoint="1">
  <div class="flex-between case-table-heading">
    <div>
      <h2><i class="fa-solid fa-folder-open"></i> <?= $labels[$type] ?></h2>
      <div class="small muted" data-result-count>Showing <?= $total_cases ? $offset + 1 : 0 ?>–<?= min($offset + $per_page, $total_cases) ?> of <?= number_format($total_cases) ?> cases</div>
    </div>
  </div>
  <div class="data-toolbar">
    <div class="data-search"><i class="fa-solid fa-magnifying-glass"></i><input data-ajax-search type="search" value="<?= e($search) ?>" placeholder="Search title, submitter, or status..."></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Title</th>
          <th>Submitted By</th>
          <th>Status</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($cases->num_rows === 0): ?>
          <tr>
            <td colspan="5" class="empty">No cases found.</td>
          </tr>
          <?php else: while ($c = $cases->fetch_assoc()):
            $case_payload = [
              'id' => (int)$c[$id_col],
              'title' => $c[$title_col],
              'status' => $c['status'],
              'description' => $c['description'] ?? $c['details'] ?? '',
              'full_name' => $c['full_name'],
              'created_at' => time_ago($c['created_at'])
            ];
            $case_json = htmlspecialchars(json_encode($case_payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
          ?>
            <tr>
              <td><?= e($c[$title_col]) ?></td>
              <td><?= e($c['full_name']) ?></td>
              <td><?= status_badge($c['status']) ?></td>
              <td class="small muted"><?= time_ago($c['created_at']) ?></td>
              <td><button type="button" class="btn btn-sm case-decide-btn" data-case="<?= $case_json ?>"><i class="fa-solid fa-gavel"></i> Decide</button></td>
            </tr>
        <?php endwhile;
        endif; ?>
      </tbody>
    </table>
  </div>

  <?php render_pagination($page, $total_pages, $total_cases, $per_page, ['type' => $type, 'status' => $status_filter, 'q' => $search]); ?>
</div>

<!-- Decision modal: details and decision form are shown only after clicking Decide. -->
<div class="case-decision-modal" id="caseDecisionModal" aria-hidden="true">
  <div class="case-decision-backdrop" data-close-case-modal></div>
  <div class="case-decision-dialog" role="dialog" aria-modal="true" aria-labelledby="caseDecisionTitle">
    <div class="case-decision-header">
      <div>
        <div class="small muted">Case Decision</div>
        <h2 id="caseDecisionTitle">Decide on Case</h2>
      </div>
      <button type="button" class="modal-close" data-close-case-modal aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="case-decision-body">
      <div class="case-decision-summary">
        <div class="flex-between"><strong id="caseDecisionCaseTitle"></strong><span id="caseDecisionCurrentStatus"></span></div>
        <p id="caseDecisionDescription" class="small muted"></p>
        <div id="caseDecisionSubmittedBy" class="small muted"></div>
      </div>
      <form method="post" id="caseDecisionForm" data-confirm="Record this decision? This will notify the citizen and write to the public decision log.">
        <input type="hidden" name="ref_id" id="caseDecisionRefId" value="">
        <div class="field">
          <label>Update Status</label>
          <select name="new_status" id="caseDecisionStatus" required>
            <?php foreach ($valid_statuses[$type] as $s): ?>
              <option value="<?= $s ?>"><?= ucwords(str_replace('_', ' ', $s)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label><i class="fa-solid fa-scroll"></i> Justification (required for transparency log) *</label>
          <textarea name="justification" required minlength="5" placeholder="Explain the reasoning for this decision..."></textarea>
        </div>
        <div class="case-decision-actions">
          <button type="button" class="btn btn-muted" data-close-case-modal>Cancel</button>
          <button type="submit" class="btn"><i class="fa-solid fa-gavel"></i> Record Decision</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  (function() {
    var modal = document.getElementById('caseDecisionModal');
    if (!modal) return;
    var title = document.getElementById('caseDecisionTitle');
    var caseTitle = document.getElementById('caseDecisionCaseTitle');
    var currentStatus = document.getElementById('caseDecisionCurrentStatus');
    var description = document.getElementById('caseDecisionDescription');
    var submittedBy = document.getElementById('caseDecisionSubmittedBy');
    var refId = document.getElementById('caseDecisionRefId');
    var status = document.getElementById('caseDecisionStatus');
    var form = document.getElementById('caseDecisionForm');
    var previousBodyOverflow = '';

    function statusBadge(value) {
      var label = value.replace(/_/g, ' ').replace(/\b\w/g, function(m) {
        return m.toUpperCase();
      });
      return '<span class="status-badge status-' + value + '">' + label + '</span>';
    }

    function openModal(data) {
      title.textContent = 'Decide on Case';
      caseTitle.textContent = data.title || 'Case';
      currentStatus.innerHTML = statusBadge(data.status || '');
      description.textContent = data.description || 'No description provided.';
      submittedBy.textContent = 'Submitted by ' + (data.full_name || 'Unknown') + ' · ' + (data.created_at || '');
      refId.value = data.id;
      status.value = data.status || status.options[0].value;
      form.reset();
      refId.value = data.id;
      status.value = data.status || status.options[0].value;
      form.dataset.confirmed = '0';
      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');
      previousBodyOverflow = document.body.style.overflow;
      document.body.style.overflow = 'hidden';
      setTimeout(function() {
        status.focus();
      }, 50);
    }

    function closeModal() {
      modal.classList.remove('open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = previousBodyOverflow;
    }
    document.addEventListener('click', function(e) {
      var button = e.target.closest('.case-decide-btn');
      if (!button) return;
      try {
        openModal(JSON.parse(button.getAttribute('data-case')));
      } catch (err) {
        console.error(err);
      }
    });
    modal.querySelectorAll('[data-close-case-modal]').forEach(function(el) {
      el.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && modal.classList.contains('open')) closeModal();
    });
  })();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>