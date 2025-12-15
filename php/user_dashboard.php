<?php
// /home/it-future/www/itf/php/user_dashboard.php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/user_auth.php';

if (!app_logged_in()) {
  app_redirect_login('/php/user_dashboard.php');
}

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$uid = app_user_id();

$stmt = $pdo->prepare("SELECT * FROM applicant_resumes WHERE applicant_id = ? ORDER BY claimed_at DESC, created_at DESC");
$stmt->execute([$uid]);
$resumes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>マイページ</title>
  <style>
    body{font-family:system-ui,"Noto Sans JP",Meiryo,Arial;margin:0;background:#f6fbff;padding:24px}
    .wrap{max-width:980px;margin:0 auto}
    .card{background:#fff;border:1px solid #e6edf6;border-radius:16px;padding:18px;box-shadow:0 10px 24px rgba(0,0,0,.05)}
    h1{margin:0 0 10px;font-size:22px}
    .muted{color:#667085}
    table{width:100%;border-collapse:collapse;margin-top:12px}
    th,td{padding:10px 12px;border-bottom:1px solid #eef2f7;text-align:left}
    th{color:#1e90ff;font-weight:900}
    .btn{display:inline-flex;gap:8px;align-items:center;padding:8px 10px;border:1px solid #dbe7f5;border-radius:10px;background:#f3f9ff;color:#0c4a7a;text-decoration:none;font-weight:800}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1>マイページ</h1>
      <p class="muted">保存した履歴書をここから再ダウンロードできます。</p>

      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px">
        <a class="btn" href="/saiyou.php">求人一覧へ</a>
        <a class="btn" href="/php/user_logout.php">ログアウト</a>
      </div>

      <?php if (!$resumes): ?>
        <p class="muted" style="margin-top:14px">まだ保存された履歴書がありません。</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>作成</th>
              <th>フォーマット</th>
              <th>求人ID</th>
              <th>ダウンロード</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($resumes as $r):
              $token = (string)$r['token'];
              $fmt   = (string)$r['fmt'];
              $xlsUrl = "/rireki/{$fmt}/resumes/{$token}.xls";
            ?>
              <tr>
                <td><?=h($r['claimed_at'] ?: $r['created_at'])?></td>
                <td><?=h($fmt)?></td>
                <td><?=h($r['job_id'] ?? '-')?></td>
                <td><a class="btn" href="<?=h($xlsUrl)?>" download>Excel</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
