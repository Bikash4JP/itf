// Rirekisho Basic — Form Behaviors
// - Step slider (absolute, no flicker)
// - DOB single input (auto-slash) + hidden Y/M/D + age calc (JST)
// - Photo preview (keeps height responsive)
// - Repeaters (education / experience / licenses)
// - Status-based end-date enable/disable
// - Global guard to prevent double-binding

(function () {
  // ===== Global guard =====
  if (window.__RIREKI_FORM_BOUND__) {
    console.debug('[rireki_form] already bound; skipping');
    return;
  }
  window.__RIREKI_FORM_BOUND__ = true;

  // ===== Step slider =====
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


// Progress init + setter (safe if bar not present)
(function(){
  const fill   = document.getElementById('progressFill');
  const labels = Array.from(document.querySelectorAll('#progressWrap .progress-labels li'));
  const panes  = Array.from(document.querySelectorAll('.step-pane'));
  if (!fill || !labels.length || !panes.length) return;

  const SEGMENT = 20;          // 20% increments
  const LAST_IDX = panes.length - 1; // Step5 pane index

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
    // simple visual cues
    labels.forEach(li => li.classList.remove('is-active','is-dim','is-done'));
    if (fullyDone){
      // everything dim except last, which is active
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

    // labels: map pane index (0..4) to label index (0..4); last label is 作成終了
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

  window.addEventListener('load', updateProgress);
  updateProgress();
})();