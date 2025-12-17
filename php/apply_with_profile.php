<?php
// /home/it-future/www/itf/php/apply_with_profile.php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/user_auth.php';

$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$fmt = isset($_GET['fmt']) ? strtolower(trim((string)$_GET['fmt'])) : 'kaigo';
if (!in_array($fmt, ['kaigo','basic'], true)) $fmt = 'kaigo';

if ($job_id <= 0) { http_response_code(400); echo '無効な求人IDです。'; exit; }

$next = '/php/apply_with_profile.php?job_id='.(int)$job_id.'&fmt='.urlencode($fmt);
app_require_login($next);

// job exists?
$st = $pdo->prepare("SELECT id,title FROM posts WHERE id=? AND post_type='job' LIMIT 1");
$st->execute([$job_id]);
$job = $st->fetch(PDO::FETCH_ASSOC);
if (!$job) { http_response_code(404); echo '求人が見つかりませんでした。'; exit; }

// Redirect to preview with explicit flow flag (this is IMPORTANT)
$dest = "/rireki/{$fmt}/php/rireki_preview.php?job_id=".(int)$job_id."&flow=apply_profile";
header("Location: {$dest}", true, 302);
exit;
