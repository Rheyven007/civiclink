<?php
/* CivicLink JSON API — PHP 8 + MySQL/MariaDB (PDO).
   Actions: bootstrap, ping, login, logout, register, password, pref, insert, insert_many, update, delete, setting */
declare(strict_types=1);
require __DIR__ . '/config.php';
if (!function_exists('mb_substr')) { function mb_substr($s, $start, $len = null) { return $len === null ? substr((string)$s, $start) : substr((string)$s, $start, $len); } }
if (!function_exists('mb_strlen')) { function mb_strlen($s) { return strlen((string)$s); } }
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

session_name('civiclink_sid');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();

function out($data, int $code = 200): never { http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
function fail(string $msg, int $code = 400): never { out(['error' => $msg], $code); }
set_exception_handler(function (Throwable $e) {
  $msg = $e instanceof PDOException ? 'Database error: ' . ($e->errorInfo[2] ?? $e->getMessage()) : $e->getMessage();
  fail($msg, 500);
});

try {
  $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_STRINGIFY_FETCHES => false]);
  $pdo->exec("SET time_zone = '+00:00'");
} catch (PDOException $e) { fail('Cannot connect to the database. Check api/config.php. (' . $e->getMessage() . ')', 503); }

/* ---------- schema map: front-end table => [pk, field=>column renames] ---------- */
const T = [
  'users' => ['user_id', []], 'citizen_profiles' => ['profile_id', []], 'sectors' => ['sector_id', []],
  'proposals' => ['proposal_id', []], 'proposal_votes' => ['vote_id', []], 'endorsements' => ['endorsement_id', []],
  'comments' => ['comment_id', []], 'service_requests' => ['request_id', []], 'complaints' => ['complaint_id', ['evidence' => 'evidence_path']],
  'consultations' => ['consultation_id', []], 'consultation_responses' => ['response_id', []], 'decision_logs' => ['decision_id', []],
  'feedback' => ['feedback_id', []], 'notifications' => ['notification_id', []], 'audit_logs' => ['audit_id', []],
  'terms_acceptances' => ['acceptance_id', []],
];
const FILE_COLS = ['proposals' => ['image'], 'service_requests' => ['attachment'], 'complaints' => ['evidence_path'], 'citizen_profiles' => ['valid_id_path']];
const INT_RX = '/(^id$|_id$|^assigned_to$|^decided_by$|^created_by$|^is_read$|^verified$|^hidden$|^rating$|^votes_(up|down)$)/';

function q(string $sql, array $p = []): PDOStatement { global $pdo; $s = $pdo->prepare($sql); $s->execute($p); return $s; }
function has_table(string $t): bool { static $c = []; if (!isset($c[$t])) $c[$t] = (bool)q('SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?', [$t])->fetchColumn(); return $c[$t]; }
function columns(string $t): array { static $c = []; if (!isset($c[$t])) $c[$t] = array_column(q("SHOW COLUMNS FROM `$t`")->fetchAll(), 'Field'); return $c[$t]; }
function me(): ?array { static $u = false; if ($u === false) { $u = null; if (!empty($_SESSION['uid'])) { $u = q('SELECT * FROM users WHERE user_id=?', [$_SESSION['uid']])->fetch() ?: null; if ($u && $u['status'] !== 'active') { $u = null; $_SESSION = []; } } } return $u; }
function need_login(): array { $u = me(); if (!$u) fail('Please sign in again.', 401); return $u; }
function role(): string { return me()['role'] ?? 'guest'; }
function is_staff(): bool { return in_array(role(), ['lgu_officer', 'admin'], true); }
function is_admin(): bool { return role() === 'admin'; }
function ip(): string { return substr($_SERVER['REMOTE_ADDR'] ?? 'unknown', 0, 45); }
function audit(?int $uid, string $action, string $details = ''): void { q('INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?,?,?,?)', [$uid, $action, mb_substr($details, 0, 255), ip()]); }

