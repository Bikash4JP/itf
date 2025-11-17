// Rirekisho Basic — Form Behaviors
// - Step slider (absolute, no flicker) + open on #step-N / ?step=N
// - DOB single input (auto-slash) + hidden Y/M/D + age calc (JST)
// - Photo preview (keeps height responsive)
// - Repeaters (education / experience / licenses)
// - Status-based end-date enable/disable
// - Auto-save / auto-restore (localStorage) so "Edit" from preview doesn't wipe values
// - Global guard to prevent double-binding

(function () {
  // ===== Global guard =====
  if (window.__RIREKI_FORM_BOUND__) {
    console.debug('[rireki_form] already bound; skipping');
    return;
  }
  window.__RIREKI_FORM_BOUND__ = true;

  const form   = document.getElementById('rirekiForm');
  const stepsEl = document.querySelector('.steps');
  const panes   = Array.from(document.querySelectorAll('.step-pane'));
  let idx = -1;

  function measure(el){ return el ? el.scrollHeight : 0; }
  function fitContainerTo(i){ if (!stepsEl || i < 0) return; stepsEl.style.height = measure(panes[i]) + 'px'; }
  function exposeFit(){ window.rirekiFitSteps = function(){ fitContainerTo(idx); }; }

  // ===== Step slider =====
  function getStepFromURL(){
    const h = (location.hash || '').toLowerCase();
    const m = h.match(/#step-(\d{1,2})/);
    if (m) return parseInt(m[1], 10);
    const qs = new URLSearchParams(location.search);
    if (qs.has('step')) return parseInt(qs.get('step'), 10);
    return null;
  }

  if (stepsEl && panes.length) {
    exposeFit();

    // Initial activation — prefer URL target if valid
    const desired = getStepFromURL(); // 1..N
    if (desired && panes.some(p => String(p.dataset.step) === String(desired))) {
      panes.forEach(p => p.classList.remove('is-active'));
      idx = panes.findIndex(p => String(p.dataset.step) === String(desired));
      panes[idx].classList.add('is-active');
    } else {
      idx = panes.findIndex(p => p.classList.contains('is-active'));
      if (idx < 0) { idx = 0; panes[0].classList.add('is-active'); }
    }

    window.addEventListener('load',  () => {
      fitContainerTo(idx);
      if (window.rirekiUpdateProgress) window.rirekiUpdateProgress();
    });
    window.addEventListener('resize',() => fitContainerTo(idx));

    function goStep(next, dir) {
      if (next === idx || next < 0 || next >= panes.length) return;
      const from = panes[idx], to = panes[next];
      to.classList.add('is-active');
      const enter = dir === 'back' ? 'slide-in-left'  : 'slide-in-right';
      const exit  = dir === 'back' ? 'slide-out-right': 'slide-out-left';
      to.classList.add(enter); from.classList.add(exit);
      fitContainerTo(next);
      const cleanup = () => {
        from.classList.remove('is-active', 'slide-out-left', 'slide-out-right');
        to.classList.remove('slide-in-left', 'slide-in-right');
        idx = next;
        if (window.rirekiUpdateProgress) window.rirekiUpdateProgress();
      };
      const onEnd = (e) => { if (e.target !== to) return; to.removeEventListener('animationend', onEnd); cleanup(); };
      to.addEventListener('animationend', onEnd, { once: true });
      setTimeout(cleanup, 500);
      try { window.scrollTo({ top: 0, behavior: 'smooth' }); } catch (_) { window.scrollTo(0, 0); }
    }

    document.addEventListener('click', (e) => {
      const nextBtn = e.target.closest('.js-next-step');
      const prevBtn = e.target.closest('.js-prev-step');
      if (nextBtn) { e.preventDefault(); goStep(idx + 1, 'forward'); }
      if (prevBtn) { e.preventDefault(); goStep(idx - 1, 'back'); }
    });

    document.querySelectorAll('[data-goto-step]').forEach(el => {
      el.addEventListener('click', (e) => {
        e.preventDefault();
        const target = parseInt(el.getAttribute('data-goto-step'), 10);
        if (!isNaN(target)) goStep(target, target > idx ? 'forward' : 'back');
      });
    });

    // react to hash changes (when clicking "編集" from preview)
    window.addEventListener('hashchange', () => {
      const s = getStepFromURL();
      if (!s) return;
      const targetIndex = panes.findIndex(p => String(p.dataset.step) === String(s));
      if (targetIndex >= 0 && targetIndex !== idx) {
        goStep(targetIndex, targetIndex > idx ? 'forward' : 'back');
      }
    });
  } else {
    exposeFit();
  }

  // ===== DOB + hidden + age =====
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
      if (window.rirekiFitSteps) window.rirekiFitSteps();
    }
    // expose for restore
    window.rirekiUpdateDOBHidden = updateHidden;

    dob.addEventListener('input', () => { dob.value = formatDOB(dob.value); updateHidden(); });
    dob.addEventListener('blur', updateHidden);
  })();

  // ===== Photo preview =====
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
      img.onload = () => { box.style.display = 'block'; fitSoon(); };
      img.onerror = () => { box.style.display='none'; fitSoon(); };
      img.src = url;
      box.style.display = 'block';
      fitSoon();
    });
  })();

  // ===== End-date toggles =====
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

  // ===== Repeaters (helpers for save/restore) =====
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

  // ===== Auto-save / Auto-restore =====
  (function(){
    if (!form) return;
    const STORAGE_KEY = 'rireki:basic:draft:v1';

    function formToObject(){
      const fd = new FormData(form);
      const obj = {};
      for (const [k, v] of fd.entries()){
        // collect multiple values per name
        if (k.endsWith('[]')) {
          const key = k.slice(0,-2);
          (obj[key] ||= []).push(v);
        } else {
          if (obj[k] !== undefined) {
            // convert to array if repeated name (e.g., radios)
            if (!Array.isArray(obj[k])) obj[k] = [obj[k]];
            obj[k].push(v);
          } else {
            obj[k] = v;
          }
        }
      }
      // Also persist which pane is active
      const activePane = panes.find(p => p.classList.contains('is-active'));
      if (activePane) obj.__active_step = String(activePane.dataset.step || '');
      obj.__ts = Date.now();
      return obj;
    }

    function saveDraft(){
      try {
        const obj = formToObject();
        localStorage.setItem(STORAGE_KEY, JSON.stringify(obj));
      } catch(e){ console.warn('saveDraft failed', e); }
    }

    function needRows(tableId, rowClass, targetCount){
      const tbody = document.querySelector(`#${tableId} tbody`);
      if (!tbody) return;
      const current = tbody.querySelectorAll(`.${rowClass}`).length;
      for (let i=current; i<targetCount; i++) addRow(tableId, rowClass);
    }

    function restoreDraft(){
      try{
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return;
        const obj = JSON.parse(raw);

        // Calculate repeater row counts from arrays
        const eduCount = Math.max(
          (obj.edu_start_year||[]).length, (obj.edu_start_month||[]).length,
          (obj.edu_school_name||[]).length, (obj.edu_faculty||[]).length,
          (obj.edu_level||[]).length, (obj.edu_status||[]).length,
          (obj.edu_end_year||[]).length, (obj.edu_end_month||[]).length, 1
        );
        const expCount = Math.max(
          (obj.exp_start_year||[]).length, (obj.exp_start_month||[]).length,
          (obj.exp_company||[]).length, (obj.exp_title||[]).length,
          (obj.exp_status||[]).length, (obj.exp_end_year||[]).length,
          (obj.exp_end_month||[]).length, 1
        );
        const licCount = Math.max(
          (obj.lic_year||[]).length, (obj.lic_month||[]).length,
          (obj.lic_name||[]).length, 1
        );

        // Ensure enough rows exist
        needRows('eduTable','edu-row', eduCount);
        needRows('expTable','exp-row', expCount);
        needRows('licTable','lic-row', licCount);

        // Fill simple (non [] names) & [] names by order
        Object.keys(obj).forEach((name)=>{
          if (name.startsWith('__')) return; // meta
          const val = obj[name];
          if (Array.isArray(val)){
            // fill fields named `${name}[]` in order
            const nodes = form.querySelectorAll(`[name="${name}[]"]`);
            nodes.forEach((el, i) => { if (val[i] !== undefined) setField(el, val[i]); });
          } else {
            const nodes = form.querySelectorAll(`[name="${name}"]`);
            if (nodes.length){
              nodes.forEach((el, i)=>{
                // for radios, choose matching
                if (el.type === 'radio' || el.type === 'checkbox'){
                  el.checked = (el.value == val || (Array.isArray(val) && val.includes(el.value)));
                } else {
                  setField(el, val);
                }
              });
            }
          }
        });

        // Re-run DOB hidden/age calc after restore
        if (window.rirekiUpdateDOBHidden) window.rirekiUpdateDOBHidden();

        // Fix end-date toggles after restore
        document.querySelectorAll('#eduTable tbody tr').forEach(toggleEduEnd);
        document.querySelectorAll('#expTable tbody tr').forEach(toggleExpEnd);

        // Restore active step if hash not forcing one
        const hashStep = (location.hash||'').match(/#step-(\d+)/);
        if (!hashStep && obj.__active_step){
          const targetPane = panes.findIndex(p => String(p.dataset.step) === String(obj.__active_step));
          if (targetPane >= 0){
            panes.forEach(p => p.classList.remove('is-active'));
            panes[targetPane].classList.add('is-active');
          }
        }

        // ensure progress recalculated
        if (window.rirekiUpdateProgress) window.rirekiUpdateProgress();
      }catch(e){ console.warn('restoreDraft failed', e); }
    }

    function setField(el, v){
      if (!el) return;
      if (el.tagName === 'SELECT'){
        el.value = v;
      } else if (el.tagName === 'TEXTAREA' || el.tagName === 'INPUT'){
        if (el.type === 'radio' || el.type === 'checkbox'){
          el.checked = (el.value == v);
        } else {
          el.value = v;
          // trigger input for DOB formatter if needed
          if (el.id === 'dob' && typeof window.rirekiUpdateDOBHidden === 'function'){
            window.rirekiUpdateDOBHidden();
          }
        }
      }
    }

    // Save often
    form.addEventListener('input', saveDraft, {capture:true});
    form.addEventListener('change', saveDraft, {capture:true});
    form.addEventListener('blur', saveDraft, true);

    // Save right before leaving to preview
    form.addEventListener('submit', saveDraft);

    // Restore on load
    window.addEventListener('DOMContentLoaded', restoreDraft);
  })();

})();


