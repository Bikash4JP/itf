<?php
// kaigo/php/submit_rireki.php
declare(strict_types=1);

// Try to relax limits (FPM may override; use .user.ini if needed)
@ini_set('memory_limit','512M');
@ini_set('max_execution_time','120');
if (function_exists('set_time_limit')) @set_time_limit(120);

// Turn on debug with ?debug=1
$debug = isset($_GET['debug']);
if ($debug) { @ini_set('display_errors','1'); error_reporting(E_ALL); }

/**
 * IMPORTANT:
 * The Composer autoloader (vendor/autoload.php) defines a global helper named v().
 * Never define v() or arr() here—use rv()/ra() to avoid collisions.
 */
function rv($a, $k, $d=''){ return isset($a[$k]) ? (is_string($a[$k]) ? trim($a[$k]) : $a[$k]) : $d; }
function ra($a, $k){ return (isset($a[$k]) && is_array($a[$k])) ? $a[$k] : []; }

// Project bootstrap (constants, autoload)
require_once __DIR__ . '/bootstrap.php';
// XLS adapter (preserves template styles from kaigo.xlsx)
require_once __DIR__ . '/adapters/adapter_xlsx.php';

/** Build flat data that matches mapping JSON keys (singles + repeaters) */
function buildFlatKaigoData(array $post, ?array $files): array {
  // Photo upload (optional)
  $photoPath = null;
  if ($files && isset($files['photo']) && is_uploaded_file($files['photo']['tmp_name'] ?? '')) {
    $upDir = RIREKI_KAIGO_ROOT . '/uploads/photos';
    if (!is_dir($upDir)) @mkdir($upDir, 0755, true);
    $ext   = strtolower(pathinfo($files['photo']['name'] ?? '', PATHINFO_EXTENSION) ?: 'jpg');
    $fname = 'kaigo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest  = $upDir . '/' . $fname;
    if (@move_uploaded_file($files['photo']['tmp_name'], $dest)) $photoPath = $dest;
  }

  // 学歴 rows 17–20
  $edu=[];
  $sy=ra($post,'edu_start_year');  $sm=ra($post,'edu_start_month');
  $ey=ra($post,'edu_end_year');    $em=ra($post,'edu_end_month');
  $nm=ra($post,'edu_school_name'); $fa=ra($post,'edu_faculty');
  $st=ra($post,'edu_status');      // 在学中/卒業/退学
  $n=max(count($sy),count($sm),count($ey),count($em),count($nm),count($fa),count($st));
  for($i=0;$i<$n;$i++){
    $status=(string)($st[$i]??'');
    $edu[]=[
      'from_year'  => (string)($sy[$i]??''),
      'from_month' => (string)($sm[$i]??''),
      'to_year'    => $status==='在学中' ? '' : (string)($ey[$i]??''),
      'to_month'   => $status==='在学中' ? '' : (string)($em[$i]??''),
      'institution'=> (string)($nm[$i]??''),
      'faculty'    => (string)($fa[$i]??''),
    ];
  }

  // 職歴 blocks: base rows 22/25/28/31 (each is a 3-row physical block)
  $wk=[];
  $sy=ra($post,'exp_start_year');  $sm=ra($post,'exp_start_month');
  $ey=ra($post,'exp_end_year');    $em=ra($post,'exp_end_month');
  $co=ra($post,'exp_company');     $ti=ra($post,'exp_title');
  $st2=ra($post,'exp_status');     // 在職中/退職
  $ws=ra($post,'exp_work_start');  $we=ra($post,'exp_work_end');
  $wd=ra($post,'exp_work_days_per_week');
  $n=max(count($sy),count($sm),count($ey),count($em),count($co),count($ti),count($st2),count($ws),count($we),count($wd));
  for($i=0;$i<$n;$i++){
    $isCurrent=((string)($st2[$i]??''))==='在職中';
    $wk[]=[
      'from_year'         => (string)($sy[$i]??''),
      'from_month'        => (string)($sm[$i]??''),
      'to_year'           => $isCurrent ? '' : (string)($ey[$i]??''),
      'to_month'          => $isCurrent ? '' : (string)($em[$i]??''),
      'org'               => (string)($co[$i]??''),
      'job_title'         => (string)($ti[$i]??''),
      'work_time_start'   => (string)($ws[$i]??''),
      'work_time_end'     => (string)($we[$i]??''),
      'work_days_per_week'=> (string)($wd[$i]??''),
    ];
  }

  // 免許 rows 36/38/40
  $lic=[];
  $ly=ra($post,'lic_year'); $lm=ra($post,'lic_month'); $ln=ra($post,'lic_name');
  $n=max(count($ly),count($lm),count($ln));
  for($i=0;$i<$n;$i++){
    $lic[]=['cert_year'=>(string)($ly[$i]??''),'cert_month'=>(string)($lm[$i]??''),'cert_name'=>(string)($ln[$i]??'')];
  }

  return [
    // singles (keys must match mapping)
    'name_romaji' => rv($post,'personal_name_romaji',''),
    'name_kana'   => rv($post,'personal_name_kana',''),
    'dob_year'    => rv($post,'dob_yyyy',''),
    'dob_month'   => rv($post,'dob_mm',''),
    'dob_day'     => rv($post,'dob_dd',''),
    'birthplace'  => rv($post,'birthplace',''),
    'postal'      => rv($post,'postcode',''),
    'address'     => rv($post,'address_full',''),

    'nationality'    => rv($post,'nationality',''),
    'gender'         => rv($post,'gender',''),
    'religion'       => rv($post,'religion',''),
    'marital_status' => rv($post,'marital_status',''),
    'contact_phone'  => rv($post,'phone',''),
    'email'          => rv($post,'email',''),

    'height_cm' => rv($post,'height_cm',''),
    'weight_kg' => rv($post,'weight_kg',''),

    'passport_has'       => rv($post,'passport_has',''),
    'passport_exp_year'  => rv($post,'passport_exp_year',''),
    'passport_exp_month' => rv($post,'passport_exp_month',''),
    'passport_exp_day'   => rv($post,'passport_exp_day',''),
    'passport_no'        => rv($post,'passport_no',''),

    'past_travel_history'=> rv($post,'past_travel_history',''),

    'recent_entry_year'  => rv($post,'recent_entry_year',''),
    'recent_entry_month' => rv($post,'recent_entry_month',''),
    'recent_entry_day'   => rv($post,'recent_entry_day',''),
    'recent_exit_year'   => rv($post,'recent_exit_year',''),
    'recent_exit_month'  => rv($post,'recent_exit_month',''),
    'recent_exit_day'    => rv($post,'recent_exit_day',''),

    'current_status'   => rv($post,'current_status',''),
    'status_from_year' => rv($post,'status_from_year',''),
    'status_from_month'=> rv($post,'status_from_month',''),
    'status_from_day'  => rv($post,'status_from_day',''),
    'status_to_year'   => rv($post,'status_to_year',''),
    'status_to_month'  => rv($post,'status_to_month',''),
    'status_to_day'    => rv($post,'status_to_day',''),

    'reason_for_resignation'=> rv($post,'reason_for_resignation',''),

    'self_pr'    => rv($post,'self_pr',''),
    'motivation' => rv($post,'motivation',''),
    'preferences'=> rv($post,'preferences',''),

    // repeaters
    'education'   => $edu,
    'work_blocks' => $wk,
    'licenses'    => $lic,

    // file
    'photo_path'  => $photoPath,
  ];
}