/* ---------- value conversion ---------- */
function to_iso($v) { return is_string($v) && preg_match('/^\d{4}-\d\d-\d\d \d\d:\d\d:\d\d$/', $v) ? str_replace(' ', 'T', $v) . 'Z' : $v; }
function from_iso($v) {
  if (is_string($v) && preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d/', $v)) { $d = new DateTime($v); $d->setTimezone(new DateTimeZone('UTC')); return $d->format('Y-m-d H:i:s'); }
  return $v;
}
function save_data_url(string $dataUrl, string $prefix): string {
  if (!preg_match('#^data:(image/(png|jpeg|jpg|webp)|application/pdf);base64,(.+)$#s', $dataUrl, $m)) fail('Only JPG, PNG, WEBP or PDF files are allowed.');
  $bin = base64_decode($m[3], true); if ($bin === false) fail('Invalid file data.');
  if (strlen($bin) > MAX_UPLOAD_MB * 1048576) fail('File must be under ' . MAX_UPLOAD_MB . ' MB.');
  $real = class_exists('finfo') ? (new finfo(FILEINFO_MIME_TYPE))->buffer($bin)
    : (str_starts_with($bin, "\x89PNG") ? 'image/png' : (str_starts_with($bin, "\xFF\xD8") ? 'image/jpeg' : (str_starts_with($bin, '%PDF') ? 'application/pdf' : (substr($bin, 8, 4) === 'WEBP' ? 'image/webp' : ''))));
  $ext = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'application/pdf' => 'pdf'][$real] ?? null;
  if (!$ext) fail('File content does not match an allowed type.');
  if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
  $name = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
  if (file_put_contents(UPLOAD_DIR . $name, $bin) === false) fail('Upload folder is not writable.', 500);
  return UPLOAD_URL . $name;
}
function file_in($v, string $prefix) { // {name,type,data:dataURL} -> JSON string with stored URL
  if ($v === null || $v === '') return null;
  if (is_array($v) && isset($v['data'])) {
    $url = str_starts_with((string)$v['data'], 'data:') ? save_data_url($v['data'], $prefix) : (string)$v['data'];
    return json_encode(['name' => mb_substr((string)($v['name'] ?? basename($url)), 0, 120), 'type' => (string)($v['type'] ?? ''), 'data' => $url], JSON_UNESCAPED_SLASHES);
  }
  fail('Invalid attachment.');
}
function file_out($v) {
  if (!is_string($v) || $v === '') return null;
  if ($v[0] === '{') return json_decode($v, true);
  $ext = strtolower(pathinfo($v, PATHINFO_EXTENSION)); // legacy v1 plain path
  return ['name' => basename($v), 'type' => $ext === 'pdf' ? 'application/pdf' : 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext), 'data' => $v];
}
function row_out(string $t, array $r): array {
  [$pk, $ren] = T[$t]; $o = ['id' => (int)$r[$pk]]; $flip = array_flip($ren);
  foreach ($r as $k => $v) {
    if ($k === $pk || $k === 'password_hash') continue;
    if (in_array($k, FILE_COLS[$t] ?? [], true)) $v = file_out($v);
    elseif (is_string($v) && preg_match(INT_RX, $k) && is_numeric($v)) $v = (int)$v;
    else $v = to_iso($v);
    $o[$flip[$k] ?? $k] = $v;
  }
  if (!isset($o['updated_at']) && isset($o['created_at'])) $o['updated_at'] = $o['created_at'];
  return $o;
}
function row_in(string $t, array $f): array { // front-end fields -> db columns (whitelisted)
  [$pk, $ren] = T[$t]; $cols = columns($t); $o = [];
  foreach ($f as $k => $v) {
    $c = $ren[$k] ?? $k;
    if ($c === $pk || $k === 'id' || in_array($c, ['created_at', 'updated_at', 'password_hash'], true) || !in_array($c, $cols, true)) continue;
    if (in_array($c, FILE_COLS[$t] ?? [], true)) $v = file_in($v, $t);
    elseif ($t === 'users' && $c === 'photo' && is_string($v) && str_starts_with($v, 'data:')) $v = save_data_url($v, 'avatar');
    elseif (is_bool($v)) $v = (int)$v;
    elseif (is_array($v)) $v = json_encode($v);
    else $v = from_iso($v);
    $o[$c] = $v;
  }
  return $o;
}
function fetch_row(string $t, int $id): ?array { $r = q('SELECT * FROM `' . $t . '` WHERE `' . T[$t][0] . '`=?', [$id])->fetch(); return $r ?: null; }
function my_sector(): ?int { $s = q('SELECT sector_id FROM citizen_profiles WHERE user_id=?', [me()['user_id']])->fetchColumn(); return $s ? (int)$s : null; }

