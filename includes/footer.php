      </div>
    </div>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.5/sweetalert2.all.min.js"></script>
<script>
(function () {
  // ---- Modern confirmations (SweetAlert2) ----
  // Any <form data-confirm="message"> is intercepted here instead of using the
  // native browser confirm() dialog.
  function civicConfirm(message, opts) {
    opts = opts || {};
    // Fallback so forms still work if the SweetAlert2 CDN script fails to load
    // (offline, blocked domain, ad-blocker, etc.) — without this, forms with
    // data-confirm would silently fail to submit.
    if (typeof Swal === 'undefined') {
      return Promise.resolve(window.confirm(message));
    }
    return Swal.fire({
      title: opts.title || 'Please confirm',
      text: message,
      icon: opts.icon || 'question',
      showCancelButton: true,
      confirmButtonText: opts.confirmText || 'Yes, continue',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#07553B',
      cancelButtonColor: '#5C7268',
      reverseButtons: true,
      focusCancel: true,
      customClass: { popup: 'civic-swal' }
    }).then(function (r) { return r.isConfirmed; }).catch(function () {
      // Swal existed but threw (e.g. mid-init) — fall back rather than block the form forever
      return window.confirm(message);
    });
  }
  window.civicConfirm = civicConfirm;

  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (form.dataset.confirmed === '1') return; // already confirmed once, let it through
      e.preventDefault();
      var msg = form.getAttribute('data-confirm');
      var icon = form.getAttribute('data-confirm-icon') || 'question';
      civicConfirm(msg, { icon: icon }).then(function (ok) {
        if (ok) { form.dataset.confirmed = '1'; form.submit(); }
      });
    });
  });

  // Selects that should confirm before triggering their onchange logic
  document.querySelectorAll('select[data-confirm-change]').forEach(function (sel) {
    var previous = sel.value;
    sel.addEventListener('focus', function () { previous = sel.value; });
    sel.addEventListener('change', function () {
      var msg = sel.getAttribute('data-confirm-change');
      civicConfirm(msg).then(function (ok) {
        if (ok) {
          sel.form.submit();
        } else {
          sel.value = previous;
        }
      });
    });
  });

  // Logout link
  var logoutLink = document.getElementById('logoutLink');
  if (logoutLink) {
    logoutLink.addEventListener('click', function (e) {
      e.preventDefault();
      civicConfirm('Are you sure you want to log out?', { title: 'Log out', confirmText: 'Log out', icon: 'question' }).then(function (ok) {
        if (ok) window.location.href = logoutLink.getAttribute('href');
      });
    });
  }
})();
(function () {
  var sidebar = document.getElementById('appSidebar');
  var overlay = document.getElementById('sidebarOverlay');
  var menuToggle = document.getElementById('menuToggle');
  var notifToggle = document.getElementById('notifToggle');
  var notifPanel = document.getElementById('notifPanel');
  var userToggle = document.getElementById('userToggle');
  var userPanel = document.getElementById('userPanel');

  function openSidebar() {
    if (!sidebar) return;
    sidebar.classList.add('open');
    if (overlay) { overlay.classList.add('open'); overlay.setAttribute('aria-hidden', 'false'); }
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove('open');
    if (overlay) { overlay.classList.remove('open'); overlay.setAttribute('aria-hidden', 'true'); }
    document.body.style.overflow = '';
  }
  if (menuToggle) {
    menuToggle.addEventListener('click', function () {
      if (sidebar && sidebar.classList.contains('open')) closeSidebar();
      else openSidebar();
    });
  }
  if (overlay) overlay.addEventListener('click', closeSidebar);

  if (notifToggle && notifPanel) {
    notifToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      if (userPanel) userPanel.classList.remove('open');
      if (userToggle) userToggle.setAttribute('aria-expanded', 'false');
      var open = notifPanel.classList.toggle('open');
      notifToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function (e) {
      if (!notifPanel.contains(e.target) && e.target !== notifToggle && !notifToggle.contains(e.target)) {
        notifPanel.classList.remove('open');
        notifToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  if (userToggle && userPanel) {
    userToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      if (notifPanel) notifPanel.classList.remove('open');
      if (notifToggle) notifToggle.setAttribute('aria-expanded', 'false');
      var open = userPanel.classList.toggle('open');
      userToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function (e) {
      if (!userPanel.contains(e.target) && e.target !== userToggle && !userToggle.contains(e.target)) {
        userPanel.classList.remove('open');
        userToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // Close mobile menu on nav link click
  if (sidebar) {
    sidebar.querySelectorAll('nav a').forEach(function (a) {
      a.addEventListener('click', function () {
        if (window.innerWidth <= 768) closeSidebar();
      });
    });
  }
})();
</script>
<script>
(function(){
  // Lightweight AJAX search/pagination for server-rendered tables.
  document.querySelectorAll('.ajax-table-container[data-ajax-endpoint], .ajax-list-container[data-ajax-endpoint]').forEach(function(container){
    var input = container.querySelector('[data-ajax-search]');
    var timer;
    function load(url, push){
      if(!url) return;
      var u = new URL(url, window.location.origin);
      u.searchParams.set('ajax','1');
      container.classList.add('is-loading');
      fetch(u.toString(), {headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){ return r.text(); })
        .then(function(html){
          var doc = new DOMParser().parseFromString(html,'text/html');
          var fresh = doc.querySelector('.ajax-table-container');
          if(!fresh) return;
          var oldBody = container.querySelector('[data-ajax-content]') || container.querySelector('tbody');
          var newBody = fresh.querySelector('[data-ajax-content]') || fresh.querySelector('tbody');
          if(oldBody && newBody) oldBody.innerHTML = newBody.innerHTML; ensureNoResults(container);
          var oldPager = container.querySelector('[data-pagination]');
          var newPager = fresh.querySelector('[data-pagination]');
          if(oldPager) oldPager.remove();
          if(newPager) container.appendChild(newPager.cloneNode(true));
          var count = container.querySelector('[data-result-count]');
          var freshCount = fresh.querySelector('[data-result-count]');
          if(count && freshCount) count.textContent = freshCount.textContent;
          if(input) input.value = u.searchParams.get('q') || '';
          if(push){ u.searchParams.delete('ajax'); history.pushState({},'',u.pathname + (u.search ? u.search : '')); }
        })
        .catch(function(err){ console.error('AJAX table load failed',err); })
        .finally(function(){ container.classList.remove('is-loading'); });
    }
    if(input){
      input.addEventListener('input',function(){
        clearTimeout(timer);
        timer=setTimeout(function(){
          var u=new URL(window.location.href);
          if(input.value.trim()) u.searchParams.set('q',input.value.trim()); else u.searchParams.delete('q');
          u.searchParams.set('p','1');
          load(u.toString(),true);
        },300);
      });
    }
    container.addEventListener('click',function(e){
      var link=e.target.closest('a[data-page-link]');
      if(!link) return;
      e.preventDefault();
      load(link.href,true);
    });
    window.addEventListener('popstate',function(){ load(window.location.href,false); });
  });
})();
</script>

<script>
(function(){
  function escHtml(v){ var d=document.createElement('div'); d.textContent=v||''; return d.innerHTML; }
  var modal=document.createElement('div');
  modal.className='app-modal'; modal.id='globalFormModal'; modal.setAttribute('aria-hidden','true');
  modal.innerHTML='<div class="app-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="globalFormModalTitle"><div class="app-modal-head"><h2 id="globalFormModalTitle">Form</h2><button type="button" class="app-modal-close" aria-label="Close"><i class="fa-solid fa-xmark"></i></button></div><div class="app-modal-body"><div class="no-results"><i class="fa-solid fa-spinner fa-spin"></i>Loading…</div></div></div>';
  document.body.appendChild(modal);
  var body=modal.querySelector('.app-modal-body'), title=modal.querySelector('#globalFormModalTitle');
  function close(){ modal.classList.remove('open'); modal.setAttribute('aria-hidden','true'); body.innerHTML='<div class="no-results"><i class="fa-solid fa-spinner fa-spin"></i>Loading…</div>'; }
  modal.querySelector('.app-modal-close').addEventListener('click',close);
  modal.addEventListener('click',function(e){ if(e.target===modal) close(); });
  document.addEventListener('keydown',function(e){ if(e.key==='Escape'&&modal.classList.contains('open')) close(); });
  function extract(html){ var doc=new DOMParser().parseFromString(html,'text/html'); var ci=doc.querySelector('.content-inner'); return {html:ci?ci.innerHTML:doc.body.innerHTML,title:(doc.querySelector('h1')||{}).textContent||'Form'}; }
  async function load(url, push){
    modal.classList.add('open'); modal.setAttribute('aria-hidden','false'); body.innerHTML='<div class="no-results"><i class="fa-solid fa-spinner fa-spin"></i>Loading form…</div>';
    try { var r=await fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest'}}); var x=extract(await r.text()); title.textContent=x.title.trim()||'Form'; body.innerHTML=x.html; bindForms(); if(push) history.pushState({formModal:url},'',url); }
    catch(e){ body.innerHTML='<div class="no-results"><i class="fa-solid fa-circle-exclamation"></i>Could not load this form.</div>'; }
  }
  async function submitForm(form){
    if(form.dataset.confirm && !window.confirm(form.dataset.confirm)) return;
    var fd=new FormData(form); var method=(form.method||'post').toUpperCase(); var url=form.action||location.href;
    body.classList.add('is-loading');
    try{
      var r=await fetch(url,{method:method,body:method==='GET'?undefined:fd,headers:{'X-Requested-With':'XMLHttpRequest'}});
      var x=extract(await r.text()); body.innerHTML=x.html; title.textContent=x.title.trim()||'Form'; bindForms();
    }catch(e){ body.innerHTML='<div class="no-results"><i class="fa-solid fa-circle-exclamation"></i>Could not submit the form. Please try again.</div>'; }
    finally{ body.classList.remove('is-loading'); }
  }
  function ensureNoResults(scope){
    (scope||document).querySelectorAll('table tbody').forEach(function(tb){
      if(!tb.querySelector('tr')){ var cols=(tb.parentElement.querySelectorAll('thead th').length||1); var tr=document.createElement('tr'); var td=document.createElement('td'); td.colSpan=cols; td.innerHTML='<div class="no-results"><i class="fa-regular fa-folder-open"></i>No results found.</div>'; tr.appendChild(td); tb.appendChild(tr); }
    });
  }
  ensureNoResults(document);
  function bindForms(){
    body.querySelectorAll('form').forEach(function(form){ if(form.dataset.modalBound) return; form.dataset.modalBound='1'; form.addEventListener('submit',function(e){e.preventDefault(); submitForm(form);}); });
  }
  document.addEventListener('click',function(e){
    var a=e.target.closest('a.open-form-modal'); if(!a) return;
    if(a.target==='_blank'||e.ctrlKey||e.metaKey||e.shiftKey||e.altKey) return;
    e.preventDefault(); load(a.href,false);
  });
  window.addEventListener('popstate',function(){ if(!location.pathname.match(/_new\.php$/)) close(); });
})();
</script>
</body>
</html>
