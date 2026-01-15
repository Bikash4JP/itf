<?php
// /home/it-future/www/itf/rireki/kaigo/php/claim_resume.php
declare(strict_types=1);

require_once __DIR__ . '/../../../php/user_auth.php';
require_once __DIR__ . '/bootstrap.php';

$pdo = app_pdo();
app_ensure_tables($pdo);

app_require_login('/rireki/kaigo/php/claim_resume.php');

$token = (string)($_GET['token'] ?? '');
$jobId = (int)($_GET['job_id'] ?? 0);

if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
  http_response_code(400); echo 'Invalid token'; exit;
}

$resumeDir = rireki_path('resumes');
$jsonPath  = rtrim($resumeDir,'/') . '/' . $token . '.json';
$xlsPath   = rtrim($resumeDir,'/') . '/' . $token . '.xls';

if (!is_readable($xlsPath) || !is_readable($jsonPath)) {
  http_response_code(404); echo 'Resume files not found'; exit;
}

$data = json_decode(file_get_contents($jsonPath), true);
if (!is_array($data)) $data = [];

app_save_profile($pdo, app_user_id(), $data, 'kaigo');
app_save_resume($pdo, app_user_id(), $token, $jobId > 0 ? $jobId : null, 'kaigo');
app_save_application($pdo, app_user_id(), $jobId, $token);

header('Location: /php/user_applied_jobs.php', true, 302);
exit;
