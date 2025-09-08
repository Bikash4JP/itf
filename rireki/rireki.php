<?php
?><!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <title>履歴書 作成フォーム</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="./css/rireki_form.css">
</head>
<body>
  <div class="container">
    <header class="topbar">
      <h1>履歴書 作成フォーム</h1>
      <div class="stepbar" id="stepbar" aria-label="Progress">
        <div class="step is-active" data-step="1">基本情報</div>
        <div class="step" data-step="2">学歴</div>
        <div class="step" data-step="3">職歴</div>
        <div class="step" data-step="4">資格</div>
        <div class="step" data-step="5">自己PR・希望</div>
      </div>
    </header>

    <form id="rireki-form" class="card" action="./php/submit_rireki.php" method="post" enctype="multipart/form-data" novalidate>
      <!-- STEP 1: Basic -->
      <section class="step-pane is-active" data-step="1">
        <h2>基本情報</h2>

        <div class="grid two">
          <label class="field">
            <span class="label">氏名（ふりがな）</span>
            <input type="text" name="personal_name_kana" id="personal_name_kana" placeholder="やまだ たろう" required>
          </label>

          <label class="field">
            <span class="label">氏名（漢字）</span>
            <input type="text" name="personal_name_kanji" id="personal_name_kanji" placeholder="山田 太郎" required>
          </label>
        </div>

        <div class="grid two">
          <label class="field">
            <span class="label">生年月日（YYYY/MM/DD）</span>
            <input type="text" name="dob" id="dob" inputmode="numeric" placeholder="1998/04/01" maxlength="10" class="date-ymd" required>
            <!-- hidden fields for backend -->
            <input type="hidden" name="dob_yyyy" id="dob_yyyy">
            <input type="hidden" name="dob_mm" id="dob_mm">
            <input type="hidden" name="dob_dd" id="dob_dd">
          </label>

          <label class="field">
            <span class="label">年齢</span>
            <input type="number" inputmode="numeric" name="age" id="age" placeholder="自動計算" readonly>
          </label>
        </div>

        <div class="grid three">
          <fieldset class="field">
            <span class="label">性別</span>
            <div class="inline">
              <label><input type="radio" name="gender" value="男"> 男</label>
              <label><input type="radio" name="gender" value="女"> 女</label>
              <label><input type="radio" name="gender" value="その他"> その他</label>
            </div>
          </fieldset>

          <label class="field">
            <span class="label">電話番号</span>
            <input type="tel" name="phone" id="phone" placeholder="090-0000-0000">
          </label>

          <label class="field">
            <span class="label">メール</span>
            <input type="email" name="email" id="email" placeholder="user@example.com">
          </label>
        </div>

        <div class="grid three">
          <label class="field">
            <span class="label">住所（ふりがな）</span>
            <input type="text" name="address_kana" id="address_kana" placeholder="とうきょうと...">
          </label>
          <label class="field">
            <span class="label">郵便番号</span>
            <input type="text" name="postcode" id="postcode" placeholder="123-4567" pattern="^\d{3}-?\d{4}$">
          </label>
          <label class="field">
            <span class="label">住所</span>
            <input type="text" name="address_full" id="address_full" placeholder="東京都千代田区1-2-3">
          </label>
        </div>

        <div class="grid two">
          <label class="field">
            <span class="label">顔写真（JPEG/PNG）</span>
            <input type="file" name="photo" id="photo" accept="image/jpeg,image/png">
            <small class="hint">最大 5MB</small>
          </label>
          <div class="photo-preview">
            <img id="photoPreviewImg" alt="プレビュー" hidden>
          </div>
        </div>

        <div class="nav">
          <button type="button" class="btn next">次へ</button>
        </div>
      </section>

      <!-- STEP 2: Education -->
      <section class="step-pane" data-step="2">
        <h2>学歴</h2>
        <table class="grid-table" id="eduTable" data-min="1">
          <thead>
            <tr><th style="width:12rem;">年月 (YYYY/MM)</th><th>学校名 / 学部等</th><th style="width:6rem;"></th></tr>
          </thead>
          <tbody id="eduBody">
            <tr>
              <td>
                <input type="text" class="date-ym" name="edu_date[]" placeholder="2018/04" inputmode="numeric" maxlength="7">
                <input type="hidden" name="edu_year[]">
                <input type="hidden" name="edu_month[]">
              </td>
              <td><input type="text" name="edu_school[]" placeholder="〇〇高校 入学"></td>
              <td><button type="button" class="btn danger row-del" aria-label="行を削除">－</button></td>
            </tr>
          </tbody>
        </table>
        <div class="toolbar">
          <button type="button" class="btn secondary row-add" data-target="#eduBody">＋ 行を追加</button>
        </div>
        <div class="nav">
          <button type="button" class="btn prev">戻る</button>
          <button type="button" class="btn next">次へ</button>
        </div>
      </section>

      <!-- STEP 3: Experience -->
      <section class="step-pane" data-step="3">
        <h2>職歴</h2>
        <table class="grid-table" id="expTable" data-min="0">
          <thead>
            <tr><th style="width:12rem;">年月 (YYYY/MM)</th><th>会社名</th><th>役職 / 職種</th><th style="width:6rem;"></th></tr>
          </thead>
          <tbody id="expBody">
            <tr>
              <td>
                <input type="text" class="date-ym" name="exp_date[]" placeholder="2021/04" inputmode="numeric" maxlength="7">
                <input type="hidden" name="exp_year[]">
                <input type="hidden" name="exp_month[]">
              </td>
              <td><input type="text" name="exp_company[]" placeholder="ABC株式会社"></td>
              <td><input type="text" name="exp_title[]" placeholder="エンジニア"></td>
              <td><button type="button" class="btn danger row-del" aria-label="行を削除">－</button></td>
            </tr>
          </tbody>
        </table>
        <div class="toolbar">
          <button type="button" class="btn secondary row-add" data-target="#expBody">＋ 行を追加</button>
        </div>
        <div class="nav">
          <button type="button" class="btn prev">戻る</button>
          <button type="button" class="btn next">次へ</button>
        </div>
      </section>

      <!-- STEP 4: Licenses -->
      <section class="step-pane" data-step="4">
        <h2>資格・免許</h2>
        <table class="grid-table" id="licTable" data-min="0">
          <thead>
            <tr><th style="width:12rem;">年月 (YYYY/MM)</th><th>資格 / 免許名</th><th style="width:6rem;"></th></tr>
          </thead>
          <tbody id="licBody">
            <tr>
              <td>
                <input type="text" class="date-ym" name="lic_date[]" placeholder="2020/12" inputmode="numeric" maxlength="7">
                <input type="hidden" name="lic_year[]">
                <input type="hidden" name="lic_month[]">
              </td>
              <td><input type="text" name="lic_name[]" placeholder="基本情報技術者"></td>
              <td><button type="button" class="btn danger row-del" aria-label="行を削除">－</button></td>
            </tr>
          </tbody>
        </table>
        <div class="toolbar">
          <button type="button" class="btn secondary row-add" data-target="#licBody">＋ 行を追加</button>
        </div>
        <div class="nav">
          <button type="button" class="btn prev">戻る</button>
          <button type="button" class="btn next">次へ</button>
        </div>
      </section>

      <!-- STEP 5: PR/Hopes -->
      <section class="step-pane" data-step="5">
        <h2>自己PR・希望</h2>
        <label class="field">
          <span class="label">自己PR</span>
          <textarea name="self_pr" id="self_pr" rows="5" placeholder="志望の動機、特技、アピールポイントなど"></textarea>
        </label>
        <label class="field">
          <span class="label">本人希望記入欄</span>
          <textarea name="hopes" id="hopes" rows="5" placeholder="給料・職種・勤務時間・勤務地などの希望があれば記入"></textarea>
        </label>

        <div class="nav">
          <button type="button" class="btn prev">戻る</button>
          <button type="submit" class="btn primary">履歴書を作成</button>
        </div>
      </section>
    </form>
  </div>

  <script src="./js/rireki_form.js"></script>
</body>
</html>
