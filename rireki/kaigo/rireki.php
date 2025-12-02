<?php // /home/it-future/www/itf/rireki/kaigo/rireki.php
$job_id = isset($_GET['job_id']) ? (int) $_GET['job_id'] : 0;
?>
<!doctype html>
<html lang="ja">

<head>
  <meta charset="utf-8" />
  <title>介護向け 履歴書フォーム</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="/rireki/basic/css/recruit.css?v=5">
  <style>
    /* layout */
    .wrap { max-width: 1200px; margin: 0 auto; padding: 0 14px }
    .appbar { padding: 16px 0; border-bottom: 1px solid #eef2f7; background: #fff; position: sticky; top: 0; z-index: 10 }
    .appbar .wrap { display: flex; align-items: center; gap: 12px }
    .appbar h1 { font-size: 18px; margin: 0 }
    .appbar .home { margin-left: auto; text-decoration: none; color: #0b6b4a }

    .card { background: #fff; border: 1px solid #e6edf6; border-radius: 12px; padding: 18px; margin-top: 16px }
    .card, .steps, .step-pane { min-height: auto !important }
    .step-pane { margin-bottom: 10px }

    .nav { display: flex; gap: 12px; justify-content: flex-end; margin-top: 12px; margin-bottom: 5px }

    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px }
    .col-2 { grid-column: 1/-1 }

    .section-title { font-weight: 700; margin: 4px 0 }
    input.in-yy { max-width: 110px }
    input.in-mm { max-width: 80px }
    input.in-hhmm { max-width: 96px }

    .table { width: 100%; border-collapse: separate; border-spacing: 0 8px }
    .table th { font-size: 12px; color: #475467; text-align: left; white-space: nowrap }
    .table td input, .table td select, .table td textarea { width: 100% }

    .row-add, .row-del { padding: 6px 10px; border-radius: 6px; border: 1px solid #dbe7f5; background: #f3f9ff; cursor: pointer }

    .table.edu col.yy { width: 110px }
    .table.edu col.mm { width: 80px }
    .table.edu col.yy2 { width: 110px }
    .table.edu col.mm2 { width: 80px }
    .table.edu col.status { width: 140px }
    .table.edu col.inst { width: 40% }
    .table.edu col.fac { width: 140px }

    .table.work col.yy { width: 80px }
    .table.work col.mm { width: 60px }
    .table.work col.status { width: 110px }
    .table.work col.yy2 { width: 80px }
    .table.work col.mm2 { width: 60px }
    .table.work col.org { width: 280px }
    .table.work col.title { width: 110px }
    .table.work col.desc { width: 320px }
    .table.work textarea { min-height: 88px }

    .photo-preview { border: 1px dashed #cbd5e1; border-radius: 8px; padding: 8px; display: none; align-items: center; justify-content: center; min-height: 120px }
    .photo-preview img { max-width: 160px; height: auto; display: block }

    .rule-box { margin-top: 14px; border: 1px solid #e5e7eb; background: #fafafa; border-radius: 8px; padding: 12px 14px; color: #64748b; font-size: 12.5px; line-height: 1.5 }
    .rule-box ol { margin: 0 0 0 1.2em; padding: 0 }
    .rule-box li { margin: 3px 0 }
    .rule-box .ban { color: #b91c1c; font-weight: 700 }

    .site-footer {
      position: relative; left: 0; right: 0; bottom: 0; z-index: 5;
      background: #000; color: #fff; text-align: center;
      padding: 10px 12px; font-size: 13px; line-height: 1;
      display: flex; align-items: center; justify-content: center; height: 50px;
    }

    .banner { margin: 10px 0 0 0; font-size: .9rem; color: #0b3772 }

    /* required mark */
    .req { color: #e11d48; font-weight: 900; margin-left: 4px }
    .hint { display: inline-block; margin-top: 4px; color: #64748b; font-size: 12px; }

    /* ✅ label header row (title + *) must stay inline on the same line */
    .lbl { display: inline-flex; align-items: baseline; gap: 0 }

    /* --- Progress Bar (labels + thin bar) --- */
    #progressWrap { max-width: 1100px; margin: 16px auto 0; padding: 0 20px }
    #progressWrap .progress-labels { list-style: none; margin: 0 0 6px 0; padding: 0; display: flex; align-items: center; justify-content: space-between }
    #progressWrap .progress-labels li { font-weight: 800; font-size: 14px; letter-spacing: .5px; color: #111; opacity: .75; user-select: none }
    #progressWrap .progress-labels li.is-active { opacity: 1 }
    #progressWrap .progress-labels li.is-dim { opacity: .45 }
    #progressWrap .progress-track { height: 10px; border: 1px solid #000; border-radius: 5px; overflow: hidden; background: #fff; box-shadow: inset 0 1px 0 rgba(0, 0, 0, .15) }
    #progressWrap .progress-fill { height: 100%; width: 0%; background: #1e90ff; transition: width .35s ease }

    /* Small tweak for AI buttons next to textareas */
    .ai-row { display: flex; gap: 8px; align-items: center; margin-top: 6px }
    .btn[disabled] { opacity: .7; cursor: not-allowed }

    /* =========================
       ✅ Responsive improvements
       ========================= */

    /* force label -> control stacking */
    .grid-2>label, .grid-2 label, label.col-2 { display: flex; flex-direction: column; gap: 6px; min-width: 0 }
    input, select, textarea { width: 100%; max-width: 100% }

    @media (max-width:900px) {
      .grid-2 { grid-template-columns: 1fr }
    }

    @media (max-width:600px) {
      input.in-yy, input.in-mm { max-width: 100% }

      /* ✅ Make YYYY/MM/DD groups also stack (one per line) */
      .grid-3 { grid-template-columns: 1fr }
      input.in-yy, input.in-mm, input.in-hhmm { max-width: 100% }

      .ai-row { flex-direction: column; align-items: stretch; gap: 8px }
      .ai-row .btn { width: 100% }
    }

    /* ✅ Mobile: 学歴/職歴/資格 table -> stacked cards */
    @media (max-width:760px) {
      .table { border-collapse: separate; border-spacing: 0 }
      .table thead { display: none }
      .table, .table tbody, .table tr, .table td { display: block; width: 100% }

      .table tr {
        background: #fff; border: 1px solid #e6edf6; border-radius: 12px;
        padding: 12px; margin: 0 0 12px 0;
      }

      .table td {
        padding: 10px 0;
        border-bottom: 1px dashed #eef2f7;
        display: flex; flex-direction: column; gap: 6px;
      }

      .table td:last-child { border-bottom: none }

      .table td::before {
        content: attr(data-label);
        font-size: 12px; font-weight: 800; color: #475467; line-height: 1.2;
      }

      .table td input, .table td select, .table td textarea { width: 100%; max-width: 100% }

      /* 操作 column */
      .table td[data-label="操作"] { flex-direction: row; align-items: center; gap: 10px; flex-wrap: wrap }
    }
  </style>
</head>

<body>
  <div class="appbar">
    <div class="wrap">
      <h1>介護向け 履歴書フォーム</h1>
      <a class="home" href="/rireki/index.php">← フォーマット選択へ</a>
    </div>
  </div>

  <!-- Progress Bar -->
  <div id="progressWrap">
    <ul class="progress-labels">
      <li data-step="1">個人情報</li>
      <li data-step="2">在留・写真</li>
      <li data-step="3">学歴・免許</li>
      <li data-step="4">職歴</li>
      <li data-step="5">自己PR・志望希望</li>
      <li data-step="6">別途情報</li>
      <li data-step="7" id="labelDone">作成終了</li>
    </ul>
    <div class="progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-label="入力進捗">
      <div id="progressFill" class="progress-fill" aria-valuenow="0"></div>
    </div>
  </div>

  <div class="wrap">
    <form class="card" action="/rireki/kaigo/php/rireki_preview.php" method="post" enctype="multipart/form-data"
      id="rirekiForm" novalidate>
      <input type="hidden" name="__fmt" value="basic">
      <?php if ($job_id > 0): ?>
        <div class="banner">この履歴書は求人応募フローから作成されます（Job ID: <?php echo (int) $job_id; ?>）。</div>
      <?php endif; ?>

      <div class="steps">

        <!-- STEP 1 -->
        <section class="step-pane is-active" d="step-1">
          <h2>基本情報</h2>

          <div class="grid-2">
            <label>
              <span class="lbl">氏名（ローマ字）<span class="req">*</span></span>
              <input
                type="text"
                name="name_romaji"
                id="name_romaji"
                placeholder="TARO YAMADA"
                required
                autocomplete="name"
              >
              <small class="hint">※ 英字は大文字（A-Z）のみ / または漢字で入力してください。</small>
            </label>

            <label>
              <span class="lbl">氏名（カタカナ）<span class="req">*</span></span>
              <input
                type="text"
                name="name_kana"
                id="name_kana"
                placeholder="ヤマダ タロウ"
                required
                autocomplete="name"
              >
              <small class="hint">※ カタカナで入力してください。</small>
            </label>

            <div>
              <div class="section-title">生年月日（YYYY/MM/DD）<span class="req">*</span></div>
              <div class="grid-3">
                <input class="in-yy" type="text" name="dob_year" id="dob_year" placeholder="YYYY" inputmode="numeric" required maxlength="4" pattern="\d{4}">
                <input class="in-mm" type="text" name="dob_month" id="dob_month" placeholder="MM" inputmode="numeric" required maxlength="2" pattern="\d{1,2}">
                <input class="in-mm" type="text" name="dob_day" id="dob_day" placeholder="DD" inputmode="numeric" required maxlength="2" pattern="\d{1,2}">
              </div>
            </div>

            <label>
              <span class="lbl">年齢（自動）</span>
              <input type="number" name="age_autofill" id="age_autofill" readonly>
            </label>

            <label>
              <span class="lbl">出生地<span class="req">*</span></span>
              <input type="text" name="birthplace" placeholder="生まれた市と国を入力" required>
            </label>
            <div></div>

            <label>
              <span class="lbl">郵便番号<span class="req">*</span></span>
              <input type="text" name="postal" id="postal" placeholder="現在の郵便番号を入力" inputmode="numeric" required pattern="\d{3}-?\d{4}">
            </label>

            <label>
              <span class="lbl">現住所<span class="req">*</span></span>
              <input type="text" name="address" placeholder="神奈川県横浜市…" required autocomplete="street-address">
            </label>

            <label>
              <span class="lbl">個人携帯番号<span class="req">*</span></span>
              <input type="tel" name="contact_phone" id="contact_phone" placeholder="090-0000-0000" required inputmode="tel" autocomplete="tel">
              <small class="hint">※ ハイフンあり/なしOK（例：09000000000）</small>
            </label>

            <label>
              <span class="lbl">Eメール<span class="req">*</span></span>
              <input type="email" name="email" placeholder="taro@example.com" required autocomplete="email">
            </label>

            <label>
              <span class="lbl">国籍<span class="req">*</span></span>
              <select name="nationality" required>
                <option value=""></option>
                <option>バングラデシュ</option>
                <option>インドネシア国籍</option>
                <option>ベトナム国籍</option>
                <option>ネパール国籍</option>
                <option>ミャンマー国籍</option>
                <option>ペルー国籍</option>
                <option>中国国籍</option>
                <option>韓国国籍</option>
              </select>
            </label>

            <label>
              <span class="lbl">性別<span class="req">*</span></span>
              <select name="gender" required>
                <option value=""></option>
                <option>男性</option>
                <option>女性</option>
              </select>
            </label>

            <label>
              <span class="lbl">宗教<span class="req">*</span></span>
              <select name="religion" required>
                <option value=""></option>
                <option>イスラム教</option>
                <option>仏教</option>
                <option>キリスト教</option>
                <option>ヒンドゥー教</option>
                <option>無宗教</option>
              </select>
            </label>

            <label>
              <span class="lbl">配偶者の有無<span class="req">*</span></span>
              <select name="marital_status" required>
                <option value=""></option>
                <option>有り（子供あり）</option>
                <option>有り（子供なし）</option>
                <option>無し</option>
              </select>
            </label>

            <label>
              <span class="lbl">身長 (cm)<span class="req">*</span></span>
              <input type="number" name="height_cm" min="0" step="1" required inputmode="numeric">
            </label>

            <label>
              <span class="lbl">体重 (kg)<span class="req">*</span></span>
              <input type="number" name="weight_kg" min="0" step="1" required inputmode="numeric">
            </label>
          </div>

          <div class="rule-box">
            <ol>
              <li>「<strong>要配慮個人情報</strong>」（人種・信条・社会的身分・病歴・犯罪の経歴 等）は<strong>記載しないでください</strong>。</li>
              <li>誤って要配慮情報が含まれている場合、該当箇所は当社にて削除・伏せ字処理を行います。</li>
            </ol>
            <div class="ban">※ 他人の情報入力（採用関連エージェントによる代理入力 など）は禁止です。</div>
          </div>

          <div class="nav">
            <button class="btn primary js-next-step" type="button">次へ</button>
          </div>
        </section>

        <!-- STEP 2 -->
        <section class="step-pane" d="step-2">
          <h2>パスポート・出入国・在留 / 写真</h2>

          <div class="grid-2">
            <label>
              <span class="lbl">パスポート</span>
              <select name="passport_has">
                <option value=""></option>
                <option>有り</option>
                <option>無し</option>
              </select>
            </label>

            <div>
              <div class="section-title">有効期限</div>
              <div class="grid-3">
                <input class="in-yy" type="text" name="passport_exp_year" placeholder="YYYY" inputmode="numeric">
                <input class="in-mm" type="text" name="passport_exp_month" placeholder="MM" inputmode="numeric">
                <input class="in-mm" type="text" name="passport_exp_day" placeholder="DD" inputmode="numeric">
              </div>
            </div>

            <label>
              <span class="lbl">パスポートNO</span>
              <input type="text" name="passport_no">
            </label>
            <div></div>

            <label>
              <span class="lbl">過去の出入国歴（回数）</span>
              <select name="past_travel_count">
                <option>0</option>
                <option>1</option>
                <option>2</option>
                <option>3</option>
              </select>
            </label>

            <label>
              <span class="lbl">詳細（任意）</span>
              <input type="text" name="past_travel_details" placeholder="例：2019/05 日本入国、2021/08 帰国 など">
            </label>

            <div>
              <div class="section-title">直近の入国</div>
              <div class="grid-3">
                <input class="in-yy" type="text" name="recent_entry_year" placeholder="YYYY" inputmode="numeric">
                <input class="in-mm" type="text" name="recent_entry_month" placeholder="MM" inputmode="numeric">
                <input class="in-mm" type="text" name="recent_entry_day" placeholder="DD" inputmode="numeric">
              </div>
            </div>

            <div>
              <div class="section-title">直近の出国</div>
              <div class="grid-3">
                <input class="in-yy" type="text" name="recent_exit_year" placeholder="YYYY" inputmode="numeric">
                <input class="in-mm" type="text" name="recent_exit_month" placeholder="MM" inputmode="numeric">
                <input class="in-mm" type="text" name="recent_exit_day" placeholder="DD" inputmode="numeric">
              </div>
            </div>

            <label>
              <span class="lbl">現在の在留資格</span>
              <select name="current_status">
                <option value=""></option>
                <option>無し</option>
                <option>特定技能</option>
                <option>技能実習</option>
                <option>その他</option>
              </select>
            </label>
            <div></div>

            <div>
              <div class="section-title">在留期限（開始）</div>
              <div class="grid-3">
                <input class="in-yy" type="text" name="status_from_year" placeholder="YYYY" inputmode="numeric">
                <input class="in-mm" type="text" name="status_from_month" placeholder="MM" inputmode="numeric">
                <input class="in-mm" type="text" name="status_from_day" placeholder="DD" inputmode="numeric">
              </div>
            </div>

            <div>
              <div class="section-title">在留期限（終了）</div>
              <div class="grid-3">
                <input class="in-yy" type="text" name="status_to_year" placeholder="YYYY" inputmode="numeric">
                <input class="in-mm" type="text" name="status_to_month" placeholder="MM" inputmode="numeric">
                <input class="in-mm" type="text" name="status_to_day" placeholder="DD" inputmode="numeric">
              </div>
            </div>

            <label class="col-2">
              <span class="lbl">証明写真をアップロード<span class="req">*</span></span>
              <div class="photo-preview" id="photoPreview"><img id="photoPreviewImg" alt="preview"></div>
              <input type="file" name="photo" id="photo" accept="image/jpeg,image/png" required>
              <small class="hint">※ JPEG/PNG（必須）</small>
            </label>
          </div>

          <div class="rule-box">
            <ol>
              <li>旅券番号・在留カード番号の公開は控えてください（社内管理用途のみ記入）。</li>
              <li>証明写真の要件：
                <ol>
                  <li>両耳・肩が見えるように撮影してください。</li>
                  <li>目を開け、正面を向いたもの（サングラス・帽子不可）。</li>
                  <li><strong>3か月以内</strong>に撮影した鮮明な写真。</li>
                  <li>背景は無地・明るめ（白/薄色推奨）。</li>
                </ol>
              </li>
            </ol>
            <div class="ban">※ 条件を満たさない写真は差し替えをお願いする場合があります。</div>
          </div>

          <div class="nav">
            <button class="btn js-prev-step" type="button">戻る</button>
            <button class="btn primary js-next-step" type="button">次へ</button>
          </div>
        </section>

        <!-- STEP 3 -->
        <section class="step-pane" d="step-3">
          <h2>学歴</h2>
          <table class="table edu" id="eduTable">
            <colgroup>
              <col class="yy">
              <col class="mm">
              <col class="yy2">
              <col class="mm2">
              <col class="status">
              <col class="inst">
              <col class="fac">
              <col class="ops">
            </colgroup>
            <thead>
              <tr>
                <th>開始年</th>
                <th>開始月</th>
                <th>終了年</th>
                <th>終了月</th>
                <th>在学状況</th>
                <th>学校名</th>
                <th>学部・専攻</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              <tr class="edu-row">
                <td><input class="in-yy" type="text" name="education[from_year][]" placeholder="YYYY" inputmode="numeric"></td>
                <td><input class="in-mm" type="text" name="education[from_month][]" placeholder="MM" inputmode="numeric"></td>
                <td><input class="in-yy js-edu-end-y" type="text" name="education[to_year][]" placeholder="YYYY" inputmode="numeric"></td>
                <td><input class="in-mm js-edu-end-m" type="text" name="education[to_month][]" placeholder="MM" inputmode="numeric"></td>
                <td>
                  <select name="education[status][]" class="js-status-edu">
                    <option value=""></option>
                    <option>在学中</option>
                    <option>卒業</option>
                    <option>退学</option>
                  </select>
                </td>
                <td><input type="text" name="education[institution][]" placeholder="△△大学 / ○○高校"></td>
                <td><input type="text" name="education[faculty][]" placeholder="情報学部 など"></td>
                <td>
                  <button type="button" class="row-add" data-for="edu">＋</button>
                  <button type="button" class="row-del">−</button>
                </td>
              </tr>
            </tbody>
          </table>

          <h2 style="margin-top:18px;">免許・資格</h2>
          <table class="table" id="licTable">
            <thead>
              <tr>
                <th>取得年</th>
                <th>取得月</th>
                <th>資格名</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              <tr class="lic-row">
                <td><input class="in-yy" type="text" name="licenses[cert_year][]" placeholder="YYYY" inputmode="numeric"></td>
                <td><input class="in-mm" type="text" name="licenses[cert_month][]" placeholder="MM" inputmode="numeric"></td>
                <td><input type="text" name="licenses[cert_name][]" placeholder="介護職員初任者研修 など"></td>
                <td>
                  <button type="button" class="row-add" data-for="lic">＋</button>
                  <button type="button" class="row-del">−</button>
                </td>
              </tr>
            </tbody>
          </table>

          <div class="rule-box">
            <ol>
              <li>学歴・資格は<strong>最新から順</strong>にご入力ください。</li>
              <li>正式名称で記入（例：× 初任者、○ 介護職員初任者研修）。</li>
            </ol>
            <div class="ban">※ 架空の資格・在籍は厳禁です。確認できない場合は不採用となることがあります。</div>
          </div>

          <div class="nav">
            <button class="btn js-prev-step" type="button">戻る</button>
            <button class="btn primary js-next-step" type="button">次へ</button>
          </div>
        </section>

        <!-- STEP 4 -->
        <section class="step-pane" d="step-4">
          <h2>職歴</h2>
          <table class="table work" id="expTable">
            <colgroup>
              <col class="yy">
              <col class="mm">
              <col class="status">
              <col class="yy2">
              <col class="mm2">
              <col class="org">
              <col class="title">
              <col class="desc">
              <col class="ops">
            </colgroup>
            <thead>
              <tr>
                <th>開始年</th>
                <th>開始月</th>
                <th>在職状況</th>
                <th>終了年</th>
                <th>終了月</th>
                <th>会社・施設名</th>
                <th>職種/役職</th>
                <th>仕事内容</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              <tr class="exp-row">
                <td><input class="in-yy" type="text" name="work_blocks[from_year][]" placeholder="YYYY" inputmode="numeric"></td>
                <td><input class="in-mm" type="text" name="work_blocks[from_month][]" placeholder="MM" inputmode="numeric"></td>
                <td>
                  <select name="work_blocks[status][]" class="js-status-exp">
                    <option value=""></option>
                    <option>在職中</option>
                    <option>退職</option>
                  </select>
                </td>
                <td><input class="in-yy js-exp-end-y" type="text" name="work_blocks[to_year][]" placeholder="YYYY" inputmode="numeric"></td>
                <td><input class="in-mm js-exp-end-m" type="text" name="work_blocks[to_month][]" placeholder="MM" inputmode="numeric"></td>
                <td><input type="text" name="work_blocks[org][]" placeholder="特養 / 老健 / 企業名"></td>
                <td><input type="text" name="work_blocks[job_title][]" placeholder="介護職 / 生活相談員 / 看護助手 等"></td>
                <td><textarea name="work_blocks[description][]" rows="4" placeholder="主な業務内容・担当・実績などを簡潔に（〜100語目安）"></textarea></td>
                <td>
                  <button type="button" class="row-add" data-for="exp">＋</button>
                  <button type="button" class="row-del">−</button>
                </td>
              </tr>
            </tbody>
          </table>

          <label class="col-2" style="margin-top:10px;">
            <span class="lbl">退職理由について（対象者のみ）</span>
            <textarea name="reason_for_resignation" rows="3" placeholder="退職済みの場合のみ入力"></textarea>
          </label>

          <label class="col-2" style="margin-top:10px;">
            <span class="lbl">退職日予定（対象者のみ）</span>
            <div class="grid-3" style="max-width:420px">
              <input class="in-yy" type="text" name="planned_resign_year" placeholder="YYYY" inputmode="numeric">
              <input class="in-mm" type="text" name="planned_resign_month" placeholder="MM" inputmode="numeric">
              <div></div>
            </div>
          </label>

          <div class="rule-box">
            <ol>
              <li>「仕事内容」には、担当フロア・介助件数・シフト帯など、わかる範囲で具体的に入力してください。</li>
              <li>退職理由は採用判断の参考にします。誹謗中傷の記載はお控えください。</li>
            </ol>
            <div class="ban">※ 経歴詐称が判明した場合は選考見送りとなります。</div>
          </div>

          <div class="nav">
            <button class="btn js-prev-step" type="button">戻る</button>
            <button class="btn primary js-next-step" type="button">次へ</button>
          </div>
        </section>

        <!-- STEP 5 (AI wired) -->
        <section class="step-pane" d="step-5">
          <h2>自己PR・志望・希望</h2>

          <div class="grid-2">
            <label class="col-2">
              <span class="lbl">自己PR</span>
              <textarea name="self_pr" rows="4" id="prText"
                placeholder="自分の言葉でOK（母国語でもOK）です。できるだけ書いてみてください。書き終わったら「AIで整える」を押すと、読みやすい日本語に整えます。"></textarea>
              <div class="ai-row">
                <button type="button" class="btn" data-ai-target="#prText">AIで整える</button>
              </div>
            </label>

            <label class="col-2">
              <span class="lbl">志望の動機</span>
              <textarea name="motivation" rows="4" id="motivationText"
                placeholder="自分の言葉でOK（母国語でもOK）です。できるだけ書いてみてください。書き終わったら「AIで整える」を押すと、読みやすい日本語に整えます。"></textarea>
              <div class="ai-row">
                <button type="button" class="btn" data-ai-target="#motivationText">AIで整える</button>
              </div>
            </label>

            <label class="col-2">
              <span class="lbl">本人希望欄（職種、給与、勤務地など）</span>
              <textarea name="preferences" rows="4" id="prefText"
                placeholder="自分の言葉でOK（母国語でもOK）です。できるだけ書いてみてください。書き終わったら「AIで整える」を押すと、読みやすい日本語に整えます。"></textarea>
              <div class="ai-row">
                <button type="button" class="btn" data-ai-target="#prefText">AIで整える</button>
              </div>
            </label>
          </div>

          <div class="rule-box">
            <ol>
              <li>具体的な経験や強みは<strong>数字</strong>を交えて簡潔に（例：利用者15名の入浴介助）。</li>
              <li>希望条件は「必須 / 望ましい」を分けて書くと伝わりやすいです。</li>
            </ol>
            <div class="ban">※ 個人・施設名への誹謗中傷は記載しないでください。</div>
          </div>

          <div class="nav">
            <button class="btn js-prev-step" type="button">戻る</button>
            <button class="btn primary js-next-step" type="button">次へ</button>
          </div>
        </section>

        <!-- STEP 6 -->
        <section class="step-pane" d="step-6">
          <h2>別途情報</h2>
          <div class="grid-2">
            <label>
              <span class="lbl">日本語コミュニケーション</span>
              <select name="jp_comm_level">
                <option value=""></option>
                <option>N1相当</option>
                <option>N2相当</option>
                <option>N3相当</option>
                <option>N4相当</option>
              </select>
            </label>

            <label>
              <span class="lbl">漢字読み書き</span>
              <select name="kanji_rw">
                <option value=""></option>
                <option>できる</option>
                <option>漢字が苦手</option>
                <option>ひらがなならOK</option>
                <option>まだまだ勉強</option>
              </select>
            </label>

            <label>
              <span class="lbl">血液型</span>
              <select name="blood_type">
                <option value=""></option>
                <option>A</option>
                <option>B</option>
                <option>O</option>
                <option>AB</option>
                <option>わからない</option>
              </select>
            </label>

            <label>
              <span class="lbl">英語について</span>
              <select name="english_level">
                <option value=""></option>
                <option>可能</option>
                <option>日常会話OK</option>
                <option>不可</option>
              </select>
            </label>

            <label>
              <span class="lbl">日本に知り合い</span>
              <select name="acquaintances_in_japan">
                <option value=""></option>
                <option>あり</option>
                <option>なし</option>
              </select>
            </label>

            <label>
              <span class="lbl">日本人の友達</span>
              <select name="jp_friends_count">
                <option value=""></option>
                <option>0名</option>
                <option>1名</option>
                <option>2名</option>
                <option>3名</option>
                <option>4名</option>
                <option>5名</option>
              </select>
            </label>

            <label>
              <span class="lbl">母国の友達（日本に）</span>
              <select name="home_country_friends_in_japan">
                <option value=""></option>
                <option>0名</option>
                <option>1名</option>
                <option>2名</option>
                <option>3名</option>
                <option>4名</option>
                <option>5名</option>
                <option>6名</option>
                <option>7名</option>
                <option>8名</option>
                <option>9名</option>
                <option>10名</option>
              </select>
            </label>

            <label>
              <span class="lbl">タバコ</span>
              <select name="smoking">
                <option value=""></option>
                <option>吸う</option>
                <option>吸わない</option>
              </select>
            </label>

            <label>
              <span class="lbl">お酒</span>
              <select name="alcohol">
                <option value=""></option>
                <option>飲む</option>
                <option>飲まない</option>
              </select>
            </label>

            <label>
              <span class="lbl">刺青</span>
              <select name="tattoo">
                <option value=""></option>
                <option>あり</option>
                <option>なし</option>
              </select>
            </label>

            <label>
              <span class="lbl">服のサイズ</span>
              <select name="clothes_size">
                <option value=""></option>
                <option>SS</option>
                <option>S</option>
                <option>M</option>
                <option>L</option>
                <option>LL</option>
                <option>XL</option>
              </select>
            </label>

            <label>
              <span class="lbl">靴のサイズ</span>
              <select name="shoe_size">
                <option value=""></option>
                <option>21.0cm(EUR32)</option>
                <option>21.5cm(EUR33)</option>
                <option>22.0cm(EUR34)</option>
                <option>22.5cm(EUR35)</option>
                <option>23.0cm(EUR36)</option>
                <option>23.5cm(EUR37)</option>
                <option>24.0cm(EUR38)</option>
                <option>24.5cm(EUR39)</option>
                <option>25.0cm(EUR40)</option>
                <option>25.5cm(EUR41)</option>
                <option>26.0cm(EUR42)</option>
                <option>26.5cm(EUR43)</option>
                <option>27.0cm(EUR44)</option>
                <option>27.5cm(EUR45)</option>
                <option>28.0cm(EUR46)</option>
                <option>28.5cm(EUR47)</option>
                <option>29.0cm(EUR48)</option>
              </select>
            </label>

            <label>
              <span class="lbl">お祈り</span>
              <select name="prayer">
                <option value=""></option>
                <option>なし</option>
                <option>あり（仕事中はしない)</option>
                <option>あり（休憩中にはしたい)</option>
              </select>
            </label>

            <label>
              <span class="lbl">断食</span>
              <select name="fasting">
                <option value=""></option>
                <option>なし</option>
                <option>あり（仕事中はしない)</option>
                <option>あり（休憩中にはしたい)</option>
              </select>
            </label>

            <label>
              <span class="lbl">食べ物の制限</span>
              <select name="food_rules">
                <option value=""></option>
                <option>特にありません</option>
                <option>全肉は食べません</option>
                <option>豚は食べません</option>
                <option>牛肉は食べません</option>
                <option>豚は食べません/お酒は飲みません</option>
              </select>
            </label>

            <label>
              <span class="lbl">ヒジャブ</span>
              <select name="hijab">
                <option value=""></option>
                <option>仕事中はしません</option>
                <option>仕事中もしたいです。</option>
                <option>必要なし</option>
              </select>
            </label>

            <label>
              <span class="lbl">日本での仕事の希望期間</span>
              <select name="work_duration_intent">
                <option value=""></option>
                <option>できるだけ長く</option>
                <option>5年は滞在したい</option>
                <option>その他</option>
              </select>
            </label>

            <label>
              <span class="lbl">現在の日本語勉強</span>
              <select name="studying_japanese_now">
                <option value=""></option>
                <option>あり</option>
                <option>なし</option>
              </select>
            </label>

            <label>
              <span class="lbl">現在の専門職の勉強</span>
              <select name="studying_specialty_now">
                <option value=""></option>
                <option>あり</option>
                <option>なし</option>
              </select>
            </label>

            <label>
              <span class="lbl">別の送り出し/別施設の面接</span>
              <select name="other_agency_or_facility_interview" id="finalSelect">
                <option value=""></option>
                <option>あり</option>
                <option>なし</option>
              </select>
            </label>
          </div>

          <div class="rule-box">
            <ol>
              <li>このページの項目は任意です。該当しないものは空欄で構いません。</li>
              <li>就業に影響のない宗教・生活習慣の情報は公開いたしません。</li>
            </ol>
            <div class="ban">※ 差別・偏見につながる表現は入力しないでください。</div>
          </div>

          <div class="nav">
            <button class="btn js-prev-step" type="button">戻る</button>
            <button class="btn primary" type="submit">この内容でプレビューへ</button>
          </div>
        </section>

      </div>
    </form>
  </div>

  <div class="site-footer">© ITF co. Ltd. ALL Rights Reserved</div>

  <script src="/rireki/kaigo/js/kaigo.js?v=5"></script>

  <script>
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
        // uppercase only A-Z (keep spaces and punctuation)
        elRomaji.value = (elRomaji.value || '').replace(/[a-z]/g, m => m.toUpperCase());
      }

      function setValid(el){ if (el) el.setCustomValidity(''); }
      function setInvalid(el, msg){ if (el) el.setCustomValidity(msg); }

      function validateNameFields(){
        if (!elRomaji || !elKana) return true;

        normalizeRomaji();

        const vR = (elRomaji.value || '').trim();
        const vK = (elKana.value || '').trim();

        // Romaji: either Japanese (kanji/kana) OR uppercase romaji
        if (!vR) {
          setInvalid(elRomaji, '氏名（ローマ字）を入力してください。');
        } else if (!(reJPName.test(vR) || reRomajiCaps.test(vR))) {
          setInvalid(elRomaji, '英字は大文字（A-Z）のみ、または漢字で入力してください。');
        } else {
          setValid(elRomaji);
        }

        // Katakana required + katakana-only (✅ no auto convert)
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

      // normalize on input/blur
      if (elRomaji){
        elRomaji.addEventListener('input', normalizeRomaji);
        elRomaji.addEventListener('blur', () => { normalizeRomaji(); validateNameFields(); });
      }
      if (elKana){
        // ✅ no auto-convert on input; only validate on blur
        elKana.addEventListener('blur', () => { validateNameFields(); });
      }
      [elY, elM, elD].forEach(el => el && el.addEventListener('blur', validateDob));
      if (elPostal) elPostal.addEventListener('blur', validatePostal);
      if (elPhone) elPhone.addEventListener('blur', validatePhone);
      if (elPhoto) elPhoto.addEventListener('change', validatePhoto);

      // Gate next-step clicks (stop kaigo.js from moving if invalid)
      document.addEventListener('click', function(e){
        const btn = e.target.closest('.js-next-step');
        if (!btn) return;

        const activePane = document.querySelector('.steps .step-pane.is-active');
        if (!activePane) return;

        // validate only for step1/step2 (others can pass for now)
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


    /* ===== KAIGO: hash-open + autosave/restore ===== */
    (function () {
      if (window.__KAIGO_FORM_PERSIST_BOUND__) return;
      window.__KAIGO_FORM_PERSIST_BOUND__ = true;

      const STORAGE_KEY = 'itf_rireki_kaigo_v1';
      const form = document.getElementById('rirekiForm');
      const panes = Array.from(document.querySelectorAll('.steps .step-pane'));

      function activateStep(idx) {
        if (!panes.length) return;
        idx = Math.max(0, Math.min(idx, panes.length - 1));
        panes.forEach(p => p.classList.remove('is-active', 'slide-in-left', 'slide-in-right', 'slide-out-left', 'slide-out-right'));
        panes[idx].classList.add('is-active');
        try { window.scrollTo({ top: 0, behavior: 'smooth' }); } catch (_) { window.scrollTo(0, 0); }
      }
      function stepIndexFromHash() {
        const m = location.hash.match(/#step-(\d+)/i);
        if (!m) return null;
        const n = parseInt(m[1], 10);
        return isNaN(n) ? null : (n - 1);
      }
      function openFromHash() {
        const idx = stepIndexFromHash();
        if (idx !== null) activateStep(idx);
      }
      window.addEventListener('hashchange', openFromHash);
      window.addEventListener('load', openFromHash);

      if (!form) return;

      function shouldStore(el) {
        if (!el.name || el.disabled) return false;
        if (['file', 'submit', 'button', 'reset'].includes(el.type)) return false;
        return true;
      }
      function serializeForm() {
        const data = {};
        Array.from(form.elements).forEach(el => {
          if (!shouldStore(el)) return;
          const name = el.name;
          const val = el.value;
          if (name.endsWith('[]')) {
            if (!Array.isArray(data[name])) data[name] = [];
            data[name].push(val);
          } else if (name.includes(']')) {
            if (!data[name]) data[name] = [];
            data[name].push(val);
          } else {
            if (data.hasOwnProperty(name)) {
              if (!Array.isArray(data[name])) data[name] = [data[name]];
              data[name].push(val);
            } else {
              data[name] = val;
            }
          }
        });
        return data;
      }
      function restoreForm(data) {
        if (!data || typeof data !== 'object') return;
        Array.from(form.elements).forEach(el => {
          if (!shouldStore(el)) return;
          const name = el.name;
          const saved = data[name];
          if (typeof saved === 'undefined') return;
          if (Array.isArray(saved)) {
            const group = Array.from(form.querySelectorAll(`[name="${CSS.escape(name)}"]`));
            group.forEach((ctrl, i) => { if (typeof saved[i] !== 'undefined') ctrl.value = saved[i]; });
          } else {
            el.value = saved;
          }
        });
        try { form.dispatchEvent(new Event('change', { bubbles: true })); } catch (_) { }
      }
      function save() {
        try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(serializeForm())); } catch (e) { }
      }
      function loadSaved() {
        try { const raw = sessionStorage.getItem(STORAGE_KEY); return raw ? JSON.parse(raw) : null; } catch (e) { return null; }
      }
      let t = null;
      function scheduleSave() { clearTimeout(t); t = setTimeout(save, 300); }

      form.addEventListener('input', scheduleSave, true);
      form.addEventListener('change', scheduleSave, true);
      window.addEventListener('beforeunload', save);

      const saved = loadSaved();
      if (saved) restoreForm(saved);
    })();


    /* ===== AI: simple-Japanese rewrite via your Worker ===== */
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
