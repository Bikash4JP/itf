<?php
// /home/it-future/www/itf/rireki/php/bootstrap.php

$ROOT = dirname(__DIR__); // /.../itf/rireki

// Try including BOTH autoloaders if available (local first, then parent)
$autoloads = [
  $ROOT . '/vendor/autoload.php',          // local
  dirname($ROOT) . '/vendor/autoload.php', // parent (/itf/vendor)
];

$loaded = 0;
foreach ($autoloads as $p) {
  if (is_readable($p)) {
    require_once $p;
    $loaded++;
  }
}
if ($loaded === 0) {
  http_response_code(500);
  exit('Composer autoload not found in local or parent vendor.');
}

date_default_timezone_set('Asia/Tokyo');
mb_internal_encoding('UTF-8');
if (session_status() === PHP_SESSION_NONE) session_start();

function rireki_path(string $rel): string {
  global $ROOT; return rtrim($ROOT, '/') . '/' . ltrim($rel, '/');
}
