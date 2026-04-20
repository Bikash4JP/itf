<?php
// /home/it-future/www/itf/rireki/kaigo/rireki.php

ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);

session_start();

// ✅ use existing DB connection (DO NOT create another)
require_once $_SERVER['DOCUMENT_ROOT'] . '/php/db_connect.php'; // expects $pdo (PDO)

// --- Auth guard: must be logged in to access the form ---
require_once $_SERVER['DOCUMENT_ROOT'] . '/php/user_auth.php';
if (!app_is_logged_in()) {
  header('Location: /php/user_login.php?next=' . urlencode($_SERVER['REQUEST_URI']), true, 302);
  exit;
}

// --- Smart redirect: if user already has a kaigo profile, send them to preview ---
// Unless they explicitly passed ?edit=1 to force the form (e.g. from the "Edit" button)
if (empty($_GET['edit']) && empty($_GET['job_id'])) {
  try {
    $pdo_auth = app_pdo();
    $uid_auth  = (int) app_user_id();
    if ($uid_auth > 0) {
      $existing = app_load_profile($pdo_auth, $uid_auth, 'kaigo');
      if ($existing) {
        header('Location: /rireki/kaigo/php/rireki_preview.php?flow=profile_only', true, 302);
        exit;
      }
    }
  } catch (Throwable $e) {
    // DB error: fall through to show the form normally
  }
}

$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

/* =========================
   Helpers
   ========================= */
function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

function normalize_token($t): string {
  $t = strtolower(trim((string)$t));
  return preg_match('/^[a-f0-9]{32}$/', $t) ? $t : '';
}

