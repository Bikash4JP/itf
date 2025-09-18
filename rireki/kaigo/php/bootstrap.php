<?php
// /rireki/kaigo/php/bootstrap.php

// PHP runtime
error_reporting(E_ALL);
mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Tokyo');

define('RIREKI_ROOT', realpath(dirname(__DIR__))); // .../rireki/kaigo

// autoload (prefer shared parent vendor, then local)
$autoloadCandidates = [
  dirname(RIREKI_ROOT) . '/vendor/autoload.php', // /rireki/vendor (shared)
  RIREKI_ROOT . '/vendor/autoload.php',          // /rireki/kaigo/vendor (if used)
];
$autoloadOk = false;
foreach ($autoloadCandidates as $a) {
  if (is_readable($a)) { require_once $a; $autoloadOk = true; break; }
}
if (!$autoloadOk) {
  header('Content-Type: text/plain; charset=UTF-8', true, 500);
  echo "Composer Autoload not found.\nTried:\n - " . implode("\n - ", $autoloadCandidates);
  exit;
}

// util: path builder inside /kaigo
function rireki_path(string $rel): string {
  return rtrim(RIREKI_ROOT, '/') . '/' . ltrim($rel, '/');
}

// logging
function rireki_log(string $level, string $msg): void {
  $dir = rireki_path('logs');
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
  $line = sprintf("[%s] [%s] %s\n", date('Y-m-d H:i:s'), $level, $msg);
  @file_put_contents($dir . '/app.log', $line, FILE_APPEND);
}
