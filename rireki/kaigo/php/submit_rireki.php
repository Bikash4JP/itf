<?php
// /rireki/kaigo/php/submit_rireki.php
error_reporting(E_ALL);
ini_set('display_errors', isset($_GET['debug']) ? '1' : '0');

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/adapters/adapter_xlsx.php';
require_once __DIR__ . '/validators.php';

// Directories
$mappingFile = rireki_path('mappings/templateB.json');
$outDir      = rireki_path('resumes');
$tmpDir      = rireki_path('tmp');
@mkdir($outDir, 0755, true);
@mkdir($tmpDir, 0755, true);

// ============ Utilities ============
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

// ============ PDF DOWNLOAD (by token) ============
if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
  @ini_set('memory_limit', '512M');
  @set_time_limit(120);
  @ini_set('pcre.backtrack_limit', '5000000');
  @ini_set('pcre.recursion_limit', '5000000');

  set_exception_handler(function($e){
    _plain_fail("PDF build failed: " . $e->getMessage(), 500);
  });

  $token = preg_replace('/[^a-f0-9]/', '', $_GET['token'] ?? '');
  if ($token === '') _plain_fail("PDF build failed: Missing token.", 400);

  $jsonPath = rtrim($outDir,'/') . '/' . $token . '.json';
  if (!is_file($jsonPath)) _plain_fail("PDF build failed: Snapshot JSON not found at $jsonPath", 404);

  $json = @file_get_contents($jsonPath);
  if ($json === false) _plain_fail("PDF build failed: Snapshot JSON unreadable at $jsonPath", 500);

  $data = json_decode($json, true);
  if (!is_array($data)) _plain_fail("PDF build failed: JSON parse error at $jsonPath", 500);

  $resPdf = rireki_make_pdf_via_html($data, $mappingFile, $outDir, $token, $tmpDir);
  if (empty($resPdf['ok'])) {
    _plain_fail("PDF build failed: " . ($resPdf['err'] ?? 'unknown'), 500);
  }

  $pdfPath = $resPdf['pdf'] ?? null;
  if (!$pdfPath || !is_readable($pdfPath)) {
    _plain_fail("PDF build failed: File missing after build.", 500);
  }

  header('Content-Type: application/pdf');
  header('Content-Disposition: attachment; filename="rirekisho_kaigo.pdf"');
  header('Content-Length: ' . filesize($pdfPath));
  readfile($pdfPath);
  exit;
}

// ============ FORM SUBMIT (build XLS only) ============
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
      ['from_year'=>'2022','from_month'=>'04','to_year'=>'2024','to_month'=>'03','org'=>'介護施設ABC','job_title'=>'介護職','work_time_start'=>'09:00','work_time_end'=>'18:00','work_days_per_week'=>'5','description'=>'入浴介助、排泄介助、記録業務など']
    ],
    'licenses'=>[
      ['cert_year'=>'2021','cert_month'=>'12','cert_name'=>'日本語能力試験N2'],
      ['cert_year'=>'2022','cert_month'=>'07','cert_name'=>'介護職員初任者研修'],
    ],
  ];
  $jobId = 0;
} else {
  $data = $_POST;

  // capture job context from form
  $jobId = isset($data['job_id']) ? (int)$data['job_id'] : 0;

  // normalize planned resignation date
  $data['planned_resign_year']  = trim($data['planned_resign_year']  ?? '');
  $data['planned_resign_month'] = trim($data['planned_resign_month'] ?? '');

  // reshape repeater groups (include description too)
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

  // photo upload -> photo_path
  if (isset($_FILES['photo']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $dir = rireki_path('uploads/photos');
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png'], true)) $ext = 'jpg';
    $dest = $dir . '/' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) $data['photo_path'] = $dest;
  }
}

// ----- persist source metadata for rireki_list.php -----
$data['_source'] = ($jobId > 0) ? 'job' : 'open';
$data['_job_id'] = $jobId;
$data['_job_title'] = '';

// if we have a job id, try to fetch the title now (so list can show it without DB)
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

// Build XLS
$token = bin2hex(random_bytes(16));
$resX = rireki_render_xls_only($data, $mappingFile, $outDir, $token);
if (empty($resX['ok'])) _plain_fail("Kaigo Rirekisho build failed: " . ($resX['err'] ?? 'unknown'), 500);

// Save JSON snapshot
@file_put_contents($outDir . '/' . $token . '.json', json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

$xlsUrl = '/rireki/kaigo/resumes/' . basename($resX['xls']);
$pdfUrl = '/rireki/kaigo/php/submit_rireki.php?download=pdf&token=' . $token;

// Success page
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <title>介護用 履歴書 生成完了</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body { font-family: ui-sans-serif, system-ui, "Segoe UI", Roboto, "Noto Sans JP","Hiragino Kaku Gothic ProN",Meiryo, Arial, sans-serif; margin: 20px; }
    .card { max-width: 900px; margin: 0 auto; background:#fff; border:1px solid #e8eef6; border-radius: 12px; padding: 18px; }
    h1 { margin: 0 0 8px; font-size: 20px; }
    .msg { background:#f0fcfd; border:1px solid #cfe2ff; padding:12px 14px; border-radius:10px; color:#0b3772; margin-bottom:12px; }
    .links a { display:inline-block; margin-right: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; border: 1px solid #dbe7f5; background: #f3f9ff; color:#0c4a7a; }
    .links a:hover{ background:#e9f5ff; }
    .note { color:#667085; font-size:12px; margin-top:6px; }
  </style>
</head>
<body>
  <div class="card">
    <h1>介護用 履歴書を生成しました</h1>

    <?php if ($jobId > 0): ?>
      <div class="msg">ご応募ありがとうございます。担当チームにて内容を確認のうえ、<strong>2営業日以内</strong>にご連絡いたします。</div>
    <?php endif; ?>

    <div class="links">
      <a href="<?=$xlsUrl?>" download>Excel（.xls）をダウンロード</a>
      <a href="<?=$pdfUrl?>">PDF でダウンロード</a>
      <?php if ($jobId > 0): ?>
        <a href="/php/job_details.php?job_id=<?=$jobId?>">求人詳細へ戻る</a>
      <?php endif; ?>
      <a href="/rireki/index.php">別のフォーマットを選ぶ</a>
    </div>
    <p class="note">※ PDFはクリック時に生成します。失敗時はテキストで原因を表示します。</p>
  </div>
</body>
</html>
