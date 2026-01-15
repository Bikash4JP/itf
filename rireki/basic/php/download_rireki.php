<?php
require_once __DIR__ . '/bootstrap.php';

header('X-Content-Type-Options: nosniff');

$token = $_GET['token'] ?? '';
$fmt   = ($_GET['fmt'] ?? 'pdf') === 'xls' ? 'xls' : 'pdf';
$view  = ($_GET['view'] ?? '') === '1'; // <- inline preview switch

if (!preg_match('/^[A-Fa-f0-9]{64}$/', $token)) {
  http_response_code(400); exit('Bad token');
}

$base = realpath(rireki_path('resumes/rirekisho'));
$ext  = $fmt === 'xls' ? '.xls' : '.pdf';

$target = $base . DIRECTORY_SEPARATOR . $token . $ext;
$real   = realpath($target);

if (!$real || strpos($real, $base) !== 0 || !is_readable($real)) {
  http_response_code(404); exit('File not found');
}

if ($fmt === 'xls') {
  header('Content-Type: application/vnd.ms-excel');
  $fname = 'rirekisho_' . $token . '.xls';
  $disposition = 'attachment'; // Excel always download
} else {
  header('Content-Type: application/pdf');
  $fname = 'rirekisho_' . $token . '.pdf';
  $disposition = $view ? 'inline' : 'attachment'; // <- inline for preview
}

header('Content-Disposition: ' . $disposition . '; filename="'.$fname.'"');
header('Content-Length: ' . filesize($real));
readfile($real);
