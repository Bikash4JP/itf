<?php
// /home/it-future/www/itf/rireki/kaigo/php/submit_rireki.php
error_reporting(E_ALL);
ini_set('display_errors', isset($_GET['debug']) ? '1' : '0');

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/adapters/adapter_xlsx.php';
require_once __DIR__ . '/validators.php';

// Optional: applicant auth (public users)
$authPath = __DIR__ . '/../../../php/user_auth.php'; // /itf/php/user_auth.php
if (is_readable($authPath)) require_once $authPath;

// ✅ Main DB (expects $pdo as PDO)
require_once $_SERVER['DOCUMENT_ROOT'] . '/php/db_connect.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
  http_response_code(500);
  echo "DB connection missing.";
  exit;
}

// Paths
$mappingFile = rireki_path('mappings/templateB.json');
$outDir      = rireki_path('resumes');
$tmpDir      = rireki_path('tmp');
@mkdir($outDir, 0755, true);
@mkdir($tmpDir, 0755, true);

/* =========================
   helpers
   ========================= */
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function normalize_token($t): string {
  $t = strtolower(trim((string)$t));
  return preg_match('/^[a-f0-9]{32}$/', $t) ? $t : '';
}

function _plain_fail(string $msg, int $code = 500): void {
  http_response_code($code);
  $wantsJson = (!empty($_POST['ajax']) && $_POST['ajax'] === '1');
  if ($wantsJson) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['ok'=>false,'err'=>$msg], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
  }
  header('Content-Type: text/plain; charset=UTF-8');
  echo $msg;
  exit;
}

function _starts_with(string $hay, string $needle): bool {
  return $needle === '' ? true : (substr($hay, 0, strlen($needle)) === $needle);
}

/**
 * Convert web photo path to absolute filesystem path safely for XLS embedding.
 * Accept only under allowed upload bases.
 */
function _normalize_photo_path_from_web(array &$data): void {
  if (empty($data['photo_path']) || !is_string($data['photo_path'])) return;

  $photo = trim($data['photo_path']);
  if ($photo === '') return;

  // already filesystem readable
  if (is_readable($photo)) return;

  // URL -> treat as photo_url
  if (preg_match('#^https?://#i', $photo)) {
    $data['photo_url'] = $photo;
    return;
  }

  if (!_starts_with($photo, '/')) return;

  $docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
  if ($docRoot === '') return;

  $candidate = realpath($docRoot . $photo);
  if (!$candidate) return;

  // allow only under these bases
  $allowBases = [
    realpath($docRoot . '/rireki/uploads'),
    realpath($docRoot . '/rireki/kaigo/uploads'),
  ];

  foreach ($allowBases as $base) {
    if ($base && _starts_with($candidate, $base) && is_readable($candidate)) {
      $data['_photo_rel'] = $photo;      // keep web path too
      $data['photo_path'] = $candidate;  // absolute path for adapter
      return;
    }
  }
}

/**
 * Fetch draft row by token (new flow: token is the source of truth).
 */
