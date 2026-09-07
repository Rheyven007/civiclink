<?php require_once __DIR__ . '/includes/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CivicLink - Inclusive Urban Governance & Participation Platform</title>
<meta name="description" content="CivicLink connects citizens, sector representatives, and LGU officers in one transparent platform for proposals, service requests, complaints, and public consultations.">
<link rel="icon" type="image/png" href="/assets/img/favicon-32.png" sizes="32x32">
<link rel="icon" type="image/png" href="/assets/img/favicon-64.png" sizes="64x64">
<link rel="apple-touch-icon" href="/assets/img/favicon-180.png">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="lp-page">

<header class="lp-header">
  <div class="lp-nav">
    <a href="/landing.php" class="lp-logo"><img src="/assets/img/logo.png" alt="CivicLink" class="brand-logo"> Civic<span>Link</span></a>
    <nav class="lp-nav-links">
      <a href="#how-it-works">How It Works</a>
      <a href="#scope">Scope</a>
      <a href="#roles">Who It's For</a>
      <a href="#faq">FAQ</a>
      <a href="#contact">Contact</a>
    </nav>
    <div class="lp-nav-cta">
      <a href="/auth/login.php" class="btn btn-outline btn-sm">Log In</a>
      <a href="/auth/register.php" class="btn btn-sm"><i class="fa-solid fa-user-plus"></i> Get Started</a>
      <button type="button" class="lp-mobile-toggle" id="lpMenuToggle" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
    </div>
  </div>
  <div class="lp-mobile-menu" id="lpMobileMenu">
    <a href="#how-it-works">How It Works</a>
    <a href="#scope">Scope</a>
    <a href="#roles">Who It's For</a>
    <a href="#faq">FAQ</a>
    <a href="#contact">Contact</a>
    <a href="/auth/login.php">Log In</a>
    <a href="/auth/register.php">Get Started</a>
  </div>
</header>

<!-- HERO -->
<section class="lp-hero">
  <div>
    <div class="eyebrow"><i class="fa-solid fa-shield-halved"></i> Transparent Local Governance</div>
    <h1>Bring your community and local government <span>closer together</span></h1>
    <p>CivicLink is a single platform where citizens submit proposals and service requests, vote on what matters, and track every decision — while LGU officers, sector representatives, and administrators manage it all with full transparency.</p>
    <div class="cta-row">
      <a href="/auth/register.php" class="btn"><i class="fa-solid fa-user-plus"></i> Create Free Account</a>
      <a href="#how-it-works" class="btn btn-outline"><i class="fa-solid fa-circle-play"></i> See How It Works</a>
    </div>
    <div class="lp-stats">
      <div><strong>4</strong><span>Participant Roles</span></div>
      <div><strong>100%</strong><span>Logged Decisions</span></div>
      <div><strong>SDG 9·10·11·16</strong><span>Aligned Goals</span></div>
    </div>
  </div>
  <div class="lp-hero-art">
    <div class="mock-card"><i class="fa-solid fa-lightbulb"></i><div class="mock-title">New Bike Lane Proposal</div><div class="mock-sub">142 support &middot; Under review</div></div>
    <div class="mock-card"><i class="fa-solid fa-scroll"></i><div class="mock-title">Decision Logged</div><div class="mock-sub">Approved by LGU Officer &middot; 2h ago</div></div>
    <div class="mock-card"><i class="fa-solid fa-bell"></i><div class="mock-title">Vote Update Notification</div><div class="mock-sub">Your proposal just gained 12 new votes</div></div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="lp-section" id="how-it-works">
  <div class="lp-head">
    <div class="eyebrow"><i class="fa-solid fa-gears"></i> How It Works</div>
    <h2>From idea to accountable decision</h2>
    <p>CivicLink guides every submission through a transparent, notification-driven lifecycle.</p>
  </div>
  <div class="how-steps">
    <div class="how-step">
      <div class="step-num">1</div>
      <i class="fa-solid fa-pen-to-square step-icon"></i>
      <h3>Submit</h3>
      <p>Citizens raise a community proposal, request a public service, or file a complaint through a simple guided form.</p>
    </div>
    <div class="how-step">
      <div class="step-num">2</div>
      <i class="fa-solid fa-people-arrows step-icon"></i>
      <h3>Discuss & Vote</h3>
      <p>The community and sector representatives support, oppose, or endorse proposals, and take part in public consultations.</p>
    </div>
    <div class="how-step">
      <div class="step-num">3</div>
      <i class="fa-solid fa-gavel step-icon"></i>
      <h3>Decide</h3>
      <p>LGU officers review the case and record a justified decision, which is automatically written to the public decision log.</p>
    </div>
    <div class="how-step">
      <div class="step-num">4</div>
      <i class="fa-solid fa-bell step-icon"></i>
      <h3>Get Notified</h3>
      <p>Everyone involved is instantly notified of vote updates and decision log changes — nothing happens silently.</p>
    </div>
  </div>
