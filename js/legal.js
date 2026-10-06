/* CivicLink — Terms of Use, supported Philippine laws, and reminders */
(function () {
  const { icon, esc } = UI;
  const VERSION = '2026.10';
  const LAWS = [
    { id: 'dpa', code: 'RA 10173', name: 'Data Privacy Act of 2012', tags: ['privacy', 'register', 'id', 'complaint', 'request'],
      sum: 'Protects personal information. The LGU may only collect what it needs, for a stated purpose, and must keep it secure.',
      you: 'Do not post other people\'s addresses, phone numbers, IDs, or health details. You can ask to access, correct, or delete your data.' },
    { id: 'cyber', code: 'RA 10175', name: 'Cybercrime Prevention Act of 2012', tags: ['post', 'comment', 'complaint'],
      sum: 'Libel committed online (cyber libel) is a crime with a higher penalty than ordinary libel.',
      you: 'Criticize policies and services, not personal reputations. State facts you can support.' },
    { id: 'rpc', code: 'Act 3815, Arts. 353–362', name: 'Revised Penal Code — Libel & defamation', tags: ['post', 'comment', 'complaint'],
      sum: 'A public and malicious imputation of a crime, vice, or defect that dishonors a person is libel.',
      you: 'File accusations against people through the Complaints form, where they are handled privately — not in public posts.' },
    { id: 'safe', code: 'RA 11313', name: 'Safe Spaces Act', tags: ['post', 'comment'],
      sum: 'Penalizes gender-based sexual harassment in streets, workplaces, and online spaces.',
      you: 'No sexist, homophobic, or sexual remarks, threats, or unwanted messages.' },
    { id: 'lgc', code: 'RA 7160', name: 'Local Government Code of 1991', tags: ['post', 'consult', 'proposal'],
      sum: 'Secs. 34–36 require LGUs to promote people\'s and NGO participation in local governance and development planning.',
      you: 'Your proposals, votes, and consultation responses are inputs to local planning — not binding decisions.' },
    { id: 'kp', code: 'RA 7160, Secs. 399–422', name: 'Katarungang Pambarangay (Barangay Justice System)', tags: ['complaint'],
      sum: 'Most disputes between residents of the same city or municipality must go through barangay conciliation (Lupon) before reaching court.',
      you: 'Neighbor disputes filed here may be referred to your Lupong Tagapamayapa for mediation.' },
    { id: 'eodb', code: 'RA 11032', name: 'Ease of Doing Business & Efficient Gov\'t Service Delivery Act', tags: ['request', 'sla'],
      sum: 'Sets maximum processing times: 3 working days (simple), 7 (complex), 20 (highly technical). Offices must publish a Citizen\'s Charter.',
      you: 'If a request goes past its target, it is flagged as overdue. You may also report delays to the Anti-Red Tape Authority (ARTA).' },
    { id: 'ethics', code: 'RA 6713', name: 'Code of Conduct and Ethical Standards for Public Officials', tags: ['staff', 'complaint'],
      sum: 'Public officials must act promptly, courteously, and with transparency, and respond to public letters within 15 working days.',
      you: 'Every LGU decision on CivicLink includes a written justification that you can read.' },
    { id: 'foi', code: 'EO No. 2, s. 2016', name: 'Freedom of Information', tags: ['decision'],
      sum: 'Recognizes the people\'s right to information on official acts and decisions, subject to exceptions such as privacy.',
      you: 'Proposal decisions are public. Private cases show their history only to you and the officers handling them.' },
    { id: 'ecom', code: 'RA 8792', name: 'Electronic Commerce Act of 2000', tags: ['request', 'complaint', 'register'],
      sum: 'Gives electronic documents and submissions the same legal recognition as paper.',
      you: 'What you submit here is an official record. Submitting false information can have legal consequences.' },
    { id: 'philsys', code: 'RA 11055', name: 'Philippine Identification System Act', tags: ['id', 'register'],
      sum: 'The PhilSys ID (National ID) is valid proof of identity for government transactions.',
      you: 'Upload a PhilSys ID or another government-issued ID to get your account verified.' },
    { id: 'pwd', code: 'RA 7277, as amended by RA 10754', name: 'Magna Carta for Persons with Disability', tags: ['sector'],
      sum: 'Guarantees accessibility and the participation of persons with disability in community affairs.',
      you: 'CivicLink supports keyboard navigation, screen readers, and dark mode.' },
    { id: 'senior', code: 'RA 9994', name: 'Expanded Senior Citizens Act of 2010', tags: ['sector'],
      sum: 'Promotes the participation of senior citizens in nation-building and community development.', you: 'Senior citizens can be represented through their sector.' },
    { id: 'sk', code: 'RA 10742', name: 'Sangguniang Kabataan Reform Act of 2015', tags: ['sector'],
      sum: 'Strengthens youth participation in local governance through the SK and Local Youth Development Councils.', you: 'Youth sector representatives can endorse proposals on behalf of young people.' },
    { id: 'eswm', code: 'RA 9003', name: 'Ecological Solid Waste Management Act of 2000', tags: ['request'],
      sum: 'Makes barangays responsible for segregated collection of biodegradable and recyclable waste and for MRFs.', you: 'Report missed or unsegregated collection as a sanitation service request.' }
  ];
  const byId = Object.fromEntries(LAWS.map(l => [l.id, l]));
  const CTX = {
    proposal: ['lgc', 'cyber', 'dpa'], request: ['eodb', 'dpa', 'ecom'], complaint: ['kp', 'rpc', 'dpa', 'ecom'], comment: ['cyber', 'safe'],
    consult: ['lgc', 'foi'], endorse: ['lgc', 'sk'], register: ['dpa', 'philsys', 'ecom'], decision: ['ethics', 'foi', 'eodb']
  };
  const RULES = [
    ['shield', 'Respect privacy', 'Never post someone else\'s personal information. (RA 10173)'],
    ['msg', 'Be civil and truthful', 'No libel, threats, or harassment — online posts are covered by RA 10175 and RA 11313.'],
    ['complaint', 'Not for emergencies', 'For fire, crime, or medical emergencies, call 911.'],
    ['gavel', 'Your submissions are official records', 'Provide true and accurate information. (RA 8792)']
  ];

  const lawChip = (id) => { const l = byId[id]; return l ? `<button type="button" class="law-chip" data-act="law" data-id="${l.id}" title="${esc(l.name)}">${icon('scale')}${esc(l.code)}</button>` : ''; };

  /* contextual notice for forms */
  function notice(kind, opts = {}) {
    const ids = CTX[kind] || [];
    const text = {
      proposal: 'Public post. Focus on the project, not on individuals.',
      request: 'Handled under the Citizen\'s Charter and service-time targets of RA 11032.',
      complaint: 'Private case. Neighbor disputes may go to barangay conciliation first.',
      comment: 'Keep it respectful — comments are public.',
      consult: 'Your view informs local planning. Written comments are public.',
      endorse: 'Endorsements are public statements made on behalf of your sector.'
    }[kind] || '';
    return `<div class="legal-note">${icon('scale')}<div><span>${esc(text)}</span><div class="law-chips">${ids.map(lawChip).join('')}</div></div></div>
      ${opts.check === false ? '' : `<label class="check legal-check"><input type="checkbox" name="legal_ok" required> <span>I confirm this is true and follows the <a href="#/legal" data-act="terms-peek">Terms of Use</a> and the laws listed above.</span></label>`}`;
  }

  function termsBody() {
    const sec = (t, b) => `<section class="terms-sec"><h4>${t}</h4>${b}</section>`;
    return `${sec('1. Acceptance', '<p>By creating an account or using CivicLink, you agree to these Terms of Use and to the Privacy Notice below. If you do not agree, please do not use the platform.</p>')}
      ${sec('2. Your account', '<ul><li>One account per person. Use your real name and accurate details.</li><li>Keep your password private. You are responsible for activity on your account.</li><li>Verification with a government-issued ID (e.g. PhilSys ID under RA 11055) adds a verified badge.</li></ul>')}
      ${sec('3. Community rules', '<ul><li>Discuss ideas, projects, and services — not people\'s private lives.</li><li>No libel or defamation (Revised Penal Code Arts. 353–362; RA 10175).</li><li>No harassment, hate, or sexual remarks (RA 11313).</li><li>No posting of other people\'s personal or sensitive information (RA 10173).</li><li>No spam, partisan campaigning, or commercial advertising.</li><li>No false reports. Submissions are official electronic records (RA 8792).</li></ul>')}
      ${sec('4. Not an emergency service', '<p>CivicLink is for non-emergency concerns. For fire, crime, or medical emergencies, call <b>911</b>.</p>')}
      ${sec('5. Public and private content', '<p>Proposals, comments, endorsements, consultation comments, and LGU decisions on proposals are <b>public</b>. Service requests, complaints, ratings, and your ID are <b>private</b> and visible only to you and authorized LGU personnel.</p>')}
      ${sec('6. Moderation', '<p>Administrators may hide or remove content that breaks these rules, and may suspend accounts after repeated violations. Moderation actions are recorded in the audit log.</p>')}
      ${sec('7. Privacy Notice (RA 10173)', '<ul><li><b>What we collect:</b> name, email, contact number, barangay, address, birthdate, sector, uploaded files, and your activity on the platform.</li><li><b>Why:</b> to verify residency, route and resolve your concerns, notify you, and produce aggregated, anonymized governance reports.</li><li><b>Who sees it:</b> authorized LGU personnel only. We do not sell or share your data with third parties.</li><li><b>Your rights:</b> to be informed, to access, to object, to correct, to erasure, to data portability, and to file a complaint with the National Privacy Commission.</li><li><b>Contact:</b> the LGU Data Protection Officer through the Complaints form (category “Other”).</li></ul>')}
      ${sec('8. Service standards', '<p>Service requests follow the LGU\'s Citizen\'s Charter and the processing times set by RA 11032. LGU officers follow the Code of Conduct for public officials (RA 6713) and give a written justification for every decision.</p>')}
      ${sec('9. Changes', `<p>We may update these terms. If they change, you will be asked to accept the new version the next time you sign in. Current version: <b>${VERSION}</b>.</p>`)}
      <p class="muted small">This summary is for information only and is not legal advice. For specific concerns, consult the LGU Legal Office.</p>`;
  }
  function lawList() {
    return `<div class="law-grid">${LAWS.map(l => `<article class="law-card" id="law-${l.id}"><div class="law-h"><span class="law-ic">${icon('scale')}</span><div><b>${esc(l.code)}</b><span>${esc(l.name)}</span></div></div><p>${esc(l.sum)}</p><p class="law-you">${icon('user')}<span>${esc(l.you)}</span></p></article>`).join('')}</div>`;
  }

  /* page */
  App.route('/legal', {
    title: 'Laws & Terms', layout: 'feed',
    render: (p, u) => {
      const tab = V2.tab || 'terms';
      const inner = `<div class="legal-hero card"><span class="legal-hero-ic">${icon('scale')}</span><div><span class="eyebrow">Your rights & responsibilities</span><h2>Laws & Terms of Use</h2><p class="muted">CivicLink follows Philippine laws on privacy, online conduct, local participation, and public service delivery.</p></div></div>
        <div class="tabs">${[['terms', 'Terms of Use'], ['laws', 'Supported laws'], ['rules', 'Community rules']].map(([k, l]) => `<button class="${tab === k ? 'on' : ''}" data-act="legal-tab" data-k="${k}">${l}</button>`).join('')}</div>
        ${tab === 'terms' ? `<div class="card pad terms-doc">${termsBody()}</div>` : tab === 'laws' ? lawList() : `<div class="card pad"><div class="rules">${RULES.map(([ic, t, d]) => `<div class="rule">${icon(ic)}<div><b>${t}</b><span>${d}</span></div></div>`).join('')}</div></div>`}
        ${u ? `<p class="muted small">You accepted version ${esc(accepted(u) || '—')} of the Terms.</p>` : '<a class="btn primary" href="#/register">Create an account</a>'}`;
      return u ? inner : `<div class="legal-public"><a class="back" href="#/login">${icon('back')} Back to sign in</a>${inner}</div>`;
    },
    rail: () => `<div class="card rail-box"><h4>Quick reference</h4>${LAWS.slice(0, 8).map(l => `<a class="trend" href="#/legal" data-act="law" data-id="${l.id}"><span class="rank c">${icon('scale')}</span><div><b>${esc(l.code)}</b><span class="muted small">${esc(l.name)}</span></div></a>`).join('')}</div>`
  });
  const V2 = {};

  /* acceptance gate & reminders (own overlay so re-renders don't close it) */
  const key = (u) => 'civiclink.terms.' + u.id;
  // acceptance is stored in the terms_acceptances table (localStorage is only a cache for older databases without that table)
  const accepted = (u) => { const rows = DB.where('terms_acceptances', x => x.user_id === u.id).map(x => x.terms_version);
    return rows.includes(VERSION) ? VERSION : (rows[rows.length - 1] || localStorage.getItem(key(u))); };
  function record(u) {
    localStorage.setItem(key(u), VERSION);
    if (!DB.first('terms_acceptances', x => x.user_id === u.id && x.terms_version === VERSION)) { try { DB.insert('terms_acceptances', { user_id: u.id, terms_version: VERSION }); } catch (e) { console.warn('Terms acceptance not stored:', e.message); } }
  }
  function overlay(cls, html) { close(cls); const w = document.createElement('div'); w.className = cls; w.innerHTML = html; document.body.appendChild(w); requestAnimationFrame(() => w.classList.add('in')); return w; }
  function close(cls) { document.querySelectorAll('.' + cls).forEach(e => e.remove()); }

  function gate(u) {
    const updated = !!accepted(u);
    overlay('terms-wrap', `<div class="terms-modal" role="dialog" aria-modal="true" aria-labelledby="terms-t">
      <div class="terms-top"><span class="legal-hero-ic">${icon('scale')}</span><div><span class="eyebrow">${updated ? 'Updated terms · v' + VERSION : 'Before you start'}</span><h3 id="terms-t">${updated ? 'Our Terms of Use have changed' : 'Welcome to CivicLink, ' + esc(u.full_name.split(' ')[0]) + '!'}</h3><p class="muted">Please review and accept the Terms of Use to continue.</p></div></div>
      <div class="rules">${RULES.map(([ic, t, d]) => `<div class="rule">${icon(ic)}<div><b>${t}</b><span>${d}</span></div></div>`).join('')}</div>
      <details class="terms-full"><summary>${icon('book')} Read the full Terms of Use & Privacy Notice</summary><div class="terms-doc">${termsBody()}</div></details>
      <form class="stack" id="terms-form">
        <label class="check"><input type="checkbox" required> <span>I have read and agree to the <b>Terms of Use</b>.</span></label>
        <label class="check"><input type="checkbox" required> <span>I consent to the processing of my personal data under the <b>Data Privacy Act of 2012 (RA 10173)</b>.</span></label>
        <label class="check"><input type="checkbox" required> <span>I will post responsibly and understand that <b>RA 10175</b> and <b>RA 11313</b> apply to online conduct.</span></label>
        <div class="form-actions"><button type="button" class="btn ghost" id="terms-out">${icon('logout')} Sign out</button><button class="btn primary" type="submit">${icon('check')} Accept & continue</button></div>
      </form></div>`);
    document.getElementById('terms-form').onsubmit = (e) => { e.preventDefault(); record(u); sessionStorage.setItem('civiclink.reminded.' + u.id, '1'); try { Civic.audit('Terms Accepted', 'Terms of Use v' + VERSION); } catch (_) {} close('terms-wrap'); UI.toast('Thanks! You can review the terms anytime under Laws & Terms.'); setTimeout(() => window.Tour && Tour.auto(u), 400); };
    document.getElementById('terms-out').onclick = () => { close('terms-wrap'); Civic.logout(); App.go('#/login'); };
  }
  function reminder(u) {
    const w = overlay('remind-wrap', `<div class="remind card" role="status"><div class="remind-h">${icon('shield')}<b>Community reminder</b><button class="icon-btn sm" data-x aria-label="Dismiss">${icon('x')}</button></div>
      <p>Be respectful, keep others' personal data private, and post only what is true. Your activity follows our <a href="#/legal" data-x>Terms of Use</a> and Philippine law.</p>
      <div class="law-chips">${['dpa', 'cyber', 'safe', 'eodb'].map(lawChip).join('')}</div>
      <button class="btn sm primary" data-x>I understand</button></div>`);
    w.querySelectorAll('[data-x]').forEach(b => b.addEventListener('click', () => { sessionStorage.setItem('civiclink.reminded.' + u.id, '1'); w.remove(); }));
  }
  function check(u) {
    if (!u) { close('terms-wrap'); close('remind-wrap'); return; }
    if (accepted(u) !== VERSION) { if (!document.querySelector('.terms-wrap')) gate(u); return; }
    if (window.Tour && Tour.active()) return;
    if (window.Tour && !Tour.done(u)) { sessionStorage.setItem('civiclink.reminded.' + u.id, '1'); return Tour.auto(u); }
    if (!sessionStorage.getItem('civiclink.reminded.' + u.id) && !document.querySelector('.remind-wrap')) reminder(u);
  }
  function lawModal(id) {
    const l = byId[id]; if (!l) return;
    UI.modal(l.code, `<div class="law-card flat"><div class="law-h"><span class="law-ic">${icon('scale')}</span><div><b>${esc(l.code)}</b><span>${esc(l.name)}</span></div></div><p>${esc(l.sum)}</p><p class="law-you">${icon('user')}<span>${esc(l.you)}</span></p></div>
      <div class="form-actions"><a class="btn ghost" href="#/legal" data-act="close-modal-go" data-href="#/legal">All laws & terms</a><button class="btn primary" data-act="close-modal">Got it</button></div>`, { noFocus: true });
  }

  App.act({
    law: (el) => lawModal(el.dataset.id),
    'legal-tab': (el) => { V2.tab = el.dataset.k; App.go('#/legal'); },
    'terms-peek': () => { if (document.getElementById('modal')) { const m = document.querySelector('#modal .modal-body'); const d = document.createElement('details'); d.className = 'terms-full'; d.open = true; d.innerHTML = `<summary>${icon('book')} Terms of Use</summary><div class="terms-doc">${termsBody()}</div>`; m.prepend(d); m.scrollTop = 0; } else { V2.tab = 'terms'; App.go('#/legal'); } },
    'terms-modal': () => UI.modal('Terms of Use & Privacy Notice', `<div class="terms-doc">${termsBody()}</div>`, { wide: true, noFocus: true })
  });

  window.Legal = { VERSION, LAWS, notice, lawChip, check, termsBody, accept: record, footer: () => `<div class="legal-foot"><a href="#/legal">Terms</a><a href="#/legal" data-act="legal-tab" data-k="terms">Privacy (RA 10173)</a><a href="#/legal" data-act="legal-tab" data-k="laws">Laws</a><a href="#/legal" data-act="help">Help</a><span>CivicLink © ${new Date().getFullYear()}</span></div>` };
})();
