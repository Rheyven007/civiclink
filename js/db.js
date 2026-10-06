/* CivicLink — data layer.
   Talks to api/index.php (PHP + MySQL/MariaDB). Data is cached in memory after bootstrap; every write goes to the API.
   There is no browser/demo fallback: if the API or database is unreachable the app shows a connection screen. */
(function () {
  const API = window.CIVICLINK_API || 'api/index.php';
  const TABLES = ['users','citizen_profiles','sectors','proposals','proposal_votes','endorsements','comments',
    'service_requests','complaints','consultations','consultation_responses','decision_logs','feedback',
    'notifications','audit_logs','terms_acceptances'];

/* ---------------- SERVER backend ---------------- */
  let S = null; // { me, stats, settings, data }
  function call(action, body, method) { // synchronous so existing UI code can stay synchronous
    const x = new XMLHttpRequest();
    x.open(method || 'POST', API + '?action=' + action, false);
    x.setRequestHeader('Content-Type', 'application/json'); x.setRequestHeader('X-CivicLink', '1');
    x.withCredentials = true;
    try { x.send(body ? JSON.stringify(body) : null); } catch (e) { throw new Error('Cannot reach the server. Check your connection.'); }
    let j; try { j = JSON.parse(x.responseText); } catch (e) { throw new Error('Server error (' + x.status + '). Check PHP error log.'); }
    if (x.status === 401 && action !== 'login' && S && S.me) { S.me = null; setTimeout(() => { location.hash = '#/login'; }, 0); }
    if (x.status >= 400 || j.error) throw new Error(j.error || 'Request failed (' + x.status + ')');
    return j;
  }
  function take(j) { S = { me: j.me, stats: j.stats, settings: j.settings || { sla: {} }, prefs: j.prefs || {}, quick: j.quick_login || [], data: {} }; TABLES.forEach(t => S.data[t] = (j.data && j.data[t]) || []); }
  const tb = (t) => S.data[t] || (S.data[t] = []);
  const server = {
    mode: 'server',
    all: (t) => tb(t).slice(), find: (t, id) => tb(t).find(x => x.id === Number(id)) || null,
    where: (t, fn) => tb(t).filter(fn), first: (t, fn) => tb(t).find(fn) || null,
    insert(t, row) { const r = call('insert', { table: t, row }).row; tb(t).push(r); return r; },
    insertMany(t, rows) { if (!rows.length) return []; const rs = call('insert_many', { table: t, rows }).rows; tb(t).push(...rs); return rs; },
    update(t, id, patch) { const r = call('update', { table: t, id, patch }).row; const i = tb(t).findIndex(x => x.id === id); if (i >= 0) tb(t)[i] = r; else tb(t).push(r); return r; },
    remove(t, id) { call('delete', { table: t, ids: [id] }); S.data[t] = tb(t).filter(x => x.id !== id); },
    removeWhere(t, fn) { const ids = tb(t).filter(fn).map(x => x.id); if (!ids.length) return; call('delete', { table: t, ids }); S.data[t] = tb(t).filter(x => !ids.includes(x.id)); },
    get settings() { return S.settings; }, get stats() { return S.stats; },
    get quickLogin() { return S.quick || []; },
    pref(k) { return S && S.prefs ? S.prefs[k] : undefined; },
    setPref(k, v) { if (!S || !S.me) return; S.prefs[k] = v; try { call('pref', { key: k, value: v }); } catch (e) { console.warn('Preference not saved:', e.message); } },
    saveSettings(k, v) { S.settings = call('setting', { key: k, value: v }).settings; },
    currentUserId: () => S.me || null,
    login(email, password, remember) { take(call('login', { email, password, remember: !!remember })); return server.find('users', S.me); },
    logout() { try { call('logout', {}); } catch (e) {} take(call('bootstrap', null, 'GET')); },
    register(f) { take(call('register', f)); return server.find('users', S.me); },
    changePassword(current, next) { call('password', { current, next }); },
    reload() { // async refresh of all data (other users' changes)
      return fetch(API + '?action=bootstrap', { credentials: 'include', cache: 'no-store' }).then(r => r.json()).then(j => { if (j.error) return false; const changed = JSON.stringify(j.data) !== JSON.stringify(S.data) || j.me !== S.me; take(j); return changed; }).catch(() => false);
    },
    exportJSON() { return JSON.stringify(S.data, null, 2); }
  };

  /* ---------------- connect ---------------- */
  server.connect = function () {
    try { take(call('bootstrap', null, 'GET')); server.offline = null; }
    catch (e) { S = { me: null, stats: null, settings: { sla: {} }, prefs: {}, quick: [], data: {} }; TABLES.forEach(t => S.data[t] = []); server.offline = e.message; console.error('CivicLink API unavailable:', e.message); }
    return !server.offline;
  };
  server.connect();
  window.DB = server;
})();
