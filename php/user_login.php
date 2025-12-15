<?php
// /home/it-future/www/itf/php/user_login.php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/user_auth.php';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$next = $_GET['next'] ?? '/saiyou.php';
if (!is_string($next) || $next === '') $next = '/saiyou.php';

// basic open-redirect protection: allow only local paths
if (!preg_match('#^/[^\\r\\n]*$#', $next)) $next = '/saiyou.php';

$err = '';
$ok  = '';

if (isset($_GET['logout'])) {
  app_logout();
  app_redirect('/php/user_login.php?next=' . urlencode($next));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $mode = $_POST['mode'] ?? 'login';

  if ($mode === 'register') {
    $username = trim((string)($_POST['username'] ?? ''));
    $email    = trim((string)($_POST['email'] ?? ''));
    $pass     = (string)($_POST['password'] ?? '');

    if ($username === '' || $email === '' || $pass === '') {
      $err = '全て入力してください。';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $err = 'メールアドレスの形式が正しくありません。';
    } elseif (mb_strlen($username, 'UTF-8') < 3) {
      $err = 'ユーザー名は3文字以上にしてください。';
    } elseif (strlen($pass) < 8) {
      $err = 'パスワードは8文字以上にしてください。';
    } else {
      try {
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO applicant_users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hash]);

        $uid = (int)$pdo->lastInsertId();
        app_login($uid);
        app_redirect($next);
      } catch (Throwable $e) {
        // likely duplicate
        $err = '登録できませんでした。ユーザー名またはメールが既に使われている可能性があります。';
      }
    }
  } else {
    // login
    $login = trim((string)($_POST['login'] ?? '')); // username OR email
    $pass  = (string)($_POST['password'] ?? '');

    if ($login === '' || $pass === '') {
      $err = 'ユーザー名（またはメール）とパスワードを入力してください。';
    } else {
      $stmt = $pdo->prepare("SELECT id, password_hash FROM applicant_users WHERE username = ? OR email = ? LIMIT 1");
      $stmt->execute([$login, $login]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$row || empty($row['password_hash']) || !password_verify($pass, $row['password_hash'])) {
        $err = 'ログイン情報が正しくありません。';
      } else {
        app_login((int)$row['id']);
        app_redirect($next);
      }
    }
  }
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>ユーザーログイン（応募者）</title>
  <style>
    :root{ --bd:#e6edf6; --ink:#0b0f19; --muted:#667085; --bg:#f6fbff; }
    body{ margin:0; font-family: system-ui,"Noto Sans JP",Meiryo,Arial; background:linear-gradient(180deg,#f8fbff,#eef6ff); color:var(--ink); }
    .wrap{ max-width:980px; margin:0 auto; padding:18px; }
    .grid{ display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    @media(max-width:920px){ .grid{ grid-template-columns:1fr; } }
    .card{ background:#fff; border:1px solid var(--bd); border-radius:16px; padding:16px; box-shadow:0 10px 24px rgba(0,0,0,.05); }
    h1{ margin:0 0 8px; font-size:20px; }
    .muted{ color:var(--muted); margin:0 0 12px; font-size:13px; }
    label{ display:block; font-weight:700; margin:10px 0 6px; }
    input{ width:100%; padding:10px 12px; border:1px solid var(--bd); border-radius:10px; font-size:14px; }
    .btn{ display:inline-flex; align-items:center; justify-content:center; padding:10px 14px; border-radius:10px; border:1px solid #bfe2ff; background:#1e90ff; color:#fff; font-weight:800; cursor:pointer; }
    .btn2{ display:inline-flex; align-items:center; justify-content:center; padding:10px 14px; border-radius:10px; border:1px solid var(--bd); background:#f3f9ff; color:#0c4a7a; font-weight:800; cursor:pointer; text-decoration:none; }
    .row{ display:flex; gap:10px; flex-wrap:wrap; margin-top:12px; }
    .alert{ padding:10px 12px; border-radius:12px; margin:0 0 12px; }
    .err{ background:#fff1f2; border:1px solid #fecdd3; color:#9f1239; }
    .ok{ background:#ecfdf5; border:1px solid #bbf7d0; color:#0b6b4a; }
    small{ color:var(--muted); }
  </style>
</head>
<body>
  <div class="wrap">
    <h1>応募者ログイン</h1>
    <p class="muted">保存した履歴書の再ダウンロードや、応募履歴の確認に使います。</p>

    <?php if ($err): ?><div class="alert err"><?=h($err)?></div><?php endif; ?>
    <?php if ($ok): ?><div class="alert ok"><?=h($ok)?></div><?php endif; ?>

    <div class="grid">
      <div class="card">
        <h2 style="margin:0 0 8px;">ログイン</h2>
        <form method="post" action="">
          <input type="hidden" name="mode" value="login">
          <label>ユーザー名 または メール</label>
          <input name="login" autocomplete="username" required>
          <label>パスワード</label>
          <input type="password" name="password" autocomplete="current-password" required>
          <div class="row">
            <button class="btn" type="submit">ログイン</button>
            <a class="btn2" href="<?=h('/php/user_login.php?logout=1&next='.urlencode($next))?>">ログアウト</a>
          </div>
          <p class="muted" style="margin-top:10px;">遷移先: <small><?=h($next)?></small></p>
        </form>
      </div>

      <div class="card">
        <h2 style="margin:0 0 8px;">新規登録</h2>
        <form method="post" action="">
          <input type="hidden" name="mode" value="register">
          <label>ユーザー名</label>
          <input name="username" autocomplete="username" required>
          <label>メール</label>
          <input name="email" type="email" autocomplete="email" required>
          <label>パスワード（8文字以上）</label>
          <input type="password" name="password" autocomplete="new-password" required>
          <div class="row">
            <button class="btn" type="submit">登録して続ける</button>
            <a class="btn2" href="<?=h($next)?>">今はしない</a>
          </div>
          <p class="muted" style="margin-top:10px;">※ パスワードは暗号化して保存されます。</p>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