/* ---------- permissions ---------- */
function prepare_insert(string $t, array $c, array $raw): array {
  $u = need_login(); $uid = (int)$u['user_id']; $r = $u['role'];
  switch ($t) {
    case 'users':
      if (!is_admin()) fail('Only administrators can create users.', 403);
      if (empty($raw['password']) || strlen((string)$raw['password']) < 8) fail('Password must be at least 8 characters.');
      if (q('SELECT 1 FROM users WHERE email=?', [$c['email'] ?? ''])->fetch()) fail('Email already exists.');
      $c['password_hash'] = password_hash((string)$raw['password'], PASSWORD_DEFAULT); return $c;
    case 'citizen_profiles': case 'sectors': if (!is_admin()) fail('Not allowed.', 403); return $c;
    case 'consultations': if (!is_admin()) fail('Only administrators can create consultations.', 403); $c['created_by'] = $uid; return $c;
    case 'proposals': $c['user_id'] = $uid; $c['status'] = 'pending'; return $c;
    case 'service_requests': $c['user_id'] = $uid; $c['status'] = 'submitted'; $c['assigned_to'] = null; return $c;
    case 'complaints': $c['user_id'] = $uid; $c['status'] = 'filed'; $c['assigned_to'] = null; return $c;
    case 'proposal_votes': case 'comments': case 'consultation_responses': case 'feedback':
      if (is_staff() && $t !== 'comments') fail('Staff accounts cannot do this.', 403); $c['user_id'] = $uid; return $c;
    case 'endorsements':
      if ($r !== 'sector_rep') fail('Only sector representatives can endorse.', 403);
      $s = my_sector(); if (!$s) fail('Your account has no sector assigned.'); $c['user_id'] = $uid; $c['sector_id'] = $s; return $c;
    case 'decision_logs':
      $c['decided_by'] = $uid;
      if (is_staff()) return $c;
      $own = q('SELECT 1 FROM service_requests WHERE request_id=? AND user_id=?', [$c['reference_id'] ?? 0, $uid])->fetch();
      if ($own && ($c['reference_type'] ?? '') === 'service_request' && ($c['to_status'] ?? '') === 'cancelled') return $c;
      fail('Only LGU officers may record decisions.', 403);
    case 'notifications': return $c;
    case 'audit_logs': $c['user_id'] = $uid; $c['ip_address'] = ip(); return $c;
    case 'terms_acceptances':
      if (!has_table('terms_acceptances')) fail('Run database/migrate_v4.sql to enable terms records.', 500);
      $c['user_id'] = $uid; $c['ip_address'] = ip(); return $c;
  }
  fail('Unknown table.', 404);
}
function prepare_update(string $t, array $row, array $c, array $raw): array {
  $u = need_login(); $uid = (int)$u['user_id'];
  $only = function (array $allowed) use (&$c) { $c = array_intersect_key($c, array_flip($allowed)); };
  $own = isset($row['user_id']) && (int)$row['user_id'] === $uid;
  switch ($t) {
    case 'users':
      if (is_admin()) {
        if ((int)$row['user_id'] === $uid) unset($c['role'], $c['status']);
        if (!empty($raw['password'])) $c['password_hash'] = password_hash((string)$raw['password'], PASSWORD_DEFAULT);
        if (isset($c['email']) && q('SELECT 1 FROM users WHERE email=? AND user_id<>?', [$c['email'], $row['user_id']])->fetch()) fail('Email already exists.');
        return $c;
      }
      if (!$own) fail('Not allowed.', 403); $only(['full_name', 'contact_number', 'address', 'bio', 'photo']); return $c;
    case 'citizen_profiles':
      if (is_admin()) return $c; if (!$own) fail('Not allowed.', 403);
      $only($u['role'] === 'sector_rep' ? ['barangay', 'birthdate', 'valid_id_path'] : ['barangay', 'birthdate', 'sector_id', 'valid_id_path']); return $c;
    case 'proposals': case 'complaints': if (!is_staff()) fail('Not allowed.', 403); return $c;
    case 'service_requests':
      if (is_staff()) return $c;
      if (!$own || $row['status'] !== 'submitted' || ($c['status'] ?? '') !== 'cancelled') fail('Only submitted requests can be cancelled.', 403);
      $only(['status', 'cancel_reason']); return $c;
    case 'proposal_votes': if (!$own) fail('Not allowed.', 403); $only(['vote_type']); return $c;
    case 'consultation_responses':
      if (is_admin()) { $only(['hidden']); return $c; } if (!$own) fail('Not allowed.', 403); $only(['choice', 'response_text']); return $c;
    case 'notifications': if (!$own) fail('Not allowed.', 403); $only(['is_read']); return $c;
    case 'sectors': case 'consultations': if (!is_admin()) fail('Not allowed.', 403); return $c;
  }
  fail('This record cannot be edited.', 403);
}
function can_delete(string $t, array $row): bool {
  $uid = (int)need_login()['user_id']; $own = isset($row['user_id']) && (int)$row['user_id'] === $uid;
  return match ($t) {
    'proposals' => is_admin() || ($own && $row['status'] === 'pending'),
    'proposal_votes', 'endorsements' => $own,
    'comments' => $own || is_admin(),
    'consultation_responses', 'consultations', 'sectors' => is_admin(),
    default => false,
  };
}