try {
  $demo = isset($_GET['demo']) && $_GET['demo'] === '1';
  if ($demo) {
    // Minimal sane demo payload
    $_POST = $_POST + [
      'personal_name_romaji'=>'Taro Yamada','personal_name_kana'=>'やまだ たろう',
      'dob_yyyy'=>'1998','dob_mm'=>'04','dob_dd'=>'01',
      'postcode'=>'123-4567','address_full'=>'東京都千代田区1-2-3',
      'nationality'=>'バングラデシュ','gender'=>'男性','religion'=>'イスラム教','marital_status'=>'無し',
      'phone'=>'090-0000-0000','email'=>'taro@example.com',
      'edu_start_year'=>['2017'],'edu_start_month'=>['04'],'edu_end_year'=>['2021'],'edu_end_month'=>['03'],
      'edu_school_name'=>['△△大学'],'edu_faculty'=>['情報学部'],'edu_status'=>['卒業'],
      'exp_start_year'=>['2021'],'exp_start_month'=>['04'],'exp_end_year'=>[''],'exp_end_month'=>[''],
      'exp_company'=>['社会福祉法人〇〇 特養△△'],'exp_title'=>['介護職'],'exp_status'=>['在職中'],
      'exp_work_start'=>['09:00'],'exp_work_end'=>['18:00'],'exp_work_days_per_week'=>['5'],
      'lic_year'=>['2020'],'lic_month'=>['12'],'lic_name'=>['介護職員初任者研修'],
      'self_pr'=>'利用者様に寄り添ったケアを心掛けています。','motivation'=>'地域に貢献したい。','preferences'=>'夜勤可、都内希望'
    ];
  }

  $data = buildFlatKaigoData($_POST, $_FILES ?? null);

  $mappingFile = __DIR__ . '/../mappings/Kaigo_Template_XLS.json'; // → ../templates/kaigo.xlsx
  $outDir      = __DIR__ . '/../resumes';
  if (!is_dir($outDir)) @mkdir($outDir, 0775, true);
  $token = bin2hex(random_bytes(16));

  $res = rireki_render_pdf($data, $mappingFile, $outDir, $token);

  header('Content-Type: text/html; charset=UTF-8');
  echo "<!doctype html><html lang='ja'><head><meta charset='utf-8'>";
  echo "<title>介護履歴書 生成</title><meta name='viewport' content='width=device-width, initial-scale=1' />";
  echo "<link rel='stylesheet' href='/rireki/basic/css/recruit.css?v=5'></head><body>";
  echo "<div class='wrap'><div class='card'>";

  if (!$res['ok']) {
    echo "<h1>Rirekisho build failed</h1><p style='color:#b00'>".htmlspecialchars((string)($res['err']??'unknown'),ENT_QUOTES,'UTF-8')."</p>";
    echo "<p><a class='btn' href='".htmlspecialchars(RIREKI_KAIGO_URL.'/rireki.php',ENT_QUOTES,'UTF-8')."'>← フォームに戻る</a></p>";
    echo "</div></div></body></html>"; exit;
  }

  echo "<h1>介護履歴書 生成完了</h1>";
  if (!empty($res['xls']) && is_file($res['xls'])) {
    $dl = RIREKI_KAIGO_URL . "/resumes/" . basename($res['xls']);
    echo "<p class='links'><a class='btn primary' href='".htmlspecialchars($dl,ENT_QUOTES,'UTF-8')."' download>Excelをダウンロード</a> ";
    echo "<a class='btn' href='".htmlspecialchars(RIREKI_KAIGO_URL.'/rireki.php',ENT_QUOTES,'UTF-8')."'>← 入力に戻る</a></p>";
  }

  // Optional heavier preview: ?preview=1
  $doPreview = isset($_GET['preview']) && $_GET['preview'] === '1';
  if ($doPreview && !empty($res['xls']) && is_file($res['xls'])) {
    try {
      $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($res['xls']);
      if (method_exists($reader,'setReadDataOnly'))         $reader->setReadDataOnly(false);
      if (method_exists($reader,'setIncludeCharts'))        $reader->setIncludeCharts(false);
      if (method_exists($reader,'setPreCalculateFormulas')) $reader->setPreCalculateFormulas(false);
      $book  = $reader->load($res['xls']);
      $sheet = $book->getActiveSheet();

      if (function_exists('cropSheetToExactRange')) cropSheetToExactRange($sheet, 'A', 'AI', 67);

      $htmlWriter = new \PhpOffice\PhpSpreadsheet\Writer\Html($book);
      if (method_exists($htmlWriter,'setPreCalculateFormulas')) $htmlWriter->setPreCalculateFormulas(false);
      if (method_exists($htmlWriter,'setGenerateSheetNavigationBlock')) $htmlWriter->setGenerateSheetNavigationBlock(false);
      if (method_exists($htmlWriter,'setUseInlineCss')) $htmlWriter->setUseInlineCss(true);
      if (method_exists($book,'getIndex') && method_exists($htmlWriter,'setSheetIndex')) {
        $htmlWriter->setSheetIndex($book->getIndex($sheet));
      }
      ob_start(); $htmlWriter->save('php://output'); $html = ob_get_clean();

      echo "<div class='preview' style='margin-top:16px'><div class='page'><h2>プレビュー（A1:AI67）</h2>{$html}</div></div>";
      $book->disconnectWorksheets(); unset($book,$sheet);
    } catch (\Throwable $e) {
      echo "<p class='note'>プレビュー生成に失敗しましたが、Excelのダウンロードは可能です。(" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ")</p>";
    }
  } else {
    echo "<p class='note'>プレビューは省略（URL に <code>?preview=1</code> を付けると <code>A1:AI67</code> を表示）。</p>";
  }

  echo "</div></div></body></html>";
} catch (\Throwable $e) {
  http_response_code(500);
  echo "Rirekisho build failed: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
