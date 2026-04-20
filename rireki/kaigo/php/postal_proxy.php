<?php
// /home/it-future/www/itf/rireki/kaigo/php/postal_proxy.php
// Server-side proxy for zipcloud API to avoid browser CORS/CSP blocks.
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$zip = preg_replace('/[^\d]/', '', (string)($_GET['zip'] ?? ''));
if (strlen($zip) !== 7) {
  echo json_encode(['status' => 400, 'message' => 'invalid zip', 'results' => null]);
  exit;
}

$url = 'https://zipcloud.ibsnet.co.jp/api/search?zipcode=' . $zip;
$ctx = stream_context_create(['http' => [
  'timeout'    => 5,
  'user_agent' => 'Mozilla/5.0 (compatible; ITF-postal-proxy/1.0)',
]]);
$raw = @file_get_contents($url, false, $ctx);

if ($raw === false) {
  echo json_encode(['status' => 500, 'message' => 'upstream error', 'results' => null]);
  exit;
}
echo $raw;
exit;
