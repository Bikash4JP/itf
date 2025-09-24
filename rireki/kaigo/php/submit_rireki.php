<?php
// /rireki/kaigo/php/submit_rireki.php
error_reporting(E_ALL);
ini_set('display_errors', isset($_GET['debug']) ? '1' : '0');

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/adapters/adapter_xlsx.php';
require_once __DIR__ . '/validators.php';

$mappingFile = rireki_path('mappings/templateB.json');
$outDir      = rireki_path('resumes');
@mkdir($outDir, 0755, true);

/* ============================================================
 * PDF endpoint (HTML -> mPDF) — NO PhpSpreadsheet PdfWriter used
 *   /rireki/kaigo/php/submit_rireki.php?download=pdf&token=<hex|latest>[&diag=1]
 * ============================================================ */
if (isset($_GET['download'], $_GET['token']) && $_GET['download'] === 'pdf') {
  $rawToken = (string)$_GET['token'];
  $diag     = isset($_GET['diag']);

  // resolve token -> json
  if ($rawToken === 'latest') {
    $files = glob(rtrim($outDir,'/').'/*.json');
    if (!$files) {
      http_response_code(404);
      header('Content-Type: text/plain; charset=UTF-8');
      echo "PDF build failed: No snapshots found. Submit the form first.";
      exit;
    }
    usort($files, fn($a,$b)=>filemtime($b)<=>filemtime($a));
    $json  = $files[0];
    $token = basename($json, '.json');
  } else {
    $token = preg_replace('/[^a-f0-9]/i', '', $rawToken);
    $json  = rtrim($outDir,'/').'/'.$token.'.json';
  }

  // diagnostics
  if ($diag) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "== PDF Diagnostic (HTML->mPDF) ==\n";
    echo "token: $token\n";
    echo "json: $json (exists=".((int)file_exists($json)).", readable=".((int)is_readable($json)).", size=".(@filesize($json)?:0)." bytes)\n";
    $vendorA = rireki_path('vendor/autoload.php');
    $vendorB = dirname(__DIR__, 2).'/vendor/autoload.php';
    echo "vendorA: $vendorA (".(is_readable($vendorA)?'yes':'NO').")\n";
    echo "vendorB: $vendorB (".(is_readable($vendorB)?'yes':'NO').")\n";
    if (is_readable($vendorA)) require_once $vendorA; elseif (is_readable($vendorB)) require_once $vendorB;
    echo "class_exists mPDF: ".(class_exists('\\Mpdf\\Mpdf')?'yes':'NO')."\n";
    $tmpDir = rireki_path('tmp');
    echo "tmpDir: $tmpDir (exists=".((int)is_dir($tmpDir)).", writable=".((int)is_writable($tmpDir)).")\n";
    exit;
  }

  // normal stream
  try {
    if (!is_readable($json)) {
      throw new RuntimeException('Snapshot JSON not found. Re-generate from the form.');
    }
    $raw  = file_get_contents($json);
    $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

    $tmpDir = rireki_path('tmp');
    if (!is_dir($tmpDir)) @mkdir($tmpDir, 0755, true);

    $resPdf = rireki_make_pdf_via_html($data, $mappingFile, $outDir, $token, $tmpDir);
    if (!$resPdf['ok']) throw new RuntimeException($resPdf['err'] ?? 'Unknown PDF error');
    $pdfPath = $resPdf['pdf'];

    if (function_exists('ob_get_length') && ob_get_length()) @ob_end_clean();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="rireki_'.$token.'.pdf"');
    header('Content-Length: '.filesize($pdfPath));
    readfile($pdfPath);
    exit;

  } catch (Throwable $e) {
    $logDir = rireki_path('logs');
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    @file_put_contents($logDir.'/pdf_error.log',
      '['.date('Y-m-d H:i:s')."] ".$e->getMessage()."\n".$e->getTraceAsString()."\n", FILE_APPEND);
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'PDF build failed: '.$e->getMessage();
    exit;
  }
}

/* ============================================================
 * Normal submit flow: POST (or demo) -> build XLS + save JSON
 * ============================================================ */

// helper: reshape field-wise arrays into row-wise
function _reshape_rows(array $group, array $fields): array {
  $rows = [];
  $len = 0;
  foreach ($fields as $f) $len = max($len, isset($group[$f]) ? count((array)$group[$f]) : 0);
  for ($i=0; $i<$len; $i++) {
    $row = [];
    foreach ($fields as $f) $row[$f] = trim($group[$f][$i] ?? '');
    if (implode('', $row) !== '') $rows[] = $row;
  }
  return $rows;
}

