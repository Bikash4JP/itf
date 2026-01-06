// /rireki/basic/js/rireki_form.js
// Rirekisho Basic — Form Behaviors
// - Step slider + open on #step-N / ?step=N
// - DOB single input auto-slash + hidden Y/M/D + age calc (JST)
// - Photo preview
// - Repeaters (education / experience / licenses)
// - Status-based end-date enable/disable
// - Auto-save / auto-restore (localStorage + sessionStorage)
// - Seed support from preview (same STORAGE_KEY)
// - Mobile stacked table support (auto data-label)
// - Step gate validation (Step1 required correctness)

(function () {
  if (window.__RIREKI_BASIC_BOUND__) return;
  window.__RIREKI_BASIC_BOUND__ = true;

  const form    = document.getElementById('rirekiForm');
  const stepsEl = document.querySelector('.steps');
  const panes   = Array.from(document.querySelectorAll('.step-pane'));
  const hiddenActive = document.getElementById('__active_step');

  const STORAGE_KEY = 'rireki:basic:draft:v1';

  let idx = -1;

  function measure(el){ return el ? el.scrollHeight : 0; }
  function fitContainerTo(i){
    if (!stepsEl || i < 0 || !panes[i]) return;
    stepsEl.style.height = measure(panes[i]) + 'px';
    if (hiddenActive) hiddenActive.value = String(panes[i].dataset.step || '');
  }
  window.rirekiFitSteps = function(){ fitContainerTo(idx); };

  // ---------- Step / URL ----------
  function getStepFromURL(){
    const h = (location.hash || '').toLowerCase();
    const m = h.match(/#step-(\d{1,2})/);
    if (m) return parseInt(m[1], 10);

    const qs = new URLSearchParams(location.search);
    if (qs.has('step')) return parseInt(qs.get('step'), 10);

    return null;
  }

  function activateByStepNumber(stepNumber){
    if (!panes.length) return;
    const targetIndex = panes.findIndex(p => String(p.dataset.step) === String(stepNumber));
    if (targetIndex < 0) return;
    panes.forEach(p => p.classList.remove('is-active'));
    panes[targetIndex].classList.add('is-active');
    idx = targetIndex;
    fitContainerTo(idx);
    if (window.rirekiUpdateProgress) window.rirekiUpdateProgress();
  }

  function goStep(next, dir) {
    if (next === idx || next < 0 || next >= panes.length) return;
    const from = panes[idx], to = panes[next];
    to.classList.add('is-active');

    const enter = dir === 'back' ? 'slide-in-left'  : 'slide-in-right';
    const exit  = dir === 'back' ? 'slide-out-right': 'slide-out-left';
    to.classList.add(enter);
    from.classList.add(exit);

    fitContainerTo(next);

    const cleanup = () => {
      from.classList.remove('is-active', 'slide-out-left', 'slide-out-right');
      to.classList.remove('slide-in-left', 'slide-in-right');
      idx = next;
      fitContainerTo(idx);
      if (window.rirekiUpdateProgress) window.rirekiUpdateProgress();
    };

    const onEnd = (e) => {
      if (e.target !== to) return;
      to.removeEventListener('animationend', onEnd);
      cleanup();
    };

    to.addEventListener('animationend', onEnd, { once: true });
    setTimeout(cleanup, 500);

    try { window.scrollTo({ top: 0, behavior: 'smooth' }); }
    catch (_) { window.scrollTo(0, 0); }
  }

  // init step
  if (stepsEl && panes.length) {
    const desired = getStepFromURL();
    if (desired && panes.some(p => String(p.dataset.step) === String(desired))) {
      activateByStepNumber(desired);
    } else {
      idx = panes.findIndex(p => p.classList.contains('is-active'));
      if (idx < 0) { idx = 0; panes[0].classList.add('is-active'); }
      fitContainerTo(idx);
    }

    window.addEventListener('load', () => {
      fitContainerTo(idx);
      if (window.rirekiUpdateProgress) window.rirekiUpdateProgress();
    });
    window.addEventListener('resize', () => fitContainerTo(idx));

    document.addEventListener('click', (e) => {
      const nextBtn = e.target.closest('.js-next-step');
      const prevBtn = e.target.closest('.js-prev-step');
      if (nextBtn) { e.preventDefault(); goStep(idx + 1, 'forward'); }
      if (prevBtn) { e.preventDefault(); goStep(idx - 1, 'back'); }
    });

    window.addEventListener('hashchange', () => {
      const s = getStepFromURL();
      if (!s) return;
      activateByStepNumber(s);
    });
  }

  // ---------- DOB + hidden + age ----------
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
          const b = new Date(by, bm, bd);

          let a = now.getFullYear() - b.getFullYear();
          const md = now.getMonth() - b.getMonth();
          if (md < 0 || (md === 0 && now.getDate() < b.getDate())) a--;

          age.value = (a>=0 && a<150) ? a : '';
        }catch(e){ age.value = ''; }
      }else{
        age.value = '';
      }

      if (window.rirekiFitSteps) window.rirekiFitSteps();
    }

    window.rirekiUpdateDOBHidden = updateHidden;

    dob.addEventListener('input', () => { dob.value = formatDOB(dob.value); updateHidden(); });
    dob.addEventListener('blur', updateHidden);
  })();

  // ---------- Photo preview ----------
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
      img.onload = () => { box.style.display = 'flex'; fitSoon(); };
      img.onerror = () => { box.style.display = 'none'; fitSoon(); };
      img.src = url;
      box.style.display = 'flex';
      fitSoon();
    });
  })();

  // ---------- End-date toggles ----------
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

  // ---------- Repeaters ----------
  function addRow(tableId, rowClass){
    const tbody = document.querySelector(`#${tableId} tbody`);
    const first = tbody ? tbody.querySelector(`.${rowClass}`) : null;
    if (!tbody || !first) return;

    const clone = first.cloneNode(true);

    clone.querySelectorAll('input').forEach(i => {
      i.value='';
      if (i.classList.contains('js-edu-end-y') || i.classList.contains('js-edu-end-m') ||
          i.classList.contains('js-exp-end-y') || i.classList.contains('js-exp-end-m')) {
        i.disabled = true;
      }
    });
    clone.querySelectorAll('select').forEach(s => { s.selectedIndex = 0; });
    clone.querySelectorAll('textarea').forEach(t => { t.value=''; });

    tbody.appendChild(clone);

    if (tableId==='eduTable') toggleEduEnd(clone);
    if (tableId==='expTable') toggleExpEnd(clone);

    // update data-labels for mobile
    applyTableLabels(document.getElementById(tableId));

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

  // initial toggles
  document.querySelectorAll('#eduTable tbody tr').forEach(toggleEduEnd);
  document.querySelectorAll('#expTable tbody tr').forEach(toggleExpEnd);

  // ---------- Mobile table labels (td[data-label]) ----------
  function applyTableLabels(table){
    if (!table) return;
    const headers = Array.from(table.querySelectorAll('thead th')).map(th => (th.textContent || '').trim());
    table.querySelectorAll('tbody tr').forEach(tr => {
      Array.from(tr.children).forEach((cell, i) => {
        if (cell && cell.tagName === 'TD') cell.setAttribute('data-label', headers[i] || '');
      });
    });
  }

  function initTableLabels(){
    document.querySelectorAll('table.table').forEach(t => applyTableLabels(t));
    document.querySelectorAll('table.table').forEach(t => {
      const tb = t.tBodies && t.tBodies[0];
      if (!tb) return;
      if (!('MutationObserver' in window)) return;
      new MutationObserver(() => applyTableLabels(t))
        .observe(tb, { childList:true, subtree:true });
    });
  }

  window.addEventListener('load', initTableLabels);

  // ---------- Auto-save / Auto-restore ----------
  function formToObject(){
    if (!form) return {};
    const fd = new FormData(form);
    const obj = {};
    for (const [k, v] of fd.entries()){
      if (k.endsWith('[]')) {
        const key = k.slice(0,-2);
        (obj[key] ||= []).push(v);
      } else {
        if (obj[k] !== undefined) {
          if (!Array.isArray(obj[k])) obj[k] = [obj[k]];
          obj[k].push(v);
        } else {
          obj[k] = v;
        }
      }
    }

    const activePane = panes.find(p => p.classList.contains('is-active'));
    obj.__active_step = String(activePane?.dataset.step || '');
    obj.__ts = Date.now();
    return obj;
  }

  function saveDraft(){
    try{
      const obj = formToObject();
      const json = JSON.stringify(obj);
      localStorage.setItem(STORAGE_KEY, json);
      sessionStorage.setItem(STORAGE_KEY, json);
    }catch(e){ console.warn('saveDraft failed', e); }
  }

  function loadDraft(){
    try{
      const s = sessionStorage.getItem(STORAGE_KEY);
      if (s) return JSON.parse(s);
    }catch(e){}
    try{
      const l = localStorage.getItem(STORAGE_KEY);
      if (l) return JSON.parse(l);
    }catch(e){}
    return null;
  }

  function arrCount(v){
    if (Array.isArray(v)) return v.length;
    if (typeof v === 'string' && v !== '') return 1;
    return 0;
  }

  function needRows(tableId, rowClass, targetCount){
    const tbody = document.querySelector(`#${tableId} tbody`);
    if (!tbody) return;
    const current = tbody.querySelectorAll(`.${rowClass}`).length;
    for (let i=current; i<targetCount; i++) addRow(tableId, rowClass);
  }

  function setField(el, v){
    if (!el) return;
    if (el.tagName === 'SELECT'){
      el.value = v;
      return;
    }
    if (el.tagName === 'TEXTAREA' || el.tagName === 'INPUT'){
      if (el.type === 'radio' || el.type === 'checkbox'){
        el.checked = (el.value == v);
      } else {
        el.value = v;
        if (el.id === 'dob' && typeof window.rirekiUpdateDOBHidden === 'function'){
          window.rirekiUpdateDOBHidden();
        }
      }
    }
  }

  function restoreDraft(){
    if (!form) return;
    const obj = loadDraft();
    if (!obj) return;

    // Ensure enough repeater rows
    const eduCount = Math.max(
      arrCount(obj.edu_start_year), arrCount(obj.edu_start_month),
      arrCount(obj.edu_school_name), arrCount(obj.edu_faculty),
      arrCount(obj.edu_level), arrCount(obj.edu_status),
      arrCount(obj.edu_end_year), arrCount(obj.edu_end_month),
      1
    );
    const expCount = Math.max(
      arrCount(obj.exp_start_year), arrCount(obj.exp_start_month),
      arrCount(obj.exp_company), arrCount(obj.exp_title),
      arrCount(obj.exp_status),
      arrCount(obj.exp_end_year), arrCount(obj.exp_end_month),
      1
    );
    const licCount = Math.max(
      arrCount(obj.lic_year), arrCount(obj.lic_month), arrCount(obj.lic_name),
      1
    );

    needRows('eduTable','edu-row', eduCount);
    needRows('expTable','exp-row', expCount);
    needRows('licTable','lic-row', licCount);

    // Fill fields
    Object.keys(obj).forEach((name)=>{
      if (name.startsWith('__')) return;
      const val = obj[name];

      if (Array.isArray(val)){
        // fill fields named `${name}[]`
        const nodes = form.querySelectorAll(`[name="${name}[]"]`);
        nodes.forEach((el, i) => { if (val[i] !== undefined) setField(el, val[i]); });
      } else {
        const nodes = form.querySelectorAll(`[name="${name}"]`);
        nodes.forEach((el) => setField(el, val));
      }
    });

    // Re-run toggles after restore
    document.querySelectorAll('#eduTable tbody tr').forEach(toggleEduEnd);
    document.querySelectorAll('#expTable tbody tr').forEach(toggleExpEnd);

    // If URL hash forces a step, keep it. Otherwise restore active step.
    const hashStep = (location.hash||'').match(/#step-(\d+)/);
    if (!hashStep && obj.__active_step){
      activateByStepNumber(obj.__active_step);
    } else {
      fitContainerTo(idx);
    }

    // Update labels
    initTableLabels();
    if (window.rirekiUpdateProgress) window.rirekiUpdateProgress();
  }

  if (form){
    // save frequently
    form.addEventListener('input', saveDraft, {capture:true});
    form.addEventListener('change', saveDraft, {capture:true});
    form.addEventListener('blur', saveDraft, true);
    form.addEventListener('submit', () => {
      // final set active step hidden
      const activePane = panes.find(p => p.classList.contains('is-active'));
      if (hiddenActive) hiddenActive.value = String(activePane?.dataset.step || '');
      saveDraft();
    });

    window.addEventListener('DOMContentLoaded', restoreDraft);
  }

  // ---------- Step gate validation (Step1 only) ----------
  (function(){
    const step1 = document.querySelector('.step-pane[data-step="1"]');
    if (!step1) return;

    const elKana  = document.getElementById('personal_name_kana');
    const elKanji = document.getElementById('personal_name_kanji');
    const elDob   = document.getElementById('dob');
    const elPost  = document.getElementById('postcode');
    const elPhone = document.getElementById('phone');
    const elEmail = document.getElementById('email');
    const elGender= document.getElementById('gender');

    const rePost = /^\d{3}-?\d{4}$/;

    function setValid(el){ if (el) el.setCustomValidity(''); }
    function setInvalid(el, msg){ if (el) el.setCustomValidity(msg); }

    function validateDob(){
      if (!elDob) return true;
      const raw = (elDob.value||'').trim();
      if (!raw){ setInvalid(elDob, '生年月日を入力してください。'); return false; }

      const digits = raw.replace(/\D/g,'');
      if (digits.length !== 8){ setInvalid(elDob, '生年月日は YYYY/MM/DD で入力してください。'); return false; }

      const yy = parseInt(digits.slice(0,4),10);
      const mm = parseInt(digits.slice(4,6),10);
      const dd = parseInt(digits.slice(6,8),10);
      if (!Number.isFinite(yy) || yy < 1900 || yy > 2100){ setInvalid(elDob,'年（YYYY）が正しくありません。'); return false; }
      if (!Number.isFinite(mm) || mm < 1 || mm > 12){ setInvalid(elDob,'月（MM）が正しくありません。'); return false; }
      if (!Number.isFinite(dd) || dd < 1 || dd > 31){ setInvalid(elDob,'日（DD）が正しくありません。'); return false; }

      const dt = new Date(yy, mm-1, dd);
      const ok = (dt.getFullYear()===yy && (dt.getMonth()+1)===mm && dt.getDate()===dd);
      if (!ok){ setInvalid(elDob,'存在しない日付です。'); return false; }

      setValid(elDob);
      return true;
    }

    function validatePost(){
      if (!elPost) return true;
      const v = (elPost.value||'').trim();
      if (!v){ setInvalid(elPost,'郵便番号を入力してください。'); return false; }
      if (!rePost.test(v)){ setInvalid(elPost,'郵便番号は 123-4567 の形式で入力してください。'); return false; }
      setValid(elPost); return true;
    }

    function validatePhone(){
      if (!elPhone) return true;
      const v = (elPhone.value||'').trim();
      if (!v){ setInvalid(elPhone,'電話番号を入力してください。'); return false; }
      const digits = v.replace(/[^\d]/g,'');
      if (digits.length < 10 || digits.length > 13){ setInvalid(elPhone,'電話番号が正しくありません。'); return false; }
      setValid(elPhone); return true;
    }

    function validateBasics(){
      let ok = true;
      if (elKana){
        if (!(elKana.value||'').trim()){ setInvalid(elKana,'フリガナを入力してください。'); ok=false; }
        else setValid(elKana);
      }
      if (elKanji){
        if (!(elKanji.value||'').trim()){ setInvalid(elKanji,'氏名を入力してください。'); ok=false; }
        else setValid(elKanji);
      }
      if (elGender){
        if (!(elGender.value||'').trim()){ setInvalid(elGender,'性別を選択してください。'); ok=false; }
        else setValid(elGender);
      }
      if (elEmail){
        // browser validity
        if (!elEmail.checkValidity()){ setInvalid(elEmail,'Eメールが正しくありません。'); ok=false; }
        else setValid(elEmail);
      }

      ok = validateDob() && ok;
      ok = validatePost() && ok;
      ok = validatePhone() && ok;

      return ok;
    }

    // Validate on blur
    elDob && elDob.addEventListener('blur', validateDob);
    elPost && elPost.addEventListener('blur', validatePost);
    elPhone && elPhone.addEventListener('blur', validatePhone);

    // Gate next-step clicks (capture to stop slider)
    document.addEventListener('click', function(e){
      const btn = e.target.closest('.js-next-step');
      if (!btn) return;
      const activePane = document.querySelector('.steps .step-pane.is-active');
      if (activePane !== step1) return;

      const ok = validateBasics() && step1.checkValidity();
      if (!ok){
        e.preventDefault();
        e.stopImmediatePropagation();
        const firstInvalid = step1.querySelector(':invalid');
        if (firstInvalid && typeof firstInvalid.reportValidity === 'function') firstInvalid.reportValidity();
      }
    }, true);
  })();

})();


