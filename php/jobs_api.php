<?php
// /home/it-future/www/itf/php/jobs_api.php
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_samesite', 'Lax');
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'Unauthorized']);
  exit;
}

$JOB_ADMIN_USERS = ['osaka_ueda', 'bikash', 'kimura'];
$currentUser = (string)($_SESSION['username'] ?? '');
if (!in_array($currentUser, $JOB_ADMIN_USERS, true)) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'error'=>'Forbidden']);
  exit;
}

require_once __DIR__ . '/db_connect.php';

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function json_out($arr, $code=200){
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

function require_csrf(){
  $t = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
  if (!$t || !hash_equals($_SESSION['csrf_token'] ?? '', $t)) {
    json_out(['ok'=>false,'error'=>'CSRF invalid'], 403);
  }
}

function normalize_status($s){
  $s = trim((string)$s);
  if ($s === '0') return '募集中';
  if ($s === '1') return '急募';
  if ($s === '2') return '募集終';
  if ($s === '') return '募集中';
  if ($s === '募集終了') return '募集終';
  if ($s === '募集終わり') return '募集終';
  if (in_array($s, ['募集中','急募','募集終'], true)) return $s;
  return '募集中';
}

function normalize_ok3($v){
  $v = trim((string)$v);
  if ($v === '') return null;
  if (in_array($v, ['OK','ok','both','Both','BOTH','どちらでもＯＫ','どちらでもok'], true)) return 'どちらでもOK';
  return $v;
}

function normalize_yesno_to_int($v): int {
  $s = trim((string)$v);
  if ($s === 'あり' || $s === '有' || $s === '1' || strtolower($s)==='true' || strtolower($s)==='yes') return 1;
  return 0;
}

function parse_json_array($raw){
  if ($raw === null || $raw === '') return [];
  if (is_array($raw)) return $raw;
  $s = trim((string)$raw);
  if ($s === '') return [];
  $j = json_decode($s, true);
  if (json_last_error() === JSON_ERROR_NONE && is_array($j)) {
    return array_values(array_filter(array_map('strval', $j), fn($x)=>trim($x)!==''));
  }
  $parts = preg_split('/\s*,\s*/u', $s);
  $parts = array_values(array_filter(array_map('trim', $parts), fn($x)=>$x!=='' ));
  return $parts;
}

/**
 * UIで扱うフィールド（DBに無いものは自動で除外してSELECT/UPDATE）
 */
function desired_post_fields(): array {
  return [
    'id','staff_id','posted_by','job_staff_id','publish_state','status','request_date','deadline_date',
    'title','summary','content','company_name','org_work_type','job_location',
    'required_vacancy','japanese_level','level',
    'updated_at','date',

    // 労働条件(11points)
    'work_location_detail','contract_period','probation_period','job_change_scope','workplace_change_scope',
    'work_hours_shift','break_time','overtime','holidays','paid_leave','annual_holidays',

    // 募集要項
    'current_residence','required_age','gender_pref','experience','skills_certifications','hijab_policy',
    'preferred_nationalities',

    // 給与・待遇
    'salary','salary_basic','tax_pension_insurance','salary_takehome',
    'bonuses','bonus_amount',
    'transportation_charges','transport_amount_limit',
    'rent_support',
    'life_support',
    'visa_support',
    'social_insurance',
    'salary_increment','increment_condition',

    // 喫煙
    'smoking',
  ];
}

function posts_existing_cols(PDO $pdo): array {
  static $cache = null;
  if (is_array($cache)) return $cache;

  $st = $pdo->query("
    SELECT COLUMN_NAME
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts'
  ");
  $cols = $st->fetchAll(PDO::FETCH_COLUMN);
  $cache = array_fill_keys($cols, true);
  return $cache;
}

function field_exists(PDO $pdo, string $field): bool {
  $exists = posts_existing_cols($pdo);
  return isset($exists[$field]);
}

function is_allowed_field(PDO $pdo, string $field): bool {
  if ($field === '') return false;
  $desired = array_fill_keys(desired_post_fields(), true);
  return isset($desired[$field]) && field_exists($pdo, $field);
}

function select_cols_sql(PDO $pdo): string {
  $exists = posts_existing_cols($pdo);
  $cols = [];
  foreach (desired_post_fields() as $f) {
    if (isset($exists[$f])) $cols[] = "`$f`";
  }
  if (!in_array('`id`', $cols, true)) array_unshift($cols, '`id`');
  return implode(',', $cols);
}

function format_row_for_ui(PDO $pdo, array $row, array $filesMap = []): array {
  $jid = (int)($row['id'] ?? 0);

  if (isset($row['status'])) {
    $row['status'] = normalize_status($row['status']);
  }

  if (isset($row['job_staff_id'])) {
    if ($row['job_staff_id'] === '' || $row['job_staff_id'] === null) $row['job_staff_id'] = null;
    else $row['job_staff_id'] = (int)$row['job_staff_id'];
  }

  // tinyint fields -> int (keep 0/1; UI decides labels)
  $tinyFields = ['bonuses','transportation_charges','life_support','visa_support','social_insurance','salary_increment'];
  foreach($tinyFields as $f){
    if (isset($row[$f]) && field_exists($pdo, $f)) {
      $row[$f] = (int)($row[$f] ?? 0);
    }
  }

  if (isset($row['current_residence'])) $row['current_residence'] = normalize_ok3($row['current_residence']) ?: ($row['current_residence'] ?? null);
  if (isset($row['gender_pref']))      $row['gender_pref']      = normalize_ok3($row['gender_pref'])      ?: ($row['gender_pref'] ?? null);
  if (isset($row['experience']))       $row['experience']       = normalize_ok3($row['experience'])       ?: ($row['experience'] ?? null);

  // preferred_nationalities: JSON string -> array for UI multiselect
  if (isset($row['preferred_nationalities'])) {
    $arr = [];
    if (!empty($row['preferred_nationalities'])) {
      $decoded = json_decode((string)$row['preferred_nationalities'], true);
      if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $arr = array_values(array_filter(array_map('strval', $decoded), fn($x)=>trim($x)!=='' ));
      } else {
        // fallback: comma separated
        $arr = array_values(array_filter(array_map('trim', explode(',', (string)$row['preferred_nationalities'])), fn($x)=>$x!==''));
      }
    }
    $row['preferred_nationalities'] = $arr;
  }

  $row['files_preview'] = $filesMap[$jid] ?? [];
  return $row;
}

try {

  if ($action === 'csrf') {
    json_out(['ok'=>true,'csrf_token'=>($_SESSION['csrf_token'] ?? '')]);
  }

  // map staff names -> id
  $staffByName = [];
  try {
    $st = $pdo->query("SELECT id, name FROM staff");
    while($r = $st->fetch(PDO::FETCH_ASSOC)){
      $staffByName[(string)$r['name']] = (int)$r['id'];
    }
  } catch(Throwable $e) {}

  if ($action === 'list') {
    $stmt = $pdo->query("
      SELECT ".select_cols_sql($pdo)."
      FROM posts
      WHERE post_type='job'
      ORDER BY updated_at DESC, id DESC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $ids = array_map(fn($r)=> (int)$r['id'], $rows);
    $filesMap = [];
    if (count($ids) > 0) {
      $in = implode(',', array_fill(0, count($ids), '?'));
      $st2 = $pdo->prepare("SELECT job_post_id, id, file_path, file_name, mime, created_at FROM job_files WHERE job_post_id IN ($in) ORDER BY created_at DESC");
      $st2->execute($ids);
      while($f = $st2->fetch(PDO::FETCH_ASSOC)){
        $jid = (int)$f['job_post_id'];
        if (!isset($filesMap[$jid])) $filesMap[$jid] = [];
        if (count($filesMap[$jid]) < 3) $filesMap[$jid][] = $f;
      }
    }

    foreach($rows as &$r){ $r = format_row_for_ui($pdo, $r, $filesMap); }
    json_out(['ok'=>true,'rows'=>$rows]);
  }

  if ($action === 'create') {
    require_csrf();

    // defaults (only include columns that actually exist)
    $defaults = [
      'post_type'    => 'job',
      'publish_state'=> 'draft',
      'status'       => '募集中',
      'title'        => '',
      'summary'      => '',
      'content'      => '',
      'staff_id'     => (int)$_SESSION['id'],
      'posted_by'    => (string)$_SESSION['username'],
      'job_staff_id' => null,
      'company_name' => '',
      'org_work_type'=> '',
      'job_location' => '',
      'date'         => date('Y-m-d'),

      // boolean defaults
      'bonuses' => 0,
      'transportation_charges' => 0,
      'life_support' => 0,
      'visa_support' => 0,
      'social_insurance' => 0,
      'salary_increment' => 0,

      // array defaults
      'preferred_nationalities' => null,

      // misc
      'smoking' => '',
    ];

    $textDefaults = [
      'level','salary','salary_basic','salary_takehome','tax_pension_insurance','bonus_amount',
      'transport_amount_limit','rent_support','increment_condition',
      'current_residence','required_age','gender_pref','experience','skills_certifications','hijab_policy',
      'work_location_detail','contract_period','probation_period','job_change_scope','workplace_change_scope',
      'work_hours_shift','break_time','overtime','holidays','paid_leave','annual_holidays',
      'required_vacancy','japanese_level',
      'request_date','deadline_date',
    ];

    $exists = posts_existing_cols($pdo);

    foreach ($textDefaults as $f) {
      if (isset($exists[$f]) && !array_key_exists($f, $defaults)) $defaults[$f] = '';
    }

    $cols = [];
    $ph   = [];
    $vals = [];
    foreach ($defaults as $k=>$v) {
      if (!isset($exists[$k])) continue;
      $cols[] = "`$k`";
      $ph[]   = "?";
      $vals[] = $v;
    }

    $sql = "INSERT INTO posts (".implode(',', $cols).") VALUES (".implode(',', $ph).")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($vals);

    $id = (int)$pdo->lastInsertId();
    json_out(['ok'=>true,'id'=>$id]);
  }

  if ($action === 'updateRow') {
    require_csrf();

    $id = (int)($_POST['id'] ?? 0);
    $data = json_decode($_POST['data'] ?? '{}', true);
    if ($id <= 0 || !is_array($data)) json_out(['ok'=>false,'error'=>'Invalid data'], 400);

    $updates = [];
    $values  = [];

    foreach($data as $field => $value) {
      $field = (string)$field;
      if (!is_allowed_field($pdo, $field)) continue;

      if ($field === 'status') $value = normalize_status($value);

      if ($field === 'job_staff_id') {
        if ($value === '' || $value === null) $value = null;
        else {
          $sv = trim((string)$value);
          $value = ctype_digit($sv) ? (int)$sv : ($staffByName[$sv] ?? null);
        }
      }

      if ($field === 'publish_state') {
        $v = trim((string)$value);
        if ($v === '下書き') $v = 'draft';
        if ($v === '公開') $v = 'published';
        if ($v === 'アーカイブ') $v = 'archived';
        $value = in_array($v, ['draft','published','archived'], true) ? $v : 'draft';
      }

      // tinyint fields
      $yesNoFields = ['bonuses','transportation_charges','life_support','visa_support','social_insurance','salary_increment'];
      if (in_array($field, $yesNoFields, true)) {
        $value = normalize_yesno_to_int($value);
      }

      if ($field === 'current_residence') {
        $v = normalize_ok3($value);
        $value = in_array($v, ['国内','国外','どちらでもOK', null, ''], true) ? $v : null;
      }
      if ($field === 'gender_pref') {
        $v = normalize_ok3($value);
        $value = in_array($v, ['男','女','どちらでもOK', null, ''], true) ? $v : null;
      }
      if ($field === 'experience') {
        $v = normalize_ok3($value);
        $value = in_array($v, ['あり','なし','どちらでもOK', null, ''], true) ? $v : null;
      }
      if ($field === 'hijab_policy') {
        $v = trim((string)$value);
        $value = in_array($v, ['OK','禁止', null, ''], true) ? $v : null;
      }

      // preferred_nationalities: array/json/comma -> JSON string
      if ($field === 'preferred_nationalities') {
        $arr = parse_json_array($value);
        $arr = array_values(array_unique($arr));
        $json = json_encode($arr, JSON_UNESCAPED_UNICODE);
        $value = ($json === '[]') ? null : $json;
      }

      if (($field === 'request_date' || $field === 'deadline_date') && ($value === '' || $value === null)) {
        $value = null;
      }

      $updates[] = "`$field` = ?";
      $values[]  = $value;
    }

    if (count($updates) > 0) {
      $values[] = $id;
      $sql = "UPDATE posts SET ".implode(', ', $updates).", updated_at = NOW() WHERE id = ? AND post_type='job'";
      $st = $pdo->prepare($sql);
      $st->execute($values);
    }

    $st2 = $pdo->prepare("SELECT ".select_cols_sql($pdo)." FROM posts WHERE id=? AND post_type='job'");
    $st2->execute([$id]);
    $row = $st2->fetch(PDO::FETCH_ASSOC);

    if ($row) {
      $st3 = $pdo->prepare("SELECT job_post_id, id, file_path, file_name, mime, created_at FROM job_files WHERE job_post_id = ? ORDER BY created_at DESC LIMIT 3");
      $st3->execute([$id]);
      $filesMap = [];
      while($f = $st3->fetch(PDO::FETCH_ASSOC)){
        if (!isset($filesMap[$id])) $filesMap[$id] = [];
        $filesMap[$id][] = $f;
      }
      $row = format_row_for_ui($pdo, $row, $filesMap);
    }

    json_out(['ok'=>true,'row'=>$row]);
  }

  if ($action === 'updateCell') {
    require_csrf();

    $id    = (int)($_POST['id'] ?? 0);
    $field = (string)($_POST['field'] ?? '');
    $value = $_POST['value'] ?? null;

    if ($id <= 0 || !is_allowed_field($pdo, $field)) {
      json_out(['ok'=>false,'error'=>'Invalid field/id'], 400);
    }

    if (($field === 'request_date' || $field === 'deadline_date') && ($value === '' || $value === null)) {
      $value = null;
    }

    if ($field === 'status') $value = normalize_status($value);

    if ($field === 'job_staff_id') {
      if ($value === '' || $value === null) $value = null;
      else {
        $sv = trim((string)$value);
        $value = ctype_digit($sv) ? (int)$sv : ($staffByName[$sv] ?? null);
      }
    }

    $yesNoFields = ['bonuses','transportation_charges','life_support','visa_support','social_insurance','salary_increment'];
    if (in_array($field, $yesNoFields, true)) {
      $value = normalize_yesno_to_int($value);
    }

    if ($field === 'current_residence') {
      $v = normalize_ok3($value);
      $value = in_array($v, ['国内','国外','どちらでもOK', null, ''], true) ? $v : null;
    }
    if ($field === 'gender_pref') {
      $v = normalize_ok3($value);
      $value = in_array($v, ['男','女','どちらでもOK', null, ''], true) ? $v : null;
    }
    if ($field === 'experience') {
      $v = normalize_ok3($value);
      $value = in_array($v, ['あり','なし','どちらでもOK', null, ''], true) ? $v : null;
    }
    if ($field === 'hijab_policy') {
      $v = trim((string)$value);
      $value = in_array($v, ['OK','禁止', null, ''], true) ? $v : null;
    }

    if ($field === 'preferred_nationalities') {
      $arr = parse_json_array($value);
      $arr = array_values(array_unique(array_values(array_filter(array_map('strval', $arr), fn($x)=>trim($x)!=='' ))));
      $json = json_encode($arr, JSON_UNESCAPED_UNICODE);
      $value = ($json === '[]') ? null : $json;
    }

    if ($field === 'publish_state') {
      $v = trim((string)$value);
      if ($v === '下書き') $v = 'draft';
      if ($v === '公開') $v = 'published';
      if ($v === 'アーカイブ') $v = 'archived';
      $value = in_array($v, ['draft','published','archived'], true) ? $v : 'draft';
    }

    $sql = "UPDATE posts SET `$field` = ?, updated_at = NOW() WHERE id = ? AND post_type='job'";
    $st = $pdo->prepare($sql);
    $st->execute([$value, $id]);

    $st2 = $pdo->prepare("SELECT ".select_cols_sql($pdo)." FROM posts WHERE id=? AND post_type='job'");
    $st2->execute([$id]);
    $row = $st2->fetch(PDO::FETCH_ASSOC);

    if ($row) {
      $st3 = $pdo->prepare("SELECT job_post_id, id, file_path, file_name, mime, created_at FROM job_files WHERE job_post_id = ? ORDER BY created_at DESC LIMIT 3");
      $st3->execute([$id]);
      $filesMap = [];
      while($f = $st3->fetch(PDO::FETCH_ASSOC)){
        if (!isset($filesMap[$id])) $filesMap[$id] = [];
        $filesMap[$id][] = $f;
      }
      $row = format_row_for_ui($pdo, $row, $filesMap);
    }

    json_out(['ok'=>true,'row'=>$row]);
  }

  if ($action === 'delete') {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) json_out(['ok'=>false,'error'=>'Invalid id'], 400);

    // job_files は FK ON DELETE CASCADE で自動削除される想定
    $pdo->prepare("DELETE FROM posts WHERE id=? AND post_type='job'")->execute([$id]);
    json_out(['ok'=>true]);
  }

  if ($action === 'files') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) json_out(['ok'=>false,'error'=>'Invalid id'], 400);
    $st = $pdo->prepare("SELECT id, file_path, file_name, mime, created_at FROM job_files WHERE job_post_id=? ORDER BY created_at DESC");
    $st->execute([$id]);
    json_out(['ok'=>true,'files'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
  }

  if ($action === 'deleteFile') {
    require_csrf();

    $fileId = (int)($_POST['file_id'] ?? 0);
    $jobId  = (int)($_POST['job_id'] ?? 0);
    if ($fileId <= 0 || $jobId <= 0) json_out(['ok'=>false,'error'=>'Invalid request'], 400);

    $st = $pdo->prepare("SELECT id, job_post_id, file_path FROM job_files WHERE id=? AND job_post_id=?");
    $st->execute([$fileId, $jobId]);
    $f = $st->fetch(PDO::FETCH_ASSOC);
    if (!$f) json_out(['ok'=>false,'error'=>'File not found'], 404);

    $filePath = (string)($f['file_path'] ?? '');

    $pdo->prepare("DELETE FROM job_files WHERE id=? AND job_post_id=?")->execute([$fileId, $jobId]);

    if ($filePath !== '' && str_starts_with($filePath, '/uploads/jobs/')) {
      $base = realpath(__DIR__ . "/.."); // /home/it-future/www/itf
      $abs  = realpath($base . $filePath);
      $uploadsRoot = realpath($base . "/uploads/jobs");
      if ($abs && $uploadsRoot && str_starts_with($abs, $uploadsRoot) && is_file($abs)) {
        @unlink($abs);
      }
    }

    $pdo->prepare("UPDATE posts SET updated_at=NOW() WHERE id=? AND post_type='job'")->execute([$jobId]);
    json_out(['ok'=>true]);
  }

  if ($action === 'uploadFile') {
    require_csrf();

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) json_out(['ok'=>false,'error'=>'Invalid job id'], 400);

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
      json_out(['ok'=>false,'error'=>'Upload failed'], 400);
    }

    $tmp  = $_FILES['file']['tmp_name'];
    $mime = mime_content_type($tmp) ?: '';
    $size = (int)$_FILES['file']['size'];

    $allowed = ['image/jpeg','image/png','image/webp','application/pdf'];
    if (!in_array($mime, $allowed, true)) {
      json_out(['ok'=>false,'error'=>'Only JPG/PNG/WEBP/PDF allowed'], 400);
    }
    if ($size > 10 * 1024 * 1024) {
      json_out(['ok'=>false,'error'=>'Max 10MB'], 400);
    }

    $dir = __DIR__ . "/../uploads/jobs/$id";
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $orig = basename($_FILES['file']['name']);
    $safe = preg_replace('/[^a-zA-Z0-9._-]/','_', $orig);
    $name = uniqid('jf_', true) . "_" . $safe;

    $destAbs = "$dir/$name";
    if (!move_uploaded_file($tmp, $destAbs)) {
      json_out(['ok'=>false,'error'=>'Move failed'], 500);
    }

    $publicPath = "/uploads/jobs/$id/$name";
    $st = $pdo->prepare("INSERT INTO job_files (job_post_id, file_path, file_name, mime) VALUES (?,?,?,?)");
    $st->execute([$id, $publicPath, $orig, $mime]);

    $pdo->prepare("UPDATE posts SET updated_at=NOW() WHERE id=? AND post_type='job'")->execute([$id]);
    json_out(['ok'=>true,'file'=>['file_path'=>$publicPath,'file_name'=>$orig,'mime'=>$mime]]);
  }

  json_out(['ok'=>false,'error'=>'Unknown action'], 400);

} catch(Exception $e){
  json_out(['ok'=>false,'error'=>$e->getMessage()], 500);
}
