<?php
// /home/it-future/www/itf/php/upload_resume.php
//
// Handles "既存の履歴書をアップロード" from /php/resume.php
// - Requires: name (romaji or kana), nationality, gender
// - Accepts: PDF / XLS / XLSX / JPG / JPEG / PNG (<= 10MB)
// - Saves original file to /rireki/{src}/uploads
// - Writes a JSON snapshot to /rireki/{src}/resumes/{token}.json
//   with source metadata so rireki_list.php can show source/job title.
// - Redirects to /php/upload_success.php with job info for a nice thanks page.

error_reporting(E_ALL);
ini_set('display_errors', isset($_GET['debug']) ? '1' : '0');

// Optional: session for staff context (not strictly required)
ini_set('session.cookie_path', '/itf');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/db_connect.php';

// -------- Config / Paths --------
$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '/home/it-future/www/itf', '/');
$host    = $_SERVER['HTTP_HOST'] ?? 'it-future.jp';
$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

// source namespace (folder) — default kaigo
$source = isset($_POST['src']) && in_array($_POST['src'], ['kaigo','basic'], true) ? $_POST['src'] : 'kaigo';

$uploadDirFs = $docRoot . '/rireki/' . $source . '/uploads';
$resumeDirFs = $docRoot . '/rireki/' . $source . '/resumes';

$uploadDirRel = '/rireki/' . $source . '/uploads';
$resumeDirRel = '/rireki/' . $source . '/resumes';

@mkdir($uploadDirFs, 0755, true);
@mkdir($resumeDirFs, 0755, true);

// -------- Helpers --------
function _bad_request(string $msg, int $code = 400): void {
  http_response_code($code);
  header('Content-Type: text/plain; charset=UTF-8');
  echo $msg;
  exit;
}
function _clean_str(?string $s): string { return trim((string)$s); }
function _ext_allow(string $filename): string {
  $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
  $allow = ['pdf','xls','xlsx','jpg','jpeg','png'];
  if (!in_array($ext, $allow, true)) return '';
  return $ext;
}
function _fetch_job_title(PDO $pdo, int $jobId): string {
  if ($jobId <= 0) return '';
  $stmt = $pdo->prepare("SELECT title FROM posts WHERE id = ? AND post_type = 'job' LIMIT 1");
  $stmt->execute([$jobId]);
  $t = $stmt->fetchColumn();
  return is_string($t) ? $t : '';
}

// -------- Validate required meta (server-side) --------
// We expect these fields to be posted by an updated form in resume.php
$name_romaji  = _clean_str($_POST['name_romaji'] ?? '');
$name_kana    = _clean_str($_POST['name_kana'] ?? '');
$nationality  = _clean_str($_POST['nationality'] ?? '');
$gender       = _clean_str($_POST['gender'] ?? '');
$jp_level     = _clean_str($_POST['jp_comm_level'] ?? ''); // optional, good to have

// Accept either romaji or kana for the display name
if ($name_romaji === '' && $name_kana === '') {
  _bad_request("氏名（ローマ字 または フリガナ）は必須です。");
}
if ($nationality === '') {
  _bad_request("国籍は必須です。");
}
if ($gender === '') {
  _bad_request("性別は必須です。");
}

// job context (if coming from job apply flow)
$job_id = (int)($_POST['job_id'] ?? 0);
$job_title = '';
if ($job_id > 0) {
  $job_title = _fetch_job_title($pdo, $job_id);
}

// -------- Validate file --------
if (!isset($_FILES['resume_file'])) {
  _bad_request("ファイルが送信されていません。");
}

$err = (int)($_FILES['resume_file']['error'] ?? UPLOAD_ERR_NO_FILE);
if ($err !== UPLOAD_ERR_OK) {
  switch ($err) {
    case UPLOAD_ERR_INI_SIZE:
    case UPLOAD_ERR_FORM_SIZE:
      _bad_request("ファイルサイズが大きすぎます（最大10MB）。");
    case UPLOAD_ERR_NO_FILE:
      _bad_request("ファイルが選択されていません。");
    default:
      _bad_request("ファイルアップロードに失敗しました（コード: $err）。", 500);
  }
}
$size = (int)($_FILES['resume_file']['size'] ?? 0);
if ($size <= 0 || $size > 10 * 1024 * 1024) { // 10MB
  _bad_request("ファイルサイズが不正です（最大10MB）。");
}
$ext = _ext_allow($_FILES['resume_file']['name'] ?? '');
if ($ext === '') {
  _bad_request("対応形式は PDF / XLS / XLSX / JPG / PNG のみです。");
}

// -------- Store file --------
$token = bin2hex(random_bytes(16)); // used for both original file name and JSON snapshot
$destFileName = $token . '.' . $ext;
$destFs  = $uploadDirFs . '/' . $destFileName;
$destRel = $uploadDirRel . '/' . $destFileName;

if (!@move_uploaded_file($_FILES['resume_file']['tmp_name'], $destFs)) {
  _bad_request("サーバーへの保存に失敗しました。", 500);
}

// -------- Snapshot minimal metadata (consumed by rireki_list.php) --------
$snapshot = [
  // Display name fields (either is OK)
  'name_romaji'    => $name_romaji,
  'name_kana'      => $name_kana,

  // Basic attributes used in listing filters
  'nationality'    => $nationality,
  'gender'         => $gender,
  'jp_comm_level'  => $jp_level, // optional

  // Source attribution
  '_source'        => 'upload',     // signals "uploaded"
  '_upload'        => [
    'rel_path' => $destRel,         // so the list can show 原本ダウンロード
    'ext'      => $ext,
    'size'     => $size,
  ],

  // Job linkage (so source shows job title if applied via job flow)
  'job_id'         => $job_id,
  'job_title'      => $job_title,
];

$jsonPath = $resumeDirFs . '/' . $token . '.json';
$ok = @file_put_contents($jsonPath, json_encode($snapshot, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
if ($ok === false) {
  // Best-effort cleanup original file if snapshot failed
  @unlink($destFs);
  _bad_request("メタデータ保存に失敗しました。", 500);
}

// -------- Redirect to Thanks page --------
$q = [
  'job_id'    => $job_id,
  'name'      => ($name_romaji !== '' ? $name_romaji : $name_kana),
  'job_title' => $job_title,
];
$qs = http_build_query($q);
$thanksUrl = '/php/upload_success.php' . ($qs ? ('?' . $qs) : '');
header('Location: ' . $thanksUrl, true, 302);
exit;