// ---------- Progress bar (Basic: 5 steps, 20% each, Step5 requires both PR & hopes) ----------
(function(){
  const fill   = document.getElementById('progressFill');
  const labels = Array.from(document.querySelectorAll('#progressWrap .progress-labels li'));
  const panes  = Array.from(document.querySelectorAll('.step-pane'));
  if (!fill || !labels.length || !panes.length) return;

  const SEGMENT = 20;                 // 20% increments
  const LAST_IDX = panes.length - 1;  // Step5 pane index

  const step5Pane = panes[LAST_IDX];
  const prEl     = step5Pane ? step5Pane.querySelector('textarea[name="self_pr"]') : null;
  const hopesEl  = step5Pane ? step5Pane.querySelector('textarea[name="hopes"]')   : null;
  const submitEl = step5Pane ? step5Pane.querySelector('button[type="submit"]')    : null;

  function activeIdx(){
    const i = panes.findIndex(p => p.classList.contains('is-active'));
    return i >= 0 ? i : 0;
  }

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
    let pct = Math.max(0, Math.min(80, idx * SEGMENT));
    let fullyDone = false;

    if (idx === LAST_IDX && allStep5Filled()){
      pct = 100;
      fullyDone = true;
    }

    fill.style.width = pct + '%';
    fill.setAttribute('aria-valuenow', String(Math.round(pct)));

    paintLabels(Math.min(idx, labels.length - 2), fullyDone);
    setSubmitState(fullyDone);
  }

  function deferUpdate(){ requestAnimationFrame(updateProgress); }

  document.addEventListener('click', (e) => {
    if (e.target.closest('.js-next-step') || e.target.closest('.js-prev-step')) deferUpdate();
  });

  const stepsRoot = document.querySelector('.steps');
  if (stepsRoot && 'MutationObserver' in window) {
    new MutationObserver(deferUpdate)
      .observe(stepsRoot, { attributes:true, subtree:true, attributeFilter:['class'] });
  }

  if (step5Pane){
    step5Pane.addEventListener('input', updateProgress, true);
    step5Pane.addEventListener('change', updateProgress, true);
  }

  window.rirekiUpdateProgress = updateProgress;

  window.addEventListener('hashchange', deferUpdate);
  window.addEventListener('load', updateProgress);
  updateProgress();
})();