// Progress init + setter (safe if bar not present)
(function(){
  const fill   = document.getElementById('progressFill');
  const labels = Array.from(document.querySelectorAll('#progressWrap .progress-labels li'));
  const panes  = Array.from(document.querySelectorAll('.step-pane'));
  if (!fill || !labels.length || !panes.length) return;

  const SEGMENT = 20;                 // 20% increments
  const LAST_IDX = panes.length - 1;  // Step5 pane index

  // Step5 fields (自己PR・希望)
  const step5Pane = panes[LAST_IDX];
  const prEl     = step5Pane ? step5Pane.querySelector('textarea[name="self_pr"]') : null;
  const hopesEl  = step5Pane ? step5Pane.querySelector('textarea[name="hopes"]')   : null;
  const submitEl = step5Pane ? step5Pane.querySelector('button[type="submit"]')    : null;

  function activeIdx(){
    const i = panes.findIndex(p => p.classList.contains('is-active'));
    return i >= 0 ? i : 0;
  }

  // Require ALL inputs in Step5 filled (both PR & hopes must have text)
  function allStep5Filled(){
    const ok1 = prEl && prEl.value.trim().length > 0;
    const ok2 = hopesEl && hopesEl.value.trim().length > 0;
    return !!(ok1 && ok2);
  }

  function setSubmitState(done){
    if (!submitEl) return;
    submitEl.disabled = !done;
    submitEl.style.opacity = done ? '' : '0.7';
    submitEl.style.pointerEvents = done ? '' : 'none';
  }

  function paintLabels(currentIdx, fullyDone){
    labels.forEach(li => li.classList.remove('is-active','is-dim','is-done'));
    if (fullyDone){
      labels.forEach(li => li.classList.add('is-dim'));
      labels[labels.length - 1].classList.remove('is-dim');
      labels[labels.length - 1].classList.add('is-active');
      return;
    }
    labels.forEach((li, i) => {
      if (i < currentIdx) li.classList.add('is-done');
      if (i === currentIdx) li.classList.add('is-active');
      if (i > currentIdx) li.classList.add('is-dim');
    });
  }

  function updateProgress(){
    const idx = activeIdx(); // 0..4
    let pct = Math.max(0, Math.min(80, idx * SEGMENT)); // 0..80
    let fullyDone = false;

    if (idx === LAST_IDX && allStep5Filled()){
      pct = 100;
      fullyDone = true;
    }

    fill.style.width = pct + '%';
    fill.setAttribute('aria-valuenow', String(Math.round(pct)));

    // labels: current pane (0..4); last label is 作成終了
    paintLabels(Math.min(idx, labels.length - 2), fullyDone);
    setSubmitState(fullyDone);
  }

  function deferUpdate(){ requestAnimationFrame(updateProgress); }

  // react to step navigation
  document.addEventListener('click', (e) => {
    if (e.target.closest('.js-next-step') || e.target.closest('.js-prev-step')) deferUpdate();
  });

  // watch class swaps from the step slider
  const stepsRoot = document.querySelector('.steps');
  if (stepsRoot && 'MutationObserver' in window) {
    new MutationObserver(deferUpdate)
      .observe(stepsRoot, { attributes:true, subtree:true, attributeFilter:['class'] });
  }

  // live check step5 fields
  if (step5Pane){
    step5Pane.addEventListener('input', updateProgress, true);
    step5Pane.addEventListener('change', updateProgress, true);
  }

  // also update when hash-based navigation is used
  window.addEventListener('hashchange', deferUpdate);

  window.addEventListener('load', updateProgress);
  updateProgress();
})();
