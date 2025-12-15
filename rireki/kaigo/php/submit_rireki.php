<?php
// /home/it-future/www/itf/rireki/kaigo/php/submit_rireki.php
error_reporting(E_ALL);
ini_set('display_errors', isset($_GET['debug']) ? '1' : '0');

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/adapters/adapter_xlsx.php';
require_once __DIR__ . '/validators.php';

// Optional: applicant auth (public users)
$authPath = __DIR__ . '/../../../php/user_auth.php';
if (is_readable($authPath)) require_once $authPath;

// Paths
$mappingFile = rireki_path('mappings/templateB.json');
$outDir      = rireki_path('resumes');
$tmpDir      = rireki_path('tmp');
@mkdir($outDir, 0755, true);
@mkdir($tmpDir, 0755, true);

// ---------- helpers ----------
function _reshape_rows(array $group, array $fields): array {
  $rows = []; $len = 0;
  foreach ($fields as $f) $len = max($len, isset($group[$f]) ? count((array)$group[$f]) : 0);
  for ($i=0; $i<$len; $i++) {
    $row = [];
    foreach ($fields as $f) $row[$f] = trim($group[$f][$i] ?? '');
    if (implode('', $row) !== '') $rows[] = $row;
  }
  return $rows;
}

function _plain_fail(string $msg, int $code = 500): void {
  http_response_code($code);
  header('Content-Type: text/plain; charset=UTF-8');
  echo $msg;
  exit;
}

function _starts_with(string $hay, string $needle): bool {
  return $needle === '' ? true : (substr($hay, 0, strlen($needle)) === $needle);
}

/**
 * Preview page moves photo to /rireki/uploads/tmp/xxx.jpg and sends it as photo_path (web path).
 * Adapter expects readable filesystem path, so convert web path to absolute path safely.
 */
function _normalize_photo_path_from_preview(array &$data): void {
  if (empty($data['photo_path']) || !is_string($data['photo_path'])) return;

  $photo = trim($data['photo_path']);
  if ($photo === '') return;

  // If already a readable filesystem path, keep.
  if (is_readable($photo)) return;

  // If it's a URL, treat as photo_url (adapter can download it)
  if (preg_match('#^https?://#i', $photo)) {
    $data['photo_url'] = $photo;
    return;
  }

  // If it's a web path like /rireki/uploads/tmp/xxx.jpg, convert to absolute
  if (_starts_with($photo, '/')) {
    $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
    if ($docRoot !== '') {
      $candidate = realpath($docRoot . $photo);

      // allow only under /rireki/uploads to prevent path abuse
      $allowBase = realpath($docRoot . '/rireki/uploads');

      if ($candidate && $allowBase && _starts_with($candidate, $allowBase) && is_readable($candidate)) {
        $data['_photo_rel'] = $photo;      // keep web-path too (adapter supports this)
        $data['photo_path'] = $candidate;  // absolute path for XLS image embedding
      }
    }
  }
}

