(function () {
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const form = $('#rireki-form');

  // step nav
  const panes = $$('.step-pane');
  function showStep(n) {
    panes.forEach(p => p.classList.toggle('is-active', p.dataset.step === String(n)));
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
  $$('.btn.next').forEach(btn => btn.addEventListener('click', () => {
    const pane = btn.closest('.step-pane'); const n = Number(pane.dataset.step);
    if (n < panes.length) showStep(n + 1);
  }));
  $$('.btn.prev').forEach(btn => btn.addEventListener('click', () => {
    const pane = btn.closest('.step-pane'); const n = Number(pane.dataset.step);
    if (n > 1) showStep(n - 1);
  }));

  // date helpers
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
    const m = v.match(/^(\d{4})\/(0[1-9]|1[0-2])\/(0[1-9]|[12]\d|3[01])$/);
    return m ? { y: m[1], m: m[2], d: m[3] } : { y:'', m:'', d:'' };
  }
  function splitYM(v) {
    const m = v.match(/^(\d{4})\/(0[1-9]|1[0-2])$/);
    return m ? { y: m[1], m: m[2] } : { y:'', m:'' };
  }

  // DOB single field
  const dob = $('#dob');
  if (dob) {
    dob.addEventListener('input', () => {
      autoSlashYMD(dob);
      const { y, m, d } = splitYMD(dob.value);
      $('#dob_yyyy').value = y; $('#dob_mm').value = m; $('#dob_dd').value = d;
      updateAge();
    });
  }
  function updateAge() {
    const y = parseInt($('#dob_yyyy')?.value || '', 10);
    const m = parseInt($('#dob_mm')?.value || '', 10);
    const d = parseInt($('#dob_dd')?.value || '', 10);
    if (!y || !m || !d) return;
    const today = new Date();
    let age = today.getFullYear() - y;
    const mm = today.getMonth() + 1, dd = today.getDate();
    if (m > mm || (m === mm && d > dd)) age--;
    const out = $('#age'); if (out) out.value = String(age);
  }

  // helpers
  const needsEndEdu = (val) => /^(卒|修了|退|grad|drop)/i.test(String(val || '').trim());
  const needsEndExp = (val) => /^(退|resign|quit|leave)/i.test(String(val || '').trim());

  // 学歴 rows
  function bindEduRow(row) {
    const start = row.querySelector('input.edu-start');
    const end   = row.querySelector('input.edu-end');
    const stSel = row.querySelector('select.edu-status');

    if (start) {
      start.addEventListener('input', () => {
        autoSlashYM(start);
        const { y, m } = splitYM(start.value);
        row.querySelector('input[name="edu_start_year[]"]').value  = y;
        row.querySelector('input[name="edu_start_month[]"]').value = m;
      });
    }
    if (end) {
      end.addEventListener('input', () => {
        autoSlashYM(end);
        const { y, m } = splitYM(end.value);
        row.querySelector('input[name="edu_end_year[]"]').value  = y;
        row.querySelector('input[name="edu_end_month[]"]').value = m;
      });
    }
    if (stSel) {
      const toggle = () => {
        const need = needsEndEdu(stSel.value);
        end.disabled = !need;
        if (!need) {
          end.value = '';
          row.querySelector('input[name="edu_end_year[]"]').value = '';
          row.querySelector('input[name="edu_end_month[]"]').value = '';
        }
      };
      stSel.addEventListener('change', toggle);
      toggle();
    }
  }
  function initEduRows() { $$('#eduBody tr').forEach(bindEduRow); }
  initEduRows();

  // 職歴 rows
  function bindExpRow(row) {
    const start = row.querySelector('input.exp-start');
    const end   = row.querySelector('input.exp-end');
    const stSel = row.querySelector('select.exp-status');

    if (start) {
      start.addEventListener('input', () => {
        autoSlashYM(start);
        const { y, m } = splitYM(start.value);
        row.querySelector('input[name="exp_start_year[]"]').value  = y;
        row.querySelector('input[name="exp_start_month[]"]').value = m;
      });
    }
    if (end) {
      end.addEventListener('input', () => {
        autoSlashYM(end);
        const { y, m } = splitYM(end.value);
        row.querySelector('input[name="exp_end_year[]"]').value  = y;
        row.querySelector('input[name="exp_end_month[]"]').value = m;
      });
    }
    if (stSel) {
      const toggle = () => {
        const need = needsEndExp(stSel.value);
        end.disabled = !need;
        if (!need) {
          end.value = '';
          row.querySelector('input[name="exp_end_year[]"]').value = '';
          row.querySelector('input[name="exp_end_month[]"]').value = '';
        }
      };
      stSel.addEventListener('change', toggle);
      toggle();
    }
  }
  function initExpRows() { $$('#expBody tr').forEach(bindExpRow); }
  initExpRows();

  // generic YYYY/MM rows (licenses etc.)
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
  $$('#licBody tr').forEach(bindRowDateYM);

  // photo preview
  const photo = $('#photo'), pv = $('#photoPreviewImg');
  if (photo && pv) {
    photo.addEventListener('change', () => {
      const f = photo.files && photo.files[0];
      if (!f) { pv.hidden = true; pv.src=''; return; }
      if (!/^image\/(jpeg|png)$/.test(f.type)) { alert('JPEG または PNG を選択してください'); photo.value = ''; return; }
      if (f.size > 5 * 1024 * 1024) { alert('5MB以下の画像を選択してください'); photo.value=''; return; }
      pv.src = URL.createObjectURL(f); pv.hidden = false;
    });
  }

  // dynamic rows add/remove
  $$('.row-add').forEach(btn => {
    btn.addEventListener('click', () => {
      const tbody = document.querySelector(btn.dataset.target);
      if (!tbody) return;
      const last = tbody.lastElementChild;
      if (!last) return;
      const tr = last.cloneNode(true);

      // reset inputs
      tr.querySelectorAll('input,select').forEach(el => {
        if (el.tagName === 'SELECT') el.selectedIndex = 0;
        else el.value = '';
        if (el.classList.contains('edu-end') || el.classList.contains('exp-end')) el.disabled = true;
      });

      tbody.appendChild(tr);
      // binders
      if (tbody.id === 'eduBody') bindEduRow(tr);
      else if (tbody.id === 'expBody') bindExpRow(tr);
      else bindRowDateYM(tr);
      bindDelete(tr);
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
    // DOB required
    if ($('#dob')) {
      autoSlashYMD($('#dob'));
      const { y, m, d } = splitYMD($('#dob').value);
      $('#dob_yyyy').value = y || '';
      $('#dob_mm').value   = m || '';
      $('#dob_dd').value   = d || '';
      if (!y || !m || !d) {
        e.preventDefault(); alert('生年月日（YYYY/MM/DD）を入力してください');
        showStep(1); $('#dob').focus(); return;
      }
    }

    // 学歴: end date required when 卒/退 selected
    let okEdu = true;
    $$('#eduBody tr').forEach(tr => {
      const stSel = tr.querySelector('select.edu-status');
      const end   = tr.querySelector('input.edu-end');
      if (stSel && needsEndEdu(stSel.value)) {
        const { y, m } = splitYM(end.value);
        if (!y || !m) okEdu = false;
      }
    });
    if (!okEdu) {
      e.preventDefault(); alert('学歴で「卒業／退学」を選んだ行には 終了年月(YYYY/MM) を入力してください。');
      showStep(2); return;
    }

    // 職歴: end date required when 退 selected
    let okExp = true;
    $$('#expBody tr').forEach(tr => {
      const stSel = tr.querySelector('select.exp-status');
      const end   = tr.querySelector('input.exp-end');
      if (stSel && needsEndExp(stSel.value)) {
        const { y, m } = splitYM(end.value);
        if (!y || !m) okExp = false;
      }
    });
    if (!okExp) {
      e.preventDefault(); alert('職歴で「退職」を選んだ行には 退職年月(YYYY/MM) を入力してください。');
      showStep(3); return;
    }
  });
})();
// --- Step router with slide animations ---
(function () {
  const panes = Array.from(document.querySelectorAll('.step-pane'));
  if (!panes.length) return;

  // ensure one active
  let idx = panes.findIndex(p => p.classList.contains('is-active'));
  if (idx < 0) { idx = 0; panes[0].classList.add('is-active'); }

  function goStep(next, dir) {
    if (next === idx || next < 0 || next >= panes.length) return;

    const from = panes[idx];
    const to   = panes[next];

    // temporarily show both panels during animation
    from.classList.add('is-transitioning');
    to.classList.add('is-transitioning', 'is-active');

    // choose classes
    const enter = dir === 'back' ? 'step-enter-left'  : 'step-enter-right';
    const exit  = dir === 'back' ? 'step-exit-right'  : 'step-exit-left';

    // apply
    to.classList.add(enter);
    from.classList.add(exit);

    // scroll to top so new step starts at top
    try { window.scrollTo({ top: 0, behavior: 'smooth' }); } catch (_) { window.scrollTo(0,0); }

    const cleanup = () => {
      from.classList.remove('is-active', 'is-transitioning', 'step-exit-left', 'step-exit-right');
      to.classList.remove('is-transitioning', 'step-enter-left', 'step-enter-right');
      idx = next;
    };

    const onEnd = (e) => {
      if (e.target !== to) return; // wait for 'to' animation end
      to.removeEventListener('animationend', onEnd);
      cleanup();
    };

    // in case animationend is missed, cleanup anyway
    to.addEventListener('animationend', onEnd, { once: true });
    setTimeout(cleanup, 500);
  }

  // Next / Back buttons
  document.addEventListener('click', (e) => {
    const nextBtn = e.target.closest('.js-next-step');
    const prevBtn = e.target.closest('.js-prev-step');
    if (nextBtn) { e.preventDefault(); goStep(idx + 1, 'forward'); }
    if (prevBtn) { e.preventDefault(); goStep(idx - 1, 'back');    }
  });

  // Optional: direct navigation via data-goto-step
  document.querySelectorAll('[data-goto-step]').forEach(el => {
    el.addEventListener('click', (e) => {
      e.preventDefault();
      const target = parseInt(el.getAttribute('data-goto-step'), 10);
      if (!isNaN(target)) goStep(target, target > idx ? 'forward' : 'back');
    });
  });
})();
