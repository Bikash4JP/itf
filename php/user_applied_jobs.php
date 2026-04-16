<?php
// /home/it-future/www/itf/php/user_applied_jobs.php
declare(strict_types=1);

require_once __DIR__ . '/user_auth.php';
$pdo = app_pdo();
app_ensure_tables($pdo);

app_require_login('/php/user_applied_jobs.php');

$uid = app_user_id();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ── Handle DELETE actions ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'delete_one') {
    $id = (int)($_POST['app_id'] ?? 0);
    if ($id > 0) {
      $pdo->prepare("DELETE FROM app_applications WHERE id = ? AND user_id = ?")->execute([$id, $uid]);
    }
  } elseif ($action === 'delete_all') {
    $pdo->prepare("DELETE FROM app_applications WHERE user_id = ?")->execute([$uid]);
  }
  header('Location: /php/user_applied_jobs.php');
  exit;
}

// ── Fetch applications ───────────────────────────────────────────
$sql = "
  SELECT
    a.id,
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
    .btn{display:inline-flex;gap:8px;align-items:center;justify-content:center;padding:8px 12px;border-radius:10px;border:1px solid #dbe7f5;background:#fff;color:#0b3772;font-weight:800;text-decoration:none;cursor:pointer;font-size:13px}
    .btn.danger{color:#dc2626;border-color:rgba(220,38,38,.3);background:rgba(220,38,38,.06)}
    .btn.danger:hover{background:rgba(220,38,38,.12)}
    .toprow{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center}
    .muted{color:#6b7280}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="toprow" style="margin-bottom:12px">
      <h2 style="margin:0">応募履歴</h2>
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <?php if ($rows): ?>
          <form method="POST" onsubmit="return confirm('応募履歴をすべて削除してよろしいですか？')">
            <input type="hidden" name="action" value="delete_all">
            <button type="submit" class="btn danger">🗑 すべて削除</button>
          </form>
        <?php endif; ?>
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
        <div class="card">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
            <div style="flex:1">
              <div style="font-weight:900;font-size:18px"><?=h($r['title'] ?? '求人')?></div>
              <div class="muted"><?=h($r['job_location'] ?? '')?><?= ($r['job_location'] && $r['salary']) ? ' / ' : '' ?><?=h($r['salary'] ?? '')?></div>
              <div class="muted" style="margin-top:6px">応募日: <?=h($r['created_at'] ?? '')?></div>
            </div>
            <form method="POST" onsubmit="return confirm('この応募履歴を削除しますか？')" style="flex-shrink:0">
              <input type="hidden" name="action" value="delete_one">
              <input type="hidden" name="app_id" value="<?=(int)$r['id']?>">
              <button type="submit" class="btn danger" style="padding:6px 10px;font-size:12px">🗑</button>
            </form>
          </div>
          <div style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap">
            <a class="btn" href="/php/job_details.php?job_id=<?=(int)($r['job_id'] ?? 0)?>">求人詳細</a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</body>
</html>
