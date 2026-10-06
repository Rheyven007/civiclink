/* CivicLink — sector, LGU officer and administrator views */
(function () {
  const { icon, esc, cap, avatar, badge, timeAgo, fmtDate, fmtDT, ref, empty, bars, donut, stars } = UI;
  const V = window.Views; const LGU = ['lgu_officer', 'admin'];
  const kpi = (ic, label, val, sub, tone) => `<div class="kpi card ${tone || ''}"><span class="kpi-ic">${icon(ic)}</span><div><span class="kpi-l">${label}</span><b class="kpi-v">${val}</b>${sub ? `<span class="kpi-s">${sub}</span>` : ''}</div></div>`;
  const titleOf = (type, x) => x[Civic.TITLE[type]];
  const openStates = { proposal: ['pending', 'under_review'], service_request: ['submitted', 'in_progress'], complaint: ['filed', 'investigating', 'mediation'] };
  const avgRating = (list) => list.length ? (list.reduce((a, b) => a + b.rating, 0) / list.length).toFixed(1) : '–';

  /* ================= SECTOR ================= */
  App.route('/sector', {
    title: 'Sector Hub', roles: ['sector_rep', 'admin'],
    render: (p, u) => {
      const my = Civic.sectorOf(u.id); const f = V.sf || (V.sf = { sector: my ? String(my.id) : 'all', kind: 'all', q: '' });
      const members = DB.where('citizen_profiles', x => f.sector === 'all' || x.sector_id === Number(f.sector)).map(x => x.user_id);
      const inS = (uid) => members.includes(uid);
      let items = [];
      if (f.kind !== 'complaint' && f.kind !== 'request') items.push(...DB.where('proposals', x => inS(x.user_id)).map(x => ({ k: 'proposal', x })));
      if (f.kind === 'all' || f.kind === 'request') items.push(...DB.where('service_requests', x => inS(x.user_id)).map(x => ({ k: 'service_request', x })));
      if (f.kind === 'all' || f.kind === 'complaint') items.push(...DB.where('complaints', x => inS(x.user_id)).map(x => ({ k: 'complaint', x })));
      if (f.q) items = items.filter(i => JSON.stringify(i.x).toLowerCase().includes(f.q.toLowerCase()));
      items.sort((a, b) => new Date(b.x.created_at) - new Date(a.x.created_at));
      const open = items.filter(i => openStates[i.k].includes(i.x.status)).length;
      const ends = DB.where('endorsements', e => e.user_id === u.id).length;
      const secs = DB.all('sectors').map(s => ({ label: s.sector_name, value: DB.where('citizen_profiles', x => x.sector_id === s.id).length, tone: my && my.id === s.id ? 'gold' : 'brand' }));
      return `<div class="hero-card"><div><span class="eyebrow light">Sector representative</span><h2>${esc(my ? my.sector_name : 'No sector assigned')}</h2><p>${esc(my ? my.description : 'Ask the administrator to assign your sector.')}</p></div>
          <div class="hero-kpis"><div><b>${members.length}</b><span>Members</span></div><div><b>${open}</b><span>Open concerns</span></div><div><b>${ends}</b><span>Your endorsements</span></div></div></div>
        <div class="grid-main">
          <div><div class="toolbar"><div class="search">${icon('search')}<input data-filter="sf.q" value="${esc(f.q)}" placeholder="Search sector issues"></div>
            <select data-filter="sf.sector"><option value="all">All sectors</option>${DB.all('sectors').map(s => `<option value="${s.id}" ${f.sector === String(s.id) ? 'selected' : ''}>${esc(s.sector_name)}</option>`).join('')}</select>
            <select data-filter="sf.kind">${[['all', 'All types'], ['proposal', 'Proposals'], ['request', 'Service requests'], ['complaint', 'Complaints']].map(([k, l]) => `<option value="${k}" ${f.kind === k ? 'selected' : ''}>${l}</option>`).join('')}</select></div>
          <div class="card list">${items.map(({ k, x }) => { const a = Civic.user(x.user_id); const s = Civic.sectorOf(x.user_id);
            return `<a class="row-item" ${k === 'proposal' ? `href="#/proposals/${x.id}"` : ''}><span class="ri-ic ${k === 'complaint' ? 'warn' : ''}">${icon(k === 'proposal' ? 'proposal' : k === 'complaint' ? 'complaint' : 'request')}</span>
              <div class="ri-main"><b>${esc(titleOf(k, x))}</b><span class="muted small">${ref(k, x.id)} · ${esc(a.full_name)} · ${esc(s ? s.sector_name : '')} · ${timeAgo(x.created_at)}</span></div><div class="ri-side">${badge(x.status)}</div></a>`; }).join('') || empty('layers', 'No sector issues found')}</div></div>
          <div class="card pad"><h4 class="sec-t">Sector membership</h4>${bars(secs)}</div></div>`;
    }
  });
  App.route('/sector/endorse', {
    title: 'Endorsements', roles: ['sector_rep'], layout: 'feed',
    render: (p, u) => { const tab = V.eTab || 'open';
      const list = tab === 'open' ? DB.where('proposals', x => ['pending', 'under_review'].includes(x.status) && !DB.first('endorsements', e => e.proposal_id === x.id && e.user_id === u.id))
        : DB.where('endorsements', e => e.user_id === u.id).map(e => DB.find('proposals', e.proposal_id)).filter(Boolean);
      return `<div class="page-head"><div><h2>Sector endorsements</h2><p class="muted">Lend your sector's voice to proposals that matter to your members. Endorsements are separate from votes and are shown to LGU reviewers.</p></div></div>
        <div class="tabs">${[['open', 'Awaiting endorsement'], ['mine', 'Endorsed by me']].map(([k, l]) => `<button class="${tab === k ? 'on' : ''}" data-act="e-tab" data-k="${k}">${l}</button>`).join('')}</div>
        <div class="feed">${list.map(x => V.proposalCard(x, u)).join('') || empty('award', tab === 'open' ? 'Nothing waiting for endorsement' : 'You have not endorsed anything yet')}</div>`; },
    rail: (p, u) => V.rail(u)
  });

  /* ================= LGU ================= */
  App.route('/lgu', {
    title: 'Overview', roles: LGU,
    render: (p, u) => {
      const R = DB.all('service_requests'), C = DB.all('complaints'), P = DB.all('proposals');
      const openCases = R.filter(x => openStates.service_request.includes(x.status)).length + C.filter(x => openStates.complaint.includes(x.status)).length + P.filter(x => openStates.proposal.includes(x.status)).length;
      const mine = [...R.filter(x => x.assigned_to === u.id && openStates.service_request.includes(x.status)).map(x => ['service_request', x]), ...C.filter(x => x.assigned_to === u.id && openStates.complaint.includes(x.status)).map(x => ['complaint', x])];
      const overdue = R.filter(r => Civic.sla(r).overdue);
      const unassigned = [...R.filter(x => !x.assigned_to && x.status === 'submitted').map(x => ['service_request', x]), ...C.filter(x => !x.assigned_to && x.status === 'filed').map(x => ['complaint', x]), ...P.filter(x => x.status === 'pending').map(x => ['proposal', x])];
      const fb = DB.all('feedback');
      const row = ([t, x]) => { const s = t === 'service_request' ? Civic.sla(x) : null; return `<a class="row-item" href="#/lgu/cases/${t}/${x.id}"><span class="ri-ic ${t === 'complaint' ? 'warn' : ''}">${icon(t === 'proposal' ? 'proposal' : t === 'complaint' ? 'complaint' : 'request')}</span><div class="ri-main"><b>${esc(titleOf(t, x))}</b><span class="muted small">${ref(t, x.id)} · ${esc(Civic.user(x.user_id).full_name)} · ${timeAgo(x.created_at)}</span></div><div class="ri-side">${badge(x.status)}${s && s.overdue ? badge('overdue', 'Overdue') : t === 'service_request' ? badge(x.priority) : ''}</div>${icon('chev', 'chev')}</a>`; };
      return `<div class="greet"><div><span class="eyebrow">${new Date().toLocaleDateString('en-PH', { weekday: 'long', month: 'long', day: 'numeric' })}</span><h2>Good ${new Date().getHours() < 12 ? 'morning' : new Date().getHours() < 18 ? 'afternoon' : 'evening'}, ${esc(u.full_name.split(' ').slice(-2, -1)[0] || u.full_name.split(' ')[0])}</h2></div><a class="btn primary" href="#/lgu/cases">${icon('inbox')} Open case management</a></div>
        <div class="kpis">${kpi('inbox', 'Open cases', openCases, 'Across all case types')}${kpi('user', 'Assigned to me', mine.length, 'Active')}${kpi('clock', 'Overdue requests', overdue.length, 'Past SLA target', overdue.length ? 'bad' : '')}${kpi('star', 'Citizen rating', avgRating(fb), fb.length + ' ratings')}</div>
        <div class="grid-2">
          <div class="card"><div class="card-h"><h4>My queue</h4><span class="muted small">${mine.length}</span></div><div class="list">${mine.map(row).join('') || empty('check', 'Your queue is clear')}</div></div>
          <div class="card"><div class="card-h"><h4>Needs triage</h4><span class="muted small">${unassigned.length} new</span></div><div class="list">${unassigned.slice(0, 6).map(row).join('') || empty('check', 'No new submissions')}</div></div>
          <div class="card"><div class="card-h"><h4>Overdue (SLA breach)</h4><a class="small" href="#/lgu/sla">View SLA</a></div><div class="list">${overdue.map(x => row(['service_request', x])).join('') || empty('check', 'Nothing overdue')}</div></div>
          <div class="card"><div class="card-h"><h4>Recent decisions</h4></div><div class="list">${DB.all('decision_logs').sort((a, b) => new Date(b.created_at) - new Date(a.created_at)).slice(0, 5).map(d => `<a class="row-item" href="#/lgu/cases/${d.reference_type}/${d.reference_id}"><span class="ri-ic gold">${icon('gavel')}</span><div class="ri-main"><b>${ref(d.reference_type, d.reference_id)} → ${cap(d.to_status)}</b><span class="muted small clamp1">${esc(d.justification)}</span></div><span class="muted small">${timeAgo(d.created_at)}</span></a>`).join('')}</div></div>
        </div>`;
    }
  });

  App.route('/lgu/cases', { title: 'Case Management', roles: LGU, render: (p, u) => caseTable(V.ct || 'service_request', u) });
  App.route('/lgu/cases/:type', { title: 'Case Management', roles: LGU, render: (p, u) => caseTable(p.type, u) });
  function caseTable(type, u) {
    if (!Civic.TABLE[type]) type = 'service_request'; V.ct = type;
    const k = V['cm_' + type] || (V['cm_' + type] = { status: 'open', who: 'all', pr: 'all', q: '' });
    const rows = DB.all(Civic.TABLE[type]);
    const list = rows.filter(x => (k.status === 'all' || (k.status === 'open' ? openStates[type].includes(x.status) : x.status === k.status)) && (k.who === 'all' || (k.who === 'me' ? x.assigned_to === u.id : !x.assigned_to)) && (k.pr === 'all' || x.priority === k.pr)
      && (!k.q || (ref(type, x.id) + ' ' + titleOf(type, x) + ' ' + (Civic.user(x.user_id) || {}).full_name + ' ' + (x.description || x.details || '')).toLowerCase().includes(k.q.toLowerCase())))
      .sort((a, b) => type === 'service_request' ? (Civic.sla(b).overdue - Civic.sla(a).overdue) || (new Date(a.created_at) - new Date(b.created_at)) : new Date(b.created_at) - new Date(a.created_at));
    const cnt = (t) => DB.where(Civic.TABLE[t], x => openStates[t].includes(x.status)).length;
    return `<div class="seg">${[['service_request', 'request', 'Service requests'], ['complaint', 'complaint', 'Complaints & disputes'], ['proposal', 'proposal', 'Proposals']].map(([t, ic, l]) => `<a href="#/lgu/cases/${t}" class="${t === type ? 'on' : ''}">${icon(ic)} ${l}<em>${cnt(t)}</em></a>`).join('')}</div>
      <div class="toolbar"><div class="search">${icon('search')}<input data-filter="cm_${type}.q" value="${esc(k.q)}" placeholder="Search reference, title, citizen"></div>
        <select data-filter="cm_${type}.status"><option value="open" ${k.status === 'open' ? 'selected' : ''}>Open cases</option><option value="all" ${k.status === 'all' ? 'selected' : ''}>All statuses</option>${Civic.STATUSES[type].map(s => `<option value="${s}" ${k.status === s ? 'selected' : ''}>${cap(s)}</option>`).join('')}</select>
        ${type !== 'proposal' ? `<select data-filter="cm_${type}.who">${[['all', 'Anyone'], ['me', 'Assigned to me'], ['none', 'Unassigned']].map(([v, l]) => `<option value="${v}" ${k.who === v ? 'selected' : ''}>${l}</option>`).join('')}</select>` : ''}
        ${type === 'service_request' ? `<select data-filter="cm_${type}.pr"><option value="all">Any priority</option>${['urgent', 'high', 'normal', 'low'].map(v => `<option value="${v}" ${k.pr === v ? 'selected' : ''}>${cap(v)}</option>`).join('')}</select>` : ''}
        <button class="btn ghost sm push" data-act="export-cases" data-t="${type}">${icon('download')} CSV</button></div>
      <div class="card table-wrap"><table class="tbl"><thead><tr><th>Reference</th><th>${type === 'proposal' ? 'Proposal' : type === 'complaint' ? 'Subject' : 'Service'}</th><th>Citizen</th><th>Status</th><th>${type === 'service_request' ? 'Priority · SLA' : type === 'proposal' ? 'Support' : 'Category'}</th>${type !== 'proposal' ? '<th>Assigned</th>' : '<th>Endorsed</th>'}<th>Age</th></tr></thead>
        <tbody>${list.map(x => { const a = Civic.user(x.user_id); const o = x.assigned_to && Civic.user(x.assigned_to); const s = type === 'service_request' ? Civic.sla(x) : null; const c = type === 'proposal' ? Civic.voteCounts(x.id) : null;
          return `<tr data-act="go" data-href="#/lgu/cases/${type}/${x.id}" class="${s && s.overdue ? 'overdue' : ''}"><td class="mono">${ref(type, x.id)}</td><td><b>${esc(titleOf(type, x))}</b>${type === 'service_request' ? `<div class="muted small">${esc(x.department)}</div>` : ''}</td><td><span class="u-cell">${avatar(a, 26)}${esc(a.full_name)}</span></td><td>${badge(x.status)}</td>
          <td>${s ? `${badge(x.priority)} ${s.overdue ? badge('overdue', 'Overdue') : s.open ? `<span class="muted small">${Math.round(s.pct)}% of ${Civic.hrs(s.threshold)}</span>` : ''}` : c ? `<span class="pos">▲ ${c.up}</span> <span class="neg">▼ ${c.down}</span>` : cap(x.category)}</td>
          <td>${type === 'proposal' ? Civic.endorsements(x.id).length : o ? esc(o.full_name) : '<span class="muted">—</span>'}</td><td class="muted">${timeAgo(x.created_at)}</td></tr>`; }).join('') || `<tr><td colspan="7">${empty('inbox', 'No cases match these filters')}</td></tr>`}</tbody></table></div>`;
  }

  App.route('/lgu/cases/:type/:id', {
    title: (p) => ref(p.type, p.id), roles: LGU,
    render: (p, u) => {
      const type = p.type; const x = DB.find(Civic.TABLE[type], p.id); if (!x) return empty('inbox', 'Case not found');
      const a = Civic.user(x.user_id); const prof = Civic.profile(a.id); const sec = Civic.sectorOf(a.id);
      const next = Civic.FLOW[type][x.status] || []; const officers = DB.where('users', y => y.role === 'lgu_officer' && y.status === 'active');
      const s = type === 'service_request' ? Civic.sla(x) : null; const file = x.attachment || x.evidence;
      const fb = DB.where('feedback', f => f.reference_type === type && f.reference_id === x.id);
      const c = type === 'proposal' ? Civic.voteCounts(x.id) : null; const ends = type === 'proposal' ? Civic.endorsements(x.id) : [];
      return `<a class="back" href="#/lgu/cases/${type}">${icon('back')} ${cap(type)}s</a>
      <div class="grid-main">
        <div class="stack">
          <div class="card pad case-hero"><div class="row-between"><span class="eyebrow">${ref(type, x.id)} · ${fmtDT(x.created_at)}</span>${badge(x.status)}</div>
            <h2>${esc(titleOf(type, x))}</h2><p>${esc(x.description || x.details)}</p>
            ${type === 'proposal' ? `<div class="kv2"><div><h5>Justification</h5><p>${esc(x.justification)}</p></div><div><h5>Expected benefits</h5><p>${esc(x.expected_benefits)}</p></div></div>` : ''}
            ${x.image ? `<img class="post-img" src="${x.image.data}" alt="">` : ''}
            <div class="facts">${type === 'service_request' ? `<div><span>Department</span><b>${esc(x.department)}</b></div><div><span>Priority</span><b>${badge(x.priority)}</b></div>${x.location ? `<div><span>Location</span><b>${esc(x.location)}</b></div>` : ''}` : `<div><span>Category</span><b>${cap(x.category)}</b></div>`}
              ${c ? `<div><span>Community votes</span><b><span class="pos">▲ ${c.up}</span> · <span class="neg">▼ ${c.down}</span></b></div><div><span>Sector endorsements</span><b>${ends.length}</b></div>` : ''}</div>
            ${file ? `<button class="file-pill" data-act="view-file" data-t="${Civic.TABLE[type]}" data-id="${x.id}">${icon('clip')} ${esc(file.name)}</button>` : ''}
            ${V.stepper(type, x.status)}
            ${s && x.status !== 'cancelled' ? `<div class="sla ${s.breached ? 'bad' : ''}"><div class="row-between small"><span>${icon('clock')} SLA target ${Civic.hrs(s.threshold)} (${x.priority})</span><b>${s.open ? 'Elapsed' : 'Resolved in'} ${Civic.hrs(s.hours)}${s.overdue ? ' · OVERDUE' : ''}</b></div><div class="sla-t"><span style="width:${s.pct}%"></span></div></div>` : ''}
            ${x.resolution_notes ? `<div class="resolution">${icon('check')}<div><b>Resolution notes</b><p>${esc(x.resolution_notes)}</p></div></div>` : ''}</div>
          ${ends.length ? `<div class="card pad"><h4 class="sec-t">${icon('award')} Sector endorsements</h4>${ends.map(e => `<div class="endorse">${avatar(Civic.user(e.user_id), 32)}<div><b>${esc((DB.find('sectors', e.sector_id) || {}).sector_name || '')}</b> <span class="muted small">by ${esc(Civic.user(e.user_id).full_name)}</span><p>“${esc(e.statement)}”</p></div></div>`).join('')}</div>` : ''}
          <div class="card pad"><h4 class="sec-t">${icon('gavel')} Decision log</h4>${V.timeline(type, x.id)}</div>
          ${fb.length ? `<div class="card pad"><h4 class="sec-t">${icon('star')} Citizen feedback</h4>${fb.map(f => `<div class="row-between"><span>${esc(f.comments || 'No comment')}</span>${stars(f.rating)}</div>`).join('')}</div>` : ''}
        </div>
        <div class="stack sticky-col">
          <div class="card pad"><h4 class="sec-t">Submitted by</h4><div class="u-cell lg">${avatar(a, 44)}<div>${V.who(a)}<div class="muted small">${esc(a.email)}</div></div></div>
            <div class="facts tight"><div><span>Contact</span><b>${esc(a.contact_number || '—')}</b></div><div><span>Barangay</span><b>${esc(prof ? prof.barangay : '—')}</b></div><div><span>Sector</span><b>${esc(sec ? sec.sector_name : '—')}</b></div></div></div>
          ${type !== 'proposal' ? `<div class="card pad"><h4 class="sec-t">Assignment</h4><form data-form="assign" data-t="${type}" data-id="${x.id}" class="stack"><select name="officer"><option value="">Unassigned</option>${officers.map(o => `<option value="${o.id}" ${x.assigned_to === o.id ? 'selected' : ''}>${esc(o.full_name)} — ${esc(o.department || '')}</option>`).join('')}</select>
            ${type === 'service_request' ? `<select name="priority">${['urgent', 'high', 'normal', 'low'].map(v => `<option value="${v}" ${x.priority === v ? 'selected' : ''}>${cap(v)} priority</option>`).join('')}</select>` : ''}<button class="btn" type="submit">Save assignment</button></form></div>` : ''}
          <div class="card pad decide"><h4 class="sec-t">${icon('gavel')} Record decision</h4>
            ${next.length ? `<form data-form="decide" data-t="${type}" data-id="${x.id}" class="stack"><div class="form-err" hidden></div>
              <div class="choice-list">${next.map((n, i) => `<label><input type="radio" name="to" value="${n}" ${i === 0 ? 'checked' : ''}><span>${badge(n)} <em class="muted small">${cap(x.status)} → ${cap(n)}</em></span></label>`).join('')}</div>
              ${type === 'complaint' ? `<label class="field"><span>Resolution notes ${x.status !== 'filed' ? '(required to resolve)' : ''}</span><textarea name="resolution_notes" rows="2">${esc(x.resolution_notes || '')}</textarea></label>` : ''}
              <label class="field"><span>Written justification <em>(required · visible to the citizen)</em></span><textarea name="justification" rows="4" required minlength="15" placeholder="Explain the basis for this decision…"></textarea></label>
              <button class="btn primary block" type="submit">Record decision</button><p class="muted small">This decision is permanent, time-stamped, and attributed to you.</p></form>`
              : `<div class="note muted">${icon('lock')} This case is in a final state (${cap(x.status)}). No further decisions can be recorded.</div>`}</div>
        </div></div>`;
    }
  });

  App.route('/lgu/sla', {
    title: 'SLA & Performance', roles: LGU,
    render: () => {
      const R = DB.all('service_requests').filter(r => r.status !== 'cancelled'); const sl = R.map(r => ({ r, s: Civic.sla(r) }));
      const resolved = sl.filter(x => ['resolved', 'closed'].includes(x.r.status));
      const avgH = resolved.length ? resolved.reduce((a, b) => a + b.s.hours, 0) / resolved.length : 0;
      const overdue = sl.filter(x => x.s.overdue); const within = resolved.filter(x => !x.s.breached).length;
      const depts = [...new Set(R.map(r => r.department))].map(dp => { const l = sl.filter(x => x.r.department === dp); const rs = l.filter(x => ['resolved', 'closed'].includes(x.r.status)); const ids = l.map(x => x.r.id);
        const fb = DB.where('feedback', f => f.reference_type === 'service_request' && ids.includes(f.reference_id));
        return { dp, total: l.length, res: rs.length, avg: rs.length ? rs.reduce((a, b) => a + b.s.hours, 0) / rs.length : 0, over: l.filter(x => x.s.overdue).length, comp: rs.length ? Math.round(rs.filter(x => !x.s.breached).length / rs.length * 100) : null, rating: avgRating(fb) }; });
      const officers = DB.where('users', u => u.role === 'lgu_officer').map(o => { const asg = DB.where('service_requests', r => r.assigned_to === o.id).length + DB.where('complaints', r => r.assigned_to === o.id).length;
        const dec = DB.where('decision_logs', d => d.decided_by === o.id).length; const fb = DB.all('feedback').filter(f => f.reference_type !== 'general' && (DB.find(Civic.TABLE[f.reference_type], f.reference_id) || {}).assigned_to === o.id); return { o, asg, dec, rating: avgRating(fb) }; });
      const th = DB.settings.sla;
      return `<div class="kpis">${kpi('request', 'Total requests', R.length)}${kpi('check', 'Resolved', resolved.length, R.length ? Math.round(resolved.length / R.length * 100) + '% of total' : '')}${kpi('clock', 'Avg. resolution', Civic.hrs(avgH), 'created → resolved')}${kpi('complaint', 'Overdue', overdue.length, 'open past target', overdue.length ? 'bad' : '')}${kpi('shield', 'SLA compliance', resolved.length ? Math.round(within / resolved.length * 100) + '%' : '–', 'resolved within target')}</div>
        <div class="grid-main"><div class="card"><div class="card-h"><h4>Department performance</h4></div><div class="table-wrap"><table class="tbl"><thead><tr><th>Department</th><th>Total</th><th>Resolved</th><th>Avg. time</th><th>Overdue</th><th>Compliance</th><th>Rating</th></tr></thead><tbody>
          ${depts.map(d => `<tr><td><b>${esc(d.dp)}</b></td><td>${d.total}</td><td>${d.res}</td><td>${d.res ? Civic.hrs(d.avg) : '–'}</td><td>${d.over ? badge('overdue', d.over) : 0}</td><td>${d.comp == null ? '–' : `<span class="mini-bar"><span style="width:${d.comp}%"></span></span> ${d.comp}%`}</td><td>${d.rating}</td></tr>`).join('')}</tbody></table></div></div>
          <div class="card pad"><h4 class="sec-t">SLA targets by priority</h4>${bars(['urgent', 'high', 'normal', 'low'].map(k => ({ label: cap(k), value: th[k], tone: UI.TONE[k] })), { fmt: v => Civic.hrs(v) })}<p class="muted small mt">Targets are set by the administrator in Settings.</p></div></div>
        <div class="grid-2"><div class="card"><div class="card-h"><h4>Overdue requests</h4></div><div class="list">${overdue.map(({ r, s }) => `<a class="row-item" href="#/lgu/cases/service_request/${r.id}"><span class="ri-ic bad">${icon('clock')}</span><div class="ri-main"><b>${esc(r.service_type)}</b><span class="muted small">${ref('service_request', r.id)} · ${esc(r.department)} · ${Civic.hrs(s.hours - s.threshold)} over target</span></div>${badge(r.priority)}</a>`).join('') || empty('check', 'No overdue requests')}</div></div>
          <div class="card"><div class="card-h"><h4>Officer performance</h4></div><div class="table-wrap"><table class="tbl"><thead><tr><th>Officer</th><th>Assigned</th><th>Decisions</th><th>Rating</th></tr></thead><tbody>${officers.map(x => `<tr><td><span class="u-cell">${avatar(x.o, 26)}${esc(x.o.full_name)}</span></td><td>${x.asg}</td><td>${x.dec}</td><td>${x.rating}</td></tr>`).join('')}</tbody></table></div></div></div>`;
    }
  });

  /* ================= ADMIN ================= */
  App.route('/admin', {
    title: 'Overview', roles: ['admin'],
    render: (p, u) => {
      const U = DB.all('users'); const pend = DB.where('citizen_profiles', x => !x.verified && x.valid_id_path); const pendAcc = U.filter(x => x.status === 'pending');
      const roles = ['citizen', 'sector_rep', 'lgu_officer', 'admin'].map((r, i) => ({ label: App.ROLE_LABEL[r], value: U.filter(x => x.role === r).length, tone: ['brand', 'amber', 'blue', 'violet'][i] }));
      const all = [['Proposals', 'proposals', 'proposal'], ['Service requests', 'service_requests', 'service_request'], ['Complaints', 'complaints', 'complaint']];
      return `<div class="greet"><div><span class="eyebrow">System administration</span><h2>CivicLink at a glance</h2></div><a class="btn" href="#/admin/reports">${icon('chart')} Full reports</a></div>
        <div class="kpis">${kpi('users', 'Registered users', U.length, pendAcc.length + ' pending activation')}${kpi('shield', 'ID verifications', pend.length, 'awaiting review', pend.length ? 'warn' : '')}${kpi('proposal', 'Proposals', DB.all('proposals').length)}${kpi('inbox', 'Open cases', DB.where('service_requests', x => openStates.service_request.includes(x.status)).length + DB.where('complaints', x => openStates.complaint.includes(x.status)).length)}${kpi('consult', 'Open consultations', DB.where('consultations', Civic.isOpen).length)}</div>
        <div class="grid-3"><div class="card pad"><h4 class="sec-t">Users by role</h4>${donut(roles, `<b>${U.length}</b><span>users</span>`)}</div>
          ${all.map(([l, t, ty]) => `<div class="card pad"><h4 class="sec-t">${l}</h4>${bars(Civic.STATUSES[ty].map(s => ({ label: cap(s), value: DB.where(t, x => x.status === s).length, tone: UI.TONE[s] })))}</div>`).join('')}</div>
        <div class="grid-2"><div class="card"><div class="card-h"><h4>Pending verification & activation</h4><a class="small" href="#/admin/users">Manage users</a></div><div class="list">${[...pendAcc.map(x => [x, 'Account pending']), ...pend.map(pp => [Civic.user(pp.user_id), 'ID uploaded'])].filter(([x]) => x).map(([x, why]) => `<div class="row-item static">${avatar(x, 36)}<div class="ri-main"><b>${esc(x.full_name)}</b><span class="muted small">${esc(x.email)} · ${why}</span></div><button class="btn sm" data-act="edit-user" data-id="${x.id}">Review</button></div>`).join('') || empty('check', 'Nothing to review')}</div></div>
          <div class="card"><div class="card-h"><h4>Latest activity</h4><a class="small" href="#/admin/audit">Audit logs</a></div><div class="list">${DB.all('audit_logs').sort((a, b) => new Date(b.created_at) - new Date(a.created_at)).slice(0, 7).map(l => { const x = Civic.user(l.user_id); return `<div class="row-item static"><span class="ri-ic">${icon('activity')}</span><div class="ri-main"><b>${esc(l.action)}</b><span class="muted small">${esc(x ? x.full_name : 'System')} · ${esc(l.details)}</span></div><span class="muted small">${timeAgo(l.created_at)}</span></div>`; }).join('')}</div></div></div>`;
    }
  });

  App.route('/admin/users', {
    title: 'User Management', roles: ['admin'],
    render: () => { const f = V.uf || (V.uf = { q: '', role: 'all', status: 'all' });
      const list = DB.all('users').filter(x => (f.role === 'all' || x.role === f.role) && (f.status === 'all' || x.status === f.status || (f.status === 'unverified' && Civic.profile(x.id) && !Civic.profile(x.id).verified)) && (!f.q || (x.full_name + x.email).toLowerCase().includes(f.q.toLowerCase()))).sort((a, b) => b.id - a.id);
      return `<div class="toolbar"><div class="search">${icon('search')}<input data-filter="uf.q" value="${esc(f.q)}" placeholder="Search name or email"></div>
          <select data-filter="uf.role"><option value="all">All roles</option>${Object.entries(App.ROLE_LABEL).map(([k, l]) => `<option value="${k}" ${f.role === k ? 'selected' : ''}>${l}</option>`).join('')}</select>
          <select data-filter="uf.status">${[['all', 'All statuses'], ['active', 'Active'], ['pending', 'Pending'], ['suspended', 'Suspended'], ['unverified', 'Unverified ID']].map(([k, l]) => `<option value="${k}" ${f.status === k ? 'selected' : ''}>${l}</option>`).join('')}</select>
          <button class="btn primary push" data-act="new-user">${icon('plus')} Add user</button></div>
        <div class="card table-wrap"><table class="tbl"><thead><tr><th>User</th><th>Role</th><th>Sector / Dept.</th><th>Status</th><th>Verified</th><th>Joined</th><th></th></tr></thead><tbody>
        ${list.map(x => { const pr = Civic.profile(x.id); const s = Civic.sectorOf(x.id); return `<tr><td><span class="u-cell">${avatar(x, 32)}<span><b>${esc(x.full_name)}</b><span class="muted small block">${esc(x.email)}</span></span></span></td><td>${App.ROLE_LABEL[x.role]}</td><td>${esc(s ? s.sector_name : x.department || '—')}</td><td>${badge(x.status)}</td>
          <td>${pr ? (pr.verified ? `<span class="pos">${icon('verified')} Verified</span>` : pr.valid_id_path ? badge('pending', 'ID submitted') : '<span class="muted">No ID</span>') : '<span class="muted">n/a</span>'}</td><td class="muted">${fmtDate(x.created_at)}</td><td><button class="btn sm ghost" data-act="edit-user" data-id="${x.id}">${icon('edit')} Manage</button></td></tr>`; }).join('')}</tbody></table></div>`; }
  });
  function userForm(x) {
    const pr = x ? Civic.profile(x.id) : null; const me = Civic.me();
    return `<form data-form="save-user" data-id="${x ? x.id : ''}" class="stack"><div class="form-err" hidden></div>
      ${x ? `<div class="u-cell lg">${avatar(x, 48)}<div><b>${esc(x.full_name)}</b><div class="muted small">${esc(x.email)} · joined ${fmtDate(x.created_at)}${x.last_login ? ' · last login ' + timeAgo(x.last_login) + ' ago' : ''}</div></div></div>` :
      `<label class="field"><span>Full name</span><input name="full_name" required></label><label class="field"><span>Email</span><input name="email" type="email" required></label><label class="field"><span>Temporary password</span><input name="password" minlength="8" required value="password123"></label>`}
      <div class="grid2"><label class="field"><span>Role</span><select name="role" ${x && x.id === me.id ? 'disabled' : ''}>${Object.entries(App.ROLE_LABEL).map(([k, l]) => `<option value="${k}" ${x && x.role === k ? 'selected' : ''}>${l}</option>`).join('')}</select></label>
      <label class="field"><span>Status</span><select name="status" ${x && x.id === me.id ? 'disabled' : ''}>${['active', 'pending', 'suspended'].map(k => `<option value="${k}" ${x && x.status === k ? 'selected' : ''}>${cap(k)}</option>`).join('')}</select></label></div>
      <div class="grid2"><label class="field"><span>Department (LGU officers)</span><select name="department"><option value="">—</option>${Civic.DEPARTMENTS.map(dp => `<option ${x && x.department === dp ? 'selected' : ''}>${dp}</option>`).join('')}</select></label>
      <label class="field"><span>Sector (citizens & reps)</span><select name="sector_id"><option value="">—</option>${DB.all('sectors').map(s => `<option value="${s.id}" ${pr && pr.sector_id === s.id ? 'selected' : ''}>${esc(s.sector_name)}</option>`).join('')}</select></label></div>
      ${pr ? `<div class="verify-box"><div>${icon('shield')}<b>Identity</b> ${pr.valid_id_path ? `<button type="button" class="file-pill" data-act="view-id" data-id="${pr.id}">${icon('clip')} ${esc(pr.valid_id_path.name)}</button>` : '<span class="muted small">No ID uploaded</span>'}</div><label class="check"><input type="checkbox" name="verified" ${pr.verified ? 'checked' : ''}> Mark as verified</label></div>` : ''}
      <div class="form-actions">${x ? `<button type="button" class="btn ghost" data-act="reset-pw" data-id="${x.id}">${icon('lock')} Reset password</button>` : ''}<button type="button" class="btn ghost" data-act="close-modal">Cancel</button><button class="btn primary" type="submit">Save</button></div></form>`;
  }

  App.route('/admin/sectors', {
    title: 'Sector Management', roles: ['admin'],
    render: () => `<div class="page-head"><div><h2>Sectors</h2><p class="muted">Community sectors citizens belong to and representatives speak for.</p></div><button class="btn primary" data-act="edit-sector">${icon('plus')} Add sector</button></div>
      <div class="grid-3">${DB.all('sectors').map(s => { const m = DB.where('citizen_profiles', x => x.sector_id === s.id).map(x => x.user_id); const reps = m.filter(id => (Civic.user(id) || {}).role === 'sector_rep').map(Civic.user); const e = DB.where('endorsements', x => x.sector_id === s.id).length;
        return `<div class="card pad sector-card"><div class="row-between"><span class="ri-ic">${icon('layers')}</span><div class="btn-row"><button class="icon-btn sm" data-act="edit-sector" data-id="${s.id}" aria-label="Edit">${icon('edit')}</button><button class="icon-btn sm" data-act="del-sector" data-id="${s.id}" aria-label="Delete">${icon('trash')}</button></div></div>
          <h3>${esc(s.sector_name)}</h3><p class="muted small">${esc(s.description || '')}</p><div class="mini-stats"><span><b>${m.length}</b> members</span><span><b>${reps.length}</b> reps</span><span><b>${e}</b> endorsements</span></div>
          <div class="reps">${reps.map(r => avatar(r, 28)).join('')}${reps.length ? `<span class="muted small">${reps.map(r => esc(r.full_name)).join(', ')}</span>` : '<span class="muted small">No representative yet</span>'}</div></div>`; }).join('')}</div>`
  });

  App.route('/admin/consultations', {
    title: 'Consultation Moderation', roles: ['admin'],
    render: () => `<div class="page-head"><div><h2>Consultations</h2><p class="muted">Create, schedule, close, and moderate public consultations.</p></div><button class="btn primary" data-act="edit-consult">${icon('plus')} New consultation</button></div>
      <div class="card table-wrap"><table class="tbl"><thead><tr><th>Consultation</th><th>Schedule</th><th>Status</th><th>Responses</th><th>Hidden</th><th></th></tr></thead><tbody>
      ${DB.all('consultations').sort((a, b) => b.id - a.id).map(c => { const t = Civic.consultTally(c.id, true); const hid = DB.where('consultation_responses', r => r.consultation_id === c.id && r.hidden).length;
        return `<tr><td><b>${esc(c.title)}</b><div class="muted small clamp1">${esc(c.description)}</div></td><td class="muted small">${fmtDate(c.start_date)} – ${fmtDate(c.end_date)}</td><td>${badge(Civic.isOpen(c) ? 'open' : c.status === 'open' ? 'pending' : 'closed', Civic.isOpen(c) ? 'Live' : c.status === 'open' ? (c.start_date > new Date().toISOString().slice(0, 10) ? 'Scheduled' : 'Ended') : 'Closed')}</td><td>${t.total}</td><td>${hid}</td>
        <td class="nowrap"><button class="btn sm ghost" data-act="mod-responses" data-id="${c.id}">${icon('eye')} Responses</button><button class="icon-btn sm" data-act="edit-consult" data-id="${c.id}" aria-label="Edit">${icon('edit')}</button><button class="btn sm ghost" data-act="toggle-consult" data-id="${c.id}">${c.status === 'open' ? 'Close' : 'Reopen'}</button><button class="icon-btn sm" data-act="del-consult" data-id="${c.id}" aria-label="Delete">${icon('trash')}</button></td></tr>`; }).join('')}</tbody></table></div>`
  });

  /* ---------- reports ---------- */
  const workDays = (a, b) => { let d = new Date(a), n = 0; const e = new Date(b); while (d < e) { const w = d.getDay(); if (w && w !== 6) n++; d = new Date(d.getTime() + 864e5); } return n; };
  const pct = (a, b) => b ? Math.round(a / b * 100) : null;
  function delta(cur, prev) { if (prev == null) return ''; if (!prev && !cur) return '<span class="delta flat">— no change</span>'; if (!prev) return '<span class="delta up">▲ new</span>'; const d = Math.round((cur - prev) / prev * 100); return `<span class="delta ${d > 0 ? 'up' : d < 0 ? 'down' : 'flat'}">${d > 0 ? '▲' : d < 0 ? '▼' : '—'} ${Math.abs(d)}%</span>`; }
  const kpi2 = (ic, label, val, d, sp, tone) => `<div class="kpi2 card"><div class="k2-h"><span class="kpi-ic t-${tone || 'brand'}">${icon(ic)}</span><span class="kpi-l">${label}</span></div><b class="kpi-v">${val}</b><div class="k2-f">${d == null || d === false ? '<span class="delta flat">all time</span>' : d}${sp || ''}</div></div>`;
  App.route('/admin/reports', {
    title: 'Reports & Analytics', roles: ['admin', 'lgu_officer'],
    render: () => {
      const rng = V.rng || '30'; const now = Date.now(); const days = rng === 'all' ? null : Number(rng);
      const t = (x) => new Date(x.created_at).getTime();
      const allRows = ['proposals', 'service_requests', 'complaints', 'consultation_responses'].flatMap(k => DB.all(k));
      const first = allRows.length ? Math.min(...allRows.map(t)) : now - 30 * 864e5;
      const since = days ? now - days * 864e5 : first; const prevSince = days ? since - days * 864e5 : null;
      const inR = (x) => t(x) >= since; const inP = (x) => prevSince != null && t(x) >= prevSince && t(x) < since;
      const sets = (fn) => ({ P: DB.where('proposals', fn), R: DB.where('service_requests', fn), C: DB.where('complaints', fn), F: DB.where('feedback', fn), CR: DB.where('consultation_responses', fn), D: DB.where('decision_logs', fn), VO: DB.where('proposal_votes', fn), CM: DB.where('comments', fn), EN: DB.where('endorsements', fn) });
      const A = sets(inR), B = days ? sets(inP) : null;
      const people = (S) => new Set([...S.P, ...S.R, ...S.C, ...S.CR, ...S.VO, ...S.CM].map(x => x.user_id)).size;
      const avgR = (L) => L.length ? L.reduce((a, b) => a + b.rating, 0) / L.length : null;
      // buckets
      const span = now - since; const bucket = days === 7 ? 864e5 : span <= 120 * 864e5 ? 7 * 864e5 : 30 * 864e5; const nb = Math.max(1, Math.min(16, Math.ceil(span / bucket)));
      const start0 = now - nb * bucket; const lbl = []; for (let i = 0; i < nb; i++) { const d = new Date(start0 + (i + 1) * bucket); lbl.push(d.toLocaleDateString('en-PH', bucket === 864e5 ? { weekday: 'short' } : { month: 'short', day: 'numeric' })); }
      const series = (L) => { const v = new Array(nb).fill(0); L.forEach(x => { const i = Math.floor((t(x) - start0) / bucket); if (i >= 0 && i < nb) v[i]++; }); return v; };
      // service delivery
      const Rv = A.R.filter(r => r.status !== 'cancelled'); const sl = Rv.map(r => ({ r, s: Civic.sla(r) }));
      const resolved = sl.filter(x => ['resolved', 'closed'].includes(x.r.status)); const within = resolved.filter(x => !x.s.breached).length; const overdue = sl.filter(x => x.s.overdue);
      const avgH = resolved.length ? resolved.reduce((a, b) => a + b.s.hours, 0) / resolved.length : 0;
      const cDone = A.C.filter(c => ['resolved', 'dismissed'].includes(c.status));
      const depts = [...new Set(Rv.map(r => r.department))].map(dp => { const l = sl.filter(x => x.r.department === dp); const rs = l.filter(x => ['resolved', 'closed'].includes(x.r.status)); const ids = l.map(x => x.r.id);
        return { dp, total: l.length, res: rs.length, avg: rs.length ? rs.reduce((a, b) => a + b.s.hours, 0) / rs.length : 0, over: l.filter(x => x.s.overdue).length, comp: pct(rs.filter(x => !x.s.breached).length, rs.length), rating: avgR(A.F.filter(f => f.reference_type === 'service_request' && ids.includes(f.reference_id))) }; }).sort((a, b) => b.total - a.total);
      const officers = DB.where('users', x => x.role === 'lgu_officer').map(o => { const mine = [...A.R, ...A.C].filter(r => r.assigned_to === o.id); const done = mine.filter(r => ['resolved', 'closed', 'dismissed'].includes(r.status)).length;
        const fb = A.F.filter(f => f.reference_type !== 'general' && (DB.find(Civic.TABLE[f.reference_type], f.reference_id) || {}).assigned_to === o.id); return { o, asg: mine.length, done, dec: A.D.filter(d => d.decided_by === o.id).length, rating: avgR(fb) }; }).sort((a, b) => b.dec + b.done - (a.dec + a.done));
      // RA 11032
      const wd = resolved.map(x => workDays(x.r.created_at, x.r.resolved_at || x.r.updated_at));
      const eodb = [['Simple', 3], ['Complex', 7], ['Highly technical', 20]].map(([l, n]) => ({ l, n, k: wd.filter(d => d <= n).length }));
      // participation
      const profs = DB.all('citizen_profiles'); const sectorOfUid = (id) => (profs.find(p => p.user_id === id) || {}).sector_id; const brgyOf = (id) => (profs.find(p => p.user_id === id) || {}).barangay;
      const acts = [...A.P, ...A.R, ...A.C, ...A.CR, ...A.VO, ...A.CM];
      const sectorPart = DB.all('sectors').map(sx => ({ label: sx.sector_name, value: acts.filter(x => sectorOfUid(x.user_id) === sx.id).length })).sort((a, b) => b.value - a.value);
      const brgys = {}; acts.forEach(x => { const b = brgyOf(x.user_id); if (b) brgys[b] = (brgys[b] || 0) + 1; });
      const brgyPart = Object.entries(brgys).sort((a, b) => b[1] - a[1]).slice(0, 8).map(([label, value]) => ({ label: 'Brgy. ' + label, value, tone: 'blue' }));
      const cr = { s: A.CR.filter(x => x.choice === 'support').length, n: A.CR.filter(x => x.choice === 'neutral').length, o: A.CR.filter(x => x.choice === 'oppose').length };
      const topCat = Civic.CATEGORIES.map(c => [c, A.P.filter(x => x.category === c).length]).sort((a, b) => b[1] - a[1])[0];
      const allP = DB.all('proposals').filter(inR);
      const fnl = [{ label: 'Submitted', value: allP.length, tone: 'brand' }, { label: 'Reviewed', value: allP.filter(x => x.status !== 'pending').length, tone: 'blue' }, { label: 'Approved', value: allP.filter(x => ['approved', 'implemented'].includes(x.status)).length, tone: 'green' }, { label: 'Implemented', value: allP.filter(x => x.status === 'implemented').length, tone: 'violet' }];
      const pa = people(A), pb = B ? people(B) : null; const ar = avgR(A.F);
      // insights
      const ins = [];
      ins.push(['users', 'brand', `<b>${pa}</b> residents took part${B ? ` — ${pb === 0 ? (pa ? 'new activity' : 'no change') : (pa >= pb ? 'up ' : 'down ') + Math.abs(Math.round((pa - pb) / (pb || 1) * 100)) + '%'} vs. the previous ${days} days` : ''}.`]);
      if (topCat && topCat[1]) ins.push(['proposal', 'brand', `<b>${cap(topCat[0])}</b> is the most-raised topic with ${topCat[1]} proposal${topCat[1] > 1 ? 's' : ''}.`]);
      if (depts[0]) ins.push(['building', 'blue', `<b>${esc(depts[0].dp)}</b> handled the most requests (${depts[0].total})${depts[0].comp != null ? ` with ${depts[0].comp}% SLA compliance` : ''}.`]);
      ins.push(overdue.length ? ['clock', 'red', `<b>${overdue.length} request${overdue.length > 1 ? 's are' : ' is'} overdue</b> — past the service-level target. Prioritize these.`] : ['check', 'green', 'No open requests are past their service-level target.']);
      if (A.CR.length) ins.push(['consult', cr.s >= cr.o ? 'green' : 'amber', `Consultation sentiment is <b>${pct(cr.s, A.CR.length)}% support</b>, ${pct(cr.o, A.CR.length)}% oppose across ${A.CR.length} responses.`]);
      if (ar != null) ins.push(['star', ar >= 4 ? 'green' : ar >= 3 ? 'amber' : 'red', `Citizens rate LGU services <b>${ar.toFixed(1)} / 5</b> on average (${A.F.length} ratings).`]);
      const range = `${fmtDate(since)} – ${fmtDate(now)}`;
      V._report = { range, rows: [['Metric', 'Value', 'Previous period'], ['Proposals', A.P.length, B ? B.P.length : ''], ['Service requests', A.R.length, B ? B.R.length : ''], ['Complaints', A.C.length, B ? B.C.length : ''], ['Consultation responses', A.CR.length, B ? B.CR.length : ''], ['Active participants', pa, pb ?? ''], ['Average rating', ar != null ? ar.toFixed(2) : '', B && avgR(B.F) != null ? avgR(B.F).toFixed(2) : ''], ['SLA compliance %', pct(within, resolved.length) ?? '', ''], ['Overdue requests', overdue.length, ''], ['Average resolution (hours)', avgH.toFixed(1), ''], [], ['Department', 'Requests', 'Resolved', 'Avg hours', 'Overdue', 'SLA %', 'Rating'], ...depts.map(d => [d.dp, d.total, d.res, d.avg.toFixed(1), d.over, d.comp ?? '', d.rating != null ? d.rating.toFixed(1) : ''])] };
      const sec = (id, ic, title, sub, body) => `<section class="rep-sec" id="rep-${id}"><div class="rep-sec-h"><span class="rep-sec-ic">${icon(ic)}</span><div><h3>${title}</h3><p class="muted small">${sub}</p></div></div>${body}</section>`;
      const sp = (L, tone) => UI.spark(series(L), tone);
      return `<div class="rep-hero card">
          <div class="rep-hero-top"><div><span class="eyebrow light">Governance performance report</span><h2>CivicLink LGU Report</h2><p>${range}${B ? ` · compared with the previous ${days} days` : ' · all recorded activity'}</p></div>
            <div class="btn-row no-print"><button class="btn sm light" data-act="export-summary">${icon('download')} Summary CSV</button><button class="btn sm light" data-act="print">${icon('printer')} Print / PDF</button></div></div>
          <div class="rep-hero-bar no-print"><div class="seg sm">${[['7', '7 days'], ['30', '30 days'], ['90', '90 days'], ['all', 'All time']].map(([k, l]) => `<button class="${rng === k ? 'on' : ''}" data-act="rng" data-k="${k}">${l}</button>`).join('')}</div>
            <nav class="rep-nav">${[['summary', 'Summary'], ['participation', 'Participation'], ['service', 'Service delivery'], ['accountability', 'Accountability'], ['compliance', 'Compliance']].map(([k, l]) => `<a href="#/admin/reports" data-act="rep-jump" data-k="${k}">${l}</a>`).join('')}</nav></div>
        </div>
        <div class="print-only"><h1>CivicLink — Governance Performance Report</h1><p>Period: ${range} · Generated ${fmtDT(new Date())} by ${esc(Civic.me().full_name)}</p></div>
        ${sec('summary', 'spark', 'Executive summary', 'Key numbers and what they mean', `
          <div class="kpi2s">${kpi2('proposal', 'Proposals', A.P.length, B && delta(A.P.length, B.P.length), sp(A.P, 'brand'), 'brand')}${kpi2('request', 'Service requests', A.R.length, B && delta(A.R.length, B.R.length), sp(A.R, 'blue'), 'blue')}${kpi2('complaint', 'Complaints', A.C.length, B && delta(A.C.length, B.C.length), sp(A.C, 'amber'), 'amber')}
            ${kpi2('consult', 'Consultation responses', A.CR.length, B && delta(A.CR.length, B.CR.length), sp(A.CR, 'violet'), 'violet')}${kpi2('users', 'Active participants', pa, B && delta(pa, pb), sp([...A.P, ...A.R, ...A.C, ...A.CR, ...A.VO, ...A.CM], 'green'), 'green')}${kpi2('star', 'Avg. citizen rating', ar != null ? ar.toFixed(1) + '<small>/5</small>' : '–', B && avgR(B.F) != null && ar != null ? delta(ar, avgR(B.F)) : '', '', 'gold')}</div>
          <div class="grid-main"><div class="card pad"><h4 class="sec-t">${icon('trend')} Activity trend</h4>${UI.lineChart([{ label: 'Proposals', tone: 'brand', values: series(A.P) }, { label: 'Requests', tone: 'blue', values: series(A.R) }, { label: 'Complaints', tone: 'amber', values: series(A.C) }, { label: 'Consultation responses', tone: 'violet', values: series(A.CR) }], lbl, { label: 'Activity over time' })}</div>
            <div class="card pad insights"><h4 class="sec-t">${icon('spark')} Insights</h4><ul>${ins.map(([ic, tone, h]) => `<li><span class="ins-ic t-${tone}">${icon(ic)}</span><span>${h}</span></li>`).join('')}</ul></div></div>`)}
        ${sec('participation', 'users', 'Participation', 'Who is engaging, where, and how', `
          <div class="mini-kpis">${[['Votes cast', A.VO.length, 'up'], ['Comments', A.CM.length, 'msg'], ['Sector endorsements', A.EN.length, 'award'], ['Consultations open', DB.where('consultations', Civic.isOpen).length, 'consult']].map(([l, v, ic]) => `<div>${icon(ic)}<b>${v}</b><span>${l}</span></div>`).join('')}</div>
          <div class="grid-3"><div class="card pad"><h4 class="sec-t">Proposal pipeline</h4>${UI.funnel(fnl)}<p class="muted small mt">Share of submitted proposals that reached each stage.</p></div>
            <div class="card pad"><h4 class="sec-t">Participation by sector</h4>${bars(sectorPart)}</div>
            <div class="card pad"><h4 class="sec-t">Participation by barangay</h4>${brgyPart.length ? bars(brgyPart) : empty('pin', 'No barangay data')}</div>
            <div class="card pad"><h4 class="sec-t">Proposals by category</h4>${bars(Civic.CATEGORIES.map(c => ({ label: cap(c), value: A.P.filter(x => x.category === c).length })).filter(x => x.value).sort((a, b) => b.value - a.value)) || empty('proposal', 'No proposals')}</div>
            <div class="card pad"><h4 class="sec-t">Consultation sentiment</h4>${donut([{ label: 'Support', value: cr.s, tone: 'green' }, { label: 'Neutral', value: cr.n, tone: 'gray' }, { label: 'Oppose', value: cr.o, tone: 'red' }], `<b>${A.CR.length}</b><span>responses</span>`)}</div>
            <div class="card pad"><h4 class="sec-t">Proposals by status</h4>${donut(Civic.STATUSES.proposal.map(x => ({ label: cap(x), value: A.P.filter(y => y.status === x).length, tone: UI.TONE[x] })), `<b>${A.P.length}</b><span>total</span>`)}</div></div>`)}
        ${sec('service', 'request', 'Service delivery', 'Speed and quality of requests and complaints', `
          <div class="grid-3"><div class="card pad center-c"><h4 class="sec-t">SLA compliance</h4>${UI.gauge(pct(within, resolved.length), 'resolved within target')}<p class="muted small">${within} of ${resolved.length} resolved requests</p></div>
            <div class="card pad center-c"><h4 class="sec-t">Resolution rate</h4>${UI.gauge(pct(resolved.length + cDone.length, Rv.length + A.C.length), 'requests & complaints closed', 'blue')}<p class="muted small">Avg. resolution time <b>${resolved.length ? Civic.hrs(avgH) : '–'}</b></p></div>
            <div class="card pad"><h4 class="sec-t">Service requests by status</h4>${donut(Civic.STATUSES.service_request.map(x => ({ label: cap(x), value: A.R.filter(y => y.status === x).length, tone: UI.TONE[x] })), `<b>${A.R.length}</b><span>requests</span>`)}</div></div>
          <div class="card"><div class="card-h"><h4>Department scorecard</h4><span class="muted small">${overdue.length} overdue</span></div><div class="table-wrap"><table class="tbl"><thead><tr><th>Department</th><th>Requests</th><th>Resolved</th><th>Avg. time</th><th>Overdue</th><th>SLA compliance</th><th>Rating</th></tr></thead><tbody>
            ${depts.map(d => `<tr><td><b>${esc(d.dp)}</b></td><td>${d.total}</td><td>${d.res}</td><td>${d.res ? Civic.hrs(d.avg) : '–'}</td><td>${d.over ? badge('overdue', d.over) : 0}</td><td>${d.comp == null ? '–' : `<span class="mini-bar ${d.comp < 60 ? 'bad' : d.comp < 85 ? 'mid' : ''}"><span style="width:${d.comp}%"></span></span> ${d.comp}%`}</td><td>${d.rating != null ? stars(Math.round(d.rating)) + ' ' + d.rating.toFixed(1) : '–'}</td></tr>`).join('') || `<tr><td colspan="7">${empty('request', 'No requests in this period')}</td></tr>`}</tbody></table></div></div>
          <div class="grid-2"><div class="card pad"><h4 class="sec-t">Complaints by category</h4>${bars(Civic.COMPLAINT_CATS.map(c => ({ label: cap(c), value: A.C.filter(x => x.category === c).length, tone: 'amber' })))}</div>
            <div class="card pad"><h4 class="sec-t">Complaints by status</h4>${donut(Civic.STATUSES.complaint.map(x => ({ label: cap(x), value: A.C.filter(y => y.status === x).length, tone: UI.TONE[x] })), `<b>${A.C.length}</b><span>complaints</span>`)}</div></div>`)}
        ${sec('accountability', 'gavel', 'Accountability', 'Decisions, officers, and citizen satisfaction', `
          <div class="grid-3"><div class="card pad"><h4 class="sec-t">Decisions by outcome</h4>${bars(['approved', 'rejected', 'escalated', 'closed'].map(k => ({ label: cap(k), value: A.D.filter(x => x.decision === k).length, tone: UI.TONE[k] || 'gray' })))}<div class="note ok mt">${icon('check')} ${A.D.length ? '100% of ' + A.D.length + ' decisions' : 'Every decision'} include a written justification (RA 6713).</div></div>
            <div class="card pad"><h4 class="sec-t">Rating distribution</h4>${bars([5, 4, 3, 2, 1].map(n => ({ label: n + ' ★', value: A.F.filter(x => x.rating === n).length, tone: n >= 4 ? 'green' : n === 3 ? 'amber' : 'red' })))}</div>
            <div class="card pad"><h4 class="sec-t">Satisfaction</h4>${UI.gauge(ar != null ? ar / 5 * 100 : null, ar != null ? ar.toFixed(1) + ' of 5 stars' : 'no ratings', 'gold')}<p class="muted small center">${A.F.length} ratings in this period</p></div></div>
          <div class="card"><div class="card-h"><h4>Officer performance</h4></div><div class="table-wrap"><table class="tbl"><thead><tr><th>#</th><th>Officer</th><th>Department</th><th>Assigned</th><th>Closed</th><th>Decisions</th><th>Rating</th></tr></thead><tbody>
            ${officers.map((x, i) => `<tr><td class="muted">${i + 1}</td><td><span class="u-cell">${avatar(x.o, 26)}${esc(x.o.full_name)}</span></td><td class="muted">${esc(x.o.department || '—')}</td><td>${x.asg}</td><td>${x.done}</td><td>${x.dec}</td><td>${x.rating != null ? x.rating.toFixed(1) + ' ★' : '–'}</td></tr>`).join('') || `<tr><td colspan="7">${empty('users', 'No officers')}</td></tr>`}</tbody></table></div></div>`)}
        ${sec('compliance', 'scale', 'Legal compliance', 'How service delivery measures against Philippine law', `
          <div class="grid-main"><div class="card"><div class="card-h"><h4>RA 11032 processing-time compliance</h4><button class="lnk small" data-act="law" data-id="eodb">About RA 11032</button></div><div class="table-wrap"><table class="tbl"><thead><tr><th>Transaction type</th><th>Max. processing time</th><th>Resolved within</th><th>Compliance</th></tr></thead><tbody>
              ${eodb.map(e => { const c = pct(e.k, wd.length); return `<tr><td><b>${e.l}</b></td><td>${e.n} working days</td><td>${e.k} of ${wd.length}</td><td>${c == null ? '–' : `<span class="mini-bar ${c < 60 ? 'bad' : c < 85 ? 'mid' : ''}"><span style="width:${c}%"></span></span> ${c}%`}</td></tr>`; }).join('')}</tbody></table></div>
              <p class="muted small rep-note">Working days exclude weekends. Holidays are not deducted, so actual compliance may be higher.</p></div>
            <div class="card pad stack"><h4 class="sec-t">${icon('shield')} Statements</h4>
              <div class="stmt">${icon('lock')}<div><b>Data Privacy Act (RA 10173)</b><span>This report contains aggregated figures only. No personal information is included in charts or summary exports.</span></div></div>
              <div class="stmt">${icon('eye')}<div><b>Freedom of Information (EO No. 2, s. 2016)</b><span>Proposal decisions and their justifications are published on the community feed.</span></div></div>
              <div class="stmt">${icon('gavel')}<div><b>Code of Conduct (RA 6713)</b><span>${A.D.length} decision${A.D.length === 1 ? '' : 's'} recorded with justification, officer, and timestamp.</span></div></div></div></div>`)}
        <div class="rep-foot card pad"><div><b>Exports</b><p class="muted small">Download raw data for further analysis. Files with personal data must be handled per RA 10173.</p></div><div class="btn-row">${[['proposals', 'Proposals'], ['service_requests', 'Requests'], ['complaints', 'Complaints'], ['feedback', 'Feedback'], ['consultation_responses', 'Consultation responses']].map(([k, l]) => `<button class="btn ghost sm" data-act="export" data-k="${k}">${icon('download')} ${l}</button>`).join('')}</div></div>
        <p class="print-only small">Prepared via CivicLink · Inclusive Urban Governance and Participation System. Figures reflect records in the system at the time of generation.</p>`;
    }
  });

  App.route('/admin/audit', {
    title: 'Audit Logs', roles: ['admin'],
    render: () => { const f = V.af || (V.af = { q: '', action: 'all', from: '', to: '' });
      const actions = [...new Set(DB.all('audit_logs').map(l => l.action))].sort();
      const list = DB.all('audit_logs').filter(l => (f.action === 'all' || l.action === f.action) && (!f.from || l.created_at.slice(0, 10) >= f.from) && (!f.to || l.created_at.slice(0, 10) <= f.to) && (!f.q || (l.action + l.details + ((Civic.user(l.user_id) || {}).full_name || '')).toLowerCase().includes(f.q.toLowerCase()))).sort((a, b) => b.id - a.id);
      V._audit = list;
      return `<div class="toolbar"><div class="search">${icon('search')}<input data-filter="af.q" value="${esc(f.q)}" placeholder="Search user, action, details"></div>
          <select data-filter="af.action"><option value="all">All actions</option>${actions.map(a => `<option ${f.action === a ? 'selected' : ''}>${esc(a)}</option>`).join('')}</select>
          <input type="date" data-filter="af.from" value="${f.from}" aria-label="From"><input type="date" data-filter="af.to" value="${f.to}" aria-label="To">
          <button class="btn ghost sm push" data-act="export-audit">${icon('download')} Export CSV</button></div>
        <div class="card table-wrap"><table class="tbl"><thead><tr><th>#</th><th>When</th><th>User</th><th>Action</th><th>Details</th><th>Source</th></tr></thead><tbody>
        ${list.slice(0, 300).map(l => { const x = Civic.user(l.user_id); return `<tr><td class="mono muted">${l.id}</td><td class="nowrap muted small">${fmtDT(l.created_at)}</td><td>${x ? `<span class="u-cell">${avatar(x, 24)}${esc(x.full_name)}</span>` : '<span class="muted">System</span>'}</td><td><span class="tag">${esc(l.action)}</span></td><td>${esc(l.details)}</td><td class="muted small">${esc(l.ip_address || '')}</td></tr>`; }).join('') || `<tr><td colspan="6">${empty('shield', 'No log entries match')}</td></tr>`}</tbody></table></div><p class="muted small">${list.length} entries${list.length > 300 ? ' (showing latest 300 — export for full list)' : ''}</p>`; }
  });

  App.route('/admin/settings', {
    title: 'Settings', roles: ['admin'],
    render: () => { const th = DB.settings.sla;
      return `<div class="grid-2"><form class="card pad stack" data-form="sla"><h4 class="sec-t">${icon('clock')} SLA targets (hours)</h4><p class="muted small">Requests not resolved within these targets are flagged as overdue.</p><div class="form-err" hidden></div>
          <div class="grid2">${['urgent', 'high', 'normal', 'low'].map(k => `<label class="field"><span>${cap(k)}</span><input type="number" name="${k}" min="1" max="2000" value="${th[k]}" required></label>`).join('')}</div><div class="form-actions"><button class="btn primary" type="submit">Save targets</button></div></form>
        <div class="card pad stack"><h4 class="sec-t">${icon('layers')} Data</h4><div class="note ok">${icon('check')} Connected to the MySQL database through <code>api/index.php</code>.</div>
          <p class="muted small">To restore the sample data, re-import <code>database/civiclink_schema.sql</code> and <code>civiclink_seed.sql</code> in phpMyAdmin. Quick sign-in on the login page is controlled by <code>QUICK_LOGIN</code> in <code>api/config.php</code>.</p>
          <div class="btn-row"><button class="btn" data-act="backup">${icon('download')} Export JSON backup</button></div></div></div>`; }
  });

  /* ---------- actions ---------- */
  App.act({
    'e-tab': (el) => { V.eTab = el.dataset.k; App.refresh(); },
    'export-cases': (el) => { const t = el.dataset.t; const rows = DB.all(Civic.TABLE[t]); UI.downloadCSV(`civiclink-${t}s.csv`, [['Reference', 'Title', 'Citizen', 'Status', t === 'service_request' ? 'Priority' : 'Category', 'Assigned', 'Created', 'Updated'], ...rows.map(x => [ref(t, x.id), titleOf(t, x), (Civic.user(x.user_id) || {}).full_name, x.status, x.priority || x.category, x.assigned_to ? Civic.user(x.assigned_to).full_name : '', x.created_at, x.updated_at])]); },
    'new-user': () => UI.modal('Add user', userForm(null), { wide: true }),
    'edit-user': (el) => UI.modal('Manage user', userForm(DB.find('users', Number(el.dataset.id))), { wide: true, noFocus: true }),
    'view-id': (el) => { const p = DB.find('citizen_profiles', Number(el.dataset.id)); const f = p.valid_id_path; const w = window.open(); if (w) w.document.write(/^image/.test(f.type) ? `<img src="${f.data}" style="max-width:100%">` : `<iframe src="${f.data}" style="width:100%;height:100vh;border:0"></iframe>`); },
    'reset-pw': (el) => UI.confirmBox('Reset password?', 'The password will be set to "password123". Tell the user to change it after signing in.', 'Reset', () => { const x = DB.find('users', Number(el.dataset.id)); DB.update('users', x.id, { password: 'password123' }); Civic.audit('Password Reset', x.email); Civic.notify(x.id, 'Your password was reset by an administrator. Please change it in your profile.', '#/profile'); UI.toast('Password reset to password123'); }),
    'edit-sector': (el) => { const s = el.dataset.id ? DB.find('sectors', Number(el.dataset.id)) : null; UI.modal(s ? 'Edit sector' : 'Add sector', `<form data-form="sector" data-id="${s ? s.id : ''}" class="stack"><div class="form-err" hidden></div><label class="field"><span>Sector name</span><input name="sector_name" value="${esc(s ? s.sector_name : '')}" required maxlength="100"></label><label class="field"><span>Description</span><input name="description" value="${esc(s ? s.description : '')}" maxlength="255"></label><div class="form-actions"><button type="button" class="btn ghost" data-act="close-modal">Cancel</button><button class="btn primary" type="submit">Save</button></div></form>`); },
    'del-sector': (el) => { const s = DB.find('sectors', Number(el.dataset.id)); const n = DB.where('citizen_profiles', x => x.sector_id === s.id).length;
      UI.confirmBox('Delete ' + s.sector_name + '?', n ? `${n} member(s) will be moved to "General Public".` : 'This sector has no members.', 'Delete', () => { const gp = DB.first('sectors', x => x.sector_name === 'General Public' && x.id !== s.id); DB.where('citizen_profiles', x => x.sector_id === s.id).forEach(x => DB.update('citizen_profiles', x.id, { sector_id: gp ? gp.id : null })); DB.remove('sectors', s.id); Civic.audit('Sector Deleted', s.sector_name); UI.toast('Sector deleted'); App.refresh(); }, true); },
    'edit-consult': (el) => { const c = el.dataset.id ? DB.find('consultations', Number(el.dataset.id)) : null; const t = new Date().toISOString().slice(0, 10);
      UI.modal(c ? 'Edit consultation' : 'New consultation', `<form data-form="consult" data-id="${c ? c.id : ''}" class="stack"><div class="form-err" hidden></div><label class="field"><span>Title</span><input name="title" value="${esc(c ? c.title : '')}" required maxlength="200"></label><label class="field"><span>Description</span><textarea name="description" rows="4" required>${esc(c ? c.description : '')}</textarea></label>
        <div class="grid2"><label class="field"><span>Start date</span><input type="date" name="start_date" value="${c ? c.start_date : t}" required></label><label class="field"><span>End date</span><input type="date" name="end_date" value="${c ? c.end_date : ''}" required></label></div>
        ${c ? '' : '<label class="check"><input type="checkbox" name="notify" checked> Notify all citizens and sector representatives</label>'}<div class="form-actions"><button type="button" class="btn ghost" data-act="close-modal">Cancel</button><button class="btn primary" type="submit">${c ? 'Save' : 'Publish'}</button></div></form>`, { wide: true }); },
    'toggle-consult': (el) => { const c = DB.find('consultations', Number(el.dataset.id)); const st = c.status === 'open' ? 'closed' : 'open'; DB.update('consultations', c.id, { status: st });
      if (st === 'closed') DB.where('consultation_responses', r => r.consultation_id === c.id).forEach(r => Civic.notify(r.user_id, `Consultation closed: "${c.title}". See the final results.`, '#/consultations/' + c.id));
      Civic.audit(st === 'closed' ? 'Consultation Closed' : 'Consultation Reopened', c.title); UI.toast('Consultation ' + st); App.refresh(); },
    'del-consult': (el) => { const c = DB.find('consultations', Number(el.dataset.id)); UI.confirmBox('Delete consultation?', `"${c.title}" and all its responses will be permanently removed.`, 'Delete', () => { DB.remove('consultations', c.id); DB.removeWhere('consultation_responses', r => r.consultation_id === c.id); Civic.audit('Consultation Deleted', c.title); UI.toast('Deleted'); App.refresh(); }, true); },
    'mod-responses': (el) => { const id = Number(el.dataset.id); const c = DB.find('consultations', id); const rs = DB.where('consultation_responses', r => r.consultation_id === id);
      UI.modal('Responses · ' + c.title, `<div class="comments">${rs.map(r => { const a = Civic.user(r.user_id); return `<div class="comment ${r.hidden ? 'hidden-r' : ''}">${avatar(a, 32)}<div class="bubble"><div><b>${esc(a ? a.full_name : '')}</b> ${badge(r.choice)} ${r.hidden ? badge('dismissed', 'Hidden') : ''} <span class="muted small">${timeAgo(r.created_at)}</span></div><p>${esc(r.response_text || '— no comment —')}</p></div>
        <div class="btn-row"><button class="icon-btn sm" data-act="hide-resp" data-id="${r.id}" data-c="${id}" aria-label="${r.hidden ? 'Unhide' : 'Hide'}">${icon(r.hidden ? 'eye' : 'eyeoff')}</button><button class="icon-btn sm" data-act="del-resp" data-id="${r.id}" data-c="${id}" aria-label="Remove">${icon('trash')}</button></div></div>`; }).join('') || '<p class="muted">No responses yet.</p>'}</div>`, { wide: true, noFocus: true }); },
    'hide-resp': (el) => { const r = DB.find('consultation_responses', Number(el.dataset.id)); DB.update('consultation_responses', r.id, { hidden: r.hidden ? 0 : 1 }); Civic.audit(r.hidden ? 'Response Hidden' : 'Response Restored', `Response #${r.id}`); App.refresh(); ACT_reopen(el.dataset.c); },
    'del-resp': (el) => { const id = Number(el.dataset.id), c = el.dataset.c; DB.remove('consultation_responses', id); Civic.audit('Response Removed', `Response #${id}`); App.refresh(); ACT_reopen(c); },
    rng: (el) => { V.rng = el.dataset.k; App.refresh(); },
    'rep-jump': (el) => { const t = document.getElementById('rep-' + el.dataset.k); if (t) t.scrollIntoView({ behavior: 'smooth', block: 'start' }); },
    'export-summary': () => { const r = V._report; if (!r) return; UI.downloadCSV('civiclink-report-summary.csv', [['CivicLink Governance Performance Report'], ['Period', r.range], ['Generated', new Date().toISOString()], [], ...r.rows]); Civic.audit('Report Exported', 'Summary ' + r.range); UI.toast('Summary exported'); },
    export: (el) => { const t = el.dataset.k; const rows = DB.all(t); if (!rows.length) return UI.toast('Nothing to export', 'bad'); const cols = Object.keys(rows[0]).filter(k => !['password_hash', 'image', 'attachment', 'evidence'].includes(k)); UI.downloadCSV(`civiclink-${t}.csv`, [cols, ...rows.map(r => cols.map(c => r[c]))]); Civic.audit('Report Exported', t); },
    print: () => { Civic.audit('Report Printed', 'Analytics'); window.print(); },
    'export-audit': () => { UI.downloadCSV('civiclink-audit-logs.csv', [['ID', 'Timestamp', 'User', 'Action', 'Details', 'Source'], ...V._audit.map(l => [l.id, l.created_at, (Civic.user(l.user_id) || {}).full_name || 'System', l.action, l.details, l.ip_address])]); },
    backup: () => { const a = document.createElement('a'); a.href = URL.createObjectURL(new Blob([DB.exportJSON()], { type: 'application/json' })); a.download = 'civiclink-backup.json'; a.click(); },
  });
  function ACT_reopen(cid) { const b = document.createElement('button'); b.dataset.act = 'mod-responses'; b.dataset.id = cid; document.body.appendChild(b); b.click(); b.remove(); }

  App.form({
    decide: (f, d) => { Civic.decide(f.dataset.t, Number(f.dataset.id), d.to, d.justification, { resolution_notes: d.resolution_notes }); UI.toast('Decision recorded & citizen notified'); App.refresh(); },
    assign: (f, d) => { const t = f.dataset.t, id = Number(f.dataset.id); const x = DB.find(Civic.TABLE[t], id); if (String(x.assigned_to || '') !== d.officer) Civic.assign(t, id, d.officer); if (d.priority && d.priority !== x.priority) Civic.setPriority(id, d.priority); UI.toast('Assignment saved'); App.refresh(); },
    'save-user': (f, d) => { const id = Number(f.dataset.id); let x;
      if (!id) { if (DB.first('users', u => u.email.toLowerCase() === d.email.toLowerCase())) throw new Error('Email already exists.'); if (d.password.length < 8) throw new Error('Password must be at least 8 characters.');
        x = DB.insert('users', { full_name: d.full_name, email: d.email, password: d.password, role: d.role, status: d.status, department: d.department || null, contact_number: '', address: '', bio: '' }); Civic.audit('User Created', `${x.email} (${d.role})`); }
      else { x = DB.find('users', id); const patch = { department: d.department || null }; if (d.role) patch.role = d.role; if (d.status) patch.status = d.status;
        if (patch.status && patch.status !== x.status) { Civic.notify(x.id, `Your account status is now ${cap(patch.status)}.`, '#/profile'); Civic.audit('User Status Changed', `${x.email} → ${patch.status}`); }
        if (patch.role && patch.role !== x.role) { Civic.notify(x.id, `Your role was changed to ${App.ROLE_LABEL[patch.role]}.`, '#/feed'); Civic.audit('User Role Changed', `${x.email} → ${patch.role}`); }
        DB.update('users', id, patch); }
      const role = d.role || x.role; let pr = Civic.profile(x.id);
      if ((role === 'citizen' || role === 'sector_rep') && !pr) pr = DB.insert('citizen_profiles', { user_id: x.id, sector_id: null, barangay: '', birthdate: null, valid_id_path: null, verified: 0 });
      if (role === 'sector_rep' && !d.sector_id) throw new Error('Sector representatives must be assigned a sector.');
      if (pr) { const v = d.verified ? 1 : 0; if (v !== pr.verified) { Civic.audit(v ? 'User Verified' : 'Verification Revoked', x.email); if (v) Civic.notify(x.id, 'Your identity has been verified. Welcome aboard!', '#/profile'); } DB.update('citizen_profiles', pr.id, { sector_id: d.sector_id ? Number(d.sector_id) : pr.sector_id, verified: v }); }
      UI.closeModal(); UI.toast('User saved'); App.refresh(); },
    sector: (f, d) => { const id = Number(f.dataset.id); if (DB.first('sectors', s => s.sector_name.toLowerCase() === d.sector_name.toLowerCase() && s.id !== id)) throw new Error('A sector with that name already exists.');
      if (id) DB.update('sectors', id, d); else DB.insert('sectors', d); Civic.audit(id ? 'Sector Updated' : 'Sector Created', d.sector_name); UI.closeModal(); UI.toast('Sector saved'); App.refresh(); },
    consult: (f, d) => { const id = Number(f.dataset.id); if (d.end_date < d.start_date) throw new Error('End date must be on or after the start date.');
      const row = { title: d.title, description: d.description, start_date: d.start_date, end_date: d.end_date };
      if (id) { DB.update('consultations', id, row); Civic.audit('Consultation Updated', d.title); }
      else { const c = DB.insert('consultations', { ...row, created_by: Civic.me().id, status: 'open' }); if (d.notify) Civic.notifyRoles(['citizen', 'sector_rep'], `New consultation: ${c.title}`, '#/consultations/' + c.id); Civic.audit('Consultation Created', c.title); }
      UI.closeModal(); UI.toast('Consultation saved'); App.refresh(); },
    sla: (f, d) => { const s = {}; for (const k of ['urgent', 'high', 'normal', 'low']) { s[k] = Number(d[k]); if (!(s[k] > 0)) throw new Error('Targets must be positive numbers.'); }
      if (!(s.urgent <= s.high && s.high <= s.normal && s.normal <= s.low)) throw new Error('Higher priorities should have shorter (or equal) targets.');
      DB.saveSettings('sla', s); Civic.audit('SLA Targets Updated', JSON.stringify(s)); UI.toast('SLA targets saved'); }
  });
})();
