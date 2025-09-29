<?php
// /home/it-future/www/itf/php/delete_rireki.php
ini_set('session.cookie_path', '/itf');
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
  http_response_code(403);
  echo "Forbidden.";
  exit;
}

// CSRF check
if (empty($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf_rireki'] ?? '')) {
  http_response_code(400);
  echo "Invalid CSRF token.";
  exit;
}

// Validate input
$src   = isset($_POST['src']) && in_array($_POST['src'], ['kaigo','basic'], true) ? $_POST['src'] : 'kaigo';
$token = $_POST['token'] ?? '';
if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
  http_response_code(400);
  echo "Invalid token.";
  exit;
}

// Paths
$root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '/home/it-future/www/itf', '/');
$dir  = $root . '/rireki/' . $src . '/resumes';

// Delete .xls, .pdf, .json if present
$exts = ['xls','pdf','json'];
$ok = false;
foreach ($exts as $ext) {
  $path = $dir . '/' . $token . '.' . $ext;
  if (is_file($path)) {
    // extra safety: ensure path begins with $dir
    $real = realpath($path);
    if ($real && str_starts_with($real, realpath($dir))) {
      @unlink($real);
      $ok = true;
    }
  }
}

// Redirect back to the list with a message
$dest = '/php/rireki_list.php?src=' . urlencode($src);
header("Location: {$dest}");
exit;