</section>

<!-- SCOPE -->
<section class="lp-section dark" id="scope">
  <div>
    <div class="lp-head">
      <div class="eyebrow"><i class="fa-solid fa-list-check"></i> Platform Scope</div>
      <h2>What CivicLink covers</h2>
      <p>A focused set of participation and governance tools — built for inclusive urban management.</p>
    </div>
    <div class="scope-grid">
      <div class="scope-card">
        <i class="fa-solid fa-lightbulb"></i>
        <h3>Community Proposals</h3>
        <p>Citizens pitch local projects; the community votes and sector reps endorse.</p>
        <ul><li>Support / oppose voting</li><li>Category tagging</li><li>Full decision log</li></ul>
      </div>
      <div class="scope-card">
        <i class="fa-solid fa-clipboard-list"></i>
        <h3>Public Service Requests</h3>
        <p>Non-emergency requests tracked from submission to resolution with SLA visibility.</p>
        <ul><li>Status tracking</li><li>LGU case management</li><li>Resolution history</li></ul>
      </div>
      <div class="scope-card">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <h3>Complaints & Disputes</h3>
        <p>Report issues and disputes; sector representatives can monitor by sector.</p>
        <ul><li>Investigation & mediation stages</li><li>Sector-level filtering</li><li>Transparent outcomes</li></ul>
      </div>
      <div class="scope-card">
        <i class="fa-solid fa-people-arrows"></i>
        <h3>Public Consultations</h3>
        <p>Open or scheduled consultations where citizens register support, opposition, or feedback.</p>
        <ul><li>Live response tally</li><li>Time-bound windows</li><li>Comment collection</li></ul>
      </div>
      <div class="scope-card">
        <i class="fa-solid fa-scroll"></i>
        <h3>Decision Log & Notifications</h3>
        <p>Every LGU decision and every vote milestone is logged and pushed to the affected citizen.</p>
        <ul><li>Justified decisions</li><li>Real-time vote alerts</li><li>Full audit trail</li></ul>
      </div>
      <div class="scope-card">
        <i class="fa-solid fa-chart-pie"></i>
        <h3>Analytics & Oversight</h3>
        <p>Administrators get sector breakdowns, SLA performance, and system-wide audit logs.</p>
        <ul><li>Role management</li><li>Moderation tools</li><li>Reporting dashboards</li></ul>
      </div>
    </div>
  </div>
</section>

<!-- ROLES -->
<section class="lp-section" id="roles">
  <div class="lp-head">
    <div class="eyebrow"><i class="fa-solid fa-users"></i> Who It's For</div>
    <h2>One platform, four connected roles</h2>
    <p>Every participant gets a dashboard tailored to their responsibilities.</p>
  </div>
  <div class="role-grid">
    <div class="role-card">
      <div class="role-icon"><i class="fa-solid fa-user"></i></div>
      <h3>Citizens</h3>
      <p>Submit proposals and requests, vote, and track every response.</p>
    </div>
    <div class="role-card">
      <div class="role-icon"><i class="fa-solid fa-users-rectangle"></i></div>
      <h3>Sector Representatives</h3>
      <p>Endorse proposals and monitor issues for the sector they represent.</p>
    </div>
    <div class="role-card">
      <div class="role-icon"><i class="fa-solid fa-user-tie"></i></div>
      <h3>LGU Officers</h3>
      <p>Review cases, record justified decisions, and manage SLAs.</p>
    </div>
    <div class="role-card">
      <div class="role-icon"><i class="fa-solid fa-user-gear"></i></div>
      <h3>Administrators</h3>
      <p>Oversee users, sectors, moderation, analytics, and audit logs.</p>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="lp-section alt" id="faq">
  <div>
    <div class="lp-head">
      <div class="eyebrow"><i class="fa-solid fa-circle-question"></i> FAQ</div>
      <h2>Frequently asked questions</h2>
      <p>Everything you need to know before you join.</p>
    </div>
    <div class="faq-list">
      <details class="faq-item">
        <summary>Is CivicLink free to use? <i class="fa-solid fa-chevron-down"></i></summary>
        <div class="faq-a">Yes. Creating a citizen account is completely free — you only need a valid email address to register and start participating.</div>
      </details>
      <details class="faq-item">
        <summary>How do I know my proposal was actually reviewed? <i class="fa-solid fa-chevron-down"></i></summary>
        <div class="faq-a">Every LGU decision is written to a public decision log with a required justification, and you're notified the moment your status changes — so nothing gets decided behind closed doors.</div>
      </details>
      <details class="faq-item">
        <summary>Will I be notified about votes on my proposal? <i class="fa-solid fa-chevron-down"></i></summary>
        <div class="faq-a">Yes. Whenever the support or oppose tally on your proposal changes, or a sector representative endorses it, you receive an in-app notification linking straight to the update.</div>
      </details>
      <details class="faq-item">
        <summary>What's the difference between a proposal and a service request? <i class="fa-solid fa-chevron-down"></i></summary>
        <div class="faq-a">A proposal is a new community project idea that the public votes on. A service request is a request for an existing public service (e.g. repairs, maintenance) that gets tracked to resolution.</div>
      </details>
      <details class="faq-item">
        <summary>Who can see my personal information? <i class="fa-solid fa-chevron-down"></i></summary>
        <div class="faq-a">Sector affiliation and contact details are only visible to LGU officers and administrators for case handling and equitable prioritization — never used for discrimination or shared publicly.</div>
      </details>
      <details class="faq-item">
        <summary>Can I become a sector representative or LGU officer? <i class="fa-solid fa-chevron-down"></i></summary>
        <div class="faq-a">Citizen accounts are self-service through registration. Sector representative, LGU officer, and administrator roles are assigned by your local government unit's administrator.</div>
      </details>
    </div>
  </div>
