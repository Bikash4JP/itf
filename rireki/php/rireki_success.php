<?php
// /home/it-future/www/itf/rireki/php/rireki_success.php
require_once __DIR__ . '/bootstrap.php';

$token = $_GET['token'] ?? '';
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>Rirekisho: 完了</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <h2>送信完了</h2>
  <p>ダウンロード用トークン（テスト）: <code><?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?></code></p>

  <!-- Real flow me yahan <embed src="../resumes/rirekisho/xxxx.pdf"> NAHI — direct access band rahega.
       Humesha secured download endpoint se serve karenge. -->
  <p>
    <a href="./download_rireki.php?token=<?php echo urlencode($token); ?>">PDFをダウンロード</a>
  </p>
</body>
</html>
