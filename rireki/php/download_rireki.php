<?php
// /home/it-future/www/itf/rireki/php/download_rireki.php
require_once __DIR__ . '/bootstrap.php';

header('X-Content-Type-Options: nosniff');

$token = $_GET['token'] ?? '';
if (!preg_match('/^[A-Fa-f0-9]{64}$/', $token)) {
  http_response_code(400); exit('Bad token');
}

// Resolve path by token-based filename
$base = realpath(rireki_path('resumes/rirekisho'));
$target = $base . DIRECTORY_SEPARATOR . $token . '.pdf';
$real = realpath($target);

if (!$real || strpos($real, $base) !== 0 || !is_readable($real)) {
  http_response_code(404); exit('File not found');
}

$fname = 'rirekisho_' . $token . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="'.$fname.'"');
header('Content-Length: ' . filesize($real));
readfile($real);
