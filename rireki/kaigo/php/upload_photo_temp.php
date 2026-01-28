<?php
// /home/it-future/www/itf/rireki/kaigo/php/upload_photo.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../php/db_connect.php';

function out($ok, $arr = []) {
  echo json_encode(array_merge(['ok'=>$ok], $arr), JSON_UNESCAPED_UNICODE);
  exit;
}

$token = $_POST['token'] ?? '';
if (!preg_match('/^[a-f0-9]{32}$/', $token)) out(false, ['error'=>'Invalid token']);

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
  out(false, ['error'=>'No photo uploaded']);
}

$f = $_FILES['photo'];
$maxBytes = 5 * 1024 * 1024;
if ($f['size'] > $maxBytes) out(false, ['error'=>'File too large (max 5MB)']);

$tmp = $f['tmp_name'];
$mime = mime_content_type($tmp);
$ext = '';
if ($mime === 'image/jpeg') $ext = 'jpg';
else if ($mime === 'image/png') $ext = 'png';
else out(false, ['error'=>'Only JPEG/PNG allowed']);

$baseDir = __DIR__ . '/../uploads/kaigo_photos';
if (!is_dir($baseDir)) {
  if (!mkdir($baseDir, 0755, true)) out(false, ['error'=>'Failed to create upload dir']);
}

// unique name per token (overwrite allowed but safe)
$filename = $token . '_' . time() . '.' . $ext;
$absPath = $baseDir . '/' . $filename;

// move
if (!move_uploaded_file($tmp, $absPath)) out(false, ['error'=>'Failed to save file']);

// ✅ Public path (adjust if needed)
$publicPath = '/rireki/kaigo/uploads/kaigo_photos/' . $filename;

// update db
$stmt = $pdo->prepare("UPDATE app_resume_kaigo SET photo_path=:p, updated_at=NOW() WHERE token=:t");
$stmt->execute([':p'=>$publicPath, ':t'=>$token]);

out(true, [
  'photo_path' => $publicPath,
  'photo_url'  => $publicPath
]);
