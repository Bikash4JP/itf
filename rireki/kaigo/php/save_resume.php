<?php
// /home/it-future/www/itf/rireki/kaigo/php/save_resume.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../php/db_connect.php';

function out($ok, $arr = []) {
  echo json_encode(array_merge(['ok'=>$ok], $arr), JSON_UNESCAPED_UNICODE);
  exit;
}

$token = $_POST['token'] ?? '';
if (!preg_match('/^[a-f0-9]{32}$/', $token)) out(false, ['error'=>'Invalid token']);

$allowedCols = [
  'step_current',
  'name_romaji','name_kana','dob_year','dob_month','dob_day','age_autofill',
  'birthplace','postal','address','contact_phone','email','nationality','gender','religion','marital_status',
  'height_cm','weight_kg',
  'passport_has','passport_exp_year','passport_exp_month','passport_exp_day','passport_no',
  'past_travel_count','past_travel_details',
  'recent_entry_year','recent_entry_month','recent_entry_day','recent_exit_year','recent_exit_month','recent_exit_day',
  'current_status','status_from_year','status_from_month','status_from_day','status_to_year','status_to_month','status_to_day',
  'photo_path',
  'reason_for_resignation','planned_resign_year','planned_resign_month',
  'self_pr','motivation','preferences',
  'jp_comm_level','kanji_rw','blood_type','english_level','acquaintances_in_japan','jp_friends_count','home_country_friends_in_japan',
  'smoking','alcohol','tattoo','clothes_size','shoe_size','prayer','fasting','food_rules','hijab','work_duration_intent',
  'studying_japanese_now','studying_specialty_now','other_agency_or_facility_interview',
];

function pick($k) {
  return isset($_POST[$k]) ? trim((string)$_POST[$k]) : null;
}

$set = [];
$params = [':token'=>$token];

// single fields
foreach ($allowedCols as $col) {
  if ($col === 'step_current') {
    if (isset($_POST['step_current'])) {
      $v = (int)$_POST['step_current'];
      $set[] = "step_current=:step_current";
      $params[':step_current'] = max(1, min(7, $v));
    }
    continue;
  }
  if (array_key_exists($col, $_POST)) {
    $set[] = "$col=:$col";
    $params[":$col"] = pick($col);
  }
}

// ===== map arrays -> fixed columns (edu1..8, lic1..8, work1..8) =====
function arr($name) {
  return (isset($_POST[$name]) && is_array($_POST[$name])) ? $_POST[$name] : [];
}

// education[xxx][]
$edu = $_POST['education'] ?? null;
if (is_array($edu)) {
  $fromY = $edu['from_year'] ?? [];
  $fromM = $edu['from_month'] ?? [];
  $toY   = $edu['to_year'] ?? [];
  $toM   = $edu['to_month'] ?? [];
  $st    = $edu['status'] ?? [];
  $inst  = $edu['institution'] ?? [];
  $fac   = $edu['faculty'] ?? [];

  for ($i=1; $i<=8; $i++) {
    $idx = $i-1;
    $map = [
      "edu{$i}_from_year" => $fromY[$idx] ?? null,
      "edu{$i}_from_month"=> $fromM[$idx] ?? null,
      "edu{$i}_to_year"   => $toY[$idx] ?? null,
      "edu{$i}_to_month"  => $toM[$idx] ?? null,
      "edu{$i}_status"    => $st[$idx] ?? null,
      "edu{$i}_institution"=> $inst[$idx] ?? null,
      "edu{$i}_faculty"   => $fac[$idx] ?? null,
    ];
    foreach ($map as $col=>$val) {
      $set[] = "$col=:$col";
      $params[":$col"] = is_null($val) ? null : trim((string)$val);
    }
  }
}

// licenses[cert_year][], [cert_month][], [cert_name][]
$lic = $_POST['licenses'] ?? null;
if (is_array($lic)) {
  $yy = $lic['cert_year'] ?? [];
  $mm = $lic['cert_month'] ?? [];
  $nm = $lic['cert_name'] ?? [];
  for ($i=1; $i<=8; $i++) {
    $idx = $i-1;
    $map = [
      "lic{$i}_year"  => $yy[$idx] ?? null,
      "lic{$i}_month" => $mm[$idx] ?? null,
      "lic{$i}_name"  => $nm[$idx] ?? null,
    ];
    foreach ($map as $col=>$val) {
      $set[] = "$col=:$col";
      $params[":$col"] = is_null($val) ? null : trim((string)$val);
    }
  }
}

// work_blocks[from_year][], etc
$work = $_POST['work_blocks'] ?? null;
if (is_array($work)) {
  $fy = $work['from_year'] ?? [];
  $fm = $work['from_month'] ?? [];
  $st = $work['status'] ?? [];
  $ty = $work['to_year'] ?? [];
  $tm = $work['to_month'] ?? [];
  $org= $work['org'] ?? [];
  $jt = $work['job_title'] ?? [];
  $ds = $work['description'] ?? [];
  for ($i=1; $i<=8; $i++) {
    $idx = $i-1;
    $map = [
      "work{$i}_from_year" => $fy[$idx] ?? null,
      "work{$i}_from_month"=> $fm[$idx] ?? null,
      "work{$i}_status"    => $st[$idx] ?? null,
      "work{$i}_to_year"   => $ty[$idx] ?? null,
      "work{$i}_to_month"  => $tm[$idx] ?? null,
      "work{$i}_org"       => $org[$idx] ?? null,
      "work{$i}_job_title" => $jt[$idx] ?? null,
      "work{$i}_description"=> $ds[$idx] ?? null,
    ];
    foreach ($map as $col=>$val) {
      $set[] = "$col=:$col";
      $params[":$col"] = is_null($val) ? null : trim((string)$val);
    }
  }
}

if (!$set) out(true, ['saved'=>false]);

$sql = "UPDATE app_resume_kaigo SET ".implode(',', $set).", updated_at=NOW() WHERE token=:token";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

out(true, ['saved'=>true]);
