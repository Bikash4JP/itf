// /rireki/kaigo/js/kaigo.js
// Kaigo form behaviors (stable layout)
// - Step slider (no flicker; container height fit)
// - DOB -> age (JST)
// - Photo preview
// - Repeaters (edu/work/lic)
// - Status toggles: ONLY disable/clear end-date fields. DO NOT hide columns.

(function () {
  if (window.__KAIGO_FORM_BOUND__) return;
  window.__KAIGO_FORM_BOUND__ = true;

  // ===== Step slider =====
  const stepsEl = document.querySelector('.steps');
  const panes = Array.from(document.querySelectorAll('.step-pane'));
  let idx = panes.findIndex(p => p.classList.contains('is-active'));
  if (idx < 0) { idx = 0; panes[0]?.classList.add('is-active'); }

  function fit() {
    if (!stepsEl || idx < 0) return;
    stepsEl.style.height = panes[idx].scrollHeight + 'px';
  }
  window.addEventListener('load', fit);
  window.addEventListener('resize', fit);

  function go(dir) {
    const to = Math.max(0, Math.min(panes.length - 1, idx + dir));
    if (to === idx) return;
    const fromEl = panes[idx], toEl = panes[to];
    toEl.classList.add('is-active', dir > 0 ? 'slide-in-right' : 'slide-in-left');
    fromEl.classList.add(dir > 0 ? 'slide-out-left' : 'slide-out-right');
    fit();
    setTimeout(() => {
      fromEl.classList.remove('is-active', 'slide-out-left', 'slide-out-right');
      toEl.classList.remove('slide-in-right', 'slide-in-left');
      idx = to;
      fit();
    }, 420);
    try { window.scrollTo({ top: 0, behavior: 'smooth' }); } catch (_) {}
  }

  document.addEventListener('click', (e) => {
    const next = e.target.closest('.js-next-step');
    const prev = e.target.closest('.js-prev-step');
    if (!next && !prev) return;
    e.preventDefault();
    go(next ? +1 : -1);
  });

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
      fit();
    }
    [y, m, d].forEach(el => el.addEventListener('input', calc));
  })();

  // ===== Photo preview =====
  (function () {
    const input = document.getElementById('photo');
    const box = document.getElementById('photoPreview');
    const img = document.getElementById('photoPreviewImg');
    if (!input || !box || !img) return;

    input.addEventListener('change', () => {
      const f = input.files && input.files[0];
      if (!f) {
        box.style.display = 'none';
        img.removeAttribute('src');
        fit(); return;
      }
      const url = URL.createObjectURL(f);
      img.onload = () => { box.style.display = 'flex'; fit(); };
      img.onerror = () => { box.style.display = 'none'; fit(); };
      img.src = url;
      box.style.display = 'flex';
      fit();
    });
  })();

  // ===== Status toggles (no layout shift) =====
  function setEndFields(tr, endYSel, endMSel, disable) {
    const y = tr.querySelector(endYSel);
    const m = tr.querySelector(endMSel);
    [y, m].forEach(el => {
      if (!el) return;
      el.disabled = disable;
      if (disable) el.value = '';
      // keep cells visible — no display:none !
      el.style.opacity = disable ? 0.5 : 1;
    });
    fit();
  }
  function onWorkStatusChange(tr) {
    const st = tr.querySelector('.js-status-exp');
    if (!st) return;
    setEndFields(tr, '.js-exp-end-y', '.js-exp-end-m', /在職中/.test(st.value));
  }
  function onEduStatusChange(tr) {
    const st = tr.querySelector('.js-status-edu');
    if (!st) return;
    setEndFields(tr, '.js-edu-end-y', '.js-edu-end-m', /在学中/.test(st.value));
  }

  document.addEventListener('change', (e) => {
    const tr = e.target.closest('tr');
    if (!tr) return;
    if (e.target.matches('.js-status-exp')) onWorkStatusChange(tr);
    if (e.target.matches('.js-status-edu')) onEduStatusChange(tr);
  });

  // ===== Repeaters =====
  function addRow(tbody, selector) {
    const first = tbody.querySelector(selector);
    if (!first) return;
    const clone = first.cloneNode(true);

    // clear values; end fields remain visible but enabled; status blank
    clone.querySelectorAll('input,select,textarea').forEach(i => {
      if (i.type === 'checkbox' || i.type === 'radio') i.checked = false;
      else i.value = '';
      if (i.tagName === 'SELECT') i.selectedIndex = 0;
      i.disabled = false;
      i.style.opacity = 1;
    });

    tbody.appendChild(clone);
    try { clone.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); } catch (_) {}
    fit();
  }
  function delRow(btn) {
    const tr = btn.closest('tr'); if (!tr) return;
    const tb = tr.parentNode; if (!tb || tb.children.length <= 1) return;
    tb.removeChild(tr); fit();
  }

  document.addEventListener('click', (e) => {
    const add = e.target.closest('.row-add');
    const del = e.target.closest('.row-del');
    if (add) {
      e.preventDefault();
      const target = add.getAttribute('data-for');
      if (target === 'edu') addRow(document.querySelector('#eduTable tbody'), '.edu-row');
      if (target === 'lic') addRow(document.querySelector('#licTable tbody'), '.lic-row');
      if (target === 'exp') addRow(document.querySelector('#expTable tbody'), '.exp-row');
    }
    if (del) { e.preventDefault(); delRow(del); }
  });

  fit();
})();
