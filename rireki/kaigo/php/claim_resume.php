<?php
// /home/it-future/www/itf/rireki/php/claim_resume.php
require_once __DIR__ . '/../../php/db_connect.php';
require_once __DIR__ . '/../../php/user_auth.php';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$token = $_GET['token'] ?? '';
$fmt   = $_GET['fmt'] ?? 'kaigo';

$token = is_string($token) ? strtolower(trim($token)) : '';
$fmt   = is_string($fmt) ? strtolower(trim($fmt)) : 'kaigo';

$allowedFmt = ['kaigo','basic'];
if (!in_array($fmt, $allowedFmt, true)) $fmt = 'kaigo';

if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
  http_response_code(400);
  echo "Invalid token.";
  exit;
}

// Require login
$next = '/rireki/php/claim_resume.php?token=' . urlencode($token) . '&fmt=' . urlencode($fmt);
app_require_login($next);

$userId = app_user_id();

// locate JSON snapshot + XLS url
$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
$jsonPath = $docRoot . "/rireki/{$fmt}/resumes/{$token}.json";
$xlsUrl   = "/rireki/{$fmt}/resumes/{$token}.xls";

if (!is_readable($jsonPath)) {
  http_response_code(404);
  $msg = "履歴書データが見つかりませんでした。（期限切れ/削除の可能性）";
} else {
  // read job_id if exists
  $jobId = null;
  $raw = @file_get_contents($jsonPath);
  if ($raw !== false) {
    $j = json_decode($raw, true);
    if (is_array($j) && isset($j['_job_id']) && (int)$j['_job_id'] > 0) $jobId = (int)$j['_job_id'];
  }

  // upsert into applicant_resumes
  try {
    // If exists, update ownership to this user (or just update updated_at)
    $stmt = $pdo->prepare("SELECT id, user_id FROM applicant_resumes WHERE fmt=? AND token=? LIMIT 1");
    $stmt->execute([$fmt, $token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
      // keep first owner? (optional). Here: allow re-claim by current user.
      $upd = $pdo->prepare("UPDATE applicant_resumes SET user_id=?, job_id=?, xls_url=?, json_path=? WHERE id=?");
      $upd->execute([(int)$userId, $jobId, $xlsUrl, $jsonPath, (int)$row['id']]);
      $msg = "アカウントに保存しました。（更新）";
    } else {
      $ins = $pdo->prepare("INSERT INTO applicant_resumes (user_id, fmt, token, job_id, xls_url, json_path) VALUES (?,?,?,?,?,?)");
      $ins->execute([(int)$userId, $fmt, $token, $jobId, $xlsUrl, $jsonPath]);
      $msg = "アカウントに保存しました。";
    }
    $ok = true;
  } catch (Throwable $e) {
    $ok = false;
    $msg = "保存に失敗しました。DBを確認してください。";
  }
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>履歴書を保存</title>
  <style>
    :root{ --bd:#e6edf6; --ink:#0b0f19; --muted:#667085; }
    body{ margin:0; font-family: system-ui,"Noto Sans JP",Meiryo,Arial; background:linear-gradient(180deg,#f8fbff,#eef6ff); color:var(--ink); padding:18px; }
    .wrap{ max-width:860px; margin:0 auto; }
    .card{ background:#fff; border:1px solid var(--bd); border-radius:16px; padding:16px; box-shadow:0 10px 24px rgba(0,0,0,.05); }
    .alert{ padding:10px 12px; border-radius:12px; margin:0 0 12px; }
    .ok{ background:#ecfdf5; border:1px solid #bbf7d0; color:#0b6b4a; font-weight:800; }
    .err{ background:#fff1f2; border:1px solid #fecdd3; color:#9f1239; font-weight:800; }
    .btn{ display:inline-flex; align-items:center; justify-content:center; padding:10px 14px; border-radius:10px; border:1px solid #bfe2ff; background:#1e90ff; color:#fff; font-weight:800; text-decoration:none; }
    .btn2{ display:inline-flex; align-items:center; justify-content:center; padding:10px 14px; border-radius:10px; border:1px solid var(--bd); background:#f3f9ff; color:#0c4a7a; font-weight:800; text-decoration:none; }
    .row{ display:flex; gap:10px; flex-wrap:wrap; margin-top:10px; }
    small{ color:var(--muted); }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1 style="margin:0 0 8px;">履歴書を保存</h1>

      <?php if (!empty($ok)): ?>
        <div class="alert ok"><?=h($msg)?></div>
      <?php else: ?>
        <div class="alert err"><?=h($msg ?? 'エラー')?></div>
      <?php endif; ?>

      <p style="margin:0 0 6px;"><small>token: <?=h($token)?> / fmt: <?=h($fmt)?></small></p>

      <div class="row">
        <a class="btn" href="<?=h($xlsUrl)?>" download>Excelをダウンロード</a>
        <a class="btn2" href="/saiyou.php">求人一覧へ戻る</a>
        <a class="btn2" href="<?=h('/php/user_login.php?logout=1&next='.urlencode('/saiyou.php'))?>">ログアウト</a>
      </div>

      <p style="margin-top:12px;color:var(--muted);font-size:13px;">
        ※ 次のステップ: 「マイページ（Applied jobs / My details）」作る時は、このテーブル applicant_resumes から一覧出せます。
      </p>
    </div>
  </div>
</body>
</html>
