/* CivicLink — arrow buttons for anything that scrolls sideways (stories, topics, tabs, tables…) */
(function () {
  const SEL = '.cn-in, .stories, .chips, .tabs, .stat-strip, .seg, .rep-nav, .table-wrap, .law-chips, .tn-tabs';
  const saved = {}; // remembers sideways position per strip so re-renders don't jump back
  const keyOf = (el, i) => location.hash.split('?')[0] + '|' + el.className.replace(/\s+/g, '.') + '|' + i;
  const chev = (d) => `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="${d === 'l' ? 'm15 6-6 6 6 6' : 'm9 6 6 6-6 6'}"/></svg>`;

  function update(w) {
    const el = w._el; if (!el) return;
    const max = el.scrollWidth - el.clientWidth;
    w.classList.toggle('can-l', el.scrollLeft > 2);
    w.classList.toggle('can-r', max > 2 && el.scrollLeft < max - 2);
  }
  function enhance(el, i) {
    if (el.parentElement && el.parentElement.classList.contains('hs-wrap')) return update(el.parentElement);
    if (el.closest('.tour-wrap')) return;
    const w = document.createElement('div');
    w.className = 'hs-wrap' + (el.classList.contains('sticky') ? ' hs-sticky' : '') + ' hs-' + (el.classList[0] || 'x');
    el.parentNode.insertBefore(w, el); w.appendChild(el); w._el = el;
    w.insertAdjacentHTML('beforeend', `<button type="button" class="hs-btn l" aria-label="Scroll left" tabindex="-1">${chev('l')}</button><button type="button" class="hs-btn r" aria-label="Scroll right" tabindex="-1">${chev('r')}</button>`);
    const k = keyOf(el, i); if (saved[k]) el.scrollLeft = saved[k];
    else { const on = el.querySelector('.on'); if (on && on.offsetLeft + on.offsetWidth > el.clientWidth) el.scrollLeft = on.offsetLeft - 40; }
    el.addEventListener('scroll', () => { saved[k] = el.scrollLeft; update(w); }, { passive: true });
    w.querySelectorAll('.hs-btn').forEach(b => b.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); el.scrollBy({ left: (b.classList.contains('l') ? -1 : 1) * Math.max(120, el.clientWidth * 0.75), behavior: 'smooth' }); }));
    if (window.ResizeObserver) new ResizeObserver(() => update(w)).observe(el);
    el.querySelectorAll('img').forEach(im => im.addEventListener('load', () => update(w), { once: true }));
    update(w);
  }
  function scan(root = document) { root.querySelectorAll(SEL).forEach((el, i) => enhance(el, i)); }
  // catch modals and anything rendered outside the router
  let t; new MutationObserver(() => { clearTimeout(t); t = setTimeout(() => scan(document), 60); }).observe(document.body || document.documentElement, { childList: true, subtree: true });
  addEventListener('resize', () => document.querySelectorAll('.hs-wrap').forEach(update));
  window.HScroll = { scan };
})();
