<?php
// kaigo/php/bootstrap.php
declare(strict_types=1);

// Errors (enable via ?debug=1)
if (isset($_GET['debug'])) { @ini_set('display_errors','1'); error_reporting(E_ALL); }

// Try to relax limits for FPM
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', '120');
if (function_exists('set_time_limit')) @set_time_limit(120);

// Timezone
date_default_timezone_set('Asia/Tokyo');

// Paths
if (!defined('RIREKI_KAIGO_ROOT')) {
  define('RIREKI_KAIGO_ROOT', realpath(__DIR__ . '/..') ?: (__DIR__ . '/..'));
}
if (!defined('RIREKI_KAIGO_URL')) {
  define('RIREKI_KAIGO_URL', '/rireki/kaigo');
}

// Composer autoload (check all plausible locations)
$__autoloadCandidates = [
  // Most likely: you ran composer in /home/it-future/www/itf/rireki
  __DIR__ . '/../vendor/autoload.php',       // /rireki/vendor/autoload.php
  __DIR__ . '/../../vendor/autoload.php',    // /rireki/kaigo/vendor/autoload.php (if ever)
  __DIR__ . '/../../../vendor/autoload.php', // /itf/vendor/autoload.php (if moved up)
  __DIR__ . '/vendor/autoload.php',          // /rireki/kaigo/php/vendor/autoload.php (rare)
];
$__autoloadLoaded = false;
foreach ($__autoloadCandidates as $__a) {
  if (is_readable($__a)) { require_once $__a; $__autoloadLoaded = true; break; }
}

// Optional: throw a clear error in debug mode
if (!$__autoloadLoaded && isset($_GET['debug'])) {
  header('Content-Type: text/plain; charset=UTF-8');
  echo "Composer autoload not found near:\n";
  foreach ($__autoloadCandidates as $__a) echo " - $__a\n";
  exit;
}
