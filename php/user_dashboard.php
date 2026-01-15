<?php
// /home/it-future/www/itf/php/user_dashboard.php
declare(strict_types=1);

require_once __DIR__ . '/user_auth.php';
$pdo = app_pdo();
app_ensure_tables($pdo);

app_require_login('/php/user_dashboard.php');

$me = app_current_user($pdo);
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>ユーザーページ</title>
  <link rel="stylesheet" href="/css/common.css">
  <style>
    .wrap{max-width:980px;margin:30px auto;padding:0 16px}
    .card{background:#fff;border:1px solid #e6edf6;border-radius:16px;padding:18px;box-shadow:0 3px 10px rgba(10,60,150,.05)}
    .btn{display:inline-flex;gap:8px;align-items:center;justify-content:center;padding:10px 14px;border-radius:10px;border:1px solid transparent;background:#2a7de1;color:#fff;font-weight:800;cursor:pointer;text-decoration:none}
    .btn.ghost{background:#fff;color:#0b3772;border-color:#dbe7f5}
    .row{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h2 style="margin:0 0 6px">こんにちは、<?=h($me['username'] ?? '')?> さん</h2>
      <div style="color:#6b7280"><?=h($me['email'] ?? '')?></div>

      <div class="row">
        <a class="btn" href="/php/user_applied_jobs.php">応募履歴（Applied Jobs）</a>
        <a class="btn" href="/rireki/kaigo/php/rireki_preview.php">マイ情報（編集）</a>
        <a class="btn ghost" href="/php/user_logout.php">ログアウト</a>
      </div>

      <div style="margin-top:12px">
        <a class="btn ghost" href="/saiyou.php">求人一覧へ戻る</a>
      </div>
    </div>
  </div>
</body>
</html>
