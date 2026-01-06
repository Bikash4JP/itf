<?php
// /home/it-future/www/itf/rireki/basic/php/rireki_preview.php

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function keep($name, $value){
  if (is_array($value)) {
    $html = '';
    foreach ($value as $k => $v) $html .= keep($name . '[' . $k . ']', $v);
    return $html;
  }
  return '<input type="hidden" name="'.h($name).'" value="'.h($value).'">' . "\n";
}

function moveTempPhoto(?array $file): ?string {
  if (!$file || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return null;

  $dir = $_SERVER['DOCUMENT_ROOT'] . '/rireki/uploads/tmp';
  if (!is_dir($dir)) @mkdir($dir, 0755, true);

  $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png'], true)) $ext = 'jpg';

  $name = bin2hex(random_bytes(8)) . '.' . $ext;
  $dest = $dir . '/' . $name;

  if (!@move_uploaded_file($file['tmp_name'], $dest)) return null;
  return '/rireki/uploads/tmp/' . $name; // web path
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /rireki/basic/rireki.php', true, 302);
  exit;
}

$post = $_POST;

// photo (optional → temp path)
$photoPath = $post['photo_path'] ?? null;
if (!$photoPath && isset($_FILES['photo'])) {
  $tmp = moveTempPhoto($_FILES['photo']);
  if ($tmp) {
    $photoPath = $tmp;
    $post['photo_path'] = $photoPath;
  }
}

// STEP 1
$step1 = [
  'フリガナ' => $post['personal_name_kana'] ?? '',
  '氏名' => $post['personal_name_kanji'] ?? '',
  '生年月日' => trim(($post['dob_yyyy'] ?? '').'/'.($post['dob_mm'] ?? '').'/'.($post['dob_dd'] ?? ''), ' /'),
  '年齢（自動）' => $post['age'] ?? '',
  '性別' => $post['gender'] ?? '',
  '住所（フリガナ）' => $post['address_kana'] ?? '',
  '郵便番号' => $post['postcode'] ?? '',
  '住所' => $post['address_full'] ?? '',
  '電話番号' => $post['phone'] ?? '',
  'Eメール' => $post['email'] ?? '',
];

// STEP 2: 学歴
$eduRows = [];
$ys = $post['edu_start_year'] ?? [];
$ms = $post['edu_start_month'] ?? [];
$sc = $post['edu_school_name'] ?? [];
$fa = $post['edu_faculty'] ?? [];
$lv = $post['edu_level'] ?? [];
$st = $post['edu_status'] ?? [];
$ye = $post['edu_end_year'] ?? [];
$me = $post['edu_end_month'] ?? [];
$N = max(count($ys), count($ms), count($sc), count($fa), count($lv), count($st), count($ye), count($me));
for ($i=0; $i<$N; $i++){
  $eduRows[] = [
    '開始' => trim(($ys[$i] ?? '').'/'.($ms[$i] ?? ''), ' /'),
    '学校名' => $sc[$i] ?? '',
    '学部・学科' => $fa[$i] ?? '',
    '区分' => $lv[$i] ?? '',
    '在学状況' => $st[$i] ?? '',
    '終了' => trim(($ye[$i] ?? '').'/'.($me[$i] ?? ''), ' /'),
  ];
}

// STEP 3: 職歴
$workRows = [];
$sY = $post['exp_start_year'] ?? [];
$sM = $post['exp_start_month'] ?? [];
$co = $post['exp_company'] ?? [];
$ti = $post['exp_title'] ?? [];
$es = $post['exp_status'] ?? [];
$eY = $post['exp_end_year'] ?? [];
$eM = $post['exp_end_month'] ?? [];
$W = max(count($sY), count($sM), count($co), count($ti), count($es), count($eY), count($eM));
for ($i=0; $i<$W; $i++){
  $workRows[] = [
    '開始' => trim(($sY[$i] ?? '').'/'.($sM[$i] ?? ''), ' /'),
    '会社名' => $co[$i] ?? '',
    '役職 / 職種' => $ti[$i] ?? '',
    '在職状況' => $es[$i] ?? '',
    '終了' => trim(($eY[$i] ?? '').'/'.($eM[$i] ?? ''), ' /'),
  ];
}

// STEP 4: 資格・免許
$licRows = [];
$ly = $post['lic_year'] ?? [];
$lm = $post['lic_month'] ?? [];
$ln = $post['lic_name'] ?? [];
$L = max(count($ly), count($lm), count($ln));
for ($i=0; $i<$L; $i++){
  $licRows[] = [
    '取得年' => $ly[$i] ?? '',
    '取得月' => $lm[$i] ?? '',
    '資格名 / 免許名' => $ln[$i] ?? '',
  ];
}

// STEP 5: 自己PR・希望
$step5 = [
  '志望動機・自己PRなど' => $post['self_pr'] ?? '',
  '本人希望記入欄' => $post['hopes'] ?? '',
];

function js_safe_json($arr){
  return json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
$seed = [];
foreach ($post as $k => $v) $seed[$k] = $v;
$seed['__active_step'] = isset($post['__active_step']) ? $post['__active_step'] : '5';
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>入力内容の確認（プレビュー）｜履歴書フォーム（Basic）</title>
  <meta name="robots" content="noindex,follow" />
  <style>
    :root{
      --sky:#1e90ff; --ink:#0b0f19; --muted:#475467; --bd:#e6edf6;
      --bg:#f6fbff; --card:#fff; --radius:14px; --shadow:0 10px 24px rgba(0,0,0,.05);
      --header-h:72px; --header-gap:12px;
    }
    *{ box-sizing:border-box }
    body{ margin:0; color:var(--ink); font-family:ui-sans-serif,system-ui,"Noto Sans JP",Meiryo,Arial;
          background:linear-gradient(180deg,#f8fbff,#eef6ff);
          padding-top:calc(var(--header-h) + var(--header-gap)); }
    header{ position:fixed; top:0; left:0; right:0; z-index:1000; min-height:var(--header-h);
            background:#9ed1ff; border-bottom:1px solid var(--bd); }
    .wrap{ max-width:1100px; margin:0 auto; padding:16px 18px; }
    .hdr{ display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .title{ margin:0; font-size:22px; font-weight:900; color:var(--ink); }
    .crumb{ color:var(--muted); font-size:12px; margin:2px 0 0; }

    main.wrap{ display:grid; grid-template-columns:2fr 1fr; gap:18px; padding:18px; align-items:start; }
    @media(max-width:980px){ main.wrap{ grid-template-columns:1fr; } }

    .section{ background:var(--card); border:1px solid var(--bd); border-radius:var(--radius);
              overflow:hidden; box-shadow:var(--shadow); }
    .section + .section{ margin-top:16px; }
    .section-head{ color:var(--sky); padding:10px 14px; }
    .section-head h2{ margin:0; font-size:16px; font-weight:900; }
    .section-body{ padding:14px; }

    .row{ display:grid; grid-template-columns:220px 1fr; gap:10px; padding:8px 0; border-bottom:1px dashed #e8f2fb; }
    .row:last-child{ border-bottom:none; }
    .label{ font-weight:800; }
    .muted{ color:var(--muted); }
    .table{ width:100%; border-collapse:collapse; }
    .table th,.table td{ border:1px solid #e8f2fb; padding:8px 10px; vertical-align:top; }
    .table thead th{ background:#eef6ff; font-weight:900; }

    .btn{
      appearance:none; cursor:pointer; border-radius:10px; padding:10px 14px;
      border:1px solid #bfe2ff; background:#f3f9ff; color:#0c4a7a; font-weight:900;
      text-decoration:none; display:inline-flex; gap:8px; align-items:center;
    }
    .btn.primary{ background:linear-gradient(180deg,#39a7ff,#1e90ff); color:#fff; border-color:#39a7ff; }
    .section-cta{ display:flex; justify-content:center; margin-top:12px; }

    img.photo{ max-width:160px; border:2px solid #e5f0ff; border-radius:10px; }
    .photo-box{ display:flex; align-items:center; gap:12px; }

    main.wrap > aside{
      position:sticky; top:calc(var(--header-h) + var(--header-gap));
      height:fit-content; align-self:start;
    }
    .side-card{
      background:#fff; border:1px solid var(--bd); border-radius:var(--radius);
      padding:14px; margin-bottom:16px; box-shadow:0 8px 18px rgba(0,0,0,.04);
    }
    .linklist{ list-style:none; margin:0; padding:0; }
    .linklist li{ margin:6px 0; }
    .linklist a{ color:#0c4a7a; text-decoration:none; border-bottom:1px dashed #9ed1ff; }
    .linklist a:hover{ border-bottom-color:#1e90ff; }
  </style>
</head>
<body>
<header>
  <div class="wrap">
    <div class="hdr">
      <div>
        <h1 class="title">入力内容の確認（Basic プレビュー）</h1>
        <p class="crumb">ホーム ＞ 履歴書メーカー ＞ Basic ＞ プレビュー</p>
      </div>
      <a class="btn" href="/rireki/index.php">フォーマット選択へ戻る</a>
    </div>
  </div>
</header>

<main class="wrap">
  <section>
    <div class="section">
      <div class="section-head"><h2>STEP 1：基本情報</h2></div>
      <div class="section-body">
        <?php foreach ($step1 as $k=>$v): ?>
          <div class="row">
            <div class="label"><?=h($k)?></div>
            <div class="value"><?= $v!=='' ? nl2br(h($v)) : '—' ?></div>
          </div>
        <?php endforeach; ?>

        <?php if ($photoPath): ?>
          <div class="row">
            <div class="label">写真</div>
            <div class="photo-box">
              <img class="photo" src="<?=h($photoPath)?>" alt="photo preview">
              <span class="muted"><?=h($photoPath)?></span>
            </div>
          </div>
        <?php endif; ?>

        <div class="section-cta">
          <a class="btn" href="/rireki/basic/rireki.php#step-1">このステップを編集</a>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="section-head"><h2>STEP 2：学歴</h2></div>
      <div class="section-body">
        <?php if ($eduRows && array_filter($eduRows, fn($r)=>implode('', $r) !== '')): ?>
          <table class="table">
            <thead><tr><th>開始</th><th>学校名</th><th>学部・学科</th><th>区分</th><th>在学状況</th><th>終了</th></tr></thead>
            <tbody>
              <?php foreach ($eduRows as $r): ?>
                <tr>
                  <td><?=h($r['開始'])?></td>
                  <td><?=h($r['学校名'])?></td>
                  <td><?=h($r['学部・学科'])?></td>
                  <td><?=h($r['区分'])?></td>
                  <td><?=h($r['在学状況'])?></td>
                  <td><?=h($r['終了'])?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="muted">未入力</p>
        <?php endif; ?>

        <div class="section-cta">
          <a class="btn" href="/rireki/basic/rireki.php#step-2">このステップを編集</a>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="section-head"><h2>STEP 3：職歴</h2></div>
      <div class="section-body">
        <?php if ($workRows && array_filter($workRows, fn($r)=>implode('', $r) !== '')): ?>
          <table class="table">
            <thead><tr><th>開始</th><th>会社名</th><th>役職 / 職種</th><th>在職状況</th><th>終了</th></tr></thead>
            <tbody>
              <?php foreach ($workRows as $r): ?>
                <tr>
                  <td><?=h($r['開始'])?></td>
                  <td><?=h($r['会社名'])?></td>
                  <td><?=h($r['役職 / 職種'])?></td>
                  <td><?=h($r['在職状況'])?></td>
                  <td><?=h($r['終了'])?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="muted">未入力</p>
        <?php endif; ?>

        <div class="section-cta">
          <a class="btn" href="/rireki/basic/rireki.php#step-3">このステップを編集</a>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="section-head"><h2>STEP 4：資格・免許</h2></div>
      <div class="section-body">
        <?php if ($licRows && array_filter($licRows, fn($r)=>implode('', $r) !== '')): ?>
          <table class="table">
            <thead><tr><th>取得年</th><th>取得月</th><th>資格名 / 免許名</th></tr></thead>
            <tbody>
              <?php foreach ($licRows as $r): ?>
                <tr>
                  <td><?=h($r['取得年'])?></td>
                  <td><?=h($r['取得月'])?></td>
                  <td><?=h($r['資格名 / 免許名'])?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="muted">未入力</p>
        <?php endif; ?>

        <div class="section-cta">
          <a class="btn" href="/rireki/basic/rireki.php#step-4">このステップを編集</a>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="section-head"><h2>STEP 5：自己PR・希望</h2></div>
      <div class="section-body">
        <?php foreach ($step5 as $k=>$v): ?>
          <div class="row">
            <div class="label"><?=h($k)?></div>
            <div class="value"><?= $v!=='' ? nl2br(h($v)) : '—' ?></div>
          </div>
        <?php endforeach; ?>
        <div class="section-cta">
          <a class="btn" href="/rireki/basic/rireki.php#step-5">このステップを編集</a>
        </div>
      </div>
    </div>

    <div class="section" style="margin-top:18px;">
      <div class="section-body">
        <h3 style="margin:0 0 8px 0;">この内容で送信しますか？</h3>
        <p class="muted" style="margin:6px 0 12px">
          送信後にExcel出力が生成されます。必要であれば「このステップを編集」から修正してください。
        </p>
        <form method="post" action="/rireki/basic/php/submit_rireki.php" style="display:flex;gap:10px;flex-wrap:wrap">
          <?php foreach ($post as $k=>$v) echo keep($k, $v); ?>
          <a class="btn" href="/rireki/basic/rireki.php#step-1">戻って修正する</a>
          <button type="submit" class="btn primary">この内容で送信する</button>
        </form>
      </div>
    </div>

  </section>

  <aside>
    <div class="side-card">
      <h3 style="margin:0 0 8px 0;">最終チェック</h3>
      <ul class="muted" style="margin:0; padding-left:18px;">
        <li>氏名・住所・連絡先は正確？</li>
        <li>学歴/職歴の年月は時系列 OK？</li>
        <li>写真（任意）は明るく背景シンプル？</li>
      </ul>
    </div>

    <div class="side-card">
      <h3 style="margin:0 0 8px 0;">関連リンク</h3>
      <ul class="linklist">
        <li><a href="/rireki/index.php">フォーマット選択に戻る</a></li>
        <li><a href="/saiyou.php">新着採用（求人一覧）</a></li>
        <li><a href="/index.html#service-naiyo">サービス紹介</a></li>
        <li><a href="/company_info.html">会社概要</a></li>
      </ul>
    </div>

    <div class="side-card">
      <h3 style="margin:0 0 8px 0;">ヘルプ</h3>
      <p class="muted" style="margin:0;">
        プレビューはブラウザ表示のため、Excel印刷時と微差が出る場合があります。提出前に内容を再確認ください。
      </p>
    </div>
  </aside>
</main>

<script>
  // Seed draft into storage from preview page (safety net)
  (function () {
    try {
      var data = <?php echo js_safe_json($seed); ?>;
      var KEY = 'rireki:basic:draft:v1';
      var json = JSON.stringify(data);
      localStorage.setItem(KEY, json);
      sessionStorage.setItem(KEY, json);
    } catch (e) { console.warn('seed draft failed', e); }
  })();
</script>

</body>
</html>
