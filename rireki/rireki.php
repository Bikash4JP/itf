<?php
// /home/it-future/www/itf/rireki/rireki.php
require_once __DIR__ . '/php/bootstrap.php';

// (Optional) CSRF seed yahan bana sakte ho; final flow me use hoga
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

// Quick sanity check: Composer classes available?
$autoload_ok = class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class) ? 'OK' : 'NG';
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>Rirekisho Builder</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="./css/rireki.css" rel="stylesheet">
</head>
<body>
  <div class="container">
    <h1>Rirekisho Builder</h1>
    <p>Composer Autoload: <strong><?php echo htmlspecialchars($autoload_ok, ENT_QUOTES, 'UTF-8'); ?></strong></p>
    <!-- Yahin pe multi-step form aayega (later). -->
  </div>
  <script src="./js/rireki.js"></script>
</body>
</html>
