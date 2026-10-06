/* CivicLink — citizen & shared community views */
(function () {
  const { icon, esc, cap, avatar, badge, timeAgo, fmtDate, fmtDT, ref, empty, kindTag } = UI;
  const CIT = ['citizen', 'sector_rep'];
  const V = window.Views = {};
  const CAT_IC = { housing: 'home', transport: 'route', safety: 'shield', sanitation: 'trash', infrastructure: 'building', health: 'spark', education: 'book', other: 'layers' };

  /* ---------- shared components ---------- */
  V.who = (u, extra = '') => { if (!u) return '<b>Unknown</b>'; const p = Civic.profile(u.id); const s = Civic.sectorOf(u.id);
    return `<b class="who">${esc(u.full_name)}${p && p.verified ? icon('verified', 'vf') : ''}</b>${u.role === 'lgu_officer' ? `<span class="tag gov">${icon('landmark')}LGU · ${esc(u.department || 'Officer')}</span>` : u.role === 'admin' ? `<span class="tag gov">${icon('landmark')}LGU Admin</span>` : u.role === 'sector_rep' ? `<span class="tag accent">${esc(s ? s.sector_name : '')} Rep</span>` : ''}${extra}`; };

  const PATHS = { proposal: { rejected: ['pending', 'under_review', 'rejected'], _: ['pending', 'under_review', 'approved', 'implemented'] },
    service_request: { cancelled: ['submitted', 'cancelled'], _: ['submitted', 'in_progress', 'resolved', 'closed'] },
    complaint: { dismissed: ['filed', 'investigating', 'dismissed'], _: ['filed', 'investigating', 'mediation', 'resolved'] } };
  const pathOf = (type, status) => PATHS[type][status] || PATHS[type]._;
  V.stepper = (type, status) => {
    const p = pathOf(type, status); const idx = p.indexOf(status);
    return `<ol class="stepper" aria-label="Workflow progress">${p.map((s, i) => `<li class="${i < idx ? 'done' : i === idx ? 'cur t-' + UI.TONE[s] : ''}" ${i === idx ? 'aria-current="step"' : ''}><span>${i < idx ? icon('check') : i + 1}</span><em>${cap(s)}</em></li>`).join('')}</ol>`;
  };
  // compact stage indicator used on activity cards and case rows
  V.progress = (type, status) => {
    const p = pathOf(type, status); const idx = p.indexOf(status); const t = UI.TONE[status] || 'brand';
    return `<div class="stage" aria-label="Stage ${idx + 1} of ${p.length}: ${cap(status)}"><span class="stage-bar">${p.map((s, i) => `<i class="${i < idx ? 'done' : i === idx ? 'cur t-' + t : ''}"></i>`).join('')}</span><span class="stage-l">Stage ${idx + 1} of ${p.length} · <b>${cap(status)}</b></span></div>`;
  };
  // vertical civic case timeline: every stage, with the date it was reached
  V.track = (type, x) => {
    const p = pathOf(type, x.status); const idx = p.indexOf(x.status); const ds = Civic.decisions(type, x.id);
    const when = (s, i) => { if (i === 0) return x.created_at; const d = ds.filter(d => d.to_status === s).slice(-1)[0]; return d ? d.created_at : null; };
    return `<ol class="track">${p.map((s, i) => { const w = i <= idx ? when(s, i) : null; const st = i < idx ? 'done' : i === idx ? 'cur' : 'todo';
      return `<li class="${st} t-${UI.TONE[s] || 'gray'}" ${st === 'cur' ? 'aria-current="step"' : ''}><span class="tr-dot">${st === 'done' ? icon('check') : ''}</span><div><b>${i === 0 ? (type === 'proposal' ? 'Submitted' : 'Reported') + ' · ' : ''}${cap(s)}</b><span>${st === 'cur' ? 'Current stage' + (w ? ' · since ' + fmtDate(w) : '') : w ? fmtDT(w) : st === 'todo' ? 'Upcoming' : 'Completed'}</span></div></li>`; }).join('')}</ol>`;
  };
  // Resident ↔ Community ↔ Local Government ↔ Resolution chain for a case
  V.flow = (type, x) => {
    const a = Civic.user(x.user_id); const o = x.assigned_to ? Civic.user(x.assigned_to) : null; const me = Civic.me();
    const fin = { service_request: ['resolved', 'closed'], complaint: ['resolved', 'dismissed'], proposal: ['approved', 'implemented', 'rejected'] }[type].includes(x.status);
    const stopped = ['cancelled'].includes(x.status);
    const nodes = [
      ['user', 'Resident', a && me && a.id === me.id ? 'You filed this' : esc(a ? a.full_name : 'Resident'), 'done'],
      ['route', type === 'service_request' ? 'Routed to' : 'Category', esc(type === 'service_request' ? x.department : cap(x.category)), 'done'],
      ['landmark', 'Local Government', o ? esc(o.full_name) : 'Awaiting assignment', o || fin ? 'done' : stopped ? 'todo' : 'cur'],
      ['check', 'Resolution', fin ? cap(x.status) : stopped ? 'Cancelled' : 'In progress', fin ? 'done' : o ? 'cur' : 'todo']
    ];
    return `<ol class="flow" aria-label="Resident to government workflow">${nodes.map(([ic, k, v, st]) => `<li class="${st}"><span class="fl-ic">${icon(ic)}</span><span class="fl-t"><em>${k}</em><b>${v}</b></span></li>`).join('')}</ol>`;
  };

  V.timeline = (type, id) => {
    const ds = Civic.decisions(type, id);
    if (!ds.length) return `<div class="note">${icon('clock')}<span>No decisions recorded yet. Every status change by the LGU will appear here with a written justification.</span></div>`;
    return `<ul class="timeline">${ds.map(d => { const o = Civic.user(d.decided_by); return `<li><span class="tl-dot t-${UI.TONE[d.to_status] || 'gray'}"></span>
      <div class="tl-head">${badge(d.to_status)} <span class="muted small">from ${cap(d.from_status)}</span> ${badge(d.decision, 'Decision: ' + cap(d.decision))}<time class="muted small">${fmtDT(d.created_at)}</time></div>
      <p class="tl-body">“${esc(d.justification)}”</p><div class="tl-sign">${icon('landmark', 'xs')} ${esc(o ? o.full_name : 'Officer')}${o && o.department ? ', ' + esc(o.department) : ''}</div></li>`; }).join('')}</ul>`;
  };

  /* ---------- Civic Activity Card: community proposal / initiative ---------- */
  V.proposalCard = (p, u, full) => {
    const a = Civic.user(p.user_id); const c = Civic.voteCounts(p.id); const mv = Civic.myVote(p.id); const tot = c.up + c.down;
    const ends = Civic.endorsements(p.id); const allComs = DB.where('comments', x => x.proposal_id === p.id).sort((x, y) => new Date(x.created_at) - new Date(y.created_at)); const coms = allComs.length;
    const last = Civic.decisions('proposal', p.id).slice(-1)[0]; const lo = last ? Civic.user(last.decided_by) : null;
    const votable = ['pending', 'under_review'].includes(p.status) && CIT.includes(u.role) && p.user_id !== u.id;
    const myEnd = ends.find(e => e.user_id === u.id);
    const secs = [...new Set(ends.map(e => (DB.find('sectors', e.sector_id) || {}).sector_name))].filter(Boolean);
    const long = (p.description || '').length > 220;
    const preview = full ? [] : allComs.slice(-2);
    const prof = Civic.profile(a ? a.id : 0);
    const supPct = tot ? Math.round(c.up / tot * 100) : 0;
    const voices = new Set(allComs.map(k => k.user_id)).size;
    return `<article class="post card act-card k-community" data-pid="${p.id}">
      <header class="ac-head">${kindTag('proposal')}<a class="ac-cat" href="#/feed" data-act="feed-cat" data-k="${p.category}">${icon(CAT_IC[p.category] || 'layers')}${esc(cap(p.category))}</a>
        <span class="ac-ref">${ref('proposal', p.id)}</span>${badge(p.status)}
        <button class="icon-btn sm" data-act="post-menu" data-id="${p.id}" aria-label="More options for ${esc(p.title)}" data-tour="post-menu">${icon('dots')}</button></header>
      <h3 class="ac-title"><a class="post-title" href="#/proposals/${p.id}">${esc(p.title)}</a></h3>
      <p class="post-body ${full || !long ? '' : 'clamp'}">${esc(p.description)}</p>${!full && long ? `<a class="see-more" href="#/proposals/${p.id}">Read full proposal ${icon('arrow', 'xs')}</a>` : ''}
      ${p.image ? `<img class="post-img" src="${p.image.data}" alt="Photo attached to ${esc(p.title)}">` : ''}
      ${full ? `<div class="kv2"><div><h5>${icon('flag')} Justification</h5><p>${esc(p.justification)}</p></div><div><h5>${icon('spark')} Expected benefits</h5><p>${esc(p.expected_benefits)}</p></div></div>` : ''}
      <dl class="ac-facts">
        <div><dt>Proposed by</dt><dd>${avatar(a, 22)}<span>${V.who(a)}</span></dd></div>
        <div><dt>${prof && prof.barangay ? 'Location' : 'Submitted'}</dt><dd>${prof && prof.barangay ? `${icon('pin', 'xs')} Brgy. ${esc(prof.barangay)}` : fmtDate(p.created_at)}</dd></div>
        <div><dt>Participants</dt><dd>${icon('people', 'xs')} <b>${c.up}</b>&nbsp;support${c.down ? ` · <b>${c.down}</b>&nbsp;oppose` : ''}</dd></div>
        <div><dt>Endorsements</dt><dd>${icon('award', 'xs')} <b>${ends.length}</b>&nbsp;sector${ends.length === 1 ? '' : 's'}</dd></div>
      </dl>
      ${V.progress('proposal', p.status)}
      ${secs.length ? `<div class="endorse-strip">${icon('award')}<span>Endorsed by the <b>${secs.map(esc).join(', ')}</b> sector${secs.length > 1 ? 's' : ''}</span></div>` : ''}
      ${last && !full ? `<div class="decision-snip">${icon('seal')}<div><b>Official LGU response · ${cap(last.to_status)}</b><span>${esc(last.justification)}</span><em>${esc(lo ? (lo.department || lo.full_name) : 'Local Government')} · ${fmtDate(last.created_at)}</em></div></div>` : ''}
      <div class="support" aria-label="Community position: ${c.up} support, ${c.down} oppose">${tot ? `<span class="support-bar"><span style="width:${supPct}%"></span></span><span class="support-l"><b>${supPct}%</b> of ${tot} resident${tot === 1 ? '' : 's'} in support</span>` : `<span class="support-l muted">No community positions yet${votable ? ' — be the first to weigh in' : ''}.</span>`}
        <a class="support-c" href="#/proposals/${p.id}">${icon('discuss', 'xs')} ${coms} comment${coms === 1 ? '' : 's'}${voices ? ' · ' + voices + ' participant' + (voices === 1 ? '' : 's') : ''}</a></div>
      <footer class="ac-actions" data-tour="post-actions">
        <div class="position" role="group" aria-label="Your position on this proposal">
          <button class="pos-btn up ${mv === 'up' ? 'on-up' : ''}" ${votable ? `data-act="vote" data-id="${p.id}" data-v="up"` : 'disabled'} aria-pressed="${mv === 'up'}" title="${votable ? 'Support this proposal' : p.user_id === u.id ? 'You can\'t vote on your own proposal' : 'Voting closed'}">${icon('check')}<span>${mv === 'up' ? 'Supporting' : 'Support'}</span></button>
          <button class="pos-btn down ${mv === 'down' ? 'on-down' : ''}" ${votable ? `data-act="vote" data-id="${p.id}" data-v="down"` : 'disabled'} aria-pressed="${mv === 'down'}" title="${votable ? 'Oppose this proposal' : 'Voting closed'}">${icon('x')}<span>${mv === 'down' ? 'Opposing' : 'Oppose'}</span></button>
        </div>
        <a class="btn tertiary sm" href="#/proposals/${p.id}">${icon('discuss')}<span>Discussion</span></a>
        ${u.role === 'sector_rep' && ['pending', 'under_review'].includes(p.status) ? (myEnd ? `<button class="btn accent-soft sm" data-act="unendorse" data-id="${p.id}">${icon('award')}<span>Endorsed</span></button>` : `<button class="btn accent sm" data-act="endorse" data-id="${p.id}">${icon('award')}<span>Endorse</span></button>`) : ''}
        ${Civic.isStaff(u) ? `<a class="btn secondary sm" href="#/lgu/cases/proposal/${p.id}">${icon('gavel')}<span>Manage</span></a>` : ''}
        <button class="btn tertiary sm push" data-act="share" data-id="${p.id}" aria-label="Copy link to this proposal">${icon('link')}<span>Copy link</span></button>
      </footer>
      ${full ? '' : `<div class="post-comments">${preview.length ? `<div class="pc-head"><span>Latest in the discussion</span>${coms > preview.length ? `<a href="#/proposals/${p.id}">View all ${coms}</a>` : ''}</div>` : ''}
        ${preview.map(k => { const ka = Civic.user(k.user_id); return `<div class="comment mini"><div class="c-who">${avatar(ka, 22)}${V.who(ka)}<span class="muted small">· ${timeAgo(k.created_at)}</span></div><p>${esc(k.body)}</p></div>`; }).join('')}
        <form class="comment-form inline" data-form="comment" data-id="${p.id}"><label class="sr-only" for="cf-${p.id}">Add to the discussion</label><input id="cf-${p.id}" name="body" placeholder="Add a constructive comment to this discussion…" maxlength="1000" required autocomplete="off"><button class="btn secondary sm" type="submit">${icon('send')}<span>Post</span></button></form></div>`}
      </article>`;
  };

  /* ---------- Civic bulletin (highlights) ---------- */
  V.stories = (u) => {
    const cons = DB.where('consultations', Civic.isOpen);
    const decs = DB.where('decision_logs', d => d.reference_type === 'proposal' && ['approved', 'implemented', 'rejected'].includes(d.to_status)).sort((a, b) => new Date(b.created_at) - new Date(a.created_at)).slice(0, 4);
    const sec = Civic.sectorOf(u.id);
    const items = [];
    cons.forEach(c => items.push(`<a class="story s-consult" href="#/consultations/${c.id}"><span class="story-tag">${icon('consult')} Consultation open</span><b>${esc(c.title)}</b><em>${icon('people', 'xs')} ${Civic.consultTally(c.id).total} responses · until ${fmtDate(c.end_date)}</em></a>`));
    decs.forEach(d => { const x = DB.find('proposals', d.reference_id); if (x) items.push(`<a class="story s-${d.to_status}" href="#/proposals/${x.id}"><span class="story-tag">${icon('seal')} LGU decision · ${cap(d.to_status)}</span><b>${esc(x.title)}</b><em>${icon('calendar', 'xs')} ${fmtDate(d.created_at)}</em></a>`); });
    if (sec) items.push(`<a class="story s-sector" href="#/proposals"><span class="story-tag">${icon('layers')} Your sector</span><b>${esc(sec.sector_name)}</b><em>${icon('people', 'xs')} ${DB.where('citizen_profiles', x => x.sector_id === sec.id).length} members</em></a>`);
    items.push(`<a class="story s-legal" href="#/legal"><span class="story-tag">${icon('scale')} Know your rights</span><b>Laws & Terms of Use</b><em>${Legal.LAWS.length} supported laws</em></a>`);
    return `<section class="bulletin" aria-labelledby="bul-h"><div class="sec-head"><h2 id="bul-h">${icon('megaphone')} Civic bulletin</h2><span class="muted small">Consultations and official decisions</span></div><div class="stories" data-tour="stories">${items.join('')}</div></section>`;
  };

  /* ---------- Public Consultation card ---------- */
  V.consultCard = (c, u) => {
    const t = Civic.consultTally(c.id); const mine = DB.first('consultation_responses', r => r.consultation_id === c.id && r.user_id === u.id); const open = Civic.isOpen(c);
    const pct = (n) => t.total ? Math.round(n / t.total * 100) : 0;
    const days = Math.ceil((new Date(c.end_date + 'T23:59:59') - new Date()) / 864e5);
    const org = c.created_by ? Civic.user(c.created_by) : null;
    return `<article class="post card act-card consult k-info">
      <header class="ac-head">${kindTag('consultation')}<span class="ac-ref">${esc(org && org.department ? org.department : 'Local Government Unit')}</span>${badge(open ? 'open' : 'closed', open ? (days > 1 ? days + ' days left' : 'Closing today') : (c.status === 'open' ? (new Date(c.start_date) > new Date() ? 'Opens ' + fmtDate(c.start_date) : 'Ended') : 'Closed'))}</header>
      <h3 class="ac-title"><a class="post-title" href="#/consultations/${c.id}">${esc(c.title)}</a></h3>
      <p class="post-body clamp">${esc(c.description)}</p>
      <dl class="ac-facts"><div><dt>Schedule</dt><dd>${icon('calendar', 'xs')} ${fmtDate(c.start_date)} – ${fmtDate(c.end_date)}</dd></div><div><dt>Participants</dt><dd>${icon('people', 'xs')} <b>${t.total}</b>&nbsp;response${t.total === 1 ? '' : 's'}</dd></div>${mine ? `<div><dt>Your position</dt><dd>${badge(mine.choice)}</dd></div>` : ''}</dl>
      <div class="tally"><div class="tally-bar" role="img" aria-label="Support ${pct(t.support)}%, neutral ${pct(t.neutral)}%, oppose ${pct(t.oppose)}%"><span class="s" style="width:${pct(t.support)}%"></span><span class="n" style="width:${pct(t.neutral)}%"></span><span class="o" style="width:${pct(t.oppose)}%"></span></div>
        <div class="tally-l"><span><i class="s"></i>Support ${pct(t.support)}%</span><span><i class="n"></i>Neutral ${pct(t.neutral)}%</span><span><i class="o"></i>Oppose ${pct(t.oppose)}%</span></div></div>
      <footer class="ac-actions">${open && CIT.includes(u.role) ? `<button class="btn sm ${mine ? 'secondary' : 'primary'}" data-act="respond" data-id="${c.id}">${icon('consult')} ${mine ? 'Edit your response' : 'Share your view'}</button>` : ''}<a class="btn sm tertiary ${open && CIT.includes(u.role) ? '' : ''}" href="#/consultations/${c.id}">${icon('chart')} View results</a></footer></article>`;
  };

  /* ---------- Official Civic Notice (government decision) ---------- */
  V.decisionPost = (d) => {
    const o = Civic.user(d.decided_by); const row = DB.find(Civic.TABLE[d.reference_type], d.reference_id); if (!row) return '';
    const title = row[Civic.TITLE[d.reference_type]]; const isPublic = d.reference_type === 'proposal';
    return `<article class="post card notice">
      <div class="notice-band">${icon('seal')}<b>Official Civic Notice</b><span class="nb-office">${icon('landmark', 'xs')} ${esc(o && o.department ? o.department : 'Local Government Unit')}</span><time datetime="${esc(d.created_at)}">${fmtDate(d.created_at)}</time></div>
      <div class="notice-body"><span class="notice-k">Decision on ${esc(UI.KIND[d.reference_type] ? UI.KIND[d.reference_type].label.toLowerCase() : d.reference_type)} · ${ref(d.reference_type, d.reference_id)}</span>
        <h3 class="notice-title">${isPublic ? `<a href="#/proposals/${row.id}">${esc(title)}</a>` : esc(title)}</h3>
        <dl class="notice-facts"><div><dt>Status</dt><dd>${badge(d.to_status)}</dd></div><div><dt>Previous</dt><dd>${cap(d.from_status)}</dd></div><div><dt>Decision</dt><dd>${cap(d.decision)}</dd></div>${row.priority ? `<div><dt>Priority</dt><dd>${badge(row.priority)}</dd></div>` : ''}</dl>
        <blockquote class="notice-text">${esc(d.justification)}</blockquote>
        <div class="notice-foot"><span class="tl-sign">Issued by ${esc(o ? o.full_name : 'LGU Officer')}${o && o.department ? ', ' + esc(o.department) : ''}</span>${isPublic ? `<a class="btn secondary sm" href="#/proposals/${row.id}">${icon('file')} View decision record</a>` : ''}</div></div></article>`;
  };

  /* ---------- Community Activity Hub ---------- */
  const partOfDay = () => { const h = new Date().getHours(); return h < 12 ? 'morning' : h < 18 ? 'afternoon' : 'evening'; };
  V.status = (u) => { // community status board — computed from existing data only
    const R = DB.all('service_requests').filter(r => r.status !== 'cancelled'); const st = DB.stats;
    const reqTotal = st ? st.requests : R.length, done = st ? st.resolved : R.filter(r => ['resolved', 'closed'].includes(r.status)).length;
    const review = DB.where('proposals', x => ['pending', 'under_review'].includes(x.status)).length;
    const cons = DB.where('consultations', Civic.isOpen).length;
    const month = DB.where('decision_logs', d => Date.now() - new Date(d.created_at) < 30 * 864e5).length;
    const myOpen = DB.where('service_requests', x => x.user_id === u.id && ['submitted', 'in_progress'].includes(x.status)).length + DB.where('complaints', x => x.user_id === u.id && ['filed', 'investigating', 'mediation'].includes(x.status)).length;
    const tiles = [
      ['consult', 'info', cons, 'Open consultations', 'Your input is requested', '#/consultations'],
      ['proposal', 'community', review, 'Proposals under review', 'Awaiting LGU decision', '#/proposals'],
      CIT.includes(u.role) ? ['request', 'brand', myOpen, 'Your active cases', 'Requests & concerns in progress', '#/requests'] : ['inbox', 'brand', DB.where('service_requests', x => ['submitted', 'in_progress'].includes(x.status)).length + DB.where('complaints', x => ['filed', 'investigating', 'mediation'].includes(x.status)).length, 'Open community cases', 'Requests & complaints', '#/lgu/cases'],
      ['seal', 'gov', month, 'Official decisions', 'Recorded in the last 30 days', '#/feed'],
      ['check', 'success', (reqTotal ? Math.round(done / reqTotal * 100) : 0) + '%', 'Requests resolved', done + ' of ' + reqTotal + ' service requests', CIT.includes(u.role) ? '#/requests' : '#/lgu/sla']
    ];
    return `<section class="status-board" aria-label="Community status">${tiles.map(([ic, tone, v, l, s, h]) => `<a class="sb-tile t-${tone}" href="${h}" ${h === '#/feed' ? 'data-act="feed-tab" data-k="decisions"' : ''}><span class="sb-ic">${icon(ic)}</span><b>${v}</b><span class="sb-l">${l}</span><em>${s}</em></a>`).join('')}</section>`;
  };
  V.collab = () => { // Community ↔ Government participation chain with live counts
    const P = DB.all('proposals'); const sub = P.length + DB.all('service_requests').length + DB.all('complaints').length;
    const support = DB.all('proposal_votes').length + DB.all('endorsements').length + DB.all('comments').length;
    const review = P.filter(x => x.status === 'under_review').length + DB.where('service_requests', x => x.status === 'in_progress').length + DB.where('complaints', x => ['investigating', 'mediation'].includes(x.status)).length;
    const acted = P.filter(x => ['approved', 'implemented'].includes(x.status)).length + DB.where('service_requests', x => ['resolved', 'closed'].includes(x.status)).length + DB.where('complaints', x => x.status === 'resolved').length;
    const n = [['user', 'Residents', 'raise ideas & concerns', sub, 'submissions'], ['people', 'Community', 'supports & discusses', support, 'votes, endorsements & comments'], ['landmark', 'Local Government', 'reviews & acts', review, 'cases in progress'], ['check', 'Action', 'resolution & progress', acted, 'resolved or approved']];
    return `<section class="collab" aria-labelledby="collab-h"><div class="collab-h"><h2 id="collab-h">Community ${icon('link', 'xs')} Government</h2><p>How participation turns into action on CivicLink</p></div>
      <ol class="collab-chain">${n.map(([ic, t, d, v, l], i) => `<li><span class="cc-ic">${icon(ic)}</span><div><b>${t}</b><em>${d}</em><span class="cc-v"><strong>${v}</strong> ${l}</span></div>${i < n.length - 1 ? `<span class="cc-arrow" aria-hidden="true">${icon('arrow')}</span>` : ''}</li>`).join('')}</ol></section>`;
  };

  App.route('/feed', {
    title: 'Community Hub', layout: 'feed',
    render: (p, u) => {
      const tab = V.feedTab || 'all'; const cat = V.feedCat || 'all';
      let props = DB.all('proposals').filter(x => cat === 'all' || x.category === cat);
      let items = [];
      if (tab === 'all' || tab === 'proposals') items.push(...props.map(x => ({ t: 'p', at: x.created_at, x })));
      if (tab === 'all' || tab === 'consultations') items.push(...DB.where('consultations', c => tab === 'consultations' || Civic.isOpen(c)).map(x => ({ t: 'c', at: x.created_at, x })));
      if (tab === 'all' || tab === 'decisions') items.push(...DB.where('decision_logs', d => d.reference_type === 'proposal' && (tab === 'decisions' || ['approved', 'rejected', 'implemented'].includes(d.to_status))).map(x => ({ t: 'd', at: x.created_at, x })));
      if (tab === 'trending') items = props.filter(x => ['pending', 'under_review'].includes(x.status)).map(x => { const c = Civic.voteCounts(x.id); return { t: 'p', x, score: c.up * 2 + c.down + Civic.endorsements(x.id).length * 5 + DB.where('comments', k => k.proposal_id === x.id).length }; }).sort((a, b) => b.score - a.score);
      else items.sort((a, b) => new Date(b.at) - new Date(a.at));
      const tabs = [['all', 'All activity'], ['trending', 'Gaining support'], ['proposals', 'Proposals'], ['consultations', 'Consultations'], ['decisions', 'Official notices']];
      const prof = Civic.profile(u.id); const first = u.full_name.split(' ')[0];
      const EMPTY = { all: ['inbox', 'No community updates yet', 'There aren\'t any updates for your community right now. Check back later or explore public consultations.'], trending: ['trend', 'No proposals are gathering support yet', 'Active proposals that residents support or endorse will rise here.'], proposals: ['proposal', 'No community proposals in this topic', 'Try another topic, or propose an initiative for your barangay.'], consultations: ['consult', 'No public consultations right now', 'When the LGU asks for residents\' input, consultations will appear here.'], decisions: ['seal', 'No official notices yet', 'Decisions recorded by the LGU will be published here with their justification.'] };
      const e = EMPTY[tab] || EMPTY.all;
      return `<section class="hub-head">
          <div class="hh-txt"><span class="eyebrow">${new Date().toLocaleDateString('en-PH', { weekday: 'long', month: 'long', day: 'numeric' })}${prof && prof.barangay ? ' · Brgy. ' + esc(prof.barangay) : ''}</span>
            <h2>Good ${partOfDay()}, ${esc(first)}</h2><p>Here's what's happening in your community — and how your local government is responding.</p></div>
          ${CIT.includes(u.role) ? `<div class="hh-actions" data-tour="composer" role="group" aria-label="Take civic action">
            <button class="action-tile t-brand" data-act="compose-request">${icon('request')}<span><b>Request a service</b><em>Streetlights, roads, garbage…</em></span></button>
            <button class="action-tile t-warn" data-act="compose-complaint">${icon('complaint')}<span><b>Raise a concern</b><em>Complaints & disputes</em></span></button>
            <button class="action-tile t-community" data-act="compose-proposal">${icon('proposal')}<span><b>Propose an initiative</b><em>Rally community support</em></span></button></div>` : ''}
        </section>
        ${V.status(u)}
        ${V.collab()}
        ${V.stories(u)}
        <section class="activity" aria-labelledby="act-h"><div class="sec-head"><h2 id="act-h">${icon('activity')} Community activity</h2></div>
        <div class="tabs sticky" data-tour="feed-tabs" role="tablist" aria-label="Filter activity">${tabs.map(([k, l]) => `<button role="tab" aria-selected="${tab === k}" class="${tab === k ? 'on' : ''}" data-act="feed-tab" data-k="${k}">${l}</button>`).join('')}</div>
        ${['all', 'trending', 'proposals'].includes(tab) ? `<div class="chips" aria-label="Filter by topic">${['all', ...Civic.CATEGORIES].map(c => `<button class="chip ${cat === c ? 'on' : ''}" aria-pressed="${cat === c}" data-act="feed-cat" data-k="${c}">${c === 'all' ? 'All topics' : icon(CAT_IC[c] || 'layers') + cap(c)}</button>`).join('')}</div>` : ''}
        <div class="feed">${items.length ? items.map(i => i.t === 'p' ? V.proposalCard(i.x, u) : i.t === 'c' ? V.consultCard(i.x, u) : V.decisionPost(i.x)).join('') : empty(e[0], e[1], e[2], tab !== 'consultations' ? '<a class="btn secondary" href="#/consultations">Explore consultations</a>' : '')}</div></section>`;
    },
    rail: (p, u) => V.rail(u)
  });
  V.rail = (u) => {
    const prof = Civic.profile(u.id); const sec = Civic.sectorOf(u.id);
    const mine = { p: DB.where('proposals', x => x.user_id === u.id).length, r: DB.where('service_requests', x => x.user_id === u.id).length, c: DB.where('complaints', x => x.user_id === u.id).length };
    const trend = DB.where('proposals', x => ['pending', 'under_review'].includes(x.status)).map(x => ({ x, n: Civic.voteCounts(x.id).up + Civic.endorsements(x.id).length * 3 })).sort((a, b) => b.n - a.n).slice(0, 4);
    const opens = DB.where('consultations', Civic.isOpen).slice(0, 3);
    const R = DB.all('service_requests').filter(r => r.status !== 'cancelled'); const fb = DB.all('feedback'); const st = DB.stats;
    const reqTotal = st ? st.requests : R.length, done = st ? st.resolved : R.filter(r => ['resolved', 'closed'].includes(r.status)).length;
    const avg = st ? (st.ratings ? st.rating.toFixed(1) : '–') : fb.length ? (fb.reduce((a, b) => a + b.rating, 0) / fb.length).toFixed(1) : '–'; const decCount = st ? st.decisions : DB.all('decision_logs').length;
    const disc = DB.all('proposals').map(x => { const cs = DB.where('comments', k => k.proposal_id === x.id); return { x, n: new Set(cs.map(k => k.user_id)).size, last: cs.reduce((m, k) => Math.max(m, new Date(k.created_at)), 0) }; }).filter(d => d.n).sort((a, b) => b.last - a.last).slice(0, 3);
    return `<section class="card ctx-card profile-mini"><div class="pm-top">${avatar(u, 48)}<div><b>${esc(u.full_name)} ${prof && prof.verified ? icon('verified', 'vf') : ''}</b>
        <span class="muted small">${App.ROLE_LABEL[u.role]}${sec ? ' · ' + esc(sec.sector_name) : ''}</span>${prof && prof.barangay ? `<span class="pm-loc">${icon('pin', 'xs')} Brgy. ${esc(prof.barangay)}</span>` : ''}</div></div>
        ${CIT.includes(u.role) ? `<h3 class="ctx-h">Your civic record</h3><div class="pm-stats"><a href="#/proposals?mine"><b>${mine.p}</b>Proposals</a><a href="#/requests"><b>${mine.r}</b>Requests</a><a href="#/complaints"><b>${mine.c}</b>Concerns</a></div>` : ''}
        ${prof && !prof.verified ? `<a class="note warn" href="#/profile">${icon('shield')}<span>Verify your account — upload a valid ID</span></a>` : ''}</section>
      <section class="card ctx-card" data-tour="rail-trending"><h3 class="ctx-h">${icon('trend')} Gaining community support</h3>${trend.map((t, i) => `<a class="trend" href="#/proposals/${t.x.id}"><span class="rank">${i + 1}</span><div><b>${esc(t.x.title)}</b><span class="muted small">${cap(t.x.category)} · ${Civic.voteCounts(t.x.id).up} supporters</span></div></a>`).join('') || '<p class="muted small ctx-empty">No active proposals right now.</p>'}</section>
      <section class="card ctx-card"><h3 class="ctx-h">${icon('consult')} Open consultations</h3>${opens.map(c => `<a class="trend" href="#/consultations/${c.id}"><span class="rank c">${icon('calendar')}</span><div><b>${esc(c.title)}</b><span class="muted small">Until ${fmtDate(c.end_date)} · ${Civic.consultTally(c.id).total} responses</span></div></a>`).join('') || '<p class="muted small ctx-empty">No consultations are open right now.</p>'}</section>
      ${disc.length ? `<section class="card ctx-card"><h3 class="ctx-h">${icon('discuss')} Active discussions</h3>${disc.map(d => `<a class="trend" href="#/proposals/${d.x.id}"><span class="rank c">${icon('discuss')}</span><div><b>${esc(d.x.title)}</b><span class="muted small">${cap(d.x.category)} · ${d.n} participant${d.n === 1 ? '' : 's'} · ${timeAgo(d.last)}</span></div></a>`).join('')}</section>` : ''}
      <section class="card ctx-card accountability"><h3 class="ctx-h">${icon('landmark')} Government accountability</h3><div class="acc-grid"><div><b>${reqTotal ? Math.round(done / reqTotal * 100) : 0}%</b><span>Requests resolved</span></div><div><b>${avg}<small>/5</small></b><span>Citizen rating</span></div><div><b>${decCount}</b><span>Justified decisions</span></div></div></section>
      <section class="card ctx-card guide"><h3 class="ctx-h">${icon('shield')} Community guidelines</h3><ul><li>Respect privacy — <button class="lnk" data-act="law" data-id="dpa">RA 10173</button></li><li>No libel or harassment — <button class="lnk" data-act="law" data-id="cyber">RA 10175</button> · <button class="lnk" data-act="law" data-id="safe">RA 11313</button></li><li>Emergencies: call <b>911</b></li></ul><a class="small" href="#/legal">Read the Terms of Use</a></section>`;
  };

  /* ---------- proposals ---------- */
  App.route('/proposals', {
    title: 'Community Proposals', layout: 'feed',
    render: (p, u) => {
      const f = V.pf || (V.pf = { scope: 'all', status: 'all', cat: 'all', q: '' }); if (location.hash.includes('?mine')) f.scope = 'mine';
      const list = DB.all('proposals').filter(x => (f.scope === 'all' || x.user_id === u.id) && (f.status === 'all' || x.status === f.status) && (f.cat === 'all' || x.category === f.cat) && (!f.q || (x.title + x.description).toLowerCase().includes(f.q.toLowerCase()))).sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
      return `<div class="page-head"><div><span class="crumb">Community</span><h2>Proposals & initiatives</h2><p class="muted">Ideas from residents, supported by the community and reviewed by the LGU with a written decision.</p></div>${CIT.includes(u.role) ? `<button class="btn primary" data-act="compose-proposal">${icon('plus')} Propose an initiative</button>` : ''}</div>
        <div class="tabs" role="tablist">${[['all', 'All proposals'], ['mine', 'My proposals']].map(([k, l]) => `<button role="tab" aria-selected="${f.scope === k}" class="${f.scope === k ? 'on' : ''}" data-act="pf" data-k="scope" data-v="${k}">${l}</button>`).join('')}</div>
        <div class="toolbar"><label class="search">${icon('search')}<span class="sr-only">Search proposals</span><input data-filter="pf.q" value="${esc(f.q)}" placeholder="Search proposals"></label>
          <label class="sel"><span class="sr-only">Status</span><select data-filter="pf.status"><option value="all">All statuses</option>${Civic.STATUSES.proposal.map(s => `<option value="${s}" ${f.status === s ? 'selected' : ''}>${cap(s)}</option>`).join('')}</select></label>
          <label class="sel"><span class="sr-only">Category</span><select data-filter="pf.cat"><option value="all">All categories</option>${Civic.CATEGORIES.map(s => `<option value="${s}" ${f.cat === s ? 'selected' : ''}>${cap(s)}</option>`).join('')}</select></label></div>
        <div class="feed">${list.map(x => V.proposalCard(x, u)).join('') || empty('proposal', f.scope === 'mine' ? 'You haven\'t proposed anything yet' : 'No proposals match these filters', f.scope === 'mine' ? 'Have an idea that would improve your barangay? Propose it and rally your neighbours\' support.' : 'Try a different status or category, or clear your search.', CIT.includes(u.role) ? `<button class="btn primary" data-act="compose-proposal">${icon('plus')} Propose an initiative</button>` : '')}</div>`;
    },
    rail: (p, u) => V.rail(u)
  });
  App.route('/proposals/:id', {
    title: 'Proposal', layout: 'feed',
    render: (p, u) => {
      const x = DB.find('proposals', p.id); if (!x) return empty('proposal', 'Proposal not found', 'It may have been withdrawn by its author.', '<a class="btn secondary" href="#/proposals">Back to proposals</a>');
      const coms = DB.where('comments', c => c.proposal_id === x.id).sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
      const ends = Civic.endorsements(x.id); const voices = new Set(coms.map(c => c.user_id)).size; const lastC = coms.slice(-1)[0];
      return `<a class="back" href="#/proposals">${icon('back')} All proposals</a>
        ${V.proposalCard(x, u, true)}
        <div class="grid-2 even">
          <section class="card pad"><h3 class="sec-t">${icon('route')} Progress</h3>${V.track('proposal', x)}</section>
          <section class="card pad"><h3 class="sec-t">${icon('gavel')} Decision record</h3>${V.timeline('proposal', x.id)}</section>
        </div>
        ${ends.length ? `<section class="card pad"><h3 class="sec-t">${icon('award')} Sector endorsements</h3>${ends.map(e => { const r = Civic.user(e.user_id); const s = DB.find('sectors', e.sector_id); return `<div class="endorse">${avatar(r, 36)}<div><div>${V.who(r)} <span class="muted small">on behalf of <b>${esc(s ? s.sector_name : '')}</b> · ${timeAgo(e.created_at)}</span></div><p>“${esc(e.statement)}”</p></div></div>`; }).join('')}</section>` : ''}
        <section class="card pad discussion"><div class="disc-head"><span class="kind k-community">${icon('discuss')}<span>Community Discussion</span></span><h3>${esc(x.title)}</h3>
          <dl class="ac-facts"><div><dt>Category</dt><dd>${cap(x.category)}</dd></div><div><dt>Participants</dt><dd>${icon('people', 'xs')} <b>${voices}</b>&nbsp;resident${voices === 1 ? '' : 's'}</dd></div><div><dt>Comments</dt><dd><b>${coms.length}</b></dd></div><div><dt>Latest activity</dt><dd>${lastC ? timeAgo(lastC.created_at) + (/^\d/.test(timeAgo(lastC.created_at)) ? ' ago' : '') : '—'}</dd></div></dl></div>
          <form class="comment-form" data-form="comment" data-id="${x.id}"><label class="sr-only" for="disc-in">Add to the discussion</label><textarea id="disc-in" name="body" rows="2" placeholder="Share a constructive point, question or local knowledge…" maxlength="1000" required></textarea><button class="btn primary sm" type="submit">${icon('send')} Post comment</button></form>
          <p class="muted small c-hint">${icon('scale', 'xs')} Comments are public. Keep it respectful — ${Legal.lawChip('cyber')} ${Legal.lawChip('safe')}</p>
          <div class="comments">${coms.map(c => { const a = Civic.user(c.user_id); return `<div class="comment"><div class="c-who">${avatar(a, 28)}${V.who(a)}<span class="muted small">· ${timeAgo(c.created_at)}</span>${c.user_id === u.id || u.role === 'admin' ? `<button class="icon-btn sm push" data-act="del-comment" data-id="${c.id}" aria-label="Delete comment">${icon('trash')}</button>` : ''}</div><p>${esc(c.body)}</p></div>`; }).join('') || `<p class="muted small">No comments yet — start the discussion.</p>`}</div></section>
        ${x.user_id === u.id && x.status === 'pending' ? `<div class="card pad row-between"><span class="muted">Still pending review. You can withdraw this proposal.</span><button class="btn danger-soft sm" data-act="withdraw" data-id="${x.id}">${icon('trash')} Withdraw</button></div>` : ''}`;
    },
    rail: (p, u) => V.rail(u)
  });

  /* ---------- service requests & complaints (civic case tracker) ---------- */
  function caseList(type, u) {
    const tbl = Civic.TABLE[type]; const key = type === 'service_request' ? 'rf' : 'cf';
    const f = V[key] || (V[key] = { status: 'all', q: '' });
    const mine = DB.where(tbl, x => x.user_id === u.id);
    const list = mine.filter(x => (f.status === 'all' || x.status === f.status) && (!f.q || JSON.stringify(x).toLowerCase().includes(f.q.toLowerCase()))).sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at));
    const counts = Civic.STATUSES[type].map(s => [s, mine.filter(x => x.status === s).length]);
    const isR = type === 'service_request';
    return `<div class="page-head"><div><span class="crumb">Services</span><h2>${isR ? 'My service requests' : 'My concerns & complaints'}</h2><p class="muted">${isR ? 'Track non-emergency public services from report to resolution. For emergencies call 911.' : 'Concerns about services, personnel, facilities, or neighbour disputes — handled privately by the LGU.'}</p></div>
        <button class="btn primary" data-act="${isR ? 'compose-request' : 'compose-complaint'}">${icon('plus')} ${isR ? 'Request a service' : 'Raise a concern'}</button></div>
      <div class="stat-strip" role="group" aria-label="Filter by status">${[['all', mine.length], ...counts].map(([s, n]) => `<button class="stat-chip ${f.status === s ? 'on' : ''}" aria-pressed="${f.status === s}" data-act="${key}" data-v="${s}"><b>${n}</b><span>${s === 'all' ? 'All cases' : cap(s)}</span></button>`).join('')}</div>
      <div class="toolbar"><label class="search">${icon('search')}<span class="sr-only">Search cases</span><input data-filter="${key}.q" value="${esc(f.q)}" placeholder="Search by reference, type, keyword"></label></div>
      <div class="case-list">${list.map(x => {
        const s = isR ? Civic.sla(x) : null; const o = x.assigned_to ? Civic.user(x.assigned_to) : null;
        return `<a class="case-row" href="#/${isR ? 'requests' : 'complaints'}/${x.id}"><span class="cr-ic ${isR ? '' : 'warn'}">${icon(isR ? 'request' : 'complaint')}</span>
          <div class="cr-main"><span class="cr-ref">${ref(type, x.id)} · ${isR ? esc(x.department) : cap(x.category)}</span><b>${esc(isR ? x.service_type : x.subject)}</b><span class="cr-meta">${isR && x.location ? `${icon('pin', 'xs')} ${esc(x.location)} · ` : ''}${o ? 'Handled by ' + esc(o.full_name) : 'Awaiting assignment'}</span></div>
          <div class="cr-prog">${V.progress(type, x.status)}</div>
          <div class="cr-side">${badge(x.status)}${s && s.overdue ? badge('overdue', 'Overdue') : isR ? badge(x.priority) : ''}<span class="muted small">Updated ${timeAgo(x.updated_at)}</span></div>${icon('chev', 'chev')}</a>`; }).join('') || `<div class="card">${empty(isR ? 'request' : 'complaint', mine.length ? 'No cases match this filter' : isR ? 'No service requests yet' : 'No concerns filed yet', mine.length ? 'Try another status or clear your search.' : isR ? 'Report a broken streetlight, missed garbage pickup, clogged drainage and more — then follow it to resolution.' : 'File a concern with supporting evidence and track it until it is resolved.', mine.length ? '' : `<button class="btn primary" data-act="${isR ? 'compose-request' : 'compose-complaint'}">${icon('plus')} ${isR ? 'Request a service' : 'Raise a concern'}</button>`)}</div>`}</div>`;
  }
  function caseDetail(type, id, u) {
    const tbl = Civic.TABLE[type]; const x = DB.find(tbl, id); const isR = type === 'service_request';
    if (!x || (x.user_id !== u.id && !Civic.isStaff(u))) return empty('lock', 'Record not found', 'This case doesn\'t exist or isn\'t visible to your account.', `<a class="btn secondary" href="#/${isR ? 'requests' : 'complaints'}">Back to my cases</a>`);
    const o = x.assigned_to ? Civic.user(x.assigned_to) : null; const s = isR ? Civic.sla(x) : null;
    const fb = DB.first('feedback', f => f.reference_type === type && f.reference_id === x.id && f.user_id === u.id);
    const canRate = x.user_id === u.id && !fb && (isR ? ['resolved', 'closed'] : ['resolved', 'dismissed']).includes(x.status);
    const file = isR ? x.attachment : x.evidence;
    return `<a class="back" href="#/${isR ? 'requests' : 'complaints'}">${icon('back')} ${isR ? 'My service requests' : 'My concerns'}</a>
      <article class="card case-sheet case-hero">
        <header class="cs-head"><div class="row-between">${kindTag(type)}${badge(x.status)}</div>
          <h2>${esc(isR ? x.service_type : x.subject)}</h2>
          <p class="cs-sub"><span class="mono">${ref(type, x.id)}</span>${isR && x.location ? `<span>${icon('pin', 'xs')} ${esc(x.location)}</span>` : ''}<span>${icon('calendar', 'xs')} Reported ${fmtDT(x.created_at)}</span></p></header>
        <dl class="case-facts"><div><dt>Status</dt><dd>${badge(x.status)}</dd></div>${isR ? `<div><dt>Department</dt><dd>${esc(x.department)}</dd></div><div><dt>Priority</dt><dd>${badge(x.priority)}</dd></div>` : `<div><dt>Category</dt><dd>${cap(x.category)}</dd></div>`}
          <div><dt>Handled by</dt><dd>${o ? esc(o.full_name) : '<em class="muted">Not yet assigned</em>'}</dd></div><div><dt>Last update</dt><dd>${timeAgo(x.updated_at)}${/^\d/.test(timeAgo(x.updated_at)) ? ' ago' : ''}</dd></div></dl>
        <div class="cs-desc"><h3>Description</h3><p>${esc(isR ? x.description : x.details)}</p>${file ? `<button class="file-pill" data-act="view-file" data-t="${tbl}" data-id="${x.id}">${icon('clip')} ${esc(file.name)}</button>` : ''}</div>
        ${s && x.status !== 'cancelled' ? `<div class="sla ${s.breached ? 'bad' : ''}"><div class="row-between small"><span>${icon('clock')} Service level target: ${Civic.hrs(s.threshold)} for ${x.priority} priority</span><b>${s.open ? 'Elapsed' : 'Took'} ${Civic.hrs(s.hours)}</b></div><div class="sla-t" role="progressbar" aria-valuenow="${Math.round(s.pct)}" aria-valuemin="0" aria-valuemax="100" aria-label="Time used against target"><span style="width:${s.pct}%"></span></div></div>` : ''}
        <div class="cs-cols"><section><h3>Progress timeline</h3>${V.track(type, x)}</section><section><h3>Resident ${icon('link', 'xs')} Government</h3>${V.flow(type, x)}</section></div>
        ${!isR && x.resolution_notes ? `<div class="resolution">${icon('check')}<div><b>Resolution notes</b><p>${esc(x.resolution_notes)}</p></div></div>` : ''}
        ${x.cancel_reason ? `<div class="note">${icon('x')}<span>Cancelled: ${esc(x.cancel_reason)}</span></div>` : ''}
        <div class="btn-row">${isR && x.status === 'submitted' && x.user_id === u.id ? `<button class="btn danger-soft" data-act="cancel-req" data-id="${x.id}">${icon('x')} Cancel request</button>` : ''}
          ${canRate ? `<button class="btn primary" data-act="rate" data-t="${type}" data-id="${x.id}">${icon('star')} Rate this service</button>` : ''}
          ${fb ? `<span class="rated">You rated ${UI.stars(fb.rating)}</span>` : ''}
          ${Civic.isStaff(u) ? `<a class="btn secondary" href="#/lgu/cases/${type}/${x.id}">${icon('gavel')} Open in case management</a>` : ''}</div></article>
      <section class="card pad"><h3 class="sec-t">${icon('gavel')} Decision & status history</h3>${V.timeline(type, x.id)}</section>`;
  }
  App.route('/requests', { title: 'Service Requests', roles: CIT, layout: 'feed', render: (p, u) => caseList('service_request', u), rail: (p, u) => V.rail(u) });
  App.route('/requests/:id', { title: 'Service Request', layout: 'feed', render: (p, u) => caseDetail('service_request', Number(p.id), u), rail: (p, u) => V.rail(u) });
  App.route('/complaints', { title: 'Concerns & Complaints', roles: CIT, layout: 'feed', render: (p, u) => caseList('complaint', u), rail: (p, u) => V.rail(u) });
  App.route('/complaints/:id', { title: 'Community Concern', layout: 'feed', render: (p, u) => caseDetail('complaint', Number(p.id), u), rail: (p, u) => V.rail(u) });

  /* ---------- consultations ---------- */
  App.route('/consultations', {
    title: 'Public Consultations', layout: 'feed',
    render: (p, u) => { const list = DB.all('consultations').sort((a, b) => (Civic.isOpen(b) - Civic.isOpen(a)) || (new Date(b.start_date) - new Date(a.start_date)));
      return `<div class="page-head"><div><span class="crumb">Community</span><h2>Public consultations</h2><p class="muted">The LGU asks — residents answer. Every result is public.</p></div>${u.role === 'admin' ? `<a class="btn secondary" href="#/admin/consultations">${icon('settings')} Moderate</a>` : ''}</div>
        <div class="feed">${list.map(c => V.consultCard(c, u)).join('') || empty('consult', 'No public consultations yet', 'When your local government asks for residents\' input on plans and policies, consultations will appear here.')}</div>`; },
    rail: (p, u) => V.rail(u)
  });
  App.route('/consultations/:id', {
    title: 'Consultation', layout: 'feed',
    render: (p, u) => { const c = DB.find('consultations', p.id); if (!c) return empty('consult', 'Consultation not found', '', '<a class="btn secondary" href="#/consultations">All consultations</a>');
      const rs = DB.where('consultation_responses', r => r.consultation_id === c.id && !r.hidden && r.response_text).sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
      const t = Civic.consultTally(c.id);
      return `<a class="back" href="#/consultations">${icon('back')} All consultations</a>${V.consultCard(c, u)}
        <section class="card pad"><h3 class="sec-t">${icon('chart')} Results</h3>${UI.donut([{ label: 'Support', value: t.support, tone: 'green' }, { label: 'Neutral', value: t.neutral, tone: 'gray' }, { label: 'Oppose', value: t.oppose, tone: 'red' }], `<b>${t.total}</b><span>responses</span>`)}</section>
        <section class="card pad"><h3 class="sec-t">${icon('discuss')} What residents said · ${rs.length}</h3><div class="comments">${rs.map(r => { const a = Civic.user(r.user_id); return `<div class="comment"><div class="c-who">${avatar(a, 28)}${V.who(a)} ${badge(r.choice)}<span class="muted small">· ${timeAgo(r.created_at)}</span></div><p>${esc(r.response_text)}</p></div>`; }).join('') || '<p class="muted small">No written responses yet.</p>'}</div></section>`; },
    rail: (p, u) => V.rail(u)
  });

  /* ---------- notifications (categorised by civic purpose) ---------- */
  const NCAT = { government: ['landmark', 'Government', 'gov'], reports: ['request', 'Reports & cases', 'brand'], community: ['people', 'Community', 'community'], discussions: ['discuss', 'Discussions', 'community'], consultations: ['consult', 'Consultations', 'info'], system: ['settings', 'System', 'gray'] };
  const ncat = (m) => /consult/i.test(m) ? 'consultations' : /commented/i.test(m) ? 'discussions' : /is now|was (approved|rejected|implemented|under review)|reason:|assigned/i.test(m) ? 'government' : /REQ-|CMP-|request|complaint|★|rating/i.test(m) ? 'reports' : /proposal|endorse|vote/i.test(m) ? 'community' : 'system';
  App.route('/notifications', {
    title: 'Notifications', layout: 'feed',
    render: (p, u) => { const only = V.nUnread; const cf = V.nCat || 'all'; const all = DB.where('notifications', n => n.user_id === u.id).sort((a, b) => new Date(b.created_at) - new Date(a.created_at)); const list = (only ? all.filter(n => !n.is_read) : all).filter(n => cf === 'all' || ncat(n.message) === cf);
      const present = Object.keys(NCAT).filter(k => all.some(n => ncat(n.message) === k));
      return `<div class="page-head"><div><span class="crumb">My Activity</span><h2>Notifications</h2><p class="muted">${all.filter(n => !n.is_read).length} unread · updates on your cases, proposals and consultations</p></div><button class="btn secondary" data-act="read-all">${icon('check')} Mark all as read</button></div>
        <div class="tabs" role="tablist">${[[false, 'All'], [true, 'Unread']].map(([k, l]) => `<button role="tab" aria-selected="${!!only === k}" class="${!!only === k ? 'on' : ''}" data-act="n-filter" data-k="${k}">${l}</button>`).join('')}</div>
        ${present.length > 1 ? `<div class="chips" aria-label="Filter by category"><button class="chip ${cf === 'all' ? 'on' : ''}" aria-pressed="${cf === 'all'}" data-act="n-cat" data-k="all">All categories</button>${present.map(k => `<button class="chip ${cf === k ? 'on' : ''}" aria-pressed="${cf === k}" data-act="n-cat" data-k="${k}">${icon(NCAT[k][0])}${NCAT[k][1]}</button>`).join('')}</div>` : ''}
        <div class="card list notif-list">${list.map(n => { const k = NCAT[ncat(n.message)]; return `<a class="notif ${n.is_read ? '' : 'unread'}" data-act="open-notif" data-id="${n.id}" href="${esc(n.link || '#/notifications')}"><span class="ri-ic t-${k[2]}">${icon(k[0])}</span><div class="ri-main"><span class="n-cat">${k[1]}</span><span class="n-msg">${esc(n.message)}</span><span class="muted small">${timeAgo(n.created_at)}${/^\d/.test(timeAgo(n.created_at)) ? ' ago' : ''}</span></div>${n.is_read ? '' : '<i class="udot" aria-label="Unread"></i>'}</a>`; }).join('') || empty('bell', only ? 'No unread notifications' : 'You\'re all caught up', 'Updates about your cases, proposals and consultations will appear here.')}</div>`; },
    rail: (p, u) => V.rail(u)
  });

  /* ---------- feedback ---------- */
  App.route('/feedback', {
    title: 'Feedback & Ratings', roles: CIT, layout: 'feed',
    render: (p, u) => { const todo = Civic.rateable(u.id); const mine = DB.where('feedback', f => f.user_id === u.id).sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
      return `<div class="page-head"><div><span class="crumb">Services</span><h2>Service feedback</h2><p class="muted">Your ratings help the LGU improve public services — and are part of its performance record.</p></div><button class="btn primary" data-act="rate" data-t="general">${icon('star')} Give general feedback</button></div>
        ${todo.length ? `<section class="card pad"><h3 class="sec-t">${icon('clock')} Waiting for your rating</h3>${todo.map(t => `<div class="row-between rate-row"><span>${icon(t.type === 'complaint' ? 'complaint' : 'request')} ${esc(t.label)}</span><button class="btn sm primary" data-act="rate" data-t="${t.type}" data-id="${t.id}">Rate service</button></div>`).join('')}</section>` : ''}
        <div class="card list">${mine.map(f => `<div class="row-item static"><span class="ri-ic gold">${icon('star')}</span><div class="ri-main"><b>${f.reference_type === 'general' ? 'General service experience' : ref(f.reference_type, f.reference_id)}</b><span class="muted small">${esc(f.comments || 'No comment')}</span></div><div class="ri-side">${UI.stars(f.rating)}<span class="muted small">${timeAgo(f.created_at)}</span></div></div>`).join('') || empty('star', 'No feedback given yet', 'Once your requests or concerns are resolved, you can rate the service here.')}</div>`; },
    rail: (p, u) => V.rail(u)
  });

  /* ---------- profile (civic participation) ---------- */
  App.route('/profile', {
    title: 'My Civic Profile',
    render: (p, u) => { const prof = Civic.profile(u.id); const sec = Civic.sectorOf(u.id);
      const stats = [['proposal', 'Proposals', DB.where('proposals', x => x.user_id === u.id).length], ['check', 'Votes cast', DB.where('proposal_votes', x => x.user_id === u.id).length], ['discuss', 'Comments', DB.where('comments', x => x.user_id === u.id).length], ['consult', 'Consultations', DB.where('consultation_responses', x => x.user_id === u.id).length]];
      if (CIT.includes(u.role)) stats.push(['request', 'Service requests', DB.where('service_requests', x => x.user_id === u.id).length], ['complaint', 'Concerns filed', DB.where('complaints', x => x.user_id === u.id).length]);
      if (Civic.isStaff(u)) stats.splice(0, stats.length, ['gavel', 'Decisions recorded', DB.where('decision_logs', d => d.decided_by === u.id).length], ['inbox', 'Assigned cases', DB.where('service_requests', r => r.assigned_to === u.id).length + DB.where('complaints', r => r.assigned_to === u.id).length]);
      return `<section class="card profile-hero"><div class="ph-row"><label class="ph-av" title="Change photo">${avatar(u, 96)}<input type="file" accept="image/*" data-act-change="photo" class="sr-only" aria-label="Change profile photo"><span class="ph-edit">${icon('edit')}</span></label>
        <div class="ph-info"><span class="eyebrow">${App.ROLE_LABEL[u.role]}</span><h2>${esc(u.full_name)} ${prof && prof.verified ? icon('verified', 'vf lg') : ''}</h2>
          <dl class="ph-about"><div><dt>${icon('pin', 'xs')} Location</dt><dd>${prof && prof.barangay ? 'Brgy. ' + esc(prof.barangay) : esc(u.address || '—')}</dd></div><div><dt>${icon('people', 'xs')} Community</dt><dd>${sec ? esc(sec.sector_name) + ' sector' : u.department ? esc(u.department) : '—'}</dd></div><div><dt>${icon('calendar', 'xs')} Member since</dt><dd>${fmtDate(u.created_at)}</dd></div><div><dt>${icon('shield', 'xs')} Identity</dt><dd>${prof ? (prof.verified ? '<span class="pos">Verified</span>' : prof.valid_id_path ? 'Under review' : 'Not verified') : 'LGU account'}</dd></div></dl>
          ${u.bio ? `<p class="ph-bio">${esc(u.bio)}</p>` : ''}</div></div>
        <div class="ph-stats-h"><h3>Civic activity</h3><span class="muted small">Your participation on CivicLink</span></div>
        <div class="ph-stats">${stats.map(([ic, l, n]) => `<div>${icon(ic)}<b>${n}</b><span>${l}</span></div>`).join('')}</div></section>
        <div class="grid-2">
        <form class="card pad stack" data-form="profile"><h3 class="sec-t">${icon('user')} About you</h3><div class="form-err" role="alert" hidden></div>
          <label class="field"><span>Full name</span><input name="full_name" value="${esc(u.full_name)}" required autocomplete="name"></label>
          <label class="field"><span>Email</span><input value="${esc(u.email)}" disabled></label>
          <div class="grid2"><label class="field"><span>Contact number</span><input name="contact_number" value="${esc(u.contact_number || '')}" autocomplete="tel"></label>
          ${prof ? `<label class="field"><span>Barangay</span><input name="barangay" value="${esc(prof.barangay || '')}"></label>` : ''}</div>
          <label class="field"><span>Address</span><input name="address" value="${esc(u.address || '')}" autocomplete="street-address"></label>
          ${prof ? `<div class="grid2"><label class="field"><span>Sector</span><select name="sector_id" ${u.role === 'sector_rep' ? 'disabled' : ''}>${DB.all('sectors').map(s => `<option value="${s.id}" ${prof.sector_id === s.id ? 'selected' : ''}>${esc(s.sector_name)}</option>`).join('')}</select></label>
          <label class="field"><span>Birthdate</span><input type="date" name="birthdate" value="${esc(prof.birthdate || '')}"></label></div>` : ''}
          <label class="field"><span>Short bio</span><textarea name="bio" rows="2" maxlength="160" placeholder="Tell your community about yourself">${esc(u.bio || '')}</textarea></label>
          <div class="form-actions"><button class="btn primary" type="submit">Save changes</button></div></form>
        <div class="stack">
        ${prof ? `<section class="card pad"><h3 class="sec-t">${icon('shield')} Identity verification</h3>${prof.verified ? `<div class="note ok">${icon('verified')}<span>Your identity is verified. Your contributions show a verified badge.</span></div>` : prof.valid_id_path ? `<div class="note warn">${icon('clock')}<span>ID submitted — waiting for administrator review.</span></div>` : `<div class="note warn">${icon('complaint')}<span>Not verified. Upload a government-issued ID.</span></div>`}
          ${!prof.verified ? `<form data-form="valid-id" class="stack mt"><label class="field"><span>Government-issued ID <em>(JPG, PNG, WEBP or PDF)</em></span><input type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.pdf" required></label><button class="btn secondary" type="submit">${icon('clip')} ${prof.valid_id_path ? 'Replace ID' : 'Upload ID'}</button></form>` : ''}</section>` : ''}
        <form class="card pad stack" data-form="password"><h3 class="sec-t">${icon('lock')} Change password</h3><div class="form-err" role="alert" hidden></div>
          <label class="field"><span>Current password</span><input type="password" name="current" required autocomplete="current-password"></label>
          <label class="field"><span>New password <em>(at least 8 characters)</em></span><input type="password" name="next" minlength="8" required autocomplete="new-password"></label>
          <label class="field"><span>Confirm new password</span><input type="password" name="confirm" required autocomplete="new-password"></label>
          <div class="form-actions"><button class="btn secondary" type="submit">Update password</button></div></form>
        <button class="btn danger-soft block" data-act="logout">${icon('logout')} Sign out</button></div></div>`; },
    after: (root) => { const i = root.querySelector('[data-act-change=photo]'); if (i) i.onchange = async () => { try { const f = await UI.readFile(i.files[0], 0.6); if (!f || !/^image/.test(f.type)) throw new Error('Choose an image.'); DB.update('users', Civic.me().id, { photo: f.data }); UI.toast('Photo updated'); App.refresh(); } catch (e) { UI.toast(e.message, 'bad'); } }; }
  });

  /* ---------- search ---------- */
  App.route('/search/:q', {
    title: 'Search', layout: 'feed',
    render: (p, u) => { const q = p.q.trim().toLowerCase(); if (!q) return empty('search', 'Search CivicLink', 'Find proposals, consultations, your cases and people. Try a reference number like REQ-0002.');
      const m = (s) => String(s || '').toLowerCase().includes(q);
      const props = DB.where('proposals', x => m(x.title) || m(x.description) || m(ref('proposal', x.id)) || m(x.category));
      const cons = DB.where('consultations', x => m(x.title) || m(x.description));
      const staff = Civic.isStaff(u);
      const reqs = DB.where('service_requests', x => (staff || x.user_id === u.id) && (m(x.service_type) || m(x.description) || m(ref('service_request', x.id))));
      const cmps = DB.where('complaints', x => (staff || x.user_id === u.id) && (m(x.subject) || m(x.details) || m(ref('complaint', x.id))));
      const people = DB.where('users', x => m(x.full_name) && x.status !== 'suspended').slice(0, 6);
      const row = (h, ic, t, s) => `<a class="row-item" href="${h}"><span class="ri-ic">${icon(ic)}</span><div class="ri-main"><b>${esc(t)}</b><span class="muted small">${s}</span></div>${icon('chev', 'chev')}</a>`;
      const total = props.length + cons.length + reqs.length + cmps.length + people.length;
      return `<div class="page-head"><div><span class="crumb">Search</span><h2>Results for “${esc(p.q)}”</h2><p class="muted">${total} match${total === 1 ? '' : 'es'}</p></div></div>
        ${props.length ? `<h3 class="sec-t">${icon('proposal')} Proposals</h3><div class="card list">${props.map(x => row('#/proposals/' + x.id, 'proposal', x.title, ref('proposal', x.id) + ' · ' + badge(x.status))).join('')}</div>` : ''}
        ${cons.length ? `<h3 class="sec-t">${icon('consult')} Consultations</h3><div class="card list">${cons.map(x => row('#/consultations/' + x.id, 'consult', x.title, fmtDate(x.start_date) + ' – ' + fmtDate(x.end_date))).join('')}</div>` : ''}
        ${reqs.length ? `<h3 class="sec-t">${icon('request')} Service requests</h3><div class="card list">${reqs.map(x => row(staff ? '#/lgu/cases/service_request/' + x.id : '#/requests/' + x.id, 'request', x.service_type, ref('service_request', x.id) + ' · ' + badge(x.status))).join('')}</div>` : ''}
        ${cmps.length ? `<h3 class="sec-t">${icon('complaint')} Concerns & complaints</h3><div class="card list">${cmps.map(x => row(staff ? '#/lgu/cases/complaint/' + x.id : '#/complaints/' + x.id, 'complaint', x.subject, ref('complaint', x.id) + ' · ' + badge(x.status))).join('')}</div>` : ''}
        ${people.length ? `<h3 class="sec-t">${icon('people')} People</h3><div class="card list">${people.map(x => `<div class="row-item static">${avatar(x, 36)}<div class="ri-main">${V.who(x)}<span class="muted small">${App.ROLE_LABEL[x.role]}</span></div></div>`).join('')}</div>` : ''}
        ${total ? '' : empty('search', 'No matching results', 'Try a reference number like PRP-0001, a street name, or a different keyword.')}`; },
    rail: (p, u) => V.rail(u)
  });

  /* ---------- composer modals ---------- */
  const proposalForm = () => `<form data-form="new-proposal" class="stack"><div class="form-err" role="alert" hidden></div>
    <label class="field"><span>Proposal title</span><input name="title" maxlength="200" required placeholder="e.g. Covered waiting sheds on J.P. Rizal"></label>
    <label class="field"><span>Category</span><select name="category">${Civic.CATEGORIES.map(c => `<option value="${c}">${cap(c)}</option>`).join('')}</select></label>
    <label class="field"><span>Description</span><textarea name="description" rows="3" required placeholder="What is the project and where?"></textarea></label>
    <label class="field"><span>Justification</span><textarea name="justification" rows="2" required placeholder="Why is it needed? Who is affected?"></textarea></label>
    <label class="field"><span>Expected benefits</span><textarea name="expected_benefits" rows="2" required placeholder="What will improve once it is done?"></textarea></label>
    <label class="field"><span>Photo <em>(optional)</em></span><input type="file" name="img" accept="image/*"></label>
    ${Legal.notice('proposal')}
    <div class="form-actions"><button type="button" class="btn secondary" data-act="close-modal">Cancel</button><button class="btn primary" type="submit">Publish proposal</button></div></form>`;
  const requestForm = () => `<form data-form="new-request" class="stack"><div class="form-err" role="alert" hidden></div>
    <div class="note emergency">${icon('alert')}<span>For emergencies (fire, crime, medical), call <b>911</b>. This form is for non-emergency services.</span></div>
    <div class="grid2"><label class="field"><span>Service type</span><select name="service_type" data-act-change="svc" required><option value="">Select…</option>${Object.keys(Civic.SERVICES).map(s => `<option>${s}</option>`).join('')}</select></label>
    <label class="field"><span>Priority</span><select name="priority"><option value="low">Low</option><option value="normal" selected>Normal</option><option value="high">High</option><option value="urgent">Urgent</option></select></label></div>
    <p class="muted small" id="svc-dept" aria-live="polite">Routed to the right department automatically.</p>
    <label class="field"><span>Location / landmark</span><input name="location" placeholder="e.g. Corner of Rizal & Luna, near the chapel"></label>
    <label class="field"><span>Description</span><textarea name="description" rows="3" required></textarea></label>
    <label class="field"><span>Photo or document <em>(optional)</em></span><input type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.pdf"></label>
    ${Legal.notice('request')}
    <div class="form-actions"><button type="button" class="btn secondary" data-act="close-modal">Cancel</button><button class="btn primary" type="submit">Submit request</button></div></form>`;
  const complaintForm = () => `<form data-form="new-complaint" class="stack"><div class="form-err" role="alert" hidden></div>
    <label class="field"><span>Subject</span><input name="subject" maxlength="200" required></label>
    <label class="field"><span>Category</span><select name="category">${Civic.COMPLAINT_CATS.map(c => `<option value="${c}">${cap(c)}</option>`).join('')}</select></label>
    <label class="field"><span>Details</span><textarea name="details" rows="4" required placeholder="What happened, when, where, and who was involved?"></textarea></label>
    <label class="field"><span>Supporting evidence <em>(JPG, PNG, PDF · max 1.5 MB)</em></span><input type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.pdf"></label>
    ${Legal.notice('complaint')}
    <div class="form-actions"><button type="button" class="btn secondary" data-act="close-modal">Cancel</button><button class="btn primary" type="submit">File complaint</button></div></form>`;
  function bindSvc() { const s = document.querySelector('[data-act-change=svc]'); if (s) s.onchange = () => { document.getElementById('svc-dept').innerHTML = s.value ? `Will be routed to <b>${esc(Civic.SERVICES[s.value])}</b>.` : ''; }; }

  App.act({
    compose: () => UI.modal('What would you like to do?', `<p class="modal-desc">Choose the right channel so your local government can act on it quickly. For emergencies, call <b>911</b>.</p><div class="tiles">
      <button class="tile t-brand" data-act="compose-request">${icon('request')}<span><b>Request a public service</b><em>Streetlights, roads, garbage, drainage, health…</em></span>${icon('chev', 'chev')}</button>
      <button class="tile t-warn" data-act="compose-complaint">${icon('complaint')}<span><b>Raise a concern or complaint</b><em>Services, personnel, facilities or disputes — handled privately</em></span>${icon('chev', 'chev')}</button>
      <button class="tile t-community" data-act="compose-proposal">${icon('proposal')}<span><b>Propose a community initiative</b><em>Suggest a project and gather your neighbours' support</em></span>${icon('chev', 'chev')}</button></div>`, { noFocus: true }),
    'compose-proposal': () => UI.modal('Propose a community initiative', proposalForm(), { wide: true }),
    'compose-request': () => { UI.modal('Request a public service', requestForm(), { wide: true }); bindSvc(); },
    'compose-complaint': () => UI.modal('Raise a concern or complaint', complaintForm(), { wide: true }),
    'post-menu': (el) => { const p = DB.find('proposals', Number(el.dataset.id)); const u = Civic.me(); const own = p.user_id === u.id;
      UI.modal('Proposal options', `<div class="menu-list"><a href="#/proposals/${p.id}" data-act="close-modal-go" data-href="#/proposals/${p.id}">${icon('eye')} Open proposal & decision record</a><button data-act="share" data-id="${p.id}">${icon('send')} Copy link</button>
        ${own && p.status === 'pending' ? `<button data-act="withdraw" data-id="${p.id}" class="danger-txt">${icon('trash')} Withdraw proposal</button>` : ''}</div>`, { noFocus: true }); },
    'feed-tab': (el) => { V.feedTab = el.dataset.k; App.refresh(); },
    'feed-cat': (el) => { V.feedCat = el.dataset.k; if (V.feedTab === 'consultations' || V.feedTab === 'decisions') V.feedTab = 'all'; if (location.hash !== '#/feed') App.go('#/feed'); else App.refresh(); },
    pf: (el) => { V.pf[el.dataset.k] = el.dataset.v; if (location.hash.includes('?')) location.hash = '#/proposals'; else App.refresh(); },
    rf: (el) => { V.rf.status = el.dataset.v; App.refresh(); },
    cf: (el) => { V.cf.status = el.dataset.v; App.refresh(); },
    vote: (el) => { const r = Civic.vote(Number(el.dataset.id), el.dataset.v); UI.toast(r ? (r === 'up' ? 'You support this proposal' : 'You oppose this proposal') : 'Vote removed'); App.refresh(); },
    share: (el) => { const url = location.href.split('#')[0] + '#/proposals/' + el.dataset.id; (navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.reject()).then(() => UI.toast('Link copied'), () => UI.toast(url)); },
    endorse: (el) => { const p = DB.find('proposals', Number(el.dataset.id)); const s = Civic.sectorOf(Civic.me().id);
      UI.modal('Endorse proposal', `<p class="muted">You are endorsing <b>${esc(p.title)}</b> on behalf of the <b>${esc(s ? s.sector_name : '—')}</b> sector. Your statement is public.</p>
      <form data-form="endorse" data-id="${p.id}" class="stack"><div class="form-err" role="alert" hidden></div><label class="field"><span>Endorsement statement</span><textarea name="statement" rows="3" required placeholder="Why does your sector support this?"></textarea></label>${Legal.notice('endorse')}
      <div class="form-actions"><button type="button" class="btn secondary" data-act="close-modal">Cancel</button><button class="btn accent" type="submit">${icon('award')} Endorse</button></div></form>`); },
    unendorse: (el) => UI.confirmBox('Withdraw endorsement?', 'Your sector endorsement will be removed from this proposal.', 'Withdraw', () => { Civic.withdrawEndorsement(Number(el.dataset.id)); UI.toast('Endorsement withdrawn'); App.refresh(); }, true),
    'del-comment': (el) => UI.confirmBox('Delete comment?', 'This cannot be undone.', 'Delete', () => { Civic.deleteComment(Number(el.dataset.id)); App.refresh(); }, true),
    withdraw: (el) => UI.confirmBox('Withdraw proposal?', 'The proposal, its votes, and comments will be removed.', 'Withdraw', () => { Civic.withdrawProposal(Number(el.dataset.id)); UI.toast('Proposal withdrawn'); App.go('#/proposals'); }, true),
    'cancel-req': (el) => UI.modal('Cancel this request?', `<form data-form="cancel-req" data-id="${el.dataset.id}" class="stack"><div class="form-err" role="alert" hidden></div><label class="field"><span>Reason (optional)</span><input name="reason" placeholder="e.g. Already fixed by neighbors"></label><div class="form-actions"><button type="button" class="btn secondary" data-act="close-modal">Keep request</button><button class="btn danger" type="submit">Cancel request</button></div></form>`),
    rate: (el) => { const t = el.dataset.t, id = el.dataset.id;
      UI.modal(t === 'general' ? 'Rate LGU services' : 'Rate ' + ref(t, id), `<form data-form="rate" data-t="${t}" data-id="${id || ''}" class="stack"><div class="form-err" role="alert" hidden></div>
        <div class="star-input" role="radiogroup">${[5, 4, 3, 2, 1].map(n => `<input type="radio" name="rating" value="${n}" id="st${n}"><label for="st${n}" title="${n} star${n > 1 ? 's' : ''}">★</label>`).join('')}</div>
        <label class="field"><span>Comments</span><textarea name="comments" rows="3" placeholder="What went well? What could be better?"></textarea></label>
        <div class="form-actions"><button type="button" class="btn secondary" data-act="close-modal">Cancel</button><button class="btn primary" type="submit">Submit rating</button></div></form>`); },
    respond: (el) => { const c = DB.find('consultations', Number(el.dataset.id)); const mine = DB.first('consultation_responses', r => r.consultation_id === c.id && r.user_id === Civic.me().id);
      UI.modal(c.title, `<p class="muted">${esc(c.description)}</p><form data-form="respond" data-id="${c.id}" class="stack"><div class="form-err" role="alert" hidden></div>
        <div class="choice">${['support', 'neutral', 'oppose'].map(k => `<label class="ch-${k}"><input type="radio" name="choice" value="${k}" ${mine && mine.choice === k ? 'checked' : ''} required><span>${icon(k === 'support' ? 'up' : k === 'oppose' ? 'down' : 'dots')}${cap(k)}</span></label>`).join('')}</div>
        <label class="field"><span>Your comments (public)</span><textarea name="response_text" rows="3">${esc(mine ? mine.response_text : '')}</textarea></label>${Legal.notice('consult', { check: false })}
        <div class="form-actions"><button type="button" class="btn secondary" data-act="close-modal">Cancel</button><button class="btn primary" type="submit">Submit</button></div></form>`); },
    'read-all': () => { DB.where('notifications', n => n.user_id === Civic.me().id && !n.is_read).forEach(n => DB.update('notifications', n.id, { is_read: 1 })); UI.toast('All caught up'); App.refresh(); },
    'n-filter': (el) => { V.nUnread = el.dataset.k === 'true'; App.refresh(); },
    'n-cat': (el) => { V.nCat = el.dataset.k; App.refresh(); },
    'open-notif': (el) => { DB.update('notifications', Number(el.dataset.id), { is_read: 1 }); const h = el.getAttribute('href'); if (location.hash === h) App.refresh(); else App.go(h); },
    'view-file': (el) => { const r = DB.find(el.dataset.t, Number(el.dataset.id)); const f = r.attachment || r.evidence || r.valid_id_path; if (!f) return;
      UI.modal(f.name, /^image/.test(f.type) ? `<img class="file-view" src="${f.data}" alt="">` : `<iframe class="file-view" src="${f.data}" title="${esc(f.name)}"></iframe>`, { wide: true, noFocus: true }); }
  });

  // live filters (input/select with data-filter="obj.key")
  document.addEventListener('input', (e) => { const el = e.target.closest('[data-filter]'); if (!el) return; const [o, k] = el.dataset.filter.split('.'); (V[o] = V[o] || {})[k] = el.value;
    clearTimeout(V._ft); V._ft = setTimeout(() => { const pos = el.selectionStart; App.refresh(); const n = document.querySelector(`[data-filter="${el.dataset.filter}"]`); if (n && n.tagName === 'INPUT') { n.focus(); try { n.setSelectionRange(pos, pos); } catch (_) {} } }, el.tagName === 'SELECT' ? 0 : 250); });

  App.form({
    'new-proposal': async (f, d) => { const img = await UI.readFile(f.img.files[0], 1); if (img && !/^image/.test(img.type)) throw new Error('Photo must be an image.'); d.image = img; const p = Civic.createProposal(d); UI.closeModal(); UI.toast('Proposal published'); App.go('#/proposals/' + p.id); },
    'new-request': async (f, d) => { d.attachment = await UI.readFile(f.file.files[0]); const r = Civic.createRequest(d); UI.closeModal(); UI.toast(ref('service_request', r.id) + ' submitted'); App.go('#/requests/' + r.id); },
    'new-complaint': async (f, d) => { d.evidence = await UI.readFile(f.file.files[0]); const c = Civic.createComplaint(d); UI.closeModal(); UI.toast(ref('complaint', c.id) + ' filed'); App.go('#/complaints/' + c.id); },
    comment: (f, d) => { Civic.comment(Number(f.dataset.id), d.body); App.refresh(); },
    endorse: (f, d) => { Civic.endorse(Number(f.dataset.id), d.statement); UI.closeModal(); UI.toast('Endorsed on behalf of your sector'); App.refresh(); },
    'cancel-req': (f, d) => { Civic.cancelRequest(Number(f.dataset.id), d.reason); UI.closeModal(); UI.toast('Request cancelled'); App.refresh(); },
    rate: (f, d) => { Civic.giveFeedback(f.dataset.t, Number(f.dataset.id) || null, d.rating, d.comments); UI.closeModal(); UI.toast('Thank you for your feedback'); App.refresh(); },
    respond: (f, d) => { Civic.respond(Number(f.dataset.id), d.choice, d.response_text); UI.closeModal(); UI.toast('Response recorded'); App.refresh(); },
    profile: (f, d) => { const u = Civic.me(); if (!d.full_name || d.full_name.length < 3) throw new Error('Name is too short.');
      DB.update('users', u.id, { full_name: d.full_name, contact_number: d.contact_number, address: d.address, bio: d.bio });
      const p = Civic.profile(u.id); if (p) DB.update('citizen_profiles', p.id, { barangay: d.barangay, birthdate: d.birthdate || null, ...(d.sector_id ? { sector_id: Number(d.sector_id) } : {}) });
      Civic.audit('Profile Updated', u.email); UI.toast('Profile saved'); App.refresh(); },
    password: (f, d) => { const u = Civic.me(); if (d.next.length < 8) throw new Error('New password must be at least 8 characters.'); if (d.next !== d.confirm) throw new Error('Passwords do not match.');
      DB.changePassword(d.current, d.next); f.reset(); UI.toast('Password updated'); },
    'valid-id': async (f) => { const file = await UI.readFile(f.file.files[0]); if (!file) throw new Error('Choose a file.'); const u = Civic.me(); const p = Civic.profile(u.id); DB.update('citizen_profiles', p.id, { valid_id_path: file });
      Civic.notifyRoles(['admin'], `${u.full_name} submitted a valid ID for verification.`, '#/admin/users'); Civic.audit('ID Submitted', u.email); UI.toast('ID submitted for review'); App.refresh(); }
  });
})();
