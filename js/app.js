/* CivicLink — router, shell, event delegation */
(function () {
  const { icon, esc, avatar, cap } = UI;
  const routes = []; const ACT = {}; const FORM = {};
  const App = {
    route(pattern, def) { const keys = []; const rx = new RegExp('^' + pattern.replace(/:(\w+)/g, (_, k) => { keys.push(k); return '([^/]+)'; }) + '$'); routes.push({ rx, keys, def }); },
    act(o) { Object.assign(ACT, o); }, form(o) { Object.assign(FORM, o); },
    go(h) { if (location.hash === h) render(); else location.hash = h; },
    refresh() { render(true); },
    // posts column scrolls on its own on tablet/desktop; the page scrolls on phones
    scroller() { const m = document.querySelector('.main'); return m && m.scrollHeight > 0 && getComputedStyle(m).overflowY === 'auto' ? m : document.scrollingElement; }
  };
  window.App = App;

  // Navigation is organised around civic activities. Routes, icons and permissions are unchanged — only labels/grouping are presentational.
  const NAV = {
    citizen: [['#/feed', 'home', 'Community Hub', 'Community'], ['#/proposals', 'proposal', 'Proposals & Initiatives', 'Community'], ['#/consultations', 'consult', 'Public Consultations', 'Community'],
      ['#/requests', 'request', 'Service Requests', 'Services'], ['#/complaints', 'complaint', 'Concerns & Complaints', 'Services'], ['#/feedback', 'star', 'Service Feedback', 'Services'],
      ['#/notifications', 'bell', 'Notifications', 'My Activity'], ['#/profile', 'user', 'My Civic Profile', 'My Activity']],
    sector_rep: [['#/feed', 'home', 'Community Hub', 'Community'], ['#/sector', 'layers', 'Sector Hub', 'Community'], ['#/sector/endorse', 'award', 'Endorsements', 'Community'], ['#/proposals', 'proposal', 'Proposals & Initiatives', 'Community'], ['#/consultations', 'consult', 'Public Consultations', 'Community'],
      ['#/requests', 'request', 'Service Requests', 'Services'], ['#/complaints', 'complaint', 'Concerns & Complaints', 'Services'], ['#/feedback', 'star', 'Service Feedback', 'Services'],
      ['#/notifications', 'bell', 'Notifications', 'My Activity'], ['#/profile', 'user', 'My Civic Profile', 'My Activity']],
    lgu_officer: [['#/lgu', 'gauge', 'Overview', 'Operations'], ['#/lgu/cases', 'inbox', 'Case Management', 'Operations'], ['#/lgu/sla', 'clock', 'SLA & Performance', 'Operations'], ['#/admin/reports', 'chart', 'Reports', 'Operations'],
      ['#/feed', 'home', 'Community Hub', 'Community'], ['#/consultations', 'consult', 'Public Consultations', 'Community'], ['#/notifications', 'bell', 'Notifications', 'My Activity'], ['#/profile', 'user', 'My Profile', 'My Activity']],
    admin: [['#/admin', 'gauge', 'Overview', 'Administration'], ['#/admin/users', 'users', 'Users', 'Administration'], ['#/admin/sectors', 'layers', 'Sectors', 'Administration'], ['#/admin/consultations', 'consult', 'Consultations', 'Administration'], ['#/admin/reports', 'chart', 'Reports', 'Administration'], ['#/admin/audit', 'shield', 'Audit Logs', 'Administration'], ['#/admin/settings', 'settings', 'Settings', 'Administration'],
      ['#/lgu/cases', 'inbox', 'All Cases', 'Operations'], ['#/feed', 'home', 'Community Hub', 'Community'], ['#/notifications', 'bell', 'Notifications', 'My Activity']]
  };
  const HOME = { citizen: '#/feed', sector_rep: '#/feed', lgu_officer: '#/lgu', admin: '#/admin' };
  const ROLE_LABEL = { citizen: 'Citizen', sector_rep: 'Sector Representative', lgu_officer: 'LGU Officer', admin: 'Administrator' };
  App.HOME = HOME; App.ROLE_LABEL = ROLE_LABEL;

  function unread(u) { return DB.where('notifications', n => n.user_id === u.id && !n.is_read).length; }

  const MOB = { citizen: ['#/feed', '#/proposals', '#/requests'], sector_rep: ['#/feed', '#/sector', '#/proposals'], lgu_officer: ['#/lgu', '#/lgu/cases', '#/lgu/sla', '#/feed'], admin: ['#/admin', '#/admin/users', '#/admin/reports', '#/lgu/cases'] }; // phones: 2 left · Report · 1 right + Menu
  const SHORT = { 'Community Hub': 'Hub', 'Proposals & Initiatives': 'Proposals', 'Public Consultations': 'Consultations', 'Service Requests': 'Requests', 'Concerns & Complaints': 'Concerns', 'Service Feedback': 'Feedback', 'My Civic Profile': 'Profile', 'My Profile': 'Profile', 'Case Management': 'Cases', 'SLA & Performance': 'SLA', 'Sector Hub': 'Sector', 'Audit Logs': 'Audit', 'All Cases': 'Cases' };
  const tourId = (h) => 'nav-' + h.slice(2).replace(/\//g, '-');
  const groupsOf = (nav) => nav.reduce((g, x) => { (g[x[3]] = g[x[3]] || []).push(x); return g; }, {});

  // community/location context shown in the header
  function context(u) {
    const prof = Civic.profile(u.id); const sec = Civic.sectorOf(u.id);
    if (Civic.isStaff(u)) return { k: u.role === 'admin' ? 'Administration' : 'Local Government', v: u.department || 'City-wide operations', ic: 'landmark' };
    return { k: sec && u.role === 'sector_rep' ? sec.sector_name + ' sector' : 'Your community', v: prof && prof.barangay ? 'Brgy. ' + prof.barangay : 'Set your barangay', ic: 'pin' };
  }
  // important civic alert, derived from real data only
  function civicAlert(u) {
    if (Civic.isStaff(u)) {
      const over = DB.all('service_requests').filter(r => Civic.sla(r).overdue).length;
      return over ? { tone: 'emergency', ic: 'clock', html: `<b>${over} service request${over > 1 ? 's are' : ' is'} past the SLA target.</b> Review overdue cases.`, href: '#/lgu/sla', cta: 'View SLA' } : null;
    }
    const c = DB.where('consultations', Civic.isOpen).sort((a, b) => new Date(a.end_date) - new Date(b.end_date))[0];
    if (!c) return null;
    const days = Math.ceil((new Date(c.end_date + 'T23:59:59') - new Date()) / 864e5);
    return { tone: 'info', ic: 'megaphone', html: `<b>Public consultation open:</b> ${esc(c.title)} <span class="ca-when">· ${days > 1 ? 'closes in ' + days + ' days' : 'closes today'}</span>`, href: '#/consultations/' + c.id, cta: 'Share your view' };
  }

  function shell(u, view, html, rail, path) {
    const nav = NAV[u.role];
    const n = unread(u);
    const best = nav.map(x => x[0].slice(1)).filter(p => path === p || path.startsWith(p + '/')).sort((a, b) => b.length - a.length)[0];
    const active = (h) => h.slice(1) === best;
    const cur = nav.find(x => active(x[0]));
    const sec = Civic.sectorOf(u.id); const prof = Civic.profile(u.id);
    const canCreate = u.role === 'citizen' || u.role === 'sector_rep';
    const ctx = context(u); const al = civicAlert(u);
    const dark = document.documentElement.dataset.theme === 'dark';
    const groups = groupsOf(nav);
    const tab = ([h, ic, l]) => `<a href="${h}" class="${active(h) ? 'on' : ''}" ${active(h) ? 'aria-current="page"' : ''} aria-label="${l}" data-tour="${tourId(h)}">${icon(ic)}<span>${SHORT[l] || l}</span>${h === '#/notifications' && n ? `<i class="dot">${n > 9 ? '9+' : n}</i>` : ''}</a>`;
    const ctxLink = ([h, ic, l]) => `<a href="${h}" class="${active(h) ? 'on' : ''}" ${active(h) ? 'aria-current="page"' : ''} data-tour="${tourId(h)}">${icon(ic)}<span>${SHORT[l] || l}</span>${h === '#/notifications' && n ? `<em class="pill">${n}</em>` : ''}</a>`;
    const feedish = view.layout === 'feed';
    const mob = (MOB[u.role] || []).map(h => nav.find(x => x[0] === h)).filter(Boolean);
    return `<div class="shell">
      <a class="skip" href="#main">Skip to main content</a>
      <div class="civic-strip">
        <div class="cs-in"><span class="cs-gov">${icon('landmark')}<span>Official community participation platform of your Local Government Unit</span></span>
          <span class="cs-right"><a href="#/legal">${icon('scale')}<span>Laws &amp; Terms</span></a><span class="cs-911">${icon('alert')}<span>Emergency? Call <b>911</b></span></span></span></div>
      </div>
      <header class="topnav">
        <div class="tn-left"><a class="brand" href="${HOME[u.role]}" aria-label="CivicLink home">${UI.logo()}</a>
          <a class="ctx-chip" href="${Civic.isStaff(u) ? HOME[u.role] : '#/profile'}" title="${esc(ctx.k)}">${icon(ctx.ic)}<span><em>${esc(ctx.k)}</em><b>${esc(ctx.v)}</b></span></a></div>
        <form class="gsearch" data-form="gsearch" role="search" data-tour="search">${icon('search')}<input name="q" placeholder="Search proposals, cases, reference no. (e.g. REQ-0002)" value="${esc(App.lastQ || '')}" autocomplete="off" aria-label="Search CivicLink"></form>
        <div class="tn-right">
          ${canCreate ? `<button class="btn primary tn-create" data-act="compose">${icon('flag')}<span>Report a Concern</span></button>` : ''}
          <button class="icon-btn only-m" data-act="m-search" aria-label="Search">${icon('search')}</button>
          <button class="icon-btn hide-s" data-act="a11y" aria-label="Accessibility settings" title="Accessibility settings">${icon('access')}</button>
          <button class="icon-btn hide-s" data-act="help" aria-label="Help & tutorial" title="Help & tutorial" data-tour="help">${icon('help')}</button>
          <a class="icon-btn bell" href="#/notifications" aria-label="Notifications${n ? ', ' + n + ' unread' : ''}" title="Notifications" data-tour="bell">${icon('bell')}${n ? `<i class="dot">${n > 9 ? '9+' : n}</i>` : ''}</a>
          <button class="tn-me" data-act="me-menu" aria-label="Account menu" data-tour="me">${avatar(u, 34)}<span class="tn-me-t"><b>${esc(u.full_name.split(' ')[0])}</b><em>${ROLE_LABEL[u.role]}</em></span></button>
        </div>
      </header>
      <nav class="ctxnav" aria-label="Primary"><div class="cn-in">${Object.entries(groups).map(([g, items]) => `<div class="cn-group" role="group" aria-label="${esc(g)}"><span class="cn-label">${esc(g)}</span><div class="cn-links">${items.map(ctxLink).join('')}</div></div>`).join('')}</div></nav>
      ${al ? `<div class="civic-alert ca-${al.tone}" role="status"><div class="ca-in">${icon(al.ic)}<span class="ca-txt">${al.html}</span><a class="ca-cta" href="${al.href}">${al.cta} ${icon('arrow')}</a></div></div>` : ''}
      <aside class="side" aria-label="Navigation menu">
        <div class="side-top">${UI.logo()}<button class="icon-btn" data-act="toggle-side" aria-label="Close menu">${icon('x')}</button></div>
        <a class="side-me" href="#/profile">${avatar(u, 42)}<div><b>${esc(u.full_name)} ${prof && prof.verified ? icon('verified', 'vf') : ''}</b><span>${ROLE_LABEL[u.role]}${sec ? ' · ' + esc(sec.sector_name) : u.department ? ' · ' + esc(u.department) : ''}</span></div></a>
        ${canCreate ? `<button class="btn primary block" data-act="compose">${icon('flag')} Report a Concern</button>` : ''}
        ${Object.entries(groups).map(([g, items]) => `<div class="side-sec"><h6>${esc(g)}</h6><nav class="nav">${items.map(([h, ic, l]) => `<a href="${h}" class="${active(h) ? 'on' : ''}" ${active(h) ? 'aria-current="page"' : ''}><span class="nav-ic">${icon(ic)}</span><span>${l}</span>${h === '#/notifications' && n ? `<em class="pill">${n}</em>` : ''}</a>`).join('')}</nav></div>`).join('')}
        <div class="side-sec"><h6>Support</h6>
          <a href="#/legal" class="${path === '/legal' ? 'on' : ''}"><span class="nav-ic">${icon('scale')}</span><span>Laws & Terms</span></a>
          <button data-act="help"><span class="nav-ic">${icon('help')}</span><span>Help & tutorial</span></button>
          <button data-act="a11y"><span class="nav-ic">${icon('access')}</span><span>Accessibility settings</span></button>
          <button data-act="theme"><span class="nav-ic">${icon(dark ? 'sun' : 'moon')}</span><span>${dark ? 'Light mode' : 'Dark mode'}</span></button>
        </div>
        ${window.Legal ? Legal.footer() : ''}
      </aside>
      <div class="side-scrim" data-act="toggle-side"></div>
      <main class="main" id="main" tabindex="-1">
        <div class="content ${rail ? 'with-rail' : 'wide'} ${feedish ? 'feed-layout' : ''}" data-route="${esc((path || '').split('/')[1] || '')}">
          <section class="col">${feedish ? `<h1 class="sr-only">${esc(view.title)}</h1>` : `<div class="page-bar">${cur ? `<span class="crumb">${esc(cur[3])}</span>` : ''}<h1 class="page-title">${esc(view.title)}</h1></div>`}${html}</section>
          ${rail ? `<aside class="rail" aria-label="Community context">${rail}</aside>` : ''}
        </div>
        <footer class="app-foot"><div>${UI.logo()}<p>Your community. Your voice. Your local government. Connected.</p></div>${window.Legal ? Legal.footer() : ''}</footer>
      </main>
      <nav class="tabbar ${canCreate ? 'has-fab' : ''}" aria-label="Quick navigation">${canCreate ? `${mob.slice(0, 2).map(tab).join('')}<span class="fab-slot"><button class="fab" data-act="compose" aria-label="Report a concern">${icon('plus')}</button><em>Report</em></span>${mob.slice(2).map(tab).join('')}` : mob.slice(0, 4).map(tab).join('')}<button data-act="toggle-side" aria-label="Open menu" class="tb-menu" data-tour="tb-menu">${icon('menu')}<span>Menu</span></button></nav>
      <button class="help-fab" data-act="help" aria-label="Help & tutorial">${icon('help')}<span>Help</span></button>
    </div>`;
  }

  /* accessibility preferences (opt-in; never the default experience) */
  const A11Y_KEY = 'civiclink.a11y';
  const A11y = {
    get() { try { return JSON.parse(localStorage.getItem(A11Y_KEY)) || {}; } catch (_) { return {}; } },
    apply(o = A11y.get()) { const d = document.documentElement.dataset; d.text = o.text || ''; d.contrast = o.contrast ? 'high' : ''; d.motion = o.motion ? 'reduce' : ''; d.links = o.links ? 'underline' : ''; d.spacing = o.spacing ? 'wide' : ''; },
    set(k, v) { const o = A11y.get(); o[k] = v; localStorage.setItem(A11Y_KEY, JSON.stringify(o)); A11y.apply(o); DB.setPref('a11y', o); }
  };
  App.A11y = A11y; A11y.apply();
  function a11yPanel() {
    const o = A11y.get(); const dark = document.documentElement.dataset.theme === 'dark';
    const sw = (k, t, d, ic) => `<label class="a11y-row">${icon(ic)}<span><b>${t}</b><em>${d}</em></span><input type="checkbox" class="switch" data-a11y="${k}" ${o[k] ? 'checked' : ''}></label>`;
    UI.modal('Accessibility settings', `<p class="modal-desc">Adjust CivicLink to how you read and navigate. Settings are saved to your account and follow you on any device.</p>
      <fieldset class="a11y-set"><legend>${icon('text')} Text size</legend><div class="seg sm" role="radiogroup" aria-label="Text size">${[['', 'Default'], ['lg', 'Large'], ['xl', 'Extra large']].map(([v, l]) => `<label class="seg-opt"><input type="radio" name="a11y-text" value="${v}" ${(o.text || '') === v ? 'checked' : ''}><span>${l}</span></label>`).join('')}</div></fieldset>
      <fieldset class="a11y-set"><legend>${icon(dark ? 'moon' : 'sun')} Appearance</legend><div class="seg sm" role="radiogroup" aria-label="Color theme">${[['light', 'Light'], ['dark', 'Dark']].map(([v, l]) => `<label class="seg-opt"><input type="radio" name="a11y-theme" value="${v}" ${(dark ? 'dark' : 'light') === v ? 'checked' : ''}><span>${l}</span></label>`).join('')}</div></fieldset>
      <div class="a11y-list">${sw('contrast', 'High contrast', 'Stronger text, borders and focus outlines', 'contrast')}${sw('motion', 'Reduce motion', 'Turn off animations and transitions', 'motion')}${sw('links', 'Underline links', 'Make every link easy to spot', 'link')}${sw('spacing', 'Readable spacing', 'More space between letters, words and lines', 'text')}</div>
      <div class="form-actions"><button class="btn tertiary" data-act="a11y-reset">Reset to default</button><button class="btn primary" data-act="close-modal">Done</button></div>`, { noFocus: true });
    const m = document.getElementById('modal');
    m.querySelectorAll('[data-a11y]').forEach(i => i.onchange = () => A11y.set(i.dataset.a11y, i.checked));
    m.querySelectorAll('[name=a11y-text]').forEach(i => i.onchange = () => A11y.set('text', i.value));
    m.querySelectorAll('[name=a11y-theme]').forEach(i => i.onchange = () => setTheme(i.value));
  }

  function setTheme(t) { document.documentElement.classList.add('theme-anim'); document.documentElement.dataset.theme = t; localStorage.setItem('civiclink.theme', t); DB.setPref('theme', t); setTimeout(() => document.documentElement.classList.remove('theme-anim'), 400); }
  // apply the signed-in user's saved preferences (user_preferences table) once per sign-in
  let prefUser = null;
  function applyPrefs(u) {
    if (!u || prefUser === u.id) return; prefUser = u.id;
    const a = DB.pref('a11y'), t = DB.pref('theme');
    if (a && typeof a === 'object') { localStorage.setItem(A11Y_KEY, JSON.stringify(a)); A11y.apply(a); }
    if (t === 'light' || t === 'dark') { document.documentElement.dataset.theme = t; localStorage.setItem('civiclink.theme', t); }
  }
  function offline(root) {
    root.innerHTML = `<main class="offline" id="main"><div class="offline-card">${UI.logo()}<span class="offline-ic">${icon('alert')}</span>
      <h1>Can't reach the CivicLink database</h1><p>The app needs its PHP API and MySQL/MariaDB database to run.</p>
      <p class="offline-err"><code>${esc(DB.offline)}</code></p>
      <ol class="offline-steps"><li>Start <b>Apache</b> and <b>MySQL</b> in XAMPP or Laragon.</li><li>Import <code>database/civiclink_schema.sql</code> + <code>civiclink_seed.sql</code>, or run <code>database/migrate_v4.sql</code> on your existing database.</li><li>Check the credentials in <code>api/config.php</code>.</li><li>Open CivicLink through the web server (e.g. <code>http://localhost/civiclink/</code>), not as a file.</li></ol>
      <button class="btn primary lg" data-act="retry-conn">${icon('refresh')} Try again</button></div></main>`;
    document.title = 'Connection problem · CivicLink';
  }
  // label table cells with their column header so tables can stack into cards on phones
  function labelTables(root) { root.querySelectorAll('table').forEach(t => { const h = [...t.querySelectorAll('thead th')].map(th => th.textContent.trim()); if (!h.length) return; t.classList.add('stackable'); t.querySelectorAll('tbody tr').forEach(tr => [...tr.children].forEach((td, i) => { if (h[i] && !td.hasAttribute('data-label')) td.setAttribute('data-label', h[i]); })); }); }

  let lastPath = null;
  function render(keepScroll) {
    const path = ((location.hash || '#/').slice(1) || '/').split('?')[0];
    const root = document.getElementById('app');
    if (DB.offline) return offline(root);
    const u = Civic.me();
    applyPrefs(u); if (!u) prefUser = null;
    const y = App.scroller().scrollTop;
    if (!u) {
      if (!['/login', '/register', '/legal'].includes(path)) { location.replace('#/login'); return; }
    } else if (['/', '/login', '/register'].includes(path)) { location.replace(HOME[u.role]); return; }
    let match = null, params = {};
    for (const r of routes) { const m = path.match(r.rx); if (m) { match = r.def; r.keys.forEach((k, i) => params[k] = decodeURIComponent(m[i + 1])); break; } }
    if (!match) { match = { title: 'Not found', render: () => UI.empty('search', 'Page not found', 'The page you are looking for does not exist.', `<a class="btn" href="${u ? HOME[u.role] : '#/login'}">Go home</a>`) }; }
    if (u && match.roles && !match.roles.includes(u.role)) { match = { title: 'Restricted', render: () => UI.empty('lock', 'You don\'t have access to this page', 'This area is limited to other roles.', `<a class="btn" href="${HOME[u.role]}">Back to home</a>`) }; }
    UI.closeModal();
    let html;
    try { html = match.render(params, u); } catch (e) { console.error(e); html = UI.empty('complaint', 'Something went wrong', e.message); }
    if (match.public || !u) { root.innerHTML = html; }
    else { const rail = match.rail ? match.rail(params, u) : null; root.innerHTML = shell(u, typeof match.title === 'function' ? { ...match, title: match.title(params) } : match, html, rail, path); }
    document.title = (typeof match.title === 'function' ? match.title(params) : match.title) + ' · CivicLink';
    if (match.after) match.after(root, params, u);
    if (window.Legal) Legal.check(u);
    if (window.Tour) Tour.refresh();
    const sc = App.scroller(); if (keepScroll === true && lastPath === path) sc.scrollTop = y; else sc.scrollTop = 0;
    labelTables(root);
    if (!(keepScroll === true && lastPath === path)) { const c = root.querySelector('.content, .auth-card, .legal-public'); if (c) { c.classList.add('view-enter'); setTimeout(() => c.classList.remove('view-enter'), 700); } }
    if (window.HScroll) HScroll.scan(root);
    lastPath = path;
  }

  /* global delegation */
  document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-act]'); if (!el) return;
    const fn = ACT[el.dataset.act]; if (!fn) return;
    e.preventDefault();
    try { fn(el, e); } catch (err) { UI.toast(err.message, 'bad'); }
  });
  document.addEventListener('submit', async (e) => {
    const f = e.target.closest('form[data-form]'); if (!f) return;
    e.preventDefault(); const fn = FORM[f.dataset.form]; if (!fn) return;
    const btn = f.querySelector('[type=submit]'); if (btn) btn.disabled = true;
    try { await fn(f, UI.formData(f)); } catch (err) { const box = f.querySelector('.form-err'); if (box) { box.textContent = err.message; box.hidden = false; } else UI.toast(err.message, 'bad'); }
    finally { if (btn && document.body.contains(btn)) btn.disabled = false; }
  });
  document.addEventListener('keydown', e => { if (e.key !== 'Escape') return; UI.closeModal(); const sh = document.querySelector('.shell.side-open'); if (sh) sh.classList.remove('side-open'); });

  App.act({
    'close-modal': () => UI.closeModal(),
    theme: () => { setTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark'); App.refresh(); },
    'retry-conn': () => { DB.connect(); render(); },
    'toggle-side': () => { const sh = document.querySelector('.shell'); const open = sh.classList.toggle('side-open'); const b = sh.querySelector('.tb-menu'); if (b) b.setAttribute('aria-expanded', open); if (open) { const f = sh.querySelector('.side .side-top .icon-btn'); if (f) f.focus(); } },
    'm-search': () => { const t = document.querySelector('.topnav'); t.classList.toggle('searching'); const i = t.querySelector('.gsearch input'); if (t.classList.contains('searching')) i.focus(); },
    go: (el) => App.go(el.dataset.href),
    'me-menu': () => { const u = Civic.me(); UI.modal('Your account', `<div class="acct">${avatar(u, 56)}<div><b>${esc(u.full_name)}</b><p class="muted">${esc(u.email)}</p><span class="badge t-brand">${ROLE_LABEL[u.role]}</span></div></div>
      <div class="menu-list"><a href="#/profile" data-act="close-modal-go" data-href="#/profile">${icon('user')} My civic profile</a><a href="#/notifications" data-act="close-modal-go" data-href="#/notifications">${icon('bell')} Notifications</a><button data-act="a11y">${icon('access')} Accessibility settings</button><button data-act="help">${icon('help')} Help & tutorial</button><a href="#/legal" data-act="close-modal-go" data-href="#/legal">${icon('scale')} Laws & Terms</a><button data-act="theme">${icon(document.documentElement.dataset.theme === 'dark' ? 'sun' : 'moon')} ${document.documentElement.dataset.theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'}</button><button data-act="logout" class="danger-txt">${icon('logout')} Sign out</button></div>`, { noFocus: true }); },
    a11y: () => a11yPanel(),
    'a11y-reset': () => { localStorage.removeItem(A11Y_KEY); A11y.apply({}); DB.setPref('a11y', {}); a11yPanel(); },
    'close-modal-go': (el) => { UI.closeModal(); App.go(el.dataset.href); },
    logout: () => { Civic.logout(); UI.closeModal(); App.go('#/login'); UI.toast('Signed out'); }
  });
  App.form({ gsearch: (f, d) => { App.lastQ = d.q; App.go('#/search/' + encodeURIComponent(d.q || ' ')); } });

  document.documentElement.dataset.theme = localStorage.getItem('civiclink.theme') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  // keep data fresh with other users' changes (server mode)
  function sync() {
    if (DB.offline || document.hidden) return;
    DB.reload().then(changed => {
      if (!changed) return;
      const busy = document.getElementById('modal') || (document.activeElement && /INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName));
      if (!Civic.me() && !/login|register/.test(location.hash)) { location.hash = '#/login'; return; }
      if (!busy) render(true);
    });
  }
  setInterval(sync, 30000); window.addEventListener('focus', sync);
  // header shadow once scrolled; on phones the tab bar slides away while scrolling down and returns when scrolling up
  let lastY = 0, ticking = false;
  window.addEventListener('scroll', () => { if (ticking) return; ticking = true; requestAnimationFrame(() => {
    const y = window.scrollY, b = document.body; b.classList.toggle('scrolled', y > 4);
    if (y > lastY + 6 && y > 160) b.classList.add('scroll-down'); else if (y < lastY - 6 || y < 160) b.classList.remove('scroll-down');
    lastY = y; ticking = false; }); }, { passive: true });
  window.addEventListener('hashchange', () => render());
  window.addEventListener('DOMContentLoaded', () => render());
})();
