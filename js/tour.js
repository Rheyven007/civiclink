/* CivicLink — guided tour (first-time walkthrough) & help center */
(function () {
  const { icon, esc } = UI;
  const W = (h) => ({ route: h });
  const STEPS = {
    citizen: [
      { title: 'Welcome to CivicLink 👋', body: 'Your digital civic space for working with your community and local government. This 1-minute tour shows you around — replay it anytime from Help.' },
      { ...W('#/feed'), sel: '[data-tour=composer]', title: 'Take civic action', body: 'Request a public service, raise a concern, or propose an initiative for your community — all from here.' },
      { ...W('#/feed'), sel: '[data-tour=stories]', title: 'Civic bulletin', body: 'Open consultations and the latest official LGU decisions, pinned for your community.' },
      { ...W('#/feed'), sel: '[data-tour=feed-tabs]', title: 'Community activity', body: 'Filter by All activity, Gaining support, Proposals, Consultations, and Official notices.' },
      { ...W('#/feed'), sel: '.post [data-tour=post-actions]', title: 'Take a position', body: 'Support or oppose a proposal, join its discussion, or copy the link to share with neighbours.' },
      { ...W('#/feed'), sel: '.post [data-tour=post-menu]', title: 'Report content', body: 'See something that breaks the rules — like libel or someone\'s private data? Report it to the moderators here.' },
      { sel: '[data-tour=nav-requests]', title: 'Track your requests', body: 'Service requests and complaints are private. Follow every status change and the LGU\'s written justification.' },
      { sel: '[data-tour=search]', title: 'Search everything', body: 'Find proposals, consultations, and your cases. Try a reference number like REQ-0002.' },
      { sel: '[data-tour=bell]', title: 'Notifications', body: 'Get notified when the LGU decides on your proposal or updates your case.' },
      { sel: '[data-tour=me]', title: 'Your profile', body: 'Upload a valid ID (e.g. your PhilSys ID) to get the verified badge.' },
      { sel: '[data-tour=help]', title: 'Help, laws & terms', body: 'Replay this tour, read the FAQ, or review the Terms of Use and the laws that protect you.' }
    ],
    sector_rep: [
      { title: 'Welcome, sector representative 👋', body: 'You speak for your sector on CivicLink. Here\'s a quick tour of your tools.' },
      { sel: '[data-tour=nav-sector]', title: 'Sector Hub', body: 'See what your sector members are proposing and how active they are.' },
      { ...W('#/feed'), sel: '[data-tour=composer]', title: 'Take civic action', body: 'You can also submit proposals, service requests, and concerns.' },
      { ...W('#/feed'), sel: '.post [data-tour=post-actions]', title: 'Endorse proposals', body: 'Use Endorse to back a proposal on behalf of your sector with a public statement.' },
      { ...W('#/feed'), sel: '[data-tour=stories]', title: 'Civic bulletin', body: 'Open consultations and recent LGU decisions at a glance.' },
      { sel: '[data-tour=bell]', title: 'Notifications', body: 'Stay updated on decisions that affect your sector.' },
      { sel: '[data-tour=help]', title: 'Help, laws & terms', body: 'Replay this tour or review the laws on sector participation, like RA 7160 and RA 10742.' }
    ],
    lgu_officer: [
      { title: 'Welcome, LGU officer 👋', body: 'This tour shows how you handle cases and keep the community informed.' },
      { ...W('#/lgu'), sel: '[data-tour=nav-lgu]', title: 'Overview', body: 'Your workload, overdue cases, and recent activity at a glance.' },
      { sel: '[data-tour=nav-lgu-cases]', title: 'Case management', body: 'Review proposals, requests, and complaints. Every decision needs a written justification (RA 6713).' },
      { sel: '[data-tour=nav-lgu-sla]', title: 'SLA & performance', body: 'Processing-time targets based on RA 11032. Overdue requests are flagged automatically.' },
      { sel: '[data-tour=nav-feed]', title: 'Community Hub', body: 'See what residents are proposing and discussing, and how they respond to decisions.' },
      { sel: '[data-tour=bell]', title: 'Notifications', body: 'You\'re notified when a case is assigned to you.' },
      { sel: '[data-tour=help]', title: 'Help, laws & terms', body: 'Replay this tour anytime.' }
    ],
    admin: [
      { title: 'Welcome, administrator 👋', body: 'Here\'s a quick look at your admin tools.' },
      { ...W('#/admin'), sel: '[data-tour=nav-admin]', title: 'Overview', body: 'Users, verifications, and open cases at a glance.' },
      { sel: '[data-tour=nav-admin-users]', title: 'Users & verification', body: 'Activate accounts, assign roles and sectors, and verify IDs.' },
      { sel: '[data-tour=nav-admin-reports]', title: 'Reports & analytics', body: 'Trends, SLA compliance, RA 11032 processing-time compliance, and participation. Export or print them.' },
      { sel: '[data-tour=nav-admin-audit]', title: 'Audit logs', body: 'Every important action, including sign-ins, decisions and terms acceptance, is logged here.' },
      { sel: '[data-tour=nav-admin-settings]', title: 'Settings', body: 'Set SLA targets per priority and back up your data.' },
      { sel: '[data-tour=help]', title: 'Help, laws & terms', body: 'Replay this tour anytime.' }
    ]
  };
  const key = (u) => 'civiclink.tour.' + u.id;
  let st = null, pending = false;

  const visible = (el) => { if (!el) return false; const r = el.getBoundingClientRect(); const cs = getComputedStyle(el); return r.width > 0 && r.height > 0 && r.right > 0 && r.left < innerWidth && cs.visibility !== 'hidden' && cs.display !== 'none'; };
  const find = (sel) => [...document.querySelectorAll(sel)].find(visible);
  const wait = (ms) => new Promise(r => setTimeout(r, ms));

  async function show(i) {
    if (!st) return; const steps = st.steps;
    if (i < 0) i = 0; if (i >= steps.length) return end(true);
    st.i = i; const s = steps[i];
    if (s.route && location.hash.split('?')[0] !== s.route) { App.go(s.route); await wait(380); }
    let el = s.sel ? find(s.sel) : null;
    if (s.sel && !el) { // skip missing targets in the same direction
      return show(st.dir < 0 ? i - 1 : i + 1);
    }
    let w = document.querySelector('.tour-wrap');
    if (!w) { w = document.createElement('div'); w.className = 'tour-wrap'; document.body.appendChild(w); requestAnimationFrame(() => w.classList.add('in')); }
    const n = steps.length;
    w.innerHTML = `<div class="tour-shade ${el ? '' : 'full'}"></div>${el ? '<div class="tour-spot"></div>' : ''}
      <div class="tour-tip ${el ? '' : 'center'}" role="dialog" aria-live="polite" aria-label="${esc(s.title)}">
        ${el ? '' : `<div class="tour-hero">${icon('spark')}</div>`}
        <span class="tour-count">Step ${i + 1} of ${n}</span><h4>${esc(s.title)}</h4><p>${esc(s.body)}</p>
        <div class="tour-dots">${steps.map((_, k) => `<i class="${k === i ? 'on' : k < i ? 'done' : ''}"></i>`).join('')}</div>
        <div class="tour-btns"><button class="btn ghost sm" data-t="skip">${i === n - 1 ? 'Close' : 'Skip tour'}</button><span class="push"></span>${i ? '<button class="btn ghost sm" data-t="back">Back</button>' : ''}<button class="btn primary sm" data-t="next">${i === n - 1 ? 'Finish' : i === 0 ? 'Start tour' : 'Next'}</button></div></div>`;
    w.querySelector('[data-t=next]').onclick = () => { st.dir = 1; show(st.i + 1); };
    w.querySelector('[data-t=skip]').onclick = () => end(i === n - 1);
    const b = w.querySelector('[data-t=back]'); if (b) b.onclick = () => { st.dir = -1; show(st.i - 1); };
    st.el = el;
    if (el) { el.scrollIntoView({ block: 'center', behavior: 'instant' in document.documentElement.style ? 'instant' : 'auto' }); await wait(60); }
    place();
    w.querySelector('[data-t=next]').focus();
  }
  function place() {
    const w = document.querySelector('.tour-wrap'); if (!w || !st) return;
    const tip = w.querySelector('.tour-tip'), spot = w.querySelector('.tour-spot'); const el = st.el;
    if (!el || !spot) return;
    const r = el.getBoundingClientRect(); const pad = 8;
    Object.assign(spot.style, { top: r.top - pad + 'px', left: r.left - pad + 'px', width: r.width + pad * 2 + 'px', height: r.height + pad * 2 + 'px' });
    const tw = Math.min(340, innerWidth - 24), th = tip.offsetHeight; tip.style.width = tw + 'px';
    let top, left;
    if (r.bottom + pad + 14 + th < innerHeight) top = r.bottom + pad + 14; else if (r.top - pad - 14 - th > 0) top = r.top - pad - 14 - th; else top = Math.max(12, innerHeight - th - 12);
    left = Math.min(Math.max(12, r.left + r.width / 2 - tw / 2), innerWidth - tw - 12);
    if (r.width < 300 && r.right + 20 + tw < innerWidth && r.height > th * 0.4 && top === Math.max(12, innerHeight - th - 12)) { left = r.right + 20; top = Math.max(12, Math.min(r.top, innerHeight - th - 12)); }
    tip.style.top = top + 'px'; tip.style.left = left + 'px';
  }
  function end(done) {
    const w = document.querySelector('.tour-wrap'); if (w) w.remove(); document.removeEventListener('keydown', onKey);
    if (st && st.u) { localStorage.setItem(key(st.u), '1'); DB.setPref('tour_done', true); try { Civic.audit(done ? 'Tutorial Completed' : 'Tutorial Skipped', App.ROLE_LABEL[st.u.role]); } catch (_) {} }
    st = null; if (done) UI.toast('You\'re all set! Tap Help anytime to replay the tour.');
  }
  function onKey(e) { if (!st) return; if (e.key === 'Escape') end(false); else if (e.key === 'ArrowRight') { st.dir = 1; show(st.i + 1); } else if (e.key === 'ArrowLeft') { st.dir = -1; show(st.i - 1); } }
  function start(from = 0) {
    const u = Civic.me(); if (!u) return; UI.closeModal(); document.querySelectorAll('.remind-wrap').forEach(e => e.remove());
    const steps = STEPS[u.role] || STEPS.citizen;
    st = { u, steps, i: from, dir: 1 }; document.addEventListener('keydown', onKey); show(from);
  }
  addEventListener('resize', () => place()); addEventListener('scroll', () => place(), { passive: true, capture: true });

  function helpCenter() {
    const u = Civic.me(); const steps = STEPS[u.role] || STEPS.citizen;
    const faq = [
      ['Is my service request public?', 'No. Service requests, complaints, ratings, and your ID are only visible to you and authorized LGU personnel. Proposals and comments are public.'],
      ['How long will my request take?', 'Each priority has a service-level target set by the LGU, following RA 11032 (3, 7, or 20 working days depending on complexity). Overdue requests are flagged.'],
      ['How do I get the verified badge?', 'Go to your Profile and upload a government-issued ID, such as your PhilSys National ID. An administrator will review it.'],
      ['What if someone posts my personal information?', 'Contact the LGU administrator or file a complaint under the Data Privacy Act (RA 10173). Administrators can remove proposals, comments and consultation responses.'],
      ['Can I settle a dispute with my neighbor here?', 'You can file it as a complaint. Under the Katarungang Pambarangay law, it may be referred to your barangay Lupon for mediation first.']
    ];
    UI.modal('Help & tutorial', `<div class="help-hero"><div class="help-ic">${icon('help')}</div><div><b>Need a hand, ${esc(u.full_name.split(' ')[0])}?</b><p class="muted small">Take the interactive tour — we'll highlight each feature step by step.</p></div><button class="btn primary" data-act="tour-start">${icon('play')} Start tour</button></div>
      <h5 class="mt">Jump to a feature</h5><div class="help-steps">${steps.map((s, i) => i ? `<button class="help-step" data-act="tour-start" data-i="${i}"><span>${i}</span>${esc(s.title)}${icon('chev')}</button>` : '').join('')}</div>
      <h5 class="mt">Frequently asked questions</h5><div class="faq">${faq.map(([q, a]) => `<details><summary>${esc(q)}</summary><p>${esc(a)}</p></details>`).join('')}</div>
      <div class="help-links"><a class="btn ghost sm" href="#/legal" data-act="close-modal-go" data-href="#/legal">${icon('scale')} Laws & Terms</a><span class="note small">${icon('complaint')} Emergency? Call <b>911</b></span></div>`, { noFocus: true, wide: true });
  }

  App.act({ help: () => helpCenter(), 'tour-start': (el) => start(Number(el.dataset.i || 0)) });
  window.Tour = { start, end, done: (u) => !!(DB.pref('tour_done') || localStorage.getItem(key(u))), auto: (u) => { if (u && !(DB.pref('tour_done') || localStorage.getItem(key(u))) && !st && !pending) { pending = true; setTimeout(() => { pending = false; if (!st && Civic.me()) start(0); }, 350); } }, active: () => !!st, STEPS, refresh: () => { if (st && st.steps[st.i] && st.steps[st.i].sel) { st.el = find(st.steps[st.i].sel); place(); } } };
})();
