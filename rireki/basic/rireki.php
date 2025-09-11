<?php
// /home/it-future/www/itf/rireki/basic/rireki.php
?><!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <title>履歴書フォーム（Basic）</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Theme CSS (merged) -->
  <link rel="stylesheet" href="/rireki/basic/css/recruit.css?v=2">

  <style>
    /* Small page-local tweaks */
    .section-title { margin: 8px 0 12px; font-weight: 800; }
    .help { color:#6b7280; font-size:12px; }
    .photo-inline { display:flex; align-items:flex-start; gap:16px; }
  </style>
</head>
<body>
  <!-- Optional Appbar -->
  <div class="appbar">
    <div class="wrap">
      <h1>履歴書フォーム（Basic）</h1>
      <a class="home" href="/rireki/index.php">← フォーマット選択へ</a>
    </div>
  </div>

  <div class="wrap">
    <form class="card" action="/rireki/basic/php/submit_rireki.php" method="post" enctype="multipart/form-data" id="rirekiForm" novalidate>
      <div class="steps">

        <!-- STEP 1: Personal / Contact -->
        <section class="step-pane is-active" data-step="1">
          <h2>基本情報</h2>
          <div class="grid-2">
            <label>
              フリガナ
              <input type="text" name="personal_name_kana" placeholder="やまだ たろう" autocomplete="name" />
            </label>
            <label>
              氏名
              <input type="text" name="personal_name_kanji" placeholder="山田 太郎" />
            </label>

            <div class="col-2"></div>

            <label>
              生年月日（YYYY/MM/DD）
              <input type="text" id="dob" placeholder="1998/04/01" inputmode="numeric" autocomplete="bday" />
              <input type="hidden" name="dob_yyyy" id="dob_yyyy" />
              <input type="hidden" name="dob_mm"   id="dob_mm" />
              <input type="hidden" name="dob_dd"   id="dob_dd" />
              <div class="help">数字だけ入力で自動的にスラッシュが入ります（例：19980401）</div>
            </label>
            <label>
              年齢（自動）
              <input type="number" name="age" id="age" placeholder="自動算出" readonly />
            </label>

            <label>
              性別
              <select name="gender">
                <option value="">未選択</option>
                <option value="男">男</option>
                <option value="女">女</option>
                <option value="その他">その他</option>
              </select>
            </label>
            <div></div>

            <label>
              住所（フリガナ）
              <input type="text" name="address_kana" placeholder="トウキョウト〇〇シ〜" />
            </label>
            <label>
              郵便番号
              <input type="text" name="postcode" placeholder="123-4567" inputmode="numeric" />
            </label>

            <label class="col-2">
              住所
              <input type="text" name="address_full" placeholder="東京都千代田区1-2-3" />
            </label>

            <label>
              電話番号
              <input type="tel" name="phone" id="phone" placeholder="090-0000-0000" />
            </label>
            <label>
              Eメール
              <input type="email" name="email" placeholder="taro@example.com" />
            </label>

            <div class="col-2">
              <div class="section-title">写真</div>
              <div class="photo-inline">
                <label style="min-width:260px">
                  パスポートサイズ写真（jpg/png）
                  <input type="file" name="photo" id="photo" accept="image/jpeg,image/png" />
                  <div class="photo-preview" id="photoPreview" style="display:none;">
                    <img id="photoPreviewImg" alt="preview">
                  </div>
                </label>
                <div class="help">アップロードした写真はプレビューにも反映され、Excel側ではM3の結合セル内に縦横比を保ってフィットします。</div>
              </div>
            </div>
          </div>

          <div class="nav">
            <button class="btn primary js-next-step">次へ</button>
          </div>
        </section>

        <!-- STEP 2: Education -->
        <section class="step-pane" data-step="2">
          <h2>学歴</h2>

          <table class="table" id="eduTable">
            <thead>
              <tr>
                <th>開始年</th>
                <th>開始月</th>
                <th>学校名</th>
                <th>学部・学科</th>
                <th>区分</th>
                <th>在学状況</th>
                <th>終了年</th>
                <th>終了月</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              <tr class="edu-row">
                <td><input class="date-ym" type="text" name="edu_start_year[]"  placeholder="YYYY" inputmode="numeric"></td>
                <td><input class="date-ym" type="text" name="edu_start_month[]" placeholder="MM"   inputmode="numeric"></td>
                <td><input type="text" name="edu_school_name[]" placeholder="△△大学 / ○○高校 等"></td>
                <td>
                  <select name="edu_faculty[]">
                    <option value="">—</option>
                    <option>理工学部</option><option>経営学部</option><option>情報学部</option>
                    <option>教育学部</option><option>人文学部</option><option>商学部</option>
                    <option>専門課程</option>
                  </select>
                </td>
                <td>
                  <select name="edu_level[]">
                    <option value="">—</option>
                    <option>小学</option><option>中学</option><option>高校</option>
                    <option>専門学校</option><option>大学</option>
                  </select>
                </td>
                <td>
                  <select name="edu_status[]" class="js-status-edu">
                    <option>在学中</option>
                    <option>卒業</option>
                    <option>退学</option>
                  </select>
                </td>
                <td><input class="date-ym js-edu-end-y" type="text" name="edu_end_year[]"  placeholder="YYYY" inputmode="numeric" disabled></td>
                <td><input class="date-ym js-edu-end-m" type="text" name="edu_end_month[]" placeholder="MM"   inputmode="numeric" disabled></td>
                <td>
                  <button type="button" class="row-add" data-for="edu">＋</button>
                  <button type="button" class="row-del">−</button>
                </td>
              </tr>
            </tbody>
          </table>

          <div class="nav">
            <button class="btn js-prev-step">戻る</button>
            <button class="btn primary js-next-step">次へ</button>
          </div>
        </section>

        <!-- STEP 3: Work -->
        <section class="step-pane" data-step="3">
          <h2>職歴</h2>

          <table class="table" id="expTable">
            <thead>
              <tr>
                <th>開始年</th>
                <th>開始月</th>
                <th>会社名</th>
                <th>役職 / 職種</th>
                <th>在職状況</th>
                <th>終了年</th>
                <th>終了月</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              <tr class="exp-row">
                <td><input class="date-ym" type="text" name="exp_start_year[]"  placeholder="YYYY" inputmode="numeric"></td>
                <td><input class="date-ym" type="text" name="exp_start_month[]" placeholder="MM"   inputmode="numeric"></td>
                <td><input type="text" name="exp_company[]" placeholder="ABC株式会社"></td>
                <td><input type="text" name="exp_title[]" placeholder="エンジニア / 販売 / 介護 等"></td>
                <td>
                  <select name="exp_status[]" class="js-status-exp">
                    <option>在職中</option>
                    <option>退職</option>
                  </select>
                </td>
                <td><input class="date-ym js-exp-end-y" type="text" name="exp_end_year[]"  placeholder="YYYY" inputmode="numeric" disabled></td>
                <td><input class="date-ym js-exp-end-m" type="text" name="exp_end_month[]" placeholder="MM"   inputmode="numeric" disabled></td>
                <td>
                  <button type="button" class="row-add" data-for="exp">＋</button>
                  <button type="button" class="row-del">−</button>
                </td>
              </tr>
            </tbody>
          </table>

          <div class="nav">
            <button class="btn js-prev-step">戻る</button>
            <button class="btn primary js-next-step">次へ</button>
          </div>
        </section>

        <!-- STEP 4: Licenses -->
        <section class="step-pane" data-step="4">
          <h2>資格・免許</h2>

          <table class="table" id="licTable">
            <thead>
              <tr>
                <th>年</th>
                <th>月</th>
                <th>資格名 / 免許名</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              <tr class="lic-row">
                <td><input class="date-ym" type="text" name="lic_year[]"  placeholder="YYYY" inputmode="numeric"></td>
                <td><input class="date-ym" type="text" name="lic_month[]" placeholder="MM"   inputmode="numeric"></td>
                <td><input type="text" name="lic_name[]"  placeholder="基本情報技術者 / 介護職員初任者研修 等"></td>
                <td>
                  <button type="button" class="row-add" data-for="lic">＋</button>
                  <button type="button" class="row-del">−</button>
                </td>
              </tr>
            </tbody>
          </table>

          <div class="nav">
            <button class="btn js-prev-step">戻る</button>
            <button class="btn primary js-next-step">次へ</button>
          </div>
        </section>

        <!-- STEP 5: PR / Hopes -->
        <section class="step-pane" data-step="5">
          <h2>自己PR・希望</h2>
          <div class="grid-2">
            <label class="col-2">
              志望動機・自己PRなど
              <textarea name="self_pr" rows="5" placeholder="自己PRを入力してください。"></textarea>
            </label>
            <label class="col-2">
              本人希望記入欄
              <textarea name="hopes" rows="5" placeholder="給料・職種・勤務時間・勤務地など希望があれば記入してください。"></textarea>
            </label>
          </div>

          <div class="nav">
            <button class="btn js-prev-step">戻る</button>
            <button class="btn primary" type="submit">この内容でプレビューへ</button>
          </div>
        </section>

      </div>
    </form>
  </div>

  <!-- Behavior JS -->
  <script src="/rireki/basic/js/rireki_form.js?v=2"></script>
  <script>
    // ---- Auto-slash DOB + hidden Y/M/D + age calc ----
    (function(){
      const dob = document.getElementById('dob');
      const y = document.getElementById('dob_yyyy');
      const m = document.getElementById('dob_mm');
      const d = document.getElementById('dob_dd');
      const age = document.getElementById('age');

      function formatDOB(v){
        // keep digits
        v = (v||'').replace(/\D/g,'').slice(0,8);
        if (v.length >= 5) v = v.slice(0,4) + '/' + v.slice(4);
        if (v.length >= 8+1) v = v.slice(0,7) + '/' + v.slice(7);
        return v;
      }
      function updateHidden(){
        const raw = (dob.value||'').replace(/\D/g,'');
        y.value = raw.slice(0,4) || '';
        m.value = raw.slice(4,6) || '';
        d.value = raw.slice(6,8) || '';
        // age (JST)
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
      }
      if (dob){
        dob.addEventListener('input', () => { dob.value = formatDOB(dob.value); updateHidden(); });
        dob.addEventListener('blur', updateHidden);
      }
    })();

    // ---- Photo preview ----
    (function(){
      const input = document.getElementById('photo');
      const box = document.getElementById('photoPreview');
      const img = document.getElementById('photoPreviewImg');
      if (!input || !box || !img) return;
      input.addEventListener('change', () => {
        const f = input.files && input.files[0];
        if (!f) { box.style.display='none'; return; }
        const url = URL.createObjectURL(f);
        img.src = url;
        box.style.display = 'block';
      });
    })();

    // ---- Enable/disable end date by status (Education) ----
    function toggleEduEnd(tr){
      const st = tr.querySelector('.js-status-edu');
      const y  = tr.querySelector('.js-edu-end-y');
      const m  = tr.querySelector('.js-edu-end-m');
      const need = st && /卒業|退学/.test(st.value);
      [y,m].forEach(el => { if(!el) return; el.disabled = !need; if(!need){ el.value=''; } });
    }
    // ---- Enable/disable end date by status (Experience) ----
    function toggleExpEnd(tr){
      const st = tr.querySelector('.js-status-exp');
      const y  = tr.querySelector('.js-exp-end-y');
      const m  = tr.querySelector('.js-exp-end-m');
      const need = st && /退職/.test(st.value);
      [y,m].forEach(el => { if(!el) return; el.disabled = !need; if(!need){ el.value=''; } });
    }

    // Bind change on status selects
    document.addEventListener('change', (e) => {
      const tr = e.target.closest('tr');
      if (!tr) return;
      if (e.target.matches('.js-status-edu')) toggleEduEnd(tr);
      if (e.target.matches('.js-status-exp')) toggleExpEnd(tr);
    });
    // Initialize first rows
    document.querySelectorAll('#eduTable tbody tr').forEach(toggleEduEnd);
    document.querySelectorAll('#expTable tbody tr').forEach(toggleExpEnd);

    // ---- Repeaters (add/remove rows) ----
    function addRow(tableId, rowClass){
      const tbody = document.querySelector(`#${tableId} tbody`);
      const first = tbody.querySelector(`.${rowClass}`);
      const clone = first.cloneNode(true);
      // clear inputs
      clone.querySelectorAll('input').forEach(i => i.value='');
      clone.querySelectorAll('select').forEach(s => { s.selectedIndex = 0; });
      tbody.appendChild(clone);
      // re-init end-date toggles
      if (tableId==='eduTable') toggleEduEnd(clone);
      if (tableId==='expTable') toggleExpEnd(clone);
      // scroll into view smoothly
      try { clone.scrollIntoView({behavior:'smooth', block:'nearest'}); } catch(_) {}
    }
    function delRow(btn){
      const tr = btn.closest('tr');
      const tbody = tr.parentNode;
      if (tbody.children.length <= 1) return;
      tbody.removeChild(tr);
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

    // ---- Step slide animations (from patched rireki_form.js; this is a light fallback if file missing) ----
    (function(){
      if (window.__RIREKI_STEPS_READY__) return; // if external JS already initialized
      const panes = Array.from(document.querySelectorAll('.step-pane'));
      if (!panes.length) return;
      let idx = panes.findIndex(p => p.classList.contains('is-active')); if (idx<0){ idx=0; panes[0].classList.add('is-active'); }
      function goStep(next, dir){
        if (next===idx || next<0 || next>=panes.length) return;
        const from = panes[idx], to = panes[next];
        from.classList.add('is-transitioning'); to.classList.add('is-transitioning','is-active');
        const enter = dir==='back' ? 'step-enter-left':'step-enter-right';
        const exit  = dir==='back' ? 'step-exit-right':'step-exit-left';
        to.classList.add(enter); from.classList.add(exit);
        try{ window.scrollTo({top:0,behavior:'smooth'}); }catch(_){ window.scrollTo(0,0); }
        const cleanup=()=>{ from.classList.remove('is-active','is-transitioning','step-exit-left','step-exit-right'); to.classList.remove('is-transitioning','step-enter-left','step-enter-right'); idx=next; };
        const onEnd=(e)=>{ if(e.target!==to) return; to.removeEventListener('animationend',onEnd); cleanup(); };
        to.addEventListener('animationend', onEnd, {once:true}); setTimeout(cleanup,500);
      }
      document.addEventListener('click', (e)=>{
        const next=e.target.closest('.js-next-step'); const prev=e.target.closest('.js-prev-step');
        if (next){ e.preventDefault(); goStep(idx+1,'forward'); }
        if (prev){ e.preventDefault(); goStep(idx-1,'back'); }
      });
    })();
  </script>
</body>
</html>
