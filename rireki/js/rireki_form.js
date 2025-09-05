(function () {
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const form = $('#rireki-form');
  const panes = $$('.step-pane');
  const stepchips = $$('.stepbar .step');

  // step nav
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

  // dob -> age auto
  ['dob_yyyy','dob_mm','dob_dd'].forEach(id => {
    const el = $('#' + id);
    el && el.addEventListener('input', updateAge);
  });
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

  // dynamic rows (edu / exp / lic)
  $$('.row-add').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetSel = btn.dataset.target;
      const tbody = $(targetSel);
      if (!tbody) return;
      const last = tbody.lastElementChild;
      const tr = document.createElement('tr');
      tr.innerHTML = last ? last.innerHTML : '';
      // clear values
      $$('input', tr).forEach(i => i.value = '');
      tbody.appendChild(tr);
      bindDelete(tr);
    });
  });

  function bindDelete(scope) {
    $$('.row-del', scope || document).forEach(del => {
      del.onclick = () => {
        const tbody = del.closest('tbody');
        const table = del.closest('table');
        const min = parseInt(table?.getAttribute('data-min') || '0', 10);
        const rows = $$('tr', tbody);
        if (rows.length <= min) return; // keep minimum row(s)
        del.closest('tr').remove();
      };
    });
  }
  bindDelete(document);

  // simple validations on submit (only step 1 hard-required)
  form.addEventListener('submit', (e) => {
    const requiredIds = ['personal_name_kana','personal_name_kanji','dob_yyyy','dob_mm','dob_dd'];
    const firstInvalid = requiredIds.find(id => !$('#' + id)?.value.trim());
    if (firstInvalid) {
      e.preventDefault();
      alert('必須項目（氏名・生年月日）を入力してください。');
      showStep(1);
      $('#' + firstInvalid)?.focus();
      return;
    }
    // postcode format if provided
    const pc = $('#postcode')?.value.trim();
    if (pc && !/^\d{3}-?\d{4}$/.test(pc)) {
      e.preventDefault();
      alert('郵便番号の形式が正しくありません（123-4567）。');
      showStep(1); $('#postcode').focus(); return;
    }
    // email basic check
    const em = $('#email')?.value.trim();
    if (em && !/.+@.+\..+/.test(em)) {
      e.preventDefault();
      alert('メールアドレスの形式が正しくありません。');
      showStep(1); $('#email').focus(); return;
    }
  });
})();
