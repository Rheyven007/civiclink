/* CivicLink — UI helpers */
(function () {
  const P = { // stroke icons (24px grid)
    home: '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/>',
    proposal: '<path d="M9 18h6"/><path d="M10 21h4"/><path d="M12 3a6 6 0 0 0-3.6 10.8c.6.5 1.1 1.3 1.1 2.2h5c0-.9.5-1.7 1.1-2.2A6 6 0 0 0 12 3Z"/>',
    request: '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4Z"/>',
    complaint: '<path d="M12 3 2 20h20L12 3Z"/><path d="M12 10v4"/><path d="M12 17.5h.01"/>',
    consult: '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20.5l1.4-4.6A8 8 0 1 1 21 12Z"/><path d="M8.5 11h.01M12 11h.01M15.5 11h.01"/>',
    bell: '<path d="M6 9a6 6 0 1 1 12 0c0 6 2.5 7.5 2.5 7.5h-17S6 15 6 9Z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
    star: '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9L12 3Z"/>',
    user: '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    users: '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8"/><path d="M18 14a6.5 6.5 0 0 1 3.5 6"/>',
    shield: '<path d="M12 3 4 6v6c0 4.5 3.4 8.2 8 9 4.6-.8 8-4.5 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/>',
    chart: '<path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-7"/><path d="M22 20H2"/>',
    gauge: '<path d="M12 14 16 9"/><path d="M3.3 17a10 10 0 1 1 17.4 0"/><circle cx="12" cy="14" r="1.5"/>',
    list: '<path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/>',
    layers: '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/>',
    logout: '<path d="M15 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4"/><path d="M10 17l-5-5 5-5"/><path d="M5 12h11"/>',
    search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    check: '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
    x: '<path d="M6 6l12 12M18 6 6 18"/>',
    up: '<path d="M7 11v9H4v-9h3Z"/><path d="M7 11l4-8c1.7 0 3 1.3 3 3v3h5a2 2 0 0 1 2 2.3l-1.2 7A2 2 0 0 1 17.8 20H7"/>',
    down: '<path d="M7 13V4H4v9h3Z"/><path d="M7 13l4 8c1.7 0 3-1.3 3-3v-3h5a2 2 0 0 0 2-2.3l-1.2-7A2 2 0 0 0 17.8 4H7"/>',
    award: '<circle cx="12" cy="9" r="6"/><path d="m8.5 14-1.5 7 5-3 5 3-1.5-7"/>',
    clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
    moon: '<path d="M20 14.5A8 8 0 0 1 9.5 4 8 8 0 1 0 20 14.5Z"/>',
    building: '<path d="M4 21V5l8-2v18"/><path d="M12 8h8v13"/><path d="M2 21h20"/><path d="M7 8h2M7 12h2M7 16h2M15 12h2M15 16h2"/>',
    download: '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M4 21h16"/>',
    printer: '<path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/>',
    eye: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    eyeoff: '<path d="M3 3l18 18"/><path d="M10.6 5.1A10 10 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4.1M6.6 6.6A17 17 0 0 0 2 12s3.5 7 10 7a9.700 9.700 0 0 0 5.4-1.6"/>',
    trash: '<path d="M4 7h16"/><path d="M10 11v6M14 11v6"/><path d="M6 7l1 14h10l1-14"/><path d="M9 7V4h6v3"/>',
    edit: '<path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/>',
    chev: '<path d="m9 6 6 6-6 6"/>',
    back: '<path d="m15 6-6 6 6 6"/>',
    send: '<path d="M21 3 10 14"/><path d="M21 3 14 21l-4-7-7-4 18-7Z"/>',
    clip: '<path d="M21 11.5 12.5 20a5 5 0 0 1-7-7L14 4.5a3.5 3.5 0 0 1 5 5L10.5 18a2 2 0 0 1-3-3L15 7.5"/>',
    flag: '<path d="M5 21V4"/><path d="M5 4h11l-2 4 2 4H5"/>',
    activity: '<path d="M3 12h4l3-8 4 16 3-8h4"/>',
    inbox: '<path d="M3 13h5l1.5 3h5L16 13h5"/><path d="M5.5 5h13L21 13v6H3v-6l2.5-8Z"/>',
    lock: '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
    settings: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
    pin: '<path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12Z"/><circle cx="12" cy="9" r="2.5"/>',
    msg: '<path d="M4 5h16v11H8l-4 4V5Z"/>',
    file: '<path d="M14 3H6v18h12V7l-4-4Z"/><path d="M14 3v4h4"/>',
    gavel: '<path d="m14 5 5 5"/><path d="m11 8 5 5"/><path d="m12.5 6.5-6 6 5 5 6-6"/><path d="M4 20h9"/>',
    spark: '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M6 18l2.5-2.5M15.5 8.5 18 6"/>',
    menu: '<path d="M4 6h16M4 12h16M4 18h16"/>',
    dots: '<circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/>',
    help: '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.6 2.6 0 0 1 5 1c0 1.8-2.5 2.2-2.5 4"/><path d="M12 17.5h.01"/>',
    scale: '<path d="M12 3v18"/><path d="M7 21h10"/><path d="M4 7h16"/><path d="m4 7-2.5 6a3 3 0 0 0 5 0L4 7Z"/><path d="m20 7-2.5 6a3 3 0 0 0 5 0L20 7Z"/>',
    book: '<path d="M4 4h6a3 3 0 0 1 3 3v13a2 2 0 0 0-2-2H4V4Z"/><path d="M20 4h-6a3 3 0 0 0-3 3v13a2 2 0 0 1 2-2h7V4Z"/>',
    image: '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-9 9"/>',
    trend: '<path d="m3 17 6-6 4 4 8-8"/><path d="M14 7h7v7"/>',
    play: '<path d="M7 4v16l13-8L7 4Z"/>',
    report: '<path d="M5 21V4"/><path d="M5 4h11l-2 4 2 4H5"/>',
    megaphone: '<path d="M3 10v4a1 1 0 0 0 1 1h2l5 4V5L6 9H4a1 1 0 0 0-1 1Z"/><path d="M15 9.5a3.5 3.5 0 0 1 0 5"/><path d="M18 7a7 7 0 0 1 0 10"/>',
    calendar: '<rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
    landmark: '<path d="M3 9.5 12 4l9 5.5"/><path d="M5 10v8M9.5 10v8M14.5 10v8M19 10v8"/><path d="M3 21h18"/><path d="M4 18h16"/>',
    seal: '<circle cx="12" cy="10" r="6"/><path d="m9.5 10 1.8 1.8 3.2-3.3"/><path d="m8.5 15.2-1.5 5.8 5-2.2 5 2.2-1.5-5.8"/>',
    people: '<circle cx="8" cy="9" r="3"/><circle cx="16.5" cy="9.5" r="2.5"/><path d="M2.5 19a5.5 5.5 0 0 1 11 0"/><path d="M14 14.2a4.6 4.6 0 0 1 7.5 3.8"/>',
    map: '<path d="m9 4-6 2.5v13.5l6-2.5 6 2.5 6-2.5V4l-6 2.5L9 4Z"/><path d="M9 4v13.5M15 6.5V20"/>',
    access: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="7.6" r="1.3"/><path d="M7.5 10.2 12 11l4.5-.8"/><path d="m12 11 0 3-2.2 4.2M12 14l2.2 4.2"/>',
    link: '<path d="M10 14a4.5 4.5 0 0 0 6.4 0l3-3a4.5 4.5 0 0 0-6.4-6.4l-1 1"/><path d="M14 10a4.5 4.5 0 0 0-6.4 0l-3 3a4.5 4.5 0 0 0 6.4 6.4l1-1"/>',
    arrow: '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
    discuss: '<path d="M14 4H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h1v3.5L10 14h4a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Z"/><path d="M16 8h3a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2h-1v3l-3.5-3H11"/>',
    alert: '<path d="M12 3v2"/><path d="M5 19V13a7 7 0 0 1 14 0v6"/><path d="M3 19h18v2H3z"/><path d="M12 10v5"/><path d="m4.2 6.2 1.4 1.4M19.8 6.2l-1.4 1.4"/>',
    route: '<circle cx="6" cy="18" r="2.5"/><circle cx="18" cy="6" r="2.5"/><path d="M8.5 18H16a3 3 0 0 0 0-6H8a3 3 0 0 1 0-6h7.5"/>',
    text: '<path d="M4 7V5h11v2"/><path d="M9.5 5v14"/><path d="M7.5 19h4"/><path d="M14 12v-1h7v1"/><path d="M17.5 11v8"/><path d="M16 19h3"/>',
    contrast: '<circle cx="12" cy="12" r="9"/><path d="M12 3v18a9 9 0 0 0 0-18Z" fill="currentColor"/>',
    refresh: '<path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 4v7h-7"/>',
    motion: '<path d="M3 12h3l2-5 4 10 2-5h7"/>',
    verified: '<path d="m12 2 2.4 1.8 3-.2.9 2.9 2.5 1.7-1 2.8 1 2.8-2.5 1.7-.9 2.9-3-.2L12 22l-2.4-1.8-3 .2-.9-2.9-2.5-1.7 1-2.8-1-2.8 2.5-1.7.9-2.9 3 .2L12 2Z"/><path d="m8.5 12 2.3 2.3 4.7-4.6"/>'
  };
  /* CivicLink brand mark: three linked nodes (people · community · government) forming a "C" */
  const logo = (opts = {}) => `<span class="logo${opts.inverse ? ' inverse' : ''}"><svg class="logo-mark" viewBox="0 0 40 40" aria-hidden="true"><rect width="40" height="40" rx="11" class="lm-bg"/><path d="M27.2 12.6a10.2 10.2 0 1 0 0 14.8" class="lm-arc"/><circle cx="27.2" cy="12.6" r="3.6" class="lm-gov"/><circle cx="9.8" cy="20" r="3.6" class="lm-node"/><circle cx="27.2" cy="27.4" r="3.6" class="lm-node"/></svg>${opts.mark ? '' : `<span class="logo-word"><b>Civic</b><b>Link</b>${opts.tag ? `<em>${opts.tag}</em>` : ''}</span>`}</span>`;
  const icon = (n, cls = '') => `<svg class="ic ${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${P[n] || ''}</svg>`;

  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const cap = (s) => String(s || '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
  function timeAgo(t) {
    const s = (Date.now() - new Date(t)) / 1000;
    if (s < 60) return 'just now'; if (s < 3600) return Math.floor(s / 60) + 'm'; if (s < 86400) return Math.floor(s / 3600) + 'h';
    if (s < 604800) return Math.floor(s / 86400) + 'd';
    return new Date(t).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: new Date(t).getFullYear() === new Date().getFullYear() ? undefined : 'numeric' });
  }
  const fmtDate = (t) => new Date(t).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
  const fmtDT = (t) => new Date(t).toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
  const PREFIX = { proposal: 'PRP', service_request: 'REQ', complaint: 'CMP', consultation: 'CON' };
  const ref = (type, id) => `${PREFIX[type] || 'REF'}-${String(id).padStart(4, '0')}`;

  const TONE = { pending: 'amber', under_review: 'blue', approved: 'green', rejected: 'red', implemented: 'brand',
    submitted: 'amber', in_progress: 'blue', resolved: 'green', closed: 'gray', cancelled: 'gray',
    filed: 'amber', investigating: 'blue', mediation: 'violet', dismissed: 'red', open: 'green', active: 'green', suspended: 'red',
    urgent: 'red', high: 'amber', normal: 'blue', low: 'gray', support: 'green', oppose: 'red', neutral: 'gray',
    escalated: 'blue', overdue: 'red' };
  // Civic content types — label, icon and accent used by activity cards, notices and notifications
  const KIND = {
    proposal: { label: 'Community Proposal', ic: 'proposal', tone: 'community' },
    consultation: { label: 'Public Consultation', ic: 'consult', tone: 'info' },
    decision: { label: 'Official Civic Notice', ic: 'seal', tone: 'gov' },
    service_request: { label: 'Service Request', ic: 'request', tone: 'brand' },
    complaint: { label: 'Community Concern', ic: 'complaint', tone: 'warn' },
    discussion: { label: 'Community Discussion', ic: 'discuss', tone: 'community' },
    endorsement: { label: 'Sector Endorsement', ic: 'award', tone: 'accent' }
  };
  const kindTag = (k, extra = '') => { const m = KIND[k] || KIND.proposal; return `<span class="kind k-${m.tone}">${icon(m.ic)}<span>${m.label}</span>${extra}</span>`; };
  const badge = (s, label) => `<span class="badge t-${TONE[s] || 'gray'}">${esc(label || cap(s))}</span>`;

  const AV = ['#0B6463', '#2B3A67', '#8A4B22', '#5E4593', '#7A5A00', '#2F6B45', '#8E3450', '#3E5468'];
  function avatar(u, size = 40) {
    if (!u) return `<span class="av" style="width:${size}px;height:${size}px"></span>`;
    const ini = u.full_name.split(' ').filter(w => /^[A-Za-z]/.test(w) && !/\.$/.test(w)).slice(0, 2).map(w => w[0]).join('').toUpperCase();
    if (u.photo) return `<img class="av" src="${u.photo}" style="width:${size}px;height:${size}px" alt="">`;
    return `<span class="av" style="width:${size}px;height:${size}px;font-size:${Math.round(size * 0.38)}px;background:${AV[u.id % AV.length]}">${esc(ini)}</span>`;
  }

  /* toasts */
  function toast(msg, tone = 'ok') {
    let w = document.getElementById('toasts'); if (!w) { w = document.createElement('div'); w.id = 'toasts'; w.setAttribute('role', 'status'); w.setAttribute('aria-live', 'polite'); document.body.appendChild(w); }
    const t = document.createElement('div'); t.className = 'toast ' + tone; t.innerHTML = icon(tone === 'bad' ? 'x' : 'check') + `<span>${esc(msg)}</span>`;
    w.appendChild(t); requestAnimationFrame(() => t.classList.add('in'));
    setTimeout(() => { t.classList.remove('in'); setTimeout(() => t.remove(), 300); }, 3200);
  }
  /* modal — accessible dialog: labelled, focus-trapped, restores focus, bottom sheet on phones */
  let lastFocus = null;
  function modal(title, body, opts = {}) {
    const had = document.getElementById('modal');
    if (!had) lastFocus = document.activeElement;
    closeModal(true);
    const m = document.createElement('div'); m.id = 'modal'; m.className = 'modal-wrap';
    m.innerHTML = `<div class="modal ${opts.wide ? 'wide' : ''}" role="dialog" aria-modal="true" aria-labelledby="modal-title">
      <div class="modal-head"><h2 id="modal-title">${esc(title)}</h2><button class="icon-btn modal-x" data-act="close-modal" aria-label="Close dialog">${icon('x')}</button></div>
      <div class="modal-body">${body}</div></div>`;
    document.body.appendChild(m); document.body.classList.add('no-scroll');
    requestAnimationFrame(() => m.classList.add('in'));
    m.addEventListener('mousedown', e => { if (e.target === m) closeModal(); });
    m.addEventListener('keydown', e => { // keep keyboard focus inside the dialog
      if (e.key !== 'Tab') return;
      const f = [...m.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]):not([type=hidden]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])')].filter(x => x.offsetParent !== null);
      if (!f.length) return; const first = f[0], last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); } else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });
    const f = m.querySelector('input:not([type=hidden]),textarea,select');
    setTimeout(() => { if (f && !opts.noFocus) f.focus(); else m.querySelector('.modal').focus({ preventScroll: true }); }, 60);
    m.querySelector('.modal').tabIndex = -1;
    // phones: drag the sheet's header down to dismiss
    const sheet = m.querySelector('.modal'), head = m.querySelector('.modal-head'); let y0 = null, dy = 0;
    head.addEventListener('touchstart', e => { if (!matchMedia('(max-width: 760px)').matches) return; y0 = e.touches[0].clientY; dy = 0; sheet.style.transition = 'none'; }, { passive: true });
    head.addEventListener('touchmove', e => { if (y0 == null) return; dy = Math.max(0, e.touches[0].clientY - y0); sheet.style.transform = `translateY(${dy}px)`; }, { passive: true });
    head.addEventListener('touchend', () => { if (y0 == null) return; y0 = null; sheet.style.transition = ''; sheet.style.transform = ''; if (dy > 90) closeModal(); });
    return m;
  }
  function closeModal(swap) {
    const m = document.getElementById('modal'); document.body.classList.remove('no-scroll');
    if (m) { m.removeAttribute('id'); if (swap === true || !m.classList.contains('in')) m.remove(); else { m.classList.remove('in'); m.classList.add('out'); m.style.pointerEvents = 'none'; setTimeout(() => m.remove(), 200); } }
    if (m && swap !== true && lastFocus && document.body.contains(lastFocus)) { try { lastFocus.focus({ preventScroll: true }); } catch (_) {} lastFocus = null; }
  }
  function confirmBox(title, text, okLabel, onOk, danger) {
    modal(title, `<p class="modal-desc">${esc(text)}</p><div class="form-actions"><button class="btn secondary" data-act="close-modal">Cancel</button><button class="btn ${danger ? 'danger' : 'primary'}" id="cfm-ok">${esc(okLabel)}</button></div>`, { noFocus: true });
    document.getElementById('cfm-ok').onclick = () => { closeModal(); onOk(); };
  }
  function formData(form) { const o = {}; new FormData(form).forEach((v, k) => { o[k] = typeof v === 'string' ? v.trim() : v; }); return o; }
  function readFile(file, maxMB = 1.5) {
    return new Promise((res, rej) => {
      if (!file || !file.size) return res(null);
      if (file.size > maxMB * 1024 * 1024) return rej(new Error(`File must be under ${maxMB} MB.`));
      if (!/^(image\/(png|jpe?g|webp)|application\/pdf)$/.test(file.type)) return rej(new Error('Only JPG, PNG, WEBP or PDF files are allowed.'));
      const r = new FileReader(); r.onload = () => res({ name: file.name, type: file.type, data: r.result }); r.onerror = rej; r.readAsDataURL(file);
    });
  }
  function downloadCSV(name, rows) {
    const csv = rows.map(r => r.map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join(',')).join('\n');
    const a = document.createElement('a'); a.href = URL.createObjectURL(new Blob(['\ufeff' + csv], { type: 'text/csv' })); a.download = name; a.click();
  }
  const empty = (ic, title, sub = '', cta = '') => `<div class="empty" role="status"><span class="empty-ic">${icon(ic)}</span><h3>${esc(title)}</h3>${sub ? `<p>${esc(sub)}</p>` : ''}${cta ? `<div class="empty-cta">${cta}</div>` : ''}</div>`;
  const skeleton = (n = 3) => `<div class="skel-list" aria-busy="true" aria-label="Loading">${Array.from({ length: n }, () => '<div class="skel-card"><span class="skel w30"></span><span class="skel w80 lg"></span><span class="skel w100"></span><span class="skel w60"></span></div>').join('')}</div>`;
  function bars(items, opts = {}) { // [{label, value, tone}]
    const max = Math.max(1, ...items.map(i => i.value));
    return `<div class="bars">${items.map(i => `<div class="bar-row"><span class="bar-l">${esc(i.label)}</span><span class="bar-t"><span class="bar-f t-${i.tone || 'brand'}" style="width:${(i.value / max) * 100}%"></span></span><span class="bar-v">${opts.fmt ? opts.fmt(i.value) : i.value}</span></div>`).join('')}</div>`;
  }
  const COLORS = { gov: 'var(--gov)', community: 'var(--community)', gold: 'var(--gold-2)', green: 'var(--green)', red: 'var(--red)', amber: 'var(--amber)', blue: 'var(--blue)', gray: 'var(--gray)', violet: 'var(--violet)', brand: 'var(--brand)' };
  function donut(items, center = '') {
    const tot = items.reduce((a, b) => a + b.value, 0) || 1; let acc = 0;
    const segs = items.map(i => { const s = acc / tot * 360; acc += i.value; return `${COLORS[i.tone] || 'var(--gray)'} ${s}deg ${acc / tot * 360}deg`; }).join(',');
    return `<div class="donut-wrap"><div class="donut" style="background:conic-gradient(${segs || 'var(--line) 0 360deg'})"><div class="donut-c">${center}</div></div>
      <ul class="legend">${items.map(i => `<li><i style="background:${COLORS[i.tone] || 'var(--gray)'}"></i>${esc(i.label)}<b>${i.value}</b></li>`).join('')}</ul></div>`;
  }

  /* ---------- SVG charts ---------- */
  function lineChart(series, labels, opts = {}) { // series: [{label, tone, values:[]}]
    const W = 640, H = opts.h || 220, pl = 34, pr = 12, pt = 14, pb = 28; const n = labels.length;
    const max = Math.max(1, ...series.flatMap(s => s.values)); const nice = Math.ceil(max / 4) * 4 || 4;
    const x = (i) => pl + (n <= 1 ? (W - pl - pr) / 2 : i * (W - pl - pr) / (n - 1)); const y = (v) => pt + (H - pt - pb) * (1 - v / nice);
    const grid = [0, 1, 2, 3, 4].map(k => { const v = nice * k / 4; return `<line x1="${pl}" x2="${W - pr}" y1="${y(v)}" y2="${y(v)}" class="lc-grid"/><text x="${pl - 8}" y="${y(v) + 4}" class="lc-ax" text-anchor="end">${Math.round(v)}</text>`; }).join('');
    const step = Math.ceil(n / 8);
    const xl = labels.map((l, i) => i % step === 0 || i === n - 1 ? `<text x="${x(i)}" y="${H - 8}" class="lc-ax" text-anchor="middle">${esc(l)}</text>` : '').join('');
    const lines = series.map(s => { const c = COLORS[s.tone] || 'var(--brand)'; const pts = s.values.map((v, i) => `${x(i)},${y(v)}`).join(' ');
      const area = `${x(0)},${y(0)} ${pts} ${x(n - 1)},${y(0)}`;
      return `<polygon points="${area}" fill="${c}" opacity=".08"/><polyline points="${pts}" fill="none" stroke="${c}" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round"/>${s.values.map((v, i) => `<circle cx="${x(i)}" cy="${y(v)}" r="3" fill="var(--surface)" stroke="${c}" stroke-width="2"><title>${esc(s.label)} · ${esc(labels[i])}: ${v}</title></circle>`).join('')}`; }).join('');
    return `<div class="lc"><svg viewBox="0 0 ${W} ${H}" preserveAspectRatio="none" role="img" aria-label="${esc(opts.label || 'Trend chart')}">${grid}${xl}${lines}</svg>
      <ul class="legend inline">${series.map(s => `<li><i style="background:${COLORS[s.tone] || 'var(--brand)'}"></i>${esc(s.label)}<b>${s.values.reduce((a, b) => a + b, 0)}</b></li>`).join('')}</ul></div>`;
  }
  function spark(values, tone = 'brand') {
    const W = 90, H = 28, max = Math.max(1, ...values), n = values.length; if (!n) return '';
    const pts = values.map((v, i) => `${n === 1 ? W / 2 : i * W / (n - 1)},${H - 3 - (H - 6) * v / max}`).join(' ');
    return `<svg class="spark" viewBox="0 0 ${W} ${H}" preserveAspectRatio="none" aria-hidden="true"><polyline points="${pts}" fill="none" stroke="${COLORS[tone] || 'var(--brand)'}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/></svg>`;
  }
  function gauge(pct, label, tone) {
    const p = pct == null ? 0 : Math.max(0, Math.min(100, pct)); const t = tone || (p >= 85 ? 'green' : p >= 60 ? 'amber' : 'red');
    const r = 52, c = Math.PI * r; const off = c * (1 - p / 100);
    return `<div class="gauge"><svg viewBox="0 0 128 74" aria-hidden="true"><path d="M12 66a52 52 0 0 1 104 0" fill="none" stroke="var(--surface-3)" stroke-width="12" stroke-linecap="round"/>
      <path d="M12 66a52 52 0 0 1 104 0" fill="none" stroke="${COLORS[t]}" stroke-width="12" stroke-linecap="round" stroke-dasharray="${c}" stroke-dashoffset="${off}"/></svg>
      <b class="gauge-n">${pct == null ? '–' : Math.round(p) + '%'}</b></div><span class="gauge-l">${esc(label || '')}</span>`;
  }
  function funnel(steps) { // [{label, value, tone}]
    const max = Math.max(1, ...steps.map(s => s.value));
    return `<div class="funnel">${steps.map((s, i) => `<div class="fn-row"><span class="fn-l">${esc(s.label)}</span><span class="fn-t"><span class="fn-f" style="width:${Math.max(4, s.value / max * 100)}%;background:${COLORS[s.tone] || 'var(--brand)'}">${s.value}</span></span><span class="fn-p">${i ? (steps[0].value ? Math.round(s.value / steps[0].value * 100) : 0) + '%' : ''}</span></div>`).join('')}</div>`;
  }
  const stars = (n) => `<span class="stars" aria-label="${n} of 5">${[1, 2, 3, 4, 5].map(i => `<span class="${i <= n ? 'on' : ''}">★</span>`).join('')}</span>`;

  window.UI = { logo, KIND, kindTag, skeleton, icon, esc, cap, timeAgo, fmtDate, fmtDT, ref, badge, avatar, toast, modal, closeModal, confirmBox, formData, readFile, downloadCSV, empty, bars, donut, stars, TONE, lineChart, spark, gauge, funnel, COLORS };
})();
