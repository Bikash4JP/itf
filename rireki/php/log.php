<?php
// /home/it-future/www/itf/rireki/php/log.php
require_once __DIR__ . '/bootstrap.php';

function log_msg(string $channel, string $msg): void {
  $dir = rireki_path('logs');
  if (!is_dir($dir)) @mkdir($dir, 0750, true);
  $line = sprintf("[%s] %s %s\n", date('c'), $_SERVER['REMOTE_ADDR'] ?? '-', $msg);
  @file_put_contents($dir . '/' . $channel . '.log', $line, FILE_APPEND | LOCK_EX);
}
