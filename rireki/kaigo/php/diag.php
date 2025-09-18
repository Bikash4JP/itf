<?php
declare(strict_types=1);
@ini_set('display_errors','1'); error_reporting(E_ALL);
@ini_set('memory_limit','512M'); @ini_set('max_execution_time','60');

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: text/plain; charset=UTF-8');

// Autoload check (mirror bootstrap search order)
$autoloadPaths = [
  __DIR__ . '/../vendor/autoload.php',
  __DIR__ . '/../../vendor/autoload.php',
  __DIR__ . '/../../../vendor/autoload.php',
  __DIR__ . '/vendor/autoload.php',
];
$autoloadOk = false;
foreach ($autoloadPaths as $p) {
  if (is_readable($p)) { require_once $p; $autoloadOk = true; break; }
}
echo "autoload: " . ($autoloadOk ? "OK\n" : "NG (composer install in /home/it-future/www/itf/rireki)\n");

$map = realpath(__DIR__ . '/../mappings/Kaigo_Template_XLS.json');
echo "mapping : " . ($map && is_readable($map) ? "OK ($map)\n" : "NG\n");

$tpl = null;
if ($map) {
  $m = json_decode((string)file_get_contents($map), true);
  $tplRel = (string)($m['template_file'] ?? '../templates/kaigo.xlsx');
  $tpl = realpath(dirname($map) . '/' . $tplRel);
  echo "template: " . ($tpl && is_readable($tpl) ? "OK ($tpl)\n" : "NG ($tplRel)\n");
}

echo "zip ext : " . (extension_loaded('zip') ? "OK\n" : "NG\n");
echo "mem/time: " . ini_get('memory_limit') . " / " . ini_get('max_execution_time') . "\n";

try {
  if ($tpl && $autoloadOk) {
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($tpl);
    if (method_exists($reader,'setReadDataOnly')) $reader->setReadDataOnly(false);
    $book = $reader->load($tpl);
    $sheet = $book->getActiveSheet();
    echo "xlsx load: OK, active sheet title = " . $sheet->getTitle() . "\n";
    $book->disconnectWorksheets(); unset($book,$sheet);
  }
} catch (\Throwable $e) {
  echo "xlsx load: FAIL: " . $e->getMessage() . "\n";
}

echo "done.\n";