/* ---------- read scope ---------- */
function all_rows(string $t, string $where = '1', array $p = []): array {
  if (!has_table($t)) return [];
  return array_map(fn($r) => row_out($t, $r), q("SELECT * FROM `$t` WHERE $where", $p)->fetchAll()); }
function bootstrap(): array {
  $u = me(); $stats = stats();
  if (!$u) return ['me' => null, 'stats' => $stats, 'settings' => settings(), 'quick_login' => quick_login(), 'data' => ['sectors' => all_rows('sectors')]];
  $uid = (int)$u['user_id']; $staff = is_staff(); $admin = is_admin(); $rep = $u['role'] === 'sector_rep';
  $users = array_map(function ($x) use ($uid, $staff) {
    if (!$staff && $x['id'] !== $uid) { unset($x['email'], $x['contact_number'], $x['address'], $x['last_login']); }
    return $x; }, all_rows('users'));
  $profiles = array_map(function ($x) use ($uid, $admin) { if (!$admin && $x['user_id'] !== $uid) { unset($x['valid_id_path'], $x['birthdate']); } return $x; }, all_rows('citizen_profiles'));
  $d = ['users' => $users, 'citizen_profiles' => $profiles, 'sectors' => all_rows('sectors'), 'proposals' => all_rows('proposals'),
    'proposal_votes' => all_rows('proposal_votes'), 'endorsements' => all_rows('endorsements'), 'comments' => all_rows('comments'),
    'consultations' => all_rows('consultations'), 'notifications' => all_rows('notifications', 'user_id=?', [$uid])];
  if ($staff) {
    foreach (['service_requests', 'complaints', 'decision_logs', 'feedback', 'consultation_responses'] as $t) $d[$t] = all_rows($t);
    $d['audit_logs'] = $admin ? all_rows('audit_logs', '1 ORDER BY audit_id DESC LIMIT 5000') : [];
  } else {
    $scope = $rep ? '1' : 'user_id=?'; $sp = $rep ? [] : [$uid];
    foreach (['service_requests', 'complaints'] as $t) $d[$t] = array_map(function ($x) use ($uid) { if ($x['user_id'] !== $uid) { unset($x['attachment'], $x['evidence'], $x['description'], $x['details'], $x['location']); } return $x; }, all_rows($t, $scope, $sp));
    $d['decision_logs'] = all_rows('decision_logs', "reference_type='proposal' OR (reference_type='service_request' AND reference_id IN (SELECT request_id FROM service_requests WHERE user_id=?)) OR (reference_type='complaint' AND reference_id IN (SELECT complaint_id FROM complaints WHERE user_id=?))", [$uid, $uid]);
    $d['feedback'] = all_rows('feedback', 'user_id=?', [$uid]);
    $d['consultation_responses'] = all_rows('consultation_responses', 'hidden=0 OR user_id=?', [$uid]);
    $d['audit_logs'] = [];
  }
  $d['terms_acceptances'] = $admin ? all_rows('terms_acceptances') : all_rows('terms_acceptances', 'user_id=?', [$uid]);
  return ['me' => $uid, 'stats' => $stats, 'settings' => settings(), 'prefs' => prefs($uid), 'data' => $d];
}
function prefs(int $uid): array {
  if (!has_table('user_preferences')) return [];
  $o = []; foreach (q('SELECT pref_key, pref_value FROM user_preferences WHERE user_id=?', [$uid])->fetchAll() as $r) $o[$r['pref_key']] = json_decode((string)$r['pref_value'], true);
  return $o;
}
/* quick sign-in list (one active account per role) — controlled by QUICK_LOGIN in config.php; turn off in production */
function quick_login(): array {
  if (!defined('QUICK_LOGIN') || !QUICK_LOGIN) return [];
  $o = [];
  foreach (['citizen', 'sector_rep', 'lgu_officer', 'admin'] as $r) {
    $x = q("SELECT full_name, email, role, department FROM users WHERE role=? AND status='active' ORDER BY user_id LIMIT 1", [$r])->fetch();
    if ($x) $o[] = $x + ['password' => defined('QUICK_LOGIN_PASSWORD') ? QUICK_LOGIN_PASSWORD : ''];
  }
  return $o;
}
function stats(): array {
  $r = q("SELECT COUNT(*) total, SUM(status IN ('resolved','closed')) resolved FROM service_requests WHERE status<>'cancelled'")->fetch();
  return ['proposals' => (int)q('SELECT COUNT(*) FROM proposals')->fetchColumn(), 'requests' => (int)$r['total'], 'resolved' => (int)$r['resolved'],
    'decisions' => (int)q('SELECT COUNT(*) FROM decision_logs')->fetchColumn(), 'rating' => (float)q('SELECT COALESCE(ROUND(AVG(rating),1),0) FROM feedback')->fetchColumn(),
    'ratings' => (int)q('SELECT COUNT(*) FROM feedback')->fetchColumn()];
}
function settings(): array {
  $s = ['sla' => ['urgent' => 24, 'high' => 72, 'normal' => 120, 'low' => 240]];
  foreach (q('SELECT setting_key, setting_value FROM settings')->fetchAll() as $r) $s[$r['setting_key']] = json_decode($r['setting_value'], true);
  return $s;
}

