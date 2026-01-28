// /home/it-future/www/itf/rireki/kaigo/js/kaigo.js
// ✅ Step navigation + layout fit + DOB->Age (JST)
// NOTE: Autosave + photo upload are handled by /rireki/kaigo/js/rireki_form_extra.js
(function () {
  if (window.__KAIGO_NAV_BOUND__) return;
  window.__KAIGO_NAV_BOUND__ = true;

  const form = document.getElementById('rirekiForm');
  const stepsEl = document.querySelector('.steps');
  const panes = Array.from(document.querySelectorAll('.step-pane'));
  if (!panes.length) return;

  let idx = panes.findIndex(p => p.classList.contains('is-active'));
  if (idx < 0) idx = 0;

  function fit() {
    if (!stepsEl) return;
    const active = panes[idx];
    if (active) stepsEl.style.height = active.scrollHeight + 'px';
  }

  function setActive(toIdx) {
    const to = Math.max(0, Math.min(panes.length - 1, toIdx));
    if (to === idx) { fit(); return; }
    panes[idx].classList.remove('is-active');
    panes[to].classList.add('is-active');
    idx = to;
    fit();
  }

  function idxFromHash() {
    const h = (location.hash || '').replace('#','').trim();
    if (!h) return -1;
    // expected: step-1 .. step-6
    const i = panes.findIndex(p => (p.getAttribute('d') || '') === h);
    return i;
  }

  function applyHash() {
    const i = idxFromHash();
    if (i >= 0) setActive(i);
  }

  async function go(dir) {
    const to = Math.max(0, Math.min(panes.length - 1, idx + dir));
    if (to === idx) return;

    // ✅ save draft BEFORE moving to next/prev (so step-by-step save is guaranteed)
    // the saver is defined in rireki_form_extra.js
    try {
      if (typeof window.KAIGO_saveDraftNow === 'function') {
        await window.KAIGO_saveDraftNow(idx + 1);
      }
    } catch (e) {
      console.warn('[kaigo] save before nav failed', e);
      // still allow navigation (UX first)
    }

    setActive(to);

    // update hash so edit links open correct step
    const d = panes[idx].getAttribute('d');
    if (d) {
      try { history.replaceState(null, '', '#' + d); } catch (_) { location.hash = d; }
    }

    // best effort step_current save
    try {
      if (typeof window.KAIGO_saveStepCurrent === 'function') {
        window.KAIGO_saveStepCurrent(idx + 1);
      }
    } catch (_) {}

    try { window.scrollTo({ top: 0, behavior: 'smooth' }); } catch (_) { window.scrollTo(0, 0); }
  }

  document.addEventListener('click', (e) => {
    const next = e.target.closest('.js-next-step');
    const prev = e.target.closest('.js-prev-step');
    if (!next && !prev) return;
    e.preventDefault();
    go(next ? +1 : -1);
  });

  window.addEventListener('load', () => { applyHash(); fit(); });
  window.addEventListener('resize', fit);
  window.addEventListener('hashchange', applyHash);

  // ===== DOB -> age (JST) =====
  (function () {
    const y = document.querySelector('[name="dob_year"]');
    const m = document.querySelector('[name="dob_month"]');
    const d = document.querySelector('[name="dob_day"]');
    const ageEl = document.getElementById('age_autofill') || document.querySelector('[name="age_autofill"]');
    if (!y || !m || !d || !ageEl) return;

    function calc() {
      const Y = +y.value, M = +m.value, D = +d.value;
      if (!Y || !M || !D) { ageEl.value = ''; return; }
      try {
        const now = new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Tokyo' }));
        const dob = new Date(Date.UTC(Y, M - 1, D));
        let a = now.getUTCFullYear() - dob.getUTCFullYear();
        const md = now.getUTCMonth() - dob.getUTCMonth();
        if (md < 0 || (md === 0 && now.getUTCDate() < dob.getUTCDate())) a--;
        ageEl.value = (a >= 0 && a < 150) ? a : '';
      } catch { ageEl.value = ''; }
    }

    ['input','change','blur'].forEach(evt => {
      [y,m,d].forEach(el => el.addEventListener(evt, () => {
        calc();
        try { if (typeof window.KAIGO_scheduleSave === 'function') window.KAIGO_scheduleSave(); } catch (_) {}
      }));
    });
  })();

  // initial
  applyHash();
  fit();
})();