/* CivicLink — business rules (status workflows, notifications, audit, SLA) */
(function () {
  const STATUSES = {
    proposal: ['pending', 'under_review', 'approved', 'rejected', 'implemented'],
    service_request: ['submitted', 'in_progress', 'resolved', 'closed', 'cancelled'],
    complaint: ['filed', 'investigating', 'mediation', 'resolved', 'dismissed']
  };
  const FLOW = { // allowed officer transitions
    proposal: { pending: ['under_review', 'rejected'], under_review: ['approved', 'rejected'], approved: ['implemented'], rejected: [], implemented: [] },
    service_request: { submitted: ['in_progress', 'closed'], in_progress: ['resolved'], resolved: ['closed', 'in_progress'], closed: [], cancelled: [] },
    complaint: { filed: ['investigating', 'dismissed'], investigating: ['mediation', 'resolved', 'dismissed'], mediation: ['resolved', 'dismissed', 'investigating'], resolved: [], dismissed: [] }
  };
  const DECISION = { approved: 'approved', implemented: 'approved', rejected: 'rejected', dismissed: 'rejected', resolved: 'closed', closed: 'closed' };
  const TABLE = { proposal: 'proposals', service_request: 'service_requests', complaint: 'complaints' };
  const TITLE = { proposal: 'title', service_request: 'service_type', complaint: 'subject' };
  const LINK = { proposal: '#/proposals/', service_request: '#/requests/', complaint: '#/complaints/' };
  const CATEGORIES = ['housing', 'transport', 'safety', 'sanitation', 'infrastructure', 'health', 'education', 'other'];
  const COMPLAINT_CATS = ['service', 'personnel', 'facility', 'dispute', 'other'];
  const SERVICES = {
    'Streetlight repair': 'Engineering Office', 'Pothole / road repair': 'Engineering Office', 'Drainage declogging': 'Engineering Office',
    'Garbage collection': 'City Environment Office', 'Tree trimming': 'City Environment Office', 'Stray animal control': 'City Health Office',
    'Health assistance': 'City Health Office', 'Social welfare assistance': 'Social Welfare Office', 'Traffic signage / signals': 'Traffic Management Office',
    'Barangay certificate / documents': 'Barangay Affairs Office', 'Other': 'Barangay Affairs Office'
  };
  const DEPARTMENTS = [...new Set(Object.values(SERVICES))];

  const me = () => { const id = DB.currentUserId(); return id ? DB.find('users', id) : null; };
  const user = (id) => DB.find('users', id);
  const profile = (uid) => DB.first('citizen_profiles', p => p.user_id === uid);
  const sectorOf = (uid) => { const p = profile(uid); return p && p.sector_id ? DB.find('sectors', p.sector_id) : null; };
  const isStaff = (u) => u && (u.role === 'lgu_officer' || u.role === 'admin');

  function audit(action, details) { const u = me(); if (!u) return; try { DB.insert('audit_logs', { user_id: u.id, action, details: String(details || '').slice(0, 255), ip_address: 'browser' }); } catch (e) { console.warn('audit failed', e); } }
  function notify(uid, message, link) { if (uid) DB.insert('notifications', { user_id: uid, message: String(message).slice(0, 255), link: link || null, is_read: 0 }); }
  function notifyRoles(roles, message, link, exceptId) { const ids = DB.where('users', u => roles.includes(u.role) && u.status === 'active' && u.id !== exceptId).map(u => u.id); DB.insertMany('notifications', ids.map(id => ({ user_id: id, message: String(message).slice(0, 255), link: link || null, is_read: 0 }))); }

  /* ---------- auth ---------- */
  function login(email, password, remember) { if (!email || !password) throw new Error('Enter your email and password.'); return DB.login(email, password, remember); }
  function logout() { DB.logout(); }
  function register(f) {
    if (!f.full_name || f.full_name.length < 3) throw new Error('Please enter your full name.');
    if (!/^\S+@\S+\.\S+$/.test(f.email)) throw new Error('Please enter a valid email address.');
    if (DB.first('users', u => u.email && u.email.toLowerCase() === f.email.toLowerCase())) throw new Error('That email is already registered.');
    if (!f.password || f.password.length < 8) throw new Error('Password must be at least 8 characters.');
    if (f.password !== f.confirm) throw new Error('Passwords do not match.');
    if (!f.agree) throw new Error('Please accept the Data Privacy notice.');
    const u = DB.register({ full_name: f.full_name, email: f.email, password: f.password, contact_number: f.contact_number, address: f.address, barangay: f.barangay, birthdate: f.birthdate, sector_id: f.sector_id, valid_id: f.valid_id });
    return u;
  }

  /* ---------- proposals ---------- */
  function voteCounts(pid) { const v = DB.where('proposal_votes', x => x.proposal_id === pid); return { up: v.filter(x => x.vote_type === 'up').length, down: v.filter(x => x.vote_type === 'down').length }; }
  function myVote(pid) { const u = me(); const v = u && DB.first('proposal_votes', x => x.proposal_id === pid && x.user_id === u.id); return v ? v.vote_type : null; }
  function vote(pid, type) {
    const u = me(), p = DB.find('proposals', pid);
    if (!['pending', 'under_review'].includes(p.status)) throw new Error('Voting is closed for this proposal.');
    if (p.user_id === u.id) throw new Error('You cannot vote on your own proposal.');
    const v = DB.first('proposal_votes', x => x.proposal_id === pid && x.user_id === u.id);
    if (v && v.vote_type === type) { DB.remove('proposal_votes', v.id); return null; }
    if (v) DB.update('proposal_votes', v.id, { vote_type: type }); else DB.insert('proposal_votes', { proposal_id: pid, user_id: u.id, vote_type: type });
    const c = voteCounts(pid); const tot = c.up + c.down;
    if (tot && tot % 5 === 0) notify(p.user_id, `Your proposal "${p.title}" reached ${tot} votes (${c.up} support).`, '#/proposals/' + pid);
    return type;
  }
  function createProposal(f) {
    if (!f.title || f.title.length < 8) throw new Error('Title should be at least 8 characters.');
    if (!f.description || f.description.length < 20) throw new Error('Describe the proposal in at least 20 characters.');
    if (!f.justification) throw new Error('Justification is required.');
    if (!f.expected_benefits) throw new Error('Expected benefits are required.');
    const p = DB.insert('proposals', { user_id: me().id, title: f.title, description: f.description, justification: f.justification, expected_benefits: f.expected_benefits, category: CATEGORIES.includes(f.category) ? f.category : 'other', status: 'pending', image: f.image || null });
    notifyRoles(['lgu_officer', 'admin'], `New proposal ${UI.ref('proposal', p.id)}: ${p.title}`, '#/lgu/cases/proposal/' + p.id);
    audit('Proposal Submitted', `${UI.ref('proposal', p.id)} ${p.title}`); return p;
  }
  function withdrawProposal(pid) {
    const p = DB.find('proposals', pid); if (p.user_id !== me().id || p.status !== 'pending') throw new Error('Only your own pending proposals can be withdrawn.');
    DB.remove('proposals', pid); DB.removeWhere('proposal_votes', v => v.proposal_id === pid); DB.removeWhere('comments', c => c.proposal_id === pid); DB.removeWhere('endorsements', c => c.proposal_id === pid);
    audit('Proposal Withdrawn', UI.ref('proposal', pid));
  }
  function comment(pid, body) {
    if (!body || body.length < 2) throw new Error('Write a comment first.');
    const p = DB.find('proposals', pid), u = me();
    const c = DB.insert('comments', { proposal_id: pid, user_id: u.id, body: body.slice(0, 1000) });
    if (p.user_id !== u.id) notify(p.user_id, `${u.full_name} commented on "${p.title}"`, '#/proposals/' + pid); return c;
  }
  function deleteComment(id) { const c = DB.find('comments', id), u = me(); if (c.user_id !== u.id && u.role !== 'admin') throw new Error('Not allowed.'); DB.remove('comments', id); if (u.role === 'admin') audit('Comment Removed', `Comment #${id} on ${UI.ref('proposal', c.proposal_id)}`); }
  const endorsements = (pid) => DB.where('endorsements', e => e.proposal_id === pid);
  function endorse(pid, statement) {
    const u = me(); if (u.role !== 'sector_rep') throw new Error('Only sector representatives can endorse.');
    const s = sectorOf(u.id); if (!s) throw new Error('Your account has no sector assigned. Ask the administrator.');
    if (!statement || statement.length < 10) throw new Error('Add a short endorsement statement (10+ characters).');
    if (DB.first('endorsements', e => e.proposal_id === pid && e.user_id === u.id)) throw new Error('You already endorsed this proposal.');
    const p = DB.find('proposals', pid);
    DB.insert('endorsements', { proposal_id: pid, user_id: u.id, sector_id: s.id, statement });
    notify(p.user_id, `${u.full_name} (${s.sector_name} sector) endorsed your proposal "${p.title}"`, '#/proposals/' + pid);
    notifyRoles(['lgu_officer'], `${s.sector_name} sector endorsed ${UI.ref('proposal', pid)}`, '#/lgu/cases/proposal/' + pid);
    audit('Sector Endorsement', `${s.sector_name} endorsed ${UI.ref('proposal', pid)}`);
  }
  function withdrawEndorsement(pid) { const u = me(); DB.removeWhere('endorsements', e => e.proposal_id === pid && e.user_id === u.id); audit('Endorsement Withdrawn', UI.ref('proposal', pid)); }

  /* ---------- requests & complaints ---------- */
  function createRequest(f) {
    if (!SERVICES[f.service_type]) throw new Error('Choose a service type.');
    if (!f.description || f.description.length < 15) throw new Error('Describe the concern in at least 15 characters.');
    const r = DB.insert('service_requests', { user_id: me().id, service_type: f.service_type, description: f.description, location: f.location || '', department: SERVICES[f.service_type], priority: ['low', 'normal', 'high', 'urgent'].includes(f.priority) ? f.priority : 'normal', status: 'submitted', assigned_to: null, attachment: f.attachment || null });
    notifyRoles(['lgu_officer'], `New ${r.priority} service request ${UI.ref('service_request', r.id)}: ${r.service_type}`, '#/lgu/cases/service_request/' + r.id);
    audit('Service Request Submitted', UI.ref('service_request', r.id)); return r;
  }
  function cancelRequest(id, reason) {
    const r = DB.find('service_requests', id); if (r.user_id !== me().id || r.status !== 'submitted') throw new Error('Only submitted requests can be cancelled.');
    DB.update('service_requests', id, { status: 'cancelled', cancel_reason: reason || '' });
    DB.insert('decision_logs', { reference_type: 'service_request', reference_id: id, decided_by: me().id, from_status: 'submitted', to_status: 'cancelled', decision: 'closed', justification: 'Cancelled by requester' + (reason ? ': ' + reason : '') });
    if (r.assigned_to) notify(r.assigned_to, `${UI.ref('service_request', id)} was cancelled by the requester.`, '#/lgu/cases/service_request/' + id);
    audit('Service Request Cancelled', UI.ref('service_request', id));
  }
  function createComplaint(f) {
    if (!f.subject || f.subject.length < 5) throw new Error('Subject is required.');
    if (!f.details || f.details.length < 20) throw new Error('Provide details of at least 20 characters.');
    const c = DB.insert('complaints', { user_id: me().id, subject: f.subject, details: f.details, category: COMPLAINT_CATS.includes(f.category) ? f.category : 'other', status: 'filed', assigned_to: null, resolution_notes: '', evidence: f.evidence || null, anonymous: f.anonymous ? 1 : 0 });
    notifyRoles(['lgu_officer'], `New complaint ${UI.ref('complaint', c.id)}: ${c.subject}`, '#/lgu/cases/complaint/' + c.id);
    audit('Complaint Filed', UI.ref('complaint', c.id)); return c;
  }

  /* ---------- LGU case actions ---------- */
  function decide(type, id, to, justification, extra = {}) {
    const u = me(); if (u.role !== 'lgu_officer' && u.role !== 'admin') throw new Error('Only LGU officers may record decisions.');
    const row = DB.find(TABLE[type], id); const from = row.status;
    if (!(FLOW[type][from] || []).includes(to)) throw new Error(`Cannot move from ${UI.cap(from)} to ${UI.cap(to)}.`);
    if (!justification || justification.length < 15) throw new Error('A written justification of at least 15 characters is required.');
    const patch = { status: to };
    if (type === 'complaint' && extra.resolution_notes != null) patch.resolution_notes = extra.resolution_notes;
    if (type === 'complaint' && (to === 'resolved') && !(extra.resolution_notes || row.resolution_notes)) throw new Error('Resolution notes are required to resolve a complaint.');
    if (type === 'service_request' && to === 'resolved') patch.resolved_at = new Date().toISOString();
    if (type === 'service_request' && to === 'in_progress' && from === 'resolved') patch.resolved_at = null;
    if (!row.assigned_to && type !== 'proposal') patch.assigned_to = u.id;
    DB.update(TABLE[type], id, patch);
    const dec = DB.insert('decision_logs', { reference_type: type, reference_id: id, decided_by: u.id, from_status: from, to_status: to, decision: DECISION[to] || 'escalated', justification });
    notify(row.user_id, `${UI.ref(type, id)} "${row[TITLE[type]]}" is now ${UI.cap(to)}. Reason: ${justification.slice(0, 90)}${justification.length > 90 ? '…' : ''}`, LINK[type] + id);
    if (type === 'proposal') { DB.where('endorsements', e => e.proposal_id === id).forEach(e => notify(e.user_id, `A proposal you endorsed is now ${UI.cap(to)}: ${row.title}`, LINK[type] + id)); }
    audit('Decision Recorded', `${UI.ref(type, id)}: ${from} → ${to}`); return dec;
  }
  function assign(type, id, officerId) {
    const row = DB.find(TABLE[type], id); const o = user(Number(officerId));
    DB.update(TABLE[type], id, { assigned_to: o ? o.id : null });
    if (o) { notify(o.id, `You were assigned ${UI.ref(type, id)}: ${row[TITLE[type]]}`, '#/lgu/cases/' + type + '/' + id); notify(row.user_id, `${UI.ref(type, id)} has been assigned to ${o.full_name}${o.department ? ' (' + o.department + ')' : ''}.`, LINK[type] + id); }
    audit('Case Assigned', `${UI.ref(type, id)} → ${o ? o.full_name : 'Unassigned'}`);
  }
  function setPriority(id, p) { DB.update('service_requests', id, { priority: p }); audit('Priority Changed', `${UI.ref('service_request', id)} → ${p}`); }
  const decisions = (type, id) => DB.where('decision_logs', d => d.reference_type === type && d.reference_id === id).sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

  /* ---------- SLA ---------- */
  function sla(r) {
    const th = DB.settings.sla[r.priority] || 120;
    const end = r.resolved_at ? new Date(r.resolved_at) : (['closed', 'cancelled'].includes(r.status) ? new Date(r.updated_at) : new Date());
    const hours = (end - new Date(r.created_at)) / 36e5;
    const open = ['submitted', 'in_progress'].includes(r.status);
    return { hours, threshold: th, open, overdue: open && hours > th, breached: hours > th && r.status !== 'cancelled', pct: Math.min(100, hours / th * 100) };
  }
  const hrs = (h) => h < 1 ? Math.round(h * 60) + 'm' : h < 48 ? h.toFixed(1) + 'h' : (h / 24).toFixed(1) + 'd';

  /* ---------- consultations ---------- */
  function isOpen(c) { const t = new Date().toISOString().slice(0, 10); return c.status === 'open' && c.start_date <= t && c.end_date >= t; }
  function respond(cid, choice, text) {
    const c = DB.find('consultations', cid), u = me(); if (!isOpen(c)) throw new Error('This consultation is not accepting responses.');
    if (!['support', 'oppose', 'neutral'].includes(choice)) throw new Error('Choose Support, Oppose or Neutral.');
    const ex = DB.first('consultation_responses', r => r.consultation_id === cid && r.user_id === u.id);
    if (ex) DB.update('consultation_responses', ex.id, { choice, response_text: text }); else DB.insert('consultation_responses', { consultation_id: cid, user_id: u.id, choice, response_text: text, hidden: 0 });
    audit('Consultation Response', `${UI.ref('consultation', cid)} → ${choice}`);
  }
  function consultTally(cid, includeHidden) { const r = DB.where('consultation_responses', x => x.consultation_id === cid && (includeHidden || !x.hidden)); return { support: r.filter(x => x.choice === 'support').length, oppose: r.filter(x => x.choice === 'oppose').length, neutral: r.filter(x => x.choice === 'neutral').length, total: r.length }; }

  /* ---------- feedback ---------- */
  function rateable(uid) { // completed cases without feedback yet
    const done = [];
    DB.where('service_requests', r => r.user_id === uid && ['resolved', 'closed'].includes(r.status)).forEach(r => done.push({ type: 'service_request', id: r.id, label: `${UI.ref('service_request', r.id)} · ${r.service_type}` }));
    DB.where('complaints', c => c.user_id === uid && ['resolved', 'dismissed'].includes(c.status)).forEach(c => done.push({ type: 'complaint', id: c.id, label: `${UI.ref('complaint', c.id)} · ${c.subject}` }));
    return done.filter(x => !DB.first('feedback', f => f.user_id === uid && f.reference_type === x.type && f.reference_id === x.id));
  }
  function giveFeedback(type, id, rating, comments) {
    rating = Number(rating); if (!(rating >= 1 && rating <= 5)) throw new Error('Pick a rating from 1 to 5 stars.');
    const u = me(); if (type !== 'general' && DB.first('feedback', f => f.user_id === u.id && f.reference_type === type && f.reference_id === id)) throw new Error('You already rated this case.');
    DB.insert('feedback', { user_id: u.id, reference_type: type, reference_id: type === 'general' ? null : id, rating, comments });
    if (type !== 'general') { const row = DB.find(TABLE[type], id); if (row && row.assigned_to) notify(row.assigned_to, `New ${rating}★ rating on ${UI.ref(type, id)}`, '#/lgu/cases/' + type + '/' + id); }
    audit('Feedback Submitted', `${type}${id ? ' #' + id : ''} — ${rating}★`);
  }

  window.Civic = { STATUSES, FLOW, TABLE, TITLE, LINK, CATEGORIES, COMPLAINT_CATS, SERVICES, DEPARTMENTS, me, user, profile, sectorOf, isStaff, audit, notify, notifyRoles,
    login, logout, register, voteCounts, myVote, vote, createProposal, withdrawProposal, comment, deleteComment, endorsements, endorse, withdrawEndorsement,
    createRequest, cancelRequest, createComplaint, decide, assign, setPriority, decisions, sla, hrs, isOpen, respond, consultTally, rateable, giveFeedback };
})();
