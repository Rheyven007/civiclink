<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$page_title = 'Help & Support';
$current_page = 'citizen/help.php';
require_once __DIR__ . '/../includes/header.php';
?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
  <a href="/citizen/dashboard.php">Dashboard</a>
  <span class="sep">/</span>
  <span class="current">Help & Support</span>
</nav>

<div class="card">
  <h2><i class="fa-solid fa-circle-question"></i> Help & Support</h2>
  <p class="muted mb">Learn how CivicLink works and how to participate in local governance.</p>

  <div class="help-toc">
    <a href="#getting-started"><i class="fa-solid fa-rocket"></i> Getting Started</a>
    <a href="#proposals"><i class="fa-solid fa-lightbulb"></i> Community Proposals</a>
    <a href="#requests"><i class="fa-solid fa-clipboard-list"></i> Service Requests</a>
    <a href="#complaints"><i class="fa-solid fa-triangle-exclamation"></i> Complaints & Concerns</a>
    <a href="#consultations"><i class="fa-solid fa-people-arrows"></i> Public Consultations</a>
    <a href="#tracking"><i class="fa-solid fa-route"></i> Track My Submission</a>
    <a href="#faq"><i class="fa-solid fa-comments"></i> Frequently Asked Questions</a>
  </div>
</div>

<div class="card help-section" id="getting-started">
  <h3><i class="fa-solid fa-rocket"></i> Getting Started</h3>
  <ul>
    <li>Create an account from the registration page, or log in if you already have one.</li>
    <li>Complete your profile so officers can contact you if needed.</li>
    <li>Use the dashboard Quick Actions to submit a proposal, request a service, report a concern, or join a consultation.</li>
  </ul>
</div>

<div class="card help-section" id="proposals">
  <h3><i class="fa-solid fa-lightbulb"></i> Community Proposals</h3>
  <p>A community proposal is an idea for a project that could improve your barangay or city — parks, roads, safety, health, education, and more.</p>
  <ul>
    <li>Go to <strong>Proposals → New Proposal</strong>.</li>
    <li>Provide a clear title, category, and description.</li>
    <li>After submission you receive a reference number (e.g. <code class="ref-code">PROP-2026-00124</code>).</li>
    <li>Other citizens can support or oppose proposals; LGU officers review and record a justified decision.</li>
  </ul>
</div>

<div class="card help-section" id="requests">
  <h3><i class="fa-solid fa-clipboard-list"></i> Service Requests</h3>
  <p>Use a service request for <strong>non-emergency</strong> public services such as streetlight repair, waste collection, or drainage clearing.</p>
  <ul>
    <li>Describe the location and the problem clearly.</li>
    <li>Choose a priority that reflects urgency (not for true emergencies).</li>
    <li>You will receive a reference number such as <code class="ref-code">REQ-2026-00318</code>.</li>
  </ul>
</div>

<div class="card help-section" id="complaints">
  <h3><i class="fa-solid fa-triangle-exclamation"></i> Complaints & Concerns</h3>
  <p>Report concerns about services, facilities, personnel, or disputes. Attach evidence (photo or PDF) when helpful.</p>
  <ul>
    <li>Be factual and specific — date, place, and what happened.</li>
    <li>Reference numbers look like <code class="ref-code">CMP-2026-00082</code>.</li>
    <li>The LGU may investigate, mediate, resolve, or dismiss with a written justification.</li>
  </ul>
</div>

<div class="card help-section" id="consultations">
  <h3><i class="fa-solid fa-people-arrows"></i> Public Consultations</h3>
  <p>When the LGU opens a consultation, you can read the topic and submit your response. Watch the <strong>Needs your attention</strong> section on the dashboard for open consultations.</p>
</div>

<div class="card help-section" id="tracking">
  <h3><i class="fa-solid fa-route"></i> Track My Submission</h3>
  <p>Typical progress for proposals:</p>
  <div class="status-flow">
    <span class="badge badge-warn">Pending</span>
    <span class="arrow">→</span>
    <span class="badge badge-info">Under Review</span>
    <span class="arrow">→</span>
    <span class="badge badge-ok">Approved</span>
    <span class="arrow">→</span>
    <span class="badge badge-ok">Implemented</span>
  </div>
  <p class="small muted">Service requests and complaints follow similar paths (Submitted → In Progress → Resolved, or Filed → Investigating → Resolved/Dismissed).</p>
  <p>You receive a notification whenever the status of your submission changes. Check the bell icon in the top bar or the Notifications page.</p>
</div>

<div class="card help-section" id="faq">
  <h3><i class="fa-solid fa-comments"></i> Frequently Asked Questions</h3>
  <p><strong>What is a community proposal?</strong><br>
  An idea for a local project that citizens submit for LGU review and possible implementation.</p>
  <p class="mt"><strong>How long does a service request take?</strong><br>
  Response times vary by workload and priority. Officers track performance against service levels; check status updates in Notifications.</p>
  <p class="mt"><strong>What happens after I submit a complaint?</strong><br>
  The LGU is notified. Officers may investigate, request more information, mediate, resolve, or dismiss — always with a written justification you can see.</p>
  <p class="mt"><strong>Can I change my submission?</strong><br>
  Contact your LGU with the reference number if you need to correct or withdraw a submission.</p>
  <p class="mt"><strong>What does “Under Review” mean?</strong><br>
  An LGU officer is actively examining the submission before making a decision.</p>
  <p class="mt"><strong>How will I know when my case is updated?</strong><br>
  CivicLink sends a notification and shows the change in the decision log / timeline on the detail page.</p>
</div>

<div class="card">
  <p class="small muted">Still need help? Contact your local LGU office and provide your reference number for faster assistance.</p>
  <a href="/citizen/dashboard.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
