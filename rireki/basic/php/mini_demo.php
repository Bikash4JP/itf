<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/adapters/adapter_xlsx.php';

$mappingFile = __DIR__ . '/../mappings/templateA.json';
$outDir      = __DIR__ . '/../resumes/rirekisho';
@mkdir($outDir, 0750, true);

// ASCII-only test data (safe to paste)
$data = [
  'personal'   => ['name_kana'=>'test', 'name_kanji'=>'test', 'dob_yyyy'=>'1990','dob_mm'=>'01','dob_dd'=>'01','age'=>'34','gender'=>'M'],
  'address'    => ['kana'=>'test', 'postcode'=>'123-4567', 'full'=>'Tokyo 1-2-3'],
  'contact'    => ['phone'=>'0900000000', 'email'=>'test@example.com'],
  'education'  => [['year'=>'2010','month'=>'04','school'=>'High School'], ['year'=>'2014','month'=>'03','school'=>'University']],
  'experience' => [['year'=>'2014','month'=>'04','company'=>'ABC Corp','title'=>'Engineer']],
  'licenses'   => [['year'=>'2018','month'=>'10','certificate'=>'FE']],
  'pr'         => ['self_pr'=>'Self PR'],
  'preferences'=> ['hopes'=>'Tokyo'],
];

$token = bin2hex(random_bytes(16));
$res = rireki_render_pdf($data, $mappingFile, $outDir, $token);

header('Content-Type: text/plain; charset=UTF-8');
echo "ok="  . (!empty($res['ok']) && $res['ok'] ? 'true' : 'false') . "\n";
echo "pdf=" . ($res['pdf']  ?? 'null') . "\n";
echo "xls=" . ($res['xls']  ?? 'null') . "\n";
echo "err=" . ($res['err']  ?? 'null') . "\n";