// ---------- build data ----------
if (isset($_GET['demo'])) {
  $data = [
    'name_romaji'=>'TARO YAMADA','name_kana'=>'やまだ たろう',
    'dob_year'=>'1998','dob_month'=>'04','dob_day'=>'01',
    'birthplace'=>'東京','postal'=>'123-4567','address'=>'東京都千代田区1-2-3',
    'nationality'=>'ネパール国籍','gender'=>'男性','religion'=>'仏教','marital_status'=>'無し',
    'contact_phone'=>'090-1111-2222','email'=>'taro@example.com',
    'height_cm'=>'170','weight_kg'=>'60','passport_has'=>'有り','passport_no'=>'AB123456',
    'self_pr'=>'責任感が強く、学習意欲が高いです。','motivation'=>'介護の現場で働きたい。','preferences'=>'東京23区で勤務希望',
    'planned_resign_year'=>'2026','planned_resign_month'=>'03',
    'education'=>[
      ['from_year'=>'2015','from_month'=>'04','to_year'=>'2018','to_month'=>'03','institution'=>'ABC高校','faculty'=>'普通科'],
      ['from_year'=>'2018','from_month'=>'04','to_year'=>'2022','to_month'=>'03','institution'=>'XYZ大学','faculty'=>'福祉学部'],
    ],
    'work_blocks'=>[
      ['from_year'=>'2022','from_month'=>'04','to_year'=>'2024','to_month'=>'03','org'=>'介護施設ABC','job_title'=>'介護職','work_time_start'=>'09:00','work_time_end'=>'18:00','work_days_per_week'=>'5','status'=>'退職','description'=>'入浴介助、排泄介助、記録業務など']
    ],
    'licenses'=>[
      ['cert_year'=>'2021','cert_month'=>'12','cert_name'=>'日本語能力試験N2'],
      ['cert_year'=>'2022','cert_month'=>'07','cert_name'=>'介護職員初任者研修'],
    ],
  ];
  $jobId = 0;
} else {
  $data  = $_POST;
  $jobId = isset($data['job_id']) ? (int)$data['job_id'] : 0;

  // normalize planned resignation date
  $data['planned_resign_year']  = trim($data['planned_resign_year']  ?? '');
  $data['planned_resign_month'] = trim($data['planned_resign_month'] ?? '');

  // reshape repeaters
  if (!empty($data['education']) && is_array($data['education']) && isset($data['education']['from_year'])) {
    $data['education'] = _reshape_rows($data['education'], ['from_year','from_month','to_year','to_month','institution','faculty','status']);
  }
  if (!empty($data['work_blocks']) && is_array($data['work_blocks']) && isset($data['work_blocks']['from_year'])) {
    $data['work_blocks'] = _reshape_rows($data['work_blocks'], ['from_year','from_month','to_year','to_month','org','job_title','work_time_start','work_time_end','work_days_per_week','status','description']);
  }
  if (!empty($data['licenses']) && is_array($data['licenses']) && isset($data['licenses']['cert_year'])) {
    $data['licenses'] = _reshape_rows($data['licenses'], ['cert_year','cert_month','cert_name']);
  }

  // consolidate travel notes
  if (empty($data['past_travel_history'])) {
    $cnt = trim($data['past_travel_count'] ?? '');
    $det = trim($data['past_travel_details'] ?? '');
    $data['past_travel_history'] = ($cnt !== '' || $det !== '') ? ("回数: ".$cnt." / ".$det) : '';
  }

  // photo upload (direct from form case)
  if (isset($_FILES['photo']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $dir = rireki_path('uploads/photos');
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png'], true)) $ext = 'jpg';
    $dest = $dir . '/' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
      $data['photo_path'] = $dest; // absolute FS path
    }
  }

  // photo path from preview (web path -> absolute)
  _normalize_photo_path_from_preview($data);
}

// ----- persist source metadata for rireki_list.php -----
$data['_source']    = ($jobId > 0) ? 'job' : 'open';
$data['_job_id']    = $jobId;
$data['_job_title'] = '';

// fetch job title if coming from a job
if ($jobId > 0) {
  $dbPath = __DIR__ . '/../../../php/db_connect.php';
  if (is_readable($dbPath)) {
    require_once $dbPath;
    if (isset($pdo) && $pdo instanceof PDO) {
      $stmt = $pdo->prepare("SELECT title FROM posts WHERE id = ? AND post_type = 'job' LIMIT 1");
      $stmt->execute([$jobId]);
      $t = $stmt->fetchColumn();
      if (is_string($t) && $t !== '') $data['_job_title'] = $t;
    }
  }
}

// ---------- render XLS ----------
$token = bin2hex(random_bytes(16));

if (function_exists('rireki_render_xls_only')) {
  $res = rireki_render_xls_only($data, $mappingFile, $outDir, $token);
} elseif (function_exists('rireki_render_pdf')) {
  $res = rireki_render_pdf($data, $mappingFile, $outDir, $token);
} else {
  _plain_fail("Renderer not found in adapter.", 500);
}

if (empty($res['ok']) || empty($res['xls'])) {
  _plain_fail("Kaigo Rirekisho build failed: " . ($res['err'] ?? 'unknown'), 500);
}

