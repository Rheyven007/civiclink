// Optional: in-memory stand-in for api/index.php so you can run the Cypress suite without XAMPP/MySQL.
// Usage (from the CivicLink folder):  node cypress/mock-server.js . 3000
const http = require('http'), fs = require('fs'), path = require('path'), crypto = require('crypto');
const ROOT = require('path').resolve(process.argv[2] || '.'); const PORT = Number(process.argv[3] || 3000);
const now = () => new Date().toISOString().replace(/\.\d+Z$/, 'Z');
const today = new Date().toISOString().slice(0, 10);
const plus = (d) => new Date(Date.now() + d * 864e5).toISOString().slice(0, 10);
const TABLES = ['users','citizen_profiles','sectors','proposals','proposal_votes','endorsements','comments','service_requests','complaints','consultations','consultation_responses','decision_logs','feedback','notifications','audit_logs','terms_acceptances'];
let D, seq, sessions = {}, prefs = {}, pw = {};
function reset() {
  D = {}; TABLES.forEach(t => D[t] = []); seq = {}; sessions = {}; prefs = {}; pw = {};
  const ins = (t, r) => { seq[t] = (seq[t] || 0) + 1; const row = { id: seq[t], created_at: now(), updated_at: now(), ...r }; D[t].push(row); return row; };
  ins('sectors', { sector_name: 'General Public', description: 'All residents' });
  ins('sectors', { sector_name: 'Youth', description: 'Ages 15-30' });
  const u = (full_name, email, role, department) => { const r = ins('users', { full_name, email, role, status: 'active', department: department || null, contact_number: '09171234567', address: 'Poblacion', bio: '', photo: null, last_login: null }); pw[email] = 'password'; return r; };
  const c = u('Juan Dela Cruz', 'citizen@civiclink.gov', 'citizen');
  const s = u('Maria Santos', 'sector@civiclink.gov', 'sector_rep');
  const o = u('Pedro Reyes', 'officer@civiclink.gov', 'lgu_officer', 'Engineering Office');
  u('Ana Admin', 'admin@civiclink.gov', 'admin', 'City Administrator');
  const c2 = u('Liza Cruz', 'citizen2@civiclink.gov', 'citizen');
  ins('citizen_profiles', { user_id: c.id, sector_id: 1, barangay: 'Poblacion', birthdate: '1990-01-01', valid_id_path: null, verified: 1 });
  ins('citizen_profiles', { user_id: s.id, sector_id: 2, barangay: 'Poblacion', birthdate: '1995-01-01', valid_id_path: null, verified: 1 });
  ins('citizen_profiles', { user_id: c2.id, sector_id: 2, barangay: 'San Isidro', birthdate: '1999-01-01', valid_id_path: null, verified: 0 });
  ins('proposals', { user_id: c2.id, title: 'Covered waiting sheds on J.P. Rizal', description: 'Build three covered waiting sheds along J.P. Rizal street.', justification: 'Commuters wait under the sun and rain.', expected_benefits: 'Safer, more comfortable commutes.', category: 'transport', status: 'pending', image: null });
  ins('consultations', { title: 'Barangay budget priorities 2027', description: 'Tell us what to prioritise.', start_date: plus(-3), end_date: plus(10), status: 'open', created_by: 4 });
  ins('service_requests', { user_id: c2.id, service_type: 'Garbage collection', description: 'Missed garbage collection for two weeks.', location: 'Purok 3', department: 'City Environment Office', priority: 'normal', status: 'submitted', assigned_to: null, attachment: null });
  D._ins = ins;
}
reset();
const ins = (t, r) => D._ins(t, r);
const json = (res, code, o, hdr = {}) => { res.writeHead(code, { 'Content-Type': 'application/json', 'Cache-Control': 'no-store', ...hdr }); res.end(JSON.stringify(o)); };
const sid = (req) => { const m = /civiclink_sid=([a-f0-9]+)/.exec(req.headers.cookie || ''); return m && sessions[m[1]] ? m[1] : null; };
function me(req) { const s = sid(req); return s ? D.users.find(u => u.id === sessions[s]) : null; }
function quick() { return ['citizen','sector_rep','lgu_officer','admin'].map(r => D.users.find(u => u.role === r && u.status === 'active')).filter(Boolean).map(u => ({ full_name: u.full_name, email: u.email, role: u.role, department: u.department, password: 'password' })); }
function stats() { const r = D.service_requests.filter(x => x.status !== 'cancelled'); return { proposals: D.proposals.length, requests: r.length, resolved: r.filter(x => ['resolved','closed'].includes(x.status)).length, decisions: D.decision_logs.length, rating: 0, ratings: D.feedback.length }; }
function boot(u) {
  if (!u) return { me: null, stats: stats(), settings: { sla: { urgent: 24, high: 72, normal: 120, low: 240 } }, quick_login: quick(), data: { sectors: D.sectors } };
  const data = {}; TABLES.forEach(t => data[t] = D[t]);
  data.notifications = D.notifications.filter(n => n.user_id === u.id);
  if (!['lgu_officer','admin'].includes(u.role)) data.audit_logs = [];
  return { me: u.id, stats: stats(), settings: { sla: { urgent: 24, high: 72, normal: 120, low: 240 } }, prefs: prefs[u.id] || {}, data };
}
function api(req, res, body) {
  const action = new URL(req.url, 'http://x').searchParams.get('action') || 'bootstrap';
  const u = me(req); const fail = (m, c = 400) => json(res, c, { error: m });
  if (action === 'bootstrap') return json(res, 200, boot(u));
  if (action === '__reset') { reset(); return json(res, 200, { ok: true }); }
  if (action === 'login') {
    const usr = D.users.find(x => x.email === body.email);
    if (!usr || pw[usr.email] !== body.password) return fail('Incorrect email or password.', 401);
    const s = crypto.randomBytes(8).toString('hex'); sessions[s] = usr.id;
    return json(res, 200, boot(usr), { 'Set-Cookie': `civiclink_sid=${s}; Path=/; HttpOnly; SameSite=Lax` });
  }
  if (action === 'logout') { const s = sid(req); if (s) delete sessions[s]; return json(res, 200, { ok: true }, { 'Set-Cookie': 'civiclink_sid=; Path=/; Max-Age=0' }); }
  if (action === 'register') {
    if (D.users.find(x => x.email === body.email)) return fail('That email is already registered.');
    const nu = ins('users', { full_name: body.full_name, email: body.email, role: 'citizen', status: 'active', department: null, contact_number: body.contact_number || null, address: body.address || null, bio: '', photo: null });
    pw[nu.email] = body.password; ins('citizen_profiles', { user_id: nu.id, sector_id: Number(body.sector_id) || null, barangay: body.barangay, birthdate: body.birthdate || null, valid_id_path: null, verified: 0 });
    const s = crypto.randomBytes(8).toString('hex'); sessions[s] = nu.id;
    return json(res, 200, boot(nu), { 'Set-Cookie': `civiclink_sid=${s}; Path=/; HttpOnly; SameSite=Lax` });
  }
  if (!u) return fail('Please sign in again.', 401);
  if (action === 'pref') { (prefs[u.id] = prefs[u.id] || {})[body.key] = body.value; return json(res, 200, { ok: true }); }
  if (action === 'setting') return json(res, 200, { ok: true, settings: { sla: body.value } });
  if (action === 'password') return json(res, 200, { ok: true });
  const t = body.table; if (!D[t] || t.startsWith('_')) return fail('Unknown table.', 404);
  const staff = ['lgu_officer','admin'].includes(u.role);
  const prep = (r) => { r = { ...r }; delete r.id;
    if (['proposals','service_requests','complaints','proposal_votes','comments','consultation_responses','feedback','endorsements','audit_logs','terms_acceptances'].includes(t)) r.user_id = u.id;
    if (t === 'decision_logs') { if (!staff && r.to_status !== 'cancelled') throw new Error('Only LGU officers may record decisions.'); r.decided_by = u.id; }
    if (t === 'endorsements') { if (u.role !== 'sector_rep') throw new Error('Only sector representatives can endorse.'); r.sector_id = D.citizen_profiles.find(p => p.user_id === u.id).sector_id; }
    for (const k of ['image','attachment','evidence','valid_id_path']) if (r[k] && r[k].data) r[k] = { name: r[k].name, type: r[k].type, data: 'uploads/mock.png' };
    return r; };
  try {
    if (action === 'insert') return json(res, 200, { row: ins(t, prep(body.row || {})) });
    if (action === 'insert_many') return json(res, 200, { rows: (body.rows || []).map(r => ins(t, prep(r))) });
    if (action === 'update') { const row = D[t].find(x => x.id === Number(body.id)); if (!row) return fail('Record not found.', 404);
      if (['proposals','complaints'].includes(t) && !staff) return fail('Not allowed.', 403);
      Object.assign(row, body.patch, { updated_at: now() }); return json(res, 200, { row }); }
    if (action === 'delete') { D[t] = D[t].filter(x => !(body.ids || []).includes(x.id)); return json(res, 200, { deleted: body.ids }); }
  } catch (e) { return fail(e.message, 403); }
  return fail('Unknown action.', 404);
}
const MIME = { '.html': 'text/html', '.js': 'text/javascript', '.css': 'text/css', '.png': 'image/png', '.svg': 'image/svg+xml', '.json': 'application/json', '.woff2': 'font/woff2' };
http.createServer((req, res) => {
  const p = decodeURIComponent(new URL(req.url, 'http://x').pathname);
  if (p.endsWith('/api/index.php')) { let b = ''; req.on('data', c => b += c); req.on('end', () => { let body = {}; try { body = b ? JSON.parse(b) : {}; } catch (_) {} api(req, res, body); }); return; }
  let f = path.join(ROOT, p === '/' ? 'index.html' : p); if (!f.startsWith(ROOT) || !fs.existsSync(f) || fs.statSync(f).isDirectory()) { res.writeHead(404); return res.end('nf'); }
  res.writeHead(200, { 'Content-Type': MIME[path.extname(f)] || 'application/octet-stream' }); fs.createReadStream(f).pipe(res);
}).listen(PORT, () => console.log('mock on', PORT));