function ensure_row(PDO $pdo, string $token, int $job_id): array {
  // find
  $st = $pdo->prepare("SELECT * FROM app_resume_kaigo WHERE token = :t LIMIT 1");
  $st->execute([':t' => $token]);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if ($row) {
    // optional: keep job_id if passed
    if ($job_id > 0 && (int)($row['job_id'] ?? 0) !== $job_id) {
      $up = $pdo->prepare("UPDATE app_resume_kaigo SET job_id = :j WHERE token = :t");
      $up->execute([':j' => $job_id, ':t' => $token]);
      $row['job_id'] = $job_id;
    }
    return $row;
  }

  // create
  $ins = $pdo->prepare("INSERT INTO app_resume_kaigo (token, job_id, step_current) VALUES (:t, :j, 1)");
  $ins->execute([':t' => $token, ':j' => ($job_id > 0 ? $job_id : null)]);
  $st->execute([':t' => $token]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return $row ?: [];
}

function allowed_columns(): array {
  // ✅ keep strict whitelist (security)
  return [
    'step_current',
    'name_romaji','name_kana','dob_year','dob_month','dob_day','age_autofill','birthplace','postal','address','contact_phone','email',
    'nationality','gender','religion','marital_status','height_cm','weight_kg',
    'passport_has','passport_exp_year','passport_exp_month','passport_exp_day','passport_no','past_travel_count','past_travel_details',
    'recent_entry_year','recent_entry_month','recent_entry_day','recent_exit_year','recent_exit_month','recent_exit_day',
    'current_status','status_from_year','status_from_month','status_from_day','status_to_year','status_to_month','status_to_day',
    'photo_path',
    // edu1..8
    'edu1_from_year','edu1_from_month','edu1_to_year','edu1_to_month','edu1_status','edu1_institution','edu1_faculty',
    'edu2_from_year','edu2_from_month','edu2_to_year','edu2_to_month','edu2_status','edu2_institution','edu2_faculty',
    'edu3_from_year','edu3_from_month','edu3_to_year','edu3_to_month','edu3_status','edu3_institution','edu3_faculty',
    'edu4_from_year','edu4_from_month','edu4_to_year','edu4_to_month','edu4_status','edu4_institution','edu4_faculty',
    'edu5_from_year','edu5_from_month','edu5_to_year','edu5_to_month','edu5_status','edu5_institution','edu5_faculty',
    'edu6_from_year','edu6_from_month','edu6_to_year','edu6_to_month','edu6_status','edu6_institution','edu6_faculty',
    'edu7_from_year','edu7_from_month','edu7_to_year','edu7_to_month','edu7_status','edu7_institution','edu7_faculty',
    'edu8_from_year','edu8_from_month','edu8_to_year','edu8_to_month','edu8_status','edu8_institution','edu8_faculty',
    // lic1..8
    'lic1_year','lic1_month','lic1_name',
    'lic2_year','lic2_month','lic2_name',
    'lic3_year','lic3_month','lic3_name',
    'lic4_year','lic4_month','lic4_name',
    'lic5_year','lic5_month','lic5_name',
    'lic6_year','lic6_month','lic6_name',
    'lic7_year','lic7_month','lic7_name',
    'lic8_year','lic8_month','lic8_name',
    // work1..8
    'work1_from_year','work1_from_month','work1_status','work1_to_year','work1_to_month','work1_org','work1_job_title','work1_description',
    'work2_from_year','work2_from_month','work2_status','work2_to_year','work2_to_month','work2_org','work2_job_title','work2_description',
    'work3_from_year','work3_from_month','work3_status','work3_to_year','work3_to_month','work3_org','work3_job_title','work3_description',
    'work4_from_year','work4_from_month','work4_status','work4_to_year','work4_to_month','work4_org','work4_job_title','work4_description',
    'work5_from_year','work5_from_month','work5_status','work5_to_year','work5_to_month','work5_org','work5_job_title','work5_description',
    'work6_from_year','work6_from_month','work6_status','work6_to_year','work6_to_month','work6_org','work6_job_title','work6_description',
    'work7_from_year','work7_from_month','work7_status','work7_to_year','work7_to_month','work7_org','work7_job_title','work7_description',
    'work8_from_year','work8_from_month','work8_status','work8_to_year','work8_to_month','work8_org','work8_job_title','work8_description',

    'reason_for_resignation','planned_resign_year','planned_resign_month',
    'self_pr','motivation','preferences',
    'jp_comm_level','kanji_rw','blood_type','english_level','acquaintances_in_japan','jp_friends_count','home_country_friends_in_japan',
    'smoking','alcohol','tattoo','clothes_size','shoe_size','prayer','fasting','food_rules','hijab','work_duration_intent',
    'studying_japanese_now','studying_specialty_now','other_agency_or_facility_interview',
  ];
}

function update_by_token(PDO $pdo, string $token, array $data): void {
  $allow = array_flip(allowed_columns());
  $set = [];
  $params = [':t' => $token];

  foreach ($data as $k => $v) {
    if (!isset($allow[$k])) continue;
    $set[] = "`$k` = :$k";
    $params[":$k"] = $v;
  }

  if (!$set) return;
  $sql = "UPDATE app_resume_kaigo SET " . implode(", ", $set) . " WHERE token = :t";
  $st = $pdo->prepare($sql);
  $st->execute($params);
}

function map_post_to_columns(array $post): array {
  $out = [];

  // direct fields: name matches columns (most are direct)
  $direct = [
    'step_current',
    'name_romaji','name_kana','dob_year','dob_month','dob_day','age_autofill','birthplace','postal','address','contact_phone','email',
    'nationality','gender','religion','marital_status','height_cm','weight_kg',
    'passport_has','passport_exp_year','passport_exp_month','passport_exp_day','passport_no','past_travel_count','past_travel_details',
    'recent_entry_year','recent_entry_month','recent_entry_day','recent_exit_year','recent_exit_month','recent_exit_day',
    'current_status','status_from_year','status_from_month','status_from_day','status_to_year','status_to_month','status_to_day',
    'reason_for_resignation','planned_resign_year','planned_resign_month',
    'self_pr','motivation','preferences',
    'jp_comm_level','kanji_rw','blood_type','english_level','acquaintances_in_japan','jp_friends_count','home_country_friends_in_japan',
    'smoking','alcohol','tattoo','clothes_size','shoe_size','prayer','fasting','food_rules','hijab','work_duration_intent',
    'studying_japanese_now','studying_specialty_now','other_agency_or_facility_interview',
  ];
  foreach ($direct as $k) {
    if (array_key_exists($k, $post)) $out[$k] = is_string($post[$k]) ? trim($post[$k]) : $post[$k];
  }

  // education arrays -> edu1..8
  $edu = $post['education'] ?? null;
  if (is_array($edu)) {
    $fy = $edu['from_year'] ?? [];
    $fm = $edu['from_month'] ?? [];
    $ty = $edu['to_year'] ?? [];
    $tm = $edu['to_month'] ?? [];
    $st = $edu['status'] ?? [];
    $in = $edu['institution'] ?? [];
    $fa = $edu['faculty'] ?? [];

    for ($i=0; $i<8; $i++) {
      $n = $i+1;
      $out["edu{$n}_from_year"] = trim((string)($fy[$i] ?? ''));
      $out["edu{$n}_from_month"] = trim((string)($fm[$i] ?? ''));
      $out["edu{$n}_to_year"] = trim((string)($ty[$i] ?? ''));
      $out["edu{$n}_to_month"] = trim((string)($tm[$i] ?? ''));
      $out["edu{$n}_status"] = trim((string)($st[$i] ?? ''));
      $out["edu{$n}_institution"] = trim((string)($in[$i] ?? ''));
      $out["edu{$n}_faculty"] = trim((string)($fa[$i] ?? ''));
    }
  }

  // licenses arrays -> lic1..8
  $lic = $post['licenses'] ?? null;
  if (is_array($lic)) {
    $cy = $lic['cert_year'] ?? [];
    $cm = $lic['cert_month'] ?? [];
    $cn = $lic['cert_name'] ?? [];
    for ($i=0; $i<8; $i++) {
      $n = $i+1;
      $out["lic{$n}_year"] = trim((string)($cy[$i] ?? ''));
      $out["lic{$n}_month"] = trim((string)($cm[$i] ?? ''));
      $out["lic{$n}_name"] = trim((string)($cn[$i] ?? ''));
    }
  }

  // work arrays -> work1..8
  $wk = $post['work_blocks'] ?? null;
  if (is_array($wk)) {
    $fy = $wk['from_year'] ?? [];
    $fm = $wk['from_month'] ?? [];
    $st = $wk['status'] ?? [];
    $ty = $wk['to_year'] ?? [];
    $tm = $wk['to_month'] ?? [];
    $org= $wk['org'] ?? [];
    $jt = $wk['job_title'] ?? [];
    $ds = $wk['description'] ?? [];
    for ($i=0; $i<8; $i++) {
      $n = $i+1;
      $out["work{$n}_from_year"] = trim((string)($fy[$i] ?? ''));
      $out["work{$n}_from_month"] = trim((string)($fm[$i] ?? ''));
      $out["work{$n}_status"] = trim((string)($st[$i] ?? ''));
      $out["work{$n}_to_year"] = trim((string)($ty[$i] ?? ''));
      $out["work{$n}_to_month"] = trim((string)($tm[$i] ?? ''));
      $out["work{$n}_org"] = trim((string)($org[$i] ?? ''));
      $out["work{$n}_job_title"] = trim((string)($jt[$i] ?? ''));
      $out["work{$n}_description"] = trim((string)($ds[$i] ?? ''));
    }
  }

  return $out;
}

function json_out(array $arr, int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

/* =========================
   Token bootstrap
   ========================= */
$token = normalize_token($_GET['token'] ?? ($_POST['token'] ?? ''));
if (!$token) {
  $token = bin2hex(random_bytes(16));
  $_SESSION['kaigo_token'] = $token;

  // redirect so URL has token (refresh-safe)
  $qs = ['token' => $token];
  if ($job_id > 0) $qs['job_id'] = $job_id;
  header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($qs));
  exit;
}
$_SESSION['kaigo_token'] = $token;

$row = ensure_row($pdo, $token, $job_id);

/* =========================
   AJAX API (same file)
   - save draft to DB
   - upload photo to server and update photo_path
   ========================= */
$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {
  // security: token must match
  $pt = normalize_token($_POST['token'] ?? '');
  if (!$pt || $pt !== $token) json_out(['ok'=>false,'error'=>'invalid token'], 400);

  $data = map_post_to_columns($_POST);

  // step_current: if client sends current step number
  if (isset($_POST['_step_current'])) {
    $sc = (int)$_POST['_step_current'];
    if ($sc >= 1 && $sc <= 7) $data['step_current'] = $sc;
  }

  update_by_token($pdo, $token, $data);
  json_out(['ok'=>true]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_photo') {
  $pt = normalize_token($_POST['token'] ?? '');
  if (!$pt || $pt !== $token) json_out(['ok'=>false,'error'=>'invalid token'], 400);

  if (!isset($_FILES['photo']) || !is_uploaded_file($_FILES['photo']['tmp_name'])) {
    json_out(['ok'=>false,'error'=>'no file'], 400);
  }

  $f = $_FILES['photo'];
  if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
    json_out(['ok'=>false,'error'=>'upload error'], 400);
  }

  // limit ~5MB
  if (($f['size'] ?? 0) > 5 * 1024 * 1024) {
    json_out(['ok'=>false,'error'=>'file too large (max 5MB)'], 400);
  }

  // MIME validate
  $fi = finfo_open(FILEINFO_MIME_TYPE);
  $mime = finfo_file($fi, $f['tmp_name']);
  unset($fi);

  $ext = '';
  if ($mime === 'image/jpeg') $ext = 'jpg';
  elseif ($mime === 'image/png') $ext = 'png';
  else json_out(['ok'=>false,'error'=>'only JPEG/PNG allowed'], 400);

  // store path (web accessible)
  $relDir = '/rireki/kaigo/uploads/photos';
  $absDir = $_SERVER['DOCUMENT_ROOT'] . $relDir;
  if (!is_dir($absDir)) @mkdir($absDir, 0755, true);

  $name = $token . '_' . date('Ymd_His') . '.' . $ext;
  $abs = $absDir . '/' . $name;
  $rel = $relDir . '/' . $name;

  if (!move_uploaded_file($f['tmp_name'], $abs)) {
    json_out(['ok'=>false,'error'=>'failed to save file'], 500);
  }

  // save to DB
  update_by_token($pdo, $token, ['photo_path' => $rel]);
  json_out(['ok'=>true,'photo_path'=>$rel]);
}

/* =========================
   Prefill rows
   ========================= */
function count_non_empty_edu(array $row): int {
  $max = 0;
  for ($i=1; $i<=8; $i++) {
    $has = trim((string)($row["edu{$i}_institution"] ?? '')) !== '' ||
           trim((string)($row["edu{$i}_from_year"] ?? '')) !== '' ||
           trim((string)($row["edu{$i}_to_year"] ?? '')) !== '';
    if ($has) $max = $i;
  }
  return max(1, $max);
}
function count_non_empty_lic(array $row): int {
  $max = 0;
  for ($i=1; $i<=8; $i++) {
    $has = trim((string)($row["lic{$i}_name"] ?? '')) !== '' ||
           trim((string)($row["lic{$i}_year"] ?? '')) !== '';
    if ($has) $max = $i;
  }
  return max(1, $max);
}
function count_non_empty_work(array $row): int {
  $max = 0;
  for ($i=1; $i<=8; $i++) {
    $has = trim((string)($row["work{$i}_org"] ?? '')) !== '' ||
           trim((string)($row["work{$i}_from_year"] ?? '')) !== '' ||
           trim((string)($row["work{$i}_description"] ?? '')) !== '';
    if ($has) $max = $i;
  }
  return max(1, $max);
}

$eduRows = count_non_empty_edu($row);
$licRows = count_non_empty_lic($row);
$workRows = count_non_empty_work($row);

$hasPhoto = trim((string)($row['photo_path'] ?? '')) !== '';
$photoPath = (string)($row['photo_path'] ?? '');
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <title>介護向け 履歴書フォーム｜ITF</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex,follow">
  <link rel="icon" href="https://it-future.jp/images/favicon-32x32.png" sizes="32x32">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=Noto+Sans+JP:wght@400;700;900&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --sky:#3b9eff; --sky2:#1e78e8; --violet:#7c3aed;
      --ink:#e6edf3; --muted:#8b949e; --border:rgba(255,255,255,.1);
      --ok:#3fb950; --danger:#f85149;
      --input-bg:rgba(255,255,255,.06); --input-bd:rgba(255,255,255,.14);
    }
    html, body { min-height:100%; font-family:'Inter','Noto Sans JP',system-ui,sans-serif; }
    body {
      background:
        radial-gradient(ellipse 80% 55% at 50% -5%,rgba(59,158,255,.2) 0%,transparent 65%),
        radial-gradient(ellipse 55% 40% at 85% 95%,rgba(124,58,237,.15) 0%,transparent 60%),
        linear-gradient(155deg,#060d1a 0%,#0d1f3c 50%,#080f20 100%);
      background-attachment:fixed; color:var(--ink);
    }

    /* Appbar */
    .appbar { position:sticky; top:0; z-index:50;
      background:rgba(6,13,26,.88); backdrop-filter:blur(18px) saturate(180%);
      border-bottom:1px solid var(--border); }
    .appbar .wrap { max-width:1200px; margin:0 auto; padding:0 16px;
      height:60px; display:flex; align-items:center; gap:12px; }
    .appbar .nav-logo { display:flex; align-items:center; gap:9px; text-decoration:none; }
    .appbar .nav-logo-mark { width:32px; height:32px; border-radius:9px; overflow:hidden;
      background:linear-gradient(135deg,var(--sky),var(--violet)); flex-shrink:0; }
    .appbar .nav-logo-mark img { width:100%; height:100%; object-fit:cover; }
    .appbar h1 { font-size:15px; font-weight:800; color:var(--ink); }
    .appbar .home { margin-left:auto; font-size:13px; color:var(--muted); text-decoration:none;
      display:flex; align-items:center; gap:5px;
      padding:6px 12px; border-radius:8px; border:1px solid var(--border);
      transition:color .2s, border-color .2s; }
    .appbar .home:hover { color:var(--ink); border-color:rgba(255,255,255,.2); }

    /* Layout */
    .wrap { max-width:1200px; margin:0 auto; padding:0 14px; }

    /* Card */
    .card {
      background:rgba(13,17,27,.65); backdrop-filter:blur(16px) saturate(160%);
      border:1px solid var(--border); border-radius:18px; padding:22px; margin-top:18px;
      box-shadow:0 8px 32px rgba(0,0,0,.4),inset 0 1px 0 rgba(255,255,255,.06);
    }
    .card,.steps,.step-pane { min-height:auto !important; }
    .step-pane { margin-bottom:10px; }

    /* Step title */
    .step-pane > h2 {
      font-size:17px; font-weight:900; margin-bottom:16px; padding-bottom:10px;
      border-bottom:1px solid rgba(255,255,255,.08);
      background:linear-gradient(90deg,#e6edf3,#7dc8ff);
      -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
    }

    /* Inputs */
    label { display:flex; flex-direction:column; gap:6px; min-width:0; }
    .lbl { display:inline-flex; align-items:baseline; gap:0;
      font-size:12px; font-weight:700; color:rgba(230,237,243,.65);
      text-transform:uppercase; letter-spacing:.3px; }
    input, select, textarea {
      width:100%; max-width:100%;
      background:var(--input-bg); border:1px solid var(--input-bd);
      border-radius:9px; color:var(--ink); font-size:14px; font-family:inherit;
      padding:10px 12px; outline:none;
      transition:border-color .2s,box-shadow .2s,background .2s;
    }
    input:focus, select:focus, textarea:focus {
      border-color:var(--sky); background:rgba(59,158,255,.07);
      box-shadow:0 0 0 3px rgba(59,158,255,.2);
    }
    input::placeholder, textarea::placeholder { color:rgba(139,148,158,.5); }
    select option { background:#0d1f3c; color:var(--ink); }
    textarea { resize:vertical; }
    input[readonly] { opacity:.6; cursor:default; }
    input.in-yy { max-width:110px; } input.in-mm { max-width:80px; } input.in-hhmm { max-width:96px; }

    /* Grids */
    .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .grid-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; }
    .col-2 { grid-column:1/-1; }
    .grid-2>label,.grid-2 label,label.col-2 { display:flex; flex-direction:column; gap:6px; min-width:0; }

    /* Nav buttons */
    .nav { display:flex; gap:12px; justify-content:flex-end; margin-top:16px; margin-bottom:5px; }
    .btn { appearance:none; cursor:pointer; border-radius:10px; padding:10px 18px;
      border:1px solid var(--border); background:rgba(255,255,255,.07);
      color:var(--ink); font-weight:700; font-size:14px; font-family:inherit;
      text-decoration:none; transition:background .2s, border-color .2s; }
    .btn:hover { background:rgba(255,255,255,.13); border-color:rgba(255,255,255,.22); }
    .btn.primary { background:linear-gradient(135deg,var(--sky),var(--sky2));
      border-color:var(--sky); color:#fff; box-shadow:0 4px 16px rgba(59,158,255,.35); }
    .btn.primary:hover { filter:brightness(1.1); }
    .btn[disabled] { opacity:.5; cursor:not-allowed; }

    /* Misc */
    .section-title { font-weight:700; margin:4px 0; font-size:13px; color:rgba(230,237,243,.7); }
    .req { color:#f85149; font-weight:900; margin-left:4px; }
    .hint { display:inline-block; margin-top:4px; color:var(--muted); font-size:12px; }
    .banner { margin:10px 0 0; font-size:.9rem; color:#7dc8ff;
      background:rgba(59,158,255,.1); border:1px solid rgba(59,158,255,.2);
      border-radius:8px; padding:8px 12px; }

    /* Tables */
    .table { width:100%; border-collapse:separate; border-spacing:0 6px; }
    .table th { font-size:11px; color:var(--muted); text-align:left; white-space:nowrap; padding:0 6px; }
    .table td input,.table td select,.table td textarea { width:100%; }
    .row-add,.row-del { padding:6px 10px; border-radius:6px;
      border:1px solid var(--border); background:var(--input-bg); color:var(--ink); cursor:pointer; transition:background .2s; }
    .row-add:hover { background:rgba(59,158,255,.15); border-color:rgba(59,158,255,.3); }
    .row-del:hover  { background:rgba(248,81,73,.15);  border-color:rgba(248,81,73,.3); color:#f85149; }
    .table.edu col.yy {width:110px} .table.edu col.mm {width:80px} .table.edu col.yy2 {width:110px}
    .table.edu col.mm2 {width:80px} .table.edu col.status {width:140px} .table.edu col.inst {width:40%} .table.edu col.fac {width:140px}
    .table.work col.yy {width:80px} .table.work col.mm {width:60px} .table.work col.status {width:110px}
    .table.work col.yy2 {width:80px} .table.work col.mm2 {width:60px} .table.work col.org {width:280px}
    .table.work col.title {width:110px} .table.work col.desc {width:320px} .table.work textarea {min-height:88px}

    /* Photo */
    .photo-preview { border:1px dashed rgba(255,255,255,.2); border-radius:10px;
      padding:8px; display:none; align-items:center; justify-content:center; min-height:120px;
      background:var(--input-bg); }
    .photo-preview img { max-width:160px; height:auto; display:block; border-radius:6px; }

    /* Rule box */
    .rule-box { margin-top:14px; border:1px solid rgba(227,179,65,.2);
      background:rgba(227,179,65,.06); border-radius:10px;
      padding:12px 14px; color:#e3b341; font-size:12.5px; line-height:1.55; }
    .rule-box ol { margin:0 0 0 1.2em; padding:0; } .rule-box li { margin:3px 0; }
    .rule-box .ban { color:#f85149; font-weight:700; }

    /* Progress bar */
    #progressWrap { max-width:1100px; margin:18px auto 0; padding:0 20px; }
    #progressWrap .progress-labels { list-style:none; margin:0 0 8px; padding:0;
      display:flex; align-items:center; justify-content:space-between; }
    #progressWrap .progress-labels li { font-weight:800; font-size:13px;
      color:var(--muted); opacity:.6; user-select:none; }
    #progressWrap .progress-labels li.is-active { opacity:1; color:#7dc8ff; }
    #progressWrap .progress-labels li.is-done   { opacity:.85; color:var(--ok); }
    #progressWrap .progress-track { height:6px; border-radius:3px; overflow:hidden;
      background:rgba(255,255,255,.08); }
    #progressWrap .progress-fill { height:100%; width:0%;
      background:linear-gradient(90deg,var(--sky),var(--violet));
      transition:width .4s cubic-bezier(.4,0,.2,1);
      box-shadow:0 0 12px rgba(59,158,255,.5); }

    /* AI row */
    .ai-row { display:flex; gap:8px; align-items:center; margin-top:6px; }

    /* Footer */
    .site-footer { background:rgba(0,0,0,.5); color:var(--muted);
      text-align:center; padding:14px 12px; font-size:13px;
      border-top:1px solid var(--border); margin-top:40px; }

    /* Responsive */
    @media(max-width:900px){ .grid-2{ grid-template-columns:1fr; } }
    @media(max-width:600px){
      input.in-yy,input.in-mm,input.in-hhmm { max-width:100%; }
      .grid-3 { grid-template-columns:1fr; }
      .ai-row { flex-direction:column; align-items:stretch; }
      .ai-row .btn { width:100%; }
    }
    @media(max-width:760px){
      .table { border-collapse:separate; border-spacing:0; }
      .table thead { display:none; }
      .table,.table tbody,.table tr,.table td { display:block; width:100%; }
      .table tr { background:rgba(255,255,255,.04); border:1px solid var(--border);
        border-radius:12px; padding:12px; margin:0 0 12px; }
      .table td { padding:10px 0; border-bottom:1px dashed rgba(255,255,255,.07);
        display:flex; flex-direction:column; gap:6px; }
      .table td:last-child { border-bottom:none; }
      .table td::before { content:attr(data-label); font-size:11px; font-weight:800; color:var(--muted); }
      .table td input,.table td select,.table td textarea { width:100%; max-width:100%; }
      .table td[data-label="操作"] { flex-direction:row; align-items:center; gap:10px; flex-wrap:wrap; }
    }
    /* ===== CRITICAL: Step visibility ===== */
    .step-pane { display: none; }
    .step-pane.is-active { display: block; }

    /* Steps container: height controlled by JS (kaigo.js) */
    .steps { overflow: hidden; }
  </style>
</head>

<body>
  <div class="appbar">
    <div class="wrap">
      <a class="nav-logo" href="/rireki/">
        <div class="nav-logo-mark"><img src="https://it-future.jp/images/android-chrome-192x192.png" alt="ITF" onerror="this.style.display='none'"></div>
        <h1>ITF 介護向け 履歴書フォーム</h1>
      </a>
      <a class="home" href="/rireki/">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        フォーマット選択へ
      </a>
    </div>
  </div>

  <!-- Progress Bar -->
  <div id="progressWrap">
    <ul class="progress-labels">
      <li data-step="1">個人情報</li>
      <li data-step="2">在留・写真</li>
      <li data-step="3">学歴・免許</li>
      <li data-step="4">職歴</li>
      <li data-step="5">自己PR・志望希望</li>
      <li data-step="6">別途情報</li>
      <li data-step="7" id="labelDone">作成終了</li>
    </ul>
    <div class="progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-label="入力進捗">
      <div id="progressFill" class="progress-fill" aria-valuenow="0"></div>
    </div>
  </div>

  <div class="wrap">
    <form class="card" action="/rireki/kaigo/php/rireki_preview.php" method="post" enctype="multipart/form-data"
      id="rirekiForm" novalidate>

      <input type="hidden" name="__fmt" value="basic">
      <!-- ✅ DB draft token -->
      <input type="hidden" name="token" id="token" value="<?php echo h($token); ?>">

      <?php if ($job_id > 0): ?>
        <div class="banner">この履歴書は求人応募フローから作成されます（Job ID: <?php echo (int)$job_id; ?>）。</div>
      <?php endif; ?>

      <div class="steps">

        <!-- STEP 1 -->
        <section class="step-pane is-active" d="step-1">
          <h2>基本情報</h2>

          <div class="grid-2">
            <label>
              <span class="lbl">氏名（ローマ字）<span class="req">*</span></span>
              <input
                type="text"
                name="name_romaji"
                id="name_romaji"
                placeholder="TARO YAMADA"
                required
                autocomplete="name"
                value="<?php echo h($row['name_romaji'] ?? ''); ?>"
              >
              <small class="hint">※ 英字は大文字（A-Z）のみ / または漢字で入力してください。</small>
            </label>

            <label>
              <span class="lbl">氏名（カタカナ）<span class="req">*</span></span>
              <input
                type="text"
                name="name_kana"
                id="name_kana"
                placeholder="ヤマダ タロウ"
                required
                autocomplete="name"
                value="<?php echo h($row['name_kana'] ?? ''); ?>"
              >
              <small class="hint">※ カタカナで入力してください。</small>
            </label>

            <div>
              <div class="section-title">生年月日（YYYY/MM/DD）<span class="req">*</span></div>
              <div class="grid-3">
                <input class="in-yy" type="text" name="dob_year" id="dob_year" placeholder="YYYY" inputmode="numeric" required maxlength="4" pattern="\d{4}" value="<?php echo h($row['dob_year'] ?? ''); ?>">
                <input class="in-mm" type="text" name="dob_month" id="dob_month" placeholder="MM" inputmode="numeric" required maxlength="2" pattern="\d{1,2}" value="<?php echo h($row['dob_month'] ?? ''); ?>">
                <input class="in-mm" type="text" name="dob_day" id="dob_day" placeholder="DD" inputmode="numeric" required maxlength="2" pattern="\d{1,2}" value="<?php echo h($row['dob_day'] ?? ''); ?>">
              </div>
            </div>

            <label>
              <span class="lbl">年齢（自動）</span>
              <input type="number" name="age_autofill" id="age_autofill" readonly value="<?php echo h($row['age_autofill'] ?? ''); ?>">
            </label>

            <label>
              <span class="lbl">出生地<span class="req">*</span></span>
              <input type="text" name="birthplace" placeholder="生まれた市と国を入力" required value="<?php echo h($row['birthplace'] ?? ''); ?>">
            </label>
            <div></div>

            <label>
              <span class="lbl">郵便番号<span class="req">*</span></span>
              <div style="display:flex;gap:8px;align-items:center">
                <input type="text" name="postal" id="postal" placeholder="1234567 (ハイフンなし)" inputmode="numeric" required pattern="\d{3}-?\d{4}" maxlength="8" style="flex:1" value="<?php echo h($row['postal'] ?? ''); ?>">
                <button type="button" id="btnPostal" style="padding:6px 12px;border-radius:8px;border:1px solid #ccc;background:#f0f8ff;cursor:pointer;white-space:nowrap;">住所検索</button>
              </div>
              <small id="postalHint" style="color:#0284c7;font-size:12px;margin-top:2px;display:block"></small>
            </label>

            <label>
              <span class="lbl">都道府県・市区町村（自動）</span>
              <input type="text" name="address_auto" id="address_auto" placeholder="郵便番号を入力すると自動入力されます" readonly style="background:#f5f9ff;color:#555" value="<?php echo h($row['address_auto'] ?? ''); ?>">
            </label>

            <label>
              <span class="lbl">現住所（丁目・番地・建物名・部屋番号）<span class="req">*</span></span>
              <input type="text" name="address" placeholder="例：1-2-3 ○○マンション 101号" required autocomplete="street-address" value="<?php echo h($row['address'] ?? ''); ?>">
              <small class="hint">※ 都道府県・市区町村は上の欄、番地・建物名はここに記入</small>
            </label>

            <label>
              <span class="lbl">個人携帯番号<span class="req">*</span></span>
              <input type="tel" name="contact_phone" id="contact_phone" placeholder="090-0000-0000" required inputmode="tel" autocomplete="tel" value="<?php echo h($row['contact_phone'] ?? ''); ?>">
              <small class="hint">※ ハイフンあり/なしOK（例：09000000000）</small>
            </label>

            <label>
              <span class="lbl">Eメール<span class="req">*</span></span>
              <input type="email" name="email" placeholder="taro@example.com" required autocomplete="email" value="<?php echo h($row['email'] ?? ''); ?>">
            </label>

            <label>
              <span class="lbl">国籍<span class="req">*</span></span>
              <select name="nationality" id="nationality" required>
                <option value=""></option>
                <?php
                  $mainOpts = ['バングラデシュ','インドネシア','ベトナム','ネパール','ミャンマー','ペルー','中国','韓国'];
                  $cur = (string)($row['nationality'] ?? '');
                  foreach ($mainOpts as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }

                  // Full world list A-Z
                  $allCountries = [
                    'アイスランド','アイルランド','アゼルバイジャン','アフガニスタン','アルジェリア','アルゼンチン','アルバニア','アルメニア',
                    'アンゴラ','アンティグア・バーブーダ','アンドラ','イエメン','イギリス','イスラエル','イタリア','イラク','イラン',
                    'インド','ウガンダ','ウクライナ','ウズベキスタン','ウルグアイ','エクアドル','エジプト','エストニア','エチオピア',
                    'エリトリア','エルサルバドル','オーストラリア','オーストリア','オマーン','オランダ','ガーナ','カーボベルデ','カザフスタン',
                    'カタール','カナダ','ガボン','カメルーン','カンボジア','ギニア','ギニアビサウ','キプロス','キューバ','ギリシャ',
                    'キリバス','キルギス','グアテマラ','クウェート','クロアチア','ケニア','コートジボワール','コスタリカ','コモロ',
                    'コロンビア','コンゴ共和国','コンゴ民主共和国','サウジアラビア','サモア','サントメ・プリンシペ','ザンビア',
                    'サンマリノ','シエラレオネ','ジブチ','ジャマイカ','ジョージア','シリア','シンガポール','ジンバブエ','スイス',
                    'スウェーデン','スーダン','スペイン','スリナム','スリランカ','スロバキア','スロベニア','スワジランド（エスワティニ）',
                    'セーシェル','赤道ギニア','セネガル','セルビア','セントクリストファー・ネービス','セントビンセント・グレナディーン',
                    'セントルシア','ソマリア','ソロモン諸島','タイ','タジキスタン','タンザニア','チェコ','チャド','中央アフリカ',
                    'チュニジア','チリ','ツバル','デンマーク','ドイツ','ドミニカ共和国','ドミニカ国','トリニダード・トバゴ',
                    'トルクメニスタン','トルコ','トンガ','ナイジェリア','ナウル','ナミビア','ニカラグア','ニジェール','ニュージーランド',
                    'ノルウェー','パキスタン','パナマ','バヌアツ','バハマ','パプアニューギニア','パラオ','パラグアイ','バルバドス',
                    'ハンガリー','バングラデシュ（国際）','東ティモール','フィジー','フィリピン','フィンランド','ブータン','ブラジル',
                    'フランス','ブルガリア','ブルキナファソ','ブルネイ','ブルンジ','ベトナム（国際）','ベナン','ベネズエラ','ベラルーシ',
                    'ベリーズ','ポーランド','ボスニア・ヘルツェゴビナ','ボツワナ','ボリビア','ポルトガル','ホンジュラス','マーシャル諸島',
                    'マダガスカル','マラウイ','マリ','マルタ','マレーシア','ミクロネシア','南アフリカ','南スーダン','モーリシャス',
                    'モーリタニア','モザンビーク','モナコ','モルディブ','モルドバ','モロッコ','モンゴル','モンテネグロ','ヨルダン',
                    'ラオス','ラトビア','リトアニア','リビア','リヒテンシュタイン','リベリア','ルーマニア','ルワンダ','レソト',
                    'レバノン','ロシア'
                  ];
                  // Remove duplicates with main opts (different spelling variants kept)
                  echo '<optgroup label="─ 全ての国（A-Z）─">';
                  foreach ($allCountries as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                  echo '</optgroup>';
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">性別<span class="req">*</span></span>
              <select name="gender" required>
                <option value=""></option>
                <?php
                  $cur = (string)($row['gender'] ?? '');
                  foreach (['男性','女性'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">宗教<span class="req">*</span></span>
              <select name="religion" required>
                <option value=""></option>
                <?php
                  $cur = (string)($row['religion'] ?? '');
                  foreach (['イスラム教','仏教','キリスト教','ヒンドゥー教','無宗教'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">配偶者の有無<span class="req">*</span></span>
              <select name="marital_status" required>
                <option value=""></option>
                <?php
                  $cur = (string)($row['marital_status'] ?? '');
                  foreach (['有り（子供あり）','有り（子供なし）','無し'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">身長 (cm)<span class="req">*</span></span>
              <input type="number" name="height_cm" min="0" step="1" required inputmode="numeric" value="<?php echo h($row['height_cm'] ?? ''); ?>">
            </label>

            <label>
              <span class="lbl">体重 (kg)<span class="req">*</span></span>
              <input type="number" name="weight_kg" min="0" step="1" required inputmode="numeric" value="<?php echo h($row['weight_kg'] ?? ''); ?>">
            </label>
          </div>

          <div class="rule-box">
            <ol>
              <li>「<strong>要配慮個人情報</strong>」（人種・信条・社会的身分・病歴・犯罪の経歴 等）は<strong>記載しないでください</strong>。</li>
              <li>誤って要配慮情報が含まれている場合、該当箇所は当社にて削除・伏せ字処理を行います。</li>
            </ol>
            <div class="ban">※ 他人の情報入力（採用関連エージェントによる代理入力 など）は禁止です。</div>
          </div>

          <div class="nav">
            <button class="btn primary js-next-step" type="button">次へ</button>
          </div>
        </section>

        <!-- STEP 2 -->
        <section class="step-pane" d="step-2">
          <h2>パスポート・出入国・在留 / 写真</h2>

          <div class="grid-2">
            <label>
              <span class="lbl">パスポート</span>
              <select name="passport_has">
                <option value=""></option>
                <?php
                  $cur = (string)($row['passport_has'] ?? '');
                  foreach (['有り','無し'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <div>
              <div class="section-title">有効期限</div>
              <div class="grid-3">
                <input class="in-yy" type="text" name="passport_exp_year" placeholder="YYYY" inputmode="numeric" value="<?php echo h($row['passport_exp_year'] ?? ''); ?>">
                <input class="in-mm" type="text" name="passport_exp_month" placeholder="MM" inputmode="numeric" value="<?php echo h($row['passport_exp_month'] ?? ''); ?>">
                <input class="in-mm" type="text" name="passport_exp_day" placeholder="DD" inputmode="numeric" value="<?php echo h($row['passport_exp_day'] ?? ''); ?>">
              </div>
            </div>

            <label>
              <span class="lbl">パスポートNO</span>
              <input type="text" name="passport_no" value="<?php echo h($row['passport_no'] ?? ''); ?>">
            </label>
            <div></div>

            <label>
              <span class="lbl">過去の出入国歴（回数）</span>
              <select name="past_travel_count">
                <?php
                  $cur = (string)($row['past_travel_count'] ?? '0');
                  foreach (['0','1','2','3'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">詳細（任意）</span>
              <input type="text" name="past_travel_details" placeholder="例：2019/05 日本入国、2021/08 帰国 など" value="<?php echo h($row['past_travel_details'] ?? ''); ?>">
            </label>

            <div>
              <div class="section-title">直近の入国</div>
              <div class="grid-3">
                <input class="in-yy" type="text" name="recent_entry_year" placeholder="YYYY" inputmode="numeric" value="<?php echo h($row['recent_entry_year'] ?? ''); ?>">
                <input class="in-mm" type="text" name="recent_entry_month" placeholder="MM" inputmode="numeric" value="<?php echo h($row['recent_entry_month'] ?? ''); ?>">
                <input class="in-mm" type="text" name="recent_entry_day" placeholder="DD" inputmode="numeric" value="<?php echo h($row['recent_entry_day'] ?? ''); ?>">
              </div>
            </div>

            <div>
              <div class="section-title">直近の出国</div>
              <div class="grid-3">
                <input class="in-yy" type="text" name="recent_exit_year" placeholder="YYYY" inputmode="numeric" value="<?php echo h($row['recent_exit_year'] ?? ''); ?>">
                <input class="in-mm" type="text" name="recent_exit_month" placeholder="MM" inputmode="numeric" value="<?php echo h($row['recent_exit_month'] ?? ''); ?>">
                <input class="in-mm" type="text" name="recent_exit_day" placeholder="DD" inputmode="numeric" value="<?php echo h($row['recent_exit_day'] ?? ''); ?>">
              </div>
            </div>

            <label>
              <span class="lbl">現在の在留資格</span>
              <select name="current_status">
                <option value=""></option>
                <?php
                  $cur = (string)($row['current_status'] ?? '');
                  foreach (['無し','特定技能','技能実習','その他'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>
            <div></div>

            <div>
              <div class="section-title">在留期限（開始）</div>
              <div class="grid-3">
                <input class="in-yy" type="text" name="status_from_year" placeholder="YYYY" inputmode="numeric" value="<?php echo h($row['status_from_year'] ?? ''); ?>">
                <input class="in-mm" type="text" name="status_from_month" placeholder="MM" inputmode="numeric" value="<?php echo h($row['status_from_month'] ?? ''); ?>">
                <input class="in-mm" type=”text" name="status_from_day" placeholder="DD" inputmode="numeric" value="<?php echo h($row['status_from_day'] ?? ''); ?>">
              </div>
            </div>

            <div>
              <div class="section-title">在留期限（終了）</div>
              <div class="grid-3">
                <input class="in-yy" type="text" name="status_to_year" placeholder="YYYY" inputmode="numeric" value="<?php echo h($row['status_to_year'] ?? ''); ?>">
                <input class="in-mm" type="text" name="status_to_month" placeholder="MM" inputmode="numeric" value="<?php echo h($row['status_to_month'] ?? ''); ?>">
                <input class="in-mm" type="text" name="status_to_day" placeholder="DD" inputmode="numeric" value="<?php echo h($row['status_to_day'] ?? ''); ?>">
              </div>
            </div>

            <label class="col-2">
              <span class="lbl">証明写真をアップロード<span class="req">*</span></span>

              <div class="photo-preview" id="photoPreview" style="<?php echo $hasPhoto ? 'display:flex' : 'display:none'; ?>">
                <img id="photoPreviewImg" alt="preview" src="<?php echo $hasPhoto ? h($photoPath) : ''; ?>">
              </div>

              <!-- ✅ IMPORTANT: do NOT submit photo to preview; upload via AJAX to this same file -->
              <input type="file" name="photo" id="photo" accept="image/jpeg,image/png" <?php echo $hasPhoto ? '' : 'required'; ?>>
              <input type="hidden" name="photo_path" id="photo_path" value="<?php echo h($photoPath); ?>">

              <small class="hint">※ JPEG/PNG（必須）</small>
              <small class="hint">※ 画像は選択後、自動で保存されます。</small>
            </label>
          </div>

          <div class="rule-box">
            <ol>
              <li>旅券番号・在留カード番号の公開は控えてください（社内管理用途のみ記入）。</li>
              <li>証明写真の要件：
                <ol>
                  <li>両耳・肩が見えるように撮影してください。</li>
                  <li>目を開け、正面を向いたもの（サングラス・帽子不可）。</li>
                  <li><strong>3か月以内</strong>に撮影した鮮明な写真。</li>
                  <li>背景は無地・明るめ（白/薄色推奨）。</li>
                </ol>
              </li>
            </ol>
            <div class="ban">※ 条件を満たさない写真は差し替えをお願いする場合があります。</div>
          </div>

          <div class="nav">
            <button class="btn js-prev-step" type="button">戻る</button>
            <button class="btn primary js-next-step" type="button">次へ</button>
          </div>
        </section>

        <!-- STEP 3 -->
        <section class="step-pane" d="step-3">
          <h2>学歴</h2>

          <table class="table edu" id="eduTable">
            <colgroup>
              <col class="yy">
              <col class="mm">
              <col class="yy2">
              <col class="mm2">
              <col class="status">
              <col class="inst">
              <col class="fac">
              <col class="ops">
            </colgroup>
            <thead>
              <tr>
                <th>開始年</th>
                <th>開始月</th>
                <th>終了年</th>
                <th>終了月</th>
                <th>在学状況</th>
                <th>学校名</th>
                <th>学部・専攻</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              <?php for ($i=1; $i<=$eduRows; $i++): ?>
              <tr class="edu-row">
                <td><input class="in-yy" type="text" name="education[from_year][]" placeholder="YYYY" inputmode="numeric" value="<?php echo h($row["edu{$i}_from_year"] ?? ''); ?>"></td>
                <td><input class="in-mm" type="text" name="education[from_month][]" placeholder="MM" inputmode="numeric" value="<?php echo h($row["edu{$i}_from_month"] ?? ''); ?>"></td>
                <td><input class="in-yy js-edu-end-y" type="text" name="education[to_year][]" placeholder="YYYY" inputmode="numeric" value="<?php echo h($row["edu{$i}_to_year"] ?? ''); ?>"></td>
                <td><input class="in-mm js-edu-end-m" type="text" name="education[to_month][]" placeholder="MM" inputmode="numeric" value="<?php echo h($row["edu{$i}_to_month"] ?? ''); ?>"></td>
                <td>
                  <select name="education[status][]" class="js-status-edu">
                    <option value=""></option>
                    <?php
                      $cur = (string)($row["edu{$i}_status"] ?? '');
                      foreach (['在学中','卒業','退学'] as $o) {
                        $sel = ($cur === $o) ? 'selected' : '';
                        echo '<option '.$sel.'>'.h($o).'</option>';
                      }
                    ?>
                  </select>
                </td>
                <td><input type="text" name="education[institution][]" placeholder="△△大学 / ○○高校" value="<?php echo h($row["edu{$i}_institution"] ?? ''); ?>"></td>
                <td><input type="text" name="education[faculty][]" placeholder="情報学部 など" value="<?php echo h($row["edu{$i}_faculty"] ?? ''); ?>"></td>
                <td>
                  <button type="button" class="row-add" data-for="edu">＋</button>
                  <button type="button" class="row-del">−</button>
                </td>
              </tr>
              <?php endfor; ?>
            </tbody>
          </table>

          <h2 style="margin-top:18px;">免許・資格</h2>

          <table class="table" id="licTable">
            <thead>
              <tr>
                <th>取得年</th>
                <th>取得月</th>
                <th>資格名</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              <?php for ($i=1; $i<=$licRows; $i++): ?>
              <tr class="lic-row">
                <td><input class="in-yy" type="text" name="licenses[cert_year][]" placeholder="YYYY" inputmode="numeric" value="<?php echo h($row["lic{$i}_year"] ?? ''); ?>"></td>
                <td><input class="in-mm" type="text" name="licenses[cert_month][]" placeholder="MM" inputmode="numeric" value="<?php echo h($row["lic{$i}_month"] ?? ''); ?>"></td>
                <td><input type="text" name="licenses[cert_name][]" placeholder="介護職員初任者研修 など" value="<?php echo h($row["lic{$i}_name"] ?? ''); ?>"></td>
                <td>
                  <button type="button" class="row-add" data-for="lic">＋</button>
                  <button type="button" class="row-del">−</button>
                </td>
              </tr>
              <?php endfor; ?>
            </tbody>
          </table>

          <div class="rule-box">
            <ol>
              <li>学歴・資格は<strong>最新から順</strong>にご入力ください。</li>
              <li>正式名称で記入（例：× 初任者、○ 介護職員初任者研修）。</li>
            </ol>
            <div class="ban">※ 架空の資格・在籍は厳禁です。確認できない場合は不採用となることがあります。</div>
          </div>

          <div class="nav">
            <button class="btn js-prev-step" type="button">戻る</button>
            <button class="btn primary js-next-step" type="button">次へ</button>
          </div>
        </section>

        <!-- STEP 4 -->
        <section class="step-pane" d="step-4">
          <h2>職歴</h2>

          <table class="table work" id="expTable">
            <colgroup>
              <col class="yy">
              <col class="mm">
              <col class="status">
              <col class="yy2">
              <col class="mm2">
              <col class="org">
              <col class="title">
              <col class="desc">
              <col class="ops">
            </colgroup>
            <thead>
              <tr>
                <th>開始年</th>
                <th>開始月</th>
                <th>在職状況</th>
                <th>終了年</th>
                <th>終了月</th>
                <th>会社・施設名</th>
                <th>職種/役職</th>
                <th>仕事内容</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              <?php for ($i=1; $i<=$workRows; $i++): ?>
              <tr class="exp-row">
                <td><input class="in-yy" type="text" name="work_blocks[from_year][]" placeholder="YYYY" inputmode="numeric" value="<?php echo h($row["work{$i}_from_year"] ?? ''); ?>"></td>
                <td><input class="in-mm" type="text" name="work_blocks[from_month][]" placeholder="MM" inputmode="numeric" value="<?php echo h($row["work{$i}_from_month"] ?? ''); ?>"></td>
                <td>
                  <select name="work_blocks[status][]" class="js-status-exp">
                    <option value=""></option>
                    <?php
                      $cur = (string)($row["work{$i}_status"] ?? '');
                      foreach (['在職中','退職'] as $o) {
                        $sel = ($cur === $o) ? 'selected' : '';
                        echo '<option '.$sel.'>'.h($o).'</option>';
                      }
                    ?>
                  </select>
                </td>
                <td><input class="in-yy js-exp-end-y" type="text" name="work_blocks[to_year][]" placeholder="YYYY" inputmode="numeric" value="<?php echo h($row["work{$i}_to_year"] ?? ''); ?>"></td>
                <td><input class="in-mm js-exp-end-m" type="text" name="work_blocks[to_month][]" placeholder="MM" inputmode="numeric" value="<?php echo h($row["work{$i}_to_month"] ?? ''); ?>"></td>
                <td><input type="text" name="work_blocks[org][]" placeholder="特養 / 老健 / 企業名" value="<?php echo h($row["work{$i}_org"] ?? ''); ?>"></td>
                <td><input type="text" name="work_blocks[job_title][]" placeholder="介護職 / 生活相談員 / 看護助手 等" value="<?php echo h($row["work{$i}_job_title"] ?? ''); ?>"></td>
                <td><textarea name="work_blocks[description][]" rows="4" placeholder="主な業務内容・担当・実績などを簡潔に（〜100語目安）"><?php echo h($row["work{$i}_description"] ?? ''); ?></textarea></td>
                <td>
                  <button type="button" class="row-add" data-for="exp">＋</button>
                  <button type="button" class="row-del">−</button>
                </td>
              </tr>
              <?php endfor; ?>
            </tbody>
          </table>

          <label class="col-2" style="margin-top:10px;">
            <span class="lbl">退職理由について（対象者のみ）</span>
            <textarea name="reason_for_resignation" rows="3" placeholder="退職済みの場合のみ入力"><?php echo h($row['reason_for_resignation'] ?? ''); ?></textarea>
          </label>

          <label class="col-2" style="margin-top:10px;">
            <span class="lbl">退職日予定（対象者のみ）</span>
            <div class="grid-3" style="max-width:420px">
              <input class="in-yy" type="text" name="planned_resign_year" placeholder="YYYY" inputmode="numeric" value="<?php echo h($row['planned_resign_year'] ?? ''); ?>">
              <input class="in-mm" type="text" name="planned_resign_month" placeholder="MM" inputmode="numeric" value="<?php echo h($row['planned_resign_month'] ?? ''); ?>">
              <div></div>
            </div>
          </label>

          <div class="rule-box">
            <ol>
              <li>「仕事内容」には、担当フロア・介助件数・シフト帯など、わかる範囲で具体的に入力してください。</li>
              <li>退職理由は採用判断の参考にします。誹謗中傷の記載はお控えください。</li>
            </ol>
            <div class="ban">※ 経歴詐称が判明した場合は選考見送りとなります。</div>
          </div>

          <div class="nav">
            <button class="btn js-prev-step" type="button">戻る</button>
            <button class="btn primary js-next-step" type="button">次へ</button>
          </div>
        </section>

        <!-- STEP 5 -->
        <section class="step-pane" d="step-5">
          <h2>自己PR・志望・希望</h2>

          <div class="grid-2">
            <label class="col-2">
              <span class="lbl">自己PR</span>
              <textarea name="self_pr" rows="4" id="prText"
                placeholder="自分の言葉でOK（母国語でもOK）です。できるだけ書いてみてください。書き終わったら「AIで整える」を押すと、読みやすい日本語に整えます。"><?php echo h($row['self_pr'] ?? ''); ?></textarea>
              <div class="ai-row">
                <button type="button" class="btn" data-ai-target="#prText">AIで整える</button>
              </div>
            </label>

            <label class="col-2">
              <span class="lbl">志望の動機</span>
              <textarea name="motivation" rows="4" id="motivationText"
                placeholder="自分の言葉でOK（母国語でもOK）です。できるだけ書いてみてください。書き終わったら「AIで整える」を押すと、読みやすい日本語に整えます。"><?php echo h($row['motivation'] ?? ''); ?></textarea>
              <div class="ai-row">
                <button type="button" class="btn" data-ai-target="#motivationText">AIで整える</button>
              </div>
            </label>

            <label class="col-2">
              <span class="lbl">本人希望欄（職種、給与、勤務地など）</span>
              <textarea name="preferences" rows="4" id="prefText"
                placeholder="自分の言葉でOK（母国語でもOK）です。できるだけ書いてみてください。書き終わったら「AIで整える」を押すと、読みやすい日本語に整えます。"><?php echo h($row['preferences'] ?? ''); ?></textarea>
              <div class="ai-row">
                <button type="button" class="btn" data-ai-target="#prefText">AIで整える</button>
              </div>
            </label>
          </div>

          <div class="rule-box">
            <ol>
              <li>具体的な経験や強みは<strong>数字</strong>を交えて簡潔に（例：利用者15名の入浴介助）。</li>
              <li>希望条件は「必須 / 望ましい」を分けて書くと伝わりやすいです。</li>
            </ol>
            <div class="ban">※ 個人・施設名への誹謗中傷は記載しないでください。</div>
          </div>

          <div class="nav">
            <button class="btn js-prev-step" type="button">戻る</button>
            <button class="btn primary js-next-step" type="button">次へ</button>
          </div>
        </section>

        <!-- STEP 6 -->
        <section class="step-pane" d="step-6">
          <h2>別途情報</h2>

          <div class="grid-2">
            <label>
              <span class="lbl">日本語コミュニケーション</span>
              <select name="jp_comm_level">
                <option value=""></option>
                <?php
                  $cur = (string)($row['jp_comm_level'] ?? '');
                  foreach (['N1相当','N2相当','N3相当','N4相当'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">漢字読み書き</span>
              <select name="kanji_rw">
                <option value=""></option>
                <?php
                  $cur = (string)($row['kanji_rw'] ?? '');
                  foreach (['できる','漢字が苦手','ひらがなならOK','まだまだ勉強'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">血液型</span>
              <select name="blood_type">
                <option value=""></option>
                <?php
                  $cur = (string)($row['blood_type'] ?? '');
                  foreach (['A','B','O','AB','わからない'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">英語について</span>
              <select name="english_level">
                <option value=""></option>
                <?php
                  $cur = (string)($row['english_level'] ?? '');
                  foreach (['可能','日常会話OK','不可'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">日本に知り合い</span>
              <select name="acquaintances_in_japan">
                <option value=""></option>
                <?php
                  $cur = (string)($row['acquaintances_in_japan'] ?? '');
                  foreach (['あり','なし'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">日本人の友達</span>
              <select name="jp_friends_count">
                <option value=""></option>
                <?php
                  $cur = (string)($row['jp_friends_count'] ?? '');
                  foreach (['0名','1名','2名','3名','4名','5名'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">母国の友達（日本に）</span>
              <select name="home_country_friends_in_japan">
                <option value=""></option>
                <?php
                  $cur = (string)($row['home_country_friends_in_japan'] ?? '');
                  $opts = [];
                  for ($i=0; $i<=10; $i++) $opts[] = $i.'名';
                  foreach ($opts as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">タバコ</span>
              <select name="smoking">
                <option value=""></option>
                <?php
                  $cur = (string)($row['smoking'] ?? '');
                  foreach (['吸う','吸わない'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">お酒</span>
              <select name="alcohol">
                <option value=""></option>
                <?php
                  $cur = (string)($row['alcohol'] ?? '');
                  foreach (['飲む','飲まない'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">刺青</span>
              <select name="tattoo">
                <option value=""></option>
                <?php
                  $cur = (string)($row['tattoo'] ?? '');
                  foreach (['あり','なし'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">服のサイズ</span>
              <select name="clothes_size">
                <option value=""></option>
                <?php
                  $cur = (string)($row['clothes_size'] ?? '');
                  foreach (['SS','S','M','L','LL','XL'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">靴のサイズ</span>
              <select name="shoe_size">
                <option value=""></option>
                <?php
                  $cur = (string)($row['shoe_size'] ?? '');
                  $opts = [
                    '21.0cm(EUR32)','21.5cm(EUR33)','22.0cm(EUR34)','22.5cm(EUR35)',
                    '23.0cm(EUR36)','23.5cm(EUR37)','24.0cm(EUR38)','24.5cm(EUR39)',
                    '25.0cm(EUR40)','25.5cm(EUR41)','26.0cm(EUR42)','26.5cm(EUR43)',
                    '27.0cm(EUR44)','27.5cm(EUR45)','28.0cm(EUR46)','28.5cm(EUR47)',
                    '29.0cm(EUR48)'
                  ];
                  foreach ($opts as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">お祈り</span>
              <select name="prayer">
                <option value=""></option>
                <?php
                  $cur = (string)($row['prayer'] ?? '');
                  foreach (['なし','あり（仕事中はしない)','あり（休憩中にはしたい)'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">断食</span>
              <select name="fasting">
                <option value=""></option>
                <?php
                  $cur = (string)($row['fasting'] ?? '');
                  foreach (['なし','あり（仕事中はしない)','あり（休憩中にはしたい)'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">食べ物の制限</span>
              <select name="food_rules">
                <option value=""></option>
                <?php
                  $cur = (string)($row['food_rules'] ?? '');
                  foreach (['特にありません','全肉は食べません','豚は食べません','牛肉は食べません','豚は食べません/お酒は飲みません'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">ヒジャブ</span>
              <select name="hijab">
                <option value=""></option>
                <?php
                  $cur = (string)($row['hijab'] ?? '');
                  foreach (['仕事中はしません','仕事中もしたいです。','必要なし'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">日本での仕事の希望期間</span>
              <select name="work_duration_intent">
                <option value=""></option>
                <?php
                  $cur = (string)($row['work_duration_intent'] ?? '');
                  foreach (['できるだけ長く','5年は滞在したい','その他'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">現在の日本語勉強</span>
              <select name="studying_japanese_now">
                <option value=""></option>
                <?php
                  $cur = (string)($row['studying_japanese_now'] ?? '');
                  foreach (['あり','なし'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">現在の専門職の勉強</span>
              <select name="studying_specialty_now">
                <option value=""></option>
                <?php
                  $cur = (string)($row['studying_specialty_now'] ?? '');
                  foreach (['あり','なし'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>

            <label>
              <span class="lbl">別の送り出し/別施設の面接</span>
              <select name="other_agency_or_facility_interview" id="finalSelect">
                <option value=""></option>
                <?php
                  $cur = (string)($row['other_agency_or_facility_interview'] ?? '');
                  foreach (['あり','なし'] as $o) {
                    $sel = ($cur === $o) ? 'selected' : '';
                    echo '<option '.$sel.'>'.h($o).'</option>';
                  }
                ?>
              </select>
            </label>
          </div>

          <div class="rule-box">
            <ol>
              <li>このページの項目は任意です。該当しないものは空欄で構いません。</li>
              <li>就業に影響のない宗教・生活習慣の情報は公開いたしません。</li>
            </ol>
            <div class="ban">※ 差別・偏見につながる表現は入力しないでください。</div>
          </div>

          <div class="nav">
            <button class="btn js-prev-step" type="button">戻る</button>
            <button class="btn primary" type="submit">この内容でプレビューへ</button>
          </div>
        </section>

      </div>
    </form>
  </div>

  <div class="site-footer">© ITF co. Ltd. ALL Rights Reserved</div>

  <script src="/rireki/kaigo/js/kaigo.js?v=5"></script>
  <script src="/rireki/kaigo/js/rireki_form_extra.js?v=20260129"></script>
  <script src="/rireki/kaigo/js/postal_autofill.js?v=1"></script>
</body>

</html>

