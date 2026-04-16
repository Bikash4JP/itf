<?php
// /home/it-future/www/itf/php/apply_now.php
// AJAX endpoint: record a profile-based application, log activity, return JSON.
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);
session_start();

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/user_auth.php';
require_once __DIR__ . '/activity_logger.php';

function json_err(string $msg, int $code = 400): void {
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('method_not_allowed', 405);

// Auth
if (!app_is_logged_in()) json_err('not_logged_in', 401);
$uid = (int) app_user_id();

// Params
$job_id = (int) ($_POST['job_id'] ?? 0);
$token  = strtolower(trim((string) ($_POST['token'] ?? '')));

if ($job_id <= 0) json_err('invalid_job_id');
if (!preg_match('/^[a-f0-9]{32}$/', $token)) json_err('invalid_token');

// Ensure tables exist
app_ensure_tables($pdo);

// Verify token belongs to this user (check both app_resumes and app_resume_kaigo)
$st = $pdo->prepare("SELECT id FROM app_resume_kaigo WHERE token = ? AND user_id = ? LIMIT 1");
$st->execute([$token, $uid]);
if (!$st->fetch()) {
  // fallback: check app_resumes table
  $st = $pdo->prepare("SELECT id FROM app_resumes WHERE token = ? AND user_id = ? LIMIT 1");
  $st->execute([$token, $uid]);
  if (!$st->fetch()) json_err('token_not_yours');
}

// Verify job exists
$st2 = $pdo->prepare("SELECT id, title, company_name FROM posts WHERE id = ? AND post_type = 'job' LIMIT 1");
$st2->execute([$job_id]);
$job = $st2->fetch(PDO::FETCH_ASSOC);
if (!$job) json_err('job_not_found', 404);

// Get applicant name from resume
$st3 = $pdo->prepare("SELECT name_romaji, name_kana FROM app_resume_kaigo WHERE token = ? LIMIT 1");
$st3->execute([$token]);
$resume = $st3->fetch(PDO::FETCH_ASSOC);
$applicantName = trim((string)($resume['name_romaji'] ?? ''));
if ($applicantName === '') $applicantName = trim((string)($resume['name_kana'] ?? ''));
if ($applicantName === '') {
  // fallback to username
  $su = $pdo->prepare("SELECT username FROM app_users WHERE id = ? LIMIT 1");
  $su->execute([$uid]);
  $applicantName = (string)($su->fetchColumn() ?: 'ユーザー');
}

// Insert application (ignore duplicate)
try {
  $ins = $pdo->prepare(
    "INSERT IGNORE INTO app_applications (user_id, job_id, resume_token) VALUES (?, ?, ?)"
  );
  $ins->execute([$uid, $job_id, $token]);
} catch (Throwable $e) {
  error_log('[apply_now] insert error: ' . $e->getMessage());
  json_err('db_error', 500);
}

// Log activity for staff dashboard
$jobTitle   = trim((string)($job['title'] ?? ''));
$companyName = trim((string)($job['company_name'] ?? ''));
$org = $companyName !== '' ? $companyName : ($jobTitle !== '' ? $jobTitle : '求人ID: ' . $job_id);
$msg = "【応募】{$applicantName} さんが「{$org}」（求人ID: {$job_id}）に応募しました。";

try {
  log_activity($pdo, [
    'actor_type'     => 'applicant',
    'actor_staff_id' => null,
    'actor_username' => $applicantName,
    'action'         => 'apply',
    'entity_type'    => 'job',
    'entity_id'      => $job_id,
    'company_name'   => $companyName !== '' ? $companyName : null,
    'talent_name_kana' => trim((string)($resume['name_kana'] ?? '')) ?: null,
    'message_ja'     => $msg,
  ]);
} catch (Throwable $e) {
  error_log('[apply_now] activity log error: ' . $e->getMessage());
  // non-fatal
}

echo json_encode(['ok' => true, 'message' => 'applied'], JSON_UNESCAPED_UNICODE);
exit;
