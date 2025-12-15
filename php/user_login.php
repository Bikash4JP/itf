<?php
// /home/it-future/www/itf/php/user_login.php
declare(strict_types=1);

require_once __DIR__ . '/user_auth.php';

$pdo = app_pdo();
app_ensure_tables($pdo);

$next = (string)($_GET['next'] ?? '/saiyou.php');
$mode = (string)($_POST['mode'] ?? '');

$err = '';
$ok  = '';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if ($mode === 'register') {
    $username = trim((string)($_POST['username'] ?? ''));
    $email    = trim((string)($_POST['email'] ?? ''));
    $pass     = (string)($_POST['password'] ?? '');

    if ($username === '' || $email === '' || $pass === '') {
      $err = '全て入力してください。';
    } else {
      $hash = password_hash($pass, PASSWORD_DEFAULT);

      try {
        $stmt = $pdo->prepare("INSERT INTO ".APP_TBL_USERS." (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hash]);
        $uid = (int)$pdo->lastInsertId();
        app_login_user_id($uid);

        // ✅ register → details form page
        header('Location: /rireki/kaigo/rireki.php', true, 302);
        exit;

      } catch (Throwable $e) {
        $err = '登録に失敗しました（同じユーザー名/メールが既に存在する可能性があります）。';
      }
    }

  } elseif ($mode === 'login') {
    $email = trim((string)($_POST['email'] ?? ''));
    $pass  = (string)($_POST['password'] ?? '');

    $stmt = $pdo->prepare("SELECT id, password_hash FROM ".APP_TBL_USERS." WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($pass, (string)$row['password_hash'])) {
      $err = 'メールアドレスまたはパスワードが違います。';
    } else {
      app_login_user_id((int)$row['id']);
      header('Location: ' . $next, true, 302);
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>ユーザーログイン｜株式会社アイティーエフ</title>
  <link rel="stylesheet" href="/css/common.css">
  <link rel="stylesheet" href="/css/login.css">
  <style>
    .wrap{max-width:980px;margin:30px auto;padding:0 16px}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    @media(max-width:900px){.grid{grid-template-columns:1fr}}
    .card{background:#fff;border:1px solid #e6edf6;border-radius:16px;padding:18px;box-shadow:0 3px 10px rgba(10,60,150,.05)}
    .card h2{margin:0 0 10px;font-size:20px}
    label{display:block;margin:10px 0 6px;font-weight:700}
    input{width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px}
    .btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border-radius:10px;border:1px solid transparent;background:#2a7de1;color:#fff;font-weight:800;cursor:pointer;text-decoration:none}
    .btn.ghost{background:#fff;color:#0b3772;border-color:#dbe7f5}
    .msg{margin:12px 0;padding:10px 12px;border-radius:10px}
    .err{background:#fff1f2;border:1px solid #fecdd3;color:#9f1239}
  </style>
</head>
<body>
  <div class="wrap">
    <?php if ($err): ?><div class="msg err"><?=h($err)?></div><?php endif; ?>

    <div class="grid">
      <section class="card">
        <h2>ログイン</h2>
        <form method="post" action="/php/user_login.php?next=<?=h($next)?>">
          <input type="hidden" name="mode" value="login">
          <label>メールアドレス</label>
          <input type="email" name="email" required>
          <label>パスワード</label>
          <input type="password" name="password" required>
          <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap">
            <button class="btn" type="submit">ログイン</button>
            <a class="btn ghost" href="<?=h($next)?>">戻る</a>
          </div>
        </form>
      </section>

      <section class="card">
        <h2>新規登録（無料）</h2>
        <form method="post" action="/php/user_login.php?next=<?=h($next)?>">
          <input type="hidden" name="mode" value="register">
          <label>ユーザー名</label>
          <input type="text" name="username" required>
          <label>メールアドレス</label>
          <input type="email" name="email" required>
          <label>パスワード</label>
          <input type="password" name="password" required>
          <div style="margin-top:12px">
            <button class="btn" type="submit">登録して続ける</button>
          </div>
          <p style="margin-top:10px;color:#6b7280;font-size:13px">
            ※ 登録後、履歴書フォームへ進みます。
          </p>
        </form>
      </section>
    </div>
  </div>
</body>
</html>
