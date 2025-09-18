// Rirekisho Basic — Form Behaviors
// - Step slider (absolute, no flicker)
// - DOB single input (auto-slash) + hidden Y/M/D + age calc
// - Photo preview with height auto-fit
// - Repeaters (education / experience / licenses)
// - Status-based end-date enable/disable
// - Global guards to prevent double-binding

(function () {
  // ===== Global guard to avoid double-binding (in case inline <script> exists) =====
  if (window.__RIREKI_FORM_BOUND__) {
    console.debug('[rireki_form] already bound; skipping');
    return;
  }
  window.__RIREKI_FORM_BOUND__ = true;

  // ===== Step slider (absolute) =====
  const stepsEl = document.querySelector('.steps');
  const panes   = Array.from(document.querySelectorAll('.step-pane'));
  let idx = -1;

  function measure(el){ return el ? el.scrollHeight : 0; }
  function fitContainerTo(i){ if (!stepsEl || i < 0) return; stepsEl.style.height = measure(panes[i]) + 'px'; }
  function exposeFit(){ window.rirekiFitSteps = function(){ fitContainerTo(idx); }; }

  if (stepsEl && panes.length) {
    idx = panes.findIndex(p => p.classList.contains('is-active'));
    if (idx < 0) { idx = 0; panes[0].classList.add('is-active'); }
    exposeFit();

    window.addEventListener('load',  () => fitContainerTo(idx));
    window.addEventListener('resize',() => fitContainerTo(idx));

    function goStep(next, dir) {
      if (next === idx || next < 0 || next >= panes.length) return;

      const from = panes[idx];
      const to   = panes[next];

      // Prepare entering pane
      to.classList.add('is-active');

      // Direction classes
      const enter = dir === 'back' ? 'slide-in-left'  : 'slide-in-right';
      const exit  = dir === 'back' ? 'slide-out-right': 'slide-out-left';

      to.classList.add(enter);
      from.classList.add(exit);

      // Animate container to new height
      fitContainerTo(next);

      const cleanup = () => {
        from.classList.remove('is-active', 'slide-out-left', 'slide-out-right');
        to.classList.remove('slide-in-left', 'slide-in-right');
        idx = next;
      };
      const onEnd = (e) => {
        if (e.target !== to) return;
        to.removeEventListener('animationend', onEnd);
        cleanup();
      };
      to.addEventListener('animationend', onEnd, { once: true });
      setTimeout(cleanup, 500); // safety

      // start from top each time
      try { window.scrollTo({ top: 0, behavior: 'smooth' }); } catch (_) { window.scrollTo(0, 0); }
    }

    // Hook buttons
    document.addEventListener('click', (e) => {
      const nextBtn = e.target.closest('.js-next-step');
      const prevBtn = e.target.closest('.js-prev-step');
      if (nextBtn) { e.preventDefault(); goStep(idx + 1, 'forward'); }
      if (prevBtn) { e.preventDefault(); goStep(idx - 1, 'back'); }
    });

    // Optional direct jump
    document.querySelectorAll('[data-goto-step]').forEach(el => {
      el.addEventListener('click', (e) => {
        e.preventDefault();
        const target = parseInt(el.getAttribute('data-goto-step'), 10);
        if (!isNaN(target)) goStep(target, target > idx ? 'forward' : 'back');
      });
    });
  } else {
    // still expose fit so other modules can call safely
    exposeFit();
  }

  // ===== DOB single input + hidden fields + age calc =====
  (function(){
    const dob = document.getElementById('dob');
    const y = document.getElementById('dob_yyyy');
    const m = document.getElementById('dob_mm');
    const d = document.getElementById('dob_dd');
    const age = document.getElementById('age');

    if (!dob || !y || !m || !d || !age) return;

    function formatDOB(v){
      v = (v || '').replace(/\D/g, '').slice(0, 8);
      if (v.length >= 5) v = v.slice(0,4) + '/' + v.slice(4);
      if (v.length >= 9) v = v.slice(0,7) + '/' + v.slice(7);
      return v;
    }
    function updateHidden(){
      const raw = (dob.value || '').replace(/\D/g, '');
      y.value = raw.slice(0,4) || '';
      m.value = raw.slice(4,6) || '';
      d.value = raw.slice(6,8) || '';

      // Age (JST)
      if (y.value && m.value && d.value){
        try{
          const now = new Date(new Date().toLocaleString('en-US', { timeZone:'Asia/Tokyo' }));
          const by = parseInt(y.value,10), bm = parseInt(m.value,10)-1, bd = parseInt(d.value,10);
          const bdate = new Date(Date.UTC(by, bm, bd));
          let a = now.getUTCFullYear() - bdate.getUTCFullYear();
          const mdiff = (now.getUTCMonth() - bdate.getUTCMonth());
          if (mdiff < 0 || (mdiff===0 && now.getUTCDate() < bdate.getUTCDate())) a--;
          age.value = (a>=0 && a<150) ? a : '';
        }catch(e){ age.value = ''; }
      }else{
        age.value = '';
      }
      // Height may change if hints appear/disappear
      if (window.rirekiFitSteps) window.rirekiFitSteps();
    }
    dob.addEventListener('input', () => { dob.value = formatDOB(dob.value); updateHidden(); });
    dob.addEventListener('blur', updateHidden);
  })();

  // ===== Photo preview (recalc height on image load) =====
  (function(){
    const input = document.getElementById('photo');
    const box = document.getElementById('photoPreview');
    const img = document.getElementById('photoPreviewImg');
    if (!input || !box || !img) return;

    function fitSoon(){ setTimeout(() => { if (window.rirekiFitSteps) window.rirekiFitSteps(); }, 0); }

    input.addEventListener('change', () => {
      const f = input.files && input.files[0];
      if (!f) {
        box.style.display='none';
        img.removeAttribute('src');
        fitSoon();
        return;
      }
      const url = URL.createObjectURL(f);
      img.onload = () => {
        box.style.display = 'block';
        fitSoon();
        // Do not revoke immediately to keep preview alive
      };
      img.onerror = () => { box.style.display='none'; fitSoon(); };
      img.src = url;
      box.style.display = 'block'; // show container so height can grow before load completes
      fitSoon();
    });
  })();

  // ===== Status-based end-date toggles =====
  function toggleEduEnd(tr){
    const st = tr.querySelector('.js-status-edu');
    const y  = tr.querySelector('.js-edu-end-y');
    const m  = tr.querySelector('.js-edu-end-m');
    const need = st && /卒業|退学/.test(st.value);
    [y,m].forEach(el => { if(!el) return; el.disabled = !need; if(!need){ el.value=''; } });
    if (window.rirekiFitSteps) window.rirekiFitSteps();
  }
  function toggleExpEnd(tr){
    const st = tr.querySelector('.js-status-exp');
    const y  = tr.querySelector('.js-exp-end-y');
    const m  = tr.querySelector('.js-exp-end-m');
    const need = st && /退職/.test(st.value);
    [y,m].forEach(el => { if(!el) return; el.disabled = !need; if(!need){ el.value=''; } });
    if (window.rirekiFitSteps) window.rirekiFitSteps();
  }
  document.addEventListener('change', (e) => {
    const tr = e.target.closest('tr');
    if (!tr) return;
    if (e.target.matches('.js-status-edu')) toggleEduEnd(tr);
    if (e.target.matches('.js-status-exp')) toggleExpEnd(tr);
  });
  document.querySelectorAll('#eduTable tbody tr').forEach(toggleEduEnd);
  document.querySelectorAll('#expTable tbody tr').forEach(toggleExpEnd);

  // ===== Repeaters =====
  function addRow(tableId, rowClass){
    const tbody = document.querySelector(`#${tableId} tbody`);
    const first = tbody ? tbody.querySelector(`.${rowClass}`) : null;
    if (!tbody || !first) return;

    const clone = first.cloneNode(true);

    // clear inputs & selects; maintain disabled state for end-date fields
    clone.querySelectorAll('input').forEach(i => {
      i.value='';
      if (i.classList.contains('js-edu-end-y') || i.classList.contains('js-edu-end-m') ||
          i.classList.contains('js-exp-end-y') || i.classList.contains('js-exp-end-m')) {
        i.disabled = true;
      }
    });
    clone.querySelectorAll('select').forEach(s => { s.selectedIndex = 0; });

    tbody.appendChild(clone);

    // re-init toggles
    if (tableId==='eduTable') toggleEduEnd(clone);
    if (tableId==='expTable') toggleExpEnd(clone);

    try { clone.scrollIntoView({behavior:'smooth', block:'nearest'}); } catch(_) {}
    if (window.rirekiFitSteps) window.rirekiFitSteps();
  }
  function delRow(btn){
    const tr = btn.closest('tr');
    if (!tr) return;
    const tbody = tr.parentNode;
    if (tbody.children.length <= 1) return;
    tbody.removeChild(tr);
    if (window.rirekiFitSteps) window.rirekiFitSteps();
  }
  document.addEventListener('click', (e) => {
    const add = e.target.closest('.row-add');
    const del = e.target.closest('.row-del');
    if (add){
      e.preventDefault();
      const target = add.getAttribute('data-for');
      if (target === 'edu') addRow('eduTable','edu-row');
      if (target === 'exp') addRow('expTable','exp-row');
      if (target === 'lic') addRow('licTable','lic-row');
    }
    if (del){
      e.preventDefault();
      delRow(del);
    }
  });
})();
