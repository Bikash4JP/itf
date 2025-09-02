<?php
// /home/it-future/www/itf/rireki/php/submit_rireki.php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/adapters/adapter_xlsx.php';
require_once __DIR__ . '/log.php';

// (Optional) CSRF check once your form includes it
// if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
//   http_response_code(400); exit('Bad CSRF');
// }

// Build canonical data from POST (and FILES)
$data = buildCanonicalData($_POST, $_FILES ?? null);

// DEMO fallback (optional): if no form data provided, build a sample for quick test
if ((isset($_GET['demo']) && $_GET['demo'] == '1') || empty($data['personal']['name_kanji'])) {
  $data = [
    'personal'=>[
      'name_kana'=>'タナカ タロウ','name_kanji'=>'田中 太郎',
      'dob_yyyy'=>'1998','dob_mm'=>'05','dob_dd'=>'12','age'=>'27','gender'=>'男'
    ],
    'address'=>[
      'kana'=>'ヨコハマシ ○○ク ○○','postcode'=>'220-0000','full'=>'神奈川県横浜市○○区○○ 1-2-3'
    ],
    'contact'=>['phone'=>'080-1234-5678','email'=>'taro@example.com'],
    'education'=>[
      ['year'=>'2016','month'=>'04','school'=>'横浜高校'],
      ['year'=>'2019','month'=>'03','school'=>'横浜大学 情報工学部']
    ],
    'experience'=>[
      ['year'=>'2020','month'=>'04','company'=>'ABC株式会社','title'=>'システムエンジニア'],
      ['year'=>'2023','month'=>'06','company'=>'XYZ株式会社','title'=>'Webエンジニア']
    ],
    'licenses'=>[
      ['year'=>'2022','month'=>'12','certificate'=>'JLPT N2'],
      ['year'=>'2024','month'=>'07','certificate'=>'基本情報技術者']
    ],
    'pr'=>['self_pr'=>"責任感が強く、チーム開発が得意です。\nReact/PHP/MySQL の経験があります。"],
    'preferences'=>['hopes'=>"勤務地：横浜／東京、勤務時間：9:00-18:00、職種：Web開発"],
    'photo_path'=> '' // leave empty for demo
  ];
}

// Token-based filename
$token = bin2hex(random_bytes(32));

// Mapping file
$mappingFile = rireki_path('mappings/templateA.json');

// Output dir for PDFs/XLSX
$outDir = rireki_path('resumes/rirekisho');

// Render
$res = rireki_render_pdf($data, $mappingFile, $outDir, $token);

if (!$res['ok']) {
  log_msg('error', 'render_fail: ' . $res['err']);
  http_response_code(500);
  echo "Rirekisho render failed: " . htmlspecialchars($res['err'], ENT_QUOTES, 'UTF-8');
  exit;
}

// (Optional) you can persist minimal registry file for traceability
@file_put_contents(
  rireki_path("logs/registry_$token.txt"),
  json_encode(['token'=>$token, 'pdf'=>$res['pdf'], 'xlsx'=>$res['xlsx'], 'ts'=>date('c')], JSON_UNESCAPED_UNICODE)
);

log_msg('success', "pdf_ready token=$token pdf={$res['pdf']}");

// Redirect to success
header('Location: ./rireki_success.php?token=' . $token);
exit;
