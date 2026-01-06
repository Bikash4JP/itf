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

function is_allowed_field($field){
  $allowed = [
    'company_name','status','request_date','deadline_date',
    'job_staff_id','level','title','summary','content',
    'org_work_type','job_location',
    'bonuses','bonus_amount',
    'salary','salary_basic','salary_takehome','transport_amount_limit','rent_support',
    'current_residence','gender_pref','experience','hijab_policy',
    'nationality_pref_json','required_vacancy','japanese_level',
    'publish_state',
  ];
  return in_array($field, $allowed, true);
}

function format_row_for_ui($row, $filesMap = []) {
  $jid = (int)$row['id'];
  $row['status'] = normalize_status($row['status'] ?? '');
  
  if (empty($row['job_staff_id'])) {
    $row['job_staff_id'] = null;
  } else {
    $row['job_staff_id'] = (int)$row['job_staff_id'];
  }
  
  $b = $row['bonuses'] ?? 0;
  $row['bonuses'] = ((int)$b === 1) ? "あり" : "なし";
  
  $row['current_residence'] = normalize_ok3($row['current_residence'] ?? '') ?: ($row['current_residence'] ?? null);
  $row['gender_pref'] = normalize_ok3($row['gender_pref'] ?? '') ?: ($row['gender_pref'] ?? null);
  $row['experience'] = normalize_ok3($row['experience'] ?? '') ?: ($row['experience'] ?? null);
  
  $arr = [];
  if (!empty($row['nationality_pref_json'])) {
    $decoded = json_decode((string)$row['nationality_pref_json'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
      $arr = array_values(array_filter(array_map('strval', $decoded), fn($x)=>trim($x)!=='' ));
    }
  }
  $row['nationality_pref_json'] = $arr;
  $row['files_preview'] = $filesMap[$jid] ?? [];
  
  return $row;
}

try {

  if ($action === 'csrf') {
    json_out(['ok'=>true,'csrf_token'=>($_SESSION['csrf_token'] ?? '')]);
  }

  $staffByUsername = [];
  $staffByName = [];
  try {
    $st = $pdo->query("SELECT id, username, name FROM staff");
    while($r = $st->fetch(PDO::FETCH_ASSOC)){
      $staffByUsername[(string)$r['username']] = (int)$r['id'];
      $staffByName[(string)$r['name']] = (int)$r['id'];
    }
  } catch(Exception $e) {}

  if ($action === 'list') {
    $stmt = $pdo->query("
      SELECT id,staff_id,posted_by,job_staff_id,publish_state,status,request_date,deadline_date,
        title,summary,content,company_name,org_work_type,job_location,bonuses,bonus_amount,
        salary,salary_basic,salary_takehome,transport_amount_limit,rent_support,
        current_residence,gender_pref,experience,hijab_policy,nationality_pref_json,
        required_vacancy,japanese_level,level,updated_at
      FROM posts WHERE post_type='job' ORDER BY updated_at DESC, id DESC
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

    foreach($rows as &$r){ $r = format_row_for_ui($r, $filesMap); }
    json_out(['ok'=>true,'rows'=>$rows]);
  }

  if ($action === 'create') {
    require_csrf();
    $stmt = $pdo->prepare("
      INSERT INTO posts (post_type, publish_state, status, title, summary, content, staff_id, posted_by, job_staff_id,
        company_name, org_work_type, job_location, bonuses, nationality_pref_json,
        current_residence, gender_pref, experience, hijab_policy, required_vacancy, japanese_level, level, date)
      VALUES ('job','draft','募集中','','','',?,?,NULL,'','','',0,NULL,NULL,NULL,NULL,NULL,'','','',CURDATE())
    ");
    $stmt->execute([$_SESSION['id'], $_SESSION['username']]);
    $id = (int)$pdo->lastInsertId();
    json_out(['ok'=>true,'id'=>$id]);
  }

  // ✅ NEW: Batch update entire row
  if ($action === 'updateRow') {
    require_csrf();
    
    $id = (int)($_POST['id'] ?? 0);
    $data = json_decode($_POST['data'] ?? '{}', true);
    
    if ($id <= 0 || !is_array($data)) {
      json_out(['ok'=>false,'error'=>'Invalid data'], 400);
    }

    $updates = [];
    $values = [];

    foreach($data as $field => $value) {
      if (!is_allowed_field($field)) continue;

      // Normalize each field
      if ($field === 'status') {
        $value = normalize_status($value);
      }
      
      if ($field === 'job_staff_id') {
        if ($value === '' || $value === null) {
          $value = null;
        } else {
          $sv = trim((string)$value);
          $value = ctype_digit($sv) ? (int)$sv : ($staffByName[$sv] ?? null);
        }
      }
      
      if ($field === 'bonuses') {
        $sv = trim((string)$value);
        $value = ($sv === 'あり' || $sv === '1') ? 1 : 0;
      }
      
      if ($field === 'current_residence') {
        $v = normalize_ok3($value);
        $value = in_array($v, ['国内','国外','どちらでもOK', null], true) ? $v : null;
      }
      
      if ($field === 'gender_pref') {
        $v = normalize_ok3($value);
        $value = in_array($v, ['男','女','どちらでもOK', null], true) ? $v : null;
      }
      
      if ($field === 'experience') {
        $v = normalize_ok3($value);
        $value = in_array($v, ['あり','なし','どちらでもOK', null], true) ? $v : null;
      }
      
      if ($field === 'hijab_policy') {
        $v = trim((string)$value);
        $value = in_array($v, ['OK','禁止', null], true) ? $v : null;
      }
      
      if ($field === 'nationality_pref_json') {
        $arr = parse_json_array($value);
        $arr = array_values(array_unique($arr));
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
      
      if (($field === 'request_date' || $field === 'deadline_date') && ($value === '' || $value === null)) {
        $value = null;
      }

      $updates[] = "`$field` = ?";
      $values[] = $value;
    }

    if (count($updates) > 0) {
      $values[] = $id;
      $sql = "UPDATE posts SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ? AND post_type='job'";
      $st = $pdo->prepare($sql);
      $st->execute($values);
    }

    // Return complete updated row
    $st2 = $pdo->prepare("
      SELECT id,staff_id,posted_by,job_staff_id,publish_state,status,request_date,deadline_date,
        title,summary,content,company_name,org_work_type,job_location,bonuses,bonus_amount,
        salary,salary_basic,salary_takehome,transport_amount_limit,rent_support,
        current_residence,gender_pref,experience,hijab_policy,nationality_pref_json,
        required_vacancy,japanese_level,level,updated_at
      FROM posts WHERE id=? AND post_type='job'
    ");
    $st2->execute([$id]);
    $row = $st2->fetch(PDO::FETCH_ASSOC);

    if ($row) {
      $st3 = $pdo->prepare("SELECT job_post_id, id, file_path, file_name, mime, created_at FROM job_files WHERE job_post_id = ? ORDER BY created_at DESC LIMIT 3");
      $st3->execute([$id]);
      $filesMap = [];
      while($f = $st3->fetch(PDO::FETCH_ASSOC)){ if (!isset($filesMap[$id])) $filesMap[$id] = []; $filesMap[$id][] = $f; }
      $row = format_row_for_ui($row, $filesMap);
    }

    json_out(['ok'=>true,'row'=>$row]);
  }

  if ($action === 'updateCell') {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $field = (string)($_POST['field'] ?? '');
    $value = $_POST['value'] ?? null;

    if ($id <= 0 || !is_allowed_field($field)) {
      json_out(['ok'=>false,'error'=>'Invalid field/id'], 400);
    }

    if (($field === 'request_date' || $field === 'deadline_date') && ($value === '' || $value === null)) {
      $value = null;
    }

    if ($field === 'status') {
      $value = normalize_status($value);
    }

    if ($field === 'job_staff_id') {
      if ($value === '' || $value === null) {
        $value = null;
      } else {
        $sv = trim((string)$value);
        $value = ctype_digit($sv) ? (int)$sv : ($staffByName[$sv] ?? null);
      }
    }

    if ($field === 'bonuses') {
      $sv = trim((string)$value);
      $value = ($sv === 'あり' || $sv === '1' || strtolower($sv) === 'true') ? 1 : 0;
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

    if ($field === 'nationality_pref_json') {
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

    $st2 = $pdo->prepare("
      SELECT id,staff_id,posted_by,job_staff_id,publish_state,status,request_date,deadline_date,
        title,summary,content,company_name,org_work_type,job_location,bonuses,bonus_amount,
        salary,salary_basic,salary_takehome,transport_amount_limit,rent_support,
        current_residence,gender_pref,experience,hijab_policy,nationality_pref_json,
        required_vacancy,japanese_level,level,updated_at
      FROM posts WHERE id=? AND post_type='job'
    ");
    $st2->execute([$id]);
    $row = $st2->fetch(PDO::FETCH_ASSOC);

    if ($row) {
      $st3 = $pdo->prepare("SELECT job_post_id, id, file_path, file_name, mime, created_at FROM job_files WHERE job_post_id = ? ORDER BY created_at DESC LIMIT 3");
      $st3->execute([$id]);
      $filesMap = [];
      while($f = $st3->fetch(PDO::FETCH_ASSOC)){ if (!isset($filesMap[$id])) $filesMap[$id] = []; $filesMap[$id][] = $f; }
      $row = format_row_for_ui($row, $filesMap);
    }

    json_out(['ok'=>true,'row'=>$row]);
  }

  if ($action === 'delete') {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) json_out(['ok'=>false,'error'=>'Invalid id'], 400);
    $st = $pdo->prepare("DELETE FROM posts WHERE id=? AND post_type='job'");
    $st->execute([$id]);
    json_out(['ok'=>true]);
  }

  if ($action === 'files') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) json_out(['ok'=>false,'error'=>'Invalid id'], 400);
    $st = $pdo->prepare("SELECT id, file_path, file_name, mime, created_at FROM job_files WHERE job_post_id=? ORDER BY created_at DESC");
    $st->execute([$id]);
    json_out(['ok'=>true,'files'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
  }

  if ($action === 'uploadFile') {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) json_out(['ok'=>false,'error'=>'Invalid job id'], 400);
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
      json_out(['ok'=>false,'error'=>'Upload failed'], 400);
    }

    $tmp = $_FILES['file']['tmp_name'];
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