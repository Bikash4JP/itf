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


// (Optional) Activity logger (recent activities on staff dashboard)
$HAS_ACTIVITY_LOGGER = false;
if (is_file(__DIR__ . '/../../../php/activity_logger.php')) {
  require_once __DIR__ . '/../../../php/activity_logger.php';
  $HAS_ACTIVITY_LOGGER = function_exists('log_activity');
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


/**
 * ✅ Activity log: when an application is submitted (apply_profile),
 * we log a "recent activity" entry for staff dashboard.
 *
 * This endpoint is called via AJAX from this preview page after submit_rireki.php succeeds.
 * IMPORTANT: It does NOT change any existing apply/save/export flows.
 */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (string) ($_POST['action'] ?? '') === 'log_apply_activity') {
  header('Content-Type: application/json; charset=utf-8');

  if (!$HAS_ACTIVITY_LOGGER) {
    echo json_encode(['ok' => false, 'error' => 'activity_logger_missing'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $t = normalize_token($_POST['token'] ?? '');
  if ($t === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'token_invalid'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  try {
    $row = fetch_kaigo_by_token($pdo, $t);
    if (!$row) {
      http_response_code(400);
      echo json_encode(['ok' => false, 'error' => 'token_not_found'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    $jobId = isset($row['job_id']) ? (int) $row['job_id'] : 0;
    if ($jobId <= 0 && isset($_POST['job_id']))
      $jobId = (int) $_POST['job_id'];

    $applicantName = trim((string) ($row['name_romaji'] ?? ''));
    if ($applicantName === '')
      $applicantName = trim((string) ($row['name_kana'] ?? ''));
    if ($applicantName === '')
      $applicantName = '応募者';

    $companyName = '';
    if ($jobId > 0) {
      // match submit_rireki.php behavior (posts table)
      $st = $pdo->prepare("SELECT company_name FROM posts WHERE id = ? LIMIT 1");
      $st->execute([$jobId]);
      $companyName = (string) ($st->fetchColumn() ?: '');
    }

    $org = $companyName !== '' ? $companyName : '求人';
    $msg = "【応募】{$org} に新しい応募が届きました。（氏名: {$applicantName}）";

    $data = [
      'actor_type' => 'applicant',
      'actor_staff_id' => null,
      'actor_username' => $applicantName,
      'action' => 'apply',
      'entity_type' => 'job',
      'entity_id' => ($jobId > 0 ? $jobId : null),
      'company_name' => ($companyName !== '' ? $companyName : null),
      'talent_name_kana' => null,
      'message_ja' => $msg,
      'meta' => ['token' => $t],
    ];

    log_activity($pdo, $data);

    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;

  } catch (Throwable $e) {
    error_log('[rireki_preview log_apply_activity] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server_error'], JSON_UNESCAPED_UNICODE);
    exit;
  }
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

// Edit base URL — links "Edit" button in each section card back to the form
$editBase = '/rireki/kaigo/rireki.php';
if ($token !== '') {
  // If a token is set (post_id), carry it so the form pre-fills
  // (the form reads it from session, no extra param needed)
}

// Photo
$photoPath  = (string)($post['photo_path'] ?? '');
$hasPhoto   = ($photoPath !== '' && file_exists($_SERVER['DOCUMENT_ROOT'] . $photoPath));


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
  <title>入力内容の確認｜ITF 履歴書メーカー</title>
  <meta name="robots" content="noindex,follow" />
  <link rel="icon" href="https://it-future.jp/images/favicon-32x32.png" sizes="32x32">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=Noto+Sans+JP:wght@400;700;900&display=swap"
    rel="stylesheet">
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --sky: #3b9eff;
      --sky2: #1e78e8;
      --violet: #7c3aed;
      --ink: #e6edf3;
      --muted: #8b949e;
      --border: rgba(255, 255, 255, .1);
      --ok: #3fb950;
      --danger: #f85149;
      --input-bg: rgba(255, 255, 255, .06);
      --input-bd: rgba(255, 255, 255, .14);
      --header-h: 64px;
    }

    html,
    body {
      min-height: 100%;
      font-family: 'Inter', 'Noto Sans JP', system-ui, sans-serif;
    }

    body {
      background:
        radial-gradient(ellipse 80% 55% at 50% -5%, rgba(59, 158, 255, .2) 0%, transparent 65%),
        radial-gradient(ellipse 55% 40% at 85% 95%, rgba(124, 58, 237, .15) 0%, transparent 60%),
        linear-gradient(155deg, #060d1a 0%, #0d1f3c 50%, #080f20 100%);
      background-attachment: fixed;
      color: var(--ink);
      padding-top: calc(var(--header-h) + 12px);
    }

    /* Header */
    header {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
      min-height: var(--header-h);
      background: rgba(6, 13, 26, .88);
      backdrop-filter: blur(18px) saturate(180%);
      border-bottom: 1px solid var(--border);
    }

    .wrap {
      max-width: 1100px;
      margin: 0 auto;
      padding: 0 18px;
    }

    header .wrap {
      height: var(--header-h);
      display: flex;
      align-items: center;
      padding: 0 18px;
    }

    .hdr {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      width: 100%;
    }

    .hdr-left {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .nav-logo-mark {
      width: 32px;
      height: 32px;
      border-radius: 9px;
      overflow: hidden;
      background: linear-gradient(135deg, var(--sky), var(--violet));
      flex-shrink: 0;
    }

    .nav-logo-mark img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .title {
      font-size: 17px;
      font-weight: 900;
      color: var(--ink);
    }

    .crumb {
      color: var(--muted);
      font-size: 11px;
      margin-top: 2px;
    }

    /* Main layout */
    main.wrap {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 18px;
      padding: 18px;
      align-items: start;
    }

    @media(max-width:980px) {
      main.wrap {
        grid-template-columns: 1fr;
      }
    }

    /* Section card */
    .section {
      background: rgba(13, 17, 27, .65);
      backdrop-filter: blur(16px) saturate(160%);
      border: 1px solid var(--border);
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 0 8px 32px rgba(0, 0, 0, .4), inset 0 1px 0 rgba(255, 255, 255, .06);
      transform: translateY(6px);
      opacity: 0;
      animation: slideIn .45s ease forwards;
    }

    .section+.section {
      margin-top: 14px;
    }

    @keyframes slideIn {
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .section-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 16px;
      border-bottom: 1px solid rgba(255, 255, 255, .07);
      background: rgba(59, 158, 255, .05);
    }

    .section-head h2 {
      margin: 0;
      font-size: 14px;
      font-weight: 900;
      color: #7dc8ff;
      letter-spacing: .5px;
    }

    .section-body {
      padding: 14px 16px;
    }

    .row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }

    @media(max-width:600px) {
      .row {
        grid-template-columns: 1fr;
      }
    }

    .pair {
      display: flex;
      flex-direction: column;
      gap: 2px;
      padding: 6px 0;
      border-bottom: 1px dashed rgba(255, 255, 255, .06);
    }

    .pair:last-child {
      border-bottom: none;
    }

    .pair dt {
      font-size: 11px;
      font-weight: 700;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .5px;
    }

    .pair dd {
      font-size: 14px;
      color: var(--ink);
      word-break: break-word;
    }

    .dl-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0 16px;
    }

    @media(max-width:600px) {
      .dl-grid {
        grid-template-columns: 1fr;
      }
    }

    /* Table */
    table.tbl {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0 4px;
      font-size: 13px;
    }

    table.tbl th {
      font-size: 11px;
      color: var(--muted);
      font-weight: 700;
      padding: 4px 8px;
      text-align: left;
    }

    table.tbl td {
      padding: 8px;
      background: rgba(255, 255, 255, .04);
      border-bottom: 1px solid rgba(255, 255, 255, .05);
    }

    table.tbl tr:last-child td {
      border-bottom: none;
    }

    /* Photo */
    .photo-wrap {
      text-align: center;
      margin-bottom: 12px;
    }

    .photo-wrap img {
      max-width: 120px;
      border-radius: 10px;
      border: 2px solid rgba(255, 255, 255, .12);
      box-shadow: 0 4px 16px rgba(0, 0, 0, .4);
    }

    /* Buttons */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 10px 18px;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 700;
      font-family: inherit;
      border: 1px solid var(--border);
      background: rgba(255, 255, 255, .07);
      color: var(--ink);
      text-decoration: none;
      cursor: pointer;
      transition: background .2s, border-color .2s;
    }

    .btn:hover {
      background: rgba(255, 255, 255, .13);
      border-color: rgba(255, 255, 255, .2);
    }

    .btn.primary {
      background: linear-gradient(135deg, var(--sky), var(--sky2));
      border-color: var(--sky);
      color: #fff;
      box-shadow: 0 4px 16px rgba(59, 158, 255, .35);
    }

    .btn.primary:hover {
      filter: brightness(1.1);
    }

    .btn.green {
      background: linear-gradient(135deg, #3fb950, #2ea043);
      border-color: #3fb950;
      color: #fff;
      box-shadow: 0 4px 14px rgba(63, 185, 80, .35);
    }

    .btn.green:hover {
      filter: brightness(1.1);
    }

    .btn.danger {
      background: rgba(248, 81, 73, .15);
      border-color: rgba(248, 81, 73, .3);
      color: #f85149;
    }

    .btn.danger:hover {
      background: rgba(248, 81, 73, .25);
    }

    .btn[disabled],
    .btn.disabled {
      opacity: .5;
      cursor: not-allowed;
      transform: none !important;
      box-shadow: none !important;
    }

    /* Aside sticky */
    main.wrap>aside {
      position: sticky;
      top: calc(var(--header-h) + 16px);
    }

    .aside-card {
      background: rgba(13, 17, 27, .65);
      backdrop-filter: blur(16px);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 20px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, .4);
    }

    .aside-card+.aside-card {
      margin-top: 14px;
    }

    .aside-title {
      font-size: 13px;
      font-weight: 800;
      color: #7dc8ff;
      margin-bottom: 12px;
      letter-spacing: .5px;
      text-transform: uppercase;
    }

    .aside-btn-row {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    /* Inputs */
    .input {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--input-bd);
      border-radius: 10px;
      font-size: 14px;
      outline: none;
      background: var(--input-bg);
      color: var(--ink);
      font-family: inherit;
    }

    .input:focus {
      box-shadow: 0 0 0 3px rgba(59, 158, 255, .2);
      border-color: var(--sky);
    }

    /* Form rows */
    .form-row {
      margin: 10px 0;
    }

    .small {
      font-size: 12px;
      color: var(--muted);
    }

    /* Notes */
    .note {
      background: rgba(227, 179, 65, .08);
      border: 1px solid rgba(227, 179, 65, .2);
      color: #e3b341;
      padding: 10px 12px;
      border-radius: 12px;
      margin: 10px 0;
      font-size: 13px;
    }

    .okbox {
      background: rgba(63, 185, 80, .08);
      border: 1px solid rgba(63, 185, 80, .2);
      color: #56d364;
      padding: 10px 12px;
      border-radius: 12px;
      margin: 10px 0;
      font-weight: 700;
    }

    /* Modal */
    .modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 900;
      background: rgba(0, 0, 0, .7);
      backdrop-filter: blur(4px);
      align-items: center;
      justify-content: center;
    }

    .modal-backdrop.open {
      display: flex;
    }

    .modal {
      background: rgba(13, 17, 27, .95);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 24px;
      max-width: 440px;
      width: 90%;
      box-shadow: 0 20px 60px rgba(0, 0, 0, .6);
    }

    .modal h3 {
      margin: 0 0 10px;
      font-size: 18px;
    }

    .modal p {
      margin: 0 0 14px;
      color: var(--muted);
      font-size: 14px;
    }

    .modal .rowbtn {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      flex-wrap: wrap;
    }

    .modal .btn {
      padding: 10px 16px;
    }

    @media(max-width:980px) {
      :root {
        --header-h: 84px;
      }

      main.wrap>aside {
        position: static;
      }
    }

    @media(max-width:600px) {
      .row {
        grid-template-columns: 1fr;
      }
    }
  <style>
    /* If embedded, hide header and aside, and adjust layout */
    <?php if (!empty($_GET['embedded'])): ?>
      header, aside { display: none !important; }
      main.wrap { display: block !important; padding-top: 10px; }
      body { padding-top: 0 !important; background: transparent !important; }
      .section { border: 1px solid rgba(255,255,255,.05); box-shadow: none; background: rgba(0,0,0,.2); }
    <?php endif; ?>
  </style>

</head>
<body>

  <!-- ===== HEADER ===== -->
  <?php if (empty($_GET['embedded'])): ?>
  <header>
    <div class="wrap">
      <div class="hdr">
        <div class="hdr-left">
          <div class="nav-logo-mark">
            <img src="https://it-future.jp/images/android-chrome-192x192.png" alt="ITF" onerror="this.style.display='none'">
          </div>
          <div>
            <h1 class="title">履歴書プレビュー</h1>
            <p class="crumb">ホーム ＞ 履歴書メーカー ＞ 介護向け ＞ プレビュー</p>
          </div>
        </div>
        <a class="btn" href="<?= h($headerBackHref) ?>"><?= h($headerBackLabel) ?></a>
      </div>
    </div>
  </header>
  <?php endif; ?>


  <!-- Modal: application success -->
  <div id="appModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal">
      <h3>ご応募ありがとうございます。</h3>
      <p>担当者より近日中にご連絡差し上げます。</p>
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
        <a class="btn primary" href="/rireki/">履歴書メーカーへ</a>
      </div>
    </div>
  </div>

  <main class="wrap">
    <!-- ===== MAIN SECTION CARDS ===== -->
    <section>

      <!-- STEP 1: 基本情報 -->
      <div class="section" style="animation-delay:.05s">
        <div class="section-head">
          <h2>📋 STEP 1 — 基本情報</h2>
          <a class="btn" href="<?= h($editBase) ?>#step-1" style="font-size:12px;padding:5px 10px">編集</a>
        </div>
        <div class="section-body">
          <?php if ($hasPhoto): ?>
          <div class="photo-wrap">
            <img src="<?= h($photoPath) ?>" alt="証明写真">
          </div>
          <?php endif; ?>
          <div class="dl-grid">
            <?php foreach ($step1 as $k => $v): ?>
            <dl class="pair">
              <dt><?= h($k) ?></dt>
              <dd><?= $v !== '' ? h($v) : '<span style="color:var(--muted)">—</span>' ?></dd>
            </dl>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- STEP 2: 在留・写真 -->
      <div class="section" style="animation-delay:.1s">
        <div class="section-head">
          <h2>🪪 STEP 2 — 在留資格・渡航</h2>
          <a class="btn" href="<?= h($editBase) ?>#step-2" style="font-size:12px;padding:5px 10px">編集</a>
        </div>
        <div class="section-body">
          <div class="dl-grid">
            <?php foreach ($step2 as $k => $v): ?>
            <dl class="pair">
              <dt><?= h($k) ?></dt>
              <dd><?= $v !== '' ? h($v) : '<span style="color:var(--muted)">—</span>' ?></dd>
            </dl>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- STEP 3: 学歴・免許 -->
      <div class="section" style="animation-delay:.15s">
        <div class="section-head">
          <h2>🎓 STEP 3 — 学歴・資格・免許</h2>
          <a class="btn" href="<?= h($editBase) ?>#step-3" style="font-size:12px;padding:5px 10px">編集</a>
        </div>
        <div class="section-body">
          <?php if (!empty($eduRows)): ?>
          <p style="font-size:12px;color:var(--muted);margin-bottom:8px;font-weight:700;text-transform:uppercase;letter-spacing:.5px">学歴</p>
          <table class="tbl" style="margin-bottom:16px">
            <thead><tr>
              <th>開始</th><th>終了</th><th>在学状況</th><th>学校名</th><th>学部・専攻</th>
            </tr></thead>
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
          <?php endif; ?>

          <?php if (!empty($licRows)): ?>
          <p style="font-size:12px;color:var(--muted);margin-bottom:8px;font-weight:700;text-transform:uppercase;letter-spacing:.5px">資格・免許</p>
          <table class="tbl">
            <thead><tr><th>取得年</th><th>取得月</th><th>資格名</th></tr></thead>
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
          <?php endif; ?>
          <?php if (empty($eduRows) && empty($licRows)): ?>
          <p style="color:var(--muted);font-size:13px">入力なし</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- STEP 4: 職歴 -->
      <div class="section" style="animation-delay:.2s">
        <div class="section-head">
          <h2>💼 STEP 4 — 職歴</h2>
          <a class="btn" href="<?= h($editBase) ?>#step-4" style="font-size:12px;padding:5px 10px">編集</a>
        </div>
        <div class="section-body">
          <?php if (!empty($workRows)): ?>
          <table class="tbl">
            <thead><tr>
              <th>開始</th><th>状況</th><th>終了</th><th>会社・施設名</th><th>職種/役職</th><th>仕事内容</th>
            </tr></thead>
            <tbody>
              <?php foreach ($workRows as $r): ?>
              <tr>
                <td><?= h($r['開始']) ?></td>
                <td><?= h($r['在職状況']) ?></td>
                <td><?= h($r['終了']) ?></td>
                <td><?= h($r['会社・施設名']) ?></td>
                <td><?= h($r['職種/役職']) ?></td>
                <td style="white-space:pre-line"><?= h($r['仕事内容']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php else: ?>
          <p style="color:var(--muted);font-size:13px">入力なし</p>
          <?php endif; ?>

          <?php if (!empty($reasonResign)): ?>
          <dl class="pair" style="margin-top:12px">
            <dt>退職理由</dt><dd><?= h($reasonResign) ?></dd>
          </dl>
          <?php endif; ?>
          <?php if (!empty($plannedResign)): ?>
          <dl class="pair">
            <dt>退職（予定）年月</dt><dd><?= h($plannedResign) ?></dd>
          </dl>
          <?php endif; ?>
        </div>
      </div>

      <!-- STEP 5: 自己PR・志望 -->
      <div class="section" style="animation-delay:.25s">
        <div class="section-head">
          <h2>✍️ STEP 5 — 自己PR・志望動機</h2>
          <a class="btn" href="<?= h($editBase) ?>#step-5" style="font-size:12px;padding:5px 10px">編集</a>
        </div>
        <div class="section-body">
          <div class="dl-grid">
            <?php foreach ($step5 as $k => $v): ?>
            <dl class="pair" style="<?= in_array($k, ['自己PR','志望の動機','本人希望（職種・給与・勤務地など）'], true) ? 'grid-column:1/-1' : '' ?>">
              <dt><?= h($k) ?></dt>
              <dd style="white-space:pre-line"><?= $v !== '' ? h($v) : '<span style="color:var(--muted)">—</span>' ?></dd>
            </dl>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- STEP 6: 別途情報 -->
      <div class="section" style="animation-delay:.3s">
        <div class="section-head">
          <h2>ℹ️ STEP 6 — 別途情報</h2>
          <a class="btn" href="<?= h($editBase) ?>#step-6" style="font-size:12px;padding:5px 10px">編集</a>
        </div>
        <div class="section-body">
          <div class="dl-grid">
            <?php foreach ($step6 as $k => $v): ?>
            <dl class="pair">
              <dt><?= h($k) ?></dt>
              <dd><?= $v !== '' ? h($v) : '<span style="color:var(--muted)">—</span>' ?></dd>
            </dl>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Apply form (if job application flow) -->
      <?php if ($flow === 'apply' && $job_id > 0 && $session_uid > 0): ?>
      <div class="section" style="animation-delay:.35s">
        <div class="section-head"><h2>📨 求人への応募</h2></div>
        <div class="section-body">
          <p style="font-size:14px;margin-bottom:14px;color:var(--muted)">上記内容で応募します。よろしければ送信してください。</p>
          <form id="applyForm" action="/rireki/kaigo/php/submit_rireki.php" method="post">
            <input type="hidden" name="token" value="<?= h($token) ?>">
            <input type="hidden" name="job_id" value="<?= h($job_id) ?>">
            <input type="hidden" name="flow" value="apply">
            <button class="btn primary" type="submit" style="width:100%;justify-content:center">この内容で応募する</button>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <!-- Save profile form (profile_only flow) -->
      <?php if ($flow === 'profile_only' && $session_uid > 0): ?>
      <div class="section" style="animation-delay:.35s">
        <div class="section-head"><h2>💾 プロフィールを更新</h2></div>
        <div class="section-body">
          <p style="font-size:14px;margin-bottom:14px;color:var(--muted)">最新の情報を保存しておくと、次回以降すぐに応募できます。</p>
          <form id="saveProfileForm" action="<?= h($_SERVER['REQUEST_URI']) ?>" method="post">
            <input type="hidden" name="__action" value="save_profile">
            <button class="btn primary" type="submit" style="width:100%;justify-content:center">プロフィールを保存する</button>
          </form>
        </div>
      </div>
      <?php endif; ?>

    </section>

    <!-- ===== ASIDE ===== -->
    <?php if (empty($_GET['embedded'])): ?>
    <aside>

      <!-- Excel export card -->
      <div class="aside-card">
        <div class="aside-title">履歴書出力（Excel）</div>
        <div class="aside-btn-row">
          <?php if ($session_uid <= 0): ?>
            <div class="note">ダウンロードは「無料登録」後に有効になります。</div>
            <button class="btn primary" type="button" disabled style="width:100%;justify-content:center">登録するとダウンロードできます</button>
          <?php else: ?>
            <p id="exportStatus" class="small" style="margin:0 0 8px">Excel（.xls）を生成してダウンロードできます。</p>
            <button id="btnBuildXls" class="btn primary" type="button" style="width:100%;justify-content:center">Excelを作成・ダウンロード</button>
            <div id="exportResult" style="display:none;margin-top:12px">
              <div class="okbox">完成しました！</div>
              <div style="display:flex;flex-direction:column;gap:8px;margin-top:10px">
                <a id="xlsDownloadLink" class="btn green" href="#" download style="justify-content:center">Excel（.xls）をダウンロード</a>
                <a id="claimLink" class="btn" href="#" style="justify-content:center">アカウントに保存</a>
              </div>
              <p id="exportTokenText" class="small" style="margin-top:8px"></p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Quick links card -->
      <div class="aside-card">
        <div class="aside-title">アクション</div>
        <div class="aside-btn-row">
          <a class="btn" href="<?= h($editBase) ?>" style="justify-content:center">フォームを編集する</a>
          <a class="btn" href="/saiyou.php" style="justify-content:center">求人を探す</a>
          <a class="btn" href="/rireki/" style="justify-content:center">フォーマット選択へ</a>
        </div>
      </div>

      <!-- Checklist card -->
      <div class="aside-card">
        <div class="aside-title">最終チェックリスト</div>
        <ul style="font-size:13px;color:var(--muted);padding-left:1.2em;line-height:1.8">
          <li>氏名・住所・連絡先は正確？</li>
          <li>在留期限・旅券番号の扱いはOK？</li>
          <li>学歴/職歴の年月は時系列？</li>
          <li>写真は明るく背景シンプル？</li>
        </ul>
      </div>

    </aside>
    <?php endif; ?>
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

        // fire-and-forget: recent activity log (staff dashboard)
        try {
          const fd2 = new FormData();
          fd2.append('action', 'log_apply_activity');
          fd2.append('token', "<?= h($token) ?>");
          fd2.append('job_id', "<?= h($job_id) ?>");
          await fetch(window.location.pathname + window.location.search, { method: 'POST', body: fd2, credentials: 'same-origin' });
        } catch (_) { }
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