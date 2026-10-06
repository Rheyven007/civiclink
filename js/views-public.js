/* CivicLink — sign in & registration */
(function () {
  const { icon, esc } = UI;
  const QICON = { citizen: 'user', sector_rep: 'people', lgu_officer: 'landmark', admin: 'shield' };
  const quick = () => { const q = DB.quickLogin || []; if (!q.length) return '';
    return `<section class="quick" aria-labelledby="quick-h"><div class="divider"><span id="quick-h">Quick sign-in · accounts from the database</span></div>
      <div class="quick-grid">${q.map(a => `<button type="button" class="quick-acct" data-act="quick-login" data-email="${esc(a.email)}" data-pw="${esc(a.password || '')}"><span class="qa-ic">${icon(QICON[a.role] || 'user')}</span><span class="qa-t"><b>${esc(App.ROLE_LABEL[a.role] || a.role)}</b><em>${esc(a.email)}</em></span>${icon('arrow', 'qa-go')}</button>`).join('')}</div>
      ${q[0].password ? `<p class="muted small center quick-pw">Password for these accounts: <code>${esc(q[0].password)}</code></p>` : ''}</section>`; };
  function frame(inner) {
    const st = DB.stats || { proposals: DB.all('proposals').length, resolved: DB.all('service_requests').filter(r => ['resolved', 'closed'].includes(r.status)).length, decisions: DB.all('decision_logs').length };
    return `<div class="auth">
      <section class="auth-hero" aria-label="About CivicLink">
        <div class="hero-top">${UI.logo({ inverse: true })}<span class="eyebrow light">Community participation platform</span></div>
        <div class="hero-copy"><h1>Your community. Your voice.<br><em>Your local government. Connected.</em></h1>
          <p>Request public services, raise concerns, propose initiatives and take part in consultations — then follow exactly how and why your LGU decides.</p></div>
        <ol class="hero-flow">${[['user', 'Residents', 'report & propose'], ['people', 'Community', 'supports & discusses'], ['landmark', 'Local Government', 'reviews & decides'], ['check', 'Action', 'resolved in the open']].map(([ic, t, d]) => `<li><span>${icon(ic)}</span><b>${t}</b><em>${d}</em></li>`).join('')}</ol>
        <div class="hero-stats"><div><b>${st.proposals}</b><span>Community proposals</span></div><div><b>${st.resolved}</b><span>Requests resolved</span></div><div><b>${st.decisions}</b><span>Public decision records</span></div></div>
        <div class="hero-quote">${icon('seal')}<span>Every LGU decision on CivicLink is published with a written justification you can read.</span></div>
      </section>
      <main class="auth-panel" id="main"><div class="auth-card">${inner}</div><p class="auth-foot">${icon('alert', 'xs')} Emergency? Call <b>911</b> · <a href="#/legal">Laws & Terms</a></p></main></div>`;
  }
  App.route('/login', { public: true, title: 'Sign in', render: () => frame(`
      <span class="eyebrow">Sign in</span><h2>Welcome back</h2><p class="muted">Sign in to take part in your community.</p>
      <form data-form="login" class="stack">
        <div class="form-err" role="alert" hidden></div>
        <label class="field"><span>Email</span><input name="email" type="email" required autocomplete="username" placeholder="you@example.com"></label>
        <label class="field"><span>Password</span><input name="password" type="password" required autocomplete="current-password" placeholder="••••••••"></label>
        <label class="check"><input type="checkbox" name="remember" checked> Keep me signed in</label>
        <button class="btn primary block lg" type="submit">Sign in</button>
      </form>
      ${quick()}
      <p class="muted center mt">New here? <a href="#/register">Create a citizen account</a></p>
      <p class="muted center small terms-line">By signing in, you agree to the <a href="#/legal" data-act="terms-modal">Terms of Use</a> and <a href="#/legal">supported laws</a>.</p>`) });

  App.route('/register', { public: true, title: 'Create account', render: () => frame(`
      <span class="eyebrow">Register as a resident</span><h2>Create your account</h2><p class="muted">Residents can register as citizens. Sector and LGU roles are assigned by the administrator.</p>
      <form data-form="register" class="stack">
        <div class="form-err" role="alert" hidden></div>
        <label class="field"><span>Full name</span><input name="full_name" required placeholder="Juan Dela Cruz"></label>
        <div class="grid2"><label class="field"><span>Email</span><input name="email" type="email" required></label>
        <label class="field"><span>Contact number</span><input name="contact_number" placeholder="09XX XXX XXXX" pattern="[0-9+ ]{7,15}"></label></div>
        <div class="grid2"><label class="field"><span>Barangay</span><input name="barangay" required placeholder="e.g. Poblacion"></label>
        <label class="field"><span>Birthdate</span><input name="birthdate" type="date"></label></div>
        <label class="field"><span>Address</span><input name="address" placeholder="House no., street"></label>
        <label class="field"><span>Sector</span><select name="sector_id">${DB.all('sectors').map(s => `<option value="${s.id}" ${s.sector_name === 'General Public' ? 'selected' : ''}>${esc(s.sector_name)} — ${esc(s.description || '')}</option>`).join('')}</select></label>
        <label class="field"><span>Valid ID <em>(optional now, needed for verification)</em></span><input name="valid_id_file" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf"></label>
        <div class="grid2"><label class="field"><span>Password</span><input name="password" type="password" minlength="8" required></label>
        <label class="field"><span>Confirm password</span><input name="confirm" type="password" required></label></div>
        <div class="legal-note">${icon('scale')}<div><span>Your data is protected under the Data Privacy Act of 2012. Online posts are covered by the Cybercrime Prevention Act and the Safe Spaces Act.</span><div class="law-chips">${['dpa', 'cyber', 'safe', 'philsys'].map(Legal.lawChip).join('')}</div></div></div>
        <label class="check"><input type="checkbox" name="agree" required> <span>I have read and agree to the <a href="#/legal" data-act="terms-modal">Terms of Use</a> and consent to the processing of my personal data under the <b>Data Privacy Act of 2012 (RA 10173)</b>.</span></label>
        <button class="btn primary block lg" type="submit">Create account</button>
      </form>
      <p class="muted center mt">Already registered? <a href="#/login">Sign in</a></p>`) });

  App.form({
    login: (f, d) => { const u = Civic.login(d.email, d.password, !!d.remember); UI.toast('Welcome, ' + u.full_name.split(' ')[0]); App.go(App.HOME[u.role]); },
    register: async (f, d) => { d.valid_id = await UI.readFile(f.valid_id_file.files[0]); d.agree = f.agree.checked; Civic.register(d); const nu = Civic.me(); if (nu) { Legal.accept(nu); try { Civic.audit('Terms Accepted', 'Terms of Use v' + Legal.VERSION + ' (registration)'); } catch (_) {} } UI.toast('Account created'); App.go('#/feed'); }
  });
  App.act({ 'quick-login': (el) => { // fills the real sign-in form so the credentials are visible, then submits it
    const f = document.querySelector('form[data-form=login]'); if (!f) return;
    f.email.value = el.dataset.email; f.password.value = el.dataset.pw || ''; f.remember.checked = false;
    if (!f.password.value) { f.password.focus(); return; }
    el.classList.add('busy'); setTimeout(() => f.requestSubmit(), 180); } });
})();