</section>

<!-- CTA -->
<div class="lp-cta">
  <div class="lp-cta-box">
    <h2>Ready to make your voice count?</h2>
    <p>Join citizens already using CivicLink to shape decisions in their community.</p>
    <a href="/auth/register.php" class="btn btn-accent"><i class="fa-solid fa-user-plus"></i> Create Your Free Account</a>
  </div>
</div>

<!-- CONTACT -->
<section class="lp-section" id="contact">
  <div class="lp-head">
    <div class="eyebrow"><i class="fa-solid fa-envelope"></i> Contact</div>
    <h2>Get in touch</h2>
    <p>Have a question about your local deployment of CivicLink? Reach out.</p>
  </div>
  <div class="contact-wrap">
    <div>
      <div class="contact-info-item">
        <i class="fa-solid fa-envelope"></i>
        <div><h4>Email</h4><p>support@civiclink.gov</p></div>
      </div>
      <div class="contact-info-item">
        <i class="fa-solid fa-phone"></i>
        <div><h4>Hotline</h4><p>(02) 8-CIVIC-LINK &middot; Mon–Fri, 8AM–5PM</p></div>
      </div>
      <div class="contact-info-item">
        <i class="fa-solid fa-location-dot"></i>
        <div><h4>Office</h4><p>Local Government Unit Civic Engagement Office</p></div>
      </div>
      <div class="contact-info-item">
        <i class="fa-solid fa-circle-info"></i>
        <div><h4>Support Hours</h4><p>Notifications and decision logs are monitored on business days.</p></div>
      </div>
    </div>
    <div class="card" style="margin-bottom:0">
      <h2><i class="fa-solid fa-paper-plane"></i> Send a message</h2>
      <form onsubmit="event.preventDefault(); this.querySelector('.contact-sent').style.display='block'; this.querySelector('button').style.display='none';">
        <div class="field"><label>Full name</label><input type="text" required></div>
        <div class="field"><label>Email address</label><input type="email" required></div>
        <div class="field"><label>Message</label><textarea required placeholder="How can we help?"></textarea></div>
        <button type="submit" class="btn btn-block"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
        <div class="alert alert-ok contact-sent" style="display:none;margin-top:14px"><i class="fa-solid fa-circle-check"></i> Thanks! Our team will get back to you shortly.</div>
      </form>
    </div>
  </div>
</section>

<footer class="lp-footer">
  <div class="lp-footer-inner">
    <div>
      <a href="/landing.php" class="lp-logo"><img src="/assets/img/logo.png" alt="CivicLink" class="brand-logo"> Civic<span>Link</span></a>
      <p class="tag">Inclusive Urban Governance and Participation System, aligned with SDG 9, 10, 11 & 16.</p>
    </div>
    <div class="lp-footer-col">
      <h4>Platform</h4>
      <a href="#how-it-works">How It Works</a>
      <a href="#scope">Scope</a>
      <a href="#roles">Who It's For</a>
    </div>
    <div class="lp-footer-col">
      <h4>Support</h4>
      <a href="#faq">FAQ</a>
      <a href="#contact">Contact</a>
      <a href="/auth/login.php">Log In</a>
    </div>
    <div class="lp-footer-col">
      <h4>Account</h4>
      <a href="/auth/register.php">Create Account</a>
      <a href="/auth/login.php">Sign In</a>
    </div>
  </div>
  <div class="lp-footer-bottom">&copy; <?= date('Y') ?> CivicLink &middot; Inclusive Urban Governance and Participation System</div>
</footer>


<script>
(function(){
  var btn = document.getElementById('lpMenuToggle');
  var menu = document.getElementById('lpMobileMenu');
  if (btn && menu) {
    btn.addEventListener('click', function(){ menu.classList.toggle('open'); });
    menu.querySelectorAll('a').forEach(function(a){
      a.addEventListener('click', function(){ menu.classList.remove('open'); });
    });
  }
})();
</script>
</body>
</html>