/* ---------- router ---------- */
$action = $_GET['action'] ?? 'bootstrap';
$in = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (($_SERVER['HTTP_X_CIVICLINK'] ?? '') !== '1') fail('Bad request.', 400); // CSRF guard: custom header can't be sent cross-site without CORS
  $in = json_decode(file_get_contents('php://input') ?: '{}', true) ?? [];
} elseif ($action !== 'bootstrap' && $action !== 'ping') fail('Use POST.', 405);
$table = $in['table'] ?? '';
if (in_array($action, ['insert', 'insert_many', 'update', 'delete'], true) && !isset(T[$table])) fail('Unknown table.', 404);
if (isset(T[$table]) && !has_table($table)) fail("Table '$table' is missing. Run database/migrate_v4.sql.", 500);

switch ($action) {
  case 'ping': out(['ok' => true, 'db' => DB_NAME]);
  case 'bootstrap': out(bootstrap());

  case 'login':
    $email = trim((string)($in['email'] ?? '')); $pw = (string)($in['password'] ?? '');
    if (($_SESSION['fails'] ?? 0) >= 8 && time() - ($_SESSION['fail_at'] ?? 0) < 300) fail('Too many attempts. Try again in 5 minutes.', 429);
    $u = q('SELECT * FROM users WHERE email=?', [$email])->fetch();
    if (!$u || !password_verify($pw, $u['password_hash'])) { $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1; $_SESSION['fail_at'] = time(); fail('Incorrect email or password.', 401); }
    if ($u['status'] === 'suspended') fail('This account is suspended. Contact the LGU administrator.', 403);
    if ($u['status'] === 'pending') fail('Your account is awaiting verification by an administrator.', 403);
    session_regenerate_id(true); $_SESSION = ['uid' => (int)$u['user_id']];
    if (!empty($in['remember'])) { $p = session_get_cookie_params(); setcookie(session_name(), session_id(), ['expires' => time() + 30 * 86400, 'path' => $p['path'], 'httponly' => true, 'samesite' => 'Lax', 'secure' => $p['secure']]); }
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) q('UPDATE users SET password_hash=? WHERE user_id=?', [password_hash($pw, PASSWORD_DEFAULT), $u['user_id']]);
    q('UPDATE users SET last_login=UTC_TIMESTAMP() WHERE user_id=?', [$u['user_id']]);
    audit((int)$u['user_id'], 'Login', $u['full_name'] . ' signed in');
    out(bootstrap());

  case 'logout':
    if ($u = me()) audit((int)$u['user_id'], 'Logout', $u['full_name']);
    $_SESSION = []; setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/']); session_destroy(); out(['ok' => true]);

  case 'register':
    $f = $in;
    $name = trim((string)($f['full_name'] ?? '')); $email = trim((string)($f['email'] ?? '')); $pw = (string)($f['password'] ?? '');
    if (mb_strlen($name) < 3) fail('Please enter your full name.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Please enter a valid email address.');
    if (strlen($pw) < 8) fail('Password must be at least 8 characters.');
    if (q('SELECT 1 FROM users WHERE email=?', [$email])->fetch()) fail('That email is already registered.');
    $sector = (int)($f['sector_id'] ?? 0); if (!q('SELECT 1 FROM sectors WHERE sector_id=?', [$sector])->fetch()) $sector = null;
    $pdo->beginTransaction();
    q("INSERT INTO users (full_name,email,password_hash,role,contact_number,address,status) VALUES (?,?,?,'citizen',?,?,'active')", [$name, $email, password_hash($pw, PASSWORD_DEFAULT), $f['contact_number'] ?? null, $f['address'] ?? null]);
    $uid = (int)$pdo->lastInsertId();
    $idFile = isset($f['valid_id']) && is_array($f['valid_id']) ? file_in($f['valid_id'], 'validid') : null;
    q('INSERT INTO citizen_profiles (user_id,sector_id,barangay,birthdate,valid_id_path,verified) VALUES (?,?,?,?,?,0)', [$uid, $sector, $f['barangay'] ?? null, ($f['birthdate'] ?? '') ?: null, $idFile]);
    foreach (q("SELECT user_id FROM users WHERE role='admin' AND status='active'")->fetchAll() as $a) q('INSERT INTO notifications (user_id,message,link) VALUES (?,?,?)', [$a['user_id'], "New citizen registered: $name — verify their ID.", '#/admin/users']);
    q('INSERT INTO notifications (user_id,message,link) VALUES (?,?,?)', [$uid, 'Welcome to CivicLink! Complete your profile and upload a valid ID to get verified.', '#/profile']);
    audit($uid, 'Registration', "New citizen account: $email");
    $pdo->commit();
    session_regenerate_id(true); $_SESSION = ['uid' => $uid];
    out(bootstrap());

  case 'password':
    $u = need_login();
    if (!password_verify((string)($in['current'] ?? ''), $u['password_hash'])) fail('Current password is incorrect.');
    if (strlen((string)($in['next'] ?? '')) < 8) fail('New password must be at least 8 characters.');
    q('UPDATE users SET password_hash=? WHERE user_id=?', [password_hash((string)$in['next'], PASSWORD_DEFAULT), $u['user_id']]);
    audit((int)$u['user_id'], 'Password Changed', $u['email']); out(['ok' => true]);

  case 'pref':
    $u = need_login();
    if (!has_table('user_preferences')) out(['ok' => false, 'error_hint' => 'Run database/migrate_v4.sql']);
    $k = preg_replace('/[^a-z0-9_]/', '', (string)($in['key'] ?? '')); if (!$k || strlen($k) > 40) fail('Invalid preference.');
    $v = json_encode($in['value'] ?? null); if (strlen($v) > 2000) fail('Preference is too large.');
    q('INSERT INTO user_preferences (user_id, pref_key, pref_value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE pref_value=VALUES(pref_value), updated_at=UTC_TIMESTAMP()', [$u['user_id'], $k, $v]);
    out(['ok' => true]);

  case 'setting':
    need_login(); if (!is_admin()) fail('Not allowed.', 403);
    $k = preg_replace('/[^a-z_]/', '', (string)($in['key'] ?? '')); if (!$k) fail('Missing key.');
    q('INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)', [$k, json_encode($in['value'])]);
    out(['ok' => true, 'settings' => settings()]);

  case 'insert':
  case 'insert_many':
    $rows = $action === 'insert' ? [$in['row'] ?? []] : ($in['rows'] ?? []);
    if (count($rows) > 1000) fail('Too many rows.');
    $res = []; $pdo->beginTransaction();
    foreach ($rows as $raw) {
      $c = prepare_insert($table, row_in($table, (array)$raw), (array)$raw);
      if (!$c) fail('Nothing to save.');
      $cols = array_keys($c);
      q('INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')', array_values($c));
      $res[] = row_out($table, fetch_row($table, (int)$pdo->lastInsertId()));
    }
    $pdo->commit();
    out($action === 'insert' ? ['row' => $res[0]] : ['rows' => $res]);

  case 'update':
    $id = (int)($in['id'] ?? 0); $row = fetch_row($table, $id); if (!$row) fail('Record not found.', 404);
    $raw = (array)($in['patch'] ?? []);
    $c = prepare_update($table, $row, row_in($table, $raw), $raw);
    if ($c) {
      $set = implode(',', array_map(fn($k) => "`$k`=?", array_keys($c)));
      if (in_array('updated_at', columns($table), true)) $set .= ',`updated_at`=UTC_TIMESTAMP()';
      q("UPDATE `$table` SET $set WHERE `" . T[$table][0] . '`=?', [...array_values($c), $id]);
    }
    out(['row' => row_out($table, fetch_row($table, $id))]);

  case 'delete':
    $ids = array_map('intval', (array)($in['ids'] ?? [])); $done = [];
    foreach ($ids as $id) { $row = fetch_row($table, $id); if (!$row) { $done[] = $id; continue; } if (!can_delete($table, $row)) fail('You cannot delete this record.', 403);
      q('DELETE FROM `' . $table . '` WHERE `' . T[$table][0] . '`=?', [$id]); $done[] = $id; }
    out(['deleted' => $done]);
}
fail('Unknown action.', 404);
