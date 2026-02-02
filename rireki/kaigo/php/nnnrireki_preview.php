<?php
// /home/it-future/www/itf/rireki/kaigo/php/rireki_preview.php
// ✅ Updated for NEW token-based draft DB (app_resume_kaigo)
// ✅ Keeps the same UI/design + same 3-flow logic
// ✅ Fixes "Invalid token" by validating token and loading data from DB by token

ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);

session_start();

// ✅ use existing DB connection (DO NOT create another)
require_once $_SERVER['DOCUMENT_ROOT'] . '/php/db_connect.php'; // expects $pdo (PDO)

// (Optional) Keep existing login helpers for apply_profile / profile_only flows (if your site uses it)
$HAS_USER_AUTH = false;
if (is_file(__DIR__ . '/../../../php/user_auth.php')) {
  require_once __DIR__ . '/../../../php/user_auth.php';
  $HAS_USER_AUTH = function_exists('app_is_logged_in') && function_exists('app_pdo') && function_exists('app_user_id');
}

// ---------- helpers ----------
function h($v)
{
  return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function normalize_token($t): string
{
  $t = strtolower(trim((string) $t));
  return preg_match('/^[a-f0-9]{32}$/', $t) ? $t : '';
}

/** Re-create POST fields (including nested arrays) as hidden inputs */
function keep($name, $value)
{
  if (is_array($value)) {
    $html = '';
    foreach ($value as $k => $v) {
      $html .= keep($name . '[' . $k . ']', $v);
    }
    return $html;
  }
  return '<input type="hidden" name="' . h($name) . '" value="' . h($value) . '">' . "\n";
}

/** Move uploaded photo to a temp public path for preview; return web path. */
function moveTempPhoto(?array $file): ?string
{
  if (!$file || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK)
    return null;

  $dir = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/') . '/rireki/uploads/tmp';
  if (!is_dir($dir))
    @mkdir($dir, 0755, true);

  $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg', 'jpeg', 'png'], true))
    $ext = 'jpg';

  $name = bin2hex(random_bytes(8)) . '.' . $ext;
  $dest = $dir . '/' . $name;

  if (!@move_uploaded_file($file['tmp_name'], $dest))
    return null;

  return '/rireki/uploads/tmp/' . $name; // web path
}

/**
 * Persist tmp photo to a permanent per-user folder so edits won't require re-upload.
 * Returns new web path, or original if already permanent, or null if missing.
 */
function persistPhotoForUser(int $user_id, ?string $photoPath): ?string
{
  if (!$photoPath)
    return null;

  $tmpPrefix = '/rireki/uploads/tmp/';
  // already permanent
  if (strpos($photoPath, $tmpPrefix) !== 0)
    return $photoPath;

  $docRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
  if ($docRoot === '')
    return null;

  $tmpDir = $docRoot . '/rireki/uploads/tmp';
  $src = $docRoot . $photoPath;

  $tmpDirReal = realpath($tmpDir);
  $srcReal = realpath($src);

  if (!$tmpDirReal || !$srcReal)
    return null;
  // security: ensure src is inside tmp dir
  if (strpos($srcReal, $tmpDirReal) !== 0)
    return null;
  if (!is_file($srcReal))
    return null;

  $dstDir = $docRoot . "/rireki/uploads/profile/{$user_id}";
  if (!is_dir($dstDir))
    @mkdir($dstDir, 0755, true);

  $ext = strtolower(pathinfo($srcReal, PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg', 'jpeg', 'png'], true))
    $ext = 'jpg';

  $name = 'photo_' . bin2hex(random_bytes(6)) . '.' . $ext;
  $dst = $dstDir . '/' . $name;

  if (!@copy($srcReal, $dst))
    return null;

  // optional: delete tmp to reduce clutter (ignore failures)
  @unlink($srcReal);

  return "/rireki/uploads/profile/{$user_id}/{$name}";
}

function ensure_profile_table(PDO $pdo)
{
  // DEPRECATED: kept for backward compatibility. Profiles are stored in app_profiles via user_auth.php.
  // Do nothing.
}

/**
 * Load saved profile for logged-in user.
 * Prefer app_load_profile() (app_profiles table) to keep compatibility with existing login/profile pages.
 */
function load_profile_post(PDO $pdo, int $user_id): ?array
{
  if (function_exists('app_load_profile')) {
    $arr = app_load_profile($pdo, $user_id, 'kaigo');
    return is_array($arr) ? $arr : null;
  }
  // Fallback: no profile system
  return null;
}

/**
 * Save profile snapshot for logged-in user.
 * Prefer app_save_profile() (app_profiles table).
 */
function save_profile_post(PDO $pdo, int $user_id, array $post): void
{
  if (function_exists('app_save_profile')) {
    app_save_profile($pdo, $user_id, $post, 'kaigo');
  }
}

function fetch_kaigo_by_token(PDO $pdo, string $token): ?array
{
  $st = $pdo->prepare("SELECT * FROM app_resume_kaigo WHERE token = :t LIMIT 1");
  $st->execute([':t' => $token]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return $row ?: null;
}

function row_to_post(array $row): array
{
  // Build $post in the SAME structure that preview expects (education/licenses/work_blocks)
  $post = $row;

  // Normalize core fields
  $post['token'] = (string) ($row['token'] ?? '');
  $post['job_id'] = isset($row['job_id']) && (string) $row['job_id'] !== '' ? (int) $row['job_id'] : 0;
  $post['photo_path'] = (string) ($row['photo_path'] ?? '');

  // education (8)
  $post['education'] = [
    'from_year' => [],
    'from_month' => [],
    'to_year' => [],
    'to_month' => [],
    'status' => [],
    'institution' => [],
    'faculty' => [],
  ];
  for ($i = 1; $i <= 8; $i++) {
    $post['education']['from_year'][] = (string) ($row["edu{$i}_from_year"] ?? '');
    $post['education']['from_month'][] = (string) ($row["edu{$i}_from_month"] ?? '');
    $post['education']['to_year'][] = (string) ($row["edu{$i}_to_year"] ?? '');
    $post['education']['to_month'][] = (string) ($row["edu{$i}_to_month"] ?? '');
    $post['education']['status'][] = (string) ($row["edu{$i}_status"] ?? '');
    $post['education']['institution'][] = (string) ($row["edu{$i}_institution"] ?? '');
    $post['education']['faculty'][] = (string) ($row["edu{$i}_faculty"] ?? '');
  }

  // licenses (8)
  $post['licenses'] = [
    'cert_year' => [],
    'cert_month' => [],
    'cert_name' => [],
  ];
  for ($i = 1; $i <= 8; $i++) {
    $post['licenses']['cert_year'][] = (string) ($row["lic{$i}_year"] ?? '');
    $post['licenses']['cert_month'][] = (string) ($row["lic{$i}_month"] ?? '');
    $post['licenses']['cert_name'][] = (string) ($row["lic{$i}_name"] ?? '');
  }

  // work_blocks (8)
  $post['work_blocks'] = [
    'from_year' => [],
    'from_month' => [],
    'status' => [],
    'to_year' => [],
    'to_month' => [],
    'org' => [],
    'job_title' => [],
    'description' => [],
  ];
  for ($i = 1; $i <= 8; $i++) {
    $post['work_blocks']['from_year'][] = (string) ($row["work{$i}_from_year"] ?? '');
    $post['work_blocks']['from_month'][] = (string) ($row["work{$i}_from_month"] ?? '');
    $post['work_blocks']['status'][] = (string) ($row["work{$i}_status"] ?? '');
    $post['work_blocks']['to_year'][] = (string) ($row["work{$i}_to_year"] ?? '');
    $post['work_blocks']['to_month'][] = (string) ($row["work{$i}_to_month"] ?? '');
    $post['work_blocks']['org'][] = (string) ($row["work{$i}_org"] ?? '');
    $post['work_blocks']['job_title'][] = (string) ($row["work{$i}_job_title"] ?? '');
    $post['work_blocks']['description'][] = (string) ($row["work{$i}_description"] ?? '');
  }

  return $post;
}

function upsert_kaigo_draft_from_post(PDO $pdo, string $token, int $user_id, int $job_id, array $post): void
{
  // Allow only known scalar columns (ignore nested arrays etc.)
  $cols = [
    'user_id',
    'job_id',
    'step_current',
    'name_romaji',
    'name_kana',
    'dob_year',
    'dob_month',
    'dob_day',
    'age_autofill',
    'birthplace',
    'postal',
    'address',
    'contact_phone',
    'email',
    'nationality',
    'gender',
    'religion',
    'marital_status',
    'height_cm',
    'weight_kg',
    'passport_has',
    'passport_exp_year',
    'passport_exp_month',
    'passport_exp_day',
    'passport_no',
    'past_travel_count',
    'past_travel_details',
    'recent_entry_year',
    'recent_entry_month',
    'recent_entry_day',
    'recent_exit_year',
    'recent_exit_month',
    'recent_exit_day',
    'current_status',
    'status_from_year',
    'status_from_month',
    'status_from_day',
    'status_to_year',
    'status_to_month',
    'status_to_day',
    'photo_path',
  ];

  // edu1..8
  for ($i = 1; $i <= 8; $i++) {
    $cols[] = "edu{$i}_from_year";
    $cols[] = "edu{$i}_from_month";
    $cols[] = "edu{$i}_to_year";
    $cols[] = "edu{$i}_to_month";
    $cols[] = "edu{$i}_status";
    $cols[] = "edu{$i}_institution";
    $cols[] = "edu{$i}_faculty";
  }
  // lic1..8
  for ($i = 1; $i <= 8; $i++) {
    $cols[] = "lic{$i}_year";
    $cols[] = "lic{$i}_month";
    $cols[] = "lic{$i}_name";
  }
  // work1..8
  for ($i = 1; $i <= 8; $i++) {
    $cols[] = "work{$i}_from_year";
    $cols[] = "work{$i}_from_month";
    $cols[] = "work{$i}_status";
    $cols[] = "work{$i}_to_year";
    $cols[] = "work{$i}_to_month";
    $cols[] = "work{$i}_org";
    $cols[] = "work{$i}_job_title";
    $cols[] = "work{$i}_description";
  }

  // Other free fields
  $cols = array_merge($cols, [
    'reason_for_resignation',
    'planned_resign_year',
    'planned_resign_month',
    'self_pr',
    'motivation',
    'preferences',
    'jp_comm_level',
    'kanji_rw',
    'blood_type',
    'english_level',
    'acquaintances_in_japan',
    'jp_friends_count',
    'home_country_friends_in_japan',
    'smoking',
    'alcohol',
    'tattoo',
    'clothes_size',
    'shoe_size',
    'prayer',
    'fasting',
    'food_rules',
    'hijab',
    'work_duration_intent',
    'studying_japanese_now',
    'studying_specialty_now',
    'other_agency_or_facility_interview',
  ]);

  $data = [':token' => $token];
  $insertCols = ['token'];
  $insertVals = [':token'];
  $updates = [];

  foreach ($cols as $c) {
    $ph = ':' . $c;
    $insertCols[] = $c;
    $insertVals[] = $ph;
    // Override user_id/job_id/step_current from arguments
    if ($c === 'user_id') {
      $val = $user_id;
    } elseif ($c === 'job_id') {
      $val = $job_id;
    } elseif ($c === 'step_current') {
      $val = 6;
    } else {
      $val = $post[$c] ?? null;
    }
    $data[$ph] = $val;
    $updates[] = "$c = VALUES($c)";
  }

  $sql = "INSERT INTO app_resume_kaigo (" . implode(',', $insertCols) . ") VALUES (" . implode(',', $insertVals) . ")\n          ON DUPLICATE KEY UPDATE " . implode(', ', $updates) . ", updated_at=CURRENT_TIMESTAMP";
  $st = $pdo->prepare($sql);
  $st->execute($data);
}

// ---------- Mode selection ----------
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$post = [];
$photoPath = null;

// token (priority: POST -> GET)
$token = normalize_token($_POST['token'] ?? ($_GET['token'] ?? ''));

// NEW: convert initial POST(from rireki.php) -> GET (refresh-safe)
// Skip when this POST is a profile-save request (flow is present).
$flow_post = strtolower(trim((string) ($_POST['flow'] ?? '')));
if (($method === 'POST') && $token && empty($_GET['token']) && $flow_post === '') {
  $job_id_tmp = isset($_POST['job_id']) ? (int) $_POST['job_id'] : 0;
  $qs = ['token' => $token];
  if ($job_id_tmp > 0)
    $qs['job_id'] = $job_id_tmp;
  header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($qs), true, 302);
  exit;
}

// NEW: flow selector
$flow = strtolower(trim((string) ($_GET['flow'] ?? ($_POST['flow'] ?? ''))));
$allowedFlows = ['open_resume', 'apply_profile', 'profile_only'];
if (!in_array($flow, $allowedFlows, true))
  $flow = '';


// NEW: myinfo context identifier (only trust if logged-in user matches)
$req_userid = (int) ($_GET['userid'] ?? ($_POST['userid'] ?? 0));
$session_uid = ($HAS_USER_AUTH && function_exists('app_is_logged_in') && app_is_logged_in()) ? (int) app_user_id() : 0;
$myinfo_uid = ($req_userid > 0 && $session_uid > 0 && $req_userid === $session_uid) ? $session_uid : 0;

// Flash flag for "profile saved"
$savedFlash = false;


// ===== AJAX register (public maker) =====
// Only for: open_resume + not logged in + token exists
if ($method === 'POST' && (string) ($_POST['action'] ?? '') === 'register_open') {
  header('Content-Type: application/json; charset=utf-8');

  if (!$token) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'token_missing'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  if ($session_uid > 0) {
    echo json_encode(['ok' => true, 'already_logged_in' => true], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $name = trim((string) ($_POST['name'] ?? ''));
  $email = trim((string) ($_POST['email'] ?? ''));
  $pass = (string) ($_POST['password'] ?? '');

  if ($name === '' || mb_strlen($name) > 60) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'name_invalid'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'email_invalid'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  if (strlen($pass) < 8) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'password_short'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $row = fetch_kaigo_by_token($pdo, $token);
  if (!$row) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'token_invalid'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if (!$HAS_USER_AUTH) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'auth_missing'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ensure auth tables exist
  if (function_exists('app_ensure_tables')) {
    try {
      app_ensure_tables($pdo);
    } catch (Throwable $e) {
    }
  }

  try {
    $hash = password_hash($pass, PASSWORD_DEFAULT);

    $base = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', strtok($email, '@'));
    $base = $base !== '' ? $base : 'user';
    $username = $base;

    $newUid = 0;
    for ($i = 0; $i < 10; $i++) {
      try {
        $stmt = $pdo->prepare("INSERT INTO " . APP_TBL_USERS . " (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hash]);
        $newUid = (int) $pdo->lastInsertId();
        break;
      } catch (Throwable $e) {
        $msg = (string) $e->getMessage();
        if (stripos($msg, 'email') !== false) {
          http_response_code(409);
          echo json_encode(['ok' => false, 'error' => 'email_exists'], JSON_UNESCAPED_UNICODE);
          exit;
        }
        $username = $base . random_int(100, 9999);
      }
    }
    if ($newUid <= 0) {
      http_response_code(500);
      echo json_encode(['ok' => false, 'error' => 'register_failed'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    app_login_user_id($newUid);

    $stmt = $pdo->prepare("UPDATE app_resume_kaigo SET user_id = ?, email = COALESCE(NULLIF(email,''), ?), name_romaji = COALESCE(NULLIF(name_romaji,''), ?) WHERE token = ? LIMIT 1");
    $stmt->execute([$newUid, $email, $name, $token]);

    $row2 = fetch_kaigo_by_token($pdo, $token);
    if ($row2) {
      $post2 = row_to_post($row2);
      app_save_profile($pdo, $newUid, $post2, 'kaigo');
    }

    echo json_encode(['ok' => true, 'user_id' => $newUid], JSON_UNESCAPED_UNICODE);
    exit;

  } catch (Throwable $e) {
    error_log('[rireki_preview register_open] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server_error'], JSON_UNESCAPED_UNICODE);
    exit;
  }
}

// ===== GET mode =====
// - If token provided: show preview using DB row
// - Else if logged-in + flow profile/apply: show saved profile (old behavior kept)
if ($method === 'GET') {
  $job_id = isset($_GET['job_id']) ? (int) $_GET['job_id'] : 0;

  // If token exists -> load from DB and ignore profile (this is the main kaigo flow)
  if ($token) {
    $row = fetch_kaigo_by_token($pdo, $token);
    if (!$row) {
      http_response_code(400);
      echo "Invalid token";
      exit;
    }
    $post = row_to_post($row);

    // If job_id passed in URL and DB job_id empty, still allow preview to treat it as applying
    if ($job_id > 0 && (int) ($post['job_id'] ?? 0) === 0) {
      $post['job_id'] = $job_id;
    }

    // Decide flow if missing
    if ($flow === '') {
      if ($job_id > 0 || (int) ($post['job_id'] ?? 0) > 0) {
        $flow = 'apply_profile';
      } elseif ($myinfo_uid > 0) {
        $flow = 'profile_only';
      } elseif ($session_uid > 0 && (int) ($post['user_id'] ?? 0) === $session_uid) {
        $flow = 'profile_only';
      } else {
        $flow = 'open_resume';
      }
    }

    $photoPath = $post['photo_path'] ?? null;
    // Force profile view when accessed from マイ情報 (userid param)
    if ($myinfo_uid > 0 && $job_id === 0) {
      $flow = 'profile_only';
    }

    // Build query params for edit links (preserve context)
    $link_qs = '';
    if ($flow === 'apply_profile') {
      $link_qs = ($job_id > 0) ? ('&job_id=' . (int) $job_id . '&flow=apply_profile') : '&flow=apply_profile';
    } elseif ($flow === 'profile_only') {
      $link_qs = ($myinfo_uid > 0) ? ('&userid=' . (int) $myinfo_uid . '&flow=profile_only') : '&flow=profile_only';
    } else {
      $link_qs = '&flow=open_resume';
    }



  } else {
    // No token: keep old profile-view behavior (only if auth exists)
    if (!$HAS_USER_AUTH) {
      http_response_code(400);
      echo "Invalid token";
      exit;
    }

    // default: job_id => apply_profile, else profile_only (old)
    if ($flow === '')
      $flow = ($job_id > 0) ? 'apply_profile' : 'profile_only';

    if (!app_is_logged_in()) {
      $next = $_SERVER['REQUEST_URI'] ?? '/saiyou.php';
      header('Location: /php/user_login.php?next=' . urlencode($next), true, 302);
      exit;
    }

    $pdo_app = app_pdo();
    $uid = (int) app_user_id();
    $saved = load_profile_post($pdo_app, $uid);

    if (!$saved) {
      $dest = '/rireki/kaigo/rireki.php';
      if ($job_id > 0)
        $dest .= '?job_id=' . urlencode((string) $job_id);
      header('Location: ' . $dest, true, 302);
      exit;
    }

    $post = $saved;
    if ($flow === 'apply_profile' && $job_id > 0) {
      $post['job_id'] = $job_id;
    } else {
      unset($post['job_id']);
      $job_id = 0;
    }

    // Make sure photo is permanent
    $perma = persistPhotoForUser($uid, $post['photo_path'] ?? null);
    if ($perma) {
      $post['photo_path'] = $perma;
      save_profile_post($pdo_app, $uid, $post);
    }

    // NEW:
    // When preview is opened without token (e.g. 「プロフィールで進む」 or 「マイ情報」),
    // create a temporary draft in app_resume_kaigo and redirect with ?token=...
    // so that submit_rireki.php can work with token->DB.
    if (!$token) {
      $token = bin2hex(random_bytes(16));
      $draftJobId = ($flow === 'apply_profile' && $job_id > 0) ? $job_id : 0;
      upsert_kaigo_draft_from_post($pdo_app, $token, $uid, $draftJobId, $post);

      $qs = ['token' => $token];
      if ($draftJobId > 0)
        $qs['job_id'] = $draftJobId;
      $qs['flow'] = $flow;
      header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($qs), true, 302);
      exit;
    }
    $photoPath = $post['photo_path'] ?? null;
  }

} else {
  // ===== POST mode =====
  // IMPORTANT for your NEW kaigo flow:
  // - We DO NOT trust posted fields anymore.
  // - We load everything from DB by token to prevent "Invalid token" mismatch.
  $pt = normalize_token($_POST['token'] ?? '');
  if (!$pt) {
    http_response_code(400);
    echo "Invalid token";
    exit;
  }
  $_SESSION['kaigo_token'] = $pt;

  $row = fetch_kaigo_by_token($pdo, $pt);
  if (!$row) {
    http_response_code(400);
    echo "Invalid token";
    exit;
  }

  $post = row_to_post($row);

  // Decide flow for POST
  if ($flow === '') {
    $flow = ((int) ($post['job_id'] ?? 0) > 0) ? 'apply_profile' : 'open_resume';
  }

  // Photo: if a file was uploaded directly to preview (rare), take it as temp path
  $photoPath = $post['photo_path'] ?? null;
  if (!$photoPath && isset($_FILES['photo'])) {
    $tmp = moveTempPhoto($_FILES['photo']);
    if ($tmp) {
      $photoPath = $tmp;
      $post['photo_path'] = $photoPath;
    }
  }

  // If logged in, persist tmp photo + save profile (kept same)
  if ($HAS_USER_AUTH && app_is_logged_in()) {
    $pdo_app = app_pdo();
    $uid = (int) app_user_id();

    $perma = persistPhotoForUser($uid, $post['photo_path'] ?? null);
    if ($perma) {
      $photoPath = $perma;
      $post['photo_path'] = $perma;
    }

    // Keep old feature: save profile snapshot as well
    save_profile_post($pdo_app, $uid, $post);
  }
}

// job_id for apply button
$job_id = isset($post['job_id']) ? (int) $post['job_id'] : (isset($_GET['job_id']) ? (int) $_GET['job_id'] : 0);

// header back button behavior
$headerBackHref = '/saiyou.php';
$headerBackLabel = '求人一覧へ戻る';
if ($job_id > 0) {
  $headerBackHref = '/php/job_details.php?job_id=' . h($job_id);
  $headerBackLabel = '求人詳細へ戻る';
} else {
  if ($flow === 'open_resume') {
    $headerBackHref = '/rireki/index.php';
    $headerBackLabel = '履歴書メーカーへ戻る';
  } else {
    $headerBackHref = '/saiyou.php';
    $headerBackLabel = '求人一覧へ戻る';
  }
}

// ---------- Build preview data from KAIGO form names ----------
// STEP 1：基本情報
$step1 = [
  '氏名（ローマ字）' => $post['name_romaji'] ?? '',
  'フリガナ' => $post['name_kana'] ?? '',
  '生年月日' => trim(($post['dob_year'] ?? '') . '/' . ($post['dob_month'] ?? '') . '/' . ($post['dob_day'] ?? ''), ' /'),
  '年齢（自動）' => $post['age_autofill'] ?? '',
  '出生地' => $post['birthplace'] ?? '',
  '郵便番号' => $post['postal'] ?? '',
  '現住所' => $post['address'] ?? '',
  '電話番号' => $post['contact_phone'] ?? '',
  'Eメール' => $post['email'] ?? '',
  '国籍' => $post['nationality'] ?? '',
  '性別' => $post['gender'] ?? '',
  '宗教' => $post['religion'] ?? '',
  '配偶者の有無' => $post['marital_status'] ?? '',
  '身長 (cm)' => $post['height_cm'] ?? '',
  '体重 (kg)' => $post['weight_kg'] ?? '',
];

// STEP 2：在留・写真
$step2 = [
  'パスポート' => $post['passport_has'] ?? '',
  'パスポートNO' => $post['passport_no'] ?? '',
  '有効期限' => trim(($post['passport_exp_year'] ?? '') . '/' . ($post['passport_exp_month'] ?? '') . '/' . ($post['passport_exp_day'] ?? ''), ' /'),
  '出入国歴（回数）' => $post['past_travel_count'] ?? '',
  '出入国歴の詳細' => $post['past_travel_details'] ?? '',
  '直近の入国' => trim(($post['recent_entry_year'] ?? '') . '/' . ($post['recent_entry_month'] ?? '') . '/' . ($post['recent_entry_day'] ?? ''), ' /'),
  '直近の出国' => trim(($post['recent_exit_year'] ?? '') . '/' . ($post['recent_exit_month'] ?? '') . '/' . ($post['recent_exit_day'] ?? ''), ' /'),
  '現在の在留資格' => $post['current_status'] ?? '',
  '在留期限（開始）' => trim(($post['status_from_year'] ?? '') . '/' . ($post['status_from_month'] ?? '') . '/' . ($post['status_from_day'] ?? ''), ' /'),
  '在留期限（終了）' => trim(($post['status_to_year'] ?? '') . '/' . ($post['status_to_month'] ?? '') . '/' . ($post['status_to_day'] ?? ''), ' /'),
];

// STEP 3：学歴・資格
$eduRows = [];
$eyF = $post['education']['from_year'] ?? [];
$emF = $post['education']['from_month'] ?? [];
$eyT = $post['education']['to_year'] ?? [];
$emT = $post['education']['to_month'] ?? [];
$es = $post['education']['status'] ?? [];
$ei = $post['education']['institution'] ?? [];
$ef = $post['education']['faculty'] ?? [];
$N = max(count($eyF), count($emF), count($eyT), count($emT), count($es), count($ei), count($ef));
for ($i = 0; $i < $N; $i++) {
  $eduRows[] = [
    '開始' => trim(($eyF[$i] ?? '') . '/' . ($emF[$i] ?? ''), ' /'),
    '終了' => trim(($eyT[$i] ?? '') . '/' . ($emT[$i] ?? ''), ' /'),
    '在学状況' => $es[$i] ?? '',
    '学校名' => $ei[$i] ?? '',
    '学部・専攻' => $ef[$i] ?? '',
  ];
}

$licRows = [];
$ly = $post['licenses']['cert_year'] ?? [];
$lm = $post['licenses']['cert_month'] ?? [];
$ln = $post['licenses']['cert_name'] ?? [];
$L = max(count($ly), count($lm), count($ln));
for ($i = 0; $i < $L; $i++) {
  $licRows[] = [
    '取得年' => $ly[$i] ?? '',
    '取得月' => $lm[$i] ?? '',
    '資格名' => $ln[$i] ?? '',
  ];
}

// STEP 4：職歴
$workRows = [];
$wyF = $post['work_blocks']['from_year'] ?? [];
$wmF = $post['work_blocks']['from_month'] ?? [];
$ws = $post['work_blocks']['status'] ?? [];
$wyT = $post['work_blocks']['to_year'] ?? [];
$wmT = $post['work_blocks']['to_month'] ?? [];
$wo = $post['work_blocks']['org'] ?? [];
$wt = $post['work_blocks']['job_title'] ?? [];
$wd = $post['work_blocks']['description'] ?? [];
$W = max(count($wyF), count($wmF), count($ws), count($wyT), count($wmT), count($wo), count($wt), count($wd));
for ($i = 0; $i < $W; $i++) {
  $workRows[] = [
    '開始' => trim(($wyF[$i] ?? '') . '/' . ($wmF[$i] ?? ''), ' /'),
    '在職状況' => $ws[$i] ?? '',
    '終了' => trim(($wyT[$i] ?? '') . '/' . ($wmT[$i] ?? ''), ' /'),
    '会社・施設名' => $wo[$i] ?? '',
    '職種/役職' => $wt[$i] ?? '',
    '仕事内容' => $wd[$i] ?? '',
  ];
}
$reasonResign = $post['reason_for_resignation'] ?? '';
$plannedResign = trim(($post['planned_resign_year'] ?? '') . '/' . ($post['planned_resign_month'] ?? ''), ' /');

// STEP 5：自己PR・志望・希望
$step5 = [
  '自己PR' => $post['self_pr'] ?? '',
  '志望の動機' => $post['motivation'] ?? '',
  '本人希望（職種・給与・勤務地など）' => $post['preferences'] ?? '',
];

// STEP 6：別途情報
$step6 = [
  '日本語コミュニケーション' => $post['jp_comm_level'] ?? '',
  '漢字読み書き' => $post['kanji_rw'] ?? '',
  '血液型' => $post['blood_type'] ?? '',
  '英語' => $post['english_level'] ?? '',
  '日本に知り合い' => $post['acquaintances_in_japan'] ?? '',
  '日本人の友達' => $post['jp_friends_count'] ?? '',
  '母国の友達（日本に）' => $post['home_country_friends_in_japan'] ?? '',
  'タバコ' => $post['smoking'] ?? '',
  'お酒' => $post['alcohol'] ?? '',
  '刺青' => $post['tattoo'] ?? '',
  '服のサイズ' => $post['clothes_size'] ?? '',
  '靴のサイズ' => $post['shoe_size'] ?? '',
  'お祈り' => $post['prayer'] ?? '',
  '断食' => $post['fasting'] ?? '',
  '食べ物の制限' => $post['food_rules'] ?? '',
  'ヒジャブ' => $post['hijab'] ?? '',
  '仕事の希望期間' => $post['work_duration_intent'] ?? '',
  '日本語の勉強' => $post['studying_japanese_now'] ?? '',
  '専門職の勉強' => $post['studying_specialty_now'] ?? '',
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
    :root {
      --sky: #1e90ff;
      --sky-2: #39a7ff;
      --ink: #0b0f19;
      --muted: #475467;
      --bd: #e6edf6;
      --bg: #f6fbff;
      --card: #fff;
      --ring: #bfe2ff;
      --radius: 14px;
      --shadow: 0 10px 24px rgba(0, 0, 0, .05);
      --header-h: 72px;
      --header-gap: 12px;
    }

    * {
      box-sizing: border-box
    }

    html,
    body {
      height: 100%
    }

    body {
      margin: 0;
      color: var(--ink);
      font-family: ui-sans-serif, system-ui, "Noto Sans JP", Meiryo, Arial;
      background: linear-gradient(180deg, #f8fbff, #eef6ff);
      padding-top: calc(var(--header-h) + var(--header-gap));
    }

    header {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
      min-height: var(--header-h);
      background: #9ed1ff;
      border-bottom: 1px solid var(--bd);
      backdrop-filter: saturate(180%) blur(6px);
    }

    .wrap {
      max-width: 1100px;
      margin: 0 auto;
      padding: 16px 18px
    }

    .hdr {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px
    }

    .title {
      margin: 0;
      font-size: 22px;
      font-weight: 900;
      letter-spacing: .2px;
      color: var(--ink)
    }

    .crumb {
      color: var(--muted);
      font-size: 12px;
      margin: 2px 0 0
    }

    main.wrap {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 18px;
      padding: 18px;
      align-items: start;
    }

    @media (max-width:980px) {
      main.wrap {
        grid-template-columns: 1fr
      }
    }

    .section {
      background: var(--card);
      border: 1px solid var(--bd);
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: var(--shadow);
      transform: translateY(6px);
      opacity: 0;
      animation: slideIn .5s ease forwards;
    }

    .section+.section {
      margin-top: 16px
    }

    @keyframes slideIn {
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .section-head {
      color: var(--sky);
      padding: 10px 14px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .section-head h2 {
      margin: 0;
      font-size: 16px;
      font-weight: 900;
      letter-spacing: .5px;
    }

    .section-body {
      padding: 14px;
    }

    .row {
      display: grid;
      grid-template-columns: 260px 1fr;
      gap: 10px;
      padding: 8px 0;
      border-bottom: 1px dashed #e8f2fb;
    }

    .row:last-child {
      border-bottom: none
    }

    .label {
      color: #0b0f19;
      font-weight: 700
    }

    .value {
      color: #0b0f19
    }

    .muted {
      color: var(--muted)
    }

    .table {
      width: 100%;
      border-collapse: collapse;
    }

    .table th,
    .table td {
      border: 1px solid #e8f2fb;
      padding: 8px 10px;
      vertical-align: top;
      color: #0b0f19;
    }

    .table thead th {
      background: #eef6ff;
      color: #0b0f19;
      font-weight: 800;
    }

    .photo-box {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    img.photo {
      max-width: 160px;
      border: 2px solid #e5f0ff;
      border-radius: 10px;
    }

    .btn {
      appearance: none;
      cursor: pointer;
      border-radius: 10px;
      padding: 10px 14px;
      border: 1px solid var(--ring);
      background: #f3f9ff;
      color: #0c4a7a;
      font-weight: 800;
      transition: transform .2s, box-shadow .2s, background .2s, border-color .2s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(30, 144, 255, .16);
      background: #e9f5ff;
    }

    .btn.primary {
      background: linear-gradient(180deg, var(--sky-2), var(--sky));
      color: #fff;
      border-color: var(--sky-2);
    }

    .final {
      background: #fff;
      border: 2px solid var(--sky);
      border-radius: var(--radius);
      padding: 16px;
      box-shadow: 0 14px 28px rgba(30, 144, 255, .12)
    }

    .final h3 {
      margin: 0 0 8px 0;
      color: var(--ink)
    }

    main.wrap>aside {
      position: sticky;
      top: calc(var(--header-h) + var(--header-gap));
      height: fit-content;
      align-self: start;
      z-index: 5;
    }

    .side-card {
      background: #fff;
      border: 1px solid var(--bd);
      border-radius: var(--radius);
      padding: 14px;
      margin-bottom: 16px;
      box-shadow: 0 8px 18px rgba(0, 0, 0, .04);
      transform: translateY(6px);
      opacity: 0;
      animation: slideIn .6s ease .05s forwards;
    }

    .side-card h3 {
      margin: 0 0 8px;
      color: var(--ink)
    }

    .linklist {
      list-style: none;
      margin: 0;
      padding: 0
    }

    .linklist li {
      margin: 6px 0
    }

    .linklist a {
      color: #0c4a7a;
      text-decoration: none;
      border-bottom: 1px dashed #9ed1ff
    }

    .linklist a:hover {
      color: #0a3861;
      border-bottom-color: #1e90ff
    }

    /* modal */
    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(2, 6, 23, .55);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 2000;
      padding: 18px;
    }

    .modal {
      width: min(560px, 100%);
      background: #fff;
      border: 1px solid #e6edf6;
      border-radius: 16px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, .25);
      padding: 16px;
    }

    .modal h3 {
      margin: 0 0 8px;
      font-size: 18px;
    }

    .modal p {
      margin: 0 0 12px;
      color: #475467;
    }

    .modal .rowbtn {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      flex-wrap: wrap;
    }

    .modal .btn {
      padding: 10px 14px;
    }

    @media (max-width:980px) {
      :root {
        --header-h: 84px;
      }

      main.wrap>aside {
        position: static;
      }

      .row {
        grid-template-columns: 1fr;
      }
    }

    /* open_resume aside tools */
    .input {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #dbe7f5;
      border-radius: 10px;
      font-size: 14px;
      outline: none;
      background: #fff;
    }

    .input:focus {
      box-shadow: 0 0 0 4px rgba(57, 167, 255, .18);
      border-color: #9ed1ff;
    }

    .form-row {
      margin: 10px 0;
    }

    .small {
      font-size: 12px;
      color: var(--muted);
    }

    .note {
      background: #fff7ed;
      border: 1px solid #fed7aa;
      color: #b45309;
      padding: 10px 12px;
      border-radius: 12px;
      margin: 10px 0;
    }

    .okbox {
      background: #ecfdf5;
      border: 1px solid #bbf7d0;
      color: #0b6b4a;
      padding: 10px 12px;
      border-radius: 12px;
      margin: 10px 0;
      font-weight: 700;
    }

    .btn[disabled],
    .btn.disabled {
      opacity: .55;
      cursor: not-allowed;
      transform: none !important;
      box-shadow: none !important;
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

        <a class="btn" href="<?= h($headerBackHref) ?>"><?= h($headerBackLabel) ?></a>
      </div>
    </div>
  </header>

  <!-- Modal: application success -->
  <div id="appModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal">
      <h3>Thank you for your application</h3>
      <p>Our team will contact you shortly.</p>
      <div class="rowbtn">
        <a class="btn primary" href="/php/user_applied_jobs.php">戻る</a>
      </div>
    </div>
  </div>

  <!-- Modal: profile saved -->
  <div id="saveModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal">
      <h3>保存しました</h3>
      <p>プロフィール情報を更新しました。</p>
      <div class="rowbtn">
        <a class="btn primary" href="/php/user_applied_jobs.php">戻る</a>
      </div>
    </div>
  </div>

  <main class="wrap">
    <section>
      <!-- STEP 1 -->
      <div class="section">
        <div class="section-head">
          <h2>STEP 1：基本情報</h2>
        </div>
        <div class="section-body">
          <?php foreach ($step1 as $k => $v): ?>
            <div class="row">
              <div class="label"><?= h($k) ?></div>
              <div class="value"><?= $v !== '' ? nl2br(h($v)) : '—' ?></div>
            </div>
          <?php endforeach; ?>
          <div style="margin-top:12px; text-align:center;">
            <a class="btn" href="/rireki/kaigo/rireki.php?token=<?= h($token) ?><?= h($link_qs) ?>#step-1">このステップを編集</a>
          </div>
        </div>
      </div>

      <!-- STEP 2 -->
      <div class="section">
        <div class="section-head">
          <h2>STEP 2：在留・写真</h2>
        </div>
        <div class="section-body">
          <?php foreach ($step2 as $k => $v): ?>
            <div class="row">
              <div class="label"><?= h($k) ?></div>
              <div class="value"><?= $v !== '' ? nl2br(h($v)) : '—' ?></div>
            </div>
          <?php endforeach; ?>

          <?php if (!empty($photoPath)): ?>
            <div class="row">
              <div class="label">証明写真（保存済み）</div>
              <div class="photo-box">
                <img class="photo" src="<?= h($photoPath) ?>" alt="photo preview">
              </div>
            </div>
          <?php else: ?>
            <div class="row">
              <div class="label">証明写真</div>
              <div class="value muted">未登録（編集時に写真を求められる場合があります）</div>
            </div>
          <?php endif; ?>

          <div style="margin-top:12px; text-align:center;">
            <a class="btn" href="/rireki/kaigo/rireki.php?token=<?= h($token) ?><?= h($link_qs) ?>#step-2">このステップを編集</a>
          </div>
        </div>
      </div>

      <!-- STEP 3 -->
      <div class="section">
        <div class="section-head">
          <h2>STEP 3：学歴・資格</h2>
        </div>
        <div class="section-body">
          <h3 class="muted" style="margin:0 0 8px 0">学歴</h3>
          <?php if ($eduRows && array_filter($eduRows, fn($r) => implode('', $r) !== '')): ?>
            <table class="table">
              <thead>
                <tr>
                  <th>開始</th>
                  <th>終了</th>
                  <th>在学状況</th>
                  <th>学校名</th>
                  <th>学部・専攻</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($eduRows as $r): ?>
                  <tr>
                    <td><?= h($r['開始']) ?></td>
                    <td><?= h($r['終了']) ?></td>
                    <td><?= h($r['在学状況']) ?></td>
                    <td><?= h($r['学校名']) ?></td>
                    <td><?= h($r['学部・専攻']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <p class="muted">未入力</p>
          <?php endif; ?>

          <h3 class="muted" style="margin:14px 0 8px 0">免許・資格</h3>
          <?php if ($licRows && array_filter($licRows, fn($r) => implode('', $r) !== '')): ?>
            <table class="table">
              <thead>
                <tr>
                  <th>取得年</th>
                  <th>取得月</th>
                  <th>資格名</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($licRows as $r): ?>
                  <tr>
                    <td><?= h($r['取得年']) ?></td>
                    <td><?= h($r['取得月']) ?></td>
                    <td><?= h($r['資格名']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <p class="muted">未入力</p>
          <?php endif; ?>

          <div style="margin-top:12px; text-align:center;">
            <a class="btn" href="/rireki/kaigo/rireki.php?token=<?= h($token) ?><?= h($link_qs) ?>#step-3">このステップを編集</a>
          </div>
        </div>
      </div>

      <!-- STEP 4 -->
      <div class="section">
        <div class="section-head">
          <h2>STEP 4：職歴</h2>
        </div>
        <div class="section-body">
          <?php if ($workRows && array_filter($workRows, fn($r) => implode('', $r) !== '')): ?>
            <table class="table">
              <thead>
                <tr>
                  <th>開始</th>
                  <th>在職状況</th>
                  <th>終了</th>
                  <th>会社・施設名</th>
                  <th>職種/役職</th>
                  <th>仕事内容</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($workRows as $r): ?>
                  <tr>
                    <td><?= h($r['開始']) ?></td>
                    <td><?= h($r['在職状況']) ?></td>
                    <td><?= h($r['終了']) ?></td>
                    <td><?= h($r['会社・施設名']) ?></td>
                    <td><?= h($r['職種/役職']) ?></td>
                    <td><?= nl2br(h($r['仕事内容'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <p class="muted">未入力</p>
          <?php endif; ?>

          <div class="row">
            <div class="label">退職理由（対象者）</div>
            <div class="value"><?= $reasonResign !== '' ? nl2br(h($reasonResign)) : '—' ?></div>
          </div>
          <div class="row">
            <div class="label">退職予定（年/月）</div>
            <div class="value"><?= $plannedResign !== '' ? h($plannedResign) : '—' ?></div>
          </div>

          <div style="margin-top:12px; text-align:center;">
            <a class="btn" href="/rireki/kaigo/rireki.php?token=<?= h($token) ?><?= h($link_qs) ?>#step-4">このステップを編集</a>
          </div>
        </div>
      </div>

      <!-- STEP 5 -->
      <div class="section">
        <div class="section-head">
          <h2>STEP 5：自己PR・志望・希望</h2>
        </div>
        <div class="section-body">
          <?php foreach ($step5 as $k => $v): ?>
            <div class="row">
              <div class="label"><?= h($k) ?></div>
              <div class="value"><?= $v !== '' ? nl2br(h($v)) : '—' ?></div>
            </div>
          <?php endforeach; ?>
          <div style="margin-top:12px; text-align:center;">
            <a class="btn" href="/rireki/kaigo/rireki.php?token=<?= h($token) ?><?= h($link_qs) ?>#step-5">このステップを編集</a>
          </div>
        </div>
      </div>

      <!-- STEP 6 -->
      <div class="section">
        <div class="section-head">
          <h2>STEP 6：別途情報</h2>
        </div>
        <div class="section-body">
          <?php foreach ($step6 as $k => $v): ?>
            <div class="row">
              <div class="label"><?= h($k) ?></div>
              <div class="value"><?= $v !== '' ? nl2br(h($v)) : '—' ?></div>
            </div>
          <?php endforeach; ?>
          <div style="margin-top:12px; text-align:center;">
            <a class="btn" href="/rireki/kaigo/rireki.php?token=<?= h($token) ?><?= h($link_qs) ?>#step-6">このステップを編集</a>
          </div>
        </div>
      </div>

      <!-- Final submit (UPDATED: 3 flows) -->
      <div class="final" style="margin-top:18px;">
        <?php if ($flow === 'apply_profile'): ?>
          <h3>この内容で応募しますか？</h3>
          <p class="muted" style="margin:6px 0 12px">
            この内容で「求人応募」まで進みます。必要であれば各ステップの「このステップを編集」から修正してください。
          </p>

          <form id="applyForm" method="post" action="/rireki/kaigo/php/submit_rireki.php"
            style="display:flex;gap:10px;flex-wrap:wrap">
            <?php foreach ($post as $k => $v)
              echo keep($k, $v); ?>
            <input type="hidden" name="flow" value="apply_profile">
            <input type="hidden" name="token" value="<?= h($token) ?>">
            <a class="btn" href="/php/job_details.php?job_id=<?= h($job_id) ?>">求人詳細へ戻る</a>
            <button type="submit" class="btn primary">この内容で応募する</button>
          </form>

        <?php elseif ($flow === 'open_resume'): ?>
          <h3>確認</h3>
          <p class="muted" style="margin:6px 0 12px">
            内容を確認しましたら、右側の「履歴書出力（Excel）」から作成・ダウンロードできます。<br>
            初めての方は、先に無料登録すると次回からログインして編集・再ダウンロードできます。
          </p>

          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a class="btn" href="/rireki/index.php">履歴書メーカーへ戻る</a>
            <a class="btn primary" href="#export-card">履歴書出力へ進む</a>
          </div>

        <?php else: /* profile_only */ ?>
          <h3>プロフィールを保存しますか？</h3>
          <p class="muted" style="margin:6px 0 12px">
            この内容をプロフィールとして保存・更新します。
          </p>

          <!-- Just save profile: re-post to preview itself -->
          <form id="saveProfileForm" method="post" action="/rireki/kaigo/php/rireki_preview.php"
            style="display:flex;gap:10px;flex-wrap:wrap">
            <?php foreach ($post as $k => $v)
              echo keep($k, $v); ?>
            <input type="hidden" name="flow" value="profile_only">
            <input type="hidden" name="token" value="<?= h($token) ?>">
            <a class="btn" href="/saiyou.php">求人一覧へ戻る</a>
            <?php if ($myinfo_uid > 0): ?>
              <button type="submit" class="btn" formaction="/rireki/kaigo/php/submit_rireki.php"
                formmethod="post">エクスポート</button>
            <?php endif; ?>
            <button type="submit" class="btn primary">Save Profile</button>
          </form>
        <?php endif; ?>
      </div>
    </section>

    <!-- Sidebar -->
    <aside>

      <?php if ($flow === 'open_resume'): ?>
        <!-- Open user: Register + Export (no separate submit page) -->
        <?php if ($session_uid <= 0): ?>
          <div class="side-card" id="register-card">
            <h3>初めての方（無料登録）</h3>
            <p class="muted" style="margin:6px 0 10px">
              いま作成した履歴書をアカウントに紐づけます。次回からログインして、少し修正→再ダウンロードができます。
            </p>

            <form id="openRegisterForm" autocomplete="on">
              <div class="form-row">
                <label class="small">お名前</label>
                <input class="input" type="text" name="name" maxlength="60" placeholder="例：Bikash Thapa" required>
              </div>
              <div class="form-row">
                <label class="small">メールアドレス</label>
                <input class="input" type="email" name="email" maxlength="190" placeholder="example@email.com" required>
              </div>
              <div class="form-row">
                <label class="small">パスワード（8文字以上）</label>
                <input class="input" type="password" name="password" minlength="8" placeholder="********" required>
              </div>

              <input type="hidden" name="action" value="register_open">
              <input type="hidden" name="token" value="<?= h($token) ?>">
              <button id="btnRegisterOpen" class="btn primary" type="submit" style="width:100%;justify-content:center">
                登録してダウンロードを有効化
              </button>
              <div id="regMsg" class="small" style="margin-top:8px;"></div>
            </form>
          </div>
        <?php endif; ?>

        <div class="side-card" id="export-card">
          <h3>履歴書出力（Excel）</h3>

          <?php if ($session_uid <= 0): ?>
            <div class="note">
              ダウンロードは「無料登録」後に有効になります。（次回からログインで再編集・再DLが可能）
            </div>
            <button class="btn primary" type="button" disabled style="width:100%;justify-content:center">
              登録するとダウンロードできます
            </button>
          <?php else: ?>
            <p id="exportStatus" class="muted" style="margin:6px 0 10px">
              Excel（.xls）を生成してダウンロードできます。
            </p>
            <button id="btnBuildXls" class="btn primary" type="button" style="width:100%;justify-content:center">
              Excelを作成
            </button>

            <div id="exportResult" style="display:none;margin-top:12px">
              <div class="okbox">履歴書が完成しました。ダウンロードしてご確認ください。</div>

              <div class="note" style="margin-top:10px">
                PDF は環境差により体裁が100%一致しない場合があります。<strong>Excel（.xls）からの印刷</strong>を推奨します。
              </div>

              <div class="rowbtn" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px">
                <a id="xlsDownloadLink" class="btn" href="#" download>Excel（.xls）をダウンロード</a>
                <a id="claimLink" class="btn" href="#">アカウントに保存（あとで再DL）</a>
                <a class="btn" href="/saiyou.php">他の求人を探す</a>
                <a class="btn" href="https://it-future.jp/">会社ホームページへ</a>
                <a class="btn" href="/rireki/index.php">別のフォーマットで作る</a>
              </div>

              <details style="margin-top:12px">
                <summary>よくある質問（FAQ）</summary>
                <div class="muted" style="margin-top:8px">
                  <div style="margin-bottom:8px">
                    <strong>PDF での出力はできますか？</strong><br>
                    現在は Excel（.xls）での出力に最適化しています。PDF は環境差で体裁が崩れることがあるため、Excel からの印刷をおすすめします。
                  </div>
                  <div>
                    <strong>内容を修正したいです。</strong><br>
                    「このステップを編集」から修正できます。ダウンロードした Excel を直接編集することも可能です。
                  </div>
                </div>
              </details>

              <p id="exportTokenText" class="small" style="margin-top:10px"></p>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>


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
          <li><a href="/saiyou.php">求人情報（求人一覧）</a></li>
          <li><a href="/company_info.html">会社概要</a></li>
        </ul>
      </div>
      <div class="side-card">
        <h3>ヘルプ</h3>
        <p class="muted">プレビューはブラウザ表示のため、Excel印刷時と微差が出る場合があります。提出前に内容を再確認ください。</p>
      </div>
    </aside>
  </main>

  <script>
    // Apply-with-profile: AJAX submit -> show THANK YOU modal -> user clicks 戻る => applied list
    (function () {
      const form = document.getElementById('applyForm');
      if (!form) return;

      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(form);

        try {
          const res = await fetch(form.action, { method: 'POST', body: fd, credentials: 'same-origin' });
          if (!res.ok) throw new Error('submit failed');
          const modal = document.getElementById('appModal');
          if (modal) { modal.style.display = 'flex'; modal.setAttribute('aria-hidden', 'false'); }
        } catch (err) {
          alert('送信に失敗しました。もう一度お試しください。');
        }
      });
    })();

    // Profile-only: POST to preview itself -> it will auto-save already; show modal (client side)
    (function () {
      const form = document.getElementById('saveProfileForm');
      if (!form) return;

      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(form);

        try {
          const res = await fetch(form.action, { method: 'POST', body: fd, credentials: 'same-origin' });
          if (!res.ok) throw new Error('save failed');
          const modal = document.getElementById('saveModal');
          if (modal) { modal.style.display = 'flex'; modal.setAttribute('aria-hidden', 'false'); }
        } catch (err) {
          alert('保存に失敗しました。もう一度お試しください。');
        }
      });
    })();

    // Close modal when clicking outside
    document.addEventListener('click', (e) => {
      const m1 = document.getElementById('appModal');
      const m2 = document.getElementById('saveModal');
      if (m1 && e.target === m1) { m1.style.display = 'none'; m1.setAttribute('aria-hidden', 'true'); }
      if (m2 && e.target === m2) { m2.style.display = 'none'; m2.setAttribute('aria-hidden', 'true'); }
    });

    // Open resume: register + build XLS inside aside (no separate submit page)
    (function () {
      const flow = "<?= h($flow) ?>";
      if (flow !== 'open_resume') return;

      const token = "<?= h($token) ?>";
      const isLoggedIn = <?= ($session_uid > 0 ? 'true' : 'false') ?>;

      // Register form (public -> create account + bind token)
      const regForm = document.getElementById('openRegisterForm');
      if (regForm) {
        regForm.addEventListener('submit', async (e) => {
          e.preventDefault();
          const btn = document.getElementById('btnRegisterOpen');
          const msg = document.getElementById('regMsg');
          if (msg) msg.textContent = '';
          if (btn) { btn.disabled = true; btn.textContent = '登録中...'; }

          try {
            const fd = new FormData(regForm);
            const res = await fetch("/rireki/kaigo/php/rireki_preview.php?token=" + encodeURIComponent(token) + "&flow=open_resume", {
              method: 'POST',
              body: fd,
              credentials: 'same-origin'
            });
            const j = await res.json().catch(() => null);
            if (!res.ok || !j || !j.ok) {
              const err = (j && j.error) ? j.error : 'register_failed';
              let t = '登録に失敗しました。';
              if (err === 'email_exists') t = 'このメールアドレスは既に登録されています。ログインしてください。';
              if (err === 'password_short') t = 'パスワードは8文字以上にしてください。';
              if (msg) msg.textContent = t;
              if (btn) { btn.disabled = false; btn.textContent = '登録してダウンロードを有効化'; }
              return;
            }
            // Logged in now (session set). Reload to show export enabled.
            window.location.href = "/rireki/kaigo/php/rireki_preview.php?token=" + encodeURIComponent(token) + "&flow=open_resume";
          } catch (e2) {
            if (msg) msg.textContent = '通信に失敗しました。もう一度お試しください。';
            if (btn) { btn.disabled = false; btn.textContent = '登録してダウンロードを有効化'; }
          }
        });
      }

      // Build XLS (AJAX -> show download link)
      const buildBtn = document.getElementById('btnBuildXls');
      if (buildBtn) {
        buildBtn.addEventListener('click', async () => {
          buildBtn.disabled = true;
          const st = document.getElementById('exportStatus');
          if (st) st.textContent = 'Excelを作成中...';

          const fd = new FormData();
          fd.append('token', token);
          fd.append('flow', 'open_resume');
          fd.append('ajax', '1');

          try {
            const res = await fetch("/rireki/kaigo/php/submit_rireki.php", { method: 'POST', body: fd, credentials: 'same-origin' });
            const j = await res.json().catch(() => null);
            if (!res.ok || !j || !j.ok || !j.xls_url) throw new Error('build_failed');

            const xls = j.xls_url;
            const outToken = j.token || '';
            const result = document.getElementById('exportResult');
            const dl = document.getElementById('xlsDownloadLink');
            const claim = document.getElementById('claimLink');
            const tok = document.getElementById('exportTokenText');

            if (dl) dl.href = xls;

            // Build claim/login links (same as submit_rireki.php)
            const fmt = 'kaigo';
            const claimNext = "/rireki/kaigo/php/claim_resume.php?token=" + encodeURIComponent(outToken) + "&fmt=" + encodeURIComponent(fmt);
            const loginUrl = "/php/user_login.php?next=" + encodeURIComponent(claimNext);

            if (claim) {
              if (isLoggedIn) {
                claim.href = claimNext;
                claim.textContent = 'アカウントに保存（あとで再DL）';
              } else {
                claim.href = loginUrl;
                claim.textContent = 'ログインして保存（あとで再DL）';
              }
            }

            if (tok) tok.textContent = 'トークン: ' + outToken + ' / 出力: ' + xls;

            if (result) result.style.display = 'block';
            if (st) st.textContent = '完了しました。下のボタンからダウンロードできます。';
          } catch (err) {
            if (st) st.textContent = '作成に失敗しました。もう一度お試しください。';
          } finally {
            buildBtn.disabled = false;
          }
        });
      }
    })();

  </script>

</body>

</html>