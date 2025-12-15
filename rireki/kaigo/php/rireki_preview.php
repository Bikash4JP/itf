<?php
// /home/it-future/www/itf/rireki/kaigo/php/rireki_preview.php

require_once __DIR__ . '/../../../php/user_auth.php'; // /itf/php/user_auth.php

// ---------- helpers ----------
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/** Re-create POST fields (including nested arrays) as hidden inputs */
function keep($name, $value){
  if (is_array($value)){
    $html = '';
    foreach ($value as $k => $v){ $html .= keep($name.'['.$k.']', $v); }
    return $html;
  }
  return '<input type="hidden" name="'.h($name).'" value="'.h($value).'">'."\n";
}

/** Move uploaded photo to a temp public path for preview; return web path. */
function moveTempPhoto(?array $file): ?string {
  if (!$file || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return null;

  $dir = $_SERVER['DOCUMENT_ROOT'] . '/rireki/uploads/tmp';
  if (!is_dir($dir)) @mkdir($dir, 0755, true);

  $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png'], true)) $ext = 'jpg';

  $name = bin2hex(random_bytes(8)).'.'.$ext;
  $dest = $dir.'/'.$name;

  if (!@move_uploaded_file($file['tmp_name'], $dest)) return null;

  return '/rireki/uploads/tmp/'.$name; // web path
}

/**
 * Persist tmp photo to a permanent per-user folder so edits won't require re-upload.
 * Returns new web path, or original if already permanent, or null if missing.
 */
function persistPhotoForUser(int $user_id, ?string $photoPath): ?string {
  if (!$photoPath) return null;

  $tmpPrefix = '/rireki/uploads/tmp/';
  // already permanent
  if (strpos($photoPath, $tmpPrefix) !== 0) return $photoPath;

  $docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
  if ($docRoot === '') return null;

  $tmpDir = $docRoot . '/rireki/uploads/tmp';
  $src    = $docRoot . $photoPath;

  $tmpDirReal = realpath($tmpDir);
  $srcReal    = realpath($src);

  if (!$tmpDirReal || !$srcReal) return null;
  // security: ensure src is inside tmp dir
  if (strpos($srcReal, $tmpDirReal) !== 0) return null;
  if (!is_file($srcReal)) return null;

  $dstDir = $docRoot . "/rireki/uploads/profile/{$user_id}";
  if (!is_dir($dstDir)) @mkdir($dstDir, 0755, true);

  $ext = strtolower(pathinfo($srcReal, PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png'], true)) $ext = 'jpg';

  $name = 'photo_' . bin2hex(random_bytes(6)) . '.' . $ext;
  $dst  = $dstDir . '/' . $name;

  if (!@copy($srcReal, $dst)) return null;

  // optional: delete tmp to reduce clutter (ignore failures)
  @unlink($srcReal);

  return "/rireki/uploads/profile/{$user_id}/{$name}";
}

function ensure_profile_table(PDO $pdo){
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS app_user_profiles (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL UNIQUE,
      data_json LONGTEXT NOT NULL,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");
}

function load_profile_post(PDO $pdo, int $user_id): ?array {
  ensure_profile_table($pdo);
  $st = $pdo->prepare("SELECT data_json FROM app_user_profiles WHERE user_id=? LIMIT 1");
  $st->execute([$user_id]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if (!$row || empty($row['data_json'])) return null;
  $arr = json_decode((string)$row['data_json'], true);
  return is_array($arr) ? $arr : null;
}

function save_profile_post(PDO $pdo, int $user_id, array $post): void {
  ensure_profile_table($pdo);
  $json = json_encode($post, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  $st = $pdo->prepare("
    INSERT INTO app_user_profiles (user_id, data_json)
    VALUES (?, ?)
    ON DUPLICATE KEY UPDATE data_json=VALUES(data_json), updated_at=CURRENT_TIMESTAMP
  ");
  $st->execute([$user_id, $json]);
}

// ---------- Mode selection ----------
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$post = [];
$photoPath = null;

// GET mode: "プロフィールで進む" → show saved data preview
if ($method === 'GET') {
  $job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

  if (!app_is_logged_in()) {
    $next = $_SERVER['REQUEST_URI'] ?? '/saiyou.php';
    header('Location: /php/user_login.php?next=' . urlencode($next), true, 302);
    exit;
  }

  $pdo_app = app_pdo();
  $uid = (int)app_user_id();
  $saved = load_profile_post($pdo_app, $uid);

  if (!$saved) {
    // No saved profile yet → send to form fill
    $dest = '/rireki/kaigo/rireki.php';
    if ($job_id > 0) $dest .= '?job_id=' . urlencode((string)$job_id);
    header('Location: ' . $dest, true, 302);
    exit;
  }

  $post = $saved;
  if ($job_id > 0) $post['job_id'] = $job_id; // keep selected job context

  // Make sure photo is permanent (if it was a tmp path saved earlier)
  $perma = persistPhotoForUser($uid, $post['photo_path'] ?? null);
  if ($perma) {
    $post['photo_path'] = $perma;
    save_profile_post($pdo_app, $uid, $post); // store back updated path
  }
  $photoPath = $post['photo_path'] ?? null;

} else {
  // POST mode: from form submit → preview
  $post = $_POST;

  // Handle photo upload → temp path first
  $photoPath = $post['photo_path'] ?? null;
  if (!$photoPath && isset($_FILES['photo'])) {
    $tmp = moveTempPhoto($_FILES['photo']);
    if ($tmp) { $photoPath = $tmp; $post['photo_path'] = $photoPath; }
  }

  // If logged in, persist photo + auto-save profile
  if (app_is_logged_in()) {
    $pdo_app = app_pdo();
    $uid = (int)app_user_id();

    // Persist tmp photo into permanent per-user folder
    $perma = persistPhotoForUser($uid, $post['photo_path'] ?? null);
    if ($perma) {
      $photoPath = $perma;
      $post['photo_path'] = $perma;
    }

    save_profile_post($pdo_app, $uid, $post);
  }
}

// job_id for apply button
$job_id = isset($post['job_id']) ? (int)$post['job_id'] : (isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0);

// ---------- Build preview data from KAIGO form names ----------
// STEP 1：基本情報
$step1 = [
  '氏名（ローマ字）' => $post['name_romaji'] ?? '',
  'フリガナ'         => $post['name_kana'] ?? '',
  '生年月日'         => trim(($post['dob_year'] ?? '').'/'.($post['dob_month'] ?? '').'/'.($post['dob_day'] ?? ''), ' /'),
  '年齢（自動）'     => $post['age_autofill'] ?? '',
  '出生地'           => $post['birthplace'] ?? '',
  '郵便番号'         => $post['postal'] ?? '',
  '現住所'           => $post['address'] ?? '',
  '電話番号'         => $post['contact_phone'] ?? '',
  'Eメール'          => $post['email'] ?? '',
  '国籍'             => $post['nationality'] ?? '',
  '性別'             => $post['gender'] ?? '',
  '宗教'             => $post['religion'] ?? '',
  '配偶者の有無'     => $post['marital_status'] ?? '',
  '身長 (cm)'        => $post['height_cm'] ?? '',
  '体重 (kg)'        => $post['weight_kg'] ?? '',
];

// STEP 2：在留・写真
$step2 = [
  'パスポート'       => $post['passport_has'] ?? '',
  'パスポートNO'     => $post['passport_no'] ?? '',
  '有効期限'         => trim(($post['passport_exp_year'] ?? '').'/'.($post['passport_exp_month'] ?? '').'/'.($post['passport_exp_day'] ?? ''), ' /'),
  '出入国歴（回数）' => $post['past_travel_count'] ?? '',
  '出入国歴の詳細'   => $post['past_travel_details'] ?? '',
  '直近の入国'       => trim(($post['recent_entry_year'] ?? '').'/'.($post['recent_entry_month'] ?? '').'/'.($post['recent_entry_day'] ?? ''), ' /'),
  '直近の出国'       => trim(($post['recent_exit_year'] ?? '').'/'.($post['recent_exit_month'] ?? '').'/'.($post['recent_exit_day'] ?? ''), ' /'),
  '現在の在留資格'   => $post['current_status'] ?? '',
  '在留期限（開始）' => trim(($post['status_from_year'] ?? '').'/'.($post['status_from_month'] ?? '').'/'.($post['status_from_day'] ?? ''), ' /'),
  '在留期限（終了）' => trim(($post['status_to_year'] ?? '').'/'.($post['status_to_month'] ?? '').'/'.($post['status_to_day'] ?? ''), ' /'),
];

// STEP 3：学歴・資格
$eduRows = [];
$eyF = $post['education']['from_year']  ?? [];
$emF = $post['education']['from_month'] ?? [];
$eyT = $post['education']['to_year']    ?? [];
$emT = $post['education']['to_month']   ?? [];
$es  = $post['education']['status']     ?? [];
$ei  = $post['education']['institution']?? [];
$ef  = $post['education']['faculty']    ?? [];
$N   = max(count($eyF),count($emF),count($eyT),count($emT),count($es),count($ei),count($ef));
for($i=0;$i<$N;$i++){
  $eduRows[] = [
    '開始'      => trim(($eyF[$i] ?? '').'/'.($emF[$i] ?? ''), ' /'),
    '終了'      => trim(($eyT[$i] ?? '').'/'.($emT[$i] ?? ''), ' /'),
    '在学状況'  => $es[$i] ?? '',
    '学校名'    => $ei[$i] ?? '',
    '学部・専攻' => $ef[$i] ?? '',
  ];
}

$licRows = [];
$ly = $post['licenses']['cert_year']  ?? [];
$lm = $post['licenses']['cert_month'] ?? [];
$ln = $post['licenses']['cert_name']  ?? [];
$L  = max(count($ly),count($lm),count($ln));
for($i=0;$i<$L;$i++){
  $licRows[] = [
    '取得年' => $ly[$i] ?? '',
    '取得月' => $lm[$i] ?? '',
    '資格名' => $ln[$i] ?? '',
  ];
}

// STEP 4：職歴
$workRows = [];
$wyF = $post['work_blocks']['from_year']  ?? [];
$wmF = $post['work_blocks']['from_month'] ?? [];
$ws  = $post['work_blocks']['status']     ?? [];
$wyT = $post['work_blocks']['to_year']    ?? [];
$wmT = $post['work_blocks']['to_month']   ?? [];
$wo  = $post['work_blocks']['org']        ?? [];
$wt  = $post['work_blocks']['job_title']  ?? [];
$wd  = $post['work_blocks']['description']?? [];
$W   = max(count($wyF),count($wmF),count($ws),count($wyT),count($wmT),count($wo),count($wt),count($wd));
for($i=0;$i<$W;$i++){
  $workRows[] = [
    '開始'        => trim(($wyF[$i] ?? '').'/'.($wmF[$i] ?? ''), ' /'),
    '在職状況'    => $ws[$i] ?? '',
    '終了'        => trim(($wyT[$i] ?? '').'/'.($wmT[$i] ?? ''), ' /'),
    '会社・施設名' => $wo[$i] ?? '',
    '職種/役職'    => $wt[$i] ?? '',
    '仕事内容'      => $wd[$i] ?? '',
  ];
}
$reasonResign  = $post['reason_for_resignation'] ?? '';
$plannedResign = trim(($post['planned_resign_year'] ?? '').'/'.($post['planned_resign_month'] ?? ''), ' /');

// STEP 5：自己PR・志望・希望
$step5 = [
  '自己PR'           => $post['self_pr'] ?? '',
  '志望の動機'       => $post['motivation'] ?? '',
  '本人希望（職種・給与・勤務地など）' => $post['preferences'] ?? '',
];

// STEP 6：別途情報
$step6 = [
  '日本語コミュニケーション' => $post['jp_comm_level'] ?? '',
  '漢字読み書き'           => $post['kanji_rw'] ?? '',
  '血液型'                 => $post['blood_type'] ?? '',
  '英語'                   => $post['english_level'] ?? '',
  '日本に知り合い'         => $post['acquaintances_in_japan'] ?? '',
  '日本人の友達'           => $post['jp_friends_count'] ?? '',
  '母国の友達（日本に）'   => $post['home_country_friends_in_japan'] ?? '',
  'タバコ'                 => $post['smoking'] ?? '',
  'お酒'                   => $post['alcohol'] ?? '',
  '刺青'                   => $post['tattoo'] ?? '',
  '服のサイズ'             => $post['clothes_size'] ?? '',
  '靴のサイズ'             => $post['shoe_size'] ?? '',
  'お祈り'                 => $post['prayer'] ?? '',
  '断食'                   => $post['fasting'] ?? '',
  '食べ物の制限'           => $post['food_rules'] ?? '',
  'ヒジャブ'               => $post['hijab'] ?? '',
  '仕事の希望期間'         => $post['work_duration_intent'] ?? '',
  '日本語の勉強'           => $post['studying_japanese_now'] ?? '',
  '専門職の勉強'           => $post['studying_specialty_now'] ?? '',
  '別途送り出し/別施設の面接' => $post['other_agency_or_facility_interview'] ?? '',
];

?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>入力内容の確認（Kaigo プレビュー）</title>
  <meta name="robots" content="noindex,follow" />
  <style>
    :root{
      --sky:#1e90ff; --sky-2:#39a7ff; --ink:#0b0f19; --muted:#475467;
      --bd:#e6edf6; --bg:#f6fbff; --card:#fff; --ring:#bfe2ff;
      --radius:14px; --shadow:0 10px 24px rgba(0,0,0,.05);
      --header-h:72px; --header-gap:12px;
    }
    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0; color:var(--ink);
      font-family:ui-sans-serif,system-ui,"Noto Sans JP",Meiryo,Arial;
      background:linear-gradient(180deg,#f8fbff,#eef6ff);
      padding-top: calc(var(--header-h) + var(--header-gap));
    }
    header{
      position:fixed; top:0; left:0; right:0; z-index:1000;
      min-height:var(--header-h);
      background:#9ed1ff;
      border-bottom:1px solid var(--bd);
      backdrop-filter:saturate(180%) blur(6px);
    }
    .wrap{max-width:1100px;margin:0 auto;padding:16px 18px}
    .hdr{display:flex;align-items:center;justify-content:space-between;gap:12px}
    .title{margin:0;font-size:22px;font-weight:900;letter-spacing:.2px;color:var(--ink)}
    .crumb{color:var(--muted);font-size:12px;margin:2px 0 0}

    main.wrap{
      display:grid; grid-template-columns:2fr 1fr; gap:18px; padding:18px;
      align-items:start;
    }
    @media (max-width:980px){ main.wrap{ grid-template-columns:1fr } }

    .section{
      background:var(--card); border:1px solid var(--bd); border-radius:var(--radius);
      overflow:hidden; box-shadow:var(--shadow);
      transform:translateY(6px); opacity:0; animation:slideIn .5s ease forwards;
    }
    .section + .section{ margin-top:16px }
    @keyframes slideIn { to { transform:translateY(0); opacity:1; } }
    .section-head{ color:var(--sky); padding:10px 14px; display:flex; align-items:center; justify-content:space-between; }
    .section-head h2{ margin:0; font-size:16px; font-weight:900; letter-spacing:.5px; }
    .section-body{ padding:14px; }

    .row{ display:grid; grid-template-columns:260px 1fr; gap:10px; padding:8px 0; border-bottom:1px dashed #e8f2fb; }
    .row:last-child{ border-bottom:none }
    .label{ color:#0b0f19; font-weight:700 }
    .value{ color:#0b0f19 }
    .muted{ color:var(--muted) }
    .table{ width:100%; border-collapse:collapse; }
    .table th,.table td{ border:1px solid #e8f2fb; padding:8px 10px; vertical-align:top; color:#0b0f19; }
    .table thead th{ background:#eef6ff; color:#0b0f19; font-weight:800; }

    .photo-box{ display:flex; align-items:center; gap:12px; }
    img.photo{ max-width:160px; border:2px solid #e5f0ff; border-radius:10px; }

    .btn{
      appearance:none; cursor:pointer; border-radius:10px; padding:10px 14px;
      border:1px solid var(--ring); background:#f3f9ff; color:#0c4a7a; font-weight:800;
      transition:transform .2s, box-shadow .2s, background .2s, border-color .2s;
      text-decoration:none; display:inline-flex; align-items:center; gap:8px;
    }
    .btn:hover{ transform:translateY(-1px); box-shadow:0 6px 18px rgba(30,144,255,.16); background:#e9f5ff; }
    .btn.primary{ background:linear-gradient(180deg,var(--sky-2),var(--sky)); color:#fff; border-color:var(--sky-2); }
    .final{ background:#fff; border:2px solid var(--sky); border-radius:var(--radius); padding:16px; box-shadow:0 14px 28px rgba(30,144,255,.12) }
    .final h3{ margin:0 0 8px 0; color:var(--ink) }

    main.wrap > aside{
      position:sticky; top: calc(var(--header-h) + var(--header-gap));
      height:fit-content; align-self:start; z-index:5;
    }
    .side-card{
      background:#fff; border:1px solid var(--bd); border-radius:var(--radius);
      padding:14px; margin-bottom:16px; box-shadow:0 8px 18px rgba(0,0,0,.04);
      transform:translateY(6px); opacity:0; animation:slideIn .6s ease .05s forwards;
    }
    .side-card h3{ margin:0 0 8px; color:var(--ink) }
    .linklist{ list-style:none; margin:0; padding:0 }
    .linklist li{ margin:6px 0 }
    .linklist a{ color:#0c4a7a; text-decoration:none; border-bottom:1px dashed #9ed1ff }
    .linklist a:hover{ color:#0a3861; border-bottom-color:#1e90ff }

    @media (max-width:980px){
      :root{ --header-h:84px; }
      main.wrap > aside{ position:static; }
      .row{ grid-template-columns:1fr; }
    }
  </style>
</head>
<body>
<header>
  <div class="wrap">
    <div class="hdr">
      <div>
        <h1 class="title">入力内容の確認（Kaigo プレビュー）</h1>
        <p class="crumb">ホーム ＞ 履歴書メーカー ＞ 介護向け ＞ プレビュー</p>
      </div>

      <?php if ($job_id > 0): ?>
        <a class="btn" href="/php/job_details.php?job_id=<?=h($job_id)?>">求人詳細へ戻る</a>
      <?php else: ?>
        <a class="btn" href="/saiyou.php">求人一覧へ戻る</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="wrap">
  <section>
    <!-- STEP 1 -->
    <div class="section">
      <div class="section-head"><h2>STEP 1：基本情報</h2></div>
      <div class="section-body">
        <?php foreach ($step1 as $k=>$v): ?>
          <div class="row">
            <div class="label"><?=h($k)?></div>
            <div class="value"><?= $v!==''?nl2br(h($v)):'—' ?></div>
          </div>
        <?php endforeach; ?>
        <div style="margin-top:12px; text-align:center;">
          <a class="btn" href="/rireki/kaigo/rireki.php<?= $job_id>0 ? '?job_id='.h($job_id) : '' ?>#step-1">このステップを編集</a>
        </div>
      </div>
    </div>

    <!-- STEP 2 -->
    <div class="section">
      <div class="section-head"><h2>STEP 2：在留・写真</h2></div>
      <div class="section-body">
        <?php foreach ($step2 as $k=>$v): ?>
          <div class="row">
            <div class="label"><?=h($k)?></div>
            <div class="value"><?= $v!==''?nl2br(h($v)):'—' ?></div>
          </div>
        <?php endforeach; ?>

        <?php if (!empty($photoPath)): ?>
          <div class="row">
            <div class="label">証明写真（保存済み）</div>
            <div class="photo-box">
              <img class="photo" src="<?=h($photoPath)?>" alt="photo preview">
              <span class="muted"><?=h($photoPath)?></span>
            </div>
          </div>
        <?php else: ?>
          <div class="row">
            <div class="label">証明写真</div>
            <div class="value muted">未登録（編集時に写真を求められる場合があります）</div>
          </div>
        <?php endif; ?>

        <div style="margin-top:12px; text-align:center;">
          <a class="btn" href="/rireki/kaigo/rireki.php<?= $job_id>0 ? '?job_id='.h($job_id) : '' ?>#step-2">このステップを編集</a>
        </div>
      </div>
    </div>

    <!-- STEP 3 -->
    <div class="section">
      <div class="section-head"><h2>STEP 3：学歴・資格</h2></div>
      <div class="section-body">
        <h3 class="muted" style="margin:0 0 8px 0">学歴</h3>
        <?php if ($eduRows && array_filter($eduRows, fn($r)=>implode('',$r)!=='')): ?>
          <table class="table">
            <thead><tr><th>開始</th><th>終了</th><th>在学状況</th><th>学校名</th><th>学部・専攻</th></tr></thead>
            <tbody>
            <?php foreach ($eduRows as $r): ?>
              <tr>
                <td><?=h($r['開始'])?></td>
                <td><?=h($r['終了'])?></td>
                <td><?=h($r['在学状況'])?></td>
                <td><?=h($r['学校名'])?></td>
                <td><?=h($r['学部・専攻'])?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="muted">未入力</p>
        <?php endif; ?>

        <h3 class="muted" style="margin:14px 0 8px 0">免許・資格</h3>
        <?php if ($licRows && array_filter($licRows, fn($r)=>implode('',$r)!=='')): ?>
          <table class="table">
            <thead><tr><th>取得年</th><th>取得月</th><th>資格名</th></tr></thead>
            <tbody>
            <?php foreach ($licRows as $r): ?>
              <tr>
                <td><?=h($r['取得年'])?></td>
                <td><?=h($r['取得月'])?></td>
                <td><?=h($r['資格名'])?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="muted">未入力</p>
        <?php endif; ?>

        <div style="margin-top:12px; text-align:center;">
          <a class="btn" href="/rireki/kaigo/rireki.php<?= $job_id>0 ? '?job_id='.h($job_id) : '' ?>#step-3">このステップを編集</a>
        </div>
      </div>
    </div>

    <!-- STEP 4 -->
    <div class="section">
      <div class="section-head"><h2>STEP 4：職歴</h2></div>
      <div class="section-body">
        <?php if ($workRows && array_filter($workRows, fn($r)=>implode('',$r)!=='')): ?>
          <table class="table">
            <thead><tr><th>開始</th><th>在職状況</th><th>終了</th><th>会社・施設名</th><th>職種/役職</th><th>仕事内容</th></tr></thead>
            <tbody>
            <?php foreach ($workRows as $r): ?>
              <tr>
                <td><?=h($r['開始'])?></td>
                <td><?=h($r['在職状況'])?></td>
                <td><?=h($r['終了'])?></td>
                <td><?=h($r['会社・施設名'])?></td>
                <td><?=h($r['職種/役職'])?></td>
                <td><?=nl2br(h($r['仕事内容']))?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="muted">未入力</p>
        <?php endif; ?>

        <div class="row">
          <div class="label">退職理由（対象者）</div>
          <div class="value"><?= $reasonResign!=='' ? nl2br(h($reasonResign)) : '—' ?></div>
        </div>
        <div class="row">
          <div class="label">退職予定（年/月）</div>
          <div class="value"><?= $plannedResign!=='' ? h($plannedResign) : '—' ?></div>
        </div>

        <div style="margin-top:12px; text-align:center;">
          <a class="btn" href="/rireki/kaigo/rireki.php<?= $job_id>0 ? '?job_id='.h($job_id) : '' ?>#step-4">このステップを編集</a>
        </div>
      </div>
    </div>

    <!-- STEP 5 -->
    <div class="section">
      <div class="section-head"><h2>STEP 5：自己PR・志望・希望</h2></div>
      <div class="section-body">
        <?php foreach ($step5 as $k=>$v): ?>
          <div class="row">
            <div class="label"><?=h($k)?></div>
            <div class="value"><?= $v!==''?nl2br(h($v)):'—' ?></div>
          </div>
        <?php endforeach; ?>
        <div style="margin-top:12px; text-align:center;">
          <a class="btn" href="/rireki/kaigo/rireki.php<?= $job_id>0 ? '?job_id='.h($job_id) : '' ?>#step-5">このステップを編集</a>
        </div>
      </div>
    </div>

    <!-- STEP 6 -->
    <div class="section">
      <div class="section-head"><h2>STEP 6：別途情報</h2></div>
      <div class="section-body">
        <?php foreach ($step6 as $k=>$v): ?>
          <div class="row">
            <div class="label"><?=h($k)?></div>
            <div class="value"><?= $v!==''?nl2br(h($v)):'—' ?></div>
          </div>
        <?php endforeach; ?>
        <div style="margin-top:12px; text-align:center;">
          <a class="btn" href="/rireki/kaigo/rireki.php<?= $job_id>0 ? '?job_id='.h($job_id) : '' ?>#step-6">このステップを編集</a>
        </div>
      </div>
    </div>

    <!-- Final submit -->
    <div class="final" style="margin-top:18px;">
      <h3>この内容で送信しますか？</h3>
      <p class="muted" style="margin:6px 0 12px">
        <?php if ($job_id > 0): ?>
          この内容で「求人応募」まで進みます。必要であれば各ステップの「このステップを編集」から修正してください。
        <?php else: ?>
          この内容で保存・更新されます。必要であれば各ステップの「このステップを編集」から修正してください。
        <?php endif; ?>
      </p>

      <form method="post" action="/rireki/kaigo/php/submit_rireki.php" style="display:flex;gap:10px;flex-wrap:wrap">
        <?php foreach ($post as $k=>$v) echo keep($k,$v); ?>
        <?php if ($job_id > 0): ?>
          <a class="btn" href="/php/job_details.php?job_id=<?=h($job_id)?>">求人詳細へ戻る</a>
          <button type="submit" class="btn primary">この内容で応募する</button>
        <?php else: ?>
          <a class="btn" href="/saiyou.php">求人一覧へ戻る</a>
          <button type="submit" class="btn primary">この内容で保存する</button>
        <?php endif; ?>
      </form>
    </div>
  </section>

  <!-- Sidebar -->
  <aside>
    <div class="side-card">
      <h3>最終チェック</h3>
      <ul class="muted" style="margin:0 0 0 1em">
        <li>氏名・住所・連絡先は正確？</li>
        <li>在留期限・旅券番号の扱いはOK？</li>
        <li>学歴/職歴の年月は時系列？</li>
        <li>写真は明るく背景シンプル？</li>
      </ul>
    </div>
    <div class="side-card">
      <h3>関連リンク</h3>
      <ul class="linklist">
        <li><a href="/saiyou.php">新着採用（求人一覧）</a></li>
        <li><a href="/company_info.html">会社概要</a></li>
      </ul>
    </div>
    <div class="side-card">
      <h3>ヘルプ</h3>
      <p class="muted">プレビューはブラウザ表示のため、Excel印刷時と微差が出る場合があります。提出前に内容を再確認ください。</p>
    </div>
  </aside>
</main>

</body>
</html>
