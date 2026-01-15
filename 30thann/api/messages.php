<?php
// Simple pass-through proxy to avoid CORS on frontend
// Replace with your actual Apps Script 'exec' URL:
$BASE = 'https://script.google.com/macros/s/PASTE_YOUR_EXEC_ID/exec';

$limit  = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$order  = isset($_GET['order'])  ? $_GET['order'] : 'asc';

$url = $BASE . '?api=messages&limit=' . $limit . '&offset=' . $offset . '&order=' . urlencode($order);

// fetch
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$out = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: max-age=30, public'); // small cache ok for live
if ($code !== 200 || $out === false) {
  echo json_encode(['ok'=>false,'error'=>'upstream_error','status'=>$code]);
  exit;
}
echo $out;
