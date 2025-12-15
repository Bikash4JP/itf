<?php
// /home/it-future/www/itf/rireki/kaigo/php/save_profile.php
declare(strict_types=1);

require_once __DIR__ . '/../../../php/user_auth.php';
$pdo = app_pdo();
app_ensure_tables($pdo);

app_require_login('/rireki/kaigo/php/rireki_preview.php');

$data = $_POST ?? [];
unset($data['_source'], $data['_job_id'], $data['_job_title']); // keep clean

app_save_profile($pdo, app_user_id(), $data, 'kaigo');

header('Location: /rireki/kaigo/php/rireki_preview.php', true, 302);
exit;
