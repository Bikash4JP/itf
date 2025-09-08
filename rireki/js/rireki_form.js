(function () {
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const form = $('#rireki-form');
  const panes = $$('.step-pane');
  const stepchips = $$('.stepbar .step');

  function showStep(n) {
    panes.forEach(p => p.classList.toggle('is-active', p.dataset.step === String(n)));
    stepchips.forEach(c => c.classList.toggle('is-active', c.dataset.step === String(n)));
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
  $$('.btn.next').forEach(btn => btn.addEventListener('click', () => {
    const pane = btn.closest('.step-pane');
    const n = Number(pane.dataset.step);
    if (n < panes.length) showStep(n + 1);
  }));
  $$('.btn.prev').forEach(btn => btn.addEventListener('click', () => {
    const pane = btn.closest('.step-pane');
    const n = Number(pane.dataset.step);
    if (n > 1) showStep(n - 1);
  }));

  // ===== Date helpers =====
  function autoSlashYMD(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 8);
    if (v.length >= 5) v = v.slice(0,4) + '/' + v.slice(4);
    if (v.length >= 8) v = v.slice(0,7) + '/' + v.slice(7);
    input.value = v;
  }
  function autoSlashYM(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 6);
    if (v.length >= 5) v = v.slice(0,4) + '/' + v.slice(4);
    input.value = v;
  }
  function splitYMD(v) {
    const m = v.match(/^(\d{4})\/(\d{2})\/(\d{2})$/);
    return m ? { y: m[1], m: m[2], d: m[3] } : { y:'', m:'', d:'' };
  }
  function splitYM(v) {
    const m = v.match(/^(\d{4})\/(\d{2})$/);
    return m ? { y: m[1], m: m[2] } : { y:'', m:'' };
  }

  // DOB field (single input)
  const dob = $('#dob');
  if (dob) {
    dob.addEventListener('input', () => {
      autoSlashYMD(dob);
      const { y, m, d } = splitYMD(dob.value);
      $('#dob_yyyy').value = y; $('#dob_mm').value = m; $('#dob_dd').value = d;
      updateAge();
    });
  }

  // Auto age from DOB hidden values
  function updateAge() {
    const y = parseInt($('#dob_yyyy')?.value || '', 10);
    const m = parseInt($('#dob_mm')?.value || '', 10);
    const d = parseInt($('#dob_dd')?.value || '', 10);
    if (!y || !m || !d) return;
    const today = new Date();
    let age = today.getFullYear() - y;
    const mm = today.getMonth() + 1;
    const dd = today.getDate();
    if (m > mm || (m === mm && d > dd)) age--;
    const out = $('#age');
    if (out) out.value = String(age);
  }

  // YYYY/MM inputs in dynamic rows
  function bindRowDateYM(row) {
    const input = row.querySelector('input.date-ym');
    const yHidden = row.querySelector('input[name$="_year[]"]');
    const mHidden = row.querySelector('input[name$="_month[]"]');
    if (!input || !yHidden || !mHidden) return;
    input.addEventListener('input', () => {
      autoSlashYM(input);
      const { y, m } = splitYM(input.value);
      yHidden.value = y; mHidden.value = m;
    });
  }
  function initAllYM() {
    $$('#eduBody tr, #expBody tr, #licBody tr').forEach(bindRowDateYM);
  }
  initAllYM();

  // photo preview
  const photo = $('#photo');
  const pv = $('#photoPreviewImg');
  if (photo && pv) {
    photo.addEventListener('change', () => {
      const f = photo.files && photo.files[0];
      if (!f) { pv.hidden = true; pv.src = ''; return; }
      if (!/^image\/(jpeg|png)$/.test(f.type)) { alert('JPEG または PNG を選択してください'); photo.value = ''; return; }
      if (f.size > 5 * 1024 * 1024) { alert('5MB以下の画像を選択してください'); photo.value=''; return; }
      const url = URL.createObjectURL(f);
      pv.src = url; pv.hidden = false;
    });
  }

  // dynamic rows (clone last row; keep names)
  $$('.row-add').forEach(btn => {
    btn.addEventListener('click', () => {
      const tbody = document.querySelector(btn.dataset.target);
      if (!tbody) return;
      const last = tbody.lastElementChild;
      if (!last) return;
      const tr = last.cloneNode(true);
      tr.querySelectorAll('input').forEach(i => {
        if (i.type === 'hidden' || i.classList.contains('date-ym')) i.value = '';
        else if (['text','number','email','tel'].includes(i.type)) i.value = '';
      });
      tbody.appendChild(tr);
      bindDelete(tr);
      bindRowDateYM(tr);
    });
  });

  function bindDelete(scope) {
    $$('.row-del', scope || document).forEach(del => {
      del.onclick = () => {
        const tbody = del.closest('tbody');
        const table = del.closest('table');
        const min = parseInt(table?.getAttribute('data-min') || '0', 10);
        const rows = Array.from(tbody.querySelectorAll('tr'));
        if (rows.length <= min) return;
        del.closest('tr').remove();
      };
    });
  }
  bindDelete(document);

  // validation
  form.addEventListener('submit', (e) => {
    if ($('#dob')) {
      const { y, m, d } = splitYMD($('#dob').value);
      $('#dob_yyyy').value = y || '';
      $('#dob_mm').value   = m || '';
      $('#dob_dd').value   = d || '';
      if (!y || !m || !d) {
        e.preventDefault();
        alert('生年月日（YYYY/MM/DD）を入力してください');
        showStep(1); $('#dob').focus(); return;
      }
    }
    const pc = $('#postcode')?.value.trim();
    if (pc && !/^\d{3}-?\d{4}$/.test(pc)) {
      e.preventDefault();
      alert('郵便番号の形式が正しくありません（123-4567）。');
      showStep(1); $('#postcode').focus(); return;
    }
    const em = $('#email')?.value.trim();
    if (em && !/.+@.+\..+/.test(em)) {
      e.preventDefault();
      alert('メールアドレスの形式が正しくありません。');
      showStep(1); $('#email').focus(); return;
    }
  });
})();