// Build input data
if (isset($_GET['demo'])) {
  $data = [
    'name_romaji' => 'TARO YAMADA',
    'name_kana'   => 'やまだ たろう',
    'dob_year'    => '1998','dob_month'=>'04','dob_day'=>'01',
    'birthplace'  => '東京',
    'postal'      => '123-4567',
    'address'     => '東京都千代田区1-2-3',
    'nationality' => 'ネパール国籍',
    'gender'      => '男性',
    'religion'    => '仏教',
    'marital_status'=>'無し',
    'contact_phone'=>'090-1111-2222',
    'email'       => 'taro@example.com',
    'height_cm'   => '170','weight_kg'=>'60',
    'passport_has'=>'有り','passport_no'=>'AB123456',
    'self_pr'     => '責任感が強く、学習意欲が高いです。',
    'motivation'  => '介護の現場で働きたい。',
    'preferences' => '東京23区で勤務希望',
    'planned_resign_year'  => '2026',
    'planned_resign_month' => '03',
    'education'   => [
      ['from_year'=>'2015','from_month'=>'04','to_year'=>'2018','to_month'=>'03','institution'=>'ABC高校','faculty'=>'普通科'],
      ['from_year'=>'2018','from_month'=>'04','to_year'=>'2022','to_month'=>'03','institution'=>'XYZ大学','faculty'=>'福祉学部'],
    ],
    'work_blocks'=>[
      ['from_year'=>'2022','from_month'=>'04','to_year'=>'2024','to_month'=>'03','org'=>'介護施設ABC','job_title'=>'介護職','work_time_start'=>'09:00','work_time_end'=>'18:00','work_days_per_week'=>'5']
    ],
    'licenses'=>[
      ['cert_year'=>'2021','cert_month'=>'12','cert_name'=>'日本語能力試験N2']
    ]
  ];
} else {
  $data = $_POST;

  $data['planned_resign_year']  = isset($data['planned_resign_year'])  ? trim($data['planned_resign_year'])  : '';
  $data['planned_resign_month'] = isset($data['planned_resign_month']) ? trim($data['planned_resign_month']) : '';

  if (!empty($data['education']) && is_array($data['education']) && isset($data['education']['from_year'])) {
    $data['education'] = _reshape_rows($data['education'], ['from_year','from_month','to_year','to_month','institution','faculty']);
  }
  if (!empty($data['work_blocks']) && is_array($data['work_blocks']) && isset($data['work_blocks']['from_year'])) {
    $data['work_blocks'] = _reshape_rows($data['work_blocks'], ['from_year','from_month','to_year','to_month','org','job_title','work_time_start','work_time_end','work_days_per_week']);
  }
  if (!empty($data['licenses']) && is_array($data['licenses']) && isset($data['licenses']['cert_year'])) {
    $data['licenses'] = _reshape_rows($data['licenses'], ['cert_year','cert_month','cert_name']);
  }

  // Photo -> photo_path
  if (isset($_FILES['photo']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $dir = rireki_path('uploads/photos');
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png'], true)) $ext = 'jpg';
    $dest = $dir . '/' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) $data['photo_path'] = $dest;
  }

  if (empty($data['past_travel_history'])) {
    $cnt = trim($data['past_travel_count'] ?? '');
    $det = trim($data['past_travel_details'] ?? '');
    $data['past_travel_history'] = ($cnt !== '' || $det !== '') ? ("回数: ".$cnt." / ".$det) : '';
  }
}

// Build XLS and snapshot
$token = bin2hex(random_bytes(16));
$res = rireki_render_pdf($data, $mappingFile, $outDir, $token);
if (!$res['ok']) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=UTF-8');
  echo "Kaigo Rirekisho build failed: " . ($res['err'] ?? 'unknown');
  exit;
}
file_put_contents(rtrim($outDir,'/').'/'.$token.'.json', json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

// Links
$xlsName = basename($res['xls']);
$xlsUrl  = '/rireki/kaigo/resumes/' . $xlsName;
$pdfUrl  = '/rireki/kaigo/php/submit_rireki.php?download=pdf&token=' . urlencode($token);
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
    .links a { display:inline-block; margin-right: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; border: 1px solid #dbe7f5; background: #f3f9ff; color:#0c4a7a; }
    .links a:hover{ background:#e9f5ff; }
  </style>
</head>
<body>
  <div class="card">
    <h1>介護用 履歴書を生成しました</h1>
    <div class="links">
      <a href="<?=$xlsUrl?>" download>Excel（.xls）をダウンロード</a>
      <a href="<?=$pdfUrl?>">PDF でダウンロード</a>
      <a href="/rireki/index.php">別のフォーマットを選ぶ</a>
    </div>
  </div>
</body>
</html>
