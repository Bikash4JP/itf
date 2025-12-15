<?php
// /home/it-future/www/itf/php/apply_with_profile.php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/user_auth.php';

$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$fmt = isset($_GET['fmt']) ? strtolower(trim((string)$_GET['fmt'])) : 'kaigo';
if (!in_array($fmt, ['kaigo','basic'], true)) $fmt = 'kaigo';

if ($job_id <= 0) { http_response_code(400); echo '無効な求人IDです。'; exit; }

$next = '/php/apply_with_profile.php?job_id='.(int)$job_id.'&fmt='.urlencode($fmt);
app_require_login($next);

$userId = (int)app_user_id();

// job exists?
$st = $pdo->prepare("SELECT id,title FROM posts WHERE id=? AND post_type='job' LIMIT 1");
$st->execute([$job_id]);
$job = $st->fetch(PDO::FETCH_ASSOC);
if (!$job) { http_response_code(404); echo '求人が見つかりませんでした。'; exit; }

// latest resume for this user+fmt
$st2 = $pdo->prepare("SELECT id, token, xls_url FROM applicant_resumes WHERE user_id=? AND fmt=? ORDER BY updated_at DESC, id DESC LIMIT 1");
$st2->execute([$userId, $fmt]);
$res = $st2->fetch(PDO::FETCH_ASSOC);
if (!$res) {
  header('Location: /php/resume.php?job_id='.(int)$job_id, true, 302);
  exit;
}

// insert applied record
$ins = $pdo->prepare("INSERT INTO applicant_applications (user_id, job_id, method, resume_id) VALUES (?,?,?,?)");
$ins->execute([$userId, $job_id, 'profile', (int)$res['id']]);

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>応募完了</title>
  <style>
    body{ margin:0; font-family:system-ui,"Noto Sans JP",Meiryo; background:linear-gradient(180deg,#f8fbff,#eef6ff); padding:18px; }
    .wrap{ max-width:860px; margin:0 auto; }
    .card{ background:#fff; border:1px solid #e6edf6; border-radius:16px; padding:16px; box-shadow:0 10px 24px rgba(0,0,0,.05); }
    .ok{ background:#ecfdf5; border:1px solid #bbf7d0; color:#0b6b4a; padding:10px 12px; border-radius:12px; font-weight:900; }
    .row{ display:flex; gap:10px; flex-wrap:wrap; margin-top:12px; }
    .btn{ display:inline-flex; justify-content:center; align-items:center; padding:10px 14px; border-radius:10px; text-decoration:none; font-weight:900; border:1px solid #bfe2ff; background:#1e90ff; color:#fff; }
    .btn2{ display:inline-flex; justify-content:center; align-items:center; padding:10px 14px; border-radius:10px; text-decoration:none; font-weight:900; border:1px solid #e6edf6; background:#f3f9ff; color:#0c4a7a; }
    small{ color:#64748b; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="ok">応募を受け付けました。</div>
      <p style="margin:10px 0 0;">
        求人：<strong><?=h($job['title'] ?? '')?></strong>
      </p>
      <p style="margin:6px 0 0;"><small>保存済み履歴書（fmt: <?=h($fmt)?> / token: <?=h($res['token'] ?? '')?>）で応募しました。</small></p>
      <div class="row">
        <a class="btn" href="<?=h($res['xls_url'] ?? '#')?>" download>履歴書をダウンロード</a>
        <a class="btn2" href="/php/user_applied_jobs.php">応募履歴を見る</a>
        <a class="btn2" href="/saiyou.php">求人一覧へ戻る</a>
      </div>
    </div>
  </div>
</body>
</html>
