// /home/it-future/www/itf/rireki/kaigo/js/kaigo.js
// ✅ DB autosave + permanent photo upload
// - No sessionStorage JSON saving anymore
// - Photo uploaded via AJAX and saved path stored in hidden photo_path + DB

(function () {
  if (window.__KAIGO_FORM_BOUND__) return;
  window.__KAIGO_FORM_BOUND__ = true;

  const form = document.getElementById('rirekiForm');
  if (!form) return;

  const tokenEl = document.getElementById('token');
  const token = tokenEl ? tokenEl.value : '';
  const stepsEl = document.querySelector('.steps');
  const panes = Array.from(document.querySelectorAll('.step-pane'));
  let idx = panes.findIndex(p => p.classList.contains('is-active'));
  if (idx < 0) { idx = 0; panes[0]?.classList.add('is-active'); }

  function fit() {
    if (!stepsEl) return;
    const active = panes[idx];
    if (active) stepsEl.style.height = active.scrollHeight + 'px';
  }
  window.addEventListener('load', fit);
  window.addEventListener('resize', fit);

  function go(dir) {
    const to = Math.max(0, Math.min(panes.length - 1, idx + dir));
    if (to === idx) return;
    const fromEl = panes[idx], toEl = panes[to];

    toEl.classList.add('is-active');
    fromEl.classList.remove('is-active');
    idx = to;

    // save step_current
    saveStepCurrent(idx + 1);

    fit();
    try { window.scrollTo({ top: 0, behavior: 'smooth' }); } catch (_) { window.scrollTo(0,0); }
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
    }
    [y, m, d].forEach(el => el.addEventListener('input', () => { calc(); scheduleSaveAll(); }));
  })();

  // ===== Photo preview + existing photo show =====
  const photoInput = document.getElementById('photo');
  const photoBox = document.getElementById('photoPreview');
  const photoImg = document.getElementById('photoPreviewImg');
  const photoPathHidden = document.getElementById('photo_path');

  function showPhoto(url) {
    if (!photoBox || !photoImg) return;
    if (!url) {
      photoBox.style.display = 'none';
      photoImg.removeAttribute('src');
      return;
    }
    photoImg.src = url;
    photoBox.style.display = 'flex';
    fit();
  }

  // show existing from server
  if (window.__KAIGO_EXISTING_PHOTO__) {
    showPhoto(window.__KAIGO_EXISTING_PHOTO__);
  }

  async function uploadPhoto(file) {
    const fd = new FormData();
    fd.append('token', token);
    fd.append('photo', file);

    const res = await fetch('/rireki/kaigo/php/upload_photo.php', {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
    });

    const data = await res.json().catch(() => null);
    if (!res.ok || !data || !data.ok) {
      throw new Error((data && data.error) ? data.error : 'Upload failed');
    }
    return data; // {ok:true, photo_path:'...', photo_url:'...'}
  }

  if (photoInput) {
    photoInput.addEventListener('change', async () => {
      const f = photoInput.files && photoInput.files[0];
      if (!f) return;

      // local preview first
      const url = URL.createObjectURL(f);
      showPhoto(url);

      // upload to server + store in DB
      try {
        photoInput.disabled = true;
        const out = await uploadPhoto(f);
        if (photoPathHidden) photoPathHidden.value = out.photo_path || '';
        showPhoto(out.photo_url || out.photo_path || '');
      } catch (e) {
        alert('写真アップロードに失敗しました：' + (e?.message || 'unknown'));
      } finally {
        photoInput.disabled = false;
      }
    });
  }

  // ===== DB autosave (throttled) =====
  let saveTimer = null;
  function scheduleSaveAll() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(saveAllToDB, 350);
  }

  function collectFormData() {
    const fd = new FormData(form);
    // ensure token present
    if (!fd.get('token') && token) fd.append('token', token);
    // remove actual photo file from autosave (we upload separately)
    fd.delete('photo');
    return fd;
  }

  async function saveAllToDB() {
    if (!token) return;
    const fd = collectFormData();
    const res = await fetch('/rireki/kaigo/php/save_resume.php', {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
    });
    const data = await res.json().catch(() => null);
    if (!res.ok || !data || !data.ok) {
      console.warn('[autosave] failed', data);
    }
  }

  async function saveStepCurrent(stepNum) {
    if (!token) return;
    const fd = new FormData();
    fd.append('token', token);
    fd.append('step_current', String(stepNum));
    const res = await fetch('/rireki/kaigo/php/save_resume.php', {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
    });
    const data = await res.json().catch(() => null);
    if (!res.ok || !data || !data.ok) console.warn('[step save] failed', data);
  }

  // save on any input/select/textarea change
  form.addEventListener('input', scheduleSaveAll, true);
  form.addEventListener('change', scheduleSaveAll, true);

  // initial save once
  scheduleSaveAll();

  fit();
})();