// Save JSON snapshot (used for claim later)
@file_put_contents($outDir . '/' . $token . '.json', json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

// URLs
$xlsUrl = '/rireki/kaigo/resumes/' . basename((string)$res['xls']);

// ---------- save to logged-in user's history (DB) ----------
if (function_exists('app_is_logged_in') && app_is_logged_in()) {
  try {
    $pdo_app = app_pdo();
    if (function_exists('app_ensure_tables')) app_ensure_tables($pdo_app);

    $uid = (int) app_user_id();
    $fmt = 'kaigo';

    // 1) Save resume token + file path
    $st = $pdo_app->prepare("
      INSERT INTO app_resumes (user_id, fmt, token, job_id, xls_path)
      VALUES (?, ?, ?, ?, ?)
    ");
    $st->execute([$uid, $fmt, $token, ($jobId > 0 ? $jobId : null), $xlsUrl]);

    // 2) If this came from a job, record application history
    if ($jobId > 0) {
      $st2 = $pdo_app->prepare("
        INSERT INTO app_applications (user_id, job_id, resume_token)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
          resume_token = VALUES(resume_token),
          created_at = CURRENT_TIMESTAMP
      ");
      $st2->execute([$uid, $jobId, $token]);
    }

  } catch (Throwable $e) {
    // Don't break user flow if DB insert fails; just log silently
    error_log('[submit_rireki] db save failed: ' . $e->getMessage());
  }
}


$fmt = 'kaigo';
$claimNext = '/rireki/php/claim_resume.php?token=' . urlencode($token) . '&fmt=' . urlencode($fmt);
$loginUrl  = '/php/user_login.php?next=' . urlencode($claimNext);

// ---------- success page ----------
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>介護向け 履歴書の作成が完了</title>
  <link rel="stylesheet" href="https://it-future.jp/css/footer.css">
  <style>
    :root{
      --ink:#0b0f19; --muted:#667085; --border:#e6edf6;
      --bg:#f8fbff; --ok:#0b6b4a; --ring:#bfe2ff;
      --btn-bg:#f3f9ff; --btn-bd:#dbe7f5; --btn-ink:#0c4a7a;
      --warn:#b45309; --warn-bg:#fff7ed; --warn-bd:#fed7aa;
    }
    *{ box-sizing:border-box }
    body{
      margin:0; padding:20px;
      background:linear-gradient(180deg,#f8fbff,#eef6ff);
      font-family: ui-sans-serif,system-ui,"Segoe UI",Roboto,"Noto Sans JP","Hiragino Kaku Gothic ProN",Meiryo,Arial,sans-serif;
      color:var(--ink);
    }
    .wrap{ max-width:980px; margin:0 auto }
    .card{
      background:#fff; border:1px solid var(--border); border-radius:16px;
      padding:18px; box-shadow:0 10px 24px rgba(0,0,0,.05)
    }
    h1{ margin:0 0 8px; font-size:22px }
    .sub{ color:var(--muted); margin:0 0 12px }
    .done{
      display:flex; gap:10px; align-items:flex-start; margin:12px 0 16px;
      background:#ecfdf5; border:1px solid #bbf7d0; color:var(--ok);
      padding:12px 14px; border-radius:12px; font-weight:700
    }
    .apply{
      display:flex; gap:10px; align-items:flex-start; margin:12px 0 16px;
      background:#eef2ff; border:1px solid #c7d2fe; color:#27326b;
      padding:12px 14px; border-radius:12px; font-weight:700
    }
    .notice{
      display:flex; gap:10px; align-items:flex-start;
      background:var(--warn-bg); border:1px solid var(--warn-bd); color:var(--warn);
      padding:12px 14px; border-radius:12px; margin:12px 0
    }
    .actions{ display:flex; gap:10px; flex-wrap:wrap; margin:12px 0 2px }
    .btn{
      display:inline-flex; align-items:center; gap:8px; text-decoration:none; cursor:pointer;
      padding:12px 14px; border-radius:10px; font-weight:800;
      background:var(--btn-bg); border:1px solid var(--btn-bd); color:var(--btn-ink);
      transition: background .2s, transform .2s, box-shadow .2s, border-color .2s;
    }
    .btn:hover{ background:#e9f5ff; transform: translateY(-1px); box-shadow:0 6px 18px rgba(57,167,255,.18) }
    .hint{ color:var(--muted); font-size:12px; margin-top:4px }
    hr{ border:none; border-top:1px dashed #e5e7eb; margin:16px 0 }
    .sec h2{ margin:0 0 8px; font-size:18px }
    details{ border-top:1px dashed #e5e7eb; padding:10px 0 }
    details:first-of-type{ border-top:none }
    summary{ cursor:pointer; font-weight:700 }
    .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; font-size:12px; color:#1f2937 }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1>介護向け 履歴書の作成が完了しました。</h1>
      <p class="sub">Excelファイルを保存し、そのまま印刷または編集してご利用ください。</p>

      <?php if ($jobId > 0): ?>
        <div class="apply">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
            <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"></path>
          </svg>
          ご応募ありがとうございます。担当チームにて内容を確認のうえ、<strong>2営業日以内</strong>にご連絡いたします。
        </div>
      <?php else: ?>
        <div class="done">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          履歴書が完成しました。ダウンロードしてご確認ください。
        </div>
      <?php endif; ?>

      <div class="notice" role="note" aria-live="polite">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
        </svg>
        PDF は環境差により体裁が100%一致しない場合があります。<strong>Excel（.xls）からの印刷</strong>を推奨します。
      </div>

      <div class="actions">
        <a class="btn" href="<?= htmlspecialchars($xlsUrl, ENT_QUOTES, 'UTF-8') ?>" download>
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <path d="M14 2v6h6"></path>
          </svg>
          Excel（.xls）をダウンロード
        </a>

        <?php if (function_exists('app_is_logged_in') && app_is_logged_in()): ?>
          <a class="btn" href="<?= htmlspecialchars($claimNext, ENT_QUOTES, 'UTF-8') ?>">
            アカウントに保存（あとで再DL）
          </a>
        <?php else: ?>
          <a class="btn" href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>">
            ログインして保存（あとで再DL）
          </a>
        <?php endif; ?>

        <?php if ($jobId > 0): ?>
          <a class="btn" href="/php/job_details.php?job_id=<?= (int)$jobId ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 13v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
              <path d="M15 3h6v6"></path>
              <path d="M10 14 21 3"></path>
            </svg>
            応募先の求人詳細を見る
          </a>
        <?php endif; ?>

        <a class="btn" href="/saiyou.php">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
            <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"></path>
          </svg>
          他の求人を探す
        </a>
        <a class="btn" href="https://it-future.jp/">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m3 9 9-7 9 7"></path>
            <path d="M9 22V12h6v10"></path>
          </svg>
          会社ホームページへ
        </a>
        <a class="btn" href="/rireki/index.php">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="1 4 1 10 7 10"></polyline>
            <polyline points="23 20 23 14 17 14"></polyline>
            <path d="M20.49 9A9 9 0 1 0 6.2 18.8"></path>
          </svg>
          別のフォーマットで作る
        </a>
      </div>

      <p class="hint">※ Excel から「余白：上下左右小さめ」「ページ設定：A4」にして印刷すると綺麗に出力できます。</p>

      <hr>

      <section class="sec">
        <h2>よくある質問（FAQ）</h2>
        <details>
          <summary>PDF での出力はできますか？</summary>
          <div>現在は Excel（.xls）での出力に最適化しています。PDF は環境差で体裁が崩れることがあるため、Excel からの印刷をおすすめします。</div>
        </details>
        <details>
          <summary>内容を修正したいです。</summary>
          <div>ダウンロードした Excel をそのまま編集できます。不要な枠線や余分な余白はページ設定から調整してください。</div>
        </details>
        <details>
          <summary>介護向け項目（夜勤・シフト・資格）も出力されますか？</summary>
          <div>はい。入力いただいた夜勤可否・希望シフト・介護資格などがテンプレートに反映されます。</div>
        </details>
        <details>
          <summary>求人へはどう応募しますか？</summary>
          <div><a href="/saiyou.php">新着採用</a>ページから、求人詳細の指示に従って応募してください。すでに求人から作成した場合は、担当より2営業日以内に連絡いたします。</div>
        </details>
      </section>

      <hr>

      <p class="mono">トークン: <?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?> / 出力: <?= htmlspecialchars($xlsUrl, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
  </div>

  <footer class="footer">
    <div class="footer-container">
      <div class="footer-row">
        <div class="footer-col">
          <h3 class="footer-heading" data-i18n="footer.location_title">所在地</h3>
          <div class="footer-link">
            <a href="https://it-future.jp/" style="color: white;" data-i18n="footer.company_name">株式会社アイティーエフ</a>
          </div>
          <p class="footer-text" data-i18n="footer.location_details">
            〒556-0017 大阪府大阪市浪速区湊町1-4-38 近鉄新難波ビル10F<br>
            06-6644-1800<br>
            〒144-0052 東京都大田区蒲田5丁目21-13<br>
            03-6424-7747<br>
            info@it-future.jp
          </p>
        </div>
        <div class="footer-col">
          <h3 class="footer-heading" data-i18n="footer.services_title">サービス案内</h3>
          <a href="https://it-future.jp/index.html#solution_03" class="footer-link" data-i18n="footer.services_for_companies">人財をお探しの企業様</a>
          <a href="https://it-future.jp/index.html#service-naiyo" class="footer-link" data-i18n="footer.service_introduction">サービス紹介</a>
          <a href="https://it-future.jp/index.html#merit" class="footer-link" data-i18n="footer.benefits">メリット</a>
          <a href="https://it-future.jp/index.html#work-step" class="footer-link" data-i18n="footer.introduction_flow">紹介の流れ</a>
          <a href="https://it-future.jp/about.html#support-naiyou" class="footer-link" data-i18n="footer.support_content">サポート内容</a>
        </div>
        <div class="footer-col">
          <h3 class="footer-heading" data-i18n="footer.company_info_title">会社案内</h3>
          <a href="https://it-future.jp/greeting.html" class="footer-link" data-i18n="footer.president_greeting">代表者挨拶</a>
          <a href="https://it-future.jp/company_info.html" class="footer-link" data-i18n="footer.company_info">会社概要</a>
        </div>
        <div class="footer-col">
          <a href="https://it-future.jp/privacy.html" class="footer-btn" data-i18n="footer.privacy_policy">プライバシーポリシー</a>
        </div>
      </div>
      <div class="footer-copyright">
        © ITF co. Ltd. ALL Rights Reserved
      </div>
    </div>
  </footer>
</body>
</html>
