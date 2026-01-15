<?php
require_once __DIR__ . '/bootstrap.php';
$token = $_GET['token'] ?? '';
?>
<!doctype html>
<html lang="ja">
<head><meta charset="utf-8"><title>Rirekisho: 完了</title></head>
<body>
  <h2>送信完了</h2>
  <p><a href="./download_rireki.php?token=<?php echo urlencode($token); ?>">PDFをダウンロード</a></p>
  <p><a href="./download_rireki.php?token=<?php echo urlencode($token); ?>&fmt=xls">Excel(.xls)をダウンロード</a></p>
</body>
</html>
