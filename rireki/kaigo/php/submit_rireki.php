<?php
// /rireki/kaigo/php/submit_rireki.php
error_reporting(E_ALL);
ini_set('display_errors', isset($_GET['debug']) ? '1' : '0');

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/adapters/adapter_xlsx.php';
require_once __DIR__ . '/validators.php';

// mapping + outdir
$mappingFile = rireki_path('mappings/templateB.json');
$outDir      = rireki_path('resumes');
@mkdir($outDir, 0750, true);

// Demo or build data
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
  $data = $_POST; // TODO: hook with canonical builder if needed
}

$token = bin2hex(random_bytes(16));
$res = rireki_render_pdf($data, $mappingFile, $outDir, $token);
if (!$res['ok']) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=UTF-8');
  echo "Kaigo Rirekisho build failed: " . ($res['err'] ?? 'unknown');
  exit;
}

$xlsName = basename($res['xls']);
$xlsUrl  = '/rireki/kaigo/resumes/' . $xlsName;
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
      <a href="/rireki/index.php">別のフォーマットを選ぶ</a>
    </div>
  </div>
</body>
</html>