function fetch_kaigo_row(PDO $pdo, string $token): array {
  $st = $pdo->prepare("SELECT * FROM app_resume_kaigo WHERE token = :t LIMIT 1");
  $st->execute([':t' => $token]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return is_array($row) ? $row : [];
}

/**
 * Build adapter-friendly data arrays from flattened DB row.
 */
function build_data_from_row(array $row): array {
  $data = $row;

  // education edu1..8 -> rows
  $edu = [];
  for ($i=1; $i<=8; $i++) {
    $r = [
      'from_year'    => trim((string)($row["edu{$i}_from_year"] ?? '')),
      'from_month'   => trim((string)($row["edu{$i}_from_month"] ?? '')),
      'to_year'      => trim((string)($row["edu{$i}_to_year"] ?? '')),
      'to_month'     => trim((string)($row["edu{$i}_to_month"] ?? '')),
      'status'       => trim((string)($row["edu{$i}_status"] ?? '')),
      'institution'  => trim((string)($row["edu{$i}_institution"] ?? '')),
      'faculty'      => trim((string)($row["edu{$i}_faculty"] ?? '')),
    ];
    if (implode('', $r) !== '') $edu[] = $r;
  }
  $data['education'] = $edu;

  // licenses lic1..8 -> rows
  $lic = [];
  for ($i=1; $i<=8; $i++) {
    $r = [
      'cert_year' => trim((string)($row["lic{$i}_year"] ?? '')),
      'cert_month'=> trim((string)($row["lic{$i}_month"] ?? '')),
      'cert_name' => trim((string)($row["lic{$i}_name"] ?? '')),
    ];
    if (implode('', $r) !== '') $lic[] = $r;
  }
  $data['licenses'] = $lic;

  // work work1..8 -> rows
  $wk = [];
  for ($i=1; $i<=8; $i++) {
    $r = [
      'from_year'    => trim((string)($row["work{$i}_from_year"] ?? '')),
      'from_month'   => trim((string)($row["work{$i}_from_month"] ?? '')),
      'status'       => trim((string)($row["work{$i}_status"] ?? '')),
      'to_year'      => trim((string)($row["work{$i}_to_year"] ?? '')),
      'to_month'     => trim((string)($row["work{$i}_to_month"] ?? '')),
      'org'          => trim((string)($row["work{$i}_org"] ?? '')),
      'job_title'    => trim((string)($row["work{$i}_job_title"] ?? '')),
      'description'  => trim((string)($row["work{$i}_description"] ?? '')),
      // optional keys (template may ignore, but keep for compatibility)
      'work_time_start'   => '',
      'work_time_end'     => '',
      'work_days_per_week'=> '',
    ];
    if (implode('', $r) !== '') $wk[] = $r;
  }
  $data['work_blocks'] = $wk;

  // travel note consolidation (optional)
  if (empty($data['past_travel_history'])) {
    $cnt = trim((string)($data['past_travel_count'] ?? ''));
    $det = trim((string)($data['past_travel_details'] ?? ''));
    $data['past_travel_history'] = ($cnt !== '' || $det !== '') ? ("回数: ".$cnt." / ".$det) : '';
  }

  // normalize planned resignation
  $data['planned_resign_year']  = trim((string)($data['planned_resign_year'] ?? ''));
  $data['planned_resign_month'] = trim((string)($data['planned_resign_month'] ?? ''));

  return $data;
}

/* =========================
   Read token + flow
   ========================= */
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') _plain_fail('Method not allowed', 405);

$token = normalize_token($_POST['token'] ?? '');
if (!$token) _plain_fail('Invalid token', 400);

$row = fetch_kaigo_row($pdo, $token);
if (!$row) _plain_fail('Invalid token', 400);

// flow: apply_profile | open_resume | profile_only
$flow = strtolower(trim((string)($_POST['flow'] ?? '')));
$allowedFlows = ['open_resume','apply_profile','profile_only'];
if (!in_array($flow, $allowedFlows, true)) {
  // fallback: if job_id exists -> apply_profile else open_resume
  $guessJob = (int)($row['job_id'] ?? 0);
  $flow = ($guessJob > 0) ? 'apply_profile' : 'open_resume';
}

// intent for downstream logic
$jobId = (int)($row['job_id'] ?? 0);
$intent = ($flow === 'apply_profile' && $jobId > 0) ? 'apply' : 'download';
$ajax = (!empty($_POST['ajax']) && (string)$_POST['ajax'] === '1');

// build data from DB row (source of truth)
$data = build_data_from_row($row);

// metadata for lists/history
$data['_source'] = ($intent === 'apply' && $jobId > 0) ? 'job' : 'open';
$data['_job_id'] = ($intent === 'apply') ? $jobId : 0;
$data['_job_title'] = '';

// fetch job title only when applying
if ($intent === 'apply' && $jobId > 0) {
  try {
    $stmt = $pdo->prepare("SELECT title FROM posts WHERE id = ? AND post_type = 'job' LIMIT 1");
    $stmt->execute([$jobId]);
    $t = $stmt->fetchColumn();
    if (is_string($t) && $t !== '') $data['_job_title'] = $t;
  } catch (Throwable $e) {
    error_log('[submit_rireki] job title fetch failed: '.$e->getMessage());
  }
}

// photo: DB keeps web path => normalize to absolute for renderer
_normalize_photo_path_from_web($data);

/* =========================
   render XLS
   ========================= */
$outToken = bin2hex(random_bytes(16));

if (function_exists('rireki_render_xls_only')) {
  $res = rireki_render_xls_only($data, $mappingFile, $outDir, $outToken);
} elseif (function_exists('rireki_render_pdf')) {
  $res = rireki_render_pdf($data, $mappingFile, $outDir, $outToken);
} else {
  _plain_fail("Renderer not found in adapter.", 500);
}

if (empty($res['ok']) || empty($res['xls'])) {
  _plain_fail("Kaigo Rirekisho build failed: " . ($res['err'] ?? 'unknown'), 500);
}

// Save JSON snapshot
@file_put_contents($outDir . '/' . $outToken . '.json', json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

// URLs
$xlsUrl = '/rireki/kaigo/resumes/' . basename((string)$res['xls']);

/* =========================
   save to logged-in user's history (DB) - unchanged
   ========================= */
if (function_exists('app_is_logged_in') && app_is_logged_in()) {
  try {
    $pdo_app = app_pdo();
    if (function_exists('app_ensure_tables')) app_ensure_tables($pdo_app);

    $uid = (int) app_user_id();
    $fmt = 'kaigo';

    // Save resume (schema: app_resumes has no xls_path)
    $st = $pdo_app->prepare("
      INSERT INTO app_resumes (user_id, fmt, token, job_id)
      VALUES (?, ?, ?, ?)
    ");
    $st->execute([$uid, $fmt, $outToken, ($intent === 'apply' && $jobId > 0) ? $jobId : null]);

    // Save application history ONLY when intent=apply
    if ($intent === 'apply' && $jobId > 0) {
      $st2 = $pdo_app->prepare("
        INSERT INTO app_applications (user_id, job_id, resume_token)
        VALUES (?, ?, ?)
      ");
      $st2->execute([$uid, $jobId, $outToken]);

      // NEW: log activity so staff dashboard recent_activities shows the apply notice
      $actPath = __DIR__ . '/../../../php/activity_logger.php';
      if (is_readable($actPath)) {
        require_once $actPath;
        if (function_exists('log_activity')) {
          $company = '';
          try {
            $stC = $pdo_app->prepare('SELECT company_name FROM posts WHERE id = ? LIMIT 1');
            $stC->execute([$jobId]);
            $company = (string)($stC->fetchColumn() ?: '');
          } catch (Throwable $e) { /* ignore */ }
          if ($company === '') $company = '求人ID ' . (string)$jobId;

          $who = trim((string)($data['name_romaji'] ?? ''));
          if ($who === '') $who = 'applicant';
          $msg = '【応募】' . $company . ' に新しい応募が届きました。';
          log_activity($pdo_app, [
              'action'           => 'apply',
              'entity_type'      => 'job_application',
              'message_ja'       => $msg,
              'actor_type'       => 'applicant',
              'actor_username'   => $who,
              'company_name'     => $company,
              'talent_name_kana' => $who
          ]);
        }
      }
    }

  } catch (Throwable $e) {
    error_log('[submit_rireki] db save failed: ' . $e->getMessage());
  }
}

// If AJAX, return JSON and exit
if ($ajax) {
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode([
    'ok' => true,
    'token' => $outToken,
    'xls_url' => $xlsUrl,
    'applied_jobs_url' => 'https://it-future.jp/php/user_applied_jobs.php',
  ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

$fmt = 'kaigo';
$claimNext = '/rireki/kaigo/php/claim_resume.php?token=' . urlencode($outToken) . '&fmt=' . urlencode($fmt);
$loginUrl  = '/php/user_login.php?next=' . urlencode($claimNext);

$showApplyThanks = ($intent === 'apply' && $jobId > 0);

/* =========================
   success page (styles kept same)
   ========================= */
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
      --ok:#0b6b4a; --btn-bg:#f3f9ff; --btn-bd:#dbe7f5; --btn-ink:#0c4a7a;
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

      <?php if ($showApplyThanks): ?>
        <div class="apply">
          ご応募ありがとうございます。担当チームにて内容を確認のうえ、<strong>2営業日以内</strong>にご連絡いたします。
        </div>
      <?php else: ?>
        <div class="done">
          履歴書が完成しました。ダウンロードしてご確認ください。
        </div>
      <?php endif; ?>

      <div class="notice" role="note" aria-live="polite">
        PDF は環境差により体裁が100%一致しない場合があります。<strong>Excel（.xls）からの印刷</strong>を推奨します。
      </div>

      <div class="actions">
        <a class="btn" href="<?= htmlspecialchars($xlsUrl, ENT_QUOTES, 'UTF-8') ?>" download>
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

        <a class="btn" href="/saiyou.php">他の求人を探す</a>
        <a class="btn" href="https://it-future.jp/">会社ホームページへ</a>
        <a class="btn" href="/rireki/index.php">別のフォーマットで作る</a>
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
      </section>

      <hr>

      <p class="mono">トークン: <?= htmlspecialchars($outToken, ENT_QUOTES, 'UTF-8') ?> / 出力: <?= htmlspecialchars($xlsUrl, ENT_QUOTES, 'UTF-8') ?></p>
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
