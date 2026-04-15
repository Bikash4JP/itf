<?php
// /home/it-future/www/itf/rireki/basic/rireki.php

// --- Auth guard: must be logged in to access the form ---
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/php/user_auth.php';
$pdo = app_pdo();
app_ensure_tables($pdo);

if (!app_is_logged_in()) {
  header('Location: /php/user_login.php?next=' . urlencode($_SERVER['REQUEST_URI']), true, 302);
  exit;
}
?><!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <title>履歴書フォーム（Basic）</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- keep your existing base css -->
  <link rel="stylesheet" href="/rireki/basic/css/recruit.css?v=5">

  <style>
    :root{
      --sky:#1e90ff;
      --ink:#0b0f19;
      --muted:#475467;
      --bd:#e6edf6;
      --card:#fff;
      --radius:12px;
    }

    body{ margin:0; font-family:ui-sans-serif,system-ui,"Noto Sans JP",Meiryo,Arial; background:#f6fbff; color:var(--ink); }
    .wrap{ max-width:1200px; margin:0 auto; padding:0 14px; }

    .appbar{ padding:16px 0; border-bottom:1px solid #eef2f7; background:#fff; position:sticky; top:0; z-index:10; }
    .appbar .wrap{ display:flex; align-items:center; gap:12px; }
    .appbar h1{ font-size:18px; margin:0; }
    .appbar .home{ margin-left:auto; text-decoration:none; color:#0b6b4a; }

    .card{ background:var(--card); border:1px solid var(--bd); border-radius:var(--radius); padding:18px; margin:16px 0; }
    .steps, .step-pane{ min-height:auto !important; }
    .step-pane{ margin-bottom:10px; }

    .grid-2{ display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .col-2{ grid-column:1/-1; }

    label{ display:flex; flex-direction:column; gap:6px; min-width:0; }
    input,select,textarea{ width:100%; max-width:100%; }
    textarea{ resize:vertical; }

    .section-title{ font-weight:800; margin:4px 0; }
    .req{ color:#e11d48; font-weight:900; margin-left:4px; }
    .hint{ display:inline-block; margin-top:4px; color:#64748b; font-size:12px; }
    .lbl{ display:inline-flex; align-items:baseline; gap:0; }

    .nav{ display:flex; gap:12px; justify-content:flex-end; margin-top:12px; margin-bottom:5px; }
    .btn{
      appearance:none; cursor:pointer;
      border-radius:10px; padding:10px 14px;
      border:1px solid #bfe2ff;
      background:#f3f9ff; color:#0c4a7a;
      font-weight:800; text-decoration:none;
    }
    .btn.primary{
      background:linear-gradient(180deg,#39a7ff,#1e90ff);
      color:#fff; border-color:#39a7ff;
    }
    .btn[disabled]{ opacity:.7; cursor:not-allowed; }

    /* Progress Bar */
    #progressWrap{ max-width:1100px; margin:16px auto 0; padding:0 20px; }
    #progressWrap .progress-labels{
      list-style:none; margin:0 0 6px 0; padding:0;
      display:flex; align-items:center; justify-content:space-between;
    }
    #progressWrap .progress-labels li{
      font-weight:900; font-size:14px; letter-spacing:.2px;
      color:#111; opacity:.75; user-select:none;
    }
    #progressWrap .progress-labels li.is-active{ opacity:1; }
    #progressWrap .progress-labels li.is-dim{ opacity:.45; }
    #progressWrap .progress-labels li.is-done{ opacity:1; }
    #progressWrap .progress-track{
      height:10px; border:1px solid #000; border-radius:5px;
      overflow:hidden; background:#fff;
      box-shadow:inset 0 1px 0 rgba(0,0,0,.15);
    }
    #progressWrap .progress-fill{
      height:100%; width:0%;
      background:var(--sky);
      transition:width .35s ease;
    }

    /* Tables */
    .table{ width:100%; border-collapse:separate; border-spacing:0 8px; }
    .table th{ font-size:12px; color:#475467; text-align:left; white-space:nowrap; }
    .table td input, .table td select, .table td textarea{ width:100%; }
    .row-add,.row-del{ padding:6px 10px; border-radius:6px; border:1px solid #dbe7f5; background:#f3f9ff; cursor:pointer; }

    .photo-preview{ border:1px dashed #cbd5e1; border-radius:8px; padding:8px; display:none; align-items:center; justify-content:center; min-height:120px; }
    .photo-preview img{ max-width:160px; height:auto; display:block; }

    /* AI buttons row */
    .ai-row{ display:flex; gap:8px; align-items:center; margin-top:6px; }

    /* responsive */
    @media (max-width:900px){
      .grid-2{ grid-template-columns:1fr; }
    }

    /* Mobile stacked table (needs td[data-label]) */
    @media (max-width:760px){
      .table thead{ display:none; }
      .table,.table tbody,.table tr,.table td{ display:block; width:100%; }
      .table tr{
        background:#fff; border:1px solid var(--bd); border-radius:12px;
        padding:12px; margin:0 0 12px 0;
      }
      .table td{
        padding:10px 0;
        border-bottom:1px dashed #eef2f7;
        display:flex; flex-direction:column; gap:6px;
      }
      .table td:last-child{ border-bottom:none; }
      .table td::before{
        content: attr(data-label);
        font-size:12px; font-weight:900; color:#475467; line-height:1.2;
      }
      .table td[data-label="操作"]{
        flex-direction:row; align-items:center; gap:10px; flex-wrap:wrap;
      }
    }
  </style>
</head>

<body>
  <div class="appbar">
    <div class="wrap">
      <h1>履歴書フォーム（Basic）</h1>
      <a class="home" href="/rireki/index.php">← フォーマット選択へ</a>
    </div>
  </div>

  <!-- Progress Bar (5 steps + done) -->
  <div id="progressWrap">
    <ul class="progress-labels">
      <li data-step="1">基本情報</li>
      <li data-step="2">学歴</li>
      <li data-step="3">職歴</li>
      <li data-step="4">資格</li>
      <li data-step="5">自己PR・希望</li>
      <li data-step="6" id="labelDone">作成終了</li>
    </ul>
    <div class="progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-label="入力進捗">
      <div id="progressFill" class="progress-fill" aria-valuenow="0"></div>
    </div>
  </div>

  <div class="wrap">
    <form class="card"
          action="/rireki/basic/php/rireki_preview.php"
          method="post"
          enctype="multipart/form-data"
          id="rirekiForm"
          novalidate>

      <input type="hidden" name="__fmt" value="basic">
      <input type="hidden" name="__active_step" id="__active_step" value="1">

      <div class="steps">

        <!-- STEP 1 -->
        <section class="step-pane is-active" data-step="1" id="step-1">
          <h2>STEP 1：基本情報</h2>

          <div class="grid-2">
            <label>
              <span class="lbl">フリガナ<span class="req">*</span></span>
              <input type="text" name="personal_name_kana" id="personal_name_kana" placeholder="ヤマダ タロウ" required>
              <small class="hint">※ カタカナ推奨</small>
            </label>

            <label>
              <span class="lbl">氏名<span class="req">*</span></span>
              <input type="text" name="personal_name_kanji" id="personal_name_kanji" placeholder="山田 太郎" required>
            </label>

            <label class="col-2">
              <span class="lbl">生年月日（YYYY/MM/DD）<span class="req">*</span></span>
              <input type="text" name="dob" id="dob" placeholder="1995/01/31" inputmode="numeric" required>
              <!-- hidden mapped fields -->
              <input type="hidden" name="dob_yyyy" id="dob_yyyy">
              <input type="hidden" name="dob_mm" id="dob_mm">
              <input type="hidden" name="dob_dd" id="dob_dd">
              <small class="hint">※ 数字入力だけでもOK（19950131 → 1995/01/31）</small>
            </label>

            <label>
              <span class="lbl">年齢（自動）</span>
              <input type="number" name="age" id="age" readonly>
            </label>

            <label>
              <span class="lbl">性別<span class="req">*</span></span>
              <select name="gender" id="gender" required>
                <option value=""></option>
                <option>男性</option>
                <option>女性</option>
              </select>
            </label>

            <label>
              <span class="lbl">住所（フリガナ）</span>
              <input type="text" name="address_kana" id="address_kana" placeholder="カナガワケン ヨコハマシ…">
            </label>

            <label>
              <span class="lbl">郵便番号<span class="req">*</span></span>
              <input type="text" name="postcode" id="postcode" placeholder="123-4567" inputmode="numeric" required pattern="\d{3}-?\d{4}">
            </label>

            <label class="col-2">
              <span class="lbl">住所<span class="req">*</span></span>
              <input type="text" name="address_full" id="address_full" placeholder="神奈川県横浜市…" required autocomplete="street-address">
            </label>

            <label>
              <span class="lbl">電話番号<span class="req">*</span></span>
              <input type="tel" name="phone" id="phone" placeholder="090-0000-0000" required inputmode="tel" autocomplete="tel">
            </label>

            <label>
              <span class="lbl">Eメール<span class="req">*</span></span>
              <input type="email" name="email" id="email" placeholder="taro@example.com" required autocomplete="email">
            </label>

            <label class="col-2">
              <span class="lbl">写真（任意）</span>
              <div class="photo-preview" id="photoPreview"><img id="photoPreviewImg" alt="preview"></div>
              <input type="file" name="photo" id="photo" accept="image/jpeg,image/png">
              <small class="hint">※ JPEG/PNG（任意）。Excelに貼り付けます。</small>
            </label>
          </div>

          <div class="nav">
            <button class="btn primary js-next-step" type="button">次へ</button>
          </div>
        </section>

        <!-- STEP 2 -->
        <section class="step-pane" data-step="2" id="step-2">
          <h2>STEP 2：学歴</h2>

          <table class="table" id="eduTable">
            <thead>
              <tr>
                <th>開始年</th><th>開始月</th>
                <th>学校名</th><th>学部・学科</th>
                <th>区分</th><th>在学状況</th>
                <th>終了年</th><th>終了月</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              <tr class="edu-row">
                <td><input type="text" name="edu_start_year[]" placeholder="YYYY" inputmode="numeric"></td>
                <td><input type="text" name="edu_start_month[]" placeholder="MM" inputmode="numeric"></td>
                <td><input type="text" name="edu_school_name[]" placeholder="〇〇大学 / 〇〇高校"></td>
                <td><input type="text" name="edu_faculty[]" placeholder="情報学部 など"></td>
                <td>
                  <select name="edu_level[]">
                    <option value=""></option>
                    <option>高校</option>
                    <option>専門学校</option>
                    <option>短大</option>
                    <option>大学</option>
                    <option>大学院</option>
                  </select>
                </td>
                <td>
                  <select name="edu_status[]" class="js-status-edu">
                    <option value=""></option>
                    <option>在学中</option>
                    <option>卒業</option>
                    <option>退学</option>
                  </select>
                </td>
                <td><input class="js-edu-end-y" type="text" name="edu_end_year[]" placeholder="YYYY" inputmode="numeric"></td>
                <td><input class="js-edu-end-m" type="text" name="edu_end_month[]" placeholder="MM" inputmode="numeric"></td>
                <td>
                  <button type="button" class="row-add" data-for="edu">＋</button>
                  <button type="button" class="row-del">−</button>
                </td>
              </tr>
            </tbody>
          </table>

          <div class="nav">
            <button class="btn js-prev-step" type="button">戻る</button>
            <button class="btn primary js-next-step" type="button">次へ</button>
          </div>
        </section>

        <!-- STEP 3 -->
        <section class="step-pane" data-step="3" id="step-3">
          <h2>STEP 3：職歴</h2>

          <table class="table" id="expTable">
            <thead>
              <tr>
                <th>開始年</th><th>開始月</th>
                <th>会社名</th><th>役職/職種</th>
                <th>在職状況</th>
                <th>終了年</th><th>終了月</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              <tr class="exp-row">
                <td><input type="text" name="exp_start_year[]" placeholder="YYYY" inputmode="numeric"></td>
                <td><input type="text" name="exp_start_month[]" placeholder="MM" inputmode="numeric"></td>
                <td><input type="text" name="exp_company[]" placeholder="〇〇株式会社"></td>
                <td><input type="text" name="exp_title[]" placeholder="Webエンジニア など"></td>
                <td>
                  <select name="exp_status[]" class="js-status-exp">
                    <option value=""></option>
                    <option>在職中</option>
                    <option>退職</option>
                  </select>
                </td>
                <td><input class="js-exp-end-y" type="text" name="exp_end_year[]" placeholder="YYYY" inputmode="numeric"></td>
                <td><input class="js-exp-end-m" type="text" name="exp_end_month[]" placeholder="MM" inputmode="numeric"></td>
                <td>
                  <button type="button" class="row-add" data-for="exp">＋</button>
                  <button type="button" class="row-del">−</button>
                </td>
              </tr>
            </tbody>
          </table>

          <div class="nav">
            <button class="btn js-prev-step" type="button">戻る</button>
            <button class="btn primary js-next-step" type="button">次へ</button>
          </div>
        </section>

        <!-- STEP 4 -->
        <section class="step-pane" data-step="4" id="step-4">
          <h2>STEP 4：資格・免許</h2>

          <table class="table" id="licTable">
            <thead>
              <tr>
                <th>取得年</th><th>取得月</th><th>資格名</th><th>操作</th>
              </tr>
            </thead>
            <tbody>
              <tr class="lic-row">
                <td><input type="text" name="lic_year[]" placeholder="YYYY" inputmode="numeric"></td>
                <td><input type="text" name="lic_month[]" placeholder="MM" inputmode="numeric"></td>
                <td><input type="text" name="lic_name[]" placeholder="例：基本情報技術者 など"></td>
                <td>
                  <button type="button" class="row-add" data-for="lic">＋</button>
                  <button type="button" class="row-del">−</button>
                </td>
              </tr>
            </tbody>
          </table>

          <div class="nav">
            <button class="btn js-prev-step" type="button">戻る</button>
            <button class="btn primary js-next-step" type="button">次へ</button>
          </div>
        </section>

        <!-- STEP 5 -->
        <section class="step-pane" data-step="5" id="step-5">
          <h2>STEP 5：自己PR・希望</h2>

          <div class="grid-2">
            <label class="col-2">
              <span class="lbl">志望動機・自己PRなど<span class="req">*</span></span>
              <textarea name="self_pr" rows="5" id="prText" required
                placeholder="自分の言葉でOK。書き終わったら「AIで整える」を押すと読みやすい日本語にします。"></textarea>
              <div class="ai-row">
                <button type="button" class="btn" data-ai-target="#prText">AIで整える</button>
              </div>
            </label>

            <label class="col-2">
              <span class="lbl">本人希望記入欄<span class="req">*</span></span>
              <textarea name="hopes" rows="5" id="hopesText" required
                placeholder="希望職種、勤務地、給与など（必須/希望を分けると良いです）。"></textarea>
              <div class="ai-row">
                <button type="button" class="btn" data-ai-target="#hopesText">AIで整える</button>
              </div>
            </label>
          </div>

          <div class="nav">
            <button class="btn js-prev-step" type="button">戻る</button>
            <button class="btn primary" type="submit">この内容でプレビューへ</button>
          </div>
        </section>

      </div>
    </form>
  </div>

  <script src="/rireki/basic/js/rireki_form.js?v=5"></script>

  <!-- AI rewrite (same Worker style as Kaigo) -->
  <script>
    (function () {
      const WORKER_URL = 'https://rireki-ai.bikash4jp.workers.dev';

      async function rewriteToSimpleJa(text) {
        const res = await fetch(WORKER_URL, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ task: 'rewrite_to_simple_japanese', text })
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data || !data.ok) throw new Error('AI request failed');
        return (data.output || '').trim();
      }

      document.addEventListener('click', async (e) => {
        const btn = e.target.closest('button[data-ai-target]');
        if (!btn) return;

        const sel = btn.getAttribute('data-ai-target');
        const ta = document.querySelector(sel);
        if (!ta) return;

        const original = (ta.value || '').trim();
        if (!original) { alert('まずテキストを入力してください。'); return; }

        btn.disabled = true;
        const old = btn.textContent;
        btn.textContent = '整え中…';

        try {
          const rewritten = await rewriteToSimpleJa(original);
          if (rewritten) ta.value = rewritten;

          // trigger autosave immediately
          try { ta.dispatchEvent(new Event('input', { bubbles:true })); } catch (_) {}
        } catch (err) {
          console.error(err);
          alert('AIの整形に失敗しました。時間をおいて再度お試しください。');
        } finally {
          btn.disabled = false;
          btn.textContent = old;
        }
      });
    })();
  </script>
</body>
</html>
