// /home/it-future/www/itf/rireki/kaigo/js/kaigo.js
// ✅ Step navigation + layout fit + DOB->Age (JST)
// ✅ Dynamic rows (+/-) for education/licenses/work (max 8, min 1)
// ✅ Fix: photo upload makes pane taller -> re-fit height + enable internal scroll if needed
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

  // ---------- height/scroll fit ----------
  function fit() {
    if (!stepsEl) return;
    const active = panes[idx];
    if (!active) return;

    // Height needed for active pane
    const needed = active.scrollHeight;

    // Available viewport space below steps container top
    const rect = stepsEl.getBoundingClientRect();
    const available = Math.max(220, window.innerHeight - rect.top - 16);

    // If content exceeds viewport, allow internal scroll
    if (needed > available) {
      stepsEl.style.height = available + 'px';
      stepsEl.style.overflowY = 'auto';
      stepsEl.style.webkitOverflowScrolling = 'touch';
    } else {
      stepsEl.style.height = needed + 'px';
      stepsEl.style.overflowY = 'visible';
    }
  }

  // ResizeObserver to catch image load / dynamic DOM changes
  let ro = null;
  function observeActivePane() {
    try {
      if (ro) ro.disconnect();
      if (!('ResizeObserver' in window)) return;
      ro = new ResizeObserver(() => fit());
      if (panes[idx]) ro.observe(panes[idx]);
    } catch (_) {}
  }

  function setActive(toIdx) {
    const to = Math.max(0, Math.min(panes.length - 1, toIdx));
    if (to === idx) { fit(); return; }
    panes[idx].classList.remove('is-active');
    panes[to].classList.add('is-active');
    idx = to;
    observeActivePane();
    fit();
  }

  function idxFromHash() {
    const h = (location.hash || '').replace('#','').trim();
    if (!h) return -1;
    return panes.findIndex(p => (p.getAttribute('d') || '') === h);
  }

  function applyHash() {
    const i = idxFromHash();
    if (i >= 0) setActive(i);
  }

  async function go(dir) {
    const to = Math.max(0, Math.min(panes.length - 1, idx + dir));
    if (to === idx) return;

    // ✅ save draft BEFORE moving (best-effort)
    try {
      if (typeof window.KAIGO_saveDraftNow === 'function') {
        await window.KAIGO_saveDraftNow(idx + 1);
      }
    } catch (e) {
      console.warn('[kaigo] save before nav failed', e);
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

  // Re-fit when any image finishes loading (photo preview etc.)
  document.addEventListener('load', (e) => {
    const t = e.target;
    if (t && t.tagName === 'IMG') fit();
  }, true);

  window.addEventListener('load', () => { applyHash(); observeActivePane(); fit(); });
  window.addEventListener('resize', fit);
  window.addEventListener('hashchange', applyHash);

  // expose (optional)
  window.KAIGO_fitSteps = fit;

  // ---------- DOB -> age (JST) ----------
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

  // ---------- (+/-) Dynamic rows ----------
  // Because PHP renders only N rows (not all 8), + must CLONE a row to add one.
  (function(){
    if (window.__KAIGO_ROWS_BOUND__) return;
    window.__KAIGO_ROWS_BOUND__ = true;

    function applyLabels(table){
      try{
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => (th.textContent || '').trim());
        table.querySelectorAll('tbody tr').forEach(tr => {
          Array.from(tr.children).forEach((cell, i) => {
            if (cell && cell.tagName === 'TD') cell.setAttribute('data-label', headers[i] || '');
          });
        });
      }catch(_){}
    }

    function clearRowValues(tr){
      const els = tr.querySelectorAll('input, select, textarea');
      els.forEach(el => {
        const tag = (el.tagName || '').toUpperCase();
        const type = (el.getAttribute('type') || '').toLowerCase();
        if (type === 'hidden') return; // keep hidden if any
        if (type === 'checkbox' || type === 'radio') { el.checked = false; return; }
        if (tag === 'SELECT') { el.selectedIndex = 0; return; }
        el.value = '';
      });
    }

    function rowCount(tbody){
      return tbody ? tbody.querySelectorAll('tr').length : 0;
    }

    document.addEventListener('click', (e) => {
      const addBtn = e.target.closest('.row-add');
      const delBtn = e.target.closest('.row-del');
      if (!addBtn && !delBtn) return;

      const btn = addBtn || delBtn;
      const tr = btn.closest('tr');
      const table = btn.closest('table');
      const tbody = table && table.tBodies ? table.tBodies[0] : null;
      if (!table || !tbody) return;

      const MAX = 8;
      const MIN = 1;

      if (addBtn) {
        const cnt = rowCount(tbody);
        if (cnt >= MAX) return;

        const src = tbody.lastElementChild || tr;
        if (!src) return;

        const clone = src.cloneNode(true);
        clearRowValues(clone);

        // remove any browser validity message residue
        clone.querySelectorAll(':invalid').forEach(el => { try { el.setCustomValidity(''); } catch(_){} });

        tbody.appendChild(clone);
        applyLabels(table);
        fit();
        return;
      }

      if (delBtn) {
        const cnt = rowCount(tbody);
        if (cnt <= MIN) return;
        if (tr) tr.remove();
        applyLabels(table);
        fit();
        return;
      }
    }, true);
  })();

  // initial
  applyHash();
  observeActivePane();
  fit();
})();
