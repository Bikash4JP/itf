<?php
// /home/it-future/www/itf/rireki/basic/rireki.php
?><!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <title>履歴書フォーム（Basic）</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="/rireki/basic/css/recruit.css?v=3">
</head>
<body>
  <!-- Appbar -->
  <div class="appbar">
    <div class="wrap">
      <h1>履歴書フォーム（Basic）</h1>
      <a class="home" href="/rireki/index.php">← フォーマット選択へ</a>
    </div>
  </div>
  <!-- Progress Bar -->
<!-- Progress Bar -->
<div id="progressWrap">
  <ul class="progress-labels">
    <li data-step="0">個人情報</li>
    <li data-step="1">学歴</li>
    <li data-step="2">職歴</li>
    <li data-step="3">資格・免許</li>
    <li data-step="4">自己PR</li>
    <li data-step="5" id="labelDone">作成終了</li>
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

      <div class="steps">

        <!-- STEP 1 -->
        <section class="step-pane is-active" data-step="1" id="step-1">
          <h2>基本情報</h2>
          <div class="grid-2">
            <label>フリガナ<input type="text" name="personal_name_kana" placeholder="やまだ たろう" autocomplete="name"></label>
            <label>氏名<input type="text" name="personal_name_kanji" placeholder="山田 太郎"></label>

            <label>
              生年月日（YYYY/MM/DD）
              <input type="text" id="dob" placeholder="1998/04/01" inputmode="numeric" autocomplete="bday">
              <input type="hidden" name="dob_yyyy" id="dob_yyyy">
              <input type="hidden" name="dob_mm"   id="dob_mm">
              <input type="hidden" name="dob_dd"   id="dob_dd">
              <div class="banner">数字だけ入力で自動的にスラッシュ（/）が入ります（例：19980401）。年齢はJSTで自動算出。</div>
            </label>
            <label>年齢（自動）<input type="number" name="age" id="age" placeholder="自動算出" readonly></label>

            <label>性別
              <select name="gender"><option value="">未選択</option><option>男</option><option>女</option><option>その他</option></select>
            </label>
            <div></div>

            <label>住所（フリガナ）<input type="text" name="address_kana" placeholder="トウキョウト〇〇シ〜"></label>
            <label>郵便番号<input type="text" name="postcode" placeholder="123-4567" inputmode="numeric"></label>
            <label class="col-2">住所<input type="text" name="address_full" placeholder="東京都千代田区1-2-3"></label>

            <label>電話番号<input type="tel" name="phone" id="phone" placeholder="090-0000-0000"></label>
            <label>Eメール<input type="email" name="email" placeholder="taro@example.com"></label>

            <div class="col-2">
              <div class="section-title">写真</div>
              <div style="display:flex;gap:16px;align-items:flex-start;">
                <label style="min-width:260px">パスポートサイズ写真（jpg/png）
                  <input type="file" name="photo" id="photo" accept="image/jpeg,image/png">
                  <div class="photo-preview" id="photoPreview" style="display:none;">
                    <img id="photoPreviewImg" alt="preview">
                  </div>
                </label>
                <div class="help" style="color:#64748b;font-size:12.5px">アップロードした写真はプレビューにも反映され、Excel側では写真枠に縦横比を保ってフィットします。</div>
              </div>
            </div>

            <!-- ルール/メモ（STEP1用：写真の基準含む） -->
            <div class="col-2 rule-box">
              <strong>入力ルール & メモ（基本情報・写真）</strong>
              <ol>
                <li>氏名・フリガナは公的書類と同じ表記で。全角スペース/記号の位置も確認。</li>
                <li>住所は建物名・部屋番号まで正確に。電話/Eメールは連絡が取れるもの。</li>
                <li>日付は半角数字（YYYY/MM/DD）。</li>
                <li><u>写真は以下の基準を満たしてください</u>：
                  <ul>
                    <li>撮影から<strong>3か月以内</strong>、カラー</li>
                    <li><strong>耳・肩が見える</strong>正面上半身、背景は無地</li>
                    <li>目を閉じない／髪で目が隠れない、<strong>ブレ・影・逆光なし</strong></li>
                    <li>フィルター・過度な加工は不可、帽子・サングラス不可</li>
                  </ul>
                </li>
                <li class="ban">虚偽入力は禁止です。提出前に誤字脱字の最終チェックを。</li>
                <li>Excel出力後に印字崩れがあれば、Excel上で微調整してください（PDFは完全一致しない場合があります）。</li>
              </ol>
            </div>
          </div>
          <div class="nav"><button class="btn primary js-next-step">次へ</button></div>
        </section>

        <!-- STEP 2: Education -->
        <section class="step-pane" data-step="2" id="step-2">
          <h2>学歴</h2>
          <table class="table edu" id="eduTable">
            <colgroup>
              <col class="yy"><col class="mm"><col class="inst"><col class="fac"><col class="level"><col class="status"><col class="yy2"><col class="mm2"><col>
            </colgroup>
            <thead>
              <tr><th>開始年</th><th>開始月</th><th>学校名</th><th>学部・学科</th><th>区分</th><th>在学状況</th><th>終了年</th><th>終了月</th><th>操作</th></tr>
            </thead>
            <tbody>
              <tr class="edu-row">
                <td><input class="date-ym" type="text" name="edu_start_year[]"  placeholder="YYYY" inputmode="numeric"></td>
                <td><input class="date-ym" type="text" name="edu_start_month[]" placeholder="MM"   inputmode="numeric"></td>
                <td><input type="text" name="edu_school_name[]" placeholder="△△大学 / ○○高校 等"></td>
                <td>
                  <select name="edu_faculty[]">
                    <option value="">—</option><option>理工学部</option><option>経営学部</option><option>情報学部</option>
                    <option>教育学部</option><option>人文学部</option><option>商学部</option><option>専門課程</option>
                  </select>
                </td>
                <td>
                  <select name="edu_level[]">
                    <option value="">—</option><option>小学</option><option>中学</option><option>高校</option><option>専門学校</option><option>大学</option>
                  </select>
                </td>
                <td>
                  <select name="edu_status[]" class="js-status-edu"><option>在学中</option><option>卒業</option><option>退学</option></select>
                </td>
                <td><input class="date-ym js-edu-end-y" type="text" name="edu_end_year[]"  placeholder="YYYY" inputmode="numeric" disabled></td>
                <td><input class="date-ym js-edu-end-m" type="text" name="edu_end_month[]" placeholder="MM"   inputmode="numeric" disabled></td>
                <td><button type="button" class="row-add" data-for="edu">＋</button> <button type="button" class="row-del">−</button></td>
              </tr>
            </tbody>
          </table>

          <!-- ルール/メモ（STEP2） -->
          <div class="rule-box">
            <strong>入力ルール & メモ（学歴）</strong>
            <ol>
              <li>学校名は正式名称で（例：×○○大 → 〇〇大学）。学部・学科があれば記入。</li>
              <li>入学・卒業の別は行ごとに分けず、<u>システム側で「入学」「卒業」を自動生成</u>します。終了予定なら「卒業」を選び年月を入力。</li>
              <li>編入・留学・休学など特記事項があれば学歴の下に追記可（自己PR欄でも可）。</li>
              <li>古い順（時系列）で入力してください。</li>
            </ol>
          </div>

          <div class="nav"><button class="btn js-prev-step">戻る</button><button class="btn primary js-next-step">次へ</button></div>
        </section>

        <!-- STEP 3: Experience -->
        <section class="step-pane" data-step="3" id="step-3">
          <h2>職歴</h2>
          <table class="table exp" id="expTable">
            <colgroup>
              <col class="yy"><col class="mm"><col class="org"><col class="title"><col class="status"><col class="yy2"><col class="mm2"><col>
            </colgroup>
            <thead>
              <tr><th>開始年</th><th>開始月</th><th>会社名</th><th>役職 / 職種</th><th>在職状況</th><th>終了年</th><th>終了月</th><th>操作</th></tr>
            </thead>
            <tbody>
              <tr class="exp-row">
                <td><input class="date-ym" type="text" name="exp_start_year[]"  placeholder="YYYY" inputmode="numeric"></td>
                <td><input class="date-ym" type="text" name="exp_start_month[]" placeholder="MM"   inputmode="numeric"></td>
                <td><input type="text" name="exp_company[]" placeholder="ABC株式会社"></td>
                <td><input type="text" name="exp_title[]"   placeholder="エンジニア / 販売 / 介護 等"></td>
                <td>
                  <select name="exp_status[]" class="js-status-exp"><option>在職中</option><option>退職</option></select>
                </td>
                <td><input class="date-ym js-exp-end-y" type="text" name="exp_end_year[]"  placeholder="YYYY" inputmode="numeric" disabled></td>
                <td><input class="date-ym js-exp-end-m" type="text" name="exp_end_month[]" placeholder="MM"   inputmode="numeric" disabled></td>
                <td><button type="button" class="row-add" data-for="exp">＋</button> <button type="button" class="row-del">−</button></td>
              </tr>
            </tbody>
          </table>

          <!-- ルール/メモ（STEP3） -->
          <div class="rule-box">
            <strong>入力ルール & メモ（職歴）</strong>
            <ol>
              <li>会社名は正式名称で。部署名は任意、<u>職種/役職は簡潔に</u>（例：販売職 → 「家電量販店 販売職」）。</li>
              <li>在職中は終了年月を空欄のまま。退職済みの場合のみ終了年月を入力。</li>
              <li>業務内容は自己PR欄で補足可。契約・派遣・アルバイトはその旨を明記。</li>
              <li>時系列（古い→新しい）で入力してください。</li>
            </ol>
          </div>

          <div class="nav"><button class="btn js-prev-step">戻る</button><button class="btn primary js-next-step">次へ</button></div>
        </section>

        <!-- STEP 4: Licenses -->
        <section class="step-pane" data-step="4" id="step-4">
          <h2>資格・免許</h2>
          <table class="table lic" id="licTable">
            <colgroup><col class="yy"><col class="mm"><col class="name"><col></colgroup>
            <thead><tr><th>年</th><th>月</th><th>資格名 / 免許名</th><th>操作</th></tr></thead>
            <tbody>
              <tr class="lic-row">
                <td><input class="date-ym" type="text" name="lic_year[]"  placeholder="YYYY" inputmode="numeric"></td>
                <td><input class="date-ym" type="text" name="lic_month[]" placeholder="MM"   inputmode="numeric"></td>
                <td><input type="text" name="lic_name[]"  placeholder="基本情報技術者 / 普通自動車第一種 など"></td>
                <td><button type="button" class="row-add" data-for="lic">＋</button> <button type="button" class="row-del">−</button></td>
              </tr>
            </tbody>
          </table>

          <!-- ルール/メモ（STEP4） -->
          <div class="rule-box">
            <strong>入力ルール & メモ（資格・免許）</strong>
            <ol>
              <li>正式名称で記入（例：× 普通免許 → 〇 普通自動車第一種運転免許）。</li>
              <li>取得予定は「取得予定」と追記し、予定年月を入力。</li>
              <li>関連性が薄い趣味の検定等は控えめに。応募職種に関係するものを優先。</li>
            </ol>
          </div>

          <div class="nav"><button class="btn js-prev-step">戻る</button><button class="btn primary js-next-step">次へ</button></div>
        </section>

        <!-- STEP 5 -->
        <section class="step-pane" data-step="5" id="step-5">
          <h2>自己PR・希望</h2>
          <div class="grid-2">
            <label class="col-2">志望動機・自己PRなど<textarea name="self_pr" rows="5" placeholder="自己PRを入力してください。"></textarea></label>
            <label class="col-2">本人希望記入欄<textarea name="hopes" rows="5" placeholder="給料・職種・勤務時間・勤務地など希望があれば記入してください。"></textarea></label>
          </div>

          <!-- ルール/メモ（STEP5：自己PR/志望動機の作り方） -->
          <div class="rule-box">
            <strong>入力ルール & メモ（自己PR / 志望動機）</strong>
            <ol>
              <li><u>結論→根拠→成果→活かし方</u>の順で簡潔に：<br>
                例）「接客での傾聴力に強みがあります。前職でアンケート満足度4.7/5を継続。御社では高齢者の方にも安心いただける対応で貢献します。」</li>
              <li>避けるべき表現：抽象的な一般論（「コミュ力があります」だけ等）、転職理由のネガティブ連発、長すぎる文章。</li>
              <li>志望動機は<strong>会社理解</strong>（事業/理念/募集背景）と<strong>自分の経験</strong>を接続して書く。</li>
              <li>本人希望は「必須条件」と「相談可」を分け、優先度を明確に。</li>
              <li class="ban">機密・個人情報の過度な開示は避ける（他社の売上データなど）。</li>
            </ol>
            <p style="margin:8px 0 0">提出前チェック：氏名・日付・連絡先・学歴/職歴の年月、誤字脱字、写真の基準、希望条件の矛盾がないか最終確認してください。</p>
          </div>

          <div class="nav"><button class="btn js-prev-step">戻る</button><button class="btn primary" type="submit">この内容で作成する</button></div>
        </section>

      </div>
    </form>
  </div>

  <div class="site-footer">© ITF co. Ltd. ALL Rights Reserved</div>

  <script src="/rireki/basic/js/rireki_form.js?v=3" defer></script>
</body>
</html>
