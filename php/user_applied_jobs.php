<?php
// /home/it-future/www/itf/php/user_applied_jobs.php
declare(strict_types=1);

require_once __DIR__ . '/user_auth.php';
$pdo = app_pdo();
app_ensure_tables($pdo);

app_require_login('/php/user_applied_jobs.php');

$uid = app_user_id();

$sql = "
  SELECT
    a.created_at,
    a.job_id,
    a.resume_token,
    p.title,
    p.company_name,
    p.job_location,
    p.salary
  FROM ".APP_TBL_APPLICATIONS." a
  LEFT JOIN posts p ON p.id = a.job_id
  WHERE a.user_id = ?
  ORDER BY a.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$uid]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>応募履歴</title>
  <link rel="stylesheet" href="/css/common.css">
  <style>
    .wrap{max-width:1100px;margin:30px auto;padding:0 16px}
    .card{background:#fff;border:1px solid #e6edf6;border-radius:16px;padding:18px;box-shadow:0 3px 10px rgba(10,60,150,.05);margin-bottom:12px}
    .btn{display:inline-flex;gap:8px;align-items:center;justify-content:center;padding:8px 12px;border-radius:10px;border:1px solid #dbe7f5;background:#fff;color:#0b3772;font-weight:800;text-decoration:none}
    .toprow{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap}
    .muted{color:#6b7280}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="toprow" style="margin-bottom:12px">
      <h2 style="margin:0">応募履歴</h2>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a class="btn" href="/rireki/kaigo/php/rireki_preview.php">マイ情報</a>
        <a class="btn" href="/saiyou.php">求人一覧へ</a>
      </div>
    </div>

    <?php if (!$rows): ?>
      <div class="card">
        <div class="muted">まだ応募履歴がありません。</div>
      </div>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <?php
          $token = (string)($r['resume_token'] ?? '');
          $xlsUrl = ($token && preg_match('/^[a-f0-9]{32}$/', $token))
            ? ('/rireki/kaigo/resumes/'.$token.'.xls')
            : '';
        ?>
        <div class="card">
          <div style="font-weight:900;font-size:18px"><?=h($r['title'] ?? '求人')?></div>
          <div class="muted">
            <?=h($r['job_location'] ?? '')?> / <?=h($r['salary'] ?? '')?>
          </div>
          <div class="muted" style="margin-top:6px">応募日: <?=h($r['created_at'] ?? '')?></div>

          <div style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap">
            <a class="btn" href="/php/job_details.php?job_id=<?= (int)($r['job_id'] ?? 0) ?>">求人詳細</a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</body>
</html>
