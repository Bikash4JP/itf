<?php
// /home/it-future/www/itf/php/resume.php
declare(strict_types=1);

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/user_auth.php';

$pdo2 = app_pdo();
app_ensure_tables($pdo2);

$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
if ($job_id <= 0) { http_response_code(400); echo '無効な求人IDです。'; exit; }

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND post_type = 'job' LIMIT 1");
$stmt->execute([$job_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$job) { http_response_code(404); echo '求人が見つかりませんでした。'; exit; }

$isKaigo = isset($job['job_category']) && $job['job_category'] === '介護';

// if user wants new form
if ($isKaigo && isset($_GET['go']) && $_GET['go'] === 'new') {
  header('Location: /rireki/kaigo/rireki.php?job_id=' . urlencode((string)$job_id), true, 302);
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$loggedIn = app_is_logged_in();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>応募 - 株式会社アイティーエフ</title>
  <link rel="stylesheet" href="https://it-future.jp/css/common.css">
  <link rel="stylesheet" href="https://it-future.jp/css/footer.css">
  <style>
    :root{ --accent:#2a7de1; --muted:#6b7280; --bg:#f0fcfd }
    body{ background:#fff }
    .wrap{ max-width:1000px;margin:40px auto;padding:0 16px }
    .job-hero{
      background:var(--bg);
      border-radius:20px;
      padding:20px;
      box-shadow:0 2px 4px rgba(0,0,0,.1);
      display:flex;gap:12px;align-items:center
    }
    .badge{ font-size:.8rem;background:#e8f1ff;color:#0a5cc7;border:1px solid #cfe2ff;border-radius:999px;padding:4px 10px }
    .job-hero h1{ margin:6px 0 0 0;font-size:1.25rem;line-height:1.35;color:#083f75 }
    .job-meta{ color:var(--muted);font-size:.9rem;margin-top:6px;display:flex;gap:14px;flex-wrap:wrap }

    .grid{ display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:18px }
    @media (max-width:900px){ .grid{ grid-template-columns:1fr } }

    .card{
      background:#fff;border:1px solid #e6edf6;border-radius:16px;padding:20px;
      box-shadow:0 3px 10px rgba(10,60,150,.05);display:flex;flex-direction:column
    }
    .card h2{ margin:0 0 6px 0;font-size:1.5rem;color:#0b3772;font-weight: bold; }
    .card p{ margin:0 0 14px 0;color:#4b5563;font-size:.95rem }
    .actions{ margin-top:auto;display:flex;gap:10px;flex-wrap:wrap }
    .btn{
      display:inline-flex;align-items:center;justify-content:center;gap:8px;
      padding:10px 16px;border-radius:10px;text-decoration:none;border:1px solid transparent;
      font-weight:800;cursor:pointer
    }
    .btn-primary{ background:var(--accent);color:#fff }
    .btn-ghost{ background:#fff;border-color:#dbe7f5;color:#0b3772 }
    .btn-ghost:hover{ background:#f7fbff }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="job-hero">
      <span class="badge"><?php echo $isKaigo ? '介護求人' : '求人'; ?></span>
      <div>
        <h1><?php echo h($job['title'] ?? ''); ?></h1>
        <div class="job-meta">
          <span>会社名：<?php echo h($job['company_name'] ?? ''); ?></span>
          <span>勤務地：<?php echo h($job['job_location'] ?? ''); ?></span>
          <span>雇用形態：<?php echo h($job['job_type'] ?? ''); ?></span>
          <span>給与：<?php echo h($job['salary'] ?? ''); ?></span>
        </div>
      </div>
    </div>

    <div class="grid">

      <!-- ✅ APPLY USING PROFILE (replaces old "upload should be here" main card) -->
      <div class="card">
        <h2>プロフィールで応募</h2>

        <?php if (!$loggedIn): ?>
          <p>ログインすると、登録した履歴書情報（マイ情報）でそのまま応募できます。</p>
          <div class="actions">
            <a class="btn btn-primary" href="/php/user_login.php?next=<?=h('/php/resume.php?job_id='.$job_id)?>">ログイン / 登録</a>
            <a class="btn btn-ghost" href="/php/job_details.php?job_id=<?php echo (int)$job_id; ?>">求人詳細に戻る</a>
          </div>
        <?php else: ?>
          <p>保存済みの「マイ情報」から自動で履歴書を作成し、応募できます。</p>
          <div class="actions">
            <a class="btn btn-primary" href="/rireki/kaigo/php/rireki_preview.php?job_id=<?php echo (int)$job_id; ?>">プロフィールで進む</a>
            <a class="btn btn-ghost" href="/rireki/kaigo/rireki.php?job_id=<?php echo (int)$job_id; ?>">内容を新規入力</a>
          </div>
        <?php endif; ?>
      </div>

      <!-- Create new resume (keep as option) -->
      <div class="card">
        <h2>新規で履歴書を作成</h2>
        <?php if ($isKaigo): ?>
          <p>フォームに沿って入力し、<strong>介護向け履歴書</strong>を自動作成できます。</p>
          <div class="actions">
            <a class="btn btn-primary" href="/php/resume.php?job_id=<?php echo (int)$job_id; ?>&go=new">新規で作成する</a>
            <a class="btn btn-ghost" href="/php/job_details.php?job_id=<?php echo (int)$job_id; ?>">求人詳細に戻る</a>
          </div>
        <?php else: ?>
          <p>この求人は介護カテゴリではありません（暫定で介護フォームを使用）。</p>
          <div class="actions">
            <a class="btn btn-primary" href="/rireki/kaigo/rireki.php?job_id=<?php echo (int)$job_id; ?>">フォームへ進む</a>
            <a class="btn btn-ghost" href="/php/job_details.php?job_id=<?php echo (int)$job_id; ?>">求人詳細に戻る</a>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</body>
</html>
