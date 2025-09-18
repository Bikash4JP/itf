<?php
@ini_set('display_errors','1'); error_reporting(EALL);
require_once __DIR__ . '/../../../vendor/autoload.php';
$mapping = __DIR__ . '/../mappings/Kaigo_Template_XLS.json';
$ok = is_readable($mapping);
$map = $ok ? json_decode(file_get_contents($mapping), true) : null;
$tpl = $ok && isset($map['template_file']) ? realpath(dirname($mapping).'/'.$map['template_file']) : null;

echo "<pre>";
echo "autoload: ", (class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory') ? "OK" : "NG"), PHP_EOL;
echo "mapping : ", ($ok ? "OK" : "NG"), " ($mapping)", PHP_EOL;
echo "template: ", ($tpl && is_readable($tpl) ? "OK" : "NG"), " ($tpl)", PHP_EOL;
echo "zip ext : ", (extension_loaded('zip') ? "OK" : "NG"), PHP_EOL;
echo "mem/time: ", ini_get('memory_limit'), " / ", ini_get('max_execution_time'), PHP_EOL;
echo "</pre>";
