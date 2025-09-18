<?php
// /home/it-future/www/itf/rireki/kaigo/php/preview.php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$fname = $_GET['f'] ?? '';
if (!preg_match('/^[a-zA-Z0-9._-]+$/', $fname)) { http_response_code(400); exit('Invalid file.'); }

$full = PATH_RESUMES . '/' . $fname;
if (!is_file($full)) { http_response_code(404); exit('File not found.'); }

$downloadUrl = RIREKI_URL . '/resumes/' . rawurlencode($fname);
?><!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<title>プレビュー | 介護向け 履歴書</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="stylesheet" href="/rireki/basic/css/recruit.css?v=5">
<style>
.wrap{max-width:920px;margin:0 auto;padding:16px}
.card{background:#fff;border:1px solid #e6edf6;border-radius:12px;padding:18px;margin-top:16px}
.actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:12px}
a.btn,button.btn{display:inline-block;padding:10px 14px;border-radius:8px;border:1px solid #dbe7f5;background:#f3f9ff;text-decoration:none}
a.btn.primary{background:#0b6b4a;color:#fff;border-color:#0b6b4a}
.note{color:#475467;font-size:14px;margin-top:6px}
.filebox{padding:12px;background:#f7fafc;border:1px dashed #cbd5e1;border-radius:10px}
</style>
</head>
<body>
<div class="wrap">
  <h1>プレビュー（Excel作成済み）</h1>
  <div class="card">
    <div class="filebox">
      <div><strong>ファイル名:</strong> <?=htmlspecialchars($fname, ENT_QUOTES,'UTF-8')?></div>
      <div class="note">生成した .xlsx をこのリンクから開く / 保存できます。</div>
    </div>
    <div class="actions">
      <a class="btn primary" href="<?=$downloadUrl?>">Excelを開く / ダウンロード</a>
      <a class="btn" href="<?=RIREKI_URL?>/rireki.php">← 入力に戻る（修正）</a>
    </div>
  </div>
</div>
</body>
</html>
