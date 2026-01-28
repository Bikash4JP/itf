/* ✅ Auto-add data-label to each TD from TH text (for mobile stacked table UI) */
    (function () {
      function applyLabels(table) {
        const headers = Array.from(table.querySelectorAll('thead th'))
          .map(th => (th.textContent || '').trim());

        table.querySelectorAll('tbody tr').forEach(tr => {
          Array.from(tr.children).forEach((cell, i) => {
            if (cell && cell.tagName === 'TD') cell.setAttribute('data-label', headers[i] || '');
          });
        });
      }

      function init() {
        const tables = document.querySelectorAll('table.table');
        tables.forEach(t => applyLabels(t));

        tables.forEach(t => {
          const tb = t.tBodies && t.tBodies[0];
          if (!tb) return;
          new MutationObserver(() => applyLabels(t))
            .observe(tb, { childList: true, subtree: true });
        });
      }

      window.addEventListener('load', init);
    })();

    /* ✅ STEP gate rules (required + format) BEFORE going next */
    (function () {
      const form = document.getElementById('rirekiForm');
      if (!form) return;

      const step1 = document.querySelector('.step-pane[d="step-1"]');
      const step2 = document.querySelector('.step-pane[d="step-2"]');

      const elRomaji = document.getElementById('name_romaji');
      const elKana   = document.getElementById('name_kana');
      const elY = document.getElementById('dob_year');
      const elM = document.getElementById('dob_month');
      const elD = document.getElementById('dob_day');
      const elPostal = document.getElementById('postal');
      const elPhone  = document.getElementById('contact_phone');
      const elPhoto  = document.getElementById('photo');

      // Helpers
      const reJPName = /^[\p{Script=Han}\p{Script=Hiragana}\p{Script=Katakana}ー・'’\s]+$/u;
      const reRomajiCaps = /^[A-Z][A-Z\s.'’-]*$/;
      const reKatakana = /^[\p{Script=Katakana}ー\s・]+$/u;

      function normalizeRomaji(){
        if (!elRomaji) return;
        elRomaji.value = (elRomaji.value || '').replace(/[a-z]/g, m => m.toUpperCase());
      }

      function setValid(el){ if (el) el.setCustomValidity(''); }
      function setInvalid(el, msg){ if (el) el.setCustomValidity(msg); }

      function validateNameFields(){
        if (!elRomaji || !elKana) return true;

        normalizeRomaji();

        const vR = (elRomaji.value || '').trim();
        const vK = (elKana.value || '').trim();

        if (!vR) {
          setInvalid(elRomaji, '氏名（ローマ字）を入力してください。');
        } else if (!(reJPName.test(vR) || reRomajiCaps.test(vR))) {
          setInvalid(elRomaji, '英字は大文字（A-Z）のみ、または漢字で入力してください。');
        } else {
          setValid(elRomaji);
        }

        if (!vK) {
          setInvalid(elKana, '氏名（カタカナ）を入力してください。');
        } else if (!reKatakana.test(vK)) {
          setInvalid(elKana, 'カタカナで入力してください。');
        } else {
          setValid(elKana);
        }

        return elRomaji.checkValidity() && elKana.checkValidity();
      }

      function validateDob(){
        if (!elY || !elM || !elD) return true;
        const y = (elY.value || '').trim();
        const m = (elM.value || '').trim();
        const d = (elD.value || '').trim();

        let ok = true;
        [elY, elM, elD].forEach(el => setValid(el));

        if (!y || !m || !d) {
          if (!y) setInvalid(elY, '生年月日（年）を入力してください。');
          if (!m) setInvalid(elM, '生年月日（月）を入力してください。');
          if (!d) setInvalid(elD, '生年月日（日）を入力してください。');
          return false;
        }

        const yy = parseInt(y, 10), mm = parseInt(m, 10), dd = parseInt(d, 10);
        if (!Number.isFinite(yy) || yy < 1900 || yy > 2100) { setInvalid(elY, '年（YYYY）が正しくありません。'); ok = false; }
        if (!Number.isFinite(mm) || mm < 1 || mm > 12) { setInvalid(elM, '月（MM）が正しくありません。'); ok = false; }
        if (!Number.isFinite(dd) || dd < 1 || dd > 31) { setInvalid(elD, '日（DD）が正しくありません。'); ok = false; }

        if (!ok) return false;

        const dt = new Date(yy, mm - 1, dd);
        const real = (dt.getFullYear() === yy && (dt.getMonth() + 1) === mm && dt.getDate() === dd);
        if (!real) { setInvalid(elD, '存在しない日付です。'); return false; }

        return true;
      }

      function validatePhone(){
        if (!elPhone) return true;
        const v = (elPhone.value || '').trim();
        const digits = v.replace(/[^\d]/g, '');
        if (!v) { setInvalid(elPhone, '個人携帯番号を入力してください。'); return false; }
        if (digits.length < 10 || digits.length > 13) { setInvalid(elPhone, '電話番号が正しくありません。'); return false; }
        setValid(elPhone);
        return true;
      }

      function validatePostal(){
        if (!elPostal) return true;
        const v = (elPostal.value || '').trim();
        const ok = /^\d{3}-?\d{4}$/.test(v);
        if (!v) { setInvalid(elPostal, '郵便番号を入力してください。'); return false; }
        if (!ok) { setInvalid(elPostal, '郵便番号は 123-4567 の形式で入力してください。'); return false; }
        setValid(elPostal);
        return true;
      }

      function validatePhoto(){
        // ✅ if already saved in DB, file input is not required
        const saved = (document.getElementById('photo_path')?.value || '').trim();
        if (saved) { setValid(elPhoto); return true; }

        if (!elPhoto) return true;
        if (!elPhoto.files || elPhoto.files.length === 0) {
          setInvalid(elPhoto, '証明写真をアップロードしてください。');
          return false;
        }
        setValid(elPhoto);
        return true;
      }

      function validatePane(pane){
        if (!pane) return true;

        let ok = true;

        // Base HTML required checks
        const requiredEls = Array.from(pane.querySelectorAll('[required]'));
        requiredEls.forEach(el => { if (!el.checkValidity()) ok = false; });

        // Our extra gate rules
        if (pane === step1){
          ok = validateNameFields() && ok;
          ok = validateDob() && ok;
          ok = validatePostal() && ok;
          ok = validatePhone() && ok;
        }
        if (pane === step2){
          ok = validatePhoto() && ok;
        }

        if (!ok){
          const firstInvalid = pane.querySelector(':invalid');
          if (firstInvalid && typeof firstInvalid.reportValidity === 'function') firstInvalid.reportValidity();
        }
        return ok;
      }

      if (elRomaji){
        elRomaji.addEventListener('input', normalizeRomaji);
        elRomaji.addEventListener('blur', () => { normalizeRomaji(); validateNameFields(); });
      }
      if (elKana){
        elKana.addEventListener('blur', () => { validateNameFields(); });
      }
      [elY, elM, elD].forEach(el => el && el.addEventListener('blur', validateDob));
      if (elPostal) elPostal.addEventListener('blur', validatePostal);
      if (elPhone) elPhone.addEventListener('blur', validatePhone);

      // Gate next-step clicks
      document.addEventListener('click', function(e){
        const btn = e.target.closest('.js-next-step');
        if (!btn) return;

        const activePane = document.querySelector('.steps .step-pane.is-active');
        if (!activePane) return;

        if (activePane === step1 || activePane === step2){
          const ok = validatePane(activePane);
          if (!ok){
            e.preventDefault();
            e.stopImmediatePropagation();
          }
        }
      }, true);
    })();

    /* ===== Progress bar logic (unchanged) ===== */
    (function () {
      const SEGMENTS = 6;
      const INC = 100 / SEGMENTS;
      const stepsRoot = document.querySelector('.steps');
      const panes = Array.from(document.querySelectorAll('.steps .step-pane'));
      const progressFill = document.getElementById('progressFill');
      const labels = Array.from(document.querySelectorAll('#progressWrap .progress-labels li'));
      const lastPane = panes[panes.length - 1];
      const submitBtn = lastPane ? lastPane.querySelector('button[type="submit"]') : null;

      function getActiveIdx() {
        const i = panes.findIndex(p => p.classList.contains('is-active'));
        return i >= 0 ? i : 0;
      }
      function allLastPaneFieldsFilled() {
        if (!lastPane) return false;
        const fields = Array.from(lastPane.querySelectorAll('input:not([type="hidden"]), select, textarea'));
        if (!fields.length) return false;
        return fields.every(el => el.disabled || (el.value || '').trim() !== '');
      }
      function setSubmitState(done) {
        if (!submitBtn) return;
        submitBtn.disabled = !done;
        submitBtn.style.opacity = done ? '' : '0.7';
        submitBtn.style.pointerEvents = done ? '' : 'none';
      }
      function setLabelStates(activeSegments, isDone) {
        if (!labels.length) return;
        labels.forEach(li => li.classList.remove('is-active', 'is-dim', 'is-done'));
        const activeStepIdx = getActiveIdx();
        const currentLabelIdx = Math.min(activeStepIdx, labels.length - 2);
        labels.forEach((li, i) => {
          if (i < currentLabelIdx) li.classList.add('is-done');
          if (i === currentLabelIdx) li.classList.add('is-active');
          if (i > currentLabelIdx) li.classList.add('is-dim');
        });
        if (isDone && labels.length) {
          labels.forEach(li => li.classList.add('is-dim'));
          labels[labels.length - 1].classList.remove('is-dim');
          labels[labels.length - 1].classList.add('is-active');
        }
      }
      function updateProgress() {
        const idx = getActiveIdx();
        const atLastPane = (idx === panes.length - 1);
        let segments = Math.min(idx, SEGMENTS - 1);
        let finalDone = false;
        if (atLastPane && allLastPaneFieldsFilled()) { segments = SEGMENTS; finalDone = true; }
        const pct = Math.min(100, Math.max(0, segments * INC));
        if (progressFill) {
          progressFill.style.width = pct + '%';
          progressFill.setAttribute('aria-valuenow', String(Math.round(pct)));
        }
        setLabelStates(segments, finalDone);
        setSubmitState(finalDone);
      }
      function deferUpdate() { requestAnimationFrame(() => requestAnimationFrame(updateProgress)); }
      document.addEventListener('click', (e) => {
        if (e.target.closest('.js-next-step') || e.target.closest('.js-prev-step')) deferUpdate();
      });
      if (stepsRoot && 'MutationObserver' in window) {
        new MutationObserver(deferUpdate).observe(stepsRoot, { attributes: true, subtree: true, attributeFilter: ['class'] });
      }
      if (lastPane) {
        lastPane.addEventListener('input', updateProgress, true);
        lastPane.addEventListener('change', updateProgress, true);
      }
      window.addEventListener('load', updateProgress);
      updateProgress();
    })();

    /* ===== ✅ DB Autosave (replaces old sessionStorage autosave) ===== */
    (function () {
      const form = document.getElementById('rirekiForm');
      if (!form) return;

      const token = document.getElementById('token')?.value || '';
      const saveUrl = window.location.pathname
        + window.location.search.replace(/([?&])action=[^&]*/,'$1').replace(/[?&]$/,'')
        + (window.location.search.includes('?') ? '&' : '?')
        + 'action=save';

      function activeStepNumber(){
        const panes = Array.from(document.querySelectorAll('.steps .step-pane'));
        const idx = panes.findIndex(p => p.classList.contains('is-active'));
        return (idx >= 0 ? idx + 1 : 1);
      }

      function toFormData(stepOverride){
        const fd = new FormData(form);
        const step = (typeof stepOverride === 'number' && stepOverride >= 1 && stepOverride <= 7)
          ? stepOverride
          : activeStepNumber();
        fd.set('_step_current', String(step));
        return fd;
      }

      let t = null;
      let lastSent = 0;
      let inflight = null;

      async function saveDraft(stepOverride, {force=false} = {}){
        if (!token) return;
        const now = Date.now();
        if (!force && now - lastSent < 350) return;
        lastSent = now;

        const fd = toFormData(stepOverride);
        try {
          inflight = fetch(saveUrl, { method: 'POST', body: fd })
            .then(async (res) => {
              const data = await res.json().catch(() => null);
              if (!res.ok || !data || data.ok !== true) {
                console.warn('[DB save] failed', data);
              }
              return data;
            })
            .catch((e) => {
              console.warn('[DB save] error', e);
              return null;
            });
          return await inflight;
        } finally {
          inflight = null;
        }
      }

      function scheduleSave(){
        clearTimeout(t);
        t = setTimeout(() => saveDraft(null, {force:false}), 450);
      }

      // expose hooks for kaigo.js navigation
      window.KAIGO_scheduleSave = scheduleSave;
      window.KAIGO_saveDraftNow = async function(stepNum){
        // force immediate save and wait (used before step change)
        return await saveDraft(typeof stepNum === 'number' ? stepNum : null, {force:true});
      };
      window.KAIGO_saveStepCurrent = function(stepNum){
        // minimal update: only step_current (no overwrite of other fields)
        try{
          if (!token) return;
          const fd = new FormData();
          fd.append('token', token);
          fd.append('_step_current', String(stepNum));
          fetch(saveUrl, { method:'POST', body: fd })
            .then(res => res.json().catch(() => null))
            .catch(() => null);
        }catch(_){}
      };

      form.addEventListener('input', scheduleSave, true);
      form.addEventListener('change', scheduleSave, true);

      window.addEventListener('beforeunload', () => {
        // best-effort sync via sendBeacon
        try {
          const fd = toFormData();
          const url = saveUrl;
          if (navigator.sendBeacon) navigator.sendBeacon(url, fd);
        } catch (_) {}
      });

      // initial save (creates/updates draft immediately)
      scheduleSave();
    })();

    /* ===== ✅ Photo upload to DB (same file) ===== */
    (function(){
      const elPhoto = document.getElementById('photo');
      const token = document.getElementById('token')?.value || '';
      const preview = document.getElementById('photoPreview');
      const img = document.getElementById('photoPreviewImg');
      const photoPath = document.getElementById('photo_path');

      if (!elPhoto) return;

      const uploadUrl = window.location.pathname + (window.location.search ? window.location.search + '&' : '?') + 'action=upload_photo';

      function showPreview(src){
        if (!preview || !img) return;
        img.src = src;
        preview.style.display = 'flex';
      }

      elPhoto.addEventListener('change', async () => {
        if (!elPhoto.files || !elPhoto.files[0]) return;

        // instant local preview
        try { showPreview(URL.createObjectURL(elPhoto.files[0])); } catch(_) {}

        const fd = new FormData();
        fd.append('token', token);
        fd.append('photo', elPhoto.files[0]);

        try {
          const res = await fetch(uploadUrl, { method: 'POST', body: fd });
          const data = await res.json().catch(() => null);

          if (!res.ok || !data || data.ok !== true) {
            alert('写真アップロードに失敗しました：' + (data?.error || 'unknown'));
            return;
          }

          if (photoPath) photoPath.value = data.photo_path || '';
          if (data.photo_path) showPreview(data.photo_path);
        } catch (e) {
          alert('写真アップロードに失敗しました（通信エラー）');
          console.warn(e);
        }
      });
    })();

    /* ===== AI: simple-Japanese rewrite via your Worker (unchanged) ===== */
    (function () {
      const WORKER_URL = 'https://rireki-ai.bikash4jp.workers.dev';

      const TASK_BY_TARGET = {
        '#prText': 'kaigo_compose_self_pr',
        '#motivationText': 'kaigo_compose_motivation',
        '#prefText': 'kaigo_compose_preferences',
      };

      async function fetchJSON(url, payload) {
        const res = await fetch(url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });

        const raw = await res.text();
        let data = null;
        try { data = JSON.parse(raw); } catch (_) {}

        if (!res.ok) {
          const msg = (data && (data.error || data.message)) ? (data.error || data.message) : raw;
          throw new Error(`HTTP ${res.status}: ${msg}`);
        }
        if (!data || data.ok !== true) {
          const msg = data && (data.error || data.message) ? (data.error || data.message) : raw;
          throw new Error(`Bad JSON: ${msg}`);
        }
        return data;
      }

      async function callAI(task, text) {
        try {
          const data = await fetchJSON(WORKER_URL, {
            task,
            text,
            meta: { domain: 'kaigo', level: 'simple', max_chars: 420 }
          });
          return (data.output || '').trim();
        } catch (err) {
          console.warn('[AI] primary failed', err);

          const data = await fetchJSON(WORKER_URL, {
            task: 'rewrite_to_simple_japanese',
            text,
            meta: { note: 'fallback', wanted_task: task }
          });
          return (data.output || '').trim();
        }
      }

      document.addEventListener('click', async (e) => {
        const btn = e.target.closest('button[data-ai-target]');
        if (!btn) return;

        const sel = btn.getAttribute('data-ai-target');
        const ta = document.querySelector(sel);
        if (!ta) return;

        const original = (ta.value || '').trim();
        if (!original) { alert('キーワードでもOKなので、まず入力してください。'); return; }

        const task = TASK_BY_TARGET[sel] || 'kaigo_compose_self_pr';

        btn.disabled = true;
        const old = btn.textContent;
        btn.textContent = '作成中…';

        try {
          const out = await callAI(task, original);
          if (out) ta.value = out;
        } catch (err) {
          console.error(err);
          alert('AIエラー：' + (err?.message || 'unknown'));
        } finally {
          btn.disabled = false;
          btn.textContent = old;
        }
      });
    })();