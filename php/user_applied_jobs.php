<?php
// /home/it-future/www/itf/php/user_applied_jobs.php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/user_auth.php';

app_require_login('/php/user_applied_jobs.php');

$userId = (int)app_user_id();

$sql = "
  SELECT a.created_at, a.method,
         p.id AS job_id, p.title, p.company_name, p.job_location,
         CONCAT('/rireki/kaigo/resumes/', r.token, '.xls') AS xls_url

  FROM applicant_applications a
  JOIN posts p ON p.id = a.job_id
  LEFT JOIN applicant_resumes r ON r.id = a.resume_id
  WHERE a.user_id = ?
  ORDER BY a.created_at DESC, a.id DESC
  LIMIT 200
";
$st = $pdo->prepare($sql);
$st->execute([$userId]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>応募履歴</title>
  <style>
    body{ margin:0; font-family:system-ui,"Noto Sans JP",Meiryo; background:linear-gradient(180deg,#f8fbff,#eef6ff); padding:18px; }
    .wrap{ max-width:980px; margin:0 auto; }
    .top{ display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:12px; }
    .card{ background:#fff; border:1px solid #e6edf6; border-radius:16px; padding:14px; box-shadow:0 10px 24px rgba(0,0,0,.05); margin-bottom:12px; }
    .title{ font-weight:900; color:#0b3772; font-size:16px; margin:0 0 6px; }
    .meta{ color:#64748b; font-size:13px; display:flex; gap:12px; flex-wrap:wrap; }
    .btn{ display:inline-flex; padding:9px 12px; border-radius:10px; border:1px solid #bfe2ff; background:#1e90ff; color:#fff; text-decoration:none; font-weight:900; }
    .btn2{ display:inline-flex; padding:9px 12px; border-radius:10px; border:1px solid #e6edf6; background:#f3f9ff; color:#0c4a7a; text-decoration:none; font-weight:900; }
    .row{ display:flex; gap:10px; flex-wrap:wrap; margin-top:10px; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <h1 style="margin:0;">応募履歴</h1>
      <div class="row" style="margin:0;">
        <a class="btn2" href="/rireki/kaigo/php/rireki_preview.php">マイ情報</a>
        <a class="btn2" href="/saiyou.php">求人一覧</a>
        <a class="btn2" href="/php/user_login.php?logout=1&next=/saiyou.php">ログアウト</a>
      </div>
    </div>

    <?php if (!$rows): ?>
      <div class="card">まだ応募履歴がありません。</div>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <div class="card">
          <div class="title"><?=h($r['title'] ?? '')?></div>
          <div class="meta">
            <span>会社名: <?=h($r['company_name'] ?? '')?></span>
            <span>勤務地: <?=h($r['job_location'] ?? '')?></span>
            <span>方法: <?=h($r['method'] ?? '')?></span>
            <span>日時: <?=h($r['created_at'] ?? '')?></span>
          </div>
          <div class="row">
            <a class="btn2" href="/php/job_details.php?job_id=<?=h($r['job_id'] ?? '')?>">求人詳細</a>
            <?php if (!empty($r['xls_url'])): ?>
              <a class="btn" href="<?=h($r['xls_url'])?>" download>履歴書DL</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</body>
</html>
